<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
$email = $_SESSION['otp_email'] ?? '';
$demoOtp = $_SESSION['otp_code'] ?? '';
if (!$email) redirect('/register.php');

$error = $success = '';

if (isPost()) {
    $otp = trim($_POST['otp'] ?? '');
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND otp_code = ? AND otp_expires_at > NOW() LIMIT 1");
    $stmt->execute([$email, $otp]);
    $user = $stmt->fetch();
    if (!$user) {
        $error = 'Kode OTP tidak valid atau sudah kadaluarsa.';
    } else {
        $db->prepare("UPDATE users SET status='active', email_verified_at=NOW(), otp_code=NULL WHERE id=?")->execute([$user['id']]);
        unset($_SESSION['otp_email'], $_SESSION['otp_code']);
        redirect('/login.php?verified=1');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Verifikasi OTP — HPP CaseTrack</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/css/app.css"/>
<style>body{font-family:'Plus Jakarta Sans',sans-serif;}
.otp-input{width:48px;height:56px;text-align:center;font-size:24px;font-weight:700;border:2px solid #e1e3e4;border-radius:8px;outline:none;}
.otp-input:focus{border-color:#800000;box-shadow:0 0 0 3px rgba(128,0,0,0.1);}
</style>
</head>
<body class="bg-[#f8f9fa] min-h-screen flex items-center justify-center p-6">
<div class="w-full max-w-md">
  <div class="card p-8 text-center">
    <div class="w-16 h-16 bg-[#fff1f1] rounded-full flex items-center justify-center mx-auto mb-6">
      <span class="material-symbols-outlined text-[#800000] text-3xl" style="font-variation-settings:'FILL' 1">mark_email_read</span>
    </div>
    <h1 class="text-2xl font-bold text-[#191c1d] mb-2">Verifikasi Email</h1>
    <p class="text-sm text-[#5a413d] mb-2">Kami mengirimkan kode 6 digit ke:</p>
    <p class="font-semibold text-[#800000] mb-6"><?= sanitize($email) ?></p>

    <?php if ($error): ?><div class="alert alert-error mb-4 text-left"><?= $error ?></div><?php endif; ?>

    <?php if ($demoOtp): ?>
    <div class="alert alert-info mb-4 text-left text-sm">
      <span class="material-symbols-outlined text-[16px]">info</span>
      <div><strong>Mode Demo:</strong> Kode OTP Anda adalah <strong class="text-[#800000] text-lg font-mono"><?= $demoOtp ?></strong></div>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="flex justify-center gap-3 mb-6">
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <input type="text" maxlength="1" class="otp-input" id="otp<?= $i ?>"
               oninput="moveNext(this, <?= $i ?>)" onkeydown="movePrev(event, <?= $i ?>)"/>
        <?php endfor; ?>
        <input type="hidden" name="otp" id="otp-hidden"/>
      </div>
      <button type="submit" onclick="combineOtp()" class="btn-primary w-full justify-center py-3 text-base">
        <span class="material-symbols-outlined">verified</span>
        Verifikasi Akun
      </button>
    </form>

    <p class="text-sm text-[#5a413d] mt-6">
      Tidak menerima kode?
      <a href="register.php" class="text-[#800000] font-semibold hover:underline">Daftar ulang</a>
    </p>
  </div>
</div>
<script>
function moveNext(el, idx) {
  el.value = el.value.replace(/[^0-9]/g,'');
  if (el.value && idx < 6) document.getElementById('otp'+(idx+1)).focus();
}
function movePrev(e, idx) {
  if (e.key === 'Backspace' && !e.target.value && idx > 1) document.getElementById('otp'+(idx-1)).focus();
}
function combineOtp() {
  let val = '';
  for (let i=1;i<=6;i++) val += document.getElementById('otp'+i).value;
  document.getElementById('otp-hidden').value = val;
}
</script>
</body>
</html>
