<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('finance');
$user = currentUser();
$db   = getDB();

$statusFilter = sanitize($_GET['status'] ?? '');
$search       = sanitize($_GET['search'] ?? '');

$where  = 'WHERE 1';
$params = [];
if ($statusFilter) { $where .= ' AND i.status=?'; $params[] = $statusFilter; }
if ($search)       { $where .= ' AND (i.invoice_number LIKE ? OR cl.name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $db->prepare("
  SELECT i.*, cl.name AS client_name, c.case_number
  FROM invoices i
  JOIN users cl ON cl.id = i.client_id
  LEFT JOIN cases c ON c.id = i.case_id
  $where
  ORDER BY i.created_at DESC
");
$stmt->execute($params);
$invoices = $stmt->fetchAll();

$totalUnpaid   = $db->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='unpaid'")->fetchColumn();
$totalVerif    = $db->query("SELECT COUNT(*) FROM invoices WHERE status='verification'")->fetchColumn();
$totalPaid     = $db->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='paid'")->fetchColumn();

$pageTitle = 'Manajemen Invoice';
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<div class="page-header">
  <div>
    <h1 class="page-title">Manajemen Invoice</h1>
    <p class="page-subtitle">Verifikasi pembayaran dan kelola invoice klien</p>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="--cols:3">
  <div class="stat-card">
    <div class="stat-icon bg-danger"><i data-lucide="file-x"></i></div>
    <div class="stat-body">
      <span class="stat-number">Rp <?= number_format($totalUnpaid,0,',','.') ?></span>
      <span class="stat-label">Belum Dibayar</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-warning"><i data-lucide="clock"></i></div>
    <div class="stat-body">
      <span class="stat-number"><?= $totalVerif ?></span>
      <span class="stat-label">Menunggu Verifikasi</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-success"><i data-lucide="check-circle"></i></div>
    <div class="stat-body">
      <span class="stat-number">Rp <?= number_format($totalPaid,0,',','.') ?></span>
      <span class="stat-label">Total Lunas</span>
    </div>
  </div>
</div>

<!-- Filter -->
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="filter-bar">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari no. invoice, nama klien…" class="form-control">
      <select name="status" class="form-select">
        <option value="">Semua Status</option>
        <option value="unpaid"       <?= $statusFilter==='unpaid'?'selected':'' ?>>Belum Bayar</option>
        <option value="verification" <?= $statusFilter==='verification'?'selected':'' ?>>Verifikasi</option>
        <option value="paid"         <?= $statusFilter==='paid'?'selected':'' ?>>Lunas</option>
      </select>
      <button type="submit" class="btn btn-secondary">Filter</button>
      <a href="<?= $base ?>finance/invoices.php" class="btn btn-outline">Reset</a>
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
            <th>No. Invoice</th>
            <th>Klien</th>
            <th>Nomor Kasus</th>
            <th>Jumlah</th>
            <th>Jatuh Tempo</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($invoices)): ?>
          <tr><td colspan="7" class="text-center py-8 text-muted">Tidak ada invoice ditemukan</td></tr>
          <?php endif; ?>
          <?php foreach ($invoices as $inv): ?>
          <tr>
            <td><span class="fw-600 text-primary"><?= htmlspecialchars($inv['invoice_number']) ?></span></td>
            <td><?= htmlspecialchars($inv['client_name']) ?></td>
            <td><?= htmlspecialchars($inv['case_number'] ?? '-') ?></td>
            <td class="fw-600">Rp <?= number_format($inv['amount'],0,',','.') ?></td>
            <td>
              <?php $due = $inv['due_date'] ? date('d M Y', strtotime($inv['due_date'])) : '-';
                    $isOverdue = $inv['due_date'] && strtotime($inv['due_date']) < time() && $inv['status'] !== 'paid'; ?>
              <span class="<?= $isOverdue ? 'text-danger fw-600' : '' ?>"><?= $due ?></span>
              <?php if ($isOverdue): ?><span class="badge badge-danger ml-1">Overdue</span><?php endif; ?>
            </td>
            <td>
              <?php
              $statusMap = ['unpaid'=>'badge-danger','verification'=>'badge-warning','paid'=>'badge-success'];
              $statusLabel = ['unpaid'=>'Belum Bayar','verification'=>'Verifikasi','paid'=>'Lunas'];
              ?>
              <span class="badge <?= $statusMap[$inv['status']] ?? 'badge-secondary' ?>">
                <?= $statusLabel[$inv['status']] ?? ucfirst($inv['status']) ?>
              </span>
            </td>
            <td>
              <a href="<?= $base ?>finance/invoice-detail.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-primary">
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
