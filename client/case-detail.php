<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');
$user = currentUser();
$db   = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT c.*, c.case_number AS reference_number, u.name AS client_name, lw.name AS lawyer_name, lw.phone AS lawyer_phone FROM cases c JOIN users u ON c.client_id=u.id LEFT JOIN users lw ON c.assigned_pj_id=lw.id WHERE c.id=? AND c.client_id=?");
$stmt->execute([$id, $user['id']]);
$case = $stmt->fetch();
if (!$case) redirect('/hpp-casetrack/client/cases.php');

// Timeline updates
$updates = $db->prepare("SELECT cu.*, u.name AS actor_name, u.role AS actor_role FROM case_activities cu JOIN users u ON cu.user_id=u.id WHERE cu.case_id=? ORDER BY cu.created_at DESC");
$updates->execute([$id]); $timeline = $updates->fetchAll();

// Milestones
$milestones = $db->prepare("SELECT * FROM milestones WHERE case_id=? ORDER BY due_date ASC");
$milestones->execute([$id]); $mstones = $milestones->fetchAll();

// Documents
$docs = $db->prepare("SELECT * FROM case_documents WHERE case_id=? ORDER BY created_at DESC");
$docs->execute([$id]); $documents = $docs->fetchAll();

// Invoice
$invoice = $db->prepare("SELECT * FROM invoices WHERE case_id=? ORDER BY created_at DESC LIMIT 1");
$invoice->execute([$id]); $inv = $invoice->fetch();

// Team
$team = $db->prepare("SELECT u.name, u.role, lw.specialization FROM case_team ct JOIN users u ON ct.lawyer_id=u.id LEFT JOIN lawyers lw ON u.id=lw.user_id WHERE ct.case_id=?");
$team->execute([$id]); $teamMembers = $team->fetchAll();

$pageTitle = sanitize($case['title']);
$base = getBasePath();
include __DIR__ . '/../includes/header.php';

$statusColors = ['active'=>'bg-[#800000]','won'=>'bg-green-600','lost'=>'bg-red-600','closed'=>'bg-gray-500','on_hold'=>'bg-yellow-600','new'=>'bg-blue-500'];
$statusColor  = $statusColors[$case['status']] ?? 'bg-gray-500';
$prog = ['new'=>10,'active'=>50,'on_hold'=>30,'closed'=>100,'won'=>100,'lost'=>100];
$pct  = $prog[$case['status']] ?? 0;
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-6xl mx-auto">

  <!-- Breadcrumb -->
  <nav class="flex items-center gap-2 text-sm text-[#8e706c] mb-6">
    <a href="/hpp-casetrack/client/dashboard.php" class="hover:text-[#800000]">Dashboard</a>
    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
    <a href="/hpp-casetrack/client/cases.php" class="hover:text-[#800000]">Kasus Saya</a>
    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
    <span class="text-[#191c1d] font-medium truncate max-w-[200px]"><?= sanitize($case['title']) ?></span>
  </nav>

  <!-- Case Header Banner -->
  <div class="<?= $statusColor ?> rounded-2xl p-6 md:p-8 text-white mb-6 relative overflow-hidden">
    <div class="absolute right-0 top-0 w-40 h-40 rounded-bl-full bg-white/5"></div>
    <div class="absolute bottom-0 left-20 w-24 h-24 rounded-tr-full bg-white/5"></div>
    <div class="relative z-10">
      <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-2">
            <span class="text-xs font-mono bg-white/20 px-2.5 py-1 rounded-full"><?= $case['reference_number'] ?></span>
            <span class="text-xs bg-white/20 px-2.5 py-1 rounded-full"><?= sanitize($case['case_type'] ?? 'Umum') ?></span>
          </div>
          <h1 class="text-2xl md:text-3xl font-bold mb-1"><?= sanitize($case['title']) ?></h1>
          <p class="text-white/70 text-sm">Dibuka: <?= formatDate($case['opened_at']) ?></p>
        </div>
        <!-- Progress ring placeholder -->
        <div class="text-center shrink-0">
          <div class="text-4xl font-extrabold"><?= $pct ?>%</div>
          <div class="text-white/70 text-xs mt-1">Progress Kasus</div>
        </div>
      </div>
      <!-- Progress bar -->
      <div class="mt-4 h-2 bg-white/20 rounded-full overflow-hidden">
        <div class="h-full bg-white/80 rounded-full transition-all" style="width:<?= $pct ?>%"></div>
      </div>
    </div>
  </div>

  <!-- Invoice Alert -->
  <?php if ($inv && in_array($inv['status'], ['unpaid','overdue'])): ?>
  <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <span class="material-symbols-outlined text-red-500" style="font-variation-settings:'FILL' 1">error</span>
      <div>
        <p class="font-semibold text-red-800">Invoice Belum Dibayar — <?= sanitize($inv['invoice_number']) ?></p>
        <p class="text-sm text-red-600">Tagihan sebesar <strong><?= formatRupiah($inv['amount']) ?></strong> jatuh tempo <?= formatDate($inv['due_date']) ?></p>
      </div>
    </div>
    <a href="/hpp-casetrack/client/invoice.php?id=<?= $inv['id'] ?>" class="btn-primary bg-red-600 hover:bg-red-700 shrink-0">
      Bayar Sekarang
    </a>
  </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Left: Timeline -->
    <div class="lg:col-span-2 space-y-6">

      <!-- Timeline Card -->
      <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-[#e2bfb9] flex items-center justify-between">
          <h2 class="font-bold text-[#191c1d]">Riwayat Progress Kasus</h2>
          <span class="text-xs text-[#8e706c]"><?= count($timeline) ?> update</span>
        </div>
        <div class="p-6">
          <?php if (empty($timeline)): ?>
          <div class="text-center py-8">
            <span class="material-symbols-outlined text-4xl text-[#e1e3e4] block mb-2">timeline</span>
            <p class="text-sm text-[#8e706c]">Belum ada update progress. Tim sedang mempersiapkan penanganan kasus Anda.</p>
          </div>
          <?php else: ?>
          <div class="timeline">
            <?php foreach ($timeline as $upd): ?>
            <div class="timeline-item">
              <div class="timeline-dot" style="background: #800000"></div>
              <div class="card-like bg-[#f8f9fa] border border-[#e1e3e4] rounded-xl p-4">
                <div class="flex items-center justify-between gap-2 mb-2">
                  <span class="font-semibold text-sm text-[#800000] bg-[#fff1f1] px-3 py-1 rounded-full">
                    <?= sanitize($upd['activity_type']) ?>
                  </span>
                  <span class="text-xs text-[#8e706c] whitespace-nowrap"><?= timeAgo($upd['created_at']) ?></span>
                </div>
                <?php if ($upd['description']): ?>
                <p class="text-sm text-[#191c1d] leading-relaxed"><?= nl2br(sanitize($upd['description'])) ?></p>
                <?php endif; ?>
                <?php if ($upd['location']): ?>
                <div class="flex items-center gap-1.5 text-xs text-[#8e706c] mt-2">
                  <span class="material-symbols-outlined text-[14px] text-[#800000]">location_on</span>
                  <?= sanitize($upd['location']) ?>
                </div>
                <?php endif; ?>
                <?php if ($upd['evidence_path']): ?>
                <a href="/<?= $upd['evidence_path'] ?>" target="_blank"
                  class="inline-flex items-center gap-1 text-xs text-[#800000] hover:underline mt-2">
                  <span class="material-symbols-outlined text-[14px]">attach_file</span>
                  Lihat Lampiran
                </a>
                <?php endif; ?>
                <p class="text-xs text-[#8e706c] mt-2">— <?= sanitize($upd['actor_name']) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
            <!-- Case opened marker -->
            <div class="timeline-item">
              <div class="timeline-dot inactive"></div>
              <p class="text-sm font-medium text-[#5a413d]">Kasus Dibuka</p>
              <p class="text-xs text-[#8e706c]"><?= formatDate($case['opened_at']) ?></p>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Documents -->
      <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-[#e2bfb9]">
          <h2 class="font-bold text-[#191c1d]">Dokumen Kasus</h2>
        </div>
        <div class="p-5">
          <?php if (empty($documents)): ?>
          <p class="text-sm text-[#8e706c] text-center py-6">Belum ada dokumen terlampir.</p>
          <?php else: ?>
          <div class="space-y-2">
            <?php foreach ($documents as $doc): ?>
            <div class="flex items-center gap-3 p-3 bg-[#f3f4f5] rounded-lg border border-[#e1e3e4]">
              <div class="w-9 h-9 bg-[#fff1f1] rounded-lg flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[#800000] text-[18px]">description</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate"><?= sanitize($doc['file_name']) ?></p>
                <p class="text-xs text-[#8e706c]"><?= strtoupper($doc['file_type']) ?> · <?= formatDate($doc['created_at']) ?></p>
              </div>
              <a href="/<?= $doc['file_path'] ?>" target="_blank"
                class="text-[#800000] hover:bg-[#fff1f1] p-1.5 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-[18px]">download</span>
              </a>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right sidebar -->
    <div class="space-y-5">
      <!-- PJ Lawyer Info -->
      <?php if ($case['lawyer_name']): ?>
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-4 text-sm">Pengacara Penanggung Jawab</h2>
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 rounded-full bg-[#800000] flex items-center justify-center text-white font-bold">
            <?= strtoupper(substr($case['lawyer_name'], 0, 1)) ?>
          </div>
          <div>
            <p class="font-semibold text-sm"><?= sanitize($case['lawyer_name']) ?></p>
            <p class="text-xs text-[#8e706c]">Pengacara PJ</p>
          </div>
        </div>
        <?php if ($case['lawyer_phone']): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $case['lawyer_phone']) ?>" target="_blank"
          class="btn-outline w-full justify-center text-xs py-2">
          <span class="material-symbols-outlined text-[16px]">chat</span>
          Hubungi via WA
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Team -->
      <?php if (!empty($teamMembers)): ?>
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-3 text-sm">Tim Advokat</h2>
        <div class="space-y-2">
          <?php foreach ($teamMembers as $m): ?>
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-full bg-[#f3f4f5] flex items-center justify-center text-[#800000] text-xs font-bold">
              <?= strtoupper(substr($m['name'], 0, 1)) ?>
            </div>
            <div>
              <p class="text-sm font-medium"><?= sanitize($m['name']) ?></p>
              <p class="text-xs text-[#8e706c]"><?= sanitize($m['specialization'] ?? 'Advokat') ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Milestones -->
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-3 text-sm flex items-center gap-2">
          <span class="material-symbols-outlined text-[#800000] text-[18px]">flag</span>
          Milestone Kasus
        </h2>
        <?php if (empty($mstones)): ?>
        <p class="text-xs text-[#8e706c]">Belum ada milestone ditentukan.</p>
        <?php else: ?>
        <div class="space-y-2">
          <?php foreach ($mstones as $m): ?>
          <div class="flex items-start gap-2 py-2 border-b border-[#edeeef] last:border-0">
            <div class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5
              <?= $m['status'] === 'completed' ? 'bg-green-500' : 'border-2 border-[#e1e3e4]' ?>">
              <?php if ($m['status'] === 'completed'): ?>
              <span class="material-symbols-outlined text-white text-[12px]">check</span>
              <?php endif; ?>
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium <?= $m['status'] === 'completed' ? 'line-through text-[#8e706c]' : 'text-[#191c1d]' ?>">
                <?= sanitize($m['title']) ?>
              </p>
              <p class="text-xs text-[#8e706c]"><?= formatDate($m['due_date']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Invoice status -->
      <?php if ($inv): ?>
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-3 text-sm">Status Pembayaran</h2>
        <div class="text-center">
          <?= statusBadge($inv['status']) ?>
          <p class="text-2xl font-extrabold text-[#191c1d] mt-3"><?= formatRupiah($inv['amount']) ?></p>
          <p class="text-xs text-[#8e706c] mt-1"><?= sanitize($inv['invoice_number']) ?></p>
          <a href="/hpp-casetrack/client/invoice.php?id=<?= $inv['id'] ?>" class="btn-outline w-full justify-center mt-4 text-xs py-2">
            Lihat Invoice
          </a>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
