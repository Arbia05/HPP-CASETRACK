<?php
// client/profile.php — redirect ke shared profile
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');
require_once __DIR__ . '/../includes/profile.php';
