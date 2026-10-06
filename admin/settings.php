<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = currentUser();
$db   = getDB();

$success = $error = '';

// Load current settings from a simple config table (or use hardcoded defaults)
$defaults = [
    'app_name'        => 'HPP CaseTrack',
    'app_tagline'     => 'Law Office Management System',
    'contact_email'   => 'info@hpp-casetrack.id',
    'contact_phone'   => '021-1234567',
    'office_address'  => 'Jl. Sudirman No. 1, Jakarta',
    'whatsapp_number' => '6281200000001',
    'invoice_prefix'  => 'INV',
    'case_prefix'     => 'HPP',
    'max_upload_mb'   => '10',
];

// Create settings table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
)");

// Load saved settings
$settingsStmt = $db->query("SELECT setting_key, setting_value FROM app_settings");
$saved = [];
foreach ($settingsStmt->fetchAll() as $row) {
    $saved[$row['setting_key']] = $row['setting_value'];
}
$settings = array_merge($defaults, $saved);

if (isPost()) {
    $fields = ['app_name','app_tagline','contact_email','contact_phone','office_address','whatsapp_number','invoice_prefix','case_prefix','max_upload_mb'];
    $stmt = $db->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    foreach ($fields as $f) {
        $val = sanitize($_POST[$f] ?? '');
        $stmt->execute([$f, $val]);
        $settings[$f] = $val;
    }
    $success = 'Pengaturan berhasil disimpan.';
}

$pageTitle = 'Pengaturan Sistem';
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Pengaturan Sistem</h1>
    <p class="page-subtitle">Konfigurasi umum aplikasi HPP CaseTrack</p>
  </div>
</div>

<div class="card" style="max-width:720px">
  <div class="card-body">
    <form method="POST">
      <h3 class="section-title mb-3"><i data-lucide="building-2" class="icon-sm"></i> Identitas Firma</h3>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama Aplikasi</label>
          <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($settings['app_name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Tagline</label>
          <input type="text" name="app_tagline" class="form-control" value="<?= htmlspecialchars($settings['app_tagline']) ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Alamat Kantor</label>
        <input type="text" name="office_address" class="form-control" value="<?= htmlspecialchars($settings['office_address']) ?>">
      </div>

      <hr class="divider">
      <h3 class="section-title mb-3"><i data-lucide="phone" class="icon-sm"></i> Kontak</h3>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email Kontak</label>
          <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings['contact_email']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Telepon Kantor</label>
          <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($settings['contact_phone']) ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Nomor WhatsApp (format: 628xxx)</label>
        <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars($settings['whatsapp_number']) ?>">
        <small class="form-hint">Digunakan untuk tombol "Hubungi via WhatsApp" di landing page</small>
      </div>

      <hr class="divider">
      <h3 class="section-title mb-3"><i data-lucide="settings-2" class="icon-sm"></i> Konfigurasi Sistem</h3>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Prefix Nomor Kasus</label>
          <input type="text" name="case_prefix" class="form-control" value="<?= htmlspecialchars($settings['case_prefix']) ?>" maxlength="10">
          <small class="form-hint">Contoh: HPP → HPP-2025-0001</small>
        </div>
        <div class="form-group">
          <label class="form-label">Prefix Invoice</label>
          <input type="text" name="invoice_prefix" class="form-control" value="<?= htmlspecialchars($settings['invoice_prefix']) ?>" maxlength="10">
          <small class="form-hint">Contoh: INV → INV-202501-0001</small>
        </div>
      </div>
      <div class="form-group" style="max-width:200px">
        <label class="form-label">Batas Upload File (MB)</label>
        <input type="number" name="max_upload_mb" class="form-control" value="<?= htmlspecialchars($settings['max_upload_mb']) ?>" min="1" max="100">
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save" class="btn-icon"></i> Simpan Pengaturan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- System Info -->
<div class="card mt-4" style="max-width:720px">
  <div class="card-header"><h2 class="card-title"><i data-lucide="info" class="icon-sm"></i> Informasi Sistem</h2></div>
  <div class="card-body">
    <div class="info-grid-2">
      <div class="info-item"><span class="info-label">PHP Version</span><span class="info-value"><?= phpversion() ?></span></div>
      <div class="info-item"><span class="info-label">Server</span><span class="info-value"><?= php_uname('s') . ' ' . php_uname('r') ?></span></div>
      <div class="info-item"><span class="info-label">Max Upload</span><span class="info-value"><?= ini_get('upload_max_filesize') ?></span></div>
      <div class="info-item"><span class="info-label">Memory Limit</span><span class="info-value"><?= ini_get('memory_limit') ?></span></div>
      <div class="info-item"><span class="info-label">Timezone</span><span class="info-value"><?= date_default_timezone_get() ?></span></div>
      <div class="info-item"><span class="info-label">App Version</span><span class="info-value">HPP CaseTrack v1.0</span></div>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
