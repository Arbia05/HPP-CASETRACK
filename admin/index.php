<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');
header('Location: /hpp-casetrack/admin/dashboard.php');
exit;
