<?php
// api/upload.php — File upload API endpoint
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_FILES['file'])) {
    jsonResponse(['success' => false, 'message' => 'Tidak ada file yang diupload.'], 400);
}

$file    = $_FILES['file'];
$folder  = sanitize($_POST['folder'] ?? 'documents');
$caseId  = (int)($_POST['case_id'] ?? 0);
$type    = sanitize($_POST['type'] ?? 'document');

// Validate folder whitelist
$allowed = ['documents', 'payments', 'evidence', 'avatars'];
if (!in_array($folder, $allowed)) {
    jsonResponse(['success' => false, 'message' => 'Folder tidak valid.'], 400);
}

$result = handleUpload($file, $folder);
if (!$result['success']) {
    jsonResponse(['success' => false, 'message' => $result['message']], 422);
}

// Save to database if case_id given
if ($caseId) {
    $user = currentUser();
    $db   = getDB();
    $db->prepare(
        "INSERT INTO case_documents (case_id, uploaded_by, file_path, file_name, file_type) VALUES (?,?,?,?,?)"
    )->execute([$caseId, $user['id'], $result['path'], $result['name'], $type]);
}

jsonResponse(['success' => true, 'path' => $result['path'], 'name' => $result['name']]);
