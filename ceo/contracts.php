<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('ceo');
$user      = currentUser();
$db        = getDB();
$pageTitle = 'Persetujuan Kontrak';
$base      = getBasePath();

$success = '';
if (isPost()) {
    $caseId = (int)$_POST['case_id'];
    $action = sanitize($_POST['action'] ?? '');
    if ($action === 'approve') {
        $db->prepare("UPDATE cases SET status='active' WHERE id=? AND status='pending'")->execute([$caseId]);
        $success = 'Kontrak disetujui, kasus diaktifkan.';
    } elseif ($action === 'reject') {
        $db->prepare("UPDATE cases SET status='cancelled' WHERE id=? AND status='pending'")->execute([$caseId]);
        $success = 'Kasus dibatalkan.';
    }
}

// Cases waiting CEO approval (status=pending)
$stmt = $db->query("
    SELECT c.*, u.name AS client_name
    FROM cases c
    JOIN users u ON u.id = c.client_id
    WHERE c.status = 'pending'
    ORDER BY c.created_at DESC
");
$pending = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Persetujuan Kontrak</h1>
    <p class="page-subtitle">Kasus yang menunggu persetujuan CEO untuk diaktifkan</p>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr><th>No. Kasus</th><th>Klien</th><th>Tipe</th><th>Tgl Masuk</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          <?php if (empty($pending)): ?>
          <tr><td colspan="5" class="text-center py-8 text-muted">Tidak ada kontrak menunggu persetujuan</td></tr>
          <?php endif; ?>
          <?php foreach ($pending as $c): ?>
          <tr>
            <td class="fw-600 text-primary"><?= htmlspecialchars($c['case_number']) ?></td>
            <td><?= htmlspecialchars($c['client_name']) ?></td>
            <td><?= htmlspecialchars($c['case_type'] ?? '—') ?></td>
            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
            <td>
              <div class="action-btns">
                <a href="<?= $base ?>ceo/case-detail.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline">Detail</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Setujui kontrak ini?')">
                  <input type="hidden" name="case_id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="action"  value="approve">
                  <button class="btn btn-sm btn-success">✓ Setujui</button>
                </form>
                <form method="POST" style="display:inline" onsubmit="return confirm('Batalkan kasus ini?')">
                  <input type="hidden" name="case_id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="action"  value="reject">
                  <button class="btn btn-sm btn-danger">✕ Tolak</button>
                </form>
              </div>
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
