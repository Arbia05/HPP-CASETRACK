<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('lawyer','pengacara_pj');
$user = currentUser();
$db   = getDB();

$caseId = (int)($_GET['case_id'] ?? 0);
$cases  = $db->prepare("SELECT c.*, u.name AS client_name FROM cases c JOIN users u ON c.client_id=u.id JOIN case_team ct ON c.id=ct.case_id WHERE ct.lawyer_id=? AND c.status='active' ORDER BY c.title");
$cases->execute([$user['id']]); $myCases = $cases->fetchAll();

// If PJ, also show cases where they are PJ
if ($user['role'] === 'pengacara_pj') {
    $pjCases = $db->prepare("SELECT c.*, u.name AS client_name FROM cases c JOIN users u ON c.client_id=u.id WHERE c.assigned_pj_id=? AND c.status='active'");
    $pjCases->execute([$user['id']]);
    $pjList = $pjCases->fetchAll();
    $myCases = array_merge($myCases, $pjList);
    // deduplicate
    $myCases = array_values(array_reduce($myCases, function($carry, $item) {
        if (!isset($carry[$item['id']])) $carry[$item['id']] = $item;
        return $carry;
    }, []));
}

$error = $success = '';

if (isPost()) {
    $caseIdPost   = (int)($_POST['case_id'] ?? 0);
    $statusLabel  = sanitize($_POST['status_label'] ?? '');
    $descTech     = sanitize($_POST['description_technical'] ?? '');
    $descLayman   = sanitize($_POST['description_layman'] ?? '');
    $lat          = $_POST['latitude'] ?? null;
    $lng          = $_POST['longitude'] ?? null;
    $locName      = sanitize($_POST['location_name'] ?? '');
    $updateType   = sanitize($_POST['update_type'] ?? 'status');

    if (!$caseIdPost || !$statusLabel || !$descLayman) {
        $error = 'Pilih kasus, status, dan deskripsi wajib diisi.';
    } else {
        $evidencePath = null;
        if (!empty($_FILES['evidence']['name'])) {
            $upload = handleUpload($_FILES['evidence'], 'evidence', ['jpg','jpeg','png','pdf','docx']);
            if ($upload['success']) $evidencePath = $upload['path'];
            else { $error = $upload['message']; goto end_process; }
        }

        $stmt = $db->prepare("INSERT INTO case_activities (case_id, user_id, activity_type, description, latitude, longitude, location, evidence_path) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$caseIdPost, $user['id'], $updateType, $descTech ?: $descLayman, $lat ?: null, $lng ?: null, $locName, $evidencePath]);

        // Update case updated_at
        $db->prepare("UPDATE cases SET updated_at=NOW() WHERE id=?")->execute([$caseIdPost]);

        // Notify client
        $caseData = $db->prepare("SELECT client_id, title FROM cases WHERE id=?");
        $caseData->execute([$caseIdPost]); $cData = $caseData->fetch();
        createNotification($cData['client_id'], 'Update Kasus: ' . $statusLabel, $descLayman, 'case_update', $caseIdPost, 'case');

        $success = 'Update kasus berhasil disimpan!';
    }
    end_process:
}

$pageTitle = 'Update Aktivitas Lapangan';
$base = getBasePath();
$roleDir = $user['role'] === 'pengacara_pj' ? 'pengacara-pj' : 'lawyer';

include __DIR__ . '/../includes/header.php';
?>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-5xl mx-auto">

  <div class="page-header">
    <h1>Update Aktivitas Lapangan</h1>
    <p>Catat progres kasus beserta lokasi aktivitas saat ini.</p>
  </div>

  <?php if ($error): ?><div class="alert alert-error mb-6"><?= $error ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success mb-6"><?= $success ?></div><?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

    <!-- Form -->
    <div class="lg:col-span-3">
      <div class="card p-6">
        <form method="POST" enctype="multipart/form-data" class="space-y-5">

          <!-- Case Selector -->
          <div>
            <label class="form-label">Pilih Kasus <span class="text-red-500">*</span></label>
            <div class="relative">
              <select name="case_id" required class="form-input appearance-none pr-10"
                onchange="this.form.case_id_display.value = this.options[this.selectedIndex].text">
                <option value="">-- Pilih kasus --</option>
                <?php foreach ($myCases as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $caseId == $c['id'] ? 'selected' : '' ?>>
                  [<?= $c['reference_number'] ?>] <?= sanitize($c['title']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[#8e706c] pointer-events-none">expand_more</span>
            </div>
          </div>

          <!-- Update Type -->
          <div>
            <label class="form-label">Jenis Update <span class="text-red-500">*</span></label>
            <div class="grid grid-cols-3 gap-2">
              <?php foreach (['status'=>['Status Kasus','gavel'],'field_activity'=>['Aktivitas Lapangan','my_location'],'document'=>['Upload Dokumen','description']] as $val => [$lbl,$ico]): ?>
              <label class="flex flex-col items-center gap-1.5 p-3 border-2 rounded-xl cursor-pointer transition-all
                has-[:checked]:border-[#800000] has-[:checked]:bg-[#fff1f1] border-[#e1e3e4] hover:border-[#800000]">
                <input type="radio" name="update_type" value="<?= $val ?>" class="hidden" <?= $val==='status' ? 'checked' : '' ?>/>
                <span class="material-symbols-outlined text-[#800000] text-[22px]"><?= $ico ?></span>
                <span class="text-xs font-semibold text-center text-[#191c1d]"><?= $lbl ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Status Label -->
          <div>
            <label class="form-label">Status / Tahapan Hukum <span class="text-red-500">*</span></label>
            <div class="relative">
              <select name="status_label" required class="form-input appearance-none pr-10">
                <option value="">-- Pilih status --</option>
                <?php foreach (['Somasi Dikirim','Negosiasi Awal','Gugatan Didaftarkan','Sidang Perdana','Mediasi','Putusan Sela','Persidangan Pokok','Pembacaan Putusan','Banding Diajukan','Eksekusi Putusan','Kesepakatan Damai','Kasus Ditutup','Lainnya'] as $s): ?>
                <option value="<?= $s ?>"><?= $s ?></option>
                <?php endforeach; ?>
              </select>
              <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[#8e706c] pointer-events-none">expand_more</span>
            </div>
          </div>

          <!-- Deskripsi Teknis -->
          <div>
            <label class="form-label">Deskripsi Teknis (Internal)</label>
            <textarea name="description_technical" rows="3" placeholder="Catatan teknis hukum (hanya terlihat oleh tim internal)..."
              class="form-input resize-none"></textarea>
          </div>

          <!-- Deskripsi Awam (untuk klien) -->
          <div>
            <label class="form-label">Deskripsi untuk Klien <span class="text-red-500">*</span></label>
            <textarea name="description_layman" rows="3" required placeholder="Jelaskan perkembangan dalam bahasa yang mudah dipahami klien awam..."
              class="form-input resize-none"></textarea>
            <p class="text-xs text-[#8e706c] mt-1">⚠ Teks ini yang akan dibaca klien. Gunakan bahasa non-teknis.</p>
          </div>

          <!-- Upload Evidence -->
          <div>
            <label class="form-label">Bukti / Dokumen Lapangan</label>
            <div id="ev-zone" class="border-2 border-dashed border-[#e2bfb9] rounded-xl p-5 text-center cursor-pointer hover:border-[#800000] transition-all">
              <span class="material-symbols-outlined text-[#800000] text-2xl">upload_file</span>
              <p class="text-sm text-[#5a413d] mt-1">Foto sidang, berita acara, surat, dll.</p>
              <input type="file" id="evidence" name="evidence" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.docx"/>
            </div>
            <div id="ev-preview" class="mt-2"></div>
          </div>

          <!-- Hidden geolocation fields -->
          <input type="hidden" name="latitude" id="lat-input"/>
          <input type="hidden" name="longitude" id="lng-input"/>
          <input type="hidden" name="location_name" id="loc-name-input"/>

          <!-- Geolocation Button -->
          <div class="bg-[#f3f4f5] rounded-xl p-4">
            <div class="flex items-center justify-between gap-3 mb-2">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#800000] text-[20px]">my_location</span>
                <span class="font-semibold text-sm">Tandai Lokasi Saat Ini</span>
              </div>
              <button type="button" id="get-location-btn" onclick="getLocation()"
                class="btn-outline btn-sm">
                <span class="material-symbols-outlined text-[16px]">gps_fixed</span>
                Deteksi GPS
              </button>
            </div>
            <p id="location-status" class="text-xs text-[#8e706c]">Klik "Deteksi GPS" untuk menandai lokasi aktivitas lapangan.</p>
          </div>

          <button type="submit" class="btn-primary w-full justify-center py-3 text-base">
            <span class="material-symbols-outlined">save</span>
            Simpan Update Kasus
          </button>
        </form>
      </div>
    </div>

    <!-- Map -->
    <div class="lg:col-span-2 space-y-5">
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-[#e2bfb9] flex items-center gap-2">
          <span class="material-symbols-outlined text-[#800000] text-[20px]">map</span>
          <h2 class="font-bold text-[#191c1d]">Peta Lokasi</h2>
        </div>
        <div id="map" class="h-64 md:h-80 w-full bg-[#f3f4f5]"></div>
        <div class="px-5 py-3 border-t border-[#e2bfb9]">
          <p id="coords-display" class="text-xs text-[#8e706c]">
            <span class="material-symbols-outlined text-[14px]">info</span>
            Koordinat GPS akan muncul setelah deteksi lokasi.
          </p>
        </div>
      </div>

      <!-- Recent updates -->
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-4 text-sm">Update Terakhir Saya</h2>
        <?php
        $recentUpdates = $db->prepare("SELECT cu.*, c.title AS case_title FROM case_activities cu JOIN cases c ON cu.case_id=c.id WHERE cu.user_id=? ORDER BY cu.created_at DESC LIMIT 4");
        $recentUpdates->execute([$user['id']]);
        $rUpdates = $recentUpdates->fetchAll();
        ?>
        <?php if (empty($rUpdates)): ?>
        <p class="text-sm text-[#8e706c] text-center py-4">Belum ada update.</p>
        <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($rUpdates as $ru): ?>
          <div class="flex gap-3 py-2 border-b border-[#edeeef] last:border-0">
            <div class="w-2 h-2 rounded-full bg-[#800000] mt-2 shrink-0"></div>
            <div>
              <p class="text-xs font-semibold text-[#800000]"><?= sanitize($ru['status_label']) ?></p>
              <p class="text-xs text-[#5a413d] truncate"><?= sanitize($ru['case_title']) ?></p>
              <p class="text-[10px] text-[#8e706c]"><?= timeAgo($ru['created_at']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= $base ?>assets/js/map.js"></script>
<script>initDropzone('ev-zone','evidence','ev-preview');</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
