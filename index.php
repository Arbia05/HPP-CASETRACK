<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect logged-in users to their dashboard
if (isLoggedIn()) {
    $redirects = ROLE_REDIRECTS;
    redirect($redirects[currentUser()['role']] ?? '/client/dashboard.php');
}

$pageTitle = 'HPP Law Office';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>HPP Law Office — Firma Hukum Terpercaya di Indonesia</title>
<meta name="description" content="HPP Law Office menyediakan layanan hukum litigasi dan non-litigasi profesional. Kami siap mendampingi klien dalam menyelesaikan berbagai permasalahan hukum."/>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/css/app.css"/>
<style>
body { font-family: 'Plus Jakarta Sans', sans-serif; }
.hero-bg {
  background: linear-gradient(135deg, #2d0000 0%, #570000 45%, #800000 100%);
  position: relative;
  overflow: hidden;
}
.hero-bg::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 600px;
  height: 600px;
  border-radius: 50%;
  background: rgba(254,214,91,0.06);
  pointer-events: none;
}
.service-card { transition: all 0.25s; border: 1px solid #e2bfb9; background: #fff; border-radius: 16px; padding: 28px; }
.service-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(128,0,0,0.1); border-color: #800000; }
.lawyer-card { border-radius: 16px; overflow: hidden; background: #fff; border: 1px solid #e1e3e4; transition: all 0.25s; }
.lawyer-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.1); }
.nav-link { font-size: 14px; font-weight: 500; color: rgba(255,255,255,0.8); transition: color 0.15s; }
.nav-link:hover { color: #fed65b; }
@keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
.float { animation: float 4s ease-in-out infinite; }
</style>
</head>
<body class="bg-white text-[#191c1d]">

<!-- Navigation -->
<nav class="fixed top-0 w-full z-50 bg-[#570000]/95 backdrop-blur-md border-b border-white/10 shadow-lg">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
    <a href="/" class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-[#fed65b] flex items-center justify-center shadow">
        <span class="material-symbols-outlined text-[#800000] text-xl" style="font-variation-settings:'FILL' 1">gavel</span>
      </div>
      <div>
        <span class="text-white font-bold text-lg leading-tight block">HPP Law Office</span>
        <span class="text-white/50 text-[10px] uppercase tracking-widest">CaseTrack System</span>
      </div>
    </a>
    <div class="hidden md:flex items-center gap-8">
      <a href="#layanan" class="nav-link">Layanan</a>
      <a href="#tim" class="nav-link">Tim Kami</a>
      <a href="#tentang" class="nav-link">Tentang</a>
      <a href="#kontak" class="nav-link">Kontak</a>
    </div>
    <div class="flex gap-3">
      <a href="login.php" class="px-4 py-2 text-sm font-semibold text-[#fed65b] border border-[#fed65b]/30 rounded-lg hover:bg-[#fed65b]/10 transition-colors">Masuk</a>
      <a href="register.php" class="px-4 py-2 text-sm font-semibold bg-[#fed65b] text-[#570000] rounded-lg hover:bg-[#ffe088] transition-colors shadow-sm">Daftar</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero-bg min-h-screen flex items-center pt-16">
  <div class="max-w-7xl mx-auto px-6 py-24 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
    <div>
      <div class="inline-flex items-center gap-2 bg-white/10 text-[#fed65b] text-xs font-semibold px-4 py-2 rounded-full mb-8 border border-[#fed65b]/20">
        <span class="material-symbols-outlined text-[16px]">verified</span>
        Firma Hukum Berpengalaman & Terpercaya
      </div>
      <h1 class="text-white font-extrabold text-5xl xl:text-6xl leading-tight mb-6">
        Solusi Hukum <span class="text-[#fed65b]">Profesional</span> untuk Setiap Kebutuhan Anda
      </h1>
      <p class="text-white/70 text-lg leading-relaxed mb-10 max-w-lg">
        HPP Law Office menyediakan layanan hukum komprehensif dengan pendekatan yang humanis dan berorientasi pada solusi terbaik bagi klien.
      </p>
      <div class="flex flex-wrap gap-4">
        <a href="register.php" class="btn-gold px-8 py-4 text-base shadow-lg">
          <span class="material-symbols-outlined text-[20px]">gavel</span>
          Konsultasikan Kasus Anda
        </a>
        <a href="#layanan" class="px-8 py-4 text-base font-semibold text-white border-2 border-white/30 rounded-lg hover:bg-white/10 transition-colors inline-flex items-center gap-2">
          <span class="material-symbols-outlined text-[20px]">info</span>
          Pelajari Layanan
        </a>
      </div>
      <!-- Stats row -->
      <div class="flex gap-8 mt-12 pt-8 border-t border-white/10">
        <?php foreach ([['100+','Kasus Ditangani'],['15+','Tahun Pengalaman'],['98%','Tingkat Kepuasan']] as [$n,$l]): ?>
        <div>
          <div class="text-[#fed65b] text-2xl font-extrabold"><?= $n ?></div>
          <div class="text-white/60 text-xs mt-0.5"><?= $l ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- Hero visual -->
    <div class="hidden lg:flex justify-center relative">
      <div class="float w-72 h-72 xl:w-80 xl:h-80 bg-white/8 rounded-3xl border border-white/10 flex items-center justify-center shadow-2xl relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#fed65b]/10 to-transparent"></div>
        <div class="text-center relative z-10">
          <span class="material-symbols-outlined text-[#fed65b] text-8xl" style="font-variation-settings:'FILL' 1">balance</span>
          <p class="text-white font-bold text-xl mt-4">Hukum & Keadilan</p>
          <p class="text-white/60 text-sm mt-1">Untuk Semua</p>
        </div>
        <div class="absolute -bottom-6 -right-6 w-32 h-32 rounded-full bg-[#fed65b]/10"></div>
        <div class="absolute -top-6 -left-6 w-24 h-24 rounded-full bg-white/5"></div>
      </div>
    </div>
  </div>
</section>

<!-- Services -->
<section id="layanan" class="py-24 bg-[#f8f9fa]">
  <div class="max-w-7xl mx-auto px-6">
    <div class="text-center mb-16">
      <p class="text-xs font-semibold text-[#800000] uppercase tracking-widest mb-3">Layanan Kami</p>
      <h2 class="text-4xl font-extrabold text-[#191c1d] mb-4">Area Praktik Hukum</h2>
      <p class="text-[#5a413d] max-w-2xl mx-auto">Kami menyediakan layanan hukum menyeluruh, baik litigasi maupun non-litigasi, dengan tim yang berpengalaman di berbagai bidang hukum.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php
      $services = [
        ['balance','Hukum Perdata','Penyelesaian sengketa perdata, wanprestasi, perbuatan melawan hukum melalui mediasi atau litigasi.','#800000'],
        ['gavel','Hukum Pidana','Pendampingan hukum dalam perkara pidana, mulai dari penyelidikan hingga persidangan.','#570000'],
        ['home_work','Sengketa Tanah & Properti','Penanganan sengketa kepemilikan, hak guna, sertifikat tanah, dan masalah properti lainnya.','#800000'],
        ['corporate_fare','Hukum Bisnis & Korporat','Pendirian perusahaan, kontrak bisnis, merger & akuisisi, dan kepatuhan korporat.','#570000'],
        ['family_restroom','Hukum Keluarga','Perceraian, hak asuh anak, waris, dan masalah hukum keluarga lainnya.','#800000'],
        ['account_balance','Tata Usaha Negara','Gugatan PTUN, sengketa perizinan, dan perkara administrasi negara.','#570000'],
      ];
      foreach ($services as [$icon,$title,$desc,$color]):
      ?>
      <div class="service-card">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-5" style="background:<?= $color ?>15">
          <span class="material-symbols-outlined text-2xl" style="color:<?= $color ?>"><?= $icon ?></span>
        </div>
        <h3 class="font-bold text-[#191c1d] text-lg mb-3"><?= $title ?></h3>
        <p class="text-[#5a413d] text-sm leading-relaxed"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Team -->
<section id="tim" class="py-24 bg-white">
  <div class="max-w-7xl mx-auto px-6">
    <div class="text-center mb-16">
      <p class="text-xs font-semibold text-[#800000] uppercase tracking-widest mb-3">Tim Kami</p>
      <h2 class="text-4xl font-extrabold text-[#191c1d] mb-4">Pengacara & Advokat</h2>
      <p class="text-[#5a413d]">Tim profesional kami siap memberikan pendampingan hukum terbaik.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php
      $lawyers = $db->query("SELECT u.*, lw.specialization, lw.years_experience, lw.bio FROM users u JOIN lawyers lw ON u.id=lw.user_id WHERE u.status='active' LIMIT 6")->fetchAll();
      foreach ($lawyers as $lw):
      ?>
      <div class="lawyer-card">
        <div class="h-36 bg-gradient-to-br from-[#800000] to-[#570000] flex items-center justify-center relative">
          <div class="w-20 h-20 rounded-full bg-[#fed65b] flex items-center justify-center text-[#800000] font-extrabold text-3xl shadow-xl">
            <?= strtoupper(substr($lw['name'], 0, 1)) ?>
          </div>
        </div>
        <div class="p-5">
          <h3 class="font-bold text-lg text-[#191c1d]"><?= sanitize($lw['name']) ?></h3>
          <p class="text-sm text-[#800000] font-medium mt-0.5"><?= sanitize($lw['specialization'] ?? 'Advokat') ?></p>
          <?php if ($lw['years_experience']): ?>
          <p class="text-xs text-[#8e706c] mt-1"><?= $lw['years_experience'] ?> tahun pengalaman</p>
          <?php endif; ?>
          <?php if ($lw['bio']): ?>
          <p class="text-sm text-[#5a413d] mt-3 leading-relaxed line-clamp-3"><?= sanitize($lw['bio']) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($lawyers)): ?>
      <?php foreach (['Budi Santoso, S.H., M.H.' => 'Litigasi Perdata & Pidana','Sari Wulandari, S.H.' => 'Hukum Bisnis & Korporat','Ahmad Faisal, S.H., M.Kn.' => 'Sengketa Tanah & Properti'] as $name => $spec): ?>
      <div class="lawyer-card">
        <div class="h-36 bg-gradient-to-br from-[#800000] to-[#570000] flex items-center justify-center">
          <div class="w-20 h-20 rounded-full bg-[#fed65b] flex items-center justify-center text-[#800000] font-extrabold text-3xl shadow-xl">
            <?= strtoupper(substr($name,0,1)) ?>
          </div>
        </div>
        <div class="p-5">
          <h3 class="font-bold text-lg text-[#191c1d]"><?= $name ?></h3>
          <p class="text-sm text-[#800000] font-medium mt-0.5"><?= $spec ?></p>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section id="tentang" class="py-24 bg-gradient-to-br from-[#570000] to-[#800000]">
  <div class="max-w-4xl mx-auto px-6 text-center">
    <span class="material-symbols-outlined text-[#fed65b] text-5xl mb-6 block" style="font-variation-settings:'FILL' 1">support_agent</span>
    <h2 class="text-4xl font-extrabold text-white mb-6">Siap Mendampingi Kasus Hukum Anda</h2>
    <p class="text-white/80 text-lg mb-10 max-w-2xl mx-auto">
      Daftarkan diri Anda dan ajukan kasus hukum secara digital. Tim HPP Law Office akan menghubungi Anda dalam 1×24 jam kerja.
    </p>
    <div class="flex flex-wrap justify-center gap-4">
      <a href="register.php" class="btn-gold px-8 py-4 text-base shadow-xl">
        <span class="material-symbols-outlined">gavel</span>
        Mulai Konsultasi Gratis
      </a>
      <a href="https://wa.me/6281234567890" target="_blank"
        class="px-8 py-4 text-base font-semibold text-white border-2 border-white/30 rounded-lg hover:bg-white/10 transition-colors inline-flex items-center gap-2">
        <span class="material-symbols-outlined">chat</span>
        Chat WhatsApp
      </a>
    </div>
  </div>
</section>

<!-- Contact -->
<section id="kontak" class="py-24 bg-[#f8f9fa]">
  <div class="max-w-7xl mx-auto px-6">
    <div class="text-center mb-16">
      <h2 class="text-4xl font-extrabold text-[#191c1d] mb-4">Hubungi Kami</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php foreach ([
        ['location_on','Alamat','Jl. HR Rasuna Said Kav C-22, Kuningan, Jakarta Selatan 12940'],
        ['call','Telepon','(021) 1234-5678 / +62 812-3456-789'],
        ['mail','Email','info@hpplawoffice.id | konsultasi@hpplawoffice.id'],
        ['schedule','Jam Operasional','Senin – Jumat: 08.00 – 17.00 WIB'],
      ] as [$icon,$label,$val]): ?>
      <div class="card p-6 text-center">
        <div class="w-12 h-12 bg-[#fff1f1] rounded-xl flex items-center justify-center mx-auto mb-4">
          <span class="material-symbols-outlined text-[#800000] text-2xl"><?= $icon ?></span>
        </div>
        <h3 class="font-bold text-[#191c1d] mb-2"><?= $label ?></h3>
        <p class="text-sm text-[#5a413d] leading-relaxed"><?= $val ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-[#2d0000] text-white py-12">
  <div class="max-w-7xl mx-auto px-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-[#fed65b] flex items-center justify-center">
          <span class="material-symbols-outlined text-[#800000] text-xl" style="font-variation-settings:'FILL' 1">gavel</span>
        </div>
        <div>
          <p class="font-bold">HPP Law Office</p>
          <p class="text-white/40 text-xs">Powered by HPP CaseTrack</p>
        </div>
      </div>
      <div class="flex gap-6 text-sm text-white/60">
        <a href="#layanan" class="hover:text-white transition-colors">Layanan</a>
        <a href="#tim" class="hover:text-white transition-colors">Tim</a>
        <a href="login.php" class="hover:text-white transition-colors">Login</a>
        <a href="register.php" class="hover:text-white transition-colors">Daftar</a>
      </div>
      <p class="text-white/40 text-xs">&copy; <?= date('Y') ?> HPP Law Office. All rights reserved.</p>
    </div>
  </div>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
