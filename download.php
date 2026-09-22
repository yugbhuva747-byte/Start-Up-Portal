<?php
/**
 * Secure Document Download Controller
 * Allows Admin, Founders, and Investors to securely download compliance & pitch documents
 */
require_once __DIR__ . '/config.php';

$user = require_auth();
$db = get_db();

$docId = (int)($_GET['id'] ?? 0);
$docType = trim($_GET['type'] ?? 'verification'); // 'verification' or 'company'

if ($docId <= 0 || !$db) {
    http_response_code(400);
    die("Invalid document identifier.");
}

$fileRecord = null;
$downloadFilename = '';
$relativePath = '';

if ($docType === 'company') {
    $stmt = $db->prepare("SELECT cd.*, c.name as company_name FROM company_documents cd JOIN companies c ON cd.company_id = c.id WHERE cd.id = ?");
    $stmt->execute([$docId]);
    $fileRecord = $stmt->fetch();

    if (!$fileRecord) {
        http_response_code(404);
        die("Company document not found.");
    }

    // Access authorization check
    if ($user['role'] !== 'admin') {
        if ($user['role'] === 'founder') {
            // Check if founder owns this company
            $ownCheck = $db->prepare("SELECT 1 FROM company_founders WHERE company_id = ? AND user_id = ?");
            $ownCheck->execute([$fileRecord['company_id'], $user['id']]);
            if (!$ownCheck->fetch()) {
                http_response_code(403);
                die("Unauthorized: You do not have permission to download this company document.");
            }
        } elseif ($user['role'] === 'investor') {
            // Verified or registered investor access
            if ($fileRecord['access_level'] === 'request_only' && !$user['is_verified']) {
                http_response_code(403);
                die("Access restricted: Verified investor accreditation required.");
            }
        }
    }

    $relativePath = $fileRecord['file_path'];
    $ext = pathinfo($relativePath, PATHINFO_EXTENSION);
    $cleanTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fileRecord['title'] ?? $fileRecord['document_type']);
    $downloadFilename = ($cleanTitle ?: 'document') . ($ext ? '.' . $ext : '');

} else {
    // Verification document
    $stmt = $db->prepare("SELECT vd.*, u.name as user_name FROM verification_documents vd JOIN users u ON vd.user_id = u.id WHERE vd.id = ?");
    $stmt->execute([$docId]);
    $fileRecord = $stmt->fetch();

    if (!$fileRecord) {
        http_response_code(404);
        die("Verification document not found.");
    }

    // Authorization check
    if ($user['role'] !== 'admin' && (int)$fileRecord['user_id'] !== (int)$user['id']) {
        http_response_code(403);
        die("Unauthorized: You do not have permission to view or download this document.");
    }

    $relativePath = $fileRecord['file_path'];
    $ext = pathinfo($relativePath, PATHINFO_EXTENSION);
    $cleanDocName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fileRecord['document_type'] . '_' . ($fileRecord['user_name'] ?? 'user'));
    $downloadFilename = $cleanDocName . ($ext ? '.' . $ext : '');
}

$fullPath = ROOT_PATH . '/' . ltrim($relativePath, '/\\');

if (!file_exists($fullPath) || is_dir($fullPath)) {
    http_response_code(404);
    die("The requested document file could not be found on the server storage.");
}

// Log download audit
log_audit($user['id'], 'DOWNLOAD_DOCUMENT', $docType === 'company' ? 'company_documents' : 'verification_documents', $docId, "Downloaded document: " . basename($fullPath));

// Determine content type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fullPath) ?: 'application/octet-stream';
finfo_close($finfo);

// Force download headers
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadFilename) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($fullPath));

// Clear output buffer if any
if (ob_get_level()) {
    ob_end_clean();
}

readfile($fullPath);
exit;
