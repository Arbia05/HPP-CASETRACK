<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('legal_admin');
$user = currentUser();
$db   = getDB();

$id   = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("
  SELECT c.*, cl.name AS client_name, cl.email AS client_email, cl.phone AS client_phone,
         pj.name AS pj_name
  FROM cases c
  JOIN users cl ON cl.id = c.client_id
  LEFT JOIN users pj ON pj.id = c.assigned_pj_id
  WHERE c.id=?
");
$stmt->execute([$id]);
$case = $stmt->fetch();
if (!$case) redirect('/hpp-casetrack/legal-admin/cases.php');

$success = $error = '';

// Assign PJ
if (isPost() && ($_POST['action'] ?? '') === 'assign_pj') {
    $pjId = (int)$_POST['pj_id'];
    $db->prepare("UPDATE cases SET assigned_pj_id=? WHERE id=?")->execute([$pjId, $id]);
    $success = 'Pengacara PJ berhasil ditugaskan.';
}

// Update status
if (isPost() && ($_POST['action'] ?? '') === 'update_status') {
    $newStatus = sanitize($_POST['new_status']);
    $db->prepare("UPDATE cases SET status=? WHERE id=?")->execute([$newStatus, $id]);
    $success = 'Status kasus diperbarui.';
    $case['status'] = $newStatus;
}

$teamStmt = $db->prepare("SELECT ct.*, u.name FROM case_team ct JOIN users u ON u.id=ct.lawyer_id WHERE ct.case_id=?");
$teamStmt->execute([$id]);
$team = $teamStmt->fetchAll();

$actStmt = $db->prepare("SELECT ca.*, u.name AS actor_name FROM case_activities ca JOIN users u ON u.id=ca.user_id WHERE ca.case_id=? ORDER BY ca.created_at DESC LIMIT 15");
$actStmt->execute([$id]);
$activities = $actStmt->fetchAll();

$docStmt = $db->prepare("SELECT cd.*, u.name AS uploader FROM case_documents cd JOIN users u ON u.id=cd.uploaded_by WHERE cd.case_id=?");
$docStmt->execute([$id]);
$documents = $docStmt->fetchAll();

$invoiceStmt = $db->prepare("SELECT * FROM invoices WHERE case_id=? ORDER BY created_at DESC LIMIT 1");
$invoiceStmt->execute([$id]);
$invoice = $invoiceStmt->fetch();

$pjStmt = $db->prepare("SELECT id, name FROM users WHERE role='pengacara_pj' AND is_active=1");
$pjStmt->execute();
$availPJs = $pjStmt->fetchAll();

$pageTitle = 'Detail Kasus';
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
    <p class="page-subtitle"><?= htmlspecialchars($case['case_type']) ?> — <?= htmlspecialchars($case['client_name']) ?></p>
  </div>
  <span class="badge badge-<?= getCaseStatusColor($case['status']) ?> badge-lg"><?= ucfirst($case['status']) ?></span>
</div>

<div class="detail-grid">
  <div class="detail-main">
    <!-- Case Info -->
    <div class="card mb-4">
      <div class="card-header"><h2 class="card-title">Informasi Kasus</h2></div>
      <div class="card-body">
        <div class="info-grid-2">
          <div class="info-item"><span class="info-label">No. Kasus</span><span class="info-value fw-600 text-primary"><?= htmlspecialchars($case['case_number']) ?></span></div>
          <div class="info-item"><span class="info-label">Tipe</span><span class="info-value"><?= htmlspecialchars($case['case_type']) ?></span></div>
          <div class="info-item"><span class="info-label">Pengacara PJ</span><span class="info-value"><?= htmlspecialchars($case['pj_name'] ?? '—') ?></span></div>
          <div class="info-item"><span class="info-label">Dibuat</span><span class="info-value"><?= date('d M Y', strtotime($case['created_at'])) ?></span></div>
        </div>
        <?php if ($case['description']): ?>
        <div class="mt-3"><span class="info-label">Deskripsi</span><p class="mt-1"><?= nl2br(htmlspecialchars($case['description'])) ?></p></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Assign PJ & Status -->
    <div class="card mb-4">
      <div class="card-header"><h2 class="card-title"><i data-lucide="settings" class="icon-sm"></i> Pengelolaan Kasus</h2></div>
      <div class="card-body">
        <div class="form-row mb-4">
          <div class="form-group">
            <label class="form-label">Tugaskan Pengacara PJ</label>
            <form method="POST" class="input-btn-group">
              <input type="hidden" name="action" value="assign_pj">
              <select name="pj_id" class="form-select">
                <option value="">— Pilih PJ —</option>
                <?php foreach ($availPJs as $pj): ?>
                <option value="<?= $pj['id'] ?>" <?= ($case['assigned_pj_id']??0)==$pj['id']?'selected':'' ?>><?= htmlspecialchars($pj['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-primary">Tugaskan</button>
            </form>
          </div>
          <div class="form-group">
            <label class="form-label">Update Status Kasus</label>
            <form method="POST" class="input-btn-group">
              <input type="hidden" name="action" value="update_status">
              <select name="new_status" class="form-select">
                <option value="pending"   <?= $case['status']==='pending'  ?'selected':'' ?>>Pending</option>
                <option value="active"    <?= $case['status']==='active'   ?'selected':'' ?>>Aktif</option>
                <option value="closed"    <?= $case['status']==='closed'   ?'selected':'' ?>>Selesai</option>
                <option value="cancelled" <?= $case['status']==='cancelled'?'selected':'' ?>>Dibatalkan</option>
              </select>
              <button type="submit" class="btn btn-secondary">Update</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Team -->
    <div class="card mb-4">
      <div class="card-header"><h2 class="card-title"><i data-lucide="users" class="icon-sm"></i> Tim (<?= count($team) ?>)</h2></div>
      <div class="card-body">
        <?php if (empty($team)): ?>
        <p class="text-muted text-sm">Belum ada tim</p>
        <?php else: ?>
        <div class="team-list">
          <?php foreach ($team as $m): ?>
          <div class="team-member">
            <div class="avatar-sm"><?= strtoupper(substr($m['name'],0,1)) ?></div>
            <span><?= htmlspecialchars($m['name']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Timeline -->
    <div class="card">
      <div class="card-header"><h2 class="card-title"><i data-lucide="activity" class="icon-sm"></i> Riwayat Aktivitas</h2></div>
      <div class="card-body p-0">
        <?php if (empty($activities)): ?>
        <p class="text-muted p-4 text-sm">Belum ada aktivitas</p>
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
              <div class="timeline-location"><i data-lucide="map-pin" class="icon-xs"></i> <?= htmlspecialchars($act['location']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="detail-sidebar">
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
    <div class="card mb-4">
      <div class="card-header"><h2 class="card-title"><i data-lucide="file-text" class="icon-sm"></i> Dokumen (<?= count($documents) ?>)</h2></div>
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
              <span class="doc-meta"><?= date('d M Y', strtotime($doc['created_at'])) ?></span>
            </div>
            <a href="<?= $base . htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline"><i data-lucide="download"></i></a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($invoice): ?>
    <div class="card">
      <div class="card-header"><h2 class="card-title"><i data-lucide="receipt" class="icon-sm"></i> Invoice</h2></div>
      <div class="card-body">
        <div class="info-list">
          <div class="info-item"><span class="info-label">No.</span><span class="info-value"><?= htmlspecialchars($invoice['invoice_number']) ?></span></div>
          <div class="info-item"><span class="info-label">Jumlah</span><span class="info-value fw-600 text-primary">Rp <?= number_format($invoice['amount'],0,',','.') ?></span></div>
          <div class="info-item"><span class="info-label">Status</span>
            <?php $im=['unpaid'=>'badge-danger','verification'=>'badge-warning','paid'=>'badge-success']; ?>
            <span class="badge <?= $im[$invoice['status']] ?? '' ?>"><?= ucfirst($invoice['status']) ?></span>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
