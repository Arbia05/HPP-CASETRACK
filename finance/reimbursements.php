<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('finance');
$user      = currentUser();
$db        = getDB();
$pageTitle = 'Reimbursement';
$base      = getBasePath();

$success = '';
if (isPost()) {
    $rid    = (int)$_POST['reimb_id'];
    $action = sanitize($_POST['action'] ?? '');
    if ($action === 'mark_paid') {
        $db->prepare("UPDATE reimbursements SET status='paid' WHERE id=?")->execute([$rid]);
        $success = 'Reimbursement ditandai sebagai dibayar.';
    }
}

$statusFilter = sanitize($_GET['status'] ?? '');
$where  = 'WHERE 1';
$params = [];
if ($statusFilter) { $where .= ' AND r.status=?'; $params[] = $statusFilter; }

$stmt = $db->prepare("
    SELECT r.*, u.name AS submitter, c.case_number
    FROM reimbursements r
    JOIN users u ON u.id=r.submitted_by
    JOIN cases c ON c.id=r.case_id
    $where
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$reimbursements = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Reimbursement</h1>
    <p class="page-subtitle">Daftar reimbursement yang telah disetujui Head Finance</p>
  </div>
</div>

<!-- Filter -->
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="filter-bar">
      <select name="status" class="form-select">
        <option value="">Semua Status</option>
        <option value="approved" <?= $statusFilter==='approved'?'selected':'' ?>>Disetujui</option>
        <option value="paid"     <?= $statusFilter==='paid'    ?'selected':'' ?>>Dibayar</option>
        <option value="pending"  <?= $statusFilter==='pending' ?'selected':'' ?>>Pending</option>
        <option value="rejected" <?= $statusFilter==='rejected'?'selected':'' ?>>Ditolak</option>
      </select>
      <button type="submit" class="btn btn-secondary">Filter</button>
      <a href="<?= $base ?>finance/reimbursements.php" class="btn btn-outline">Reset</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr><th>Pengacara</th><th>Kasus</th><th>Deskripsi</th><th>Jumlah</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          <?php if (empty($reimbursements)): ?>
          <tr><td colspan="6" class="text-center py-8 text-muted">Tidak ada data</td></tr>
          <?php endif; ?>
          <?php
          $smap = ['pending'=>'badge-warning','approved'=>'badge-primary','paid'=>'badge-success','rejected'=>'badge-danger'];
          $slab = ['pending'=>'Pending','approved'=>'Disetujui','paid'=>'Dibayar','rejected'=>'Ditolak'];
          foreach ($reimbursements as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['submitter']) ?></td>
            <td class="fw-600 text-primary"><?= htmlspecialchars($r['case_number']) ?></td>
            <td><?= htmlspecialchars(substr($r['description'],0,50)) ?></td>
            <td class="fw-600">Rp <?= number_format($r['amount'],0,',','.') ?></td>
            <td><span class="badge <?= $smap[$r['status']] ?? 'badge-secondary' ?>"><?= $slab[$r['status']] ?? ucfirst($r['status']) ?></span></td>
            <td>
              <?php if ($r['status'] === 'approved'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="reimb_id" value="<?= $r['id'] ?>">
                <input type="hidden" name="action"   value="mark_paid">
                <button class="btn btn-sm btn-success">Tandai Dibayar</button>
              </form>
              <?php elseif ($r['receipt_path']): ?>
              <a href="<?= $base . htmlspecialchars($r['receipt_path']) ?>" target="_blank" class="btn btn-sm btn-outline">Kwitansi</a>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
