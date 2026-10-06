<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('lawyer');
$user = currentUser();
$db   = getDB();

$statusFilter = sanitize($_GET['status'] ?? '');
$search       = sanitize($_GET['search'] ?? '');

// Cases where this lawyer is in the team
$where  = 'WHERE ct.lawyer_id=?';
$params = [$user['id']];
if ($statusFilter) { $where .= ' AND c.status=?'; $params[] = $statusFilter; }
if ($search)       { $where .= ' AND (c.case_number LIKE ? OR cl.name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $db->prepare("
  SELECT DISTINCT c.*, cl.name AS client_name, u.name AS pj_name
  FROM cases c
  JOIN case_team ct ON ct.case_id = c.id
  JOIN users cl ON cl.id = c.client_id
  LEFT JOIN users u ON u.id = c.assigned_pj_id
  $where
  ORDER BY c.created_at DESC
");
$stmt->execute($params);
$cases = $stmt->fetchAll();

// Latest activity for each case
$latestActivity = [];
foreach ($cases as $c) {
    $la = $db->prepare("SELECT description, created_at FROM case_activities WHERE case_id=? ORDER BY created_at DESC LIMIT 1");
    $la->execute([$c['id']]);
    $latestActivity[$c['id']] = $la->fetch();
}

$pageTitle = 'Kasus Saya';
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<div class="page-header">
  <div>
    <h1 class="page-title">Kasus yang Ditugaskan</h1>
    <p class="page-subtitle">Daftar kasus di mana Anda tergabung sebagai anggota tim</p>
  </div>
  <a href="<?= $base ?>lawyer/field-update.php" class="btn btn-primary">
    <i data-lucide="map-pin" class="btn-icon"></i> Update Lapangan
  </a>
</div>

<!-- Filter -->
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="filter-bar">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nomor kasus atau klien…" class="form-control">
      <select name="status" class="form-select">
        <option value="">Semua Status</option>
        <option value="active"  <?= $statusFilter==='active' ?'selected':'' ?>>Aktif</option>
        <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Pending</option>
        <option value="closed"  <?= $statusFilter==='closed' ?'selected':'' ?>>Selesai</option>
      </select>
      <button type="submit" class="btn btn-secondary">Filter</button>
      <a href="<?= $base ?>lawyer/cases.php" class="btn btn-outline">Reset</a>
    </form>
  </div>
</div>

<!-- Cases -->
<?php if (empty($cases)): ?>
<div class="empty-state">
  <i data-lucide="briefcase" class="empty-icon"></i>
  <p>Belum ada kasus yang ditugaskan kepada Anda</p>
</div>
<?php else: ?>
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>No. Kasus</th>
            <th>Tipe</th>
            <th>Klien</th>
            <th>PJ</th>
            <th>Aktivitas Terakhir</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cases as $c): ?>
          <tr>
            <td><span class="fw-600 text-primary"><?= htmlspecialchars($c['case_number']) ?></span></td>
            <td><?= htmlspecialchars($c['case_type']) ?></td>
            <td><?= htmlspecialchars($c['client_name']) ?></td>
            <td><?= htmlspecialchars($c['pj_name'] ?? '-') ?></td>
            <td>
              <?php if ($la = $latestActivity[$c['id']] ?? null): ?>
              <span class="text-sm" title="<?= htmlspecialchars($la['description']) ?>">
                <?= htmlspecialchars(substr($la['description'],0,40)) ?>…
                <br><small class="text-muted"><?= date('d M Y H:i', strtotime($la['created_at'])) ?></small>
              </span>
              <?php else: ?>
              <span class="text-muted text-sm">Belum ada aktivitas</span>
              <?php endif; ?>
            </td>
            <td><span class="badge badge-<?= getCaseStatusColor($c['status']) ?>"><?= ucfirst($c['status']) ?></span></td>
            <td>
              <a href="<?= $base ?>lawyer/field-update.php?case_id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">
                <i data-lucide="map-pin"></i> Update
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
