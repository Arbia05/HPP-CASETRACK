<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('finance');
header('Location: /hpp-casetrack/finance/dashboard.php');
exit;
