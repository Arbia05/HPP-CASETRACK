<?php
// 403 Forbidden Page
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
http_response_code(403);
$pageTitle = 'Akses Ditolak';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>403 — Akses Ditolak</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:'Plus Jakarta Sans',sans-serif;}</style>
</head>
<body class="bg-[#f8f9fa] min-h-screen flex items-center justify-center p-6">
<div class="text-center max-w-md">
  <span class="material-symbols-outlined text-6xl text-[#800000] block mb-6" style="font-variation-settings:'FILL' 1">lock</span>
  <h1 class="text-4xl font-extrabold text-[#191c1d] mb-3">Akses Ditolak</h1>
  <p class="text-[#5a413d] mb-8">Anda tidak memiliki izin untuk mengakses halaman ini. Silakan kembali ke dashboard atau hubungi administrator.</p>
  <div class="flex gap-3 justify-center">
    <a href="javascript:history.back()" class="px-6 py-3 border border-[#e1e3e4] rounded-lg text-sm font-semibold text-[#5a413d] hover:bg-[#f3f4f5] transition-colors">
      Kembali
    </a>
    <a href="<?= getBasePath() ?>login.php" class="px-6 py-3 bg-[#800000] text-white rounded-lg text-sm font-semibold hover:bg-[#570000] transition-colors">
      Login Ulang
    </a>
  </div>
</div>
</body>
</html>
