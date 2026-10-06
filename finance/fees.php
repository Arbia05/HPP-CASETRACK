<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('finance');
$user = currentUser();
$db   = getDB();

// Handle approve/reject payment verification
$error = $success = '';
if (isPost()) {
    $action    = sanitize($_POST['action'] ?? '');
    $invoiceId = (int)$_POST['invoice_id'];
    $notes     = sanitize($_POST['notes'] ?? '');

    if ($action === 'approve') {
        $db->prepare("UPDATE invoices SET status='paid', verified_by=?, verified_at=NOW(), notes=? WHERE id=?")
           ->execute([$user['id'], $notes, $invoiceId]);
        $success = 'Pembayaran berhasil diverifikasi dan invoice ditandai Lunas.';
    } elseif ($action === 'reject') {
        $db->prepare("UPDATE invoices SET status='unpaid', notes=? WHERE id=?")
           ->execute([$notes, $invoiceId]);
        $success = 'Bukti bayar ditolak. Invoice dikembalikan ke status Belum Bayar.';
    }
}

// Monthly fee management: list pending fees
$feeStmt = $db->query("
    SELECT c.case_number, u.name AS lawyer_name, SUM(i.amount) AS total_invoiced,
           SUM(CASE WHEN i.status='paid' THEN i.amount ELSE 0 END) AS total_paid
    FROM cases c
    JOIN invoices i ON i.case_id = c.id
    JOIN users u ON u.id = c.assigned_pj_id
    GROUP BY c.id, u.id
    ORDER BY total_invoiced DESC
    LIMIT 10
");
$fees = $feeStmt->fetchAll();

$pageTitle = 'Manajemen Fee';
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Manajemen Fee</h1>
    <p class="page-subtitle">Pantau realisasi pendapatan per kasus dan pengacara</p>
  </div>
</div>

<div class="card">
  <div class="card-header"><h2 class="card-title"><i data-lucide="bar-chart-2" class="icon-sm"></i> Realisasi Fee per Kasus</h2></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>No. Kasus</th>
            <th>Pengacara PJ</th>
            <th>Total Ditagih</th>
            <th>Total Diterima</th>
            <th>Sisa Tagihan</th>
            <th>Realisasi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($fees)): ?>
          <tr><td colspan="6" class="text-center py-8 text-muted">Belum ada data fee</td></tr>
          <?php endif; ?>
          <?php foreach ($fees as $f):
            $pct = $f['total_invoiced'] > 0 ? round($f['total_paid'] / $f['total_invoiced'] * 100) : 0;
            $sisa = $f['total_invoiced'] - $f['total_paid'];
          ?>
          <tr>
            <td class="fw-600 text-primary"><?= htmlspecialchars($f['case_number']) ?></td>
            <td><?= htmlspecialchars($f['lawyer_name'] ?? '—') ?></td>
            <td>Rp <?= number_format($f['total_invoiced'],0,',','.') ?></td>
            <td class="fw-600 text-green-700">Rp <?= number_format($f['total_paid'],0,',','.') ?></td>
            <td class="<?= $sisa > 0 ? 'text-danger fw-600' : '' ?>">Rp <?= number_format($sisa,0,',','.') ?></td>
            <td>
              <div style="display:flex; align-items:center; gap:8px;">
                <div style="flex:1; height:8px; background:#f3f4f6; border-radius:4px; overflow:hidden;">
                  <div style="width:<?= $pct ?>%; height:100%; background:<?= $pct >= 100 ? '#16a34a' : '#800000' ?>; border-radius:4px;"></div>
                </div>
                <span class="text-sm fw-600"><?= $pct ?>%</span>
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
