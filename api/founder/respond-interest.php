<?php
/**
 * API Endpoint: Founder Respond to Investor Interest
 * Allows startup founders to Accept & Connect or Decline investor interests.
 * Enforces ownership verification (IDOR protection), conversation linking/reuse, notifications & email delivery.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

// 1. Authentication Check
if (!auth_check()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'You must be logged in to respond to interests.'
    ]);
    exit;
}

$user = current_user();

// 2. Role Check
if (!$user || ($user['role'] !== 'founder' && $user['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Only startup founders can respond to investor interests.'
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

// 3. CSRF Verification
$token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf($token)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Security token invalid or expired. Please refresh the page.'
    ]);
    exit;
}

// 4. Resolve Interest ID
$rawInterestId = $_POST['interest_id'] ?? '';
$interestId = 0;
if (is_numeric($rawInterestId)) {
    $interestId = (int)$rawInterestId;
} else {
    $interestId = hash_id_decode($rawInterestId);
}

if ($interestId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid interest identifier.'
    ]);
    exit;
}

// 5. Fetch Interest Record
$stmt = $db->prepare("
    SELECT ii.*, c.name as company_name, u.name as investor_name, u.email as investor_email
    FROM investor_interests ii
    JOIN companies c ON ii.company_id = c.id
    JOIN users u ON ii.investor_id = u.id
    WHERE ii.id = ?
");
$stmt->execute([$interestId]);
$interest = $stmt->fetch();

if (!$interest) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Interest record not found.'
    ]);
    exit;
}

// 6. Security Ownership Verification (Prevents IDOR Vulnerability)
if ($user['role'] !== 'admin') {
    $ownStmt = $db->prepare("
        SELECT id FROM company_founders
        WHERE company_id = ? AND user_id = ?
    ");
    $ownStmt->execute([$interest['company_id'], $user['id']]);
    if (!$ownStmt->fetch()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: You are not a registered founder of this company.'
        ]);
        exit;
    }
}

// 7. Verify Status is Currently Pending
if ($interest['status'] !== 'pending') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'This interest has already been ' . $interest['status'] . '.'
    ]);
    exit;
}

// 8. Action Routing: Accept or Decline
$action = strtolower(trim($_POST['action'] ?? ''));

if ($action === 'accept') {
    try {
        // Begin Transaction
        $db->beginTransaction();

        // 8a. Update status to 'accepted'
        $upd = $db->prepare("UPDATE investor_interests SET status = 'accepted', updated_at = NOW() WHERE id = ?");
        $upd->execute([$interestId]);

        // 8b. Check if conversation already exists between founder and investor for this company
        $convStmt = $db->prepare("
            SELECT id FROM conversations
            WHERE company_id = ?
              AND ((user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?))
            LIMIT 1
        ");
        $convStmt->execute([$interest['company_id'], $user['id'], $interest['investor_id'], $interest['investor_id'], $user['id']]);
        $existingConv = $convStmt->fetch();

        $convId = 0;
        if ($existingConv) {
            $convId = (int)$existingConv['id'];
            // Update last_message_at
            $db->prepare("UPDATE conversations SET last_message_at = NOW() WHERE id = ?")->execute([$convId]);
        } else {
            // Create new conversation
            $cIns = $db->prepare("
                INSERT INTO conversations (user_one_id, user_two_id, company_id, last_message_at, created_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $cIns->execute([$user['id'], $interest['investor_id'], $interest['company_id']]);
            $convId = (int)$db->lastInsertId();

            // Insert initial welcome message from Founder
            $initMsg = "Hello {$interest['investor_name']}! I have accepted your expression of interest regarding {$interest['company_name']}. We are excited to connect with you directly on Nexora.";
            $mIns = $db->prepare("
                INSERT INTO messages (conversation_id, sender_user_id, message_text, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $mIns->execute([$convId, $user['id'], $initMsg]);
        }

        $db->commit();

        // 8c. In-App Notification to Investor
        $investorConvUrl = 'investor/messages.php?conv=' . hash_id_encode($convId);
        send_notification(
            (int)$interest['investor_id'],
            'Your Interest Was Accepted',
            'The founder has accepted your interest. You can now continue the conversation on Nexora.',
            'chat',
            $investorConvUrl
        );

        // 8d. Send Email to Investor
        send_interest_accepted_email_to_investor($db, $interestId, $convId);

        // 8e. Audit Log
        log_audit(
            $user['id'],
            'ACCEPT_INVESTOR_INTEREST',
            'investor_interests',
            $interestId,
            "Accepted interest from {$interest['investor_name']} for company {$interest['company_name']}"
        );

        echo json_encode([
            'success' => true,
            'message' => 'Interest accepted! Direct communication is now open.',
            'status' => 'accepted',
            'conversation_id' => hash_id_encode($convId),
            'conversation_url' => url('founder/messages.php?conv=' . hash_id_encode($convId))
        ]);
        exit;

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to accept interest: ' . $e->getMessage()
        ]);
        exit;
    }

} elseif ($action === 'decline') {
    try {
        // 9a. Update status to 'declined'
        $upd = $db->prepare("UPDATE investor_interests SET status = 'declined', updated_at = NOW() WHERE id = ?");
        $upd->execute([$interestId]);

        // 9b. In-App Notification to Investor
        send_notification(
            (int)$interest['investor_id'],
            'Update on Your Interest',
            'The startup has decided not to continue with this interest at this time.',
            'info',
            'investor/discover.php'
        );

        // 9c. Send Polite Email to Investor (No conversation created)
        send_interest_declined_email_to_investor($db, $interestId);

        // 9d. Audit Log
        log_audit(
            $user['id'],
            'DECLINE_INVESTOR_INTEREST',
            'investor_interests',
            $interestId,
            "Declined interest from {$interest['investor_name']} for company {$interest['company_name']}"
        );

        echo json_encode([
            'success' => true,
            'message' => 'Interest has been declined.',
            'status' => 'declined'
        ]);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to decline interest: ' . $e->getMessage()
        ]);
        exit;
    }

} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid action specified. Must be "accept" or "decline".'
    ]);
    exit;
}
