<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('finance');
$user = currentUser();
$db   = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/hpp-casetrack/finance/invoices.php');

$stmt = $db->prepare("SELECT i.*, u.name AS client_name, u.email AS client_email, u.phone AS client_phone, c.title AS case_title, c.case_number AS reference_number FROM invoices i JOIN users u ON i.client_id=u.id JOIN cases c ON i.case_id=c.id WHERE i.id=?");
$stmt->execute([$id]);
$inv = $stmt->fetch();
if (!$inv) redirect('/hpp-casetrack/finance/invoices.php');

$error = $success = '';

if (isPost()) {
    $action = sanitize($_POST['action'] ?? '');
    if ($action === 'verify') {
        $db->prepare("UPDATE invoices SET status='paid', paid_at=NOW(), verified_by=?, verified_at=NOW() WHERE id=?")
           ->execute([$user['id'], $id]);
        createNotification($inv['client_id'], 'Pembayaran Dikonfirmasi', 'Pembayaran untuk kasus ' . $inv['case_title'] . ' telah dikonfirmasi. Terima kasih!', 'payment', $id, 'invoice');
        $success = 'Pembayaran berhasil dikonfirmasi!';
    } elseif ($action === 'reject') {
        $notes = sanitize($_POST['notes'] ?? '');
        $db->prepare("UPDATE invoices SET status='unpaid', verified_by=NULL WHERE id=?")->execute([$id]);
        createNotification($inv['client_id'], 'Bukti Pembayaran Ditolak', 'Bukti pembayaran Anda ditolak. Catatan: ' . $notes . ' Silakan upload ulang.', 'payment', $id, 'invoice');
        $success = 'Bukti pembayaran ditolak, klien diberitahu.';
    }
    $stmt->execute([$id]);
    $inv = $stmt->fetch();
}

$pageTitle = 'Verifikasi Invoice — ' . sanitize($inv['invoice_number']);
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-4xl mx-auto">

  <nav class="flex items-center gap-2 text-sm text-[#8e706c] mb-6">
    <a href="/hpp-casetrack/finance/dashboard.php" class="hover:text-[#800000]">Dashboard</a>
    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
    <a href="/hpp-casetrack/finance/invoices.php" class="hover:text-[#800000]">Invoice</a>
    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
    <span class="text-[#191c1d] font-medium"><?= sanitize($inv['invoice_number']) ?></span>
  </nav>

  <?php if ($success): ?><div class="alert alert-success mb-6"><?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error mb-6"><?= $error ?></div><?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
      <!-- Invoice Details -->
      <div class="card overflow-hidden">
        <div class="bg-gradient-to-r from-[#800000] to-[#570000] text-white p-6">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-white/60 text-xs uppercase mb-1">Invoice</p>
              <h1 class="text-2xl font-bold font-mono"><?= sanitize($inv['invoice_number']) ?></h1>
              <p class="text-white/80 mt-1"><?= sanitize($inv['case_title']) ?></p>
            </div>
            <?= statusBadge($inv['status']) ?>
          </div>
          <p class="text-4xl font-extrabold mt-3"><?= formatRupiah($inv['amount']) ?></p>
        </div>
        <div class="p-6 grid grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-xs text-[#8e706c] uppercase tracking-wider mb-1">Klien</p>
            <p class="font-semibold"><?= sanitize($inv['client_name']) ?></p>
          </div>
          <div>
            <p class="text-xs text-[#8e706c] uppercase tracking-wider mb-1">No. Kasus</p>
            <p class="font-mono font-semibold text-[#800000]"><?= sanitize($inv['reference_number']) ?></p>
          </div>
          <div>
            <p class="text-xs text-[#8e706c] uppercase tracking-wider mb-1">Dibuat</p>
            <p class="font-semibold"><?= formatDate($inv['created_at']) ?></p>
          </div>
          <div>
            <p class="text-xs text-[#8e706c] uppercase tracking-wider mb-1">Jatuh Tempo</p>
            <p class="font-semibold"><?= formatDate($inv['due_date']) ?></p>
          </div>
        </div>
      </div>

      <!-- Payment Proof -->
      <?php if ($inv['payment_proof_path']): ?>
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-4 flex items-center gap-2">
          <span class="material-symbols-outlined text-[#800000]">receipt</span>
          Bukti Pembayaran
        </h2>
        <div class="bg-[#f3f4f5] rounded-xl p-4 flex items-center gap-4">
          <div class="w-12 h-12 bg-[#fff1f1] rounded-xl flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[#800000] text-2xl">image</span>
          </div>
          <div class="flex-1">
            <p class="font-medium text-sm">Bukti Transfer — <?= sanitize($inv['invoice_number']) ?></p>
            <p class="text-xs text-[#8e706c]">Diunggah oleh klien</p>
          </div>
          <a href="/<?= $inv['payment_proof_path'] ?>" target="_blank"
            class="btn-primary btn-sm">
            <span class="material-symbols-outlined text-[16px]">visibility</span>
            Lihat Bukti
          </a>
        </div>

        <!-- Verification Actions -->
        <?php if ($inv['status'] === 'pending_verification'): ?>
        <div class="mt-5 grid grid-cols-2 gap-3">
          <form method="POST">
            <input type="hidden" name="action" value="verify"/>
            <button type="submit" class="btn-primary w-full justify-center py-2.5"
              onclick="return confirm('Konfirmasi pembayaran ini?')">
              <span class="material-symbols-outlined text-[18px]">verified</span>
              Konfirmasi Lunas
            </button>
          </form>
          <form method="POST">
            <input type="hidden" name="action" value="reject"/>
            <div class="mb-2">
              <textarea name="notes" rows="2" placeholder="Alasan penolakan..." class="form-input text-xs resize-none"></textarea>
            </div>
            <button type="submit" class="w-full py-2.5 text-sm font-semibold text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors flex items-center justify-center gap-2">
              <span class="material-symbols-outlined text-[18px]">cancel</span>
              Tolak Bukti
            </button>
          </form>
        </div>
        <?php endif; ?>
      </div>
      <?php elseif ($inv['status'] === 'unpaid'): ?>
      <div class="card p-6 text-center">
        <span class="material-symbols-outlined text-4xl text-[#e1e3e4] block mb-2">hourglass_empty</span>
        <p class="text-[#5a413d]">Klien belum mengunggah bukti pembayaran.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Client Info -->
    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-4 text-sm">Info Klien</h2>
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-[#800000] flex items-center justify-center text-white font-bold">
            <?= strtoupper(substr($inv['client_name'], 0, 1)) ?>
          </div>
          <div>
            <p class="font-semibold text-sm"><?= sanitize($inv['client_name']) ?></p>
            <p class="text-xs text-[#8e706c]"><?= sanitize($inv['client_email']) ?></p>
          </div>
        </div>
        <?php if ($inv['client_phone']): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $inv['client_phone']) ?>" target="_blank"
          class="btn-outline w-full justify-center text-xs py-2">
          <span class="material-symbols-outlined text-[16px]">chat</span>
          Hubungi via WA
        </a>
        <?php endif; ?>
      </div>

      <?php if ($inv['status'] === 'paid'): ?>
      <div class="card p-5 bg-green-50 border-green-200">
        <div class="text-center">
          <span class="material-symbols-outlined text-green-500 text-4xl" style="font-variation-settings:'FILL' 1">check_circle</span>
          <p class="font-bold text-green-800 mt-2">Sudah Lunas</p>
          <p class="text-xs text-green-700 mt-1">Dikonfirmasi <?= formatDate($inv['verified_at']) ?></p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
