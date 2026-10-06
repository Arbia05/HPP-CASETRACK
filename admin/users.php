<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = currentUser();
$db   = getDB();

$error = $success = '';

// Handle delete
if (isPost() && ($_POST['action'] ?? '') === 'delete') {
    $uid = (int)$_POST['user_id'];
    if ($uid === $user['id']) {
        $error = 'Tidak bisa menghapus akun sendiri.';
    } else {
        $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
        $success = 'User berhasil dihapus.';
    }
}

// Handle toggle active
if (isPost() && ($_POST['action'] ?? '') === 'toggle') {
    $uid    = (int)$_POST['user_id'];
    $status = (int)$_POST['current_status'];
    $db->prepare("UPDATE users SET is_active=? WHERE id=?")->execute([!$status, $uid]);
    $success = 'Status user diperbarui.';
}

// Filters
$roleFilter   = sanitize($_GET['role'] ?? '');
$searchFilter = sanitize($_GET['search'] ?? '');
$where  = 'WHERE 1';
$params = [];
if ($roleFilter) { $where .= ' AND role=?'; $params[] = $roleFilter; }
if ($searchFilter) { $where .= ' AND (name LIKE ? OR email LIKE ?)'; $params[] = "%$searchFilter%"; $params[] = "%$searchFilter%"; }

$stmt = $db->prepare("SELECT * FROM users $where ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$totalAll    = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalActive = $db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();

$pageTitle   = 'Manajemen User';
$base        = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Manajemen User</h1>
    <p class="page-subtitle">Kelola seluruh akun pengguna sistem</p>
  </div>
  <a href="<?= $base ?>admin/add-user.php" class="btn btn-primary">
    <i data-lucide="user-plus" class="btn-icon"></i> Tambah User
  </a>
</div>

<!-- Stats -->
<div class="stats-grid" style="--cols:3">
  <div class="stat-card">
    <div class="stat-icon bg-primary"><i data-lucide="users"></i></div>
    <div class="stat-body"><span class="stat-number"><?= $totalAll ?></span><span class="stat-label">Total User</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-success"><i data-lucide="user-check"></i></div>
    <div class="stat-body"><span class="stat-number"><?= $totalActive ?></span><span class="stat-label">Aktif</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-warning"><i data-lucide="user-x"></i></div>
    <div class="stat-body"><span class="stat-number"><?= $totalAll - $totalActive ?></span><span class="stat-label">Nonaktif</span></div>
  </div>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="filter-bar">
      <input type="text" name="search" value="<?= htmlspecialchars($searchFilter) ?>" placeholder="Cari nama atau email…" class="form-control">
      <select name="role" class="form-select">
        <option value="">Semua Role</option>
        <?php foreach (ROLE_NAMES as $key => $label): ?>
        <option value="<?= $key ?>" <?= $roleFilter===$key?'selected':'' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-secondary">Filter</button>
      <a href="<?= $base ?>admin/users.php" class="btn btn-outline">Reset</a>
    </form>
  </div>
</div>

<!-- Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Role</th>
            <th>Kontak</th>
            <th>Status</th>
            <th>Terdaftar</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr><td colspan="6" class="text-center py-8 text-muted">Tidak ada user ditemukan</td></tr>
          <?php endif; ?>
          <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div class="user-cell">
                <div class="avatar-sm"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                <div>
                  <div class="fw-600"><?= htmlspecialchars($u['name']) ?></div>
                  <div class="text-muted text-sm">#<?= $u['id'] ?></div>
                </div>
              </div>
            </td>
            <td><span class="badge badge-<?= getRoleColor($u['role']) ?>"><?= ROLE_NAMES[$u['role']] ?? $u['role'] ?></span></td>
            <td>
              <div><?= htmlspecialchars($u['email']) ?></div>
              <div class="text-muted text-sm"><?= htmlspecialchars($u['phone'] ?? '-') ?></div>
            </td>
            <td>
              <span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                <?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td>
              <div class="action-btns">
                <a href="<?= $base ?>admin/edit-user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline" title="Edit">
                  <i data-lucide="pencil"></i>
                </a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Toggle status user ini?')">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="current_status" value="<?= $u['is_active'] ?>">
                  <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-warning' : 'btn-success' ?>" title="Toggle Status">
                    <i data-lucide="<?= $u['is_active'] ? 'user-x' : 'user-check' ?>"></i>
                  </button>
                </form>
                <?php if ($u['id'] !== $user['id']): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus user ini secara permanen?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger" title="Hapus"><i data-lucide="trash-2"></i></button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
