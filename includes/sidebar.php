<?php
// includes/sidebar.php — Role-based sidebar navigation
$user = currentUser();
$role = $user['role'] ?? '';
$base = getBasePath();

$navItems = [
  'client' => [
    ['icon'=>'home',         'label'=>'Dashboard',      'url'=>'client/dashboard.php'],
    ['icon'=>'folder_open',  'label'=>'Kasus Saya',     'url'=>'client/cases.php'],
    ['icon'=>'add_circle',   'label'=>'Ajukan Kasus',   'url'=>'client/new-case-step1.php'],
    ['icon'=>'payments',     'label'=>'Invoice & Bayar','url'=>'client/invoice.php'],
    ['icon'=>'person',       'label'=>'Profil Saya',    'url'=>'client/profile.php'],
  ],
  'legal_admin' => [
    ['icon'=>'dashboard',    'label'=>'Dashboard',      'url'=>'legal-admin/dashboard.php'],
    ['icon'=>'inbox',        'label'=>'Pengaduan',      'url'=>'legal-admin/complaints.php'],
    ['icon'=>'gavel',        'label'=>'Manajemen Kasus','url'=>'legal-admin/cases.php'],
    ['icon'=>'event',        'label'=>'Jadwal & Milestone','url'=>'legal-admin/schedule.php'],
    ['icon'=>'group',        'label'=>'Staff Pending',  'url'=>'legal-admin/staff-pending.php'],
  ],
  'ceo' => [
    ['icon'=>'dashboard',    'label'=>'Dashboard',      'url'=>'ceo/dashboard.php'],
    ['icon'=>'history_edu',  'label'=>'Persetujuan Kontrak','url'=>'ceo/contracts.php'],
    ['icon'=>'folder_shared','label'=>'Semua Kasus',   'url'=>'ceo/cases.php'],
    ['icon'=>'bar_chart',    'label'=>'Laporan Kinerja','url'=>'ceo/reports.php'],
  ],
  'pengacara_pj' => [
    ['icon'=>'dashboard',    'label'=>'Dashboard',      'url'=>'pengacara-pj/dashboard.php'],
    ['icon'=>'folder_shared','label'=>'Kasus Saya',     'url'=>'pengacara-pj/cases.php'],
    ['icon'=>'group_add',    'label'=>'Penugasan Tim',  'url'=>'pengacara-pj/assign-team.php'],
    ['icon'=>'strategy',     'label'=>'Strategi Kasus', 'url'=>'pengacara-pj/strategy.php'],
  ],
  'lawyer' => [
    ['icon'=>'dashboard',    'label'=>'Dashboard',      'url'=>'lawyer/dashboard.php'],
    ['icon'=>'folder_open',  'label'=>'Kasus Saya',     'url'=>'lawyer/cases.php'],
    ['icon'=>'my_location',  'label'=>'Update Lapangan','url'=>'lawyer/field-update.php'],
    ['icon'=>'receipt_long', 'label'=>'Reimbursement',  'url'=>'lawyer/reimbursement.php'],
  ],
  'finance' => [
    ['icon'=>'dashboard',       'label'=>'Dashboard',        'url'=>'finance/dashboard.php'],
    ['icon'=>'receipt_long',    'label'=>'Invoice',          'url'=>'finance/invoices.php'],
    ['icon'=>'account_balance_wallet','label'=>'Manajemen Fee','url'=>'finance/fees.php'],
    ['icon'=>'currency_exchange','label'=>'Reimbursement',   'url'=>'finance/reimbursements.php'],
  ],
  'head_finance' => [
    ['icon'=>'dashboard',    'label'=>'Dashboard',      'url'=>'head-finance/dashboard.php'],
    ['icon'=>'fact_check',   'label'=>'Otorisasi Reimbursement','url'=>'head-finance/reimbursement-approval.php'],
    ['icon'=>'bar_chart',    'label'=>'Laporan Keuangan','url'=>'head-finance/financial-overview.php'],
  ],
  'admin' => [
    ['icon'=>'dashboard',    'label'=>'Dashboard',      'url'=>'admin/dashboard.php'],
    ['icon'=>'manage_accounts','label'=>'Manajemen User','url'=>'admin/users.php'],
    ['icon'=>'person_add',   'label'=>'Tambah User',    'url'=>'admin/add-user.php'],
    ['icon'=>'settings',     'label'=>'Pengaturan Sistem','url'=>'admin/settings.php'],
  ],
];

$currentItems = $navItems[$role] ?? [];
$currentPath  = $_SERVER['SCRIPT_NAME'];
?>

<!-- Sidebar -->
<aside id="sidebar"
  class="fixed left-0 top-0 h-full w-[280px] bg-[#800000] text-white z-50 flex flex-col shadow-xl
         transition-transform duration-300 md:translate-x-0"
  style="-webkit-transition: transform 0.3s;">

  <!-- Brand -->
  <div class="px-6 pt-6 pb-5 border-b border-white/10">
    <a href="/<?= $base ?>index.php" class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-[#fed65b] flex items-center justify-center shadow">
        <span class="material-symbols-outlined text-[#800000] text-xl font-bold">gavel</span>
      </div>
      <div>
        <h1 class="text-base font-bold leading-tight">HPP CaseTrack</h1>
        <p class="text-[10px] text-white/60 uppercase tracking-widest">Law Office Management</p>
      </div>
    </a>
  </div>

  <!-- Navigation -->
  <nav class="flex-1 px-3 py-4 overflow-y-auto custom-scrollbar">
    <ul class="space-y-1">
      <?php foreach ($currentItems as $item):
        $href = $base . $item['url'];
        $isActive = str_ends_with($currentPath, $item['url']) || str_ends_with($currentPath, str_replace('.php','',$item['url']));
      ?>
      <li>
        <a href="/<?= $href ?>"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 group
                  <?= $isActive
                    ? 'bg-white/15 border-l-4 border-[#fed65b] text-white font-semibold'
                    : 'text-white/75 hover:bg-white/8 hover:text-white border-l-4 border-transparent' ?>">
          <span class="material-symbols-outlined text-[20px] <?= $isActive ? 'text-[#fed65b]' : '' ?>"
                style="<?= $isActive ? "font-variation-settings:'FILL' 1" : '' ?>"><?= $item['icon'] ?></span>
          <span class="text-sm"><?= $item['label'] ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <!-- User Info -->
  <div class="px-4 py-4 border-t border-white/10">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-full bg-[#fed65b] flex items-center justify-center text-[#800000] font-bold text-sm shrink-0">
        <?= strtoupper(substr($user['name'], 0, 1)) ?>
      </div>
      <div class="min-w-0">
        <p class="text-sm font-semibold truncate"><?= sanitize($user['name']) ?></p>
        <p class="text-xs text-white/60 truncate"><?= ROLE_NAMES[$role] ?? $role ?></p>
      </div>
      <a href="<?= $base ?>api/auth/logout.php" class="ml-auto p-1.5 rounded-lg hover:bg-white/10 text-white/60 hover:text-white transition-colors" title="Keluar">
        <span class="material-symbols-outlined text-[18px]">logout</span>
      </a>
    </div>
  </div>
</aside>

<!-- Overlay for mobile -->
<div id="sidebar-overlay" onclick="closeSidebar()"
  class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>
