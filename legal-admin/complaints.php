<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('legal_admin');
$user = currentUser();
$db = getDB();

// Filters
$filter = sanitize($_GET['filter'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$where = 'WHERE 1=1';
$params = [];
if ($filter) { $where .= ' AND c.status=?'; $params[] = $filter; }
if ($search) { $where .= ' AND (c.title LIKE ? OR u.name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$complaints = $db->prepare("SELECT c.*, u.name AS client_name, u.email AS client_email, u.phone AS client_phone FROM complaints c JOIN users u ON c.client_id=u.id $where ORDER BY FIELD(c.status,'new','pending_completion','verified','rejected'), c.created_at DESC");
$complaints->execute($params);
$allComplaints = $complaints->fetchAll();

$counts = $db->query("SELECT status, COUNT(*) AS cnt FROM complaints GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Manajemen Pengaduan';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-7xl mx-auto">

  <div class="page-header">
    <h1>Manajemen Pengaduan</h1>
    <p>Tinjau dan validasi pengaduan kasus yang masuk dari klien.</p>
  </div>

  <!-- Filter tabs -->
  <div class="flex gap-2 flex-wrap mb-6">
    <?php
    $tabs = [
      '' => ['Semua', array_sum($counts)],
      'new' => ['Baru', $counts['new'] ?? 0],
      'pending_completion' => ['Perlu Kelengkapan', $counts['pending_completion'] ?? 0],
      'verified' => ['Terverifikasi', $counts['verified'] ?? 0],
      'rejected' => ['Ditolak', $counts['rejected'] ?? 0],
    ];
    foreach ($tabs as $val => [$label, $count]):
    $active = $filter === $val;
    ?>
    <a href="?filter=<?= $val ?>" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all
      <?= $active ? 'bg-[#800000] text-white shadow-sm' : 'bg-white text-[#5a413d] border border-[#e1e3e4] hover:border-[#800000] hover:text-[#800000]' ?>">
      <?= $label ?>
      <span class="text-xs px-1.5 py-0.5 rounded-full <?= $active ? 'bg-white/20 text-white' : 'bg-[#f3f4f5] text-[#5a413d]' ?>"><?= $count ?></span>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Search -->
  <div class="flex gap-3 mb-6">
    <form method="GET" class="flex gap-2 flex-1 max-w-lg">
      <input type="hidden" name="filter" value="<?= $filter ?>"/>
      <div class="relative flex-1">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">search</span>
        <input type="text" name="search" placeholder="Cari nama klien atau judul kasus..."
          value="<?= $search ?>"
          class="form-input pl-10"/>
      </div>
      <button type="submit" class="btn-primary">Cari</button>
    </form>
  </div>

  <!-- Table -->
  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead><tr>
          <th>Klien</th>
          <th>Judul Kasus</th>
          <th>Kategori</th>
          <th>Status</th>
          <th>Tanggal Masuk</th>
          <th class="text-center">Aksi</th>
        </tr></thead>
        <tbody>
          <?php if (empty($allComplaints)): ?>
          <tr><td colspan="6" class="text-center py-10 text-[#8e706c]">
            <span class="material-symbols-outlined text-4xl text-[#e1e3e4] block mb-2">search_off</span>
            Tidak ada pengaduan ditemukan.
          </td></tr>
          <?php endif; ?>
          <?php foreach ($allComplaints as $c): ?>
          <tr>
            <td>
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[#800000] flex items-center justify-center text-white text-xs font-bold shrink-0">
                  <?= strtoupper(substr($c['client_name'], 0, 1)) ?>
                </div>
                <div>
                  <div class="font-medium text-sm"><?= sanitize($c['client_name']) ?></div>
                  <div class="text-xs text-[#8e706c]"><?= sanitize($c['client_email']) ?></div>
                </div>
              </div>
            </td>
            <td class="max-w-[200px]">
              <p class="truncate font-medium text-sm"><?= sanitize($c['title']) ?></p>
              <p class="text-xs text-[#8e706c] mt-0.5 truncate"><?= sanitize(substr($c['description'], 0, 80)) ?>...</p>
            </td>
            <td><span class="text-xs bg-[#f3f4f5] px-2 py-1 rounded font-medium"><?= sanitize($c['case_category'] ?? 'Umum') ?></span></td>
            <td><?= statusBadge($c['status']) ?></td>
            <td class="text-xs text-[#8e706c] whitespace-nowrap"><?= formatDate($c['created_at'], 'd M Y') ?></td>
            <td class="text-center">
              <a href="/hpp-casetrack/legal-admin/complaint-detail.php?id=<?= $c['id'] ?>"
                class="btn-outline btn-sm">
                <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                Tinjau
              </a>
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
