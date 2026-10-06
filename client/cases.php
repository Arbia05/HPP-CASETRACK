<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');
$user = currentUser();
$db   = getDB();

$filter = sanitize($_GET['filter'] ?? '');
$where = 'WHERE c.client_id=?';
$params = [$user['id']];
if ($filter === 'active')  { $where .= " AND c.status IN ('active','new','on_hold')"; }
if ($filter === 'closed')  { $where .= " AND c.status IN ('closed','won','lost')"; }

$cases = $db->prepare("SELECT c.*, u.name AS lawyer_name FROM cases c LEFT JOIN users u ON c.assigned_pj_id=u.id $where ORDER BY c.updated_at DESC");
$cases->execute($params);
$allCases = $cases->fetchAll();

$totals = $db->prepare("SELECT status, COUNT(*) AS cnt FROM cases WHERE client_id=? GROUP BY status");
$totals->execute([$user['id']]);
$counts = $totals->fetchAll(PDO::FETCH_KEY_PAIR);
$totalAll = array_sum($counts);

$pageTitle = 'Kasus Saya';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-5xl mx-auto">

  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
    <div class="page-header" style="margin-bottom:0">
      <h1>Kasus Saya</h1>
      <p>Daftar seluruh perkara hukum yang Anda daftarkan.</p>
    </div>
    <a href="/client/new-case-step1.php" class="btn-gold shrink-0">
      <span class="material-symbols-outlined text-[18px]">add_circle</span>
      Ajukan Kasus Baru
    </a>
  </div>

  <!-- Filter tabs -->
  <div class="flex gap-2 mb-6">
    <?php foreach ([
      '' => ['Semua', $totalAll],
      'active' => ['Aktif', ($counts['active'] ?? 0) + ($counts['new'] ?? 0)],
      'closed' => ['Selesai', ($counts['closed'] ?? 0) + ($counts['won'] ?? 0) + ($counts['lost'] ?? 0)],
    ] as $val => [$label, $count]):
    $active = $filter === $val; ?>
    <a href="?filter=<?= $val ?>" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all
      <?= $active ? 'bg-[#800000] text-white shadow-sm' : 'bg-white text-[#5a413d] border border-[#e1e3e4] hover:border-[#800000] hover:text-[#800000]' ?>">
      <?= $label ?>
      <span class="text-xs px-1.5 py-0.5 rounded-full <?= $active ? 'bg-white/20 text-white' : 'bg-[#f3f4f5] text-[#5a413d]' ?>"><?= $count ?></span>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Cases grid -->
  <?php if (empty($allCases)): ?>
  <div class="card p-12 text-center">
    <span class="material-symbols-outlined text-5xl text-[#e1e3e4] block mb-4">folder_open</span>
    <h3 class="text-xl font-bold text-[#191c1d] mb-2">Belum ada kasus</h3>
    <p class="text-sm text-[#5a413d] mb-6">Ajukan kasus hukum Anda untuk mulai dipantau.</p>
    <a href="/client/new-case-step1.php" class="btn-primary">Ajukan Kasus Pertama</a>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($allCases as $c):
      $prog = ['new'=>10,'active'=>50,'on_hold'=>30,'closed'=>100,'won'=>100,'lost'=>100];
      $pct  = $prog[$c['status']] ?? 0;
    ?>
    <div class="card p-5 hover:shadow-md transition-all group">
      <div class="flex items-start justify-between gap-3 mb-3">
        <div class="min-w-0">
          <div class="flex items-center gap-2 mb-1.5">
            <span class="text-xs font-mono text-[#800000] font-bold"><?= $c['reference_number'] ?></span>
            <?= statusBadge($c['status']) ?>
          </div>
          <h3 class="font-bold text-[#191c1d] group-hover:text-[#800000] transition-colors leading-snug">
            <?= sanitize($c['title']) ?>
          </h3>
        </div>
      </div>

      <div class="flex flex-wrap gap-3 text-xs text-[#5a413d] mb-4">
        <?php if ($c['case_type']): ?>
        <span class="flex items-center gap-1">
          <span class="material-symbols-outlined text-[14px]">category</span>
          <?= sanitize($c['case_type']) ?>
        </span>
        <?php endif; ?>
        <?php if ($c['lawyer_name']): ?>
        <span class="flex items-center gap-1">
          <span class="material-symbols-outlined text-[14px]">person</span>
          <?= sanitize($c['lawyer_name']) ?>
        </span>
        <?php endif; ?>
        <span class="flex items-center gap-1">
          <span class="material-symbols-outlined text-[14px]">calendar_today</span>
          <?= formatDate($c['opened_at']) ?>
        </span>
      </div>

      <!-- Progress bar -->
      <div class="mb-4">
        <div class="flex justify-between text-xs text-[#8e706c] mb-1">
          <span>Progress</span><span><?= $pct ?>%</span>
        </div>
        <div class="h-1.5 bg-[#edeeef] rounded-full overflow-hidden">
          <div class="h-full bg-gradient-to-r from-[#800000] to-[#b22b1d] rounded-full transition-all"
               style="width:<?= $pct ?>%"></div>
        </div>
      </div>

      <a href="/client/case-detail.php?id=<?= $c['id'] ?>"
        class="flex items-center justify-center gap-2 w-full py-2 border border-[#e2bfb9] rounded-lg text-sm font-medium text-[#800000] hover:bg-[#fff1f1] transition-colors">
        <span class="material-symbols-outlined text-[18px]">visibility</span>
        Lihat Detail Kasus
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
