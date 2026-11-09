<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('finance');
$user = currentUser();
$db   = getDB();

$unpaidCount    = $db->query("SELECT COUNT(*) FROM invoices WHERE status='unpaid'")->fetchColumn();
$pendingVerif   = $db->query("SELECT COUNT(*) FROM invoices WHERE status='verification'")->fetchColumn();
$paidTotal      = $db->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='paid'")->fetchColumn();
$reimbPending   = $db->query("SELECT COUNT(*) FROM reimbursements WHERE status='pending'")->fetchColumn();

$recentInvoices = $db->query("SELECT i.*, u.name AS client_name, c.title AS case_title, c.case_number AS reference_number FROM invoices i JOIN users u ON i.client_id=u.id LEFT JOIN cases c ON i.case_id=c.id ORDER BY i.created_at DESC LIMIT 8")->fetchAll();

$pageTitle = 'Dashboard Finance';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-7xl mx-auto">

  <div class="page-header">
    <h1>Dashboard Keuangan</h1>
    <p>Overview invoice, verifikasi pembayaran, dan manajemen fee.</p>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php foreach ([
      ['Invoice Belum Bayar','unpaid',$unpaidCount,'receipt_long','text-red-600','bg-red-50'],
      ['Menunggu Verifikasi','pending',$pendingVerif,'pending_actions','text-yellow-700','bg-yellow-50'],
      ['Total Pendapatan Lunas','',$paidTotal,'payments','text-green-700','bg-green-50'],
      ['Reimbursement Pending','',$reimbPending,'currency_exchange','text-[#800000]','bg-[#fff1f1]'],
    ] as [$label,$status,$val,$icon,$color,$bg]): ?>
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]"><?= $label ?></p>
        <div class="w-9 h-9 rounded-xl <?= $bg ?> flex items-center justify-center">
          <span class="material-symbols-outlined <?= $color ?> text-[18px]"><?= $icon ?></span>
        </div>
      </div>
      <div class="text-2xl font-extrabold text-[#191c1d]">
        <?= is_numeric($val) && $val > 999 ? formatRupiah($val) : $val ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Invoice Table -->
  <div class="card overflow-hidden">
    <div class="px-6 py-4 border-b border-[#e2bfb9] flex items-center justify-between">
      <h2 class="font-bold text-[#191c1d]">Invoice Terbaru</h2>
      <a href="/hpp-casetrack/finance/invoices.php" class="text-sm text-[#800000] hover:underline font-medium">Kelola Semua →</a>
    </div>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead><tr>
          <th>No. Invoice</th><th>Klien</th><th>Kasus</th><th>Jumlah</th><th>Status</th><th>Aksi</th>
        </tr></thead>
        <tbody>
          <?php if (empty($recentInvoices)): ?>
          <tr><td colspan="6" class="text-center py-8 text-[#8e706c]">Belum ada invoice.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentInvoices as $inv): ?>
          <tr>
            <td class="font-mono text-xs text-[#800000] font-semibold"><?= sanitize($inv['invoice_number']) ?></td>
            <td class="font-medium text-sm"><?= sanitize($inv['client_name']) ?></td>
            <td class="max-w-[160px] truncate text-sm"><?= sanitize($inv['case_title']) ?></td>
            <td class="font-semibold"><?= formatRupiah($inv['amount']) ?></td>
            <td><?= statusBadge($inv['status']) ?></td>
            <td>
              <?php if ($inv['status'] === 'pending_verification'): ?>
              <a href="/hpp-casetrack/finance/invoice-detail.php?id=<?= $inv['id'] ?>"
                class="btn-primary btn-sm">Verifikasi</a>
              <?php else: ?>
              <a href="/hpp-casetrack/finance/invoice-detail.php?id=<?= $inv['id'] ?>"
                class="btn-outline btn-sm">Detail</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
