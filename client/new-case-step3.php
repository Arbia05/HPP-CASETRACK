<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');
$user = currentUser();
$db   = getDB();

session_start();
$complaintId = $_SESSION['complaint_id'] ?? null;
if (!$complaintId) redirect('/hpp-casetrack/client/new-case-step1.php');

$complaint = $db->prepare("SELECT * FROM complaints WHERE id=? AND client_id=?");
$complaint->execute([$complaintId, $user['id']]);
$comp = $complaint->fetch();
if (!$comp) redirect('/hpp-casetrack/client/new-case-step1.php');

if (isPost()) {
    // Submission confirmed — clear session
    unset($_SESSION['complaint_id']);
    redirect('/hpp-casetrack/client/cases.php?submitted=1');
}

$pageTitle = 'Ajukan Kasus — Konfirmasi';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-3xl mx-auto">

  <div class="page-header">
    <h1>Konfirmasi Pengajuan</h1>
    <p>Tinjau informasi kasus Anda sebelum mengirimkan.</p>
  </div>

  <!-- Stepper complete -->
  <div class="flex items-center mb-10">
    <?php foreach ([['1','Informasi Kasus','done'],['2','Upload Dokumen','done'],['3','Konfirmasi','active']] as [$num,$label,$state]): ?>
    <div class="flex items-center flex-1 <?= $state==='inactive' ? 'opacity-40' : '' ?>">
      <div class="flex items-center gap-2 shrink-0">
        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
          <?= $state==='done' ? 'bg-green-500 text-white' : 'bg-[#800000] text-white' ?>">
          <?= $state==='done' ? '✓' : $num ?>
        </div>
        <span class="text-xs font-semibold hidden md:block uppercase tracking-wider text-[#800000]"><?= $label ?></span>
      </div>
      <?php if ($num < 3): ?><div class="flex-1 h-px bg-green-300 mx-3"></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Summary Card -->
  <div class="card p-6 mb-6">
    <h2 class="font-bold text-[#191c1d] mb-5 flex items-center gap-2">
      <span class="material-symbols-outlined text-[#800000]">summarize</span>
      Ringkasan Pengajuan
    </h2>
    <div class="space-y-4">
      <div class="flex gap-3">
        <span class="text-xs font-semibold text-[#8e706c] uppercase tracking-wider w-32 shrink-0 mt-0.5">Kategori</span>
        <span class="font-medium text-sm"><?= sanitize($comp['case_category']) ?></span>
      </div>
      <div class="flex gap-3">
        <span class="text-xs font-semibold text-[#8e706c] uppercase tracking-wider w-32 shrink-0 mt-0.5">Judul Kasus</span>
        <span class="font-medium text-sm"><?= sanitize($comp['title']) ?></span>
      </div>
      <div class="flex gap-3">
        <span class="text-xs font-semibold text-[#8e706c] uppercase tracking-wider w-32 shrink-0 mt-0.5">Kronologi</span>
        <div class="bg-[#f8f9fa] rounded-xl p-4 flex-1 text-sm text-[#191c1d] leading-relaxed">
          <?= nl2br(sanitize(substr($comp['description'], 0, 300))) ?>
          <?= strlen($comp['description']) > 300 ? '...' : '' ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Info box -->
  <div class="bg-[#eff6ff] border border-blue-200 rounded-xl p-5 mb-6">
    <div class="flex items-start gap-3">
      <span class="material-symbols-outlined text-blue-500 text-[20px] shrink-0" style="font-variation-settings:'FILL' 1">info</span>
      <div class="text-sm text-blue-800">
        <p class="font-semibold mb-1">Apa yang terjadi setelah pengajuan?</p>
        <ol class="list-decimal list-inside space-y-1 text-xs leading-relaxed">
          <li>Tim Legal Admin akan meninjau pengaduan Anda dalam <strong>1–2 hari kerja</strong>.</li>
          <li>Anda akan mendapat notifikasi status melalui aplikasi ini.</li>
          <li>Jika disetujui, Anda akan diminta melengkapi data identitas dan melakukan pembayaran awal.</li>
          <li>Setelah kontrak ditandatangani, kasus resmi ditangani tim pengacara HPP Law Office.</li>
        </ol>
      </div>
    </div>
  </div>

  <form method="POST">
    <div class="flex gap-3 justify-between">
      <a href="/hpp-casetrack/client/new-case-step2.php" class="btn-outline">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Kembali
      </a>
      <button type="submit" class="btn-primary px-8 py-3 text-base">
        <span class="material-symbols-outlined text-[18px]">send</span>
        Kirim Pengajuan Kasus
      </button>
    </div>
  </form>

</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
