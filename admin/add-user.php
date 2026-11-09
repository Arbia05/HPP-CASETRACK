<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = currentUser();
$db   = getDB();

$error = $success = '';

if (isPost()) {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $role     = sanitize($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';

    $validRoles = ['legal_admin','ceo','pengacara_pj','lawyer','finance','head_finance','admin'];

    if (!$name || !$email || !$role || !$password) {
        $error = 'Semua field wajib diisi.';
    } elseif (!in_array($role, $validRoles)) {
        $error = 'Role tidak valid.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } else {
        $exists = $db->prepare("SELECT id FROM users WHERE email=?");
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            $username = strtolower(str_replace(' ', '.', $name)) . rand(10,99);
            $hashed   = password_hash($password, PASSWORD_DEFAULT);
            $is_active = (int)($_POST['is_active'] ?? 1);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role, name, phone, is_active, email_verified_at) VALUES (?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$username, $email, $hashed, $role, $name, $phone, $is_active]);
            $newUserId = $db->lastInsertId();

            // Create lawyer profile if needed
            if (in_array($role, ['pengacara_pj','lawyer'])) {
                $spec = sanitize($_POST['specialization'] ?? '');
                $exp  = (int)($_POST['years_experience'] ?? 0);
                $db->prepare("INSERT INTO lawyers (user_id, specialization, years_experience) VALUES (?,?,?)")
                   ->execute([$newUserId, $spec, $exp]);
            }

            $success = 'Akun ' . sanitize($name) . ' berhasil dibuat!';
        }
    }
}

$pageTitle = 'Tambah Pengguna Baru';
$base = getBasePath();
$roleNames = ROLE_NAMES;
include __DIR__ . '/../includes/header.php';
?>
<div id="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div id="page-content">
<div class="p-6 md:p-8 max-w-2xl mx-auto">

  <nav class="flex items-center gap-2 text-sm text-[#8e706c] mb-6">
    <a href="/hpp-casetrack/admin/dashboard.php" class="hover:text-[#800000]">Dashboard</a>
    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
    <a href="/hpp-casetrack/admin/users.php" class="hover:text-[#800000]">Pengguna</a>
    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
    <span class="text-[#191c1d] font-medium">Tambah Baru</span>
  </nav>

  <div class="page-header">
    <h1>Tambah Pengguna Baru</h1>
    <p>Buat akun untuk staf internal HPP Law Office.</p>
  </div>

  <?php if ($success): ?><div class="alert alert-success mb-6"><?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error mb-6"><?= $error ?></div><?php endif; ?>

  <div class="card p-6 md:p-8">
    <form method="POST" class="space-y-5" id="add-user-form">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">person</span>
            <input type="text" name="name" required placeholder="Nama lengkap"
              value="<?= sanitize($_POST['name'] ?? '') ?>" class="form-input pl-10"/>
          </div>
        </div>
        <div>
          <label class="form-label">Nomor Telepon</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">phone</span>
            <input type="tel" name="phone" placeholder="08xxxxxxxxxx"
              value="<?= sanitize($_POST['phone'] ?? '') ?>" class="form-input pl-10"/>
          </div>
        </div>
      </div>

      <div>
        <label class="form-label">Email <span class="text-red-500">*</span></label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">mail</span>
          <input type="email" name="email" required placeholder="email@hpp-casetrack.id"
            value="<?= sanitize($_POST['email'] ?? '') ?>" class="form-input pl-10"/>
        </div>
      </div>

      <div>
        <label class="form-label">Role / Jabatan <span class="text-red-500">*</span></label>
        <div class="relative">
          <select name="role" required id="role-select" onchange="toggleLawyerFields(this.value)" class="form-input appearance-none pr-10">
            <option value="">-- Pilih role --</option>
            <?php foreach (array_filter(ROLE_NAMES, fn($k) => $k !== 'client', ARRAY_FILTER_USE_KEY) as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= ($_POST['role'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
          <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[#8e706c] pointer-events-none">expand_more</span>
        </div>
      </div>

      <!-- Lawyer-specific fields -->
      <div id="lawyer-fields" class="hidden space-y-4 p-4 bg-[#f3f4f5] rounded-xl border border-[#e1e3e4]">
        <p class="text-xs font-semibold text-[#5a413d] uppercase tracking-wider">Informasi Pengacara</p>
        <div>
          <label class="form-label">Spesialisasi</label>
          <input type="text" name="specialization" placeholder="cth: Litigasi Perdata & Pidana"
            value="<?= sanitize($_POST['specialization'] ?? '') ?>" class="form-input"/>
        </div>
        <div>
          <label class="form-label">Tahun Pengalaman</label>
          <input type="number" name="years_experience" min="0" max="50" placeholder="0"
            value="<?= sanitize($_POST['years_experience'] ?? '') ?>" class="form-input"/>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="form-label">Password <span class="text-red-500">*</span></label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#8e706c] text-[20px]">lock</span>
            <input type="password" name="password" required placeholder="Minimal 8 karakter" class="form-input pl-10"/>
          </div>
        </div>
        <div>
          <div>
          <label class="form-label">Status Akun</label>
          <div class="relative">
            <select name="is_active" class="form-input appearance-none pr-10">
              <option value="1" selected>Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[#8e706c] pointer-events-none">expand_more</span>
          </div>
        </div>
      </div>

      <div class="section-divider"></div>

      <div class="flex gap-3 justify-end">
        <a href="/hpp-casetrack/admin/users.php" class="btn-outline">Batal</a>
        <button type="submit" class="btn-primary px-8">
          <span class="material-symbols-outlined text-[18px]">person_add</span>
          Buat Akun
        </button>
      </div>
    </form>
  </div>
</div>
</div>
</div>
<script>
function toggleLawyerFields(role) {
  const fields = document.getElementById('lawyer-fields');
  if (role === 'pengacara_pj' || role === 'lawyer') {
    fields.classList.remove('hidden');
  } else {
    fields.classList.add('hidden');
  }
}
// Init on load
toggleLawyerFields(document.getElementById('role-select').value);
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
