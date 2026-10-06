<?php
// Root-level profile page — accessible by all roles
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
require_once __DIR__ . '/includes/profile.php';
