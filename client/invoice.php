<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');
$user = currentUser();
$db   = getDB();

$id = (int)($_GET['id'] ?? 0);
$where = $id ? "AND i.id=$id" : '';

$invoices = $db->prepare("SELECT i.*, c.title AS case_title, c.case_number AS reference_number FROM invoices i JOIN cases c ON i.case_id=c.id WHERE i.client_id=? $where ORDER BY i.created_at DESC");
$invoices->execute([$user['id']]);
$allInvoices = $invoices->fetchAll();
$currentInv = $id ? ($allInvoices[0] ?? null) : null;

$error = $success = '';

// Handle payment proof upload
if (isPost() && isset($_POST['invoice_id'])) {
    $invId = (int)$_POST['invoice_id'];
    if (!empty($_FILES['payment_proof']['name'])) {
        $upload = handleUpload($_FILES['payment_proof'], 'payment-proofs', ['jpg','jpeg','png','pdf']);
        if ($upload['success']) {
            $db->prepare("UPDATE invoices SET payment_proof_path=?, status='pending_verification' WHERE id=? AND client_id=?")
               ->execute([$upload['path'], $invId, $user['id']]);

            // Notify finance
            $finances = $db->query("SELECT id FROM users WHERE role='finance' AND status='active'")->fetchAll();
            foreach ($finances as $f) {
                createNotification($f['id'], 'Bukti Pembayaran Masuk', $user['name'] . ' mengunggah bukti pembayaran invoice.', 'payment', $invId, 'invoice');
            }
            $success = 'Bukti pembayaran berhasil diupload. Menunggu verifikasi dari tim keuangan.';
            header('Location: ?id=' . $invId . '&success=1');
            exit;
        } else { $error = $upload['message']; }
    } else { $error = 'Pilih file bukti pembayaran terlebih dahulu.'; }
}

$pageTitle = 'Invoice & Pembayaran';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-5xl mx-auto">

  <div class="page-header">
    <h1>Invoice & Pembayaran</h1>
    <p>Daftar tagihan dan status pembayaran kasus Anda.</p>
  </div>

  <?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success mb-6">
    <span class="material-symbols-outlined text-[18px]">check_circle</span>
    Bukti pembayaran berhasil diupload. Menunggu verifikasi tim keuangan.
  </div>
  <?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error mb-6"><?= $error ?></div><?php endif; ?>

  <?php if (empty($allInvoices)): ?>
  <div class="card p-12 text-center">
    <span class="material-symbols-outlined text-5xl text-[#e1e3e4] block mb-4">receipt_long</span>
    <h3 class="text-xl font-bold text-[#191c1d] mb-2">Belum ada invoice</h3>
    <p class="text-sm text-[#5a413d]">Invoice akan muncul setelah pengaduan Anda disetujui.</p>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Invoice List -->
    <div class="lg:col-span-1">
      <h2 class="font-bold text-[#191c1d] mb-4 text-sm uppercase tracking-wider">Daftar Invoice</h2>
      <div class="space-y-3">
        <?php foreach ($allInvoices as $inv): ?>
        <a href="?id=<?= $inv['id'] ?>"
          class="block card p-4 hover:shadow-md transition-all <?= ($currentInv && $currentInv['id'] === $inv['id']) ? 'border-[#800000] bg-[#fff8f8]' : '' ?>">
          <div class="flex justify-between items-start gap-2">
            <div>
              <p class="font-mono text-xs text-[#800000] font-bold"><?= sanitize($inv['invoice_number']) ?></p>
              <p class="font-semibold text-sm mt-0.5 truncate"><?= sanitize($inv['case_title']) ?></p>
              <p class="font-bold text-[#191c1d] mt-1"><?= formatRupiah($inv['amount']) ?></p>
            </div>
            <?= statusBadge($inv['status']) ?>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Invoice Detail -->
    <div class="lg:col-span-2">
      <?php if ($currentInv): ?>
      <div class="card overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-[#800000] to-[#570000] text-white p-6">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-white/60 text-xs uppercase tracking-widest mb-1">Invoice</p>
              <h2 class="font-bold text-2xl font-mono"><?= sanitize($currentInv['invoice_number']) ?></h2>
              <p class="text-white/80 text-sm mt-1"><?= sanitize($currentInv['case_title']) ?></p>
            </div>
            <?= statusBadge($currentInv['status']) ?>
          </div>
          <div class="mt-4">
            <p class="text-white/60 text-xs">Jumlah Tagihan</p>
            <p class="text-4xl font-extrabold"><?= formatRupiah($currentInv['amount']) ?></p>
          </div>
        </div>

        <div class="p-6">
          <!-- Details -->
          <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
            <div><p class="text-xs text-[#8e706c] uppercase tracking-wider mb-1">Nomor Referensi</p>
              <p class="font-semibold"><?= sanitize($currentInv['reference_number']) ?></p></div>
            <div><p class="text-xs text-[#8e706c] uppercase tracking-wider mb-1">Jatuh Tempo</p>
              <p class="font-semibold <?= strtotime($currentInv['due_date']) < time() && $currentInv['status'] === 'unpaid' ? 'text-red-600' : '' ?>">
                <?= formatDate($currentInv['due_date']) ?></p></div>
            <div><p class="text-xs text-[#8e706c] uppercase tracking-wider mb-1">Dibuat</p>
              <p class="font-semibold"><?= formatDate($currentInv['created_at']) ?></p></div>
            <div><p class="text-xs text-[#8e706c] uppercase tracking-wider mb-1">Keterangan</p>
              <p class="font-semibold"><?= sanitize($currentInv['description'] ?? 'Biaya penanganan kasus') ?></p></div>
          </div>

          <!-- Payment Instructions (for unpaid) -->
          <?php if ($currentInv['status'] === 'unpaid'): ?>
          <div class="bg-[#fffbeb] border border-yellow-200 rounded-xl p-5 mb-6">
            <h3 class="font-bold text-[#735c00] mb-3 flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px]">info</span>
              Cara Pembayaran
            </h3>
            <div class="text-sm text-[#735c00] space-y-2">
              <p>Transfer ke rekening berikut:</p>
              <div class="bg-white rounded-lg p-3 border border-yellow-200 font-mono">
                <p><strong>Bank BCA</strong> — No. Rek: <strong>1234567890</strong></p>
                <p>A/n: <strong>HPP Law Office</strong></p>
              </div>
              <p class="text-xs">Setelah transfer, upload bukti pembayaran di bawah ini. Tim keuangan akan memverifikasi dalam 1×24 jam kerja.</p>
            </div>
          </div>

          <!-- Upload proof form -->
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="invoice_id" value="<?= $currentInv['id'] ?>"/>
            <label class="form-label">Upload Bukti Transfer <span class="text-red-500">*</span></label>
            <div id="proof-zone" class="border-2 border-dashed border-[#e2bfb9] rounded-xl p-6 text-center cursor-pointer hover:border-[#800000] transition-all mb-4">
              <span class="material-symbols-outlined text-[#800000] text-3xl">upload</span>
              <p class="text-sm text-[#5a413d] mt-1">Klik untuk pilih file bukti transfer</p>
              <p class="text-xs text-[#8e706c]">JPG, PNG, PDF — maks 10MB</p>
              <input type="file" id="payment_proof" name="payment_proof" class="hidden" accept=".jpg,.jpeg,.png,.pdf" required/>
            </div>
            <div id="prev-proof" class="mb-4"></div>
            <button type="submit" class="btn-primary w-full justify-center py-3">
              <span class="material-symbols-outlined">upload_file</span>
              Upload Bukti Pembayaran
            </button>
          </form>

          <?php elseif ($currentInv['status'] === 'pending_verification'): ?>
          <div class="bg-[#fffbeb] border border-yellow-200 rounded-xl p-5 text-center">
            <span class="material-symbols-outlined text-yellow-600 text-3xl mb-2 block">hourglass_empty</span>
            <h3 class="font-bold text-[#735c00]">Menunggu Verifikasi</h3>
            <p class="text-sm text-[#735c00] mt-1">Bukti pembayaran Anda sedang diverifikasi oleh tim keuangan.</p>
            <?php if ($currentInv['payment_proof_path']): ?>
            <a href="/<?= $currentInv['payment_proof_path'] ?>" target="_blank"
              class="inline-flex items-center gap-1 text-sm text-[#800000] hover:underline mt-3">
              <span class="material-symbols-outlined text-[16px]">visibility</span> Lihat Bukti Upload
            </a>
            <?php endif; ?>
          </div>

          <?php elseif ($currentInv['status'] === 'paid'): ?>
          <div class="bg-green-50 border border-green-200 rounded-xl p-5 text-center">
            <span class="material-symbols-outlined text-green-500 text-4xl mb-2 block" style="font-variation-settings:'FILL' 1">check_circle</span>
            <h3 class="font-bold text-green-800 text-lg">Pembayaran Lunas</h3>
            <p class="text-sm text-green-700 mt-1">Dibayar pada <?= formatDate($currentInv['paid_at']) ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="card p-12 text-center">
        <span class="material-symbols-outlined text-5xl text-[#e1e3e4] block mb-3">receipt</span>
        <p class="text-sm text-[#8e706c]">Pilih invoice dari daftar untuk melihat detail.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</div>
</div>
</div>
<script>initDropzone('proof-zone','payment_proof','prev-proof');</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
