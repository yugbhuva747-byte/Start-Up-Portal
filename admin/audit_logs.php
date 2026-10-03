<?php
/**
 * Admin Module: Platform Security & Immutable Audit Logs
 * High-Readability Security & Activity Audit Trail
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Security Audit Trail';

$logs = [];
$stats = [
    'total' => 0,
    'last_24h' => 0,
    'auth_count' => 0,
    'deal_count' => 0
];

// Read filter parameters
$search = trim($_GET['search'] ?? $_GET['action_filter'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$timeframe = trim($_GET['timeframe'] ?? '');
$limit = min(200, max(25, (int)($_GET['limit'] ?? 75)));

if ($db) {
    // 1. Fetch KPI Metrics
    try {
        $statStmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN created_at >= NOW() - INTERVAL 24 HOUR THEN 1 ELSE 0 END) as last_24h,
                SUM(CASE WHEN action LIKE '%LOGIN%' OR action LIKE '%2FA%' OR action LIKE '%AUTH%' OR action LIKE '%LOGOUT%' THEN 1 ELSE 0 END) as auth_count,
                SUM(CASE WHEN action LIKE '%INTEREST%' OR action LIKE '%INVEST%' OR action LIKE '%FUNDING%' OR action LIKE '%ROUND%' THEN 1 ELSE 0 END) as deal_count
            FROM audit_logs
        ");
        $fetchedStats = $statStmt->fetch(PDO::FETCH_ASSOC);
        if ($fetchedStats) {
            $stats = [
                'total' => (int)($fetchedStats['total'] ?? 0),
                'last_24h' => (int)($fetchedStats['last_24h'] ?? 0),
                'auth_count' => (int)($fetchedStats['auth_count'] ?? 0),
                'deal_count' => (int)($fetchedStats['deal_count'] ?? 0)
            ];
        }
    } catch (Exception $e) {
        // Fallback gracefully
    }

    // 2. Build filtered log query
    $query = "
        SELECT al.*, u.name as actor_name, u.email as actor_email, u.role as actor_role
        FROM audit_logs al
        LEFT JOIN users u ON al.actor_user_id = u.id
        WHERE 1=1
    ";
    $params = [];

    // Search keyword
    if (!empty($search)) {
        $query .= " AND (al.action LIKE ? OR al.details LIKE ? OR al.ip_address LIKE ? OR al.entity_type LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Role filter
    if (!empty($roleFilter) && $roleFilter !== 'all') {
        if ($roleFilter === 'system') {
            $query .= " AND (al.actor_user_id IS NULL OR u.role IS NULL)";
        } else {
            $query .= " AND u.role = ?";
            $params[] = $roleFilter;
        }
    }

    // Category filter
    if (!empty($categoryFilter) && $categoryFilter !== 'all') {
        if ($categoryFilter === 'auth') {
            $query .= " AND (al.action LIKE '%LOGIN%' OR al.action LIKE '%2FA%' OR al.action LIKE '%AUTH%' OR al.action LIKE '%LOGOUT%')";
        } elseif ($categoryFilter === 'deals') {
            $query .= " AND (al.action LIKE '%INTEREST%' OR al.action LIKE '%INVEST%' OR al.action LIKE '%FUNDING%' OR al.action LIKE '%ROUND%')";
        } elseif ($categoryFilter === 'admin') {
            $query .= " AND (al.action LIKE '%SETTING%' OR al.action LIKE '%MAINTENANCE%' OR al.action LIKE '%USER_STATUS%' OR al.action LIKE '%ADMIN%')";
        } elseif ($categoryFilter === 'alerts') {
            $query .= " AND (al.action LIKE '%DECLINE%' OR al.action LIKE '%REJECT%' OR al.action LIKE '%DELETE%' OR al.action LIKE '%FAIL%' OR al.action LIKE '%BLOCK%')";
        }
    }

    // Timeframe filter
    if (!empty($timeframe) && $timeframe !== 'all') {
        if ($timeframe === 'today') {
            $query .= " AND al.created_at >= CURDATE()";
        } elseif ($timeframe === '7days') {
            $query .= " AND al.created_at >= NOW() - INTERVAL 7 DAY";
        } elseif ($timeframe === '30days') {
            $query .= " AND al.created_at >= NOW() - INTERVAL 30 DAY";
        }
    }

    $query .= " ORDER BY al.created_at DESC LIMIT " . (int)$limit;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!function_exists('get_action_badge')) {
    function get_action_badge(string $action): array {
        $a = strtoupper($action);

        if (str_contains($a, '2FA') || str_contains($a, 'LOGIN') || str_contains($a, 'LOGOUT') || str_contains($a, 'AUTH')) {
            return [
                'label' => str_replace('_', ' ', $action),
                'icon' => 'shield-check',
                'bg' => 'bg-purple-50 dark:bg-purple-950/60',
                'text' => 'text-purple-700 dark:text-purple-300',
                'border' => 'border-purple-200 dark:border-purple-800/70',
                'category' => 'Auth & Security'
            ];
        }
        if (str_contains($a, 'ACCEPT') || str_contains($a, 'APPROVE') || str_contains($a, 'SUCCESS') || str_contains($a, 'VERIFIED')) {
            return [
                'label' => str_replace('_', ' ', $action),
                'icon' => 'check-circle-2',
                'bg' => 'bg-emerald-50 dark:bg-emerald-950/60',
                'text' => 'text-emerald-700 dark:text-emerald-300',
                'border' => 'border-emerald-200 dark:border-emerald-800/70',
                'category' => 'Approval & Success'
            ];
        }
        if (str_contains($a, 'DECLINE') || str_contains($a, 'REJECT') || str_contains($a, 'DELETE') || str_contains($a, 'FAIL') || str_contains($a, 'SUSPEND') || str_contains($a, 'BLOCK')) {
            return [
                'label' => str_replace('_', ' ', $action),
                'icon' => 'alert-triangle',
                'bg' => 'bg-rose-50 dark:bg-rose-950/60',
                'text' => 'text-rose-700 dark:text-rose-300',
                'border' => 'border-rose-200 dark:border-rose-800/70',
                'category' => 'Restriction & Alert'
            ];
        }
        if (str_contains($a, 'INTEREST') || str_contains($a, 'INVEST') || str_contains($a, 'FUNDING') || str_contains($a, 'ROUND')) {
            return [
                'label' => str_replace('_', ' ', $action),
                'icon' => 'sparkles',
                'bg' => 'bg-blue-50 dark:bg-blue-950/60',
                'text' => 'text-blue-700 dark:text-blue-300',
                'border' => 'border-blue-200 dark:border-blue-800/70',
                'category' => 'Deal & Discovery'
            ];
        }
        if (str_contains($a, 'SETTING') || str_contains($a, 'CONFIG') || str_contains($a, 'UPDATE') || str_contains($a, 'MAINTENANCE')) {
            return [
                'label' => str_replace('_', ' ', $action),
                'icon' => 'sliders',
                'bg' => 'bg-amber-50 dark:bg-amber-950/60',
                'text' => 'text-amber-700 dark:text-amber-300',
                'border' => 'border-amber-200 dark:border-amber-800/70',
                'category' => 'System & Settings'
            ];
        }

        return [
            'label' => str_replace('_', ' ', $action),
            'icon' => 'activity',
            'bg' => 'bg-slate-100 dark:bg-slate-800',
            'text' => 'text-slate-700 dark:text-slate-300',
            'border' => 'border-slate-200 dark:border-slate-700',
            'category' => 'General Event'
        ];
    }
}

if (!function_exists('get_actor_meta')) {
    function get_actor_meta(?string $role, ?string $name): array {
        $r = strtolower($role ?? '');
        $initials = '';
        if (!empty($name)) {
            $parts = explode(' ', trim($name));
            $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
        }
        if (empty($initials)) {
            $initials = 'SYS';
        }

        switch ($r) {
            case 'admin':
                return [
                    'role_label' => 'Admin',
                    'badge' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800',
                    'avatar_bg' => 'bg-purple-600 text-white',
                    'icon' => 'shield'
                ];
            case 'founder':
                return [
                    'role_label' => 'Founder',
                    'badge' => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800',
                    'avatar_bg' => 'bg-sky-600 text-white',
                    'icon' => 'rocket'
                ];
            case 'investor':
                return [
                    'role_label' => 'Investor',
                    'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                    'avatar_bg' => 'bg-emerald-600 text-white',
                    'icon' => 'briefcase'
                ];
            default:
                return [
                    'role_label' => 'System / Guest',
                    'badge' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                    'avatar_bg' => 'bg-slate-600 text-white',
                    'icon' => 'cpu'
                ];
        }
    }
}

if (!function_exists('format_audit_timestamp')) {
    function format_audit_timestamp(string $datetime): array {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            $relative = 'Just now';
        } elseif ($diff < 3600) {
            $mins = max(1, (int)floor($diff / 60));
            $relative = $mins . 'm ago';
        } elseif ($diff < 86400) {
            $hours = (int)floor($diff / 3600);
            $relative = $hours . 'h ago';
        } elseif ($diff < 172800) {
            $relative = 'Yesterday';
        } else {
            $days = (int)floor($diff / 86400);
            $relative = $days . 'd ago';
        }

        return [
            'relative' => $relative,
            'clock' => date('h:i:s A', $timestamp),
            'date' => date('M j, Y', $timestamp),
            'full' => date('Y-m-d H:i:s', $timestamp)
        ];
    }
}

$hasActiveFilters = !empty($search) || (!empty($roleFilter) && $roleFilter !== 'all') || (!empty($categoryFilter) && $categoryFilter !== 'all') || (!empty($timeframe) && $timeframe !== 'all');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Audit Trail • <?= APP_NAME ?></title>

    <?php include __DIR__ . '/../includes/admin/head.php'; ?>
    <style>
        .stat-card-clean { 
            background: #FFFFFF; 
            border: 1px solid #E2E8F0; 
            border-radius: 1rem; 
            padding: 1.15rem 1.35rem; 
            box-shadow: 0 1px 3px 0 rgba(0,0,0,0.02); 
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card-clean:hover {
            box-shadow: 0 4px 12px 0 rgba(0,0,0,0.04);
        }
        .dark .stat-card-clean, html.dark .stat-card-clean { 
            background: #111827 !important; 
            border-color: #1e293b !important; 
        }
        .card-clean { 
            background: #FFFFFF; 
            border: 1px solid #E2E8F0; 
            border-radius: 1rem; 
            box-shadow: 0 1px 3px 0 rgba(0,0,0,0.02); 
        }
        .dark .card-clean, html.dark .card-clean { 
            background: #111827 !important; 
            border-color: #1e293b !important; 
        }
        .audit-table tr.audit-row {
            transition: background-color 0.15s ease, border-left-color 0.15s ease;
        }
        .audit-table tr.audit-row:hover {
            background-color: rgba(241, 245, 249, 0.7);
        }
        .dark .audit-table tr.audit-row:hover {
            background-color: rgba(30, 41, 59, 0.45);
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 flex min-h-screen dark:bg-[#0b0f19] dark:text-slate-100 font-sans antialiased">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="audit-admin-main">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200/80 dark:border-indigo-800/80 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-sm flex-shrink-0">
                        <i data-lucide="shield-alert" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2.5">
                            <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                                Security & Activity Audit Trail
                            </h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                Immutable Logs
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            Chronological, tamper-evident record of all platform authentication, investor interest submissions, and governance actions.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 self-start sm:self-auto">
                    <button type="button" onclick="exportAuditCSV()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-xs transition shadow-sm flex items-center space-x-1.5 cursor-pointer">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Export CSV</span>
                    </button>
                    <button type="button" onclick="window.location.reload()" class="p-2 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs transition shadow-sm flex items-center justify-center" title="Refresh records">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Top Metric Cards Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <div class="stat-card-clean">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Audit Events</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="history" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white mt-2">
                        <?= number_format($stats['total']) ?>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center space-x-1">
                        <i data-lucide="database" class="w-3 h-3 text-slate-400"></i>
                        <span>Historical log records</span>
                    </div>
                </div>

                <div class="stat-card-clean">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">24h Recent Activity</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-xl sm:text-2xl font-extrabold text-sky-600 dark:text-sky-400 mt-2">
                        <?= number_format($stats['last_24h']) ?>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center space-x-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Logged in last 24 hours</span>
                    </div>
                </div>

                <div class="stat-card-clean">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Auth & 2FA Security</span>
                        <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-xl sm:text-2xl font-extrabold text-purple-600 dark:text-purple-400 mt-2">
                        <?= number_format($stats['auth_count']) ?>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center space-x-1">
                        <i data-lucide="key-round" class="w-3 h-3 text-purple-400"></i>
                        <span>Logins, 2FA & sessions</span>
                    </div>
                </div>

                <div class="stat-card-clean">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Interests & Deals</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="handshake" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-xl sm:text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">
                        <?= number_format($stats['deal_count']) ?>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center space-x-1">
                        <i data-lucide="sparkles" class="w-3 h-3 text-emerald-400"></i>
                        <span>Interests & funding actions</span>
                    </div>
                </div>
            </div>

            <!-- Filter Card & Category Tabs -->
            <div class="card-clean rounded-2xl p-4 sm:p-5 space-y-4">
                <!-- Category Quick Filter Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                    <span class="text-slate-400 font-bold uppercase text-[10px] tracking-wider mr-1 flex-shrink-0">Category:</span>
                    <?php
                    $categories = [
                        '' => ['label' => 'All Events', 'icon' => 'layers'],
                        'auth' => ['label' => 'Security & Auth', 'icon' => 'shield-check'],
                        'deals' => ['label' => 'Investor Interests', 'icon' => 'sparkles'],
                        'admin' => ['label' => 'Settings & Admin', 'icon' => 'sliders'],
                        'alerts' => ['label' => 'Alerts & Declines', 'icon' => 'alert-triangle']
                    ];
                    foreach ($categories as $catKey => $catData):
                        $isActive = ($categoryFilter === $catKey);
                        $targetUrl = url('admin/audit_logs.php?' . http_build_query(array_merge($_GET, ['category' => $catKey])));
                    ?>
                        <a href="<?= $targetUrl ?>"
                           class="px-3 py-1.5 rounded-xl font-semibold transition flex items-center space-x-1.5 whitespace-nowrap flex-shrink-0 <?= $isActive ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' ?>">
                            <i data-lucide="<?= $catData['icon'] ?>" class="w-3.5 h-3.5"></i>
                            <span><?= $catData['label'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Search & Detailed Select Controls -->
                <form action="<?= url('admin/audit_logs.php') ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter) ?>">

                    <div class="sm:col-span-5 relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by action, details, actor name, email, IP, or entity..."
                               class="w-full pl-10 pr-9 py-2.5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 focus:bg-white dark:focus:bg-slate-900 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-xl text-slate-900 dark:text-slate-100 text-xs outline-none transition">
                        <?php if (!empty($search)): ?>
                            <button type="button" onclick="window.location.href='<?= url('admin/audit_logs.php?' . http_build_query(array_merge($_GET, ['search' => '', 'action_filter' => '']))) ?>'"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" title="Clear search">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="sm:col-span-3">
                        <select name="role" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs outline-none focus:ring-1 focus:ring-indigo-600">
                            <option value="">All Actor Roles</option>
                            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admins Only</option>
                            <option value="founder" <?= $roleFilter === 'founder' ? 'selected' : '' ?>>Founders Only</option>
                            <option value="investor" <?= $roleFilter === 'investor' ? 'selected' : '' ?>>Investors Only</option>
                            <option value="system" <?= $roleFilter === 'system' ? 'selected' : '' ?>>System / Automated</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <select name="timeframe" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs outline-none focus:ring-1 focus:ring-indigo-600">
                            <option value="">All Time</option>
                            <option value="today" <?= $timeframe === 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="7days" <?= $timeframe === '7days' ? 'selected' : '' ?>>Past 7 Days</option>
                            <option value="30days" <?= $timeframe === '30days' ? 'selected' : '' ?>>Past 30 Days</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2 flex items-center space-x-2">
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-xs transition shadow-xs flex items-center justify-center space-x-1.5">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span>Filter</span>
                        </button>

                        <?php if ($hasActiveFilters): ?>
                            <a href="<?= url('admin/audit_logs.php') ?>" class="p-2.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-300 transition flex items-center justify-center flex-shrink-0" title="Reset all filters">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table Card Container -->
            <div class="card-clean rounded-2xl p-5 md:p-6 shadow-sm overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2.5">
                        <h2 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Activity Log Stream
                        </h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 whitespace-nowrap">
                            <?= count($logs) ?> Events Found
                        </span>
                        <?php if ($hasActiveFilters): ?>
                            <span class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded-lg border border-amber-200 dark:border-amber-800/50">
                                Filtered
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-[11.5px] text-slate-400 flex items-center space-x-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Click any row or the <strong class="text-slate-600 dark:text-slate-300">Inspect</strong> button to open full event details.</span>
                    </div>
                </div>

                <div class="overflow-x-auto -mx-5 sm:mx-0">
                    <table class="w-full text-left border-collapse audit-table">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase tracking-wider text-[11px] font-bold bg-slate-50/70 dark:bg-slate-800/40">
                                <th class="py-3 px-4 font-bold">When</th>
                                <th class="py-3 px-3 font-bold">Initiated By</th>
                                <th class="py-3 px-3 font-bold">Event & Action</th>
                                <th class="py-3 px-3 font-bold">Entity</th>
                                <th class="py-3 px-3 font-bold">Context Summary</th>
                                <th class="py-3 px-3 font-bold">IP Address</th>
                                <th class="py-3 px-4 font-bold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                            <i data-lucide="search-x" class="w-6 h-6"></i>
                                        </div>
                                        <div class="text-sm font-bold text-slate-800 dark:text-slate-200">No audit records match your query</div>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                            Try broadening your search term, clearing category filters, or selecting a wider date range.
                                        </p>
                                        <?php if ($hasActiveFilters): ?>
                                            <a href="<?= url('admin/audit_logs.php') ?>" class="inline-flex items-center space-x-1.5 mt-3.5 px-3.5 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold text-xs border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 transition">
                                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                                <span>Reset All Filters</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $idx => $l): 
                                    $timeMeta = format_audit_timestamp($l['created_at']);
                                    $actionMeta = get_action_badge($l['action']);
                                    $actorMeta = get_actor_meta($l['actor_role'], $l['actor_name']);
                                    $initials = !empty($l['actor_name']) ? strtoupper(substr($l['actor_name'], 0, 1)) : 'S';
                                ?>
                                    <tr class="audit-row cursor-pointer group" onclick="openAuditInspector(<?= $idx ?>)">
                                        <!-- Timestamp Column -->
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 dark:text-white text-xs">
                                                <?= htmlspecialchars($timeMeta['relative']) ?>
                                            </div>
                                            <div class="text-[10.5px] text-slate-400 font-medium" title="<?= htmlspecialchars($timeMeta['full']) ?>">
                                                <?= htmlspecialchars($timeMeta['date']) ?> • <?= htmlspecialchars($timeMeta['clock']) ?>
                                            </div>
                                        </td>

                                        <!-- Actor Column -->
                                        <td class="py-3 px-3">
                                            <div class="flex items-center space-x-2.5">
                                                <div class="w-7 h-7 rounded-lg <?= $actorMeta['avatar_bg'] ?> flex items-center justify-center text-[10.5px] font-bold flex-shrink-0 shadow-xs">
                                                    <?= $initials ?>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-slate-900 dark:text-slate-100 truncate max-w-[130px] sm:max-w-[160px]" title="<?= htmlspecialchars($l['actor_name'] ?? 'System') ?>">
                                                        <?= htmlspecialchars($l['actor_name'] ?? 'System / Guest') ?>
                                                    </div>
                                                    <div class="flex items-center space-x-1.5 mt-0.5">
                                                        <span class="inline-block px-1.5 py-0.2 rounded text-[9.5px] font-bold border <?= $actorMeta['badge'] ?>">
                                                            <?= $actorMeta['role_label'] ?>
                                                        </span>
                                                        <?php if (!empty($l['actor_email'])): ?>
                                                            <span class="text-[10px] text-slate-400 truncate max-w-[90px]" title="<?= htmlspecialchars($l['actor_email']) ?>">
                                                                <?= htmlspecialchars($l['actor_email']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Event Action Column -->
                                        <td class="py-3 px-3">
                                            <div class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-lg border text-xs font-bold <?= $actionMeta['bg'] ?> <?= $actionMeta['text'] ?> <?= $actionMeta['border'] ?>">
                                                <i data-lucide="<?= $actionMeta['icon'] ?>" class="w-3.5 h-3.5 flex-shrink-0"></i>
                                                <span class="font-mono tracking-tight"><?= htmlspecialchars($l['action']) ?></span>
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5 pl-0.5 font-medium">
                                                <?= $actionMeta['category'] ?>
                                            </div>
                                        </td>

                                        <!-- Entity Column -->
                                        <td class="py-3 px-3 whitespace-nowrap">
                                            <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-[11px] font-medium">
                                                <span class="text-slate-400"><?= htmlspecialchars($l['entity_type'] ?? 'record') ?></span>
                                                <span class="font-bold text-slate-900 dark:text-white">#<?= htmlspecialchars($l['entity_id'] ?? '0') ?></span>
                                            </span>
                                        </td>

                                        <!-- Summary Column -->
                                        <td class="py-3 px-3">
                                            <div class="text-slate-600 dark:text-slate-300 max-w-xs sm:max-w-sm truncate text-xs font-normal" title="<?= htmlspecialchars($l['details'] ?? '') ?>">
                                                <?= htmlspecialchars($l['details'] ?? '—') ?>
                                            </div>
                                        </td>

                                        <!-- IP Address Column -->
                                        <td class="py-3 px-3 whitespace-nowrap">
                                            <div class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono text-[11px] border border-slate-200 dark:border-slate-700 group-hover:border-slate-300">
                                                <i data-lucide="globe" class="w-3 h-3 text-slate-400"></i>
                                                <span><?= htmlspecialchars($l['ip_address'] ?? '::1') ?></span>
                                                <button type="button" onclick="event.stopPropagation(); copyText('<?= htmlspecialchars($l['ip_address'] ?? '') ?>', 'IP Address copied!')"
                                                        class="ml-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition" title="Copy IP">
                                                    <i data-lucide="copy" class="w-3 h-3"></i>
                                                </button>
                                            </div>
                                        </td>

                                        <!-- Inspect Column -->
                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            <button type="button" onclick="event.stopPropagation(); openAuditInspector(<?= $idx ?>)"
                                                    class="px-2.5 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-300 font-semibold text-xs transition inline-flex items-center space-x-1 shadow-xs border border-indigo-100 dark:border-indigo-900/40">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                <span>Inspect</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Summary Count -->
                <?php if (!empty($logs)): ?>
                    <div class="pt-4 mt-2 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-400 gap-2">
                        <span>Showing <strong><?= count($logs) ?></strong> events sorted by newest first (Limited to latest <?= $limit ?>)</span>
                        <span>Security Audit Trail • Immutable Storage</span>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Slide-in Event Inspector Modal / Drawer -->
    <div id="inspector-modal" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeAuditInspector()"></div>

        <!-- Dialog Box -->
        <div class="fixed inset-y-0 right-0 max-w-xl w-full bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col z-10 overflow-hidden transform transition-transform duration-300">
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-800/40">
                <div class="flex items-center space-x-2.5">
                    <div id="modal-action-badge" class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center space-x-1.5">
                        <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                        <span id="modal-action-text">ACTION_NAME</span>
                    </div>
                    <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Event Details</span>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="copyCurrentLogJson()" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs transition flex items-center space-x-1">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        <span>Copy JSON</span>
                    </button>
                    <button type="button" onclick="closeAuditInspector()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 text-xs">
                
                <!-- Quick Info Bar -->
                <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50">
                    <div class="flex items-center space-x-2 text-indigo-900 dark:text-indigo-200">
                        <i data-lucide="calendar" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                        <span id="modal-timestamp" class="font-semibold">Oct 3, 2026 • 11:36:09 AM</span>
                    </div>
                    <span id="modal-audit-id" class="font-mono font-bold text-indigo-600 dark:text-indigo-400">#AUD-000000</span>
                </div>

                <!-- Metadata Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider block">Initiated By</span>
                        <div id="modal-actor-name" class="font-bold text-slate-900 dark:text-white mt-1 text-sm">Aarav Sharma</div>
                        <div id="modal-actor-sub" class="text-[11px] text-slate-500 mt-0.5">founder@techpulse.io • <span class="font-semibold text-indigo-600">Founder</span></div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider block">Target Entity</span>
                        <div id="modal-entity-type" class="font-bold text-slate-900 dark:text-white mt-1 text-sm">investor_interests</div>
                        <div id="modal-entity-id" class="text-[11px] text-slate-500 mt-0.5">Record ID: <strong>#4</strong></div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider block">Network IP Address</span>
                        <div id="modal-ip-address" class="font-mono font-bold text-slate-800 dark:text-slate-200 mt-1 text-sm">::1</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Origin Remote Client</div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider block">Integrity State</span>
                        <div class="font-bold text-emerald-600 dark:text-emerald-400 mt-1 text-sm flex items-center space-x-1">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            <span>Verified Immutable</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Read-only transaction log</div>
                    </div>
                </div>

                <!-- Payload Content -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400 text-[10.5px] uppercase font-bold tracking-wider">
                            Payload Context & Mutation Details:
                        </span>
                        <button type="button" onclick="copyCurrentLogDetails()" class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline font-semibold flex items-center space-x-1">
                            <i data-lucide="copy" class="w-3 h-3"></i>
                            <span>Copy Details</span>
                        </button>
                    </div>
                    <div id="modal-details-body" class="font-mono text-xs p-4 rounded-xl bg-slate-900 text-slate-200 dark:bg-slate-950 dark:text-emerald-300/90 break-all border border-slate-800 shadow-inner whitespace-pre-wrap leading-relaxed select-all max-h-72 overflow-y-auto">
                        —
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-center justify-between">
                <span class="text-[11px] text-slate-400">Security Audit Trail • Nexora</span>
                <button type="button" onclick="closeAuditInspector()" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-semibold text-xs transition">
                    Close Inspector
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Toast Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none"></div>

    <script>
        lucide.createIcons();

        if (window.gsap) {
            gsap.from("#audit-admin-main", { duration: 0.35, y: 8, opacity: 0, ease: "power2.out" });
        }

        const auditLogsData = <?= json_encode($logs ?? []) ?>;
        let currentInspectedIndex = null;

        function showToast(message, isSuccess = true) {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `px-4 py-2.5 rounded-xl text-xs font-bold text-white shadow-lg pointer-events-auto flex items-center space-x-2 transition-all transform duration-300 translate-y-2 opacity-0 ${
                isSuccess ? 'bg-slate-900 dark:bg-indigo-600' : 'bg-rose-600'
            }`;
            toast.innerHTML = `
                <i data-lucide="${isSuccess ? 'check-circle' : 'alert-circle'}" class="w-4 h-4"></i>
                <span>${message}</span>
            `;
            container.appendChild(toast);
            lucide.createIcons();

            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 2500);
        }

        function copyText(text, successMsg = 'Copied to clipboard!') {
            if (!text) return;
            navigator.clipboard.writeText(text).then(() => {
                showToast(successMsg, true);
            }).catch(() => {
                showToast('Failed to copy', false);
            });
        }

        function openAuditInspector(index) {
            const log = auditLogsData[index];
            if (!log) return;
            currentInspectedIndex = index;

            document.getElementById('modal-action-text').textContent = log.action || 'ACTIVITY';
            document.getElementById('modal-timestamp').textContent = log.created_at || '—';
            document.getElementById('modal-audit-id').textContent = '#AUD-' + String(log.id).padStart(6, '0');
            
            document.getElementById('modal-actor-name').textContent = log.actor_name || 'System / Anonymous';
            document.getElementById('modal-actor-sub').innerHTML = `${log.actor_email || 'No email logged'} • <span class="font-semibold text-indigo-600 dark:text-indigo-400 capitalize">${log.actor_role || 'System'}</span>`;
            
            document.getElementById('modal-entity-type').textContent = log.entity_type || 'None';
            document.getElementById('modal-entity-id').innerHTML = `Record ID: <strong>#${log.entity_id || '0'}</strong>`;
            
            document.getElementById('modal-ip-address').textContent = log.ip_address || '::1';
            
            const detailsBody = document.getElementById('modal-details-body');
            detailsBody.textContent = log.details || 'No extra payload recorded for this operation.';

            const modal = document.getElementById('inspector-modal');
            modal.classList.remove('hidden');
            lucide.createIcons();
        }

        function closeAuditInspector() {
            const modal = document.getElementById('inspector-modal');
            modal.classList.add('hidden');
            currentInspectedIndex = null;
        }

        function copyCurrentLogJson() {
            if (currentInspectedIndex === null) return;
            const log = auditLogsData[currentInspectedIndex];
            if (!log) return;
            copyText(JSON.stringify(log, null, 2), 'Complete Event JSON copied!');
        }

        function copyCurrentLogDetails() {
            if (currentInspectedIndex === null) return;
            const log = auditLogsData[currentInspectedIndex];
            if (!log) return;
            copyText(log.details || '', 'Payload details copied!');
        }

        function exportAuditCSV() {
            if (!auditLogsData || auditLogsData.length === 0) {
                alert("No audit logs available to export.");
                return;
            }

            const headers = ["Timestamp", "Actor Name", "Actor Role", "Actor Email", "Action", "Entity", "Entity ID", "IP Address", "Details"];
            const rows = auditLogsData.map(log => [
                `"${log.created_at || ''}"`,
                `"${(log.actor_name || 'System / Anonymous').replace(/"/g, '""')}"`,
                `"${(log.actor_role || 'Guest').replace(/"/g, '""')}"`,
                `"${(log.actor_email || '').replace(/"/g, '""')}"`,
                `"${(log.action || '').replace(/"/g, '""')}"`,
                `"${(log.entity_type || '').replace(/"/g, '""')}"`,
                `"${log.entity_id || ''}"`,
                `"${(log.ip_address || '').replace(/"/g, '""')}"`,
                `"${(log.details || '').replace(/"/g, '""')}"`
            ]);

            const csvContent = "data:text/csv;charset=utf-8," + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `security_audit_trail_${new Date().toISOString().slice(0, 10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showToast('Audit trail CSV exported successfully!');
        }

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeAuditInspector();
            }
        });
    </script>
</body>
</html>
