<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/hpp-casetrack/admin/users.php');

// Activate: set is_active=1 and stamp email_verified_at if not already set
$db->prepare("UPDATE users SET is_active=1, email_verified_at=COALESCE(email_verified_at, NOW()) WHERE id=?")
   ->execute([$id]);

redirect('/hpp-casetrack/admin/users.php?msg=activated');
