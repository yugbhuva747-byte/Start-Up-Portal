<?php
/**
 * API: Subscription & Priority Plan Activation Endpoint
 * Handles Instant Plan Purchases, Priority Upgrades, and Trial Activations
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// 1. Check Authentication
if (!auth_check()) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'error' => 'Please create an account or sign in to activate your plan.',
        'requires_auth' => true,
        'redirect' => url('index.php#nx-portal-register')
    ]);
    exit;
}

$user = current_user();
$userId = (int)$user['id'];
$role = $user['role'] ?? 'founder';

// 2. Parse JSON or Form Input
$input = $_POST;
$raw = file_get_contents('php://input');
if (!empty($raw)) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = array_merge($input, $decoded);
    }
}

// Check CSRF
$token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF security token.']);
    exit;
}

$planCode = trim((string)($input['plan_code'] ?? '1_month'));
$billingCycle = strtolower(trim((string)($input['billing_cycle'] ?? 'monthly'))) === 'annually' ? 'annually' : 'monthly';
$paymentMethod = trim((string)($input['payment_method'] ?? 'Instant Gateway'));

if (!in_array($planCode, ['free_trial', '1_month', '6_months', '1_year'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid plan tier selected.']);
    exit;
}

// 3. Activate Subscription & Priority Level
try {
    $result = activate_user_subscription($userId, $planCode, $billingCycle, null, 'NEX-PAY-' . strtoupper(bin2hex(random_bytes(4))));
    
    if (!$result['success']) {
        echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Failed to activate plan.']);
        exit;
    }

    $planName = $result['plan_name'];
    $priorityName = $result['priority_name'];
    $priorityLevel = $result['priority_level'];

    if (function_exists('set_flash')) {
        set_flash('success', "🎉 Your {$planName} ({$priorityName}) has been activated successfully! Your priority privileges are now live.");
    }

    $dashboardUrl = $role === 'investor' ? url('investor/dashboard.php') : url('founder/dashboard.php');

    echo json_encode([
        'success' => true,
        'message' => "Successfully activated {$planName} with {$priorityName} (Priority Level {$priorityLevel})!",
        'redirect' => $dashboardUrl,
        'subscription' => $result
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while activating subscription: ' . $e->getMessage()
    ]);
}
