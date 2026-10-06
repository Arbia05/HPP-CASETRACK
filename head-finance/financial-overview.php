<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('head_finance');
$user = currentUser();
$db   = getDB();

// Date range
$month = sanitize($_GET['month'] ?? date('Y-m'));
[$year, $mon] = explode('-', $month);

$totalInvoiced = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE DATE_FORMAT(created_at,'%Y-%m')=?");
$totalInvoiced->execute([$month]);
$totalInvoiced = $totalInvoiced->fetchColumn();

$totalPaid = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='paid' AND DATE_FORMAT(paid_at,'%Y-%m')=?");
$totalPaid->execute([$month]);
$totalPaid = $totalPaid->fetchColumn();

$totalReimb = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM reimbursements WHERE status IN('approved','paid') AND DATE_FORMAT(created_at,'%Y-%m')=?");
$totalReimb->execute([$month]);
$totalReimb = $totalReimb->fetchColumn();

$netRevenue = $totalPaid - $totalReimb;

// Invoice breakdown
$invStmt = $db->prepare("
    SELECT i.*, u.name AS client_name FROM invoices i
    JOIN users u ON u.id=i.client_id
    WHERE DATE_FORMAT(i.created_at,'%Y-%m')=?
    ORDER BY i.created_at DESC
");
$invStmt->execute([$month]);
$invoices = $invStmt->fetchAll();

// Reimbursement breakdown
$reimbStmt = $db->prepare("
    SELECT r.*, u.name AS submitter FROM reimbursements r
    JOIN users u ON u.id=r.submitted_by
    WHERE DATE_FORMAT(r.created_at,'%Y-%m')=? AND r.status IN('approved','paid')
    ORDER BY r.amount DESC
");
$reimbStmt->execute([$month]);
$reimbList = $reimbStmt->fetchAll();

$pageTitle = 'Laporan Keuangan';
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<div class="page-header">
  <div>
    <h1 class="page-title">Laporan Keuangan</h1>
    <p class="page-subtitle">Ringkasan pendapatan dan pengeluaran firma</p>
  </div>
  <form method="GET" class="filter-bar">
    <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" class="form-control" style="width:auto">
    <button type="submit" class="btn btn-secondary">Tampilkan</button>
  </form>
</div>

<!-- KPI Stats -->
<div class="stats-grid" style="--cols:4">
  <div class="stat-card">
    <div class="stat-icon bg-primary"><i data-lucide="file-text"></i></div>
    <div class="stat-body"><span class="stat-number" style="font-size:18px">Rp <?= number_format($totalInvoiced,0,',','.') ?></span><span class="stat-label">Total Ditagih</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-success"><i data-lucide="check-circle"></i></div>
    <div class="stat-body"><span class="stat-number" style="font-size:18px">Rp <?= number_format($totalPaid,0,',','.') ?></span><span class="stat-label">Total Diterima</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-danger"><i data-lucide="trending-down"></i></div>
    <div class="stat-body"><span class="stat-number" style="font-size:18px">Rp <?= number_format($totalReimb,0,',','.') ?></span><span class="stat-label">Total Reimbursement</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-<?= $netRevenue >= 0 ? 'success' : 'danger' ?>"><i data-lucide="trending-up"></i></div>
    <div class="stat-body"><span class="stat-number" style="font-size:18px">Rp <?= number_format($netRevenue,0,',','.') ?></span><span class="stat-label">Pendapatan Bersih</span></div>
  </div>
</div>

<!-- Invoice Table -->
<div class="card mb-4">
  <div class="card-header"><h2 class="card-title"><i data-lucide="receipt" class="icon-sm"></i> Daftar Invoice — <?= date('F Y', mktime(0,0,0,(int)$mon,1,(int)$year)) ?></h2></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="data-table">
        <thead><tr><th>No. Invoice</th><th>Klien</th><th>Jumlah</th><th>Status</th><th>Tgl Bayar</th></tr></thead>
        <tbody>
          <?php if (empty($invoices)): ?>
          <tr><td colspan="5" class="text-center py-8 text-muted">Tidak ada invoice bulan ini</td></tr>
          <?php endif; ?>
          <?php foreach ($invoices as $inv):
            $imap=['unpaid'=>'badge-danger','verification'=>'badge-warning','paid'=>'badge-success','overdue'=>'badge-danger'];
            $ilab=['unpaid'=>'Belum Bayar','verification'=>'Verifikasi','paid'=>'Lunas','overdue'=>'Overdue']; ?>
          <tr>
            <td class="fw-600 text-primary"><?= htmlspecialchars($inv['invoice_number']) ?></td>
            <td><?= htmlspecialchars($inv['client_name']) ?></td>
            <td class="fw-600">Rp <?= number_format($inv['amount'],0,',','.') ?></td>
            <td><span class="badge <?= $imap[$inv['status']] ?? '' ?>"><?= $ilab[$inv['status']] ?? ucfirst($inv['status']) ?></span></td>
            <td><?= $inv['paid_at'] ? date('d M Y', strtotime($inv['paid_at'])) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Reimbursement Table -->
<div class="card">
  <div class="card-header"><h2 class="card-title"><i data-lucide="trending-down" class="icon-sm"></i> Reimbursement Disetujui</h2></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="data-table">
        <thead><tr><th>Pengacara</th><th>Deskripsi</th><th>Jumlah</th><th>Tgl Kegiatan</th></tr></thead>
        <tbody>
          <?php if (empty($reimbList)): ?>
          <tr><td colspan="4" class="text-center py-8 text-muted">Tidak ada reimbursement bulan ini</td></tr>
          <?php endif; ?>
          <?php foreach ($reimbList as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['submitter']) ?></td>
            <td><?= htmlspecialchars(substr($r['description'],0,60)) ?></td>
            <td class="fw-600">Rp <?= number_format($r['amount'],0,',','.') ?></td>
            <td><?= $r['activity_date'] ? date('d M Y', strtotime($r['activity_date'])) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
