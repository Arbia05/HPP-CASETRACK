<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('ceo');
header('Location: /hpp-casetrack/ceo/dashboard.php');
exit;
