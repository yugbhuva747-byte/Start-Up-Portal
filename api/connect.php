<?php
/**
 * API: LinkedIn-Style Connect & Contact Request Handler
 * Supports both AJAX/JSON and traditional Form Submissions
 * Operations: send_request, accept, decline, withdraw, remove, get_status
 */
require_once __DIR__ . '/../config.php';

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
       || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
       || (isset($_GET['format']) && $_GET['format'] === 'json');

if (!auth_check()) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Please sign in to connect with members.']);
        exit;
    }
    set_flash('error', 'Please sign in to connect.');
    header('Location: ' . url('auth/login.php'));
    exit;
}

$currentUser = current_user();
$db = get_db();

if (!$db) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
        exit;
    }
    set_flash('error', 'Database connection failed.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('/')));
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

// Verify CSRF on state changes
if ($method === 'POST') {
    if (!verify_csrf($csrfToken)) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh the page.']);
            exit;
        }
        set_flash('error', 'Invalid security token.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('/')));
        exit;
    }
}

$response = ['success' => false, 'error' => 'Invalid request'];

if ($action === 'send_request') {
    $targetHash = $_POST['target_id'] ?? '';
    $companyHash = $_POST['target_company_id'] ?? '';

    $targetId = decode_id($targetHash);
    if ($targetId <= 0 && is_numeric($targetHash)) {
        $targetId = (int)$targetHash;
    }

    $companyId = decode_id($companyHash);
    if ($companyId <= 0 && is_numeric($companyHash)) {
        $companyId = (int)$companyHash;
    }

    $note = trim($_POST['message'] ?? '');

    if ($companyId > 0) {
        $res = connect_with_company((int)$currentUser['id'], $companyId, $note);
    } else {
        $res = send_connection_request((int)$currentUser['id'], $targetId, $note);
    }

    $response = $res;
    if ($res['success']) {
        $notifTarget = !empty($res['target_id']) ? decode_id($res['target_id']) : $targetId;
        $response['badge_count'] = get_pending_connections_count($notifTarget);
    }
} elseif ($action === 'accept') {
    $connHash = $_POST['connection_id'] ?? '';
    $connId = decode_id($connHash);
    if ($connId <= 0 && is_numeric($connHash)) {
        $connId = (int)$connHash;
    }

    $res = respond_connection_request($connId, (int)$currentUser['id'], 'accept');
    $response = $res;
    if ($res['success']) {
        $response['badge_count'] = get_pending_connections_count((int)$currentUser['id']);
    }
} elseif ($action === 'decline') {
    $connHash = $_POST['connection_id'] ?? '';
    $connId = decode_id($connHash);
    if ($connId <= 0 && is_numeric($connHash)) {
        $connId = (int)$connHash;
    }

    $res = respond_connection_request($connId, (int)$currentUser['id'], 'decline');
    $response = $res;
    if ($res['success']) {
        $response['badge_count'] = get_pending_connections_count((int)$currentUser['id']);
    }
} elseif ($action === 'withdraw') {
    $connHash = $_POST['connection_id'] ?? '';
    $connId = decode_id($connHash);
    if ($connId <= 0 && is_numeric($connHash)) {
        $connId = (int)$connHash;
    }

    // Fallback: If target_id passed instead of connection_id
    if ($connId <= 0 && !empty($_POST['target_id'])) {
        $targetId = decode_id($_POST['target_id']) ?: (int)$_POST['target_id'];
        $chk = $db->prepare("SELECT id FROM user_connections WHERE requester_id = ? AND receiver_id = ? AND status = 'pending' LIMIT 1");
        $chk->execute([$currentUser['id'], $targetId]);
        $connId = (int)$chk->fetchColumn();
    }

    $res = respond_connection_request($connId, (int)$currentUser['id'], 'withdraw');
    $response = $res;
} elseif ($action === 'remove') {
    $targetHash = $_POST['target_id'] ?? '';
    $targetId = decode_id($targetHash);
    if ($targetId <= 0 && is_numeric($targetHash)) {
        $targetId = (int)$targetHash;
    }

    $res = remove_user_connection((int)$currentUser['id'], $targetId);
    $response = $res;
} elseif ($action === 'get_status') {
    $targetHash = $_GET['target_id'] ?? '';
    $targetId = decode_id($targetHash);
    if ($targetId <= 0 && is_numeric($targetHash)) {
        $targetId = (int)$targetHash;
    }

    $statusData = get_connection_status((int)$currentUser['id'], $targetId);
    $response = [
        'success' => true,
        'status' => $statusData['status'],
        'is_connected' => $statusData['is_connected'],
        'connection_id' => !empty($statusData['connection']['id']) ? encode_id($statusData['connection']['id']) : null
    ];
}

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}

// Redirect back for regular POST
if (!empty($response['success'])) {
    set_flash('success', $response['message'] ?? 'Action completed successfully.');
} else {
    set_flash('error', $response['error'] ?? 'Action failed.');
}

$redirectTo = $_POST['redirect_to'] ?? ($_SERVER['HTTP_REFERER'] ?? url('/'));
header('Location: ' . $redirectTo);
exit;
