<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('head_finance');
$user = currentUser();
$db   = getDB();

$error = $success = '';

// Handle approve/reject
if (isPost() && in_array($_POST['action'] ?? '', ['approve','reject'])) {
    $rid    = (int)$_POST['reimb_id'];
    $action = $_POST['action'];
    $notes  = sanitize($_POST['notes'] ?? '');
    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $db->prepare("UPDATE reimbursements SET status=?, reviewed_by=?, review_notes=? WHERE id=?")
       ->execute([$newStatus, $user['id'], $notes, $rid]);
    $success = $action === 'approve' ? 'Reimbursement disetujui.' : 'Reimbursement ditolak.';
}

$statusFilter = sanitize($_GET['status'] ?? '');
$where  = 'WHERE 1';
$params = [];
if ($statusFilter) { $where .= ' AND r.status=?'; $params[] = $statusFilter; }

$stmt = $db->prepare("
    SELECT r.*, u.name AS submitter_name, c.case_number
    FROM reimbursements r
    JOIN users u ON u.id = r.submitted_by
    JOIN cases c ON c.id = r.case_id
    $where
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$reimbursements = $stmt->fetchAll();

$totalPending  = $db->query("SELECT COUNT(*) FROM reimbursements WHERE status='pending'")->fetchColumn();
$totalApproved = $db->query("SELECT COALESCE(SUM(amount),0) FROM reimbursements WHERE status='approved'")->fetchColumn();

$pageTitle = 'Otorisasi Reimbursement';
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Otorisasi Reimbursement</h1>
    <p class="page-subtitle">Review dan setujui pengajuan biaya operasional pengacara</p>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="--cols:3">
  <div class="stat-card">
    <div class="stat-icon bg-warning"><i data-lucide="clock"></i></div>
    <div class="stat-body"><span class="stat-number"><?= $totalPending ?></span><span class="stat-label">Menunggu Review</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-success"><i data-lucide="check-circle"></i></div>
    <div class="stat-body"><span class="stat-number">Rp <?= number_format($totalApproved,0,',','.') ?></span><span class="stat-label">Total Disetujui</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-primary"><i data-lucide="receipt"></i></div>
    <div class="stat-body"><span class="stat-number"><?= count($reimbursements) ?></span><span class="stat-label">Total Pengajuan</span></div>
  </div>
</div>

<!-- Filter -->
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="filter-bar">
      <select name="status" class="form-select">
        <option value="">Semua Status</option>
        <option value="pending"  <?= $statusFilter==='pending' ?'selected':'' ?>>Menunggu</option>
        <option value="approved" <?= $statusFilter==='approved'?'selected':'' ?>>Disetujui</option>
        <option value="rejected" <?= $statusFilter==='rejected'?'selected':'' ?>>Ditolak</option>
      </select>
      <button type="submit" class="btn btn-secondary">Filter</button>
      <a href="<?= $base ?>head-finance/reimbursement-approval.php" class="btn btn-outline">Reset</a>
    </form>
  </div>
</div>

<!-- Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Pengajuan</th>
            <th>Kasus</th>
            <th>Pengacara</th>
            <th>Jumlah</th>
            <th>Tgl Kegiatan</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($reimbursements)): ?>
          <tr><td colspan="7" class="text-center py-8 text-muted">Tidak ada pengajuan ditemukan</td></tr>
          <?php endif; ?>
          <?php
          $statusMap   = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger','paid'=>'badge-success'];
          $statusLabel = ['pending'=>'Menunggu','approved'=>'Disetujui','rejected'=>'Ditolak','paid'=>'Dibayar'];
          foreach ($reimbursements as $r): ?>
          <tr>
            <td>
              <div class="fw-600"><?= htmlspecialchars(substr($r['description'],0,50)) ?>…</div>
              <div class="text-muted text-sm"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
            </td>
            <td><span class="text-primary fw-600"><?= htmlspecialchars($r['case_number']) ?></span></td>
            <td><?= htmlspecialchars($r['submitter_name']) ?></td>
            <td class="fw-600">Rp <?= number_format($r['amount'],0,',','.') ?></td>
            <td><?= $r['activity_date'] ? date('d M Y', strtotime($r['activity_date'])) : '—' ?></td>
            <td><span class="badge <?= $statusMap[$r['status']] ?? 'badge-secondary' ?>"><?= $statusLabel[$r['status']] ?? ucfirst($r['status']) ?></span></td>
            <td>
              <?php if ($r['status'] === 'pending'): ?>
              <button onclick="openReviewModal(<?= $r['id'] ?>, 'approve')" class="btn btn-sm btn-success"><i data-lucide="check"></i> Setujui</button>
              <button onclick="openReviewModal(<?= $r['id'] ?>, 'reject')"  class="btn btn-sm btn-danger"><i data-lucide="x"></i> Tolak</button>
              <?php else: ?>
              <span class="text-muted text-sm"><?= $r['review_notes'] ? htmlspecialchars(substr($r['review_notes'],0,30)) : '—' ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Review Modal -->
<div id="review-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
  <div class="card" style="width:440px; max-width:90vw;">
    <div class="card-header">
      <h2 class="card-title" id="modal-title">Review Reimbursement</h2>
      <button onclick="closeReviewModal()" class="btn btn-sm btn-outline">✕</button>
    </div>
    <div class="card-body">
      <form method="POST" id="review-form">
        <input type="hidden" name="reimb_id" id="modal-reimb-id">
        <input type="hidden" name="action"   id="modal-action">
        <div class="form-group">
          <label class="form-label">Catatan (opsional)</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan review…"></textarea>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary" id="modal-submit-btn">Konfirmasi</button>
          <button type="button" onclick="closeReviewModal()" class="btn btn-outline">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openReviewModal(id, action) {
    document.getElementById('modal-reimb-id').value = id;
    document.getElementById('modal-action').value   = action;
    document.getElementById('modal-title').textContent = action === 'approve' ? '✅ Setujui Reimbursement' : '❌ Tolak Reimbursement';
    document.getElementById('modal-submit-btn').textContent = action === 'approve' ? 'Setujui' : 'Tolak';
    document.getElementById('review-modal').style.display = 'flex';
}
function closeReviewModal() {
    document.getElementById('review-modal').style.display = 'none';
}
</script>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
