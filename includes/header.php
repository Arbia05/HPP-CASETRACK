<?php
// includes/header.php — Shared HTML head + top nav
$base = getBasePath();
$user = currentUser();
$unread = $user ? getUnreadCount($user['id']) : 0;
$roleNames = ROLE_NAMES;
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= $pageTitle ?? 'HPP CaseTrack' ?> — HPP Law Office</title>
<meta name="description" content="HPP CaseTrack - Sistem Informasi Manajemen Perkara HPP Law Office"/>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        primary:   { DEFAULT: '#570000', 50:'#fff1f1', 100:'#ffdada', 500:'#800000', 600:'#570000', 700:'#410000' },
        gold:      { DEFAULT: '#fed65b', dark:'#735c00', light:'#ffe088' },
        maroon:    '#800000',
      },
      fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] }
    }
  }
}
</script>
<link rel="stylesheet" href="<?= $base ?>assets/css/app.css"/>
<script>window.APP_BASE = '<?= rtrim(getBasePath(), '/') ?>';</script>
<?= $extraHead ?? '' ?>
</head>
<body class="bg-[#f8f9fa] text-[#191c1d] font-sans antialiased">

<?php if ($user): ?>
<!-- Top App Bar (internal pages) -->
<header class="fixed top-0 right-0 z-40 h-16 bg-white border-b border-[#e2bfb9]
  flex items-center justify-between px-6 shadow-sm
  transition-all duration-200"
  style="left: 280px;" id="topbar">
  <!-- Search -->
  <div class="hidden md:flex items-center relative">
    <span class="material-symbols-outlined absolute left-3 text-[#8e706c] text-[20px]">search</span>
    <input type="text" placeholder="Cari kasus, klien, dokumen..."
      class="pl-10 pr-4 py-2 bg-[#f3f4f5] border border-[#e1e3e4] rounded-lg text-sm
             focus:outline-none focus:border-[#800000] focus:ring-1 focus:ring-[#800000]/10 w-72"/>
  </div>
  <!-- Right actions -->
  <div class="flex items-center gap-3 ml-auto">
    <!-- Notifications -->
    <div class="relative" id="notif-wrapper">
      <button onclick="toggleNotif()" class="relative p-2 rounded-full text-[#5a413d] hover:bg-[#edeeef] transition-colors">
        <span class="material-symbols-outlined">notifications</span>
        <?php if ($unread > 0): ?>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-[#800000] rounded-full animate-pulse"></span>
        <?php endif; ?>
      </button>
      <!-- Dropdown -->
      <div id="notif-dropdown" class="hidden absolute right-0 top-12 w-80 bg-white rounded-xl shadow-xl border border-[#e1e3e4] z-50 overflow-hidden">
        <div class="p-4 border-b border-[#e1e3e4] flex justify-between items-center">
          <h3 class="font-semibold text-sm">Notifikasi</h3>
          <button onclick="markAllReadAjax()" class="text-xs text-[#800000] hover:underline">Tandai semua dibaca</button>
        </div>
        <div id="notif-list" class="max-h-72 overflow-y-auto divide-y divide-[#e1e3e4]">
          <p class="p-4 text-sm text-gray-400 text-center">Memuat...</p>
        </div>
      </div>
    </div>
    <!-- User -->
    <div class="relative" id="user-wrapper">
      <button onclick="toggleUserMenu()" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-[#edeeef] transition-colors">
        <div class="w-8 h-8 rounded-full bg-[#800000] flex items-center justify-center text-white text-sm font-bold">
          <?= strtoupper(substr($user['name'], 0, 1)) ?>
        </div>
        <div class="hidden md:block text-left">
          <p class="text-sm font-semibold leading-tight"><?= sanitize($user['name']) ?></p>
          <p class="text-xs text-[#8e706c]"><?= $roleNames[$user['role']] ?? $user['role'] ?></p>
        </div>
        <span class="material-symbols-outlined text-[18px] text-[#8e706c]">expand_more</span>
      </button>
      <!-- User dropdown -->
      <div id="user-dropdown" class="hidden absolute right-0 top-12 w-52 bg-white rounded-xl shadow-xl border border-[#e1e3e4] z-50 overflow-hidden">
        <a href="/hpp-casetrack/profile.php" class="flex items-center gap-3 px-4 py-3 hover:bg-[#f3f4f5] text-sm transition-colors">
          <span class="material-symbols-outlined text-[20px]">person</span> Profil Saya
        </a>
        <hr class="border-[#e1e3e4]"/>
        <a href="<?= $base ?>api/auth/logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 text-red-600 text-sm transition-colors">
          <span class="material-symbols-outlined text-[20px]">logout</span> Keluar
        </a>
      </div>
    </div>
  </div>
</header>
<?php endif; ?>
