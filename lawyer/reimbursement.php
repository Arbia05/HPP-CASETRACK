<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('lawyer');
$user = currentUser();
$db   = getDB();

$error = $success = '';

// Handle submit reimbursement
if (isPost() && ($_POST['action'] ?? '') === 'submit') {
    $caseId      = (int)$_POST['case_id'];
    $description = sanitize($_POST['description'] ?? '');
    $amount      = (float)str_replace(['.',','], ['','.'], $_POST['amount'] ?? '0');
    $actDate     = sanitize($_POST['activity_date'] ?? '');

    if (!$caseId || !$description || $amount <= 0) {
        $error = 'Semua field wajib diisi dengan benar.';
    } else {
        $receiptPath = null;
        if (!empty($_FILES['receipt']['name'])) {
            $upload = handleUpload($_FILES['receipt'], 'payments', ['jpg','jpeg','png','pdf']);
            if (!$upload['success']) { $error = $upload['message']; }
            else { $receiptPath = $upload['path']; }
        }
        if (!$error) {
            $db->prepare("INSERT INTO reimbursements (case_id, submitted_by, description, amount, receipt_path, activity_date, status) VALUES (?,?,?,?,?,?,?)")
               ->execute([$caseId, $user['id'], $description, $amount, $receiptPath, $actDate, 'pending']);
            $success = 'Pengajuan reimbursement berhasil dikirim.';
        }
    }
}

// Get lawyer's cases
$caseStmt = $db->prepare("
    SELECT DISTINCT c.id, c.case_number, c.case_type
    FROM cases c JOIN case_team ct ON ct.case_id=c.id
    WHERE ct.lawyer_id=? AND c.status='active'
");
$caseStmt->execute([$user['id']]);
$cases = $caseStmt->fetchAll();

// Get existing reimbursements
$reimStmt = $db->prepare("
    SELECT r.*, c.case_number FROM reimbursements r
    JOIN cases c ON c.id=r.case_id
    WHERE r.submitted_by=?
    ORDER BY r.created_at DESC
");
$reimStmt->execute([$user['id']]);
$reimbursements = $reimStmt->fetchAll();

$pageTitle = 'Reimbursement';
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Pengajuan Reimbursement</h1>
    <p class="page-subtitle">Ajukan penggantian biaya operasional lapangan</p>
  </div>
</div>

<div class="detail-grid">
  <!-- Form -->
  <div class="detail-main">
    <div class="card mb-4">
      <div class="card-header"><h2 class="card-title"><i data-lucide="plus-circle" class="icon-sm"></i> Ajukan Reimbursement Baru</h2></div>
      <div class="card-body">
        <?php if (empty($cases)): ?>
        <div class="empty-state p-4">
          <i data-lucide="briefcase" class="empty-icon"></i>
          <p>Anda tidak memiliki kasus aktif untuk diajukan reimbursement</p>
        </div>
        <?php else: ?>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="submit">
          <div class="form-group">
            <label class="form-label">Kasus Terkait</label>
            <select name="case_id" class="form-select" required>
              <option value="">— Pilih Kasus —</option>
              <?php foreach ($cases as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['case_number']) ?> — <?= htmlspecialchars($c['case_type']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Jumlah (Rp)</label>
              <input type="number" name="amount" class="form-control" placeholder="0" min="1000" required>
            </div>
            <div class="form-group">
              <label class="form-label">Tanggal Kegiatan</label>
              <input type="date" name="activity_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Deskripsi Pengeluaran</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Jelaskan keperluan pengeluaran…" required></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Bukti Kwitansi (JPG / PNG / PDF)</label>
            <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
            <small class="form-hint">Opsional — max 10MB</small>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">
              <i data-lucide="send" class="btn-icon"></i> Kirim Pengajuan
            </button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- History -->
  <div class="detail-sidebar">
    <div class="card">
      <div class="card-header"><h2 class="card-title">Riwayat Pengajuan</h2></div>
      <div class="card-body p-0">
        <?php if (empty($reimbursements)): ?>
        <p class="text-muted text-sm p-4">Belum ada pengajuan</p>
        <?php else: ?>
        <div class="reimb-list">
          <?php
          $statusMap   = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger','paid'=>'badge-success'];
          $statusLabel = ['pending'=>'Menunggu','approved'=>'Disetujui','rejected'=>'Ditolak','paid'=>'Dibayar'];
          foreach ($reimbursements as $r): ?>
          <div class="reimb-item">
            <div class="reimb-header">
              <span class="fw-600 text-sm"><?= htmlspecialchars($r['case_number']) ?></span>
              <span class="badge <?= $statusMap[$r['status']] ?? 'badge-secondary' ?>"><?= $statusLabel[$r['status']] ?? ucfirst($r['status']) ?></span>
            </div>
            <p class="reimb-desc"><?= htmlspecialchars(substr($r['description'],0,60)) ?>…</p>
            <div class="reimb-amount">Rp <?= number_format($r['amount'],0,',','.') ?></div>
            <div class="reimb-date"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
