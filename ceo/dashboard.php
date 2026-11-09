<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('ceo');
$user = currentUser();
$db   = getDB();

$activeCases     = $db->query("SELECT COUNT(*) FROM cases WHERE status='active'")->fetchColumn();
$pendingContracts= $db->query("SELECT COUNT(*) FROM cases WHERE status='pending_ceo'")->fetchColumn();
$totalRevenue    = $db->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='paid'")->fetchColumn();
$closedWon       = $db->query("SELECT COUNT(*) FROM cases WHERE status='won'")->fetchColumn();

// Kasus pending persetujuan CEO
$contracts = $db->query("SELECT c.*, c.case_number AS reference_number, c.title AS case_title, u.name AS client_name FROM cases c JOIN users u ON c.client_id=u.id WHERE c.status='pending_ceo' ORDER BY c.created_at DESC LIMIT 6")->fetchAll();

// All cases overview
$allCases = $db->query("SELECT c.*, u.name AS client_name, lw.name AS lawyer_name FROM cases c JOIN users u ON c.client_id=u.id LEFT JOIN users lw ON c.assigned_pj_id=lw.id ORDER BY c.updated_at DESC LIMIT 8")->fetchAll();

$pageTitle = 'CEO Dashboard';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-7xl mx-auto">

  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
    <div>
      <p class="text-xs font-semibold text-[#8e706c] uppercase tracking-widest mb-1">CEO / Manajemen</p>
      <h1 class="text-3xl font-bold text-[#191c1d]">CEO Dashboard</h1>
      <p class="text-sm text-[#5a413d] mt-1">Gambaran kinerja firma dan validasi yang perlu tindakan.</p>
    </div>
    <div class="flex items-center gap-2 text-xs text-[#8e706c]">
      <span class="material-symbols-outlined text-[16px]">update</span>
      Diperbarui: <?= date('d M Y, H:i') ?> WIB
    </div>
  </div>

  <!-- KPI Stats -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card">
      <div class="flex justify-between items-start mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Kasus Aktif</p>
        <div class="w-9 h-9 rounded-xl bg-[#fff1f1] flex items-center justify-center">
          <span class="material-symbols-outlined text-[#800000] text-[18px]">gavel</span>
        </div>
      </div>
      <div class="text-4xl font-extrabold text-[#191c1d]"><?= $activeCases ?></div>
      <div class="flex items-center gap-1 mt-2 text-green-600 text-xs">
        <span class="material-symbols-outlined text-[14px]">trending_up</span> Berjalan lancar
      </div>
    </div>
    <div class="stat-card relative overflow-hidden">
      <div class="absolute right-0 top-0 w-20 h-20 bg-[#800000]/5 rounded-bl-full"></div>
      <div class="flex justify-between items-start mb-3 relative z-10">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#800000]">Kontrak Pending</p>
        <div class="w-9 h-9 rounded-xl bg-[#800000] flex items-center justify-center">
          <span class="material-symbols-outlined text-white text-[18px]">fact_check</span>
        </div>
      </div>
      <div class="text-4xl font-extrabold text-[#800000] relative z-10"><?= $pendingContracts ?></div>
      <p class="text-xs text-[#5a413d] mt-2 relative z-10">Butuh persetujuan segera</p>
    </div>
    <div class="stat-card">
      <div class="flex justify-between items-start mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Total Pendapatan</p>
        <div class="w-9 h-9 rounded-xl bg-[#fffbeb] flex items-center justify-center">
          <span class="material-symbols-outlined text-[#735c00] text-[18px]">payments</span>
        </div>
      </div>
      <div class="text-2xl font-extrabold text-[#191c1d]"><?= formatRupiah($totalRevenue) ?></div>
      <div class="flex items-center gap-1 mt-2 text-green-600 text-xs">
        <span class="material-symbols-outlined text-[14px]">trending_up</span> YTD kumulatif
      </div>
    </div>
    <div class="stat-card">
      <div class="flex justify-between items-start mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Kasus Berhasil</p>
        <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center">
          <span class="material-symbols-outlined text-green-600 text-[18px]">emoji_events</span>
        </div>
      </div>
      <div class="text-4xl font-extrabold text-green-600"><?= $closedWon ?></div>
      <p class="text-xs text-[#5a413d] mt-2">Perkara dimenangkan</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

    <!-- Pending Contracts -->
    <div class="lg:col-span-3 card overflow-hidden">
      <div class="px-6 py-4 border-b border-[#e2bfb9] flex items-center justify-between">
        <h2 class="font-bold text-[#191c1d]">Kasus Menunggu Persetujuan</h2>
        <a href="<?= $base ?>ceo/cases.php" class="text-sm text-[#800000] hover:underline font-medium">Lihat Semua →</a>
      </div>
      <?php if (empty($contracts)): ?>
      <div class="p-8 text-center">
        <span class="material-symbols-outlined text-4xl text-green-400 mb-2 block">check_circle</span>
        <p class="text-sm text-[#5a413d]">Tidak ada kontrak yang menunggu persetujuan.</p>
      </div>
      <?php else: ?>
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead><tr>
            <th>No. Kasus</th><th>Klien</th><th>Judul Kasus</th><th>Masuk</th><th>Aksi</th>
          </tr></thead>
          <tbody>
          <?php foreach ($contracts as $ct): ?>
          <tr>
            <td class="font-mono text-xs text-[#800000] font-semibold"><?= $ct['reference_number'] ?></td>
            <td><?= sanitize($ct['client_name']) ?></td>
            <td class="max-w-[180px] truncate"><?= sanitize($ct['case_title']) ?></td>
            <td class="text-xs text-[#8e706c]"><?= formatDate($ct['created_at']) ?></td>
            <td>
              <a href="<?= $base ?>ceo/case-detail.php?id=<?= $ct['id'] ?>"
                class="btn-primary btn-sm">Tinjau</a>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- All Cases Snapshot -->
    <div class="lg:col-span-2 space-y-4">
      <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-[#e2bfb9]">
          <h2 class="font-bold text-[#191c1d]">Status Kasus Terkini</h2>
        </div>
        <div class="divide-y divide-[#edeeef]">
          <?php foreach ($allCases as $c): ?>
          <div class="px-5 py-3 flex items-center gap-3">
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium truncate"><?= sanitize($c['title']) ?></p>
              <p class="text-xs text-[#8e706c]"><?= sanitize($c['client_name']) ?></p>
            </div>
            <?= statusBadge($c['status']) ?>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="px-5 py-3 border-t border-[#edeeef]">
          <a href="/hpp-casetrack/ceo/cases.php" class="text-xs text-[#800000] hover:underline font-medium">Lihat Semua Kasus →</a>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
