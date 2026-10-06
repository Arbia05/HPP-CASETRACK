<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('legal_admin');
$user = currentUser();
$db   = getDB();

$statusFilter = sanitize($_GET['status'] ?? '');
$search       = sanitize($_GET['search'] ?? '');
$where        = 'WHERE 1';
$params       = [];
if ($statusFilter) { $where .= ' AND c.status=?'; $params[] = $statusFilter; }
if ($search)       { $where .= ' AND (c.case_number LIKE ? OR cl.name LIKE ? OR c.case_type LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $db->prepare("
  SELECT c.*, cl.name AS client_name, u.name AS pj_name
  FROM cases c
  JOIN users cl ON cl.id = c.client_id
  LEFT JOIN users u ON u.id = c.assigned_pj_id
  $where
  ORDER BY c.created_at DESC
");
$stmt->execute($params);
$cases = $stmt->fetchAll();

$pageTitle = 'Daftar Kasus Aktif';
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<div class="page-header">
  <div>
    <h1 class="page-title">Daftar Kasus</h1>
    <p class="page-subtitle">Monitor seluruh kasus yang sedang berjalan</p>
  </div>
</div>

<!-- Filter Bar -->
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="filter-bar">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nomor kasus, klien, tipe…" class="form-control">
      <select name="status" class="form-select">
        <option value="">Semua Status</option>
        <option value="active" <?= $statusFilter==='active'?'selected':'' ?>>Aktif</option>
        <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Pending</option>
        <option value="closed" <?= $statusFilter==='closed'?'selected':'' ?>>Selesai</option>
        <option value="cancelled" <?= $statusFilter==='cancelled'?'selected':'' ?>>Dibatalkan</option>
      </select>
      <button type="submit" class="btn btn-secondary">Filter</button>
      <a href="<?= $base ?>legal-admin/cases.php" class="btn btn-outline">Reset</a>
    </form>
  </div>
</div>

<!-- Cases Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>No. Kasus</th>
            <th>Klien</th>
            <th>Tipe Kasus</th>
            <th>PJ Pengacara</th>
            <th>Status</th>
            <th>Tgl Dibuat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($cases)): ?>
          <tr><td colspan="7" class="text-center py-8 text-muted">Tidak ada kasus ditemukan</td></tr>
          <?php endif; ?>
          <?php foreach ($cases as $c): ?>
          <tr>
            <td><span class="fw-600 text-primary"><?= htmlspecialchars($c['case_number']) ?></span></td>
            <td><?= htmlspecialchars($c['client_name']) ?></td>
            <td><?= htmlspecialchars($c['case_type']) ?></td>
            <td><?= $c['pj_name'] ? htmlspecialchars($c['pj_name']) : '<span class="text-muted">Belum ditugaskan</span>' ?></td>
            <td><span class="badge badge-<?= getCaseStatusColor($c['status']) ?>"><?= ucfirst($c['status']) ?></span></td>
            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
            <td>
              <a href="<?= $base ?>legal-admin/case-detail.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">
                <i data-lucide="eye"></i> Detail
              </a>
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
