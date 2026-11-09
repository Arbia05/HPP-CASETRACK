<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('lawyer');
$user = currentUser();
$db   = getDB();

$myCases = $db->prepare("SELECT c.*, c.case_number AS reference_number, u.name AS client_name FROM cases c JOIN users u ON c.client_id=u.id JOIN case_team ct ON c.id=ct.case_id WHERE ct.lawyer_id=? AND c.status='active' ORDER BY c.updated_at DESC");
$myCases->execute([$user['id']]); $cases = $myCases->fetchAll();

$reimbPending = $db->prepare("SELECT COUNT(*) FROM reimbursements WHERE submitted_by=? AND status='pending'");
$reimbPending->execute([$user['id']]); $reimbCnt = $reimbPending->fetchColumn();

$recentUpdates = $db->prepare("SELECT cu.*, c.title AS case_title FROM case_activities cu JOIN cases c ON cu.case_id=c.id WHERE cu.user_id=? ORDER BY cu.created_at DESC LIMIT 5");
$recentUpdates->execute([$user['id']]); $updates = $recentUpdates->fetchAll();

$pageTitle = 'Dashboard Lawyer';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-7xl mx-auto">

  <div class="page-header">
    <h1>Dashboard Saya</h1>
    <p>Kasus aktif, update lapangan, dan pengajuan reimbursement.</p>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Kasus Aktif</p>
        <div class="w-9 h-9 bg-[#800000] rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-white text-[18px]">gavel</span>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-[#800000]"><?= count($cases) ?></div>
      <p class="text-xs text-[#5a413d] mt-1">Kasus yang Anda ikuti</p>
    </div>
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Reimbursement Pending</p>
        <div class="w-9 h-9 bg-yellow-50 rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-yellow-600 text-[18px]">receipt_long</span>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-yellow-600"><?= $reimbCnt ?></div>
      <p class="text-xs text-[#5a413d] mt-1">Menunggu otorisasi</p>
    </div>
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Update Hari Ini</p>
        <div class="w-9 h-9 bg-green-50 rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-green-600 text-[18px]">update</span>
        </div>
      </div>
      <?php $todayUpdates = $db->prepare("SELECT COUNT(*) FROM case_activities WHERE user_id=? AND DATE(created_at)=CURDATE()"); $todayUpdates->execute([$user['id']]); ?>
      <div class="text-3xl font-extrabold text-green-600"><?= $todayUpdates->fetchColumn() ?></div>
      <p class="text-xs text-[#5a413d] mt-1">Update hari ini</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Cases -->
    <div class="lg:col-span-2 card overflow-hidden">
      <div class="px-6 py-4 border-b border-[#e2bfb9] flex items-center justify-between">
        <h2 class="font-bold text-[#191c1d]">Kasus Saya</h2>
        <a href="/hpp-casetrack/lawyer/cases.php" class="text-sm text-[#800000] hover:underline font-medium">Semua →</a>
      </div>
      <div class="divide-y divide-[#edeeef]">
        <?php if (empty($cases)): ?>
        <p class="p-8 text-center text-[#8e706c] text-sm">Belum ada kasus ditugaskan ke Anda.</p>
        <?php endif; ?>
        <?php foreach ($cases as $c): ?>
        <div class="px-6 py-4 flex items-center gap-4 hover:bg-[#fafafa] group transition-colors">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-xs font-mono text-[#800000]"><?= $c['reference_number'] ?></span>
              <?= statusBadge($c['status']) ?>
            </div>
            <p class="font-semibold text-sm truncate group-hover:text-[#800000] transition-colors"><?= sanitize($c['title']) ?></p>
            <p class="text-xs text-[#5a413d]">Klien: <?= sanitize($c['client_name']) ?></p>
          </div>
          <a href="/hpp-casetrack/lawyer/field-update.php?case_id=<?= $c['id'] ?>" class="btn-primary btn-sm shrink-0">
            <span class="material-symbols-outlined text-[16px]">my_location</span>
            Update
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Right -->
    <div class="space-y-5">
      <!-- Quick actions -->
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-4 text-sm">Aksi Cepat</h2>
        <div class="space-y-2">
          <?php foreach ([
            ['/lawyer/field-update.php','Update Aktivitas Lapangan','my_location','bg-[#800000] text-white'],
            ['/lawyer/reimbursement.php','Ajukan Reimbursement','receipt_long','bg-yellow-50 text-yellow-700 border border-yellow-200'],
            ['/lawyer/cases.php','Lihat Semua Kasus','folder_open','bg-[#f3f4f5] text-[#191c1d]'],
          ] as [$url,$label,$icon,$cls]): ?>
          <a href="<?= $url ?>" class="flex items-center gap-3 px-4 py-3 <?= $cls ?> rounded-xl font-medium text-sm transition-all hover:opacity-90">
            <span class="material-symbols-outlined text-[20px]"><?= $icon ?></span>
            <?= $label ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Recent updates -->
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-3 text-sm">Update Terbaru</h2>
        <?php if (empty($updates)): ?>
        <p class="text-xs text-[#8e706c] text-center py-4">Belum ada update.</p>
        <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($updates as $u): ?>
          <div class="py-2 border-b border-[#edeeef] last:border-0">
            <p class="text-xs font-semibold text-[#800000]"><?= sanitize($u['activity_type']) ?></p>
            <p class="text-xs text-[#5a413d] truncate"><?= sanitize($u['case_title']) ?></p>
            <p class="text-[10px] text-[#8e706c]"><?= timeAgo($u['created_at']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
