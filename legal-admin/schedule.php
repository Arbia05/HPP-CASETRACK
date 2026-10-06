<?php
// Simple stub page template untuk halaman yang belum diimplementasi penuh
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('legal_admin');
$user      = currentUser();
$db        = getDB();
$pageTitle = 'Jadwal & Milestone';
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<div class="page-header">
  <div>
    <h1 class="page-title">Jadwal &amp; Milestone</h1>
    <p class="page-subtitle">Pantau milestone dan deadline seluruh kasus</p>
  </div>
</div>

<?php
// Fetch all milestones with case info
$stmt = $db->query("
    SELECT m.*, c.case_number, c.case_type, u.name AS client_name
    FROM milestones m
    JOIN cases c ON c.id = m.case_id
    JOIN users u ON u.id = c.client_id
    ORDER BY m.due_date ASC
");
$milestones = $stmt->fetchAll();

$statusMap   = ['pending'=>'badge-warning','in_progress'=>'badge-primary','completed'=>'badge-success','overdue'=>'badge-danger'];
$statusLabel = ['pending'=>'Pending','in_progress'=>'Berjalan','completed'=>'Selesai','overdue'=>'Overdue'];
?>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Kasus</th>
            <th>Milestone</th>
            <th>Deadline</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($milestones)): ?>
          <tr><td colspan="4" class="text-center py-8 text-muted">Belum ada milestone tercatat</td></tr>
          <?php endif; ?>
          <?php foreach ($milestones as $m): ?>
          <tr>
            <td>
              <div class="fw-600 text-primary"><?= htmlspecialchars($m['case_number']) ?></div>
              <div class="text-muted text-sm"><?= htmlspecialchars($m['client_name']) ?></div>
            </td>
            <td>
              <div class="fw-600"><?= htmlspecialchars($m['title']) ?></div>
              <?php if ($m['description']): ?>
              <div class="text-muted text-sm"><?= htmlspecialchars(substr($m['description'],0,60)) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php
              $due      = $m['due_date'] ? date('d M Y', strtotime($m['due_date'])) : '—';
              $isLate   = $m['due_date'] && strtotime($m['due_date']) < time() && $m['status'] !== 'completed';
              ?>
              <span class="<?= $isLate ? 'text-danger fw-600' : '' ?>"><?= $due ?></span>
            </td>
            <td><span class="badge <?= $statusMap[$m['status']] ?? 'badge-secondary' ?>"><?= $statusLabel[$m['status']] ?? ucfirst($m['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
