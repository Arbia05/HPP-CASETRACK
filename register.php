<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) redirect('/hpp-casetrack/client/dashboard.php');

$error = $success = '';

if (isPost()) {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if (!$name || !$email || !$password) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $db = getDB();
        $existing = $db->prepare("SELECT id FROM users WHERE email = ?");
        $existing->execute([$email]);
        if ($existing->fetch()) {
            $error = 'Email sudah terdaftar. Silakan login.';
        } else {
            $otp     = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $hashed  = password_hash($password, PASSWORD_DEFAULT);
            $username = strtolower(str_replace(' ', '.', $name)) . rand(10,99);

            // is_active=0 → akun belum aktif sampai OTP diverifikasi
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role, name, phone, is_active, otp_code, otp_expires_at) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$username, $email, $hashed, 'client', $name, $phone, 0, $otp, $expires]);
            $userId = $db->lastInsertId();

            // In a real app: send OTP via email. For demo, store in session.
            session_start();
            $_SESSION['otp_email']  = $email;
            $_SESSION['otp_code']   = $otp; // demo only
            redirect('/hpp-casetrack/otp-verify.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Daftar Akun — HPP CaseTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/css/app.css"/>
<style>body{font-family:'Plus Jakarta Sans',sans-serif;}</style>
</head>
<body class="bg-[#f8f9fa] min-h-screen flex items-center justify-center p-6">

<div class="w-full max-w-lg">
  <!-- Back link -->
  <a href="login.php" class="inline-flex items-center gap-2 text-sm text-[#5a413d] hover:text-[#800000] mb-6 transition-colors">
    <span class="material-symbols-outlined text-[18px]">arrow_back</span> Kembali ke Login
  </a>

  <div class="card p-8">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
      <div class="w-10 h-10 rounded-xl bg-[#800000] flex items-center justify-center">
        <span class="material-symbols-outlined text-[#fed65b] text-xl" style="font-variation-settings:'FILL' 1">gavel</span>
      </div>
      <div>
        <h1 class="text-xl font-bold text-[#191c1d]">Daftar Akun Klien</h1>
        <p class="text-sm text-[#5a413d]">Buat akun untuk mengajukan kasus ke HPP Law Office</p>
      </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">person</span>
          <input type="text" name="name" required placeholder="Nama sesuai KTP"
            value="<?= sanitize($_POST['name'] ?? '') ?>"
            class="form-input pl-10"/>
        </div>
      </div>
      <div>
        <label class="form-label">Email Aktif <span class="text-red-500">*</span></label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">mail</span>
          <input type="email" name="email" required placeholder="email@example.com"
            value="<?= sanitize($_POST['email'] ?? '') ?>"
            class="form-input pl-10"/>
        </div>
      </div>
      <div>
        <label class="form-label">Nomor Telepon / WA</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">phone</span>
          <input type="tel" name="phone" placeholder="08xxxxxxxxxx"
            value="<?= sanitize($_POST['phone'] ?? '') ?>"
            class="form-input pl-10"/>
        </div>
      </div>
      <div>
        <label class="form-label">Password <span class="text-red-500">*</span></label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">lock</span>
          <input type="password" name="password" required placeholder="Minimal 8 karakter" class="form-input pl-10" id="pwd"/>
        </div>
        <p class="text-xs text-[#8e706c] mt-1">Gunakan kombinasi huruf, angka, dan simbol.</p>
      </div>
      <div>
        <label class="form-label">Konfirmasi Password <span class="text-red-500">*</span></label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">lock_reset</span>
          <input type="password" name="password_confirm" required placeholder="Ulangi password" class="form-input pl-10" id="pwd2"/>
        </div>
      </div>

      <!-- Terms -->
      <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" required class="mt-1 accent-[#800000]"/>
        <span class="text-sm text-[#5a413d]">
          Saya menyetujui <a href="#" class="text-[#800000] hover:underline font-medium">Syarat & Ketentuan</a>
          dan <a href="#" class="text-[#800000] hover:underline font-medium">Kebijakan Privasi</a> HPP Law Office.
        </span>
      </label>

      <button type="submit" class="btn-primary w-full justify-center py-3 text-base mt-2">
        <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
        Buat Akun & Verifikasi Email
      </button>
    </form>

    <p class="text-center text-sm text-[#5a413d] mt-6">
      Sudah punya akun? <a href="login.php" class="text-[#800000] font-semibold hover:underline">Masuk di sini</a>
    </p>
  </div>
</div>
</body>
</html>
