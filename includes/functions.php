<?php
// ============================================================
// HPP-CaseTrack — Global Functions & Helpers
// ============================================================
require_once __DIR__ . '/../config/database.php';

// ---- Notification helpers ----
function createNotification($userId, $title, $message, $type = 'system', $refId = null, $refType = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id, reference_type) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$userId, $title, $message, $type, $refId, $refType]);
}

function getUnreadCount($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function getNotifications($userId, $limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function markAllRead($userId) {
    $db = getDB();
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
}

// ---- Case reference generator ----
function generateCaseRef() {
    $db = getDB();
    $year = date('Y');
    $stmt = $db->query("SELECT COUNT(*) FROM cases WHERE YEAR(created_at) = $year");
    $count = $stmt->fetchColumn() + 1;
    return 'HPP-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// ---- Invoice number generator ----
function generateInvoiceNumber() {
    $db = getDB();
    $month = date('Ym');
    $stmt = $db->query("SELECT COUNT(*) FROM invoices WHERE DATE_FORMAT(created_at, '%Y%m') = '$month'");
    $count = $stmt->fetchColumn() + 1;
    return 'INV-' . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// ---- File upload handler ----
function handleUpload($file, $folder = 'documents', $allowedTypes = ['pdf','jpg','jpeg','png','docx']) {
    $uploadDir = __DIR__ . '/../uploads/' . $folder . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan.'];
    }
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB max
        return ['success' => false, 'message' => 'Ukuran file maksimal 10MB.'];
    }

    $newName = uniqid('hpp_', true) . '.' . $ext;
    $destPath = $uploadDir . $newName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'message' => 'Gagal mengupload file.'];
    }
    return ['success' => true, 'path' => 'uploads/' . $folder . '/' . $newName, 'name' => $file['name']];
}

// ---- Format helpers ----
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatDate($dateStr, $format = 'd M Y') {
    if (!$dateStr) return '-';
    return date($format, strtotime($dateStr));
}

function timeAgo($dateStr) {
    $diff = time() - strtotime($dateStr);
    if ($diff < 60)    return $diff . ' detik lalu';
    if ($diff < 3600)  return floor($diff/60) . ' menit lalu';
    if ($diff < 86400) return floor($diff/3600) . ' jam lalu';
    return floor($diff/86400) . ' hari lalu';
}

function statusBadge($status) {
    $map = [
        'new'                  => ['Baru', 'bg-blue-100 text-blue-800'],
        'pending_completion'   => ['Menunggu Kelengkapan', 'bg-yellow-100 text-yellow-800'],
        'verified'             => ['Diverifikasi', 'bg-green-100 text-green-800'],
        'rejected'             => ['Ditolak', 'bg-red-100 text-red-800'],
        'active'               => ['Aktif', 'bg-green-100 text-green-800'],
        'on_hold'              => ['Ditangguhkan', 'bg-yellow-100 text-yellow-800'],
        'closed'               => ['Selesai', 'bg-gray-100 text-gray-800'],
        'won'                  => ['Menang', 'bg-emerald-100 text-emerald-800'],
        'lost'                 => ['Kalah', 'bg-red-100 text-red-800'],
        'unpaid'               => ['Belum Bayar', 'bg-red-100 text-red-800'],
        'pending_verification' => ['Menunggu Verifikasi', 'bg-yellow-100 text-yellow-800'],
        'paid'                 => ['Lunas', 'bg-green-100 text-green-800'],
        'overdue'              => ['Jatuh Tempo', 'bg-red-100 text-red-800'],
        'pending'              => ['Menunggu', 'bg-yellow-100 text-yellow-800'],
        'approved'             => ['Disetujui', 'bg-green-100 text-green-800'],
        'draft'                => ['Draft', 'bg-gray-100 text-gray-800'],
        'pending_ceo'          => ['Menunggu CEO', 'bg-purple-100 text-purple-800'],
    ];
    $s = $map[$status] ?? [$status, 'bg-gray-100 text-gray-800'];
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ' . $s[1] . '">' . $s[0] . '</span>';
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function isPost() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function isGet()  { return $_SERVER['REQUEST_METHOD'] === 'GET'; }

// ---- Base path helper ----
if (!function_exists('getBasePath')) {
    function getBasePath() {
        // Returns '/' for root-level pages; adjust if deployed in subdirectory
        return '/hpp-casetrack/';
    }
}

// ---- Status color helpers ----
function getCaseStatusColor($status) {
    $map = [
        'active'    => 'success',
        'pending'   => 'warning',
        'closed'    => 'secondary',
        'cancelled' => 'danger',
        'on_hold'   => 'warning',
        'won'       => 'success',
        'lost'      => 'danger',
    ];
    return $map[$status] ?? 'secondary';
}

function getComplaintStatusColor($status) {
    $map = [
        'new'                 => 'primary',
        'pending_completion'  => 'warning',
        'verified'            => 'success',
        'rejected'            => 'danger',
        'revision_requested'  => 'warning',
    ];
    return $map[$status] ?? 'secondary';
}

function getRoleColor($role) {
    $map = [
        'client'        => 'primary',
        'legal_admin'   => 'info',
        'ceo'           => 'danger',
        'pengacara_pj'  => 'warning',
        'lawyer'        => 'success',
        'finance'       => 'secondary',
        'head_finance'  => 'secondary',
        'admin'         => 'dark',
    ];
    return $map[$role] ?? 'secondary';
}

// ---- Current URL check ----
function currentUrl() {
    return $_SERVER['SCRIPT_NAME'];
}

function isActivePage($path) {
    return str_contains(currentUrl(), $path);
}
