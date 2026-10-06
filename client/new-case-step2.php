<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');
$user = currentUser();
$db   = getDB();

session_start();
$complaintId = $_SESSION['complaint_id'] ?? null;
if (!$complaintId) redirect('/client/new-case-step1.php');

$error = '';
if (isPost()) {
    $uploadedAny = false;
    $errors = [];

    $fileFields = ['ktp','bukti_pendukung','dokumen_legal'];
    foreach ($fileFields as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $upload = handleUpload($_FILES[$field], 'documents');
            if ($upload['success']) {
                $db->prepare("INSERT INTO documents (complaint_id_ref, client_id, uploaded_by, file_name, file_path, file_type, file_size, category) SELECT ?, ?, ?, ?, ?, ?, ?, ?")
                   // simplified insert
                   ;
                // Store document record
                $stmt = $db->prepare("INSERT INTO documents (client_id, uploaded_by, file_name, file_path, file_type, file_size, category) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$user['id'], $user['id'], $_FILES[$field]['name'], $upload['path'], pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION), $_FILES[$field]['size'], 'identity']);
                $uploadedAny = true;
            } else {
                $errors[] = $upload['message'];
            }
        }
    }

    if ($errors) { $error = implode(', ', $errors); }
    else {
        redirect('/client/new-case-step3.php');
    }
}

$pageTitle = 'Ajukan Kasus — Langkah 2';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-3xl mx-auto">

  <div class="page-header">
    <h1>Upload Dokumen Pendukung</h1>
    <p>Unggah dokumen yang mendukung kronologi kasus Anda.</p>
  </div>

  <!-- Stepper -->
  <div class="flex items-center mb-10">
    <?php foreach ([['1','Informasi Kasus','done'],['2','Upload Dokumen','active'],['3','Konfirmasi','inactive']] as [$num,$label,$state]): ?>
    <div class="flex items-center flex-1 <?= $state==='inactive' ? 'opacity-40' : '' ?>">
      <div class="flex items-center gap-2 shrink-0">
        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
          <?= $state==='done' ? 'bg-green-500 text-white' : ($state==='active' ? 'bg-[#800000] text-white' : 'bg-[#e1e3e4] text-[#5a413d]') ?>">
          <?= $state==='done' ? '✓' : $num ?>
        </div>
        <span class="text-xs font-semibold hidden md:block uppercase tracking-wider
          <?= $state==='active' ? 'text-[#800000]' : 'text-[#5a413d]' ?>"><?= $label ?></span>
      </div>
      <?php if ($num < 3): ?><div class="flex-1 h-px <?= $state==='done' ? 'bg-green-300' : 'bg-[#e1e3e4]' ?> mx-3"></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($error): ?><div class="alert alert-error mb-6"><?= $error ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="space-y-5">

    <?php
    $uploadFields = [
      ['ktp', 'KTP / Identitas Diri', 'Scan/foto KTP yang jelas dan terbaca', 'identity', true],
      ['bukti_pendukung', 'Bukti Pendukung', 'Foto, tangkapan layar, atau dokumen yang relevan dengan kasus', 'evidence', false],
      ['dokumen_legal', 'Dokumen Legal (opsional)', 'Sertifikat, akta, surat kuasa, atau dokumen hukum lainnya', 'court_doc', false],
    ];
    foreach ($uploadFields as [$name, $label, $desc, $cat, $required]):
    ?>
    <div class="card p-5">
      <div class="flex items-start justify-between gap-3 mb-4">
        <div>
          <h3 class="font-semibold text-[#191c1d]"><?= $label ?> <?= $required ? '<span class="text-red-500">*</span>' : '' ?></h3>
          <p class="text-xs text-[#5a413d] mt-0.5"><?= $desc ?></p>
        </div>
        <div class="w-9 h-9 bg-[#fff1f1] rounded-lg flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-[#800000] text-[18px]">upload_file</span>
        </div>
      </div>
      <div id="drop-<?= $name ?>" class="border-2 border-dashed border-[#e2bfb9] rounded-xl p-5 text-center cursor-pointer hover:border-[#800000] transition-all">
        <p class="text-sm text-[#5a413d]">Klik atau drag file ke sini</p>
        <p class="text-xs text-[#8e706c] mt-1">PDF, JPG, PNG, DOCX — maks 10MB</p>
        <input type="file" id="<?= $name ?>" name="<?= $name ?>" <?= $required ? 'required' : '' ?> accept=".pdf,.jpg,.jpeg,.png,.docx" class="hidden"/>
      </div>
      <div id="prev-<?= $name ?>" class="mt-2 space-y-1"></div>
    </div>
    <?php endforeach; ?>

    <div class="section-divider"></div>
    <div class="flex gap-3 justify-between">
      <a href="/client/new-case-step1.php" class="btn-outline">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Kembali
      </a>
      <button type="submit" class="btn-primary px-8">
        Lanjutkan <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
      </button>
    </div>
  </form>
</div>
</div>
</div>
<script>
['ktp','bukti_pendukung','dokumen_legal'].forEach(name => {
  initDropzone('drop-'+name, name, 'prev-'+name);
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
