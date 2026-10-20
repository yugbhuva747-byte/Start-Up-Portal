<?php
/**
 * API Endpoint: Investor Express Interest
 * Allows verified investors to express interest in a startup/opportunity.
 * Validates role, duplicates, ownership, message length, and triggers founder notifications & email.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

// 1. Authentication Check
if (!auth_check()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'You must be logged in to express interest.'
    ]);
    exit;
}

$user = current_user();

// 2. Role Verification
if (!$user || $user['role'] !== 'investor') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Only registered investors can express interest in startups.'
    ]);
    exit;
}

$db = get_db();
if (!$db) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed. Please try again later.'
    ]);
    exit;
}

// 3. CSRF Token Verification
$token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf($token)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Security token invalid or expired. Please refresh the page.'
    ]);
    exit;
}

// 4. Resolve & Validate Company ID
$rawCompanyId = $_POST['company_id'] ?? '';
$companyId = 0;
if (is_numeric($rawCompanyId)) {
    $companyId = (int)$rawCompanyId;
} else {
    $companyId = hash_id_decode($rawCompanyId);
}

if ($companyId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid startup identifier.'
    ]);
    exit;
}

// Check startup existence
$cStmt = $db->prepare("SELECT id, name FROM companies WHERE id = ?");
$cStmt->execute([$companyId]);
$company = $cStmt->fetch();

if (!$company) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Startup not found.'
    ]);
    exit;
}

// 5. Ownership Check: Investor cannot express interest in their own company
$ownStmt = $db->prepare("SELECT id FROM company_founders WHERE company_id = ? AND user_id = ?");
$ownStmt->execute([$companyId, $user['id']]);
if ($ownStmt->fetch()) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'You cannot express interest in your own company.'
    ]);
    exit;
}

// 6. Duplicate Check: Same investor cannot submit interest twice
$dupStmt = $db->prepare("SELECT id, status FROM investor_interests WHERE investor_id = ? AND company_id = ?");
$dupStmt->execute([$user['id'], $companyId]);
$existing = $dupStmt->fetch();

if ($existing) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'error' => 'You have already expressed interest in this startup.',
        'current_status' => $existing['status']
    ]);
    exit;
}

// 7. Message Validation
$message = trim($_POST['message'] ?? '');
if (mb_strlen($message) < 10) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Please provide a brief message explaining why you are interested (minimum 10 characters).'
    ]);
    exit;
}

if (mb_strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Message is too long (maximum 2,000 characters).'
    ]);
    exit;
}

// 8. Insert Record into investor_interests
try {
    $ins = $db->prepare("
        INSERT INTO investor_interests (investor_id, company_id, message, status, created_at, updated_at)
        VALUES (?, ?, ?, 'pending', NOW(), NOW())
    ");
    $ins->execute([$user['id'], $companyId, $message]);
    $interestId = (int)$db->lastInsertId();

    // 9. In-App Notification to Startup Founder(s)
    $fStmt = $db->prepare("
        SELECT u.id, u.name, u.email
        FROM company_founders cf
        JOIN users u ON cf.user_id = u.id
        WHERE cf.company_id = ?
    ");
    $fStmt->execute([$companyId]);
    $founders = $fStmt->fetchAll();

    if (empty($founders)) {
        // Fallback: notify platform founder if no specific founder attached
        $fStmt2 = $db->prepare("SELECT id, name, email FROM users WHERE role = 'founder' LIMIT 1");
        $fStmt2->execute();
        $founders = $fStmt2->fetchAll();
    }

    foreach ($founders as $f) {
        send_notification(
            (int)$f['id'],
            'New Investor Interest',
            "{$user['name']} has expressed interest in {$company['name']}.",
            'info',
            'founder/investor_interests.php'
        );
    }

    // 10. Send Founder Email via existing mailer system
    $mailResult = send_new_interest_email_to_founder($db, $interestId);

    // 11. Security Audit Log
    log_audit($user['id'], 'EXPRESS_INTEREST', 'investor_interests', $interestId, "Expressed interest in company ID: {$companyId} ({$company['name']})");

    echo json_encode([
        'success' => true,
        'message' => 'Interest sent successfully. The founder has been notified.',
        'interest_id' => $interestId,
        'status' => 'pending',
        'email_status' => $mailResult['success'] ?? false
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to record interest. Please try again: ' . $e->getMessage()
    ]);
    exit;
}
