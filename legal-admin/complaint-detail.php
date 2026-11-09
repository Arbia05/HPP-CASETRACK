<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('legal_admin');
$user = currentUser();
$db   = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/hpp-casetrack/legal-admin/complaints.php');

$stmt = $db->prepare("SELECT c.*, u.name AS client_name, u.email AS client_email, u.phone AS client_phone FROM complaints c JOIN users u ON c.client_id=u.id WHERE c.id=?");
$stmt->execute([$id]);
$complaint = $stmt->fetch();
if (!$complaint) redirect('/hpp-casetrack/legal-admin/complaints.php');

$error = $success = '';

if (isPost()) {
    $action = sanitize($_POST['action'] ?? '');
    $notes  = sanitize($_POST['admin_notes'] ?? '');

    $statusMap = ['verify'=>'verified','reject'=>'rejected','request_more'=>'pending_completion'];
    $newStatus = $statusMap[$action] ?? null;

    if ($newStatus) {
        $db->prepare("UPDATE complaints SET status=?, admin_notes=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")
           ->execute([$newStatus, $notes, $user['id'], $id]);

        // Notify client
        $notifMsg = [
            'verified'           => 'Pengaduan Anda telah diverifikasi dan akan segera diproses.',
            'rejected'           => 'Pengaduan Anda ditolak. Catatan: ' . $notes,
            'pending_completion' => 'Diperlukan kelengkapan data tambahan. Catatan: ' . $notes,
        ];
        createNotification($complaint['client_id'], 'Status Pengaduan Diperbarui', $notifMsg[$newStatus] ?? '', 'complaint', $id, 'complaint');

        // If verified: redirect to create full data intake
        if ($newStatus === 'verified') {
            $success = 'Pengaduan berhasil diverifikasi. Klien akan diminta melengkapi data.';
        } else {
            redirect('/hpp-casetrack/legal-admin/complaints.php?msg=updated');
        }

        // Reload complaint
        $stmt->execute([$id]);
        $complaint = $stmt->fetch();
    }
}

$pageTitle = 'Detail Pengaduan — ' . sanitize($complaint['title']);
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-5xl mx-auto">

  <!-- Breadcrumb -->
  <nav class="flex items-center gap-2 text-sm text-[#8e706c] mb-6">
    <a href="/hpp-casetrack/legal-admin/dashboard.php" class="hover:text-[#800000]">Dashboard</a>
    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
    <a href="/hpp-casetrack/legal-admin/complaints.php" class="hover:text-[#800000]">Pengaduan</a>
    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
    <span class="text-[#191c1d] font-medium">Detail Pengaduan</span>
  </nav>

  <?php if ($success): ?><div class="alert alert-success mb-6"><?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error mb-6"><?= $error ?></div><?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
      <!-- Complaint Card -->
      <div class="card p-6">
        <div class="flex items-start justify-between gap-4 mb-5">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <?= statusBadge($complaint['status']) ?>
              <span class="text-xs text-[#8e706c]">Masuk: <?= formatDate($complaint['created_at'], 'd M Y, H:i') ?></span>
            </div>
            <h1 class="text-2xl font-bold text-[#191c1d]"><?= sanitize($complaint['title']) ?></h1>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-5">
          <div class="bg-[#f3f4f5] rounded-lg p-3">
            <p class="text-xs text-[#8e706c] uppercase tracking-wider mb-1">Kategori Kasus</p>
            <p class="font-semibold text-sm"><?= sanitize($complaint['category'] ?? 'Tidak ditentukan') ?></p>
          </div>
          <div class="bg-[#f3f4f5] rounded-lg p-3">
            <p class="text-xs text-[#8e706c] uppercase tracking-wider mb-1">Ditinjau Oleh</p>
            <p class="font-semibold text-sm"><?= $complaint['reviewed_by'] ? sanitize($user['name']) : '—' ?></p>
          </div>
        </div>

        <div class="mb-5">
          <h3 class="text-xs font-semibold text-[#5a413d] uppercase tracking-wider mb-3">Kronologi / Deskripsi</h3>
          <div class="bg-[#f8f9fa] rounded-xl p-5 border border-[#e1e3e4] text-sm leading-relaxed text-[#191c1d]">
            <?= nl2br(sanitize($complaint['description'])) ?>
          </div>
        </div>

        <!-- Attachment -->
        <?php if ($complaint['attachment_path']): ?>
        <div class="border border-dashed border-[#e2bfb9] rounded-xl p-4 flex items-center gap-4">
          <div class="w-10 h-10 bg-[#fff1f1] rounded-lg flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[#800000]">description</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium">Lampiran Dokumen</p>
            <p class="text-xs text-[#8e706c] truncate"><?= sanitize(basename($complaint['attachment_path'])) ?></p>
          </div>
          <a href="/<?= $complaint['attachment_path'] ?>" target="_blank"
            class="btn-outline btn-sm">
            <span class="material-symbols-outlined text-[16px]">download</span>
            Unduh
          </a>
        </div>
        <?php endif; ?>

        <!-- Admin Notes (if any) -->
        <?php if ($complaint['admin_notes']): ?>
        <div class="mt-5 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
          <p class="text-xs font-semibold text-yellow-800 uppercase tracking-wider mb-1">Catatan Admin</p>
          <p class="text-sm text-yellow-900"><?= nl2br(sanitize($complaint['admin_notes'])) ?></p>
        </div>
        <?php endif; ?>
      </div>

      <!-- Action Panel — only show if not yet finalized -->
      <?php if (in_array($complaint['status'], ['new','pending_completion'])): ?>
      <div class="card p-6">
        <h2 class="font-bold text-[#191c1d] mb-5 flex items-center gap-2">
          <span class="material-symbols-outlined text-[#800000]">rule</span>
          Tindakan Validasi
        </h2>

        <!-- Verify -->
        <form method="POST" class="mb-4">
          <input type="hidden" name="action" value="verify"/>
          <div class="form-group">
            <label class="form-label">Catatan (opsional)</label>
            <textarea name="admin_notes" rows="2" placeholder="Tambahkan catatan untuk klien..."
              class="form-input resize-none"></textarea>
          </div>
          <button type="submit" class="btn-primary w-full justify-center py-2.5"
            onclick="return confirm('Verifikasi pengaduan ini?')">
            <span class="material-symbols-outlined text-[18px]">verified</span>
            Verifikasi & Setujui Pengaduan
          </button>
        </form>

        <div class="grid grid-cols-2 gap-3">
          <!-- Request more info -->
          <form method="POST">
            <input type="hidden" name="action" value="request_more"/>
            <div class="form-group">
              <label class="form-label">Catatan Kelengkapan <span class="text-red-500">*</span></label>
              <textarea name="admin_notes" rows="2" required placeholder="Data apa yang perlu dilengkapi..."
                class="form-input resize-none text-xs"></textarea>
            </div>
            <button type="submit" class="w-full py-2.5 text-sm font-semibold text-[#735c00] bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 transition-colors flex items-center justify-center gap-2">
              <span class="material-symbols-outlined text-[18px]">pending_actions</span>
              Minta Kelengkapan
            </button>
          </form>
          <!-- Reject -->
          <form method="POST">
            <input type="hidden" name="action" value="reject"/>
            <div class="form-group">
              <label class="form-label">Alasan Penolakan <span class="text-red-500">*</span></label>
              <textarea name="admin_notes" rows="2" required placeholder="Jelaskan alasan penolakan..."
                class="form-input resize-none text-xs"></textarea>
            </div>
            <button type="submit" class="w-full py-2.5 text-sm font-semibold text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors flex items-center justify-center gap-2"
              onclick="return confirm('Tolak pengaduan ini?')">
              <span class="material-symbols-outlined text-[18px]">cancel</span>
              Tolak Pengaduan
            </button>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right: Client Info -->
    <div class="space-y-6">
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-4 flex items-center gap-2">
          <span class="material-symbols-outlined text-[#800000] text-[20px]">person</span>
          Informasi Klien
        </h2>
        <div class="flex items-center gap-3 mb-4">
          <div class="w-12 h-12 rounded-full bg-[#800000] flex items-center justify-center text-white font-bold text-lg">
            <?= strtoupper(substr($complaint['client_name'], 0, 1)) ?>
          </div>
          <div>
            <p class="font-semibold"><?= sanitize($complaint['client_name']) ?></p>
            <p class="text-xs text-[#8e706c]">Klien HPP Law Office</p>
          </div>
        </div>
        <div class="space-y-3 text-sm">
          <div class="flex items-center gap-2 text-[#5a413d]">
            <span class="material-symbols-outlined text-[16px] text-[#800000]">mail</span>
            <span><?= sanitize($complaint['client_email']) ?></span>
          </div>
          <?php if ($complaint['client_phone']): ?>
          <div class="flex items-center gap-2 text-[#5a413d]">
            <span class="material-symbols-outlined text-[16px] text-[#800000]">phone</span>
            <span><?= sanitize($complaint['client_phone']) ?></span>
          </div>
          <?php endif; ?>
        </div>
        <div class="section-divider"></div>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $complaint['client_phone'] ?? '') ?>" target="_blank"
          class="btn-outline w-full justify-center text-xs py-2">
          <span class="material-symbols-outlined text-[16px]">chat</span>
          Hubungi via WA
        </a>
      </div>

      <!-- Timeline status -->
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-4">Riwayat Status</h2>
        <div class="timeline">
          <div class="timeline-item">
            <div class="timeline-dot"></div>
            <p class="text-sm font-medium">Pengaduan Masuk</p>
            <p class="text-xs text-[#8e706c]"><?= formatDate($complaint['created_at']) ?></p>
          </div>
          <?php if ($complaint['reviewed_at']): ?>
          <div class="timeline-item">
            <div class="timeline-dot <?= $complaint['status'] === 'rejected' ? 'bg-red-500' : '' ?>" style="background:<?= $complaint['status']==='verified' ? '#22c55e' : ($complaint['status']==='rejected'?'#ef4444':'#f59e0b') ?>"></div>
            <p class="text-sm font-medium"><?= ucfirst(str_replace('_',' ',$complaint['status'])) ?></p>
            <p class="text-xs text-[#8e706c]"><?= formatDate($complaint['reviewed_at']) ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
