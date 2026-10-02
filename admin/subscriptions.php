<?php
/**
 * ============================================================================
 * admin/subscriptions.php
 * ----------------------------------------------------------------------------
 * Subscriptions & Priority Center
 * Monitor platform recurring revenues, manage subscriber priority levels (Level 1–4),
 * and override user feature entitlements.
 * ============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/plan_permissions.php';

$adminUser = require_auth('admin');
$db = get_db();
$pageTitle = 'Subscriptions & Priority Center';

$flash = get_flash();
$error = '';

if (!$db) {
    die("Database connection failure.");
}

// ----------------------------------------------------------------------------
// POST Actions: Modify Plan, Extend Validity, Cancel/Reactivate, Manual Grant
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security session expired. Please try again.');
        header('Location: ' . url('admin/subscriptions.php'));
        exit;
    }

    $action = trim($_POST['form_action'] ?? '');

    // 1. UPDATE USER PLAN & PRIORITY / MANUAL GRANT
    if ($action === 'update_plan') {
        $subId = (int)($_POST['subscription_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $planCode = trim($_POST['plan_code'] ?? 'free_trial');
        $priorityLevel = (int)($_POST['priority_level'] ?? 1);
        $status = trim($_POST['status'] ?? 'active');
        $expiresAt = trim($_POST['expires_at'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0.00);

        if ($userId <= 0) {
            set_flash('error', 'Invalid user selected.');
            header('Location: ' . url('admin/subscriptions.php'));
            exit;
        }

        // Map Plan Names & Default amounts
        $planMeta = [
            'free_trial' => ['name' => '14-Day Free Trial', 'cycle' => 'trial', 'amount' => 0.00],
            '1_month'    => ['name' => '1 Month Sprint', 'cycle' => 'monthly', 'amount' => 999.00],
            '6_months'   => ['name' => '6 Months Dealmaker', 'cycle' => 'monthly', 'amount' => 1666.00],
            '1_year'     => ['name' => '1 Year Scale Pro', 'cycle' => 'annually', 'amount' => 14988.00]
        ];

        $planName = $planMeta[$planCode]['name'] ?? ucfirst(str_replace('_', ' ', $planCode));
        $billingCycle = $planMeta[$planCode]['cycle'] ?? 'monthly';
        if ($amount <= 0 && isset($planMeta[$planCode]['amount'])) {
            $amount = $planMeta[$planCode]['amount'];
        }

        // Format expiry
        $expDate = (!empty($expiresAt)) ? date('Y-m-d H:i:s', strtotime($expiresAt)) : date('Y-m-d H:i:s', strtotime('+30 days'));

        // Update Subscription record if exists, or create new
        if ($subId > 0) {
            $stmt = $db->prepare("
                UPDATE subscriptions 
                SET plan_code = ?, plan_name = ?, billing_cycle = ?, priority_level = ?, status = ?, expires_at = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$planCode, $planName, $billingCycle, $priorityLevel, $status, $expDate, $subId]);
        } else {
            $uRoleStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $uRoleStmt->execute([$userId]);
            $uRole = $uRoleStmt->fetchColumn() ?: 'founder';

            $stmt = $db->prepare("
                INSERT INTO subscriptions 
                (user_id, plan_code, plan_name, billing_cycle, role, amount, priority_level, status, payment_method, payment_status, transaction_ref, starts_at, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Admin Manual Grant', 'completed', ?, NOW(), ?)
            ");
            $ref = 'TXN-ADM-' . strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 8));
            $stmt->execute([$userId, $planCode, $planName, $billingCycle, $uRole, $amount, $priorityLevel, $status, $ref, $expDate]);
            $subId = (int)$db->lastInsertId();
        }

        // Synchronize Users Table
        $uUpd = $db->prepare("
            UPDATE users 
            SET current_plan = ?, priority_level = ?, plan_expires_at = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $uUpd->execute([$planCode, $priorityLevel, $expDate, $userId]);

        // If user is founder, also update their company's priority level for dealflow ranking
        $cUpd = $db->prepare("
            UPDATE companies c 
            JOIN company_founders cf ON cf.company_id = c.id 
            SET c.priority_level = ? 
            WHERE cf.user_id = ?
        ");
        $cUpd->execute([$priorityLevel, $userId]);

        log_audit($adminUser['id'], 'UPDATE_SUBSCRIPTION_PRIORITY', 'subscriptions', $subId, "Admin updated user #{$userId} to plan '{$planCode}' with Priority Level {$priorityLevel}");

        set_flash('success', "Plan and Priority Level updated successfully for member.");
        header('Location: ' . url('admin/subscriptions.php'));
        exit;
    }

    // 2. QUICK EXTEND VALIDITY
    if ($action === 'extend_validity') {
        $subId = (int)($_POST['subscription_id'] ?? 0);
        $days = (int)($_POST['days_to_add'] ?? 30);

        if ($subId > 0 && $days > 0) {
            $subStmt = $db->prepare("SELECT user_id, expires_at FROM subscriptions WHERE id = ?");
            $subStmt->execute([$subId]);
            $subRow = $subStmt->fetch();

            if ($subRow) {
                $curExp = strtotime($subRow['expires_at'] ?? 'now');
                if ($curExp < time()) {
                    $curExp = time();
                }
                $newExp = date('Y-m-d H:i:s', strtotime("+{$days} days", $curExp));

                $db->prepare("UPDATE subscriptions SET expires_at = ?, status = 'active', updated_at = NOW() WHERE id = ?")
                   ->execute([$newExp, $subId]);

                $db->prepare("UPDATE users SET plan_expires_at = ?, updated_at = NOW() WHERE id = ?")
                   ->execute([$newExp, $subRow['user_id']]);

                log_audit($adminUser['id'], 'EXTEND_SUBSCRIPTION', 'subscriptions', $subId, "Admin extended validity by {$days} days for subscription #{$subId}");
                set_flash('success', "Validity successfully extended by {$days} days (New expiry: " . date('M d, Y', strtotime($newExp)) . ").");
            }
        }
        header('Location: ' . url('admin/subscriptions.php'));
        exit;
    }

    // 3. TOGGLE CANCEL / REACTIVATE
    if ($action === 'toggle_status') {
        $subId = (int)($_POST['subscription_id'] ?? 0);
        $newStatus = trim($_POST['new_status'] ?? 'active');

        if ($subId > 0 && in_array($newStatus, ['active', 'cancelled', 'expired'])) {
            $subStmt = $db->prepare("SELECT user_id, plan_code, priority_level FROM subscriptions WHERE id = ?");
            $subStmt->execute([$subId]);
            $subRow = $subStmt->fetch();

            if ($subRow) {
                $db->prepare("UPDATE subscriptions SET status = ?, updated_at = NOW() WHERE id = ?")
                   ->execute([$newStatus, $subId]);

                if ($newStatus === 'cancelled' || $newStatus === 'expired') {
                    $db->prepare("UPDATE users SET current_plan = 'free_trial', priority_level = 1, updated_at = NOW() WHERE id = ?")
                       ->execute([$subRow['user_id']]);
                    $db->prepare("UPDATE companies c JOIN company_founders cf ON cf.company_id = c.id SET c.priority_level = 1 WHERE cf.user_id = ?")
                       ->execute([$subRow['user_id']]);
                } else {
                    $db->prepare("UPDATE users SET current_plan = ?, priority_level = ?, updated_at = NOW() WHERE id = ?")
                       ->execute([$subRow['plan_code'], $subRow['priority_level'], $subRow['user_id']]);
                    $db->prepare("UPDATE companies c JOIN company_founders cf ON cf.company_id = c.id SET c.priority_level = ? WHERE cf.user_id = ?")
                       ->execute([$subRow['priority_level'], $subRow['user_id']]);
                }

                log_audit($adminUser['id'], 'TOGGLE_SUBSCRIPTION_STATUS', 'subscriptions', $subId, "Admin updated subscription #{$subId} status to {$newStatus}");
                set_flash('success', "Subscription status updated to " . ucfirst($newStatus) . ".");
            }
        }
        header('Location: ' . url('admin/subscriptions.php'));
        exit;
    }
}

// ----------------------------------------------------------------------------
// Analytics & Metrics Computation (Dynamic Queries)
// ----------------------------------------------------------------------------
$totalRevenue = 0.0;
$activePaidSubscribers = 0;
$annualSubscribers = 0;
$level4VipSubscribers = 0;

try {
    $revStmt = $db->query("SELECT SUM(amount) FROM subscriptions WHERE payment_status = 'completed'");
    $totalRevenue = (float)$revStmt->fetchColumn();

    $activeStmt = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'");
    $activePaidSubscribers = (int)$activeStmt->fetchColumn();

    $annStmt = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active' AND (billing_cycle = 'annually' OR plan_code = '1_year')");
    $annualSubscribers = (int)$annStmt->fetchColumn();

    $vipStmt = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active' AND priority_level = 4");
    $level4VipSubscribers = (int)$vipStmt->fetchColumn();
} catch (Exception $e) {}

// ----------------------------------------------------------------------------
// Filter & Search Query Handling
// ----------------------------------------------------------------------------
$filterPlan = trim($_GET['plan'] ?? '');
$filterRole = trim($_GET['role'] ?? '');
$filterPriority = (int)($_GET['priority'] ?? 0);
$filterStatus = trim($_GET['status'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

$sql = "
    SELECT 
        s.*,
        u.name AS user_name,
        u.email AS user_email,
        u.avatar_url AS user_avatar,
        u.status AS user_status,
        c.name AS company_name
    FROM subscriptions s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN company_founders cf ON cf.user_id = u.id
    LEFT JOIN companies c ON c.id = cf.company_id
    WHERE 1=1
";
$params = [];

if (!empty($filterPlan)) {
    $sql .= " AND s.plan_code = ?";
    $params[] = $filterPlan;
}
if (!empty($filterRole)) {
    $sql .= " AND s.role = ?";
    $params[] = $filterRole;
}
if ($filterPriority > 0) {
    $sql .= " AND s.priority_level = ?";
    $params[] = $filterPriority;
}
if (!empty($filterStatus)) {
    $sql .= " AND s.status = ?";
    $params[] = $filterStatus;
}
if (!empty($searchQuery)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR s.transaction_ref LIKE ? OR c.name LIKE ?)";
    $like = "%{$searchQuery}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY s.priority_level DESC, s.id DESC";

$subscriptions = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Failed to load subscriptions: " . $e->getMessage();
}

// Fetch all users for Manual Grant dropdown
$allUsers = [];
try {
    $allUsers = $db->query("SELECT id, name, email, role, priority_level FROM users ORDER BY name ASC LIMIT 250")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>
    
    <?php include __DIR__ . '/../includes/admin/head.php'; ?>

    <style>
        .stat-card-clean {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease-in-out;
        }
        .dark .stat-card-clean,
        html.dark .stat-card-clean {
            background: #111827 !important;
            border-color: #1e293b !important;
        }
        .filter-bar-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 0.875rem 1.25rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        }
        .dark .filter-bar-clean,
        html.dark .filter-bar-clean {
            background: #111827 !important;
            border-color: #1e293b !important;
        }
        .table-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }
        .dark .table-card-clean,
        html.dark .table-card-clean {
            background: #111827 !important;
            border-color: #1e293b !important;
        }
    </style>
</head>
<body class="bg-[#f8fafc] dark:bg-[#0b0f19] text-slate-900 dark:text-slate-100 flex min-h-screen font-sans antialiased">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <!-- Main Content Stage -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navbar -->
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6" id="subscriptions-admin-main">
            
            <!-- Page Header (Identical to reference screenshot) -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <!-- Crown Badge -->
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#f3e8ff] text-[#9333ea] text-[11px] font-bold uppercase tracking-wider mb-2">
                        <i data-lucide="crown" class="w-3.5 h-3.5 text-[#9333ea]"></i>
                        <span>TIER &amp; PRIORITY MANAGEMENT</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Subscriptions &amp; Priority Center
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Monitor platform recurring revenues, manage subscriber priority levels (Level 1–4), and override user feature entitlements.
                    </p>
                </div>

                <!-- Action Buttons: Manual Plan Grant + Invoices -->
                <div class="flex items-center gap-2.5 flex-shrink-0">
                    <button type="button" onclick="openGrantModal()" 
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-[#9333ea] to-[#ea580c] hover:opacity-95 text-white text-xs sm:text-sm font-bold shadow-md shadow-purple-600/20 transition cursor-pointer">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Manual Plan Grant</span>
                    </button>
                    <a href="<?= url('admin/revenue.php') ?>" 
                       class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs sm:text-sm font-semibold shadow-2xs transition">
                        <i data-lucide="receipt" class="w-4 h-4 text-slate-500"></i>
                        <span>Invoices</span>
                    </a>
                </div>
            </div>

            <!-- Flash Alert -->
            <?php if (!empty($flash['message'])): ?>
                <div class="p-4 rounded-xl flex items-center gap-3 text-xs sm:text-sm font-semibold shadow-2xs transition-all <?= $flash['type'] === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- 4 Metric Cards (Matching Screenshot Exactly) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- 1. Total Plan Revenue -->
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400">TOTAL PLAN REVENUE</span>
                        <div class="w-8 h-8 rounded-lg bg-[#faf5ff] dark:bg-purple-950/60 text-[#9333ea] dark:text-purple-300 flex items-center justify-center font-bold text-sm">
                            ₹
                        </div>
                    </div>
                    <div class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                        ₹<?= number_format($totalRevenue, 2) ?>
                    </div>
                    <div class="mt-1 text-xs font-semibold text-[#a855f7] dark:text-purple-400">
                        Lifetime completed charges
                    </div>
                </div>

                <!-- 2. Active Subscribers -->
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400">ACTIVE SUBSCRIBERS</span>
                        <div class="w-8 h-8 rounded-lg bg-[#f0fdf4] dark:bg-emerald-950/60 text-[#16a34a] dark:text-emerald-300 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                        <?= number_format($activePaidSubscribers) ?>
                    </div>
                    <div class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-[#16a34a] dark:text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#16a34a] dark:bg-emerald-400"></span>
                        <span>Paying founders &amp; angels</span>
                    </div>
                </div>

                <!-- 3. Annual Pass Holders -->
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400">ANNUAL PASS HOLDERS</span>
                        <div class="w-8 h-8 rounded-lg bg-[#eff6ff] dark:bg-blue-950/60 text-[#2563eb] dark:text-blue-300 flex items-center justify-center">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                        <?= number_format($annualSubscribers) ?>
                    </div>
                    <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                        High-retention 1-year commitments
                    </div>
                </div>

                <!-- 4. Level 4 VIP Spotlight -->
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400">LEVEL 4 VIP SPOTLIGHT</span>
                        <div class="w-8 h-8 rounded-lg bg-[#fefce8] dark:bg-amber-950/60 text-[#ca8a04] dark:text-amber-300 flex items-center justify-center">
                            <i data-lucide="crown" class="w-4 h-4 text-amber-500"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                        <?= number_format($level4VipSubscribers) ?>
                    </div>
                    <div class="mt-1 text-xs font-semibold text-[#ca8a04] dark:text-amber-400">
                        Top tier VIP founders &amp; VCs
                    </div>
                </div>
            </div>

            <!-- Filter Bar (Matching Screenshot Exactly) -->
            <div class="filter-bar-clean dark:bg-slate-900 dark:border-slate-800">
                <form method="GET" action="<?= url('admin/subscriptions.php') ?>" class="flex flex-wrap items-center gap-2.5 w-full">
                    
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[240px]">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" 
                               placeholder="Search user name, email, tx ref, company..." 
                               class="w-full pl-10 pr-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                    </div>

                    <!-- Plan Filter -->
                    <select name="plan" class="px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                        <option value="">All Plan Tiers</option>
                        <option value="6_months" <?= $filterPlan === '6_months' ? 'selected' : '' ?>>6 Months Dealmaker</option>
                        <option value="1_year" <?= $filterPlan === '1_year' ? 'selected' : '' ?>>1 Year Scale Pro</option>
                        <option value="1_month" <?= $filterPlan === '1_month' ? 'selected' : '' ?>>1 Month Sprint</option>
                        <option value="free_trial" <?= $filterPlan === 'free_trial' ? 'selected' : '' ?>>Free Trial</option>
                    </select>

                    <!-- Role Filter -->
                    <select name="role" class="px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                        <option value="">All Roles</option>
                        <option value="founder" <?= $filterRole === 'founder' ? 'selected' : '' ?>>Founder</option>
                        <option value="investor" <?= $filterRole === 'investor' ? 'selected' : '' ?>>Investor</option>
                    </select>

                    <!-- Priority Level Filter -->
                    <select name="priority" class="px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                        <option value="0">All Priorities</option>
                        <option value="4" <?= $filterPriority === 4 ? 'selected' : '' ?>>Level 4 (VIP Spotlight)</option>
                        <option value="3" <?= $filterPriority === 3 ? 'selected' : '' ?>>Level 3 (Featured)</option>
                        <option value="2" <?= $filterPriority === 2 ? 'selected' : '' ?>>Level 2 (FastTrack)</option>
                        <option value="1" <?= $filterPriority === 1 ? 'selected' : '' ?>>Level 1 (Explorer)</option>
                    </select>

                    <!-- Status Filter -->
                    <select name="status" class="px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="expired" <?= $filterStatus === 'expired' ? 'selected' : '' ?>>Expired</option>
                        <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="trial" <?= $filterStatus === 'trial' ? 'selected' : '' ?>>Trial</option>
                    </select>

                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#0f172a] hover:bg-slate-800 dark:bg-purple-600 dark:hover:bg-purple-700 text-white text-xs font-bold transition shadow-xs">
                        Filter
                    </button>

                    <?php if (!empty($filterPlan) || !empty($filterRole) || $filterPriority > 0 || !empty($filterStatus) || !empty($searchQuery)): ?>
                        <a href="<?= url('admin/subscriptions.php') ?>" class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Subscriptions Table (Matching Screenshot Exactly) -->
            <div class="table-card-clean dark:bg-slate-900 dark:border-slate-800">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Registered Subscriptions &amp; Priority Records</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-400 mt-0.5">Showing <?= count($subscriptions) ?> record(s)</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 text-[10.5px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400">
                                <th class="py-3 px-6">USER &amp; ORGANIZATION</th>
                                <th class="py-3 px-4">ROLE</th>
                                <th class="py-3 px-4">PLAN TIER &amp; BILLING</th>
                                <th class="py-3 px-4">PRIORITY LEVEL</th>
                                <th class="py-3 px-4">AMOUNT</th>
                                <th class="py-3 px-4">STATUS</th>
                                <th class="py-3 px-4">VALIDITY / EXPIRY</th>
                                <th class="py-3 px-6 text-right">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if (empty($subscriptions)): ?>
                                <tr>
                                    <td colspan="8" class="py-14 text-center text-slate-400">
                                        <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 opacity-40"></i>
                                        <p class="text-xs font-semibold text-slate-600">No subscriptions found matching query.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subscriptions as $sub): 
                                    $pLevel = (int)($sub['priority_level'] ?? 1);
                                    $expTimestamp = strtotime($sub['expires_at'] ?? 'now');
                                    $isExpired = $expTimestamp < time();
                                    $daysRemaining = max(0, (int)ceil(($expTimestamp - time()) / 86400));
                                    $initial = strtoupper(substr($sub['user_name'] ?? 'U', 0, 1));
                                ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <!-- User & Org -->
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-[#8b5cf6] text-white font-bold flex items-center justify-center text-xs shrink-0 shadow-2xs">
                                                    <?= $initial ?>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-900 leading-tight text-xs">
                                                        <?= htmlspecialchars($sub['user_name'] ?? 'Unknown User') ?>
                                                    </div>
                                                    <div class="text-[11px] text-slate-400 font-normal mt-0.5">
                                                        <?= htmlspecialchars($sub['user_email'] ?? '') ?>
                                                    </div>
                                                    <?php if (!empty($sub['company_name'])): ?>
                                                        <div class="text-[11px] text-slate-600 font-medium flex items-center gap-1 mt-0.5">
                                                            <span>🏢</span>
                                                            <span><?= htmlspecialchars($sub['company_name']) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Role Badge -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <?php if (($sub['role'] ?? '') === 'founder'): ?>
                                                <span class="inline-flex items-center px-3 py-0.5 rounded-full bg-[#eff6ff] text-[#3b82f6] border border-[#dbeafe] text-[11px] font-semibold">
                                                    Founder
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-0.5 rounded-full bg-[#faf5ff] text-[#a855f7] border border-[#f3e8ff] text-[11px] font-semibold">
                                                    Investor
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Plan Tier & Billing -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 text-xs">
                                                <?= htmlspecialchars($sub['plan_name'] ?? ucfirst($sub['plan_code'])) ?>
                                            </div>
                                            <div class="text-[11px] text-slate-400 capitalize mt-0.5">
                                                <?= htmlspecialchars($sub['billing_cycle'] ?? 'monthly') ?> Cycle
                                            </div>
                                        </td>

                                        <!-- Priority Level -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <?php if ($pLevel === 4): ?>
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gradient-to-r from-[#f97316] to-[#ec4899] text-white text-[10.5px] font-bold shadow-2xs">
                                                    <span>👑</span>
                                                    <span>Level 4 (VIP Spotlight)</span>
                                                </span>
                                            <?php elseif ($pLevel === 3): ?>
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-[#f3e8ff] text-[#9333ea] text-[10.5px] font-bold">
                                                    <span>⭐</span>
                                                    <span>Level 3 (Featured)</span>
                                                </span>
                                            <?php elseif ($pLevel === 2): ?>
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-[#dbeafe] text-[#2563eb] text-[10.5px] font-bold">
                                                    <span>⚡</span>
                                                    <span>Level 2 (FastTrack)</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-[#f1f5f9] text-[#64748b] text-[10.5px] font-semibold">
                                                    <span>🌱</span>
                                                    <span>Level 1 (Explorer)</span>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Amount -->
                                        <td class="py-4 px-4 font-bold text-slate-900 text-xs font-mono whitespace-nowrap">
                                            ₹<?= number_format((float)($sub['amount'] ?? 0), 2) ?>
                                        </td>

                                        <!-- Status -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <?php if (($sub['status'] ?? '') === 'active' && !$isExpired): ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#f0fdf4] text-[#16a34a] border border-[#dcfce7] text-[11px] font-semibold">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></span>
                                                    <span>Active</span>
                                                </span>
                                            <?php elseif ($isExpired || ($sub['status'] ?? '') === 'expired'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#fef2f2] text-[#ef4444] border border-[#fee2e2] text-[11px] font-semibold">
                                                    Expired
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200 text-[11px] font-semibold capitalize">
                                                    <?= htmlspecialchars($sub['status'] ?? 'Unknown') ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Validity / Expiry -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div class="font-medium text-slate-900 text-xs">
                                                <?= date('M d, Y', $expTimestamp) ?>
                                            </div>
                                            <?php if (!$isExpired && ($sub['status'] ?? '') === 'active'): ?>
                                                <div class="text-[11px] text-[#059669] font-medium mt-0.5">
                                                    <?= $daysRemaining ?> days remaining
                                                </div>
                                            <?php else: ?>
                                                <div class="text-[11px] text-rose-500 font-medium mt-0.5">Expired</div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-4 px-6 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <!-- Edit Priority Button -->
                                                <button type="button" 
                                                    onclick='openEditModal(<?= json_encode($sub) ?>)'
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-purple-600 hover:bg-purple-50 transition"
                                                    title="Modify Plan &amp; Priority Level">
                                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                </button>

                                                <!-- Extend Validity Button -->
                                                <button type="button" 
                                                    onclick='openExtendModal(<?= json_encode($sub) ?>)'
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition"
                                                    title="Extend Validity (+30, 60, 365 Days)">
                                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                                </button>

                                                <!-- Cancel / Reactivate Toggle Button -->
                                                <form method="POST" action="<?= url('admin/subscriptions.php') ?>" class="inline" onsubmit="return confirm('Change status for this subscription?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="form_action" value="toggle_status">
                                                    <input type="hidden" name="subscription_id" value="<?= $sub['id'] ?>">
                                                    <input type="hidden" name="new_status" value="<?= ($sub['status'] ?? '') === 'active' ? 'cancelled' : 'active' ?>">
                                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="<?= ($sub['status'] ?? '') === 'active' ? 'Cancel Subscription' : 'Reactivate' ?>">
                                                        <i data-lucide="<?= ($sub['status'] ?? '') === 'active' ? 'ban' : 'rotate-ccw' ?>" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ==========================================================================
     MODAL 1: EDIT PLAN & PRIORITY LEVEL
     ========================================================================== -->
<div id="editPlanModal" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative w-full max-w-[500px] bg-white rounded-3xl p-6 shadow-2xl border border-slate-200">
        <!-- Close Button -->
        <button type="button" onclick="closeEditModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 font-bold text-sm">
            ✕
        </button>

        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                <i data-lucide="sliders" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-slate-900 leading-tight">Modify Plan &amp; Priority</h3>
                <p class="text-xs text-slate-500" id="editModalSubTitle">Updating entitlements for member</p>
            </div>
        </div>

        <form method="POST" action="<?= url('admin/subscriptions.php') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="update_plan">
            <input type="hidden" id="editModalSubId" name="subscription_id" value="0">
            <input type="hidden" id="editModalUserId" name="user_id" value="0">

            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Target Plan Tier</label>
                <select id="editModalPlanCode" name="plan_code" onchange="autoSyncPriorityLevel(this.value)" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                    <option value="6_months">⭐ 6 Months Dealmaker (Level 3 - Featured)</option>
                    <option value="1_year">👑 1 Year Scale Pro (Level 4 - VIP Spotlight)</option>
                    <option value="1_month">⚡ 1 Month Sprint (Level 2 - FastTrack)</option>
                    <option value="free_trial">🌱 14-Day Free Trial (Level 1 - Explorer)</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Assigned Priority Level (1 to 4)</label>
                <select id="editModalPriorityLevel" name="priority_level" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                    <option value="1">Level 1 — Standard Read-Only / Basic Listing</option>
                    <option value="2">Level 2 — FastTrack Seed / Verified Angel</option>
                    <option value="3">Level 3 — Featured Dealflow / Syndicate Lead</option>
                    <option value="4">Level 4 — VIP Spotlight / Institutional Partner</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Subscription Status</label>
                    <select id="editModalStatus" name="status" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="trial">Trial</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Expiry Date</label>
                    <input type="date" id="editModalExpiresAt" name="expires_at" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                </div>
            </div>

            <div class="p-3 rounded-xl bg-purple-50 border border-purple-100 text-[11px] text-purple-800 font-medium">
                💡 <strong>Automatic Sync:</strong> Updating this priority instantly recalculates dealflow spotlight placement, unlocks/locks gated pages, and updates company ranking.
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/20 transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     MODAL 2: MANUAL PLAN GRANT
     ========================================================================== -->
<div id="grantPlanModal" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative w-full max-w-[500px] bg-white rounded-3xl p-6 shadow-2xl border border-slate-200">
        <!-- Close Button -->
        <button type="button" onclick="closeGrantModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 font-bold text-sm">
            ✕
        </button>

        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#9333ea] to-[#ea580c] text-white flex items-center justify-center font-bold">
                <i data-lucide="sparkles" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-slate-900 leading-tight">Manual Subscription Grant</h3>
                <p class="text-xs text-slate-500">Grant VIP plan and priority to partner or member</p>
            </div>
        </div>

        <form method="POST" action="<?= url('admin/subscriptions.php') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="update_plan">
            <input type="hidden" name="subscription_id" value="0">
            <input type="hidden" name="status" value="active">

            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Select Member Account</label>
                <select name="user_id" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Choose User --</option>
                    <?php foreach ($allUsers as $u): ?>
                        <option value="<?= $u['id'] ?>">
                            <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>) &middot; <?= ucfirst($u['role']) ?> &middot; Level <?= $u['priority_level'] ?? 1 ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Plan Tier to Grant</label>
                    <select name="plan_code" id="grantModalPlanCode" onchange="autoSyncGrantPriority(this.value)" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                        <option value="1_year" selected>👑 1 Year Scale Pro (Level 4)</option>
                        <option value="6_months">⭐ 6 Months Dealmaker (Level 3)</option>
                        <option value="1_month">⚡ 1 Month Sprint (Level 2)</option>
                        <option value="free_trial">🌱 14-Day Free Trial (Level 1)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Plan Amount (₹)</label>
                    <input type="number" step="0.01" name="amount" id="grantModalAmount" value="14988.00" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Priority Level</label>
                <select name="priority_level" id="grantModalPriority" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
                    <option value="4" selected>Level 4 — VIP Spotlight / Institutional Partner</option>
                    <option value="3">Level 3 — Featured Dealflow / Syndicate Lead</option>
                    <option value="2">Level 2 — FastTrack Seed / Verified Angel</option>
                    <option value="1">Level 1 — Standard Read-Only / Basic Listing</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Valid Until (Expiry Date)</label>
                <input type="date" name="expires_at" value="<?= date('Y-m-d', strtotime('+365 days')) ?>" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-purple-500">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeGrantModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-[#9333ea] to-[#ea580c] hover:opacity-95 text-white text-xs font-bold shadow-md shadow-purple-600/20 transition">
                    Grant Plan &amp; Priority
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     MODAL 3: EXTEND VALIDITY
     ========================================================================== -->
<div id="extendPlanModal" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative w-full max-w-[420px] bg-white rounded-3xl p-6 shadow-2xl border border-slate-200">
        <!-- Close Button -->
        <button type="button" onclick="closeExtendModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 font-bold text-sm">
            ✕
        </button>

        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <i data-lucide="calendar" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-slate-900 leading-tight">Extend Validity</h3>
                <p class="text-xs text-slate-500" id="extendModalUserTitle">Adding active days to subscription</p>
            </div>
        </div>

        <form method="POST" action="<?= url('admin/subscriptions.php') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="extend_validity">
            <input type="hidden" id="extendModalSubId" name="subscription_id" value="0">

            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Choose Extension Period</label>
                <select name="days_to_add" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                    <option value="30">+ 30 Days (1 Month)</option>
                    <option value="90">+ 90 Days (1 Quarter)</option>
                    <option value="180">+ 180 Days (6 Months)</option>
                    <option value="365">+ 365 Days (1 Full Year)</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeExtendModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition">
                    Extend Subscription
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Edit Modal Logic
    function openEditModal(sub) {
        document.getElementById('editModalSubId').value = sub.id || 0;
        document.getElementById('editModalUserId').value = sub.user_id || 0;
        document.getElementById('editModalSubTitle').textContent = 'User: ' + (sub.user_name || 'Member') + ' (' + (sub.user_email || '') + ')';
        document.getElementById('editModalPlanCode').value = sub.plan_code || 'free_trial';
        document.getElementById('editModalPriorityLevel').value = sub.priority_level || 1;
        document.getElementById('editModalStatus').value = sub.status || 'active';
        
        if (sub.expires_at) {
            document.getElementById('editModalExpiresAt').value = sub.expires_at.split(' ')[0];
        }
        
        document.getElementById('editPlanModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editPlanModal').classList.add('hidden');
    }

    function autoSyncPriorityLevel(planCode) {
        const prioritySelect = document.getElementById('editModalPriorityLevel');
        if (planCode === '1_year') prioritySelect.value = '4';
        else if (planCode === '6_months') prioritySelect.value = '3';
        else if (planCode === '1_month') prioritySelect.value = '2';
        else prioritySelect.value = '1';
    }

    // Grant Modal Logic
    function openGrantModal() {
        document.getElementById('grantPlanModal').classList.remove('hidden');
    }

    function closeGrantModal() {
        document.getElementById('grantPlanModal').classList.add('hidden');
    }

    function autoSyncGrantPriority(planCode) {
        const pSelect = document.getElementById('grantModalPriority');
        const amtInput = document.getElementById('grantModalAmount');
        if (planCode === '1_year') {
            pSelect.value = '4';
            if (amtInput) amtInput.value = '14988.00';
        } else if (planCode === '6_months') {
            pSelect.value = '3';
            if (amtInput) amtInput.value = '1666.00';
        } else if (planCode === '1_month') {
            pSelect.value = '2';
            if (amtInput) amtInput.value = '999.00';
        } else {
            pSelect.value = '1';
            if (amtInput) amtInput.value = '0.00';
        }
    }

    // Extend Modal Logic
    function openExtendModal(sub) {
        document.getElementById('extendModalSubId').value = sub.id || 0;
        document.getElementById('extendModalUserTitle').textContent = 'User: ' + (sub.user_name || 'Member');
        document.getElementById('extendPlanModal').classList.remove('hidden');
    }

    function closeExtendModal() {
        document.getElementById('extendPlanModal').classList.add('hidden');
    }

    // Initialize Lucide Icons
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

</body>
</html>
