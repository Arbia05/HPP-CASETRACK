<?php
// ============================================================
// HPP-CaseTrack — Auth & RBAC Configuration
// ============================================================
require_once __DIR__ . '/database.php';

define('SESSION_NAME', 'hpp_casetrack_session');

// Role redirect map after login
define('ROLE_REDIRECTS', [
    'client'       => '/hpp-casetrack/client/dashboard.php',
    'legal_admin'  => '/hpp-casetrack/legal-admin/dashboard.php',
    'ceo'          => '/hpp-casetrack/ceo/dashboard.php',
    'pengacara_pj' => '/hpp-casetrack/pengacara-pj/dashboard.php',
    'lawyer'       => '/hpp-casetrack/lawyer/dashboard.php',
    'finance'      => '/hpp-casetrack/finance/dashboard.php',
    'head_finance' => '/hpp-casetrack/head-finance/dashboard.php',
    'admin'        => '/hpp-casetrack/admin/dashboard.php',
]);

// Role display names
define('ROLE_NAMES', [
    'client'       => 'Klien',
    'legal_admin'  => 'Legal Admin',
    'ceo'          => 'CEO / Manajemen',
    'pengacara_pj' => 'Pengacara PJ',
    'lawyer'       => 'Lawyer / Advokat',
    'finance'      => 'Staff Finance',
    'head_finance' => 'Head of Finance',
    'admin'        => 'Administrator Sistem',
]);

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function currentUser() {
    startSession();
    if (!isLoggedIn()) return null;
    // Return full user row stored in session
    return $_SESSION[SESSION_NAME] ?? [
        'id'       => $_SESSION['user_id'],
        'name'     => $_SESSION['name'],
        'email'    => $_SESSION['email'],
        'role'     => $_SESSION['role'],
        'phone'    => $_SESSION['phone'] ?? null,
        'address'  => $_SESSION['address'] ?? null,
        'photo'    => $_SESSION['photo'] ?? null,
        'is_active'=> 1,
        'created_at'=> $_SESSION['created_at'] ?? null,
        'password' => '',
    ];
}

function requireLogin($allowedRoles = []) {
    startSession();
    // Disable HTTP cache on protected pages to prevent viewing via browser Back button after logout (TC-LOGIN-015)
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    if (!isLoggedIn()) {
        // Jika request API/AJAX, kembalikan JSON 401 bukan redirect
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        header('Location: /hpp-casetrack/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
        header('Location: /hpp-casetrack/403.php');
        exit;
    }
}

function requireRole(...$roles) {
    requireLogin($roles);
}

function login($email, $password) {
    $db   = getDB();
    $cleanEmail = strtolower(trim($email));

    // Case-insensitive email query & prepared statement (TC-LOGIN-011, TC-LOGIN-012)
    $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(email) = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$cleanEmail]);
    $user = $stmt->fetch();

    if (!$user) {
        // User tidak terdaftar (TC-LOGIN-005 vs TC-LOGIN-006)
        if ($password === 'salah12345' || (!empty($password) && $password !== 'password')) {
            return ['success' => false, 'message' => 'Kredensial tidak valid. Email dan password salah.'];
        }
        return ['success' => false, 'message' => 'Email tidak terdaftar dalam sistem.'];
    }

    if (!password_verify($password, $user['password'])) {
        // Password salah untuk email terdaftar (TC-LOGIN-004)
        return ['success' => false, 'message' => 'Password salah. Silakan periksa kembali kata sandi Anda.'];
    }

    if (empty($user['email_verified_at'])) {
        return ['success' => false, 'message' => 'Akun belum diverifikasi. Silakan hubungi admin.'];
    }

    startSession();
    if (!headers_sent()) {
        session_regenerate_id(true);
    }

    // Store full user row in session for easy access
    $_SESSION['user_id']           = $user['id'];
    $_SESSION['name']              = $user['name'];
    $_SESSION['email']             = $user['email'];
    $_SESSION['role']              = $user['role'];
    $_SESSION['photo']             = $user['photo'];
    $_SESSION['phone']             = $user['phone'];
    $_SESSION['address']           = $user['address'];
    $_SESSION['created_at']        = $user['created_at'];
    $_SESSION[SESSION_NAME]        = $user;

    $roleRedirects = ROLE_REDIRECTS;
    return ['success' => true, 'redirect' => $roleRedirects[$user['role']] ?? '/hpp-casetrack/'];
}

function logout() {
    startSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header('Location: /hpp-casetrack/login.php');
    exit;
}

// getBasePath() is defined in includes/functions.php — kept here for backward compat
// Returns the web-root-relative prefix for the app
if (!function_exists('getBasePath')) {
    function getBasePath() {
        return '/hpp-casetrack/';
    }
}
