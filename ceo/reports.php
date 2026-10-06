<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('ceo');
$user      = currentUser();
$db        = getDB();
$pageTitle = 'Laporan Kinerja';
$base      = getBasePath();

// Simple stats
$totalCases   = $db->query("SELECT COUNT(*) FROM cases")->fetchColumn();
$activeCases  = $db->query("SELECT COUNT(*) FROM cases WHERE status='active'")->fetchColumn();
$closedCases  = $db->query("SELECT COUNT(*) FROM cases WHERE status='closed'")->fetchColumn();
$totalClients = $db->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='paid'")->fetchColumn();
$pending      = $db->query("SELECT COUNT(*) FROM cases WHERE status='pending'")->fetchColumn();

// Cases per type
$byType = $db->query("SELECT case_type, COUNT(*) AS total FROM cases GROUP BY case_type ORDER BY total DESC LIMIT 8")->fetchAll();

// Recent cases
$recent = $db->query("
    SELECT c.*, u.name AS client_name FROM cases c
    JOIN users u ON u.id=c.client_id
    ORDER BY c.created_at DESC LIMIT 8
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<div class="page-header">
  <div>
    <h1 class="page-title">Laporan Kinerja</h1>
    <p class="page-subtitle">Overview kinerja firma hukum HPP</p>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="--cols:3">
  <div class="stat-card">
    <div class="stat-icon bg-primary"><i data-lucide="briefcase"></i></div>
    <div class="stat-body"><span class="stat-number"><?= $totalCases ?></span><span class="stat-label">Total Kasus</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-success"><i data-lucide="activity"></i></div>
    <div class="stat-body"><span class="stat-number"><?= $activeCases ?></span><span class="stat-label">Kasus Aktif</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-secondary"><i data-lucide="check-circle"></i></div>
    <div class="stat-body"><span class="stat-number"><?= $closedCases ?></span><span class="stat-label">Kasus Selesai</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-info"><i data-lucide="users"></i></div>
    <div class="stat-body"><span class="stat-number"><?= $totalClients ?></span><span class="stat-label">Total Klien</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-warning"><i data-lucide="clock"></i></div>
    <div class="stat-body"><span class="stat-number"><?= $pending ?></span><span class="stat-label">Menunggu Persetujuan</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-success"><i data-lucide="trending-up"></i></div>
    <div class="stat-body"><span class="stat-number" style="font-size:18px">Rp <?= number_format($totalRevenue,0,',','.') ?></span><span class="stat-label">Total Pendapatan</span></div>
  </div>
</div>

<div class="detail-grid">
  <!-- Cases by type -->
  <div class="card">
    <div class="card-header"><h2 class="card-title">Kasus per Tipe</h2></div>
    <div class="card-body p-0">
      <table class="data-table">
        <thead><tr><th>Tipe Kasus</th><th>Jumlah</th></tr></thead>
        <tbody>
          <?php if (empty($byType)): ?>
          <tr><td colspan="2" class="text-center py-8 text-muted">Belum ada data</td></tr>
          <?php endif; ?>
          <?php foreach ($byType as $t): ?>
          <tr>
            <td><?= htmlspecialchars($t['case_type'] ?? 'Lainnya') ?></td>
            <td class="fw-600"><?= $t['total'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent cases -->
  <div class="card">
    <div class="card-header"><h2 class="card-title">Kasus Terbaru</h2></div>
    <div class="card-body p-0">
      <table class="data-table">
        <thead><tr><th>No. Kasus</th><th>Klien</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $c): ?>
          <tr>
            <td class="fw-600 text-primary"><?= htmlspecialchars($c['case_number']) ?></td>
            <td><?= htmlspecialchars($c['client_name']) ?></td>
            <td><span class="badge badge-<?= getCaseStatusColor($c['status']) ?>"><?= ucfirst($c['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
