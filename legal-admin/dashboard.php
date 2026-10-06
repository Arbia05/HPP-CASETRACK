<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('legal_admin');
$user = currentUser();
$db   = getDB();

// Stats
$newComplaints    = $db->query("SELECT COUNT(*) FROM complaints WHERE status='new'")->fetchColumn();
$pendingComplaints= $db->query("SELECT COUNT(*) FROM complaints WHERE status='pending_completion'")->fetchColumn();
$activeCases      = $db->query("SELECT COUNT(*) FROM cases WHERE status='active'")->fetchColumn();
$totalClients     = $db->query("SELECT COUNT(*) FROM users WHERE role='client' AND status='active'")->fetchColumn();
$pendingStaff     = $db->query("SELECT COUNT(*) FROM users WHERE status='pending' AND role != 'client'")->fetchColumn();

// Recent complaints
$complaints = $db->query("SELECT c.*, u.name AS client_name, u.email AS client_email FROM complaints c JOIN users u ON c.client_id=u.id ORDER BY c.created_at DESC LIMIT 8")->fetchAll();

// Upcoming milestones
$milestones = $db->query("SELECT m.*, c.title AS case_title, c.case_number AS reference_number FROM milestones m JOIN cases c ON m.case_id=c.id WHERE m.status IN ('pending','in_progress') AND m.due_date >= CURDATE() ORDER BY m.due_date LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard Legal Admin';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-7xl mx-auto">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
    <div>
      <p class="text-xs font-semibold text-[#8e706c] uppercase tracking-widest mb-1">Legal Admin</p>
      <h1 class="text-3xl font-bold text-[#191c1d]">Dashboard</h1>
      <p class="text-sm text-[#5a413d] mt-1">Kelola pengaduan masuk dan perkara aktif.</p>
    </div>
    <a href="/legal-admin/complaints.php" class="btn-primary">
      <span class="material-symbols-outlined text-[18px]">inbox</span>
      Lihat Semua Pengaduan
    </a>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    <?php
    $stats = [
      ['Pengaduan Baru','new','inbox',$newComplaints,'text-blue-600','bg-blue-50'],
      ['Menunggu Data','pending','pending_actions',$pendingComplaints,'text-yellow-700','bg-yellow-50'],
      ['Kasus Aktif','active','gavel',$activeCases,'text-green-700','bg-green-50'],
      ['Total Klien','','people',$totalClients,'text-[#5a413d]','bg-[#f3f4f5]'],
      ['Staff Pending','','manage_accounts',$pendingStaff,'text-[#800000]','bg-[#fff1f1]'],
    ];
    foreach ($stats as [$lbl,$status,$icon,$val,$color,$bg]):
    ?>
    <div class="stat-card text-center">
      <div class="w-10 h-10 rounded-xl <?= $bg ?> flex items-center justify-center mx-auto mb-3">
        <span class="material-symbols-outlined <?= $color ?>"><?= $icon ?></span>
      </div>
      <div class="text-2xl font-extrabold text-[#191c1d]"><?= $val ?></div>
      <p class="text-xs text-[#5a413d] mt-1 leading-tight"><?= $lbl ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Complaints Table -->
    <div class="lg:col-span-2 card overflow-hidden">
      <div class="px-6 py-4 border-b border-[#e2bfb9] flex items-center justify-between">
        <h2 class="font-bold text-[#191c1d]">Pengaduan Masuk Terbaru</h2>
        <a href="/legal-admin/complaints.php" class="text-sm text-[#800000] hover:underline font-medium">Lihat Semua →</a>
      </div>
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Klien</th>
              <th>Judul Kasus</th>
              <th>Kategori</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($complaints)): ?>
            <tr><td colspan="5" class="text-center py-8 text-[#8e706c]">Belum ada pengaduan masuk.</td></tr>
            <?php endif; ?>
            <?php foreach ($complaints as $c): ?>
            <tr>
              <td>
                <div class="font-medium"><?= sanitize($c['client_name']) ?></div>
                <div class="text-xs text-[#8e706c]"><?= sanitize($c['client_email']) ?></div>
              </td>
              <td class="max-w-[200px] truncate"><?= sanitize($c['title']) ?></td>
              <td><span class="text-xs bg-[#f3f4f5] px-2 py-1 rounded"><?= sanitize($c['case_category'] ?? 'Umum') ?></span></td>
              <td><?= statusBadge($c['status']) ?></td>
              <td>
                <a href="/legal-admin/complaint-detail.php?id=<?= $c['id'] ?>"
                  class="inline-flex items-center gap-1 text-xs text-[#800000] hover:underline font-medium">
                  Review <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Right sidebar -->
    <div class="space-y-6">
      <!-- Upcoming milestones -->
      <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-[#e2bfb9]">
          <h2 class="font-bold text-[#191c1d] flex items-center gap-2">
            <span class="material-symbols-outlined text-[#800000] text-[20px]">event</span>
            Deadline Mendatang
          </h2>
        </div>
        <div class="divide-y divide-[#edeeef]">
          <?php if (empty($milestones)): ?>
          <p class="p-4 text-sm text-[#8e706c] text-center">Tidak ada milestone mendatang.</p>
          <?php endif; ?>
          <?php foreach ($milestones as $m): ?>
          <div class="px-5 py-4">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-[#fff1f1] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[#800000] text-[16px]">flag</span>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-[#191c1d] truncate"><?= sanitize($m['title']) ?></p>
                <p class="text-xs text-[#5a413d] truncate"><?= sanitize($m['case_title']) ?></p>
                <p class="text-xs font-semibold text-[#800000] mt-1"><?= formatDate($m['due_date'], 'd M Y') ?></p>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="px-5 py-3">
            <a href="/legal-admin/schedule.php" class="text-xs text-[#800000] hover:underline font-medium">Lihat Semua Jadwal →</a>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-4">Aksi Cepat</h2>
        <div class="space-y-2">
          <?php
          $actions = [
            ['/legal-admin/complaints.php?filter=new','Tinjau Pengaduan Baru','inbox'],
            ['/legal-admin/cases.php','Manajemen Kasus','gavel'],
            ['/legal-admin/schedule.php','Atur Jadwal & Milestone','event'],
            ['/legal-admin/staff-pending.php','Konfirmasi Staff Baru','manage_accounts'],
          ];
          foreach ($actions as [$url,$label,$icon]): ?>
          <a href="<?= $url ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#f3f4f5] transition-colors group">
            <span class="material-symbols-outlined text-[#800000] text-[20px]"><?= $icon ?></span>
            <span class="text-sm text-[#191c1d] group-hover:text-[#800000]"><?= $label ?></span>
            <span class="material-symbols-outlined text-[14px] text-[#e1e3e4] ml-auto">arrow_forward_ios</span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
