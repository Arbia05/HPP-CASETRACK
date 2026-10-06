<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('pengacara_pj');
$user = currentUser();
$db   = getDB();

$id   = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("
  SELECT c.*, cl.name AS client_name, cl.email AS client_email, cl.phone AS client_phone,
         pj.name AS pj_name
  FROM cases c
  JOIN users cl ON cl.id = c.client_id
  LEFT JOIN users pj ON pj.id = c.assigned_pj_id
  WHERE c.id=? AND c.assigned_pj_id=?
");
$stmt->execute([$id, $user['id']]);
$case = $stmt->fetch();
if (!$case) {
    redirect('/pengacara-pj/cases.php');
}

$error = $success = '';

// Handle add team member
if (isPost() && ($_POST['action'] ?? '') === 'add_member') {
    $lawyerId = (int)$_POST['lawyer_id'];
    $role     = sanitize($_POST['team_role'] ?? 'lawyer');
    $check    = $db->prepare("SELECT id FROM case_team WHERE case_id=? AND lawyer_id=?");
    $check->execute([$id, $lawyerId]);
    if ($check->fetch()) {
        $error = 'Pengacara sudah ada di tim ini.';
    } else {
        $db->prepare("INSERT INTO case_team (case_id, lawyer_id, role) VALUES (?,?,?)")->execute([$id, $lawyerId, $role]);
        $success = 'Anggota tim berhasil ditambahkan.';
    }
}

// Handle remove team member
if (isPost() && ($_POST['action'] ?? '') === 'remove_member') {
    $memberId = (int)$_POST['member_id'];
    $db->prepare("DELETE FROM case_team WHERE id=? AND case_id=?")->execute([$memberId, $id]);
    $success = 'Anggota tim berhasil dihapus.';
}

// Handle update strategy
if (isPost() && ($_POST['action'] ?? '') === 'update_strategy') {
    $strategy = sanitize($_POST['strategy'] ?? '');
    $db->prepare("UPDATE cases SET strategy=? WHERE id=?")->execute([$strategy, $id]);
    $success = 'Strategi berhasil diperbarui.';
    $case['strategy'] = $strategy;
}

// Team members
$teamStmt = $db->prepare("
  SELECT ct.*, u.name, u.email, u.role FROM case_team ct
  JOIN users u ON u.id = ct.lawyer_id
  WHERE ct.case_id=?
");
$teamStmt->execute([$id]);
$team = $teamStmt->fetchAll();

// Available lawyers to add
$usedIds    = array_column($team, 'lawyer_id');
$usedIds[]  = $user['id'];
$placeholders = implode(',', array_fill(0, count($usedIds), '?'));
$availStmt    = $db->prepare("SELECT id, name, role FROM users WHERE role IN ('lawyer') AND id NOT IN ($placeholders) AND is_active=1");
$availStmt->execute($usedIds);
$availLawyers = $availStmt->fetchAll();

// Activities
$actStmt = $db->prepare("SELECT ca.*, u.name AS actor_name FROM case_activities ca JOIN users u ON u.id=ca.user_id WHERE ca.case_id=? ORDER BY ca.created_at DESC");
$actStmt->execute([$id]);
$activities = $actStmt->fetchAll();

// Documents
$docStmt = $db->prepare("SELECT cd.*, u.name AS uploader FROM case_documents cd JOIN users u ON u.id=cd.uploaded_by WHERE cd.case_id=?");
$docStmt->execute([$id]);
$documents = $docStmt->fetchAll();

$pageTitle = 'Detail Kasus — ' . $case['case_number'];
$base      = getBasePath();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div id="page-content">
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><?= htmlspecialchars($case['case_number']) ?></h1>
    <p class="page-subtitle"><?= htmlspecialchars($case['case_type']) ?> — Klien: <?= htmlspecialchars($case['client_name']) ?></p>
  </div>
  <span class="badge badge-<?= getCaseStatusColor($case['status']) ?> badge-lg"><?= ucfirst($case['status']) ?></span>
</div>

<div class="detail-grid">
  <!-- LEFT COLUMN -->
  <div class="detail-main">

    <!-- Strategy -->
    <div class="card mb-4">
      <div class="card-header">
        <h2 class="card-title"><i data-lucide="target" class="icon-sm"></i> Strategi Kasus</h2>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_strategy">
          <textarea name="strategy" class="form-control" rows="5" placeholder="Tuliskan strategi penanganan kasus ini…"><?= htmlspecialchars($case['strategy'] ?? '') ?></textarea>
          <div class="mt-3">
            <button type="submit" class="btn btn-primary">
              <i data-lucide="save" class="btn-icon"></i> Simpan Strategi
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Team Management -->
    <div class="card mb-4">
      <div class="card-header">
        <h2 class="card-title"><i data-lucide="users" class="icon-sm"></i> Tim Pengacara</h2>
      </div>
      <div class="card-body">
        <!-- Current team -->
        <div class="team-list mb-4">
          <div class="team-member pj-member">
            <div class="avatar-sm"><?= strtoupper(substr($case['pj_name'],0,1)) ?></div>
            <div class="member-info">
              <span class="member-name"><?= htmlspecialchars($case['pj_name']) ?></span>
              <span class="member-role badge badge-primary">Pengacara PJ (Anda)</span>
            </div>
          </div>
          <?php foreach ($team as $m): ?>
          <div class="team-member">
            <div class="avatar-sm"><?= strtoupper(substr($m['name'],0,1)) ?></div>
            <div class="member-info">
              <span class="member-name"><?= htmlspecialchars($m['name']) ?></span>
              <span class="member-role badge badge-secondary"><?= htmlspecialchars($m['role'] ?? 'Anggota') ?></span>
            </div>
            <form method="POST" class="ml-auto" onsubmit="return confirm('Hapus anggota ini dari tim?')">
              <input type="hidden" name="action" value="remove_member">
              <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger"><i data-lucide="x"></i></button>
            </form>
          </div>
          <?php endforeach; ?>
          <?php if (empty($team)): ?>
          <p class="text-muted text-sm">Belum ada anggota tim ditambahkan</p>
          <?php endif; ?>
        </div>

        <!-- Add member -->
        <?php if (!empty($availLawyers)): ?>
        <form method="POST" class="add-member-form">
          <input type="hidden" name="action" value="add_member">
          <select name="lawyer_id" class="form-select" required>
            <option value="">— Pilih Pengacara —</option>
            <?php foreach ($availLawyers as $l): ?>
            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="team_role" class="form-select">
            <option value="lawyer">Pengacara Anggota</option>
            <option value="paralegal">Paralegal</option>
            <option value="researcher">Peneliti Hukum</option>
          </select>
          <button type="submit" class="btn btn-primary">
            <i data-lucide="plus" class="btn-icon"></i> Tambahkan
          </button>
        </form>
        <?php else: ?>
        <p class="text-muted text-sm mt-2">Tidak ada pengacara tersedia untuk ditambahkan</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Activity Timeline -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title"><i data-lucide="activity" class="icon-sm"></i> Riwayat Aktivitas</h2>
      </div>
      <div class="card-body p-0">
        <?php if (empty($activities)): ?>
        <p class="text-muted text-sm p-4">Belum ada aktivitas tercatat</p>
        <?php else: ?>
        <div class="timeline">
          <?php foreach ($activities as $act): ?>
          <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
              <div class="timeline-header">
                <span class="fw-600"><?= htmlspecialchars($act['actor_name']) ?></span>
                <span class="text-muted text-sm"><?= date('d M Y H:i', strtotime($act['created_at'])) ?></span>
              </div>
              <p class="timeline-text"><?= nl2br(htmlspecialchars($act['description'])) ?></p>
              <?php if ($act['location']): ?>
              <div class="timeline-location">
                <i data-lucide="map-pin" class="icon-xs"></i>
                <?= htmlspecialchars($act['location']) ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /detail-main -->

  <!-- RIGHT COLUMN -->
  <div class="detail-sidebar">

    <!-- Case Info -->
    <div class="card mb-4">
      <div class="card-header"><h2 class="card-title">Info Kasus</h2></div>
      <div class="card-body">
        <div class="info-list">
          <div class="info-item"><span class="info-label">No. Kasus</span><span class="info-value fw-600 text-primary"><?= htmlspecialchars($case['case_number']) ?></span></div>
          <div class="info-item"><span class="info-label">Tipe</span><span class="info-value"><?= htmlspecialchars($case['case_type']) ?></span></div>
          <div class="info-item"><span class="info-label">Status</span><span class="info-value"><span class="badge badge-<?= getCaseStatusColor($case['status']) ?>"><?= ucfirst($case['status']) ?></span></span></div>
          <div class="info-item"><span class="info-label">Dibuat</span><span class="info-value"><?= date('d M Y', strtotime($case['created_at'])) ?></span></div>
        </div>
      </div>
    </div>

    <!-- Client Info -->
    <div class="card mb-4">
      <div class="card-header"><h2 class="card-title">Info Klien</h2></div>
      <div class="card-body">
        <div class="info-list">
          <div class="info-item"><span class="info-label">Nama</span><span class="info-value"><?= htmlspecialchars($case['client_name']) ?></span></div>
          <div class="info-item"><span class="info-label">Email</span><span class="info-value"><?= htmlspecialchars($case['client_email']) ?></span></div>
          <div class="info-item"><span class="info-label">Telepon</span><span class="info-value"><?= htmlspecialchars($case['client_phone'] ?? '-') ?></span></div>
        </div>
      </div>
    </div>

    <!-- Documents -->
    <div class="card">
      <div class="card-header"><h2 class="card-title"><i data-lucide="file-text" class="icon-sm"></i> Dokumen</h2></div>
      <div class="card-body p-0">
        <?php if (empty($documents)): ?>
        <p class="text-muted text-sm p-4">Belum ada dokumen</p>
        <?php else: ?>
        <div class="doc-list">
          <?php foreach ($documents as $doc): ?>
          <div class="doc-item">
            <i data-lucide="file" class="doc-icon"></i>
            <div class="doc-info">
              <span class="doc-name"><?= htmlspecialchars($doc['file_name'] ?? basename($doc['file_path'])) ?></span>
              <span class="doc-meta">oleh <?= htmlspecialchars($doc['uploader']) ?> · <?= date('d M Y', strtotime($doc['created_at'])) ?></span>
            </div>
            <a href="<?= $base . htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">
              <i data-lucide="download"></i>
            </a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /detail-sidebar -->
</div><!-- /detail-grid -->
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
