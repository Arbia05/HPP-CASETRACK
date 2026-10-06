<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('pengacara_pj');
header('Location: /hpp-casetrack/pengacara-pj/dashboard.php');
exit;
