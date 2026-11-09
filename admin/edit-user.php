<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = currentUser();
$db   = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/hpp-casetrack/admin/users.php');

$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$target = $stmt->fetch();
if (!$target) redirect('/hpp-casetrack/admin/users.php');

$error = $success = '';

if (isPost()) {
    $name    = sanitize($_POST['name'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $role    = sanitize($_POST['role'] ?? '');
    $address = sanitize($_POST['address'] ?? '');

    if (!$name || !$email || !$role) {
        $error = 'Nama, email, dan role wajib diisi.';
    } else {
        // Check email unique (exclude self)
        $check = $db->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $check->execute([$email, $id]);
        if ($check->fetch()) {
            $error = 'Email sudah digunakan oleh user lain.';
        } else {
            $db->prepare("UPDATE users SET name=?, email=?, phone=?, role=?, address=? WHERE id=?")
               ->execute([$name, $email, $phone, $role, $address, $id]);

            // Optional password reset
            if (!empty($_POST['new_password'])) {
                if (strlen($_POST['new_password']) < 6) {
                    $error = 'Password minimal 6 karakter.';
                } else {
                    $db->prepare("UPDATE users SET password=? WHERE id=?")
                       ->execute([password_hash($_POST['new_password'], PASSWORD_BCRYPT), $id]);
                }
            }

            if (!$error) {
                $success = 'Data user berhasil diperbarui.';
                $stmt->execute([$id]);
                $target = $stmt->fetch();
            }
        }
    }
}

$pageTitle = 'Edit User — ' . htmlspecialchars($target['name']);
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Edit User</h1>
    <p class="page-subtitle">Mengedit data akun: <strong><?= htmlspecialchars($target['name']) ?></strong></p>
  </div>
  <a href="<?= $base ?>admin/users.php" class="btn btn-outline">
    <i data-lucide="arrow-left" class="btn-icon"></i> Kembali
  </a>
</div>

<div class="card" style="max-width:680px">
  <div class="card-header"><h2 class="card-title"><i data-lucide="user-cog" class="icon-sm"></i> Data User</h2></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($target['name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($target['email']) ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Role</label>
          <select name="role" class="form-select" required>
            <?php foreach (ROLE_NAMES as $key => $label): ?>
            <option value="<?= $key ?>" <?= $target['role']===$key?'selected':'' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nomor Telepon</label>
          <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($target['phone'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Alamat</label>
        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($target['address'] ?? '') ?>">
      </div>

      <hr class="divider">
      <h3 class="section-title mb-3"><i data-lucide="lock" class="icon-sm"></i> Reset Password</h3>
      <p class="text-muted text-sm mb-3">Kosongkan jika tidak ingin mengubah password user ini</p>
      <div class="form-group">
        <label class="form-label">Password Baru</label>
        <input type="password" name="new_password" class="form-control" placeholder="Min. 6 karakter">
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save" class="btn-icon"></i> Simpan Perubahan
        </button>
        <a href="<?= $base ?>admin/users.php" class="btn btn-outline">Batal</a>
      </div>
    </form>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
