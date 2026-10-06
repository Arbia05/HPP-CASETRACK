<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('head_finance');
header('Location: /hpp-casetrack/head-finance/dashboard.php');
exit;
