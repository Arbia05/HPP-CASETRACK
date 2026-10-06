<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = currentUser();
$db   = getDB();

$totalUsers   = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers  = $db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
$pendingUsers = $db->query("SELECT COUNT(*) FROM users WHERE is_active=0")->fetchColumn();
$roleDistrib  = $db->query("SELECT role, COUNT(*) AS cnt FROM users GROUP BY role")->fetchAll();

$allUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 15")->fetchAll();

$pageTitle = 'Dashboard Admin';
$base = getBasePath();
include __DIR__ . '/../includes/header.php';
$roleNames = ROLE_NAMES;
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-7xl mx-auto">

  <div class="page-header">
    <h1>Dashboard Administrator</h1>
    <p>Kelola akun pengguna, hak akses, dan konfigurasi sistem.</p>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Total Pengguna</p>
        <div class="w-9 h-9 bg-[#fff1f1] rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-[#800000] text-[18px]">people</span>
        </div>
      </div>
      <div class="text-3xl font-extrabold"><?= $totalUsers ?></div>
    </div>
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Aktif</p>
        <div class="w-9 h-9 bg-green-50 rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-green-600 text-[18px]">check_circle</span>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-green-600"><?= $activeUsers ?></div>
    </div>
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#5a413d]">Pending Konfirmasi</p>
        <div class="w-9 h-9 bg-yellow-50 rounded-xl flex items-center justify-center">
          <span class="material-symbols-outlined text-yellow-600 text-[18px]">pending</span>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-yellow-600"><?= $pendingUsers ?></div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Users Table -->
    <div class="lg:col-span-2 card overflow-hidden">
      <div class="px-6 py-4 border-b border-[#e2bfb9] flex items-center justify-between">
        <h2 class="font-bold text-[#191c1d]">Pengguna Terbaru</h2>
        <div class="flex gap-2">
          <a href="/admin/users.php" class="btn-outline btn-sm">Lihat Semua</a>
          <a href="/admin/add-user.php" class="btn-primary btn-sm">
            <span class="material-symbols-outlined text-[16px]">person_add</span>
            Tambah
          </a>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead><tr>
            <th>Pengguna</th><th>Role</th><th>Status</th><th>Bergabung</th><th>Aksi</th>
          </tr></thead>
          <tbody>
            <?php foreach ($allUsers as $u): ?>
            <tr>
              <td>
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-[#800000] flex items-center justify-center text-white text-xs font-bold shrink-0">
                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                  </div>
                  <div>
                    <div class="font-medium text-sm"><?= sanitize($u['name']) ?></div>
                    <div class="text-xs text-[#8e706c]"><?= sanitize($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <td>
                <span class="text-xs bg-[#fff1f1] text-[#800000] font-semibold px-2 py-1 rounded">
                  <?= $roleNames[$u['role']] ?? $u['role'] ?>
                </span>
              </td>
              <td>
                <?php
                $statusClasses = ['active'=>'text-green-700 bg-green-50','inactive'=>'text-gray-700 bg-gray-100','pending'=>'text-yellow-700 bg-yellow-50'];
                $sc = $statusClasses[$u['status']] ?? 'bg-gray-100';
                $statusLabels = ['active'=>'Aktif','inactive'=>'Nonaktif','pending'=>'Pending'];
                ?>
                <span class="text-xs font-semibold px-2 py-1 rounded <?= $sc ?>">
                  <?= $statusLabels[$u['status']] ?? $u['status'] ?>
                </span>
              </td>
              <td class="text-xs text-[#8e706c]"><?= formatDate($u['created_at'], 'd M Y') ?></td>
              <td>
                <div class="flex gap-1">
                  <a href="/admin/edit-user.php?id=<?= $u['id'] ?>" class="p-1.5 text-[#5a413d] hover:text-[#800000] hover:bg-[#fff1f1] rounded-lg transition-colors" title="Edit">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                  </a>
                  <?php if ($u['status'] === 'pending'): ?>
                  <a href="/admin/activate-user.php?id=<?= $u['id'] ?>" class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Aktifkan"
                    onclick="return confirm('Aktifkan pengguna ini?')">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                  </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Role Distribution -->
    <div class="space-y-5">
      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-4">Distribusi Role</h2>
        <div class="space-y-3">
          <?php foreach ($roleDistrib as $rd):
            $pct = $totalUsers > 0 ? round(($rd['cnt'] / $totalUsers) * 100) : 0;
          ?>
          <div>
            <div class="flex justify-between text-sm mb-1">
              <span class="text-[#5a413d] text-xs"><?= $roleNames[$rd['role']] ?? $rd['role'] ?></span>
              <span class="font-semibold text-xs"><?= $rd['cnt'] ?></span>
            </div>
            <div class="h-1.5 bg-[#edeeef] rounded-full">
              <div class="h-full bg-[#800000] rounded-full" style="width:<?= $pct ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card p-5">
        <h2 class="font-bold text-[#191c1d] mb-4">Aksi Cepat</h2>
        <div class="space-y-2">
          <?php foreach ([
            ['/admin/add-user.php','Tambah User Baru','person_add'],
            ['/admin/users.php?filter=pending','Aktivasi User Pending','pending_actions'],
            ['/admin/settings.php','Pengaturan Sistem','settings'],
          ] as [$url,$label,$icon]): ?>
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
