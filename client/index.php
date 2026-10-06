<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('client');
header('Location: /hpp-casetrack/client/dashboard.php');
exit;
