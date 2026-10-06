<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');
$user = currentUser();
$db   = getDB();

// Stats
$totalCases = $db->prepare("SELECT COUNT(*) FROM cases WHERE client_id=?");
$totalCases->execute([$user['id']]); $totalCasesCount = $totalCases->fetchColumn();

$activeCases = $db->prepare("SELECT COUNT(*) FROM cases WHERE client_id=? AND status='active'");
$activeCases->execute([$user['id']]); $activeCasesCount = $activeCases->fetchColumn();

$unpaidInvoices = $db->prepare("SELECT COUNT(*) FROM invoices WHERE client_id=? AND status='unpaid'");
$unpaidInvoices->execute([$user['id']]); $unpaidCount = $unpaidInvoices->fetchColumn();

// Recent cases
$recentCases = $db->prepare("SELECT c.*, u.name AS lawyer_name FROM cases c LEFT JOIN users u ON c.assigned_pj_id=u.id WHERE c.client_id=? ORDER BY c.updated_at DESC LIMIT 3");
$recentCases->execute([$user['id']]); $cases = $recentCases->fetchAll();

// Recent activity (case updates)
$activity = $db->prepare("SELECT cu.*, c.title AS case_title, c.case_number AS reference_number, u.name AS actor_name FROM case_activities cu JOIN cases c ON cu.case_id=c.id JOIN users u ON cu.user_id=u.id WHERE c.client_id=? ORDER BY cu.created_at DESC LIMIT 5");
$activity->execute([$user['id']]); $activities = $activity->fetchAll();

// Pending actions
$pendingInvoices = $db->prepare("SELECT i.*, c.title AS case_title, c.case_number AS reference_number FROM invoices i JOIN cases c ON i.case_id=c.id WHERE i.client_id=? AND i.status IN ('unpaid','pending_verification') ORDER BY i.due_date LIMIT 3");
$pendingInvoices->execute([$user['id']]); $invoices = $pendingInvoices->fetchAll();

$pageTitle = 'Dashboard';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">

<div class="p-6 md:p-8 max-w-7xl mx-auto">

  <!-- Page Header -->
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
    <div>
      <p class="text-xs font-semibold text-[#8e706c] uppercase tracking-widest mb-1">Dashboard Klien</p>
      <h1 class="text-3xl font-bold text-[#191c1d]">Selamat datang, <?= sanitize(explode(' ', $user['name'])[0]) ?>!</h1>
      <p class="text-sm text-[#5a413d] mt-1">Ringkasan perkara hukum dan aktivitas terbaru Anda.</p>
    </div>
    <div class="flex gap-3">
      <a href="/client/new-case-step1.php" class="btn-gold">
        <span class="material-symbols-outlined text-[18px]">add_circle</span>
        Ajukan Kasus Baru
      </a>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="stat-card">
      <div class="flex items-center justify-between mb-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Total Kasus</p>
        <div class="w-10 h-10 rounded-xl bg-[#fff1f1] flex items-center justify-center">
          <span class="material-symbols-outlined text-[#800000]">folder_open</span>
        </div>
      </div>
      <div class="text-4xl font-extrabold text-[#191c1d]"><?= $totalCasesCount ?></div>
      <p class="text-xs text-[#5a413d] mt-2">Semua kasus terdaftar</p>
    </div>
    <div class="stat-card relative overflow-hidden">
      <div class="absolute right-0 top-0 w-24 h-24 bg-[#800000]/5 rounded-bl-full -z-0"></div>
      <div class="flex items-center justify-between mb-4 relative z-10">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#800000]">Kasus Aktif</p>
        <div class="w-10 h-10 rounded-xl bg-[#800000] flex items-center justify-center">
          <span class="material-symbols-outlined text-white" style="font-variation-settings:'FILL' 1">gavel</span>
        </div>
      </div>
      <div class="text-4xl font-extrabold text-[#800000] relative z-10"><?= $activeCasesCount ?></div>
      <p class="text-xs text-[#5a413d] mt-2 relative z-10">Sedang ditangani tim</p>
    </div>
    <div class="stat-card">
      <div class="flex items-center justify-between mb-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Invoice Belum Bayar</p>
        <div class="w-10 h-10 rounded-xl bg-[#fffbeb] flex items-center justify-center">
          <span class="material-symbols-outlined text-[#735c00]">receipt_long</span>
        </div>
      </div>
      <div class="text-4xl font-extrabold text-[#735c00]"><?= $unpaidCount ?></div>
      <p class="text-xs text-[#5a413d] mt-2">Perlu segera dibayar</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Active Cases (left 2/3) -->
    <div class="lg:col-span-2 space-y-6">

      <!-- CTA Card if no cases -->
      <?php if (empty($cases)): ?>
      <div class="card p-8 text-center">
        <div class="w-16 h-16 bg-[#fff1f1] rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="material-symbols-outlined text-[#800000] text-3xl">gavel</span>
        </div>
        <h3 class="text-xl font-bold text-[#191c1d] mb-2">Belum ada kasus terdaftar</h3>
        <p class="text-sm text-[#5a413d] mb-6">Ajukan kasus hukum Anda dan tim HPP Law Office siap membantu.</p>
        <a href="/client/new-case-step1.php" class="btn-primary">
          <span class="material-symbols-outlined text-[18px]">add_circle</span>
          Ajukan Kasus Pertama
        </a>
      </div>
      <?php else: ?>
      <!-- Cases list -->
      <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-[#e2bfb9] flex items-center justify-between">
          <h2 class="font-bold text-[#191c1d]">Kasus Aktif Saya</h2>
          <a href="/client/cases.php" class="text-sm text-[#800000] hover:underline font-medium flex items-center gap-1">
            Lihat Semua <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
          </a>
        </div>
        <div class="divide-y divide-[#edeeef]">
          <?php foreach ($cases as $case): ?>
          <div class="px-6 py-5 hover:bg-[#fafafa] transition-colors group">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-xs font-mono text-[#8e706c]"><?= $case['reference_number'] ?></span>
                  <?= statusBadge($case['status']) ?>
                </div>
                <h3 class="font-semibold text-[#191c1d] group-hover:text-[#800000] transition-colors truncate"><?= sanitize($case['title']) ?></h3>
                <p class="text-xs text-[#5a413d] mt-1">
                  Jenis: <?= sanitize($case['case_type'] ?? '-') ?>
                  <?php if ($case['lawyer_name']): ?> · PJ: <?= sanitize($case['lawyer_name']) ?><?php endif; ?>
                </p>
              </div>
              <a href="/client/case-detail.php?id=<?= $case['id'] ?>" class="text-[#800000] hover:bg-[#fff1f1] p-1.5 rounded-lg transition-colors shrink-0">
                <span class="material-symbols-outlined text-[20px]">arrow_forward_ios</span>
              </a>
            </div>
            <!-- Mini progress bar -->
            <?php
              $prog = ['new'=>10,'active'=>50,'on_hold'=>30,'closed'=>100,'won'=>100,'lost'=>100];
              $pct  = $prog[$case['status']] ?? 0;
            ?>
            <div class="mt-3 h-1 bg-[#edeeef] rounded-full overflow-hidden">
              <div class="h-full bg-[#800000] rounded-full transition-all" style="width:<?= $pct ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Recent Activity -->
      <?php if (!empty($activities)): ?>
      <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-[#e2bfb9]">
          <h2 class="font-bold text-[#191c1d]">Aktivitas Terbaru</h2>
        </div>
        <div class="divide-y divide-[#edeeef]">
          <?php foreach ($activities as $act): ?>
          <div class="px-6 py-4 flex items-start gap-4">
            <div class="w-9 h-9 rounded-full bg-[#fff1f1] flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[#800000] text-[18px]">update</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-[#191c1d]">
                <span class="font-semibold"><?= sanitize($act['actor_name']) ?></span>
                memperbarui status: <span class="font-medium text-[#800000]"><?= sanitize($act['status_label']) ?></span>
              </p>
              <p class="text-xs text-[#5a413d] mt-0.5 truncate"><?= sanitize($act['case_title']) ?></p>
              <?php if ($act['description_layman']): ?>
              <p class="text-xs text-[#8e706c] mt-1 italic"><?= sanitize($act['description_layman']) ?></p>
              <?php endif; ?>
            </div>
            <span class="text-xs text-[#8e706c] whitespace-nowrap shrink-0"><?= timeAgo($act['created_at']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right column -->
    <div class="space-y-6">

      <!-- Pending invoices -->
      <?php if (!empty($invoices)): ?>
      <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-[#e2bfb9] flex items-center justify-between">
          <h2 class="font-bold text-[#191c1d] flex items-center gap-2">
            <span class="material-symbols-outlined text-red-500 text-[20px]">assignment_late</span>
            Perlu Tindakan
          </h2>
          <span class="text-xs bg-red-100 text-red-700 font-bold px-2 py-0.5 rounded-full"><?= count($invoices) ?></span>
        </div>
        <div class="divide-y divide-[#edeeef]">
          <?php foreach ($invoices as $inv): ?>
          <div class="p-4">
            <div class="border-l-4 border-red-400 pl-3">
              <p class="text-sm font-semibold text-[#191c1d]">Invoice <?= sanitize($inv['invoice_number']) ?></p>
              <p class="text-xs text-[#5a413d] mt-0.5"><?= sanitize($inv['case_title']) ?></p>
              <p class="text-sm font-bold text-[#800000] mt-1"><?= formatRupiah($inv['amount']) ?></p>
              <a href="/client/invoice.php?id=<?= $inv['id'] ?>" class="text-xs text-[#800000] hover:underline font-medium mt-2 inline-block">
                <?= $inv['status'] === 'unpaid' ? 'Bayar Sekarang →' : 'Lihat Status →' ?>
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Quick actions -->
      <div class="card p-6">
        <h2 class="font-bold text-[#191c1d] mb-4">Aksi Cepat</h2>
        <div class="space-y-2">
          <?php
          $quickActions = [
            ['icon'=>'add_circle','label'=>'Ajukan Kasus Baru','url'=>'/client/new-case-step1.php','color'=>'text-[#800000]'],
            ['icon'=>'folder_open','label'=>'Lihat Semua Kasus','url'=>'/client/cases.php','color'=>'text-[#5a413d]'],
            ['icon'=>'receipt_long','label'=>'Invoice & Pembayaran','url'=>'/client/invoice.php','color'=>'text-[#735c00]'],
            ['icon'=>'person','label'=>'Perbarui Profil','url'=>'/client/profile.php','color'=>'text-[#5a413d]'],
          ];
          foreach ($quickActions as $qa): ?>
          <a href="<?= $qa['url'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#f3f4f5] transition-colors group">
            <span class="material-symbols-outlined text-[20px] <?= $qa['color'] ?>"><?= $qa['icon'] ?></span>
            <span class="text-sm text-[#191c1d] group-hover:text-[#800000] transition-colors"><?= $qa['label'] ?></span>
            <span class="material-symbols-outlined text-[16px] text-[#e1e3e4] group-hover:text-[#800000] ml-auto transition-colors">arrow_forward_ios</span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Contact info -->
      <div class="card p-6 bg-gradient-to-br from-[#800000] to-[#570000] text-white">
        <h2 class="font-bold mb-3 flex items-center gap-2">
          <span class="material-symbols-outlined text-[#fed65b]" style="font-variation-settings:'FILL' 1">support_agent</span>
          Butuh Bantuan?
        </h2>
        <p class="text-sm text-white/80 mb-4">Hubungi tim HPP Law Office untuk konsultasi.</p>
        <div class="space-y-2 text-sm">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[#fed65b] text-[16px]">call</span>
            <span>+62 21 1234 5678</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[#fed65b] text-[16px]">mail</span>
            <span>info@hpplawoffice.id</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[#fed65b] text-[16px]">schedule</span>
            <span>Sen–Jum, 08:00–17:00</span>
          </div>
        </div>
        <a href="https://wa.me/6281234567890" target="_blank"
          class="mt-4 inline-flex items-center gap-2 bg-white text-[#800000] font-semibold text-sm px-4 py-2 rounded-lg hover:bg-[#fed65b] transition-colors">
          <span class="material-symbols-outlined text-[18px]">chat</span>
          Chat via WhatsApp
        </a>
      </div>
    </div>
  </div>

</div><!-- /p-6 -->
</div><!-- /#page-content -->
</div><!-- /#app-wrapper -->

<?php
$extraScript = '<script>
document.querySelectorAll(".btn-menu").forEach(btn=>{
  btn.addEventListener("click", openSidebar);
});
</script>';
include __DIR__ . "/../includes/footer.php";
?>
