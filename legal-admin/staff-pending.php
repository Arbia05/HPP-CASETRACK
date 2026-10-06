<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('legal_admin');
$user      = currentUser();
$db        = getDB();
$pageTitle = 'Staff Pending Verifikasi';
$base      = getBasePath();

// Staff whose accounts are not yet verified (email_verified_at IS NULL) or is_active=0
$stmt = $db->query("SELECT * FROM users WHERE (email_verified_at IS NULL OR is_active=0) AND role != 'client' ORDER BY created_at DESC");
$pendingStaff = $stmt->fetchAll();

// Handle activate
$success = '';
if (isPost() && ($_POST['action'] ?? '') === 'activate') {
    $uid = (int)$_POST['user_id'];
    $db->prepare("UPDATE users SET is_active=1, email_verified_at=NOW() WHERE id=?")->execute([$uid]);
    $success = 'Akun staff berhasil diaktifkan.';
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Staff Pending</h1>
    <p class="page-subtitle">Akun staff yang menunggu aktivasi</p>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr><th>Nama</th><th>Email</th><th>Role</th><th>Terdaftar</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          <?php if (empty($pendingStaff)): ?>
          <tr><td colspan="5" class="text-center py-8 text-muted">Tidak ada staff pending</td></tr>
          <?php endif; ?>
          <?php foreach ($pendingStaff as $s): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td><span class="badge badge-secondary"><?= ROLE_NAMES[$s['role']] ?? $s['role'] ?></span></td>
            <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
            <td>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="activate">
                <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-sm btn-success">Aktifkan</button>
              </form>
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
