<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('head_finance');
$user = currentUser();
$db   = getDB();

$pendingReimb  = $db->query("SELECT COUNT(*) FROM reimbursements WHERE status='pending'")->fetchColumn();
$approvedReimb = $db->query("SELECT COALESCE(SUM(amount),0) FROM reimbursements WHERE status='approved'")->fetchColumn();
$totalPaid     = $db->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='paid'")->fetchColumn();

$reimbursements = $db->query("SELECT r.*, u.name AS submitted_name, c.title AS case_title, c.case_number AS reference_number FROM reimbursements r JOIN users u ON r.submitted_by=u.id JOIN cases c ON r.case_id=c.id ORDER BY FIELD(r.status,'pending','approved','rejected','paid'), r.created_at DESC LIMIT 10")->fetchAll();

$error = $success = '';
if (isPost()) {
    $rid    = (int)$_POST['reimb_id'];
    $action = sanitize($_POST['action']);
    $notes  = sanitize($_POST['review_notes'] ?? '');
    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $db->prepare("UPDATE reimbursements SET status=?, reviewed_by=?, review_notes=?, updated_at=NOW() WHERE id=?")
       ->execute([$newStatus, $user['id'], $notes, $rid]);

    // notify submitter
    $reimb = $db->prepare("SELECT * FROM reimbursements WHERE id=?"); $reimb->execute([$rid]); $rd = $reimb->fetch();
    $msg = $action === 'approve' ? 'Reimbursement Anda sebesar ' . formatRupiah($rd['amount']) . ' disetujui.' : 'Reimbursement Anda ditolak. Catatan: ' . $notes;
    createNotification($rd['submitted_by'], 'Status Reimbursement', $msg, 'reimbursement', $rid, 'reimbursement');
    $success = 'Reimbursement berhasil ' . ($action === 'approve' ? 'disetujui' : 'ditolak') . '.';
    header('Location: /head-finance/reimbursement-approval.php?msg=' . $action);
    exit;
}

$pageTitle = 'Dashboard Head of Finance';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-7xl mx-auto">

  <div class="page-header">
    <h1>Dashboard Head of Finance</h1>
    <p>Otorisasi reimbursement dan overview keuangan firma.</p>
  </div>

  <?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success mb-6">
    Reimbursement berhasil <?= $_GET['msg'] === 'approve' ? 'disetujui' : 'ditolak' ?>.
  </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#800000]">Reimb. Pending</p>
        <div class="w-9 h-9 bg-[#800000] rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-white text-[18px]">pending_actions</span>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-[#800000]"><?= $pendingReimb ?></div>
      <p class="text-xs text-[#5a413d] mt-1">Butuh otorisasi Anda</p>
    </div>
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Reimb. Disetujui</p>
        <div class="w-9 h-9 bg-green-50 rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-green-600 text-[18px]">fact_check</span>
        </div>
      </div>
      <div class="text-lg font-extrabold text-green-600"><?= formatRupiah($approvedReimb) ?></div>
    </div>
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Total Pendapatan</p>
        <div class="w-9 h-9 bg-[#fffbeb] rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-[#735c00] text-[18px]">payments</span>
        </div>
      </div>
      <div class="text-lg font-extrabold"><?= formatRupiah($totalPaid) ?></div>
    </div>
  </div>

  <!-- Reimbursement Table -->
  <div class="card overflow-hidden">
    <div class="px-6 py-4 border-b border-[#e2bfb9] flex items-center justify-between">
      <h2 class="font-bold text-[#191c1d]">Daftar Pengajuan Reimbursement</h2>
      <a href="/head-finance/reimbursement-approval.php" class="text-sm text-[#800000] hover:underline font-medium">Kelola Semua →</a>
    </div>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead><tr>
          <th>Pengaju</th><th>Kasus</th><th>Jumlah</th><th>Keterangan</th><th>Status</th><th class="text-center">Otorisasi</th>
        </tr></thead>
        <tbody>
          <?php if (empty($reimbursements)): ?>
          <tr><td colspan="6" class="text-center py-8 text-[#8e706c]">Tidak ada pengajuan.</td></tr>
          <?php endif; ?>
          <?php foreach ($reimbursements as $r): ?>
          <tr>
            <td class="font-medium text-sm"><?= sanitize($r['submitted_name']) ?></td>
            <td class="max-w-[160px] truncate text-sm"><?= sanitize($r['case_title']) ?></td>
            <td class="font-bold"><?= formatRupiah($r['amount']) ?></td>
            <td class="max-w-[180px] truncate text-sm text-[#5a413d]"><?= sanitize($r['description']) ?></td>
            <td><?= statusBadge($r['status']) ?></td>
            <td class="text-center">
              <?php if ($r['status'] === 'pending'): ?>
              <div class="flex justify-center gap-2">
                <form method="POST" class="inline">
                  <input type="hidden" name="reimb_id" value="<?= $r['id'] ?>"/>
                  <input type="hidden" name="action" value="approve"/>
                  <button type="submit" class="btn-primary btn-sm" onclick="return confirm('Setujui reimbursement ini?')">
                    <span class="material-symbols-outlined text-[14px]">check</span> Setuju
                  </button>
                </form>
                <form method="POST" class="inline">
                  <input type="hidden" name="reimb_id" value="<?= $r['id'] ?>"/>
                  <input type="hidden" name="action" value="reject"/>
                  <input type="hidden" name="review_notes" value="Tidak memenuhi syarat"/>
                  <button type="submit" class="text-xs font-semibold text-red-600 bg-red-50 border border-red-200 px-2 py-1 rounded-lg hover:bg-red-100 transition-colors" onclick="return confirm('Tolak reimbursement ini?')">
                    Tolak
                  </button>
                </form>
              </div>
              <?php else: ?>
              <span class="text-xs text-[#8e706c]">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
