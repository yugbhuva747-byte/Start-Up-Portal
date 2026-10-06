<?php
/**
 * API: User Registration Endpoint
 * Handles AJAX & Form Registrations for Founders and Investors
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// Support JSON payload or standard Form POST
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
if (!empty($token) && !verify_csrf($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired security token. Please refresh the page and try again.']);
    exit;
}

$name = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));
$password = (string)($input['password'] ?? '');
$passwordConfirm = (string)($input['password_confirmation'] ?? ($input['confirm_password'] ?? ''));
$roleInput = strtolower(trim((string)($input['role'] ?? 'founder')));
$role = in_array($roleInput, ['investor']) ? 'investor' : 'founder';

$city = trim((string)($input['city'] ?? 'Bengaluru'));
$country = trim((string)($input['country'] ?? 'India'));

// Plan Selection & Priority
$planCode = trim((string)($input['plan_code'] ?? 'free_trial'));
if (!in_array($planCode, ['free_trial', '1_month', '6_months', '1_year'], true)) {
    $planCode = 'free_trial';
}
$billingCycle = strtolower(trim((string)($input['billing_cycle'] ?? 'monthly'))) === 'annually' ? 'annually' : 'monthly';

// Founder fields
$companyName = trim((string)($input['company_name'] ?? ($input['organization'] ?? '')));
$industry = trim((string)($input['industry'] ?? 'AI & Enterprise SaaS'));
$stage = trim((string)($input['stage'] ?? ($input['category'] ?? 'Seed')));
$designation = trim((string)($input['designation'] ?? 'Founder & CEO'));
$pitch = trim((string)($input['pitch'] ?? ''));

// Investor fields
$investorType = trim((string)($input['investor_type'] ?? 'Angel Investor'));
$ticketRange = trim((string)($input['ticket_range'] ?? ($input['category'] ?? '10-50L')));
$preferredIndustries = trim((string)($input['preferred_industries'] ?? 'AI & DeepTech, SaaS, FinTech, HealthTech'));

// Validation
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all mandatory fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Please provide a valid email address.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters long.']);
    exit;
}

if (!empty($passwordConfirm) && $password !== $passwordConfirm) {
    echo json_encode(['success' => false, 'error' => 'Passwords do not match.']);
    exit;
}

$db = get_db();
if (!$db) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed. Please run setup.php.']);
    exit;
}

try {
    // Check if email already exists
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        echo json_encode([
            'success' => false, 
            'error' => 'An account with this email address already exists. Please sign in.'
        ]);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("
        INSERT INTO users (name, email, phone, password_hash, role, status, is_verified, city, country, created_at)
        VALUES (?, ?, ?, ?, ?, 'active', 0, ?, ?, NOW())
    ");
    $stmt->execute([$name, $email, $phone, $passwordHash, $role, $city, $country]);
    $userId = (int)$db->lastInsertId();

    if ($role === 'founder') {
        // Founder Profile
        $bio = $pitch ?: ($companyName ? "Building {$companyName}" : "Founder on NEXORA Portal");
        $fpStmt = $db->prepare("INSERT INTO founder_profiles (user_id, designation, bio, created_at) VALUES (?, ?, ?, NOW())");
        $fpStmt->execute([$userId, $designation ?: 'Founder & CEO', $bio]);

        // Company Record
        $cName = $companyName ?: ($name . "'s Venture");
        $compStmt = $db->prepare("
            INSERT INTO companies (name, industry, stage, pitch, description, city, country, verified_status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $compStmt->execute([
            $cName,
            $industry,
            $stage,
            $pitch ?: "Building next-generation solutions in {$industry}.",
            $pitch ?: "High-growth venture focused on {$industry} innovations.",
            $city,
            $country
        ]);
        $companyId = (int)$db->lastInsertId();

        // Map founder to company
        $cfStmt = $db->prepare("INSERT INTO company_founders (company_id, user_id, designation, is_signatory, created_at) VALUES (?, ?, ?, 1, NOW())");
        $cfStmt->execute([$companyId, $userId, $designation ?: 'Founder & CEO']);

    } else {
        // Investor Profile
        $ipStmt = $db->prepare("INSERT INTO investor_profiles (user_id, investor_type, experience_years, risk_disclosure_accepted, created_at) VALUES (?, ?, 3, 1, NOW())");
        $ipStmt->execute([$userId, $investorType ?: 'Angel Investor']);

        // Ticket calculation
        $minTicket = 200000.00;
        $maxTicket = 5000000.00;
        if ($ticketRange === '2-10L') {
            $minTicket = 200000.00;
            $maxTicket = 1000000.00;
        } elseif ($ticketRange === '10-50L' || $ticketRange === 'angel') {
            $minTicket = 1000000.00;
            $maxTicket = 5000000.00;
        } elseif ($ticketRange === '50L-2Cr' || $ticketRange === 'syndicate') {
            $minTicket = 5000000.00;
            $maxTicket = 20000000.00;
        } elseif ($ticketRange === '2Cr+' || $ticketRange === 'fund') {
            $minTicket = 20000000.00;
            $maxTicket = 100000000.00;
        }

        $prefStmt = $db->prepare("
            INSERT INTO investor_preferences (user_id, preferred_industries, preferred_stages, min_ticket, max_ticket, created_at)
            VALUES (?, ?, 'Seed, Pre-Series A, Series A', ?, ?, NOW())
        ");
        $prefStmt->execute([$userId, $preferredIndustries ?: 'AI/SaaS, FinTech, HealthTech', $minTicket, $maxTicket]);
    }

    // Initialize Plan Subscription & Priority
    if (function_exists('activate_user_subscription')) {
        activate_user_subscription($userId, $planCode, $billingCycle);
    }

    // Set Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $role;
    $_SESSION['user_name'] = $name;

    // Set persistent 30-day cookie session
    if (function_exists('set_remember_me_cookie')) {
        set_remember_me_cookie($userId, 30);
    }

    if (function_exists('log_audit')) {
        log_audit($userId, 'USER_REGISTERED', 'users', $userId, "Registered via registration form as {$role} on {$planCode} plan");
    }
    if (function_exists('set_flash')) {
        set_flash('success', "Welcome to the portal, " . htmlspecialchars($name) . "! Your " . ucfirst($role) . " account is ready.");
    }

    $redirectUrl = $role === 'investor' ? url('investor/dashboard.php') : url('founder/dashboard.php');

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Redirecting to your dashboard...',
        'redirect' => $redirectUrl,
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => $role
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    $isDev = (defined('APP_ENV') && APP_ENV === 'development');
    echo json_encode([
        'success' => false,
        'error' => $isDev ? ('Registration failed: ' . $e->getMessage()) : 'An unexpected error occurred during account creation. Please verify your details and try again.'
    ]);
}
