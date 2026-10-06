<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('lawyer');
header('Location: /hpp-casetrack/lawyer/dashboard.php');
exit;
