<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $redirects = ROLE_REDIRECTS;
    redirect($redirects[currentUser()['role']] ?? '/client/dashboard.php');
}

if (isPost()) {
    $rawEmail    = trim($_POST['email'] ?? '');
    $rawPassword = $_POST['password'] ?? '';
    $email       = sanitize($rawEmail);
    $password    = $rawPassword;

    if ($email === '' && $password === '') {
        $error = 'Email dan password wajib diisi.';
    } elseif ($email === '') {
        $error = 'Email wajib diisi.';
    } elseif ($password === '') {
        $error = 'Password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid. Contoh: nama@domain.com';
    } else {
        $result = login($email, $password);
        if ($result['success']) {
            redirect($result['redirect']);
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Login — HPP CaseTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/css/app.css"/>
<style>
  body { font-family: 'Plus Jakarta Sans', sans-serif; }
  .hero-gradient { background: linear-gradient(135deg, #570000 0%, #800000 40%, #9a0000 100%); }
  .input-focus:focus { border-color: #800000; box-shadow: 0 0 0 3px rgba(128,0,0,0.1); }
</style>
</head>
<body class="bg-[#f8f9fa] min-h-screen flex">

<!-- Left Hero Panel -->
<div class="hidden lg:flex lg:w-1/2 xl:w-3/5 hero-gradient relative overflow-hidden flex-col justify-center px-16">
  <!-- Abstract background shapes -->
  <div class="absolute inset-0 overflow-hidden">
    <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-white/5"></div>
    <div class="absolute bottom-10 -left-10 w-60 h-60 rounded-full bg-white/5"></div>
    <div class="absolute top-1/2 right-10 w-32 h-32 rounded-full bg-[#fed65b]/10"></div>
  </div>
  <div class="relative z-10 max-w-lg">
    <!-- Logo -->
    <div class="flex items-center gap-3 mb-12">
      <div class="w-12 h-12 rounded-xl bg-[#fed65b] flex items-center justify-center shadow-lg">
        <span class="material-symbols-outlined text-[#800000] text-2xl" style="font-variation-settings:'FILL' 1">gavel</span>
      </div>
      <div>
        <h1 class="text-white font-bold text-xl leading-tight">HPP CaseTrack</h1>
        <p class="text-white/60 text-xs uppercase tracking-widest">Law Office Management</p>
      </div>
    </div>
    <!-- Headline -->
    <h2 class="text-white font-bold text-4xl xl:text-5xl leading-tight mb-6">
      Kelola Perkara <span class="text-[#fed65b]">Lebih Cerdas,</span> Lebih Terstruktur.
    </h2>
    <p class="text-white/70 text-lg leading-relaxed mb-10">
      Platform manajemen kasus hukum terpadu untuk HPP Law Office. Dari pengaduan klien hingga penyelesaian keuangan — semua dalam satu ekosistem digital.
    </p>
    <!-- Stats -->
    <div class="grid grid-cols-3 gap-6">
      <?php foreach ([['100+','Kasus Ditangani'],['8','Tim Pengacara'],['98%','Kepuasan Klien']] as [$num,$label]): ?>
      <div class="text-center">
        <div class="text-[#fed65b] text-3xl font-extrabold"><?= $num ?></div>
        <div class="text-white/60 text-sm mt-1"><?= $label ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Right Auth Panel -->
<div class="w-full lg:w-1/2 xl:w-2/5 flex flex-col justify-center items-center px-8 py-12 bg-white min-h-screen shadow-2xl relative z-10">
  <!-- Mobile logo -->
  <div class="lg:hidden flex items-center gap-3 mb-10">
    <div class="w-10 h-10 rounded-xl bg-[#800000] flex items-center justify-center">
      <span class="material-symbols-outlined text-[#fed65b] text-xl" style="font-variation-settings:'FILL' 1">gavel</span>
    </div>
    <span class="text-[#800000] font-bold text-xl">HPP CaseTrack</span>
  </div>

  <div class="w-full max-w-md">
    <div class="mb-8">
      <h2 class="text-3xl font-bold text-[#191c1d] mb-2">Selamat Datang</h2>
      <p class="text-[#5a413d] text-sm">Masuk ke akun Anda untuk melanjutkan.</p>
    </div>

    <!-- Flash error -->
    <?php if (isset($error)): ?>
    <div id="flash-error" class="alert alert-error mb-6">
      <span class="material-symbols-outlined text-[18px]">error</span>
      <?= sanitize($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="login-form" novalidate class="space-y-5">
      <!-- Email -->
      <div>
        <label class="form-label" for="email">Email</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">mail</span>
          <input type="email" id="email" name="email"
            value="<?= sanitize($_POST['email'] ?? '') ?>"
            placeholder="admin@hpp-casetrack.id"
            class="form-input input-focus pl-10"
            autocomplete="email"/>
        </div>
        <!-- Peringatan validasi email -->
        <p id="email-error-required" class="hidden mt-1.5 text-xs text-red-600 flex items-center gap-1 font-medium">
          <span class="material-symbols-outlined text-[14px]">error</span>
          Email wajib diisi.
        </p>
        <p id="email-error-format" class="hidden mt-1.5 text-xs text-red-600 flex items-center gap-1 font-medium">
          <span class="material-symbols-outlined text-[14px]">error</span>
          Format email tidak valid. Contoh: <strong>nama@domain.com</strong>
        </p>
      </div>
      <!-- Password -->
      <div>
        <div class="flex justify-between mb-1.5">
          <label class="form-label" for="password">Password</label>
          <a href="forgot-password.php" class="text-xs text-[#800000] hover:underline font-medium">Lupa password?</a>
        </div>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">lock</span>
          <input type="password" id="password" name="password"
            placeholder="••••••••"
            class="form-input input-focus pl-10 pr-10"
            autocomplete="current-password"/>
          <button type="button" onclick="togglePwd()" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8e706c] hover:text-[#800000] transition-colors">
            <span class="material-symbols-outlined text-[20px]" id="pwd-icon">visibility_off</span>
          </button>
        </div>
        <!-- Peringatan validasi password -->
        <p id="password-error-required" class="hidden mt-1.5 text-xs text-red-600 flex items-center gap-1 font-medium">
          <span class="material-symbols-outlined text-[14px]">error</span>
          Password wajib diisi.
        </p>
      </div>
      <!-- Submit -->
      <button type="submit" id="btn-submit" class="btn-primary w-full justify-center py-3 text-base mt-2 transition-all">
        <span class="material-symbols-outlined text-[20px]" id="btn-icon">login</span>
        <span id="btn-text">Masuk</span>
      </button>
    </form>

    <div class="relative my-8">
      <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[#e1e3e4]"></div></div>
      <div class="relative flex justify-center"><span class="px-4 bg-white text-xs text-[#8e706c] uppercase tracking-wider">atau</span></div>
    </div>

    <a href="register.php" class="btn-outline w-full justify-center py-3 text-base">
      <span class="material-symbols-outlined text-[20px]">person_add</span>
      Daftar Akun Klien Baru
    </a>

    <!-- Security note -->
    <div class="mt-8 flex items-center justify-center gap-2 text-[#8e706c] text-xs">
      <span class="material-symbols-outlined text-[16px]">verified_user</span>
      Koneksi terenkripsi & aman
    </div>

    <!-- Demo accounts -->
    <div class="mt-8 p-4 bg-[#f3f4f5] rounded-xl border border-[#e1e3e4]">
      <p class="text-xs font-semibold text-[#5a413d] mb-3 uppercase tracking-wider">Akun Demo (password: password)</p>
      <div class="grid grid-cols-2 gap-2 text-xs text-[#5a413d]">
        <?php
        $demos = [
          'admin@hpp-casetrack.id'      => 'Administrator',
          'legal@hpp-casetrack.id'      => 'Legal Admin',
          'ceo@hpp-casetrack.id'        => 'CEO',
          'budi@hpp-casetrack.id'       => 'Pengacara PJ',
          'sari@hpp-casetrack.id'       => 'Lawyer',
          'finance@hpp-casetrack.id'    => 'Finance',
          'headfinance@hpp-casetrack.id'=> 'Head Finance',
          'klien@demo.id'               => 'Klien',
        ];
        foreach ($demos as $em => $lbl):
        ?>
        <button type="button" onclick="fillDemo('<?= $em ?>')"
          class="text-left p-2 bg-white rounded-lg hover:bg-[#edeeef] transition-colors border border-[#e1e3e4]">
          <span class="font-semibold block"><?= $lbl ?></span>
          <span class="text-[10px] text-gray-400 truncate block"><?= $em ?></span>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
function togglePwd() {
  const inp = document.getElementById('password');
  const ico = document.getElementById('pwd-icon');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  ico.textContent = inp.type === 'password' ? 'visibility_off' : 'visibility';
}

function clearValidation() {
  document.getElementById('email-error-required').classList.add('hidden');
  document.getElementById('email-error-format').classList.add('hidden');
  document.getElementById('password-error-required').classList.add('hidden');
  document.getElementById('email').classList.remove('border-red-500');
  document.getElementById('password').classList.remove('border-red-500');
}

function fillDemo(email) {
  clearValidation();
  const inp = document.getElementById('email');
  inp.value = email;
  document.getElementById('password').value = 'password';
}

// Interaktif: bersihkan error saat user mengetik
document.getElementById('email').addEventListener('input', function() {
  document.getElementById('email-error-required').classList.add('hidden');
  document.getElementById('email-error-format').classList.add('hidden');
  this.classList.remove('border-red-500');
});
document.getElementById('password').addEventListener('input', function() {
  document.getElementById('password-error-required').classList.add('hidden');
  this.classList.remove('border-red-500');
});

// Penanganan submit form & cegah klik berulang (TS-05 s/d TS-08, TS-14 / TC-LOGIN-007 s/d TC-LOGIN-010, TC-LOGIN-016)
let isSubmitting = false;
const loginForm = document.getElementById('login-form');

loginForm.addEventListener('submit', function(e) {
  clearValidation();

  const emailInput = document.getElementById('email');
  const pwdInput   = document.getElementById('password');
  const emailVal   = emailInput.value.trim();
  const pwdVal     = pwdInput.value;

  const emailEmpty = emailVal === '';
  const pwdEmpty   = pwdVal === '';

  // Validasi format email RFC 5322 sederhana
  const emailValidFormat = /^[^\s@]+@[^\s@]+\.[^\s@]+$/i.test(emailVal);

  let hasError = false;

  // Cek Email
  if (emailEmpty) {
    document.getElementById('email-error-required').classList.remove('hidden');
    emailInput.classList.add('border-red-500');
    hasError = true;
  } else if (!emailValidFormat) {
    document.getElementById('email-error-format').classList.remove('hidden');
    emailInput.classList.add('border-red-500');
    hasError = true;
  }

  // Cek Password
  if (pwdEmpty) {
    document.getElementById('password-error-required').classList.remove('hidden');
    pwdInput.classList.add('border-red-500');
    hasError = true;
  }

  if (hasError) {
    e.preventDefault();
    if (emailEmpty || !emailValidFormat) {
      emailInput.focus();
    } else if (pwdEmpty) {
      pwdInput.focus();
    }
    return false;
  }

  // Cegah submit ganda jika user mengklik tombol berkali-kali (TC-LOGIN-016)
  if (isSubmitting) {
    e.preventDefault();
    return false;
  }

  isSubmitting = true;
  const submitBtn = document.getElementById('btn-submit');
  const btnIcon   = document.getElementById('btn-icon');
  const btnText   = document.getElementById('btn-text');

  submitBtn.disabled = true;
  submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
  btnIcon.textContent = 'progress_activity';
  btnIcon.classList.add('animate-spin');
  btnText.textContent = 'Memproses...';
});
</script>
</body>
</html>
