<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
$user = currentUser();

header('Content-Type: application/json');
$db = getDB();
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
$stmt->execute([$user['id']]);
$notifs = $stmt->fetchAll();

foreach ($notifs as &$n) {
    $n['time_ago'] = timeAgo($n['created_at']);
}

echo json_encode(['success' => true, 'data' => $notifs]);
