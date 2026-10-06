<?php
// NOTE: auth and functions must already be loaded by the caller.
$user = currentUser();
$db   = getDB();

// Mark all as read
if (isPost() && ($_POST['action'] ?? '') === 'mark_all') {
    markAllRead($user['id']);
}

// Mark single
if (isPost() && ($_POST['action'] ?? '') === 'mark_one') {
    $nid = (int)$_POST['notif_id'];
    $db->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$nid, $user['id']]);
}

$notifs = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
$notifs->execute([$user['id']]);
$notifications = $notifs->fetchAll();

$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));

$pageTitle = 'Notifikasi';
$base      = getBasePath();
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>
<div id="page-content">
<div class="page-header">
  <div>
    <h1 class="page-title">Notifikasi</h1>
    <p class="page-subtitle"><?= $unreadCount ?> notifikasi belum dibaca</p>
  </div>
  <?php if ($unreadCount > 0): ?>
  <form method="POST">
    <input type="hidden" name="action" value="mark_all">
    <button type="submit" class="btn btn-outline">
      <i data-lucide="check-check" class="btn-icon"></i> Tandai Semua Dibaca
    </button>
  </form>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-body p-0">
    <?php if (empty($notifications)): ?>
    <div class="empty-state p-8">
      <i data-lucide="bell-off" class="empty-icon"></i>
      <p>Tidak ada notifikasi</p>
    </div>
    <?php else: ?>
    <div class="notif-list">
      <?php foreach ($notifications as $n): ?>
      <div class="notif-item <?= !$n['is_read'] ? 'notif-unread' : '' ?>">
        <div class="notif-icon">
          <?php
          $iconMap = [
            'complaint' => 'file-text',
            'case'      => 'briefcase',
            'invoice'   => 'receipt',
            'activity'  => 'activity',
            'system'    => 'bell',
          ];
          $icon = $iconMap[$n['type'] ?? 'system'] ?? 'bell';
          ?>
          <i data-lucide="<?= $icon ?>"></i>
        </div>
        <div class="notif-content">
          <div class="notif-title"><?= htmlspecialchars($n['title'] ?? 'Notifikasi') ?></div>
          <div class="notif-message"><?= htmlspecialchars($n['message']) ?></div>
          <div class="notif-time"><i data-lucide="clock" class="icon-xs"></i> <?= date('d M Y H:i', strtotime($n['created_at'])) ?></div>
        </div>
        <div class="notif-actions">
          <?php if (!$n['is_read']): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="mark_one">
            <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline" title="Tandai dibaca">
              <i data-lucide="check"></i>
            </button>
          </form>
          <?php endif; ?>
          <?php if ($n['link']): ?>
          <a href="<?= $base . htmlspecialchars($n['link']) ?>" class="btn btn-sm btn-primary">Lihat</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
