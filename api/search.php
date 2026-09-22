<?php
/**
 * API: Instant Live Search Suggestions
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$query = trim($_GET['q'] ?? '');
$db = get_db();

if (strlen($query) >= 2 && $db) {
    $stmt = $db->prepare("
        SELECT id, name, industry, stage, pitch, logo_url
        FROM companies
        WHERE verified_status = 'verified' AND (name LIKE ? OR pitch LIKE ? OR industry LIKE ?)
        LIMIT 6
    ");
    $term = "%{$query}%";
    $stmt->execute([$term, $term, $term]);
    $results = $stmt->fetchAll();

    foreach ($results as &$r) {
        $r['hash_id'] = hash_id_encode($r['id']);
    }

    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}

echo json_encode(['success' => true, 'results' => []]);
exit;
