<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('legal_admin');
header('Location: /hpp-casetrack/legal-admin/dashboard.php');
exit;
