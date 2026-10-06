<?php
// Mark notification as read API
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$user = currentUser();
markAllRead($user['id']);
echo json_encode(['success' => true]);
