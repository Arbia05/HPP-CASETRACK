<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');
$user = currentUser();
$db   = getDB();

$error = '';
if (isPost()) {
    $category    = sanitize($_POST['category'] ?? '');
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');

    if (!$category || !$title || !$description) {
        $error = 'Semua field wajib diisi.';
    } else {
        $attachPath = null;
        if (!empty($_FILES['attachment']['name'])) {
            $upload = handleUpload($_FILES['attachment'], 'documents');
            if (!$upload['success']) { $error = $upload['message']; }
            else $attachPath = $upload['path'];
        }

        if (!$error) {
            $stmt = $db->prepare("INSERT INTO complaints (client_id, case_category, title, description, attachment_path, status) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$user['id'], $category, $title, $description, $attachPath, 'new']);
            $complaintId = $db->lastInsertId();

            // Notify legal admins
            $admins = $db->query("SELECT id FROM users WHERE role='legal_admin' AND is_active=1")->fetchAll();
            foreach ($admins as $a) {
                createNotification($a['id'], 'Pengaduan Baru Masuk', 'Pengaduan baru dari ' . $user['name'] . ': ' . $title, 'complaint', $complaintId, 'complaint');
            }

            // Store in session for step 2
            session_start();
            $_SESSION['complaint_id'] = $complaintId;
            redirect('/hpp-casetrack/client/new-case-step2.php');
        }
    }
}

$pageTitle = 'Ajukan Kasus Baru — Langkah 1';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-3xl mx-auto">

  <div class="page-header">
    <h1>Ajukan Kasus Baru</h1>
    <p>Ceritakan permasalahan hukum Anda. Tim HPP Law Office akan segera merespons.</p>
  </div>

  <!-- Stepper -->
  <div class="flex items-center mb-10">
    <?php foreach ([
      ['1','Informasi Kasus','active'],
      ['2','Upload Dokumen','inactive'],
      ['3','Konfirmasi','inactive'],
    ] as [$num,$label,$state]): ?>
    <div class="flex items-center <?= $state==='active' ? 'flex-1' : 'flex-1 opacity-40' ?>">
      <div class="flex items-center gap-2 shrink-0">
        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
          <?= $state==='active' ? 'bg-[#800000] text-white' : 'bg-[#e1e3e4] text-[#5a413d]' ?>">
          <?= $num ?>
        </div>
        <span class="text-xs font-semibold hidden md:block <?= $state==='active' ? 'text-[#800000]' : 'text-[#5a413d]' ?> uppercase tracking-wider">
          <?= $label ?>
        </span>
      </div>
      <?php if ($num < 3): ?>
      <div class="flex-1 h-px bg-[#e1e3e4] mx-3"></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($error): ?><div class="alert alert-error mb-6"><?= $error ?></div><?php endif; ?>

  <div class="card p-6 md:p-8">
    <form method="POST" enctype="multipart/form-data" class="space-y-6">

      <!-- Category -->
      <div>
        <label class="form-label" for="category">Jenis/Kategori Kasus <span class="text-red-500">*</span></label>
        <div class="relative">
          <select id="category" name="category" required class="form-input appearance-none pr-10">
            <option value="">-- Pilih kategori --</option>
            <?php foreach (['Sengketa Tanah & Properti','Hukum Pidana','Hukum Keluarga & Perkawinan','Hukum Bisnis & Korporat','Hukum Ketenagakerjaan','Tata Usaha Negara (PTUN)','Hak Kekayaan Intelektual','Lainnya'] as $cat): ?>
            <option value="<?= $cat ?>" <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
          <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[#8e706c] pointer-events-none">expand_more</span>
        </div>
      </div>

      <!-- Title -->
      <div>
        <label class="form-label" for="title">Judul / Ringkasan Kasus <span class="text-red-500">*</span></label>
        <input type="text" id="title" name="title" required
          value="<?= sanitize($_POST['title'] ?? '') ?>"
          placeholder="cth: Sengketa kepemilikan tanah kavling di Jakarta Selatan"
          class="form-input"/>
        <p class="text-xs text-[#8e706c] mt-1">Buat ringkasan singkat yang menggambarkan inti permasalahan Anda.</p>
      </div>

      <!-- Description -->
      <div>
        <label class="form-label" for="description">Kronologi / Uraian Masalah <span class="text-red-500">*</span></label>
        <textarea id="description" name="description" rows="6" required
          placeholder="Ceritakan kronologi kejadian secara lengkap. Anda tidak perlu menggunakan bahasa hukum — ceritakan saja apa yang terjadi, kapan, dan dengan siapa..."
          class="form-input resize-y"><?= sanitize($_POST['description'] ?? '') ?></textarea>
        <p class="text-xs text-[#8e706c] mt-1">Semakin lengkap informasi, semakin cepat tim dapat menilai kasus Anda.</p>
      </div>

      <!-- Attachment (optional) -->
      <div>
        <label class="form-label">Lampiran Awal <span class="text-[#8e706c] font-normal normal-case text-xs">(opsional)</span></label>
        <div id="dropzone" class="border-2 border-dashed border-[#e2bfb9] rounded-xl p-8 text-center cursor-pointer hover:border-[#800000] hover:bg-[#fff8f8] transition-all">
          <div class="w-12 h-12 bg-[#fff1f1] rounded-full flex items-center justify-center mx-auto mb-3">
            <span class="material-symbols-outlined text-[#800000] text-2xl">upload_file</span>
          </div>
          <p class="font-semibold text-[#191c1d] text-sm mb-1">Klik untuk memilih file atau drag & drop</p>
          <p class="text-xs text-[#8e706c]">PDF, DOCX, JPG, PNG — Maksimal 10MB</p>
          <input type="file" id="attachment" name="attachment" class="hidden" accept=".pdf,.docx,.jpg,.jpeg,.png"/>
        </div>
        <div id="file-preview" class="mt-3 space-y-2"></div>
      </div>

      <div class="section-divider"></div>

      <div class="flex gap-3 justify-end">
        <a href="/hpp-casetrack/client/dashboard.php" class="btn-outline">Batal</a>
        <button type="submit" class="btn-primary px-8">
          Lanjutkan <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </button>
      </div>
    </form>
  </div>
</div>
</div>
</div>
<script>initDropzone('dropzone','attachment','file-preview');</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
