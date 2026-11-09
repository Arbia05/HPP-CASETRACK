<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('pengacara_pj');
$user = currentUser();
$db   = getDB();

$myCases   = $db->prepare("SELECT c.*, c.case_number AS reference_number, u.name AS client_name FROM cases c JOIN users u ON c.client_id=u.id WHERE c.assigned_pj_id=? ORDER BY c.updated_at DESC");
$myCases->execute([$user['id']]); $cases = $myCases->fetchAll();

$activeCnt  = count(array_filter($cases, fn($c) => $c['status'] === 'active'));
$upMilestone= $db->prepare("SELECT m.*, c.title AS case_title, c.case_number AS reference_number FROM milestones m JOIN cases c ON m.case_id=c.id WHERE c.assigned_pj_id=? AND m.status IN ('pending','in_progress') AND m.due_date >= CURDATE() ORDER BY m.due_date LIMIT 5");
$upMilestone->execute([$user['id']]); $milestones = $upMilestone->fetchAll();

$recentUpdates = $db->prepare("SELECT cu.*, c.title AS case_title FROM case_activities cu JOIN cases c ON cu.case_id=c.id LEFT JOIN case_team ct ON c.id=ct.case_id WHERE ct.lawyer_id=? OR c.assigned_pj_id=? ORDER BY cu.created_at DESC LIMIT 5");
$recentUpdates->execute([$user['id'], $user['id']]); $updates = $recentUpdates->fetchAll();

$pageTitle = 'Dashboard Pengacara PJ';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-7xl mx-auto">

  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
    <div>
      <p class="text-xs font-semibold text-[#8e706c] uppercase tracking-widest mb-1">Pengacara Penanggung Jawab</p>
      <h1 class="text-3xl font-bold text-[#191c1d]">Dashboard Saya</h1>
      <p class="text-sm text-[#5a413d] mt-1">Overview kasus yang Anda pimpin dan aktivitas tim.</p>
    </div>
    <a href="/hpp-casetrack/pengacara-pj/cases.php" class="btn-primary">
      <span class="material-symbols-outlined text-[18px]">folder_shared</span>
      Kelola Semua Kasus
    </a>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Total Kasus Dipimpin</p>
        <div class="w-9 h-9 bg-[#fff1f1] rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-[#800000] text-[18px]">folder_shared</span>
        </div>
      </div>
      <div class="text-3xl font-extrabold"><?= count($cases) ?></div>
    </div>
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#800000]">Kasus Aktif</p>
        <div class="w-9 h-9 bg-[#800000] rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-white text-[18px]">gavel</span>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-[#800000]"><?= $activeCnt ?></div>
    </div>
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Deadline Mendatang</p>
        <div class="w-9 h-9 bg-yellow-50 rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-yellow-600 text-[18px]">event</span>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-yellow-600"><?= count($milestones) ?></div>
    </div>
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Kasus Selesai</p>
        <div class="w-9 h-9 bg-green-50 rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-green-600 text-[18px]">check_circle</span>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-green-600"><?= count(array_filter($cases, fn($c) => in_array($c['status'], ['closed','won']))) ?></div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- My Cases List -->
    <div class="lg:col-span-2 card overflow-hidden">
      <div class="px-6 py-4 border-b border-[#e2bfb9]">
        <h2 class="font-bold text-[#191c1d]">Kasus yang Saya Pimpin</h2>
      </div>
      <div class="divide-y divide-[#edeeef]">
        <?php if (empty($cases)): ?>
        <p class="p-8 text-center text-[#8e706c] text-sm">Belum ada kasus ditugaskan.</p>
        <?php endif; ?>
        <?php foreach ($cases as $c): ?>
        <div class="px-6 py-4 flex items-center gap-4 hover:bg-[#fafafa] transition-colors group">
          <div class="w-10 h-10 rounded-xl bg-[#fff1f1] flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[#800000]">gavel</span>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-0.5">
              <span class="text-xs font-mono text-[#800000]"><?= $c['reference_number'] ?></span>
              <?= statusBadge($c['status']) ?>
            </div>
            <p class="font-semibold text-sm truncate group-hover:text-[#800000] transition-colors"><?= sanitize($c['title']) ?></p>
            <p class="text-xs text-[#5a413d]">Klien: <?= sanitize($c['client_name']) ?></p>
          </div>
          <div class="flex gap-2 shrink-0">
            <a href="/hpp-casetrack/pengacara-pj/case-detail.php?id=<?= $c['id'] ?>" class="btn-primary btn-sm">
              Kelola
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Right: Milestones & Updates -->
    <div class="space-y-5">
      <!-- Upcoming Milestones -->
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-[#e2bfb9]">
          <h2 class="font-bold text-[#191c1d] text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-[#800000] text-[18px]">flag</span>
            Deadline Mendatang
          </h2>
        </div>
        <div class="divide-y divide-[#edeeef]">
          <?php if (empty($milestones)): ?>
          <p class="p-4 text-xs text-[#8e706c] text-center">Tidak ada deadline mendatang.</p>
          <?php endif; ?>
          <?php foreach ($milestones as $m): ?>
          <div class="px-5 py-3">
            <p class="font-semibold text-xs text-[#800000]"><?= formatDate($m['due_date'], 'd M Y') ?></p>
            <p class="text-sm font-medium"><?= sanitize($m['title']) ?></p>
            <p class="text-xs text-[#5a413d] truncate"><?= sanitize($m['case_title']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-3 text-sm">Aksi Cepat</h2>
        <div class="space-y-2">
          <?php foreach ([
            ['/lawyer/field-update.php','Update Lapangan','my_location'],
            ['/pengacara-pj/assign-team.php','Tugaskan Tim','group_add'],
            ['/pengacara-pj/strategy.php','Catatan Strategi','strategy'],
          ] as [$url,$label,$icon]): ?>
          <a href="<?= $url ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#f3f4f5] transition-colors group">
            <span class="material-symbols-outlined text-[#800000] text-[20px]"><?= $icon ?></span>
            <span class="text-sm text-[#191c1d] group-hover:text-[#800000]"><?= $label ?></span>
            <span class="material-symbols-outlined text-[14px] text-[#e1e3e4] ml-auto">arrow_forward_ios</span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
