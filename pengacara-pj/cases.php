<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('pengacara_pj');
$user = currentUser();
$db   = getDB();

$statusFilter = sanitize($_GET['status'] ?? '');
$search       = sanitize($_GET['search'] ?? '');

$where  = 'WHERE c.assigned_pj_id=?';
$params = [$user['id']];
if ($statusFilter) { $where .= ' AND c.status=?'; $params[] = $statusFilter; }
if ($search)       { $where .= ' AND (c.case_number LIKE ? OR cl.name LIKE ? OR c.case_type LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $db->prepare("
  SELECT c.*, cl.name AS client_name,
    (SELECT COUNT(*) FROM case_activities ca WHERE ca.case_id=c.id) AS activity_count,
    (SELECT COUNT(*) FROM case_team ct WHERE ct.case_id=c.id) AS team_count
  FROM cases c
  JOIN users cl ON cl.id = c.client_id
  $where
  ORDER BY c.created_at DESC
");
$stmt->execute($params);
$cases = $stmt->fetchAll();

$pageTitle = 'Kasus Saya';
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<div class="page-header">
  <div>
    <h1 class="page-title">Kasus yang Saya Tangani</h1>
    <p class="page-subtitle">Daftar seluruh kasus yang Anda pimpin sebagai Pengacara PJ</p>
  </div>
</div>

<!-- Filter -->
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="filter-bar">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nomor kasus, klien…" class="form-control">
      <select name="status" class="form-select">
        <option value="">Semua Status</option>
        <option value="active"    <?= $statusFilter==='active'   ?'selected':'' ?>>Aktif</option>
        <option value="pending"   <?= $statusFilter==='pending'  ?'selected':'' ?>>Pending</option>
        <option value="closed"    <?= $statusFilter==='closed'   ?'selected':'' ?>>Selesai</option>
      </select>
      <button type="submit" class="btn btn-secondary">Filter</button>
      <a href="<?= $base ?>pengacara-pj/cases.php" class="btn btn-outline">Reset</a>
    </form>
  </div>
</div>

<!-- Cases Grid -->
<?php if (empty($cases)): ?>
<div class="empty-state">
  <i data-lucide="briefcase" class="empty-icon"></i>
  <p>Belum ada kasus yang ditugaskan kepada Anda</p>
</div>
<?php else: ?>
<div class="cases-grid">
  <?php foreach ($cases as $c): ?>
  <div class="case-card">
    <div class="case-card-header">
      <span class="case-number"><?= htmlspecialchars($c['case_number']) ?></span>
      <span class="badge badge-<?= getCaseStatusColor($c['status']) ?>"><?= ucfirst($c['status']) ?></span>
    </div>
    <div class="case-card-body">
      <h3 class="case-title"><?= htmlspecialchars($c['case_type']) ?></h3>
      <p class="case-client"><i data-lucide="user" class="icon-xs"></i> <?= htmlspecialchars($c['client_name']) ?></p>
      <div class="case-meta">
        <span><i data-lucide="users" class="icon-xs"></i> <?= $c['team_count'] ?> anggota tim</span>
        <span><i data-lucide="activity" class="icon-xs"></i> <?= $c['activity_count'] ?> aktivitas</span>
      </div>
      <div class="case-date">
        <i data-lucide="calendar" class="icon-xs"></i>
        Dibuat <?= date('d M Y', strtotime($c['created_at'])) ?>
      </div>
    </div>
    <div class="case-card-footer">
      <a href="<?= $base ?>pengacara-pj/case-detail.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm w-100">
        Kelola Kasus <i data-lucide="arrow-right" class="icon-xs"></i>
      </a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
