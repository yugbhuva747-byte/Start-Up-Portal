<?php
/**
 * API: Real-Time Chat Polling & Send Endpoint
 * Enforces participant authorization (IDOR protection) and dispatches in-app notifications
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (!auth_check()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user = current_user();
$db = get_db();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'fetch');

if (!$db) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$convHash = ($action === 'send') ? ($_POST['conv'] ?? '') : ($_GET['conv'] ?? '');
$convId = hash_id_decode($convHash);

if ($convId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid conversation identifier']);
    exit;
}

// 1. Participant Authorization Check (Prevents IDOR Vulnerability)
$convStmt = $db->prepare("SELECT * FROM conversations WHERE id = ?");
$convStmt->execute([$convId]);
$conv = $convStmt->fetch();

if (!$conv) {
    echo json_encode(['success' => false, 'error' => 'Conversation not found']);
    exit;
}

$isParticipant = ((int)$conv['user_one_id'] === (int)$user['id'] || (int)$conv['user_two_id'] === (int)$user['id'] || $user['role'] === 'admin');
if (!$isParticipant) {
    echo json_encode(['success' => false, 'error' => 'Access denied: You are not a participant in this conversation']);
    exit;
}

if ($action === 'fetch') {
    // Mark unread messages sent by the other party as read
    $db->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE conversation_id = ? AND sender_user_id != ? AND is_read = 0
    ")->execute([$convId, $user['id']]);

    // Fetch conversation thread
    $stmt = $db->prepare("
        SELECT m.*, u.name as sender_name, u.avatar_url as sender_avatar
        FROM messages m
        JOIN users u ON m.sender_user_id = u.id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$convId]);
    $messages = $stmt->fetchAll();

    echo json_encode(['success' => true, 'messages' => $messages, 'current_user_id' => $user['id']]);
    exit;

} elseif ($action === 'send') {
    $text = trim($_POST['message_text'] ?? '');

    if (empty($text)) {
        echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
        exit;
    }

    // Insert new message
    $ins = $db->prepare("
        INSERT INTO messages (conversation_id, sender_user_id, message_text, is_read, created_at)
        VALUES (?, ?, ?, 0, NOW())
    ");
    $ins->execute([$convId, $user['id'], $text]);
    $msgId = $db->lastInsertId();

    // Update conversation timestamp
    $db->prepare("UPDATE conversations SET last_message_at = NOW() WHERE id = ?")->execute([$convId]);

    // Identify recipient and trigger notification
    $recipientId = ((int)$conv['user_one_id'] === (int)$user['id']) ? (int)$conv['user_two_id'] : (int)$conv['user_one_id'];
    
    // Determine proper chat destination path for the recipient
    $rStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $rStmt->execute([$recipientId]);
    $recipientRole = $rStmt->fetchColumn() ?: 'investor';
    $targetPath = ($recipientRole === 'founder') ? 'founder/messages.php?conv=' . encode_id($convId) : 'investor/messages.php?conv=' . encode_id($convId);

    $snippet = mb_strlen($text) > 80 ? mb_substr($text, 0, 80) . '...' : $text;
    send_notification(
        $recipientId,
        "New message from {$user['name']}",
        $snippet,
        'info',
        $targetPath
    );

    echo json_encode(['success' => true, 'message_id' => $msgId]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
exit;
