<?php
// NOTE: auth, functions, and DB must already be loaded by the caller.
$user = currentUser();
$db   = getDB();

$success = $error = '';

if (isPost()) {
    $name    = sanitize($_POST['name'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');

    if (!$name) {
        $error = 'Nama tidak boleh kosong.';
    } else {
        $db->prepare("UPDATE users SET name=?, phone=?, address=? WHERE id=?")->execute([$name, $phone, $address, $user['id']]);

        // Password change
        if (!empty($_POST['new_password'])) {
            if (!password_verify($_POST['current_password'] ?? '', $user['password'])) {
                $error = 'Password lama tidak sesuai.';
            } elseif (strlen($_POST['new_password']) < 6) {
                $error = 'Password baru minimal 6 karakter.';
            } else {
                $hashed = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed, $user['id']]);
            }
        }

        if (!$error) {
            $success = 'Profil berhasil diperbarui.';
            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
            $stmt->execute([$user['id']]);
            $_SESSION[SESSION_NAME] = $stmt->fetch();
            $user = currentUser();
        }
    }
}

$pageTitle = 'Profil Saya';
$base      = getBasePath();
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>
<div id="page-content">
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Profil Saya</h1>
    <p class="page-subtitle">Kelola informasi akun dan keamanan Anda</p>
  </div>
</div>

<div class="profile-grid">
  <!-- Profile Card -->
  <div class="profile-sidebar">
    <div class="card">
      <div class="card-body text-center">
        <div class="profile-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
        <h3 class="profile-name"><?= htmlspecialchars($user['name']) ?></h3>
        <span class="badge badge-<?= getRoleColor($user['role']) ?> badge-lg mb-2">
          <?= ROLE_NAMES[$user['role']] ?? $user['role'] ?>
        </span>
        <div class="profile-meta">
          <div class="meta-item"><i data-lucide="mail" class="icon-xs"></i> <?= htmlspecialchars($user['email']) ?></div>
          <?php if ($user['phone']): ?>
          <div class="meta-item"><i data-lucide="phone" class="icon-xs"></i> <?= htmlspecialchars($user['phone']) ?></div>
          <?php endif; ?>
          <div class="meta-item"><i data-lucide="calendar" class="icon-xs"></i> Bergabung <?= date('M Y', strtotime($user['created_at'])) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Form -->
  <div class="profile-main">
    <div class="card mb-4">
      <div class="card-header"><h2 class="card-title"><i data-lucide="user" class="icon-sm"></i> Informasi Pribadi</h2></div>
      <div class="card-body">
        <form method="POST">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nama Lengkap</label>
              <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
              <small class="form-hint">Email tidak dapat diubah</small>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nomor Telepon</label>
              <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+62812…">
            </div>
            <div class="form-group">
              <label class="form-label">Alamat</label>
              <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="Alamat lengkap">
            </div>
          </div>

          <hr class="divider">
          <h3 class="section-title mb-3"><i data-lucide="lock" class="icon-sm"></i> Ubah Password</h3>
          <p class="text-muted text-sm mb-4">Kosongkan jika tidak ingin mengubah password</p>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Password Saat Ini</label>
              <input type="password" name="current_password" class="form-control" placeholder="••••••••">
            </div>
            <div class="form-group">
              <label class="form-label">Password Baru</label>
              <input type="password" name="new_password" class="form-control" placeholder="Min. 6 karakter">
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">
              <i data-lucide="save" class="btn-icon"></i> Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Account Info -->
    <div class="card">
      <div class="card-header"><h2 class="card-title"><i data-lucide="shield" class="icon-sm"></i> Informasi Akun</h2></div>
      <div class="card-body">
        <div class="info-grid-2">
          <div class="info-item"><span class="info-label">User ID</span><span class="info-value">#<?= $user['id'] ?></span></div>
          <div class="info-item"><span class="info-label">Role</span><span class="info-value"><?= ROLE_NAMES[$user['role']] ?? $user['role'] ?></span></div>
          <div class="info-item"><span class="info-label">Status Akun</span><span class="info-value"><span class="badge badge-success">Aktif</span></span></div>
          <div class="info-item"><span class="info-label">Bergabung</span><span class="info-value"><?= date('d M Y H:i', strtotime($user['created_at'])) ?></span></div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
