<?php
/**
 * API: Email Role Auto-Detector
 * Fast, lightweight endpoint that detects whether an email belongs to a
 * Founder, Investor, Admin, or is unregistered.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config.php';

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Invalid or empty email address.'
    ]);
    exit;
}

try {
    $db = get_db();
    if (!$db) {
        throw new Exception('Database unavailable');
    }

    $stmt = $db->prepare("
        SELECT u.id, u.name, u.role, u.status, u.avatar_url,
               fp.designation as founder_title,
               c.name as company_name,
               ip.investor_type
        FROM users u
        LEFT JOIN founder_profiles fp ON fp.user_id = u.id
        LEFT JOIN company_founders cf ON cf.user_id = u.id
        LEFT JOIN companies c ON c.id = cf.company_id
        LEFT JOIN investor_profiles ip ON ip.user_id = u.id
        WHERE LOWER(u.email) = LOWER(?)
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $metaSubtitle = '';
        if ($user['role'] === 'founder') {
            $metaSubtitle = !empty($user['company_name']) 
                ? ($user['founder_title'] ?: 'Founder') . ' • ' . $user['company_name']
                : ($user['founder_title'] ?: 'Startup Founder');
        } elseif ($user['role'] === 'investor') {
            $metaSubtitle = $user['investor_type'] ?: 'Accredited Investor';
        } elseif ($user['role'] === 'admin') {
            $metaSubtitle = 'Platform Governance & Compliance';
        }

        echo json_encode([
            'success' => true,
            'exists' => true,
            'role' => $user['role'],
            'name' => $user['name'],
            'subtitle' => $metaSubtitle,
            'status' => $user['status']
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'exists' => false,
        'role' => null,
        'message' => 'No account found with this email.'
    ]);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'error' => 'Unable to detect account role.'
    ]);
    exit;
}
