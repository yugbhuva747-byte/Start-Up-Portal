<?php
/**
 * API: Real-Time Chat Polling & Send Endpoint
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

if ($action === 'fetch') {
    $convHash = $_GET['conv'] ?? '';
    $convId = hash_id_decode($convHash);

    if ($convId > 0 && $db) {
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
    }
} elseif ($action === 'send') {
    $convHash = $_POST['conv'] ?? '';
    $convId = hash_id_decode($convHash);
    $text = trim($_POST['message_text'] ?? '');

    if ($convId > 0 && !empty($text) && $db) {
        $ins = $db->prepare("
            INSERT INTO messages (conversation_id, sender_user_id, message_text, is_read, created_at)
            VALUES (?, ?, ?, 0, NOW())
        ");
        $ins->execute([$convId, $user['id'], $text]);
        $msgId = $db->lastInsertId();

        $db->prepare("UPDATE conversations SET last_message_at = NOW() WHERE id = ?")->execute([$convId]);

        echo json_encode(['success' => true, 'message_id' => $msgId]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
