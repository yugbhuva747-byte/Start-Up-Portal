<?php
/**
 * Cron / Maintenance API Endpoint: 30-Day Photo Retention Cleanup
 * Automatically finds and permanently deletes archived user photos that have passed their 30-day retention window.
 * 
 * Usage:
 *   CLI: php api/cleanup_photos.php
 *   Web/Cron: GET /api/cleanup_photos.php?key=YOUR_CRON_KEY (or logged in admin)
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = current_user();
$isAdmin = $currentUser && ($currentUser['role'] ?? '') === 'admin';
$isCli = (php_sapi_name() === 'cli');

// Basic security check for Web invocations
$cronSecret = defined('CRON_SECRET_KEY') ? CRON_SECRET_KEY : 'startup_portal_retention_2026';
$providedKey = $_GET['key'] ?? '';

if (!$isCli && !$isAdmin && $providedKey !== $cronSecret) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Provide a valid cron key or authenticate as administrator.'
    ], JSON_PRETTY_PRINT);
    exit;
}

$db = get_db();
if (!$db) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection unavailable.'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Run the 30-day expired photo cleanup
$result = cleanup_expired_archived_photos($db);

// Count pending photos in retention queue
$pendingStmt = $db->query("
    SELECT COUNT(*) as pending_count,
           MIN(scheduled_delete_at) as next_delete_at
    FROM archived_user_photos 
    WHERE status = 'pending'
");
$stats = $pendingStmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => $result['success'] ?? true,
    'message' => 'Retention cleanup completed successfully.',
    'deleted_count' => $result['deleted_count'] ?? 0,
    'errors' => $result['errors'] ?? [],
    'pending_in_queue' => (int)($stats['pending_count'] ?? 0),
    'next_scheduled_delete_at' => $stats['next_delete_at'] ?? null,
    'executed_at' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
