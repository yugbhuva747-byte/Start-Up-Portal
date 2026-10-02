<?php
/**
 * Admin Module: Users Directory & Governance
 * Clean, Executive User Directory & Permissions Management
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Users & Entity Directory';

$users = [];
$error = '';
$flash = get_flash();

$totalUsers = 0;
$totalFounders = 0;
$totalInvestors = 0;
$totalAdmins = 0;
$totalSuspended = 0;

// Handle User Status Toggle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newStatus = trim($_POST['new_status'] ?? 'active');

        if ($targetUserId > 0 && in_array($newStatus, ['active', 'suspended'])) {
            try {
                $db->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $targetUserId]);
                log_audit($user['id'], 'UPDATE_USER_STATUS', 'users', $targetUserId, "Admin toggled user status to {$newStatus}");
                set_flash('success', "User account status successfully updated to " . ucfirst($newStatus) . ".");
                header('Location: ' . url('admin/users.php'));
                exit;
            } catch (Exception $e) {
                $error = 'Failed to update user status: ' . $e->getMessage();
            }
        }
    }
}

if ($db) {
    try {
        $uStmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
        $users = $uStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $u) {
            $totalUsers++;
            if ($u['role'] === 'founder') $totalFounders++;
            elseif ($u['role'] === 'investor') $totalInvestors++;
            elseif ($u['role'] === 'admin') $totalAdmins++;

            if ($u['status'] === 'suspended') $totalSuspended++;
        }
    } catch (Exception $e) {
        $error = 'Failed to load users: ' . $e->getMessage();
    }
}
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
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
        }
        .dark .stat-card-clean, html.dark .stat-card-clean { background: #111827 !important; border-color: #1e293b !important; }
        .filter-bar-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 0.875rem 1.25rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        }
        .dark .filter-bar-clean, html.dark .filter-bar-clean { background: #111827 !important; border-color: #1e293b !important; }
        .table-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }
        .dark .table-card-clean, html.dark .table-card-clean { background: #111827 !important; border-color: #1e293b !important; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 flex min-h-screen dark:bg-[#0b0f19] dark:text-slate-100 font-sans antialiased">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6" id="users-admin-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-sm font-medium border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800' ?> flex items-center space-x-3 shadow-sm">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-4 rounded-xl text-sm font-medium border bg-rose-50 text-rose-800 border-rose-200 flex items-center space-x-3 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200/80 dark:border-indigo-800/80 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-sm flex-shrink-0">
                        <i data-lucide="users-round" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Users & Entity Directory
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            Manage platform participants, govern role authorizations, verify credentials, and manage account statuses.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="window.location.reload()" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Refresh Users</span>
                    </button>
                    <a href="<?= url('admin/subscriptions.php') ?>" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                        <i data-lucide="crown" class="w-3.5 h-3.5"></i>
                        <span>Manage Subscriptions</span>
                    </a>
                </div>
            </div>

            <!-- Top Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Users</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-2"><?= number_format($totalUsers) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Platform members</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Founders</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <i data-lucide="rocket" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-2"><?= number_format($totalFounders) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Startup operators</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Investors</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2"><?= number_format($totalInvestors) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Accredited angels & VCs</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800 <?= $totalSuspended > 0 ? 'ring-1 ring-rose-400/50' : '' ?>">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Suspended</span>
                        <div class="w-8 h-8 rounded-lg <?= $totalSuspended > 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-slate-100 text-slate-400 dark:bg-slate-800' ?> flex items-center justify-center">
                            <i data-lucide="user-x" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold <?= $totalSuspended > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' ?> mt-2"><?= number_format($totalSuspended) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Access restricted</div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="filter-bar-clean dark:bg-slate-900 dark:border-slate-800 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                <div class="relative w-full sm:w-80">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="user-search" oninput="filterUsersTable()" 
                           placeholder="Search name, email, phone, city..." 
                           class="w-full pl-9 pr-3.5 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition">
                </div>

                <!-- Role Filter Tabs -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                    <button type="button" onclick="setUserRoleFilter('ALL')" id="filter-role-ALL" 
                            class="user-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white shadow-sm transition">
                        All (<?= $totalUsers ?>)
                    </button>
                    <button type="button" onclick="setUserRoleFilter('founder')" id="filter-role-founder" 
                            class="user-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Founders (<?= $totalFounders ?>)
                    </button>
                    <button type="button" onclick="setUserRoleFilter('investor')" id="filter-role-investor" 
                            class="user-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Investors (<?= $totalInvestors ?>)
                    </button>
                    <button type="button" onclick="setUserRoleFilter('admin')" id="filter-role-admin" 
                            class="user-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Admins (<?= $totalAdmins ?>)
                    </button>
                    <button type="button" onclick="setUserRoleFilter('suspended')" id="filter-role-suspended" 
                            class="user-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Suspended (<?= $totalSuspended ?>)
                    </button>
                </div>
            </div>

            <!-- Users Master Table Card -->
            <div class="table-card-clean dark:bg-slate-900 dark:border-slate-800">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse" id="users-table">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700/80 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                                <th class="py-3.5 px-4">User Profile</th>
                                <th class="py-3.5 px-4">Role</th>
                                <th class="py-3.5 px-4">Plan & Priority</th>
                                <th class="py-3.5 px-4">Location & Contact</th>
                                <th class="py-3.5 px-4">KYC Status</th>
                                <th class="py-3.5 px-4">Account State</th>
                                <th class="py-3.5 px-4">Registered</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody" class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="py-16 text-center text-slate-400 dark:text-slate-500">
                                        <i data-lucide="users" class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2"></i>
                                        <p class="font-medium text-sm">No registered user accounts found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): 
                                    $pLevel = (int)($u['priority_level'] ?? 1);
                                    $plan = $u['current_plan'] ?? 'free_trial';
                                    $isSuspended = ($u['status'] === 'suspended');
                                ?>
                                    <tr class="user-row hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                                        data-role="<?= htmlspecialchars($u['role']) ?>"
                                        data-is-suspended="<?= $isSuspended ? 'true' : 'false' ?>"
                                        data-search="<?= strtolower(htmlspecialchars($u['name'] . ' ' . $u['email'] . ' ' . ($u['city'] ?? '') . ' ' . ($u['phone'] ?? '') . ' ' . $u['role'])) ?>">
                                        
                                        <!-- User Profile with Avatar -->
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-full bg-slate-900 dark:bg-slate-700 text-white flex items-center justify-center font-bold text-xs uppercase flex-shrink-0 shadow-xs">
                                                    <?= strtoupper(substr($u['name'], 0, 2)) ?>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-slate-900 dark:text-white text-xs truncate"><?= htmlspecialchars($u['name']) ?></div>
                                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 truncate"><?= htmlspecialchars($u['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Role Badge -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <?php if ($u['role'] === 'founder'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800">
                                                    Founder
                                                </span>
                                            <?php elseif ($u['role'] === 'investor'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                                    Investor
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800">
                                                    Admin
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Plan & Priority Level -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <div class="flex items-center gap-1.5">
                                                <?php if ($pLevel === 4): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-extrabold bg-gradient-to-r from-amber-500 to-pink-500 text-white shadow-2xs">
                                                        👑 Level 4 VIP
                                                    </span>
                                                <?php elseif ($pLevel === 3): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                                                        ⭐ Level 3
                                                    </span>
                                                <?php elseif ($pLevel === 2): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                                        ⚡ Level 2
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                        🌱 Level 1
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400 capitalize mt-0.5 font-medium">
                                                <?= htmlspecialchars(str_replace('_', ' ', $plan)) ?>
                                            </div>
                                        </td>

                                        <!-- Location / Contact -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <div class="text-slate-800 dark:text-slate-200 text-xs font-medium">
                                                <?= htmlspecialchars($u['city'] ?? 'Bengaluru') ?>, <?= htmlspecialchars($u['country'] ?? 'India') ?>
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                <?= htmlspecialchars($u['phone'] ?: 'No Phone') ?>
                                            </div>
                                        </td>

                                        <!-- KYC Status -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <?php if (!empty($u['is_verified'])): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                                    Verified
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">
                                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                                    Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Account State -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <?php if ($u['status'] === 'active'): ?>
                                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400">
                                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                                    Suspended
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Registration Date -->
                                        <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400 text-xs">
                                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                                        </td>

                                        <!-- Action Buttons -->
                                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5">
                                                <a href="<?= url('admin/subscriptions.php?search=' . urlencode($u['email'])) ?>" 
                                                   class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                                                   title="Manage Plan & Priority">
                                                    Plan
                                                </a>

                                                <?php if ($u['role'] !== 'admin'): ?>
                                                    <form action="<?= url('admin/users.php') ?>" method="POST" class="inline" onsubmit="return confirm('Change status for this account?');">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                        <input type="hidden" name="new_status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                                        <button type="submit" 
                                                                class="px-2.5 py-1 text-xs font-semibold rounded-lg transition <?= $u['status'] === 'active' ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800' ?>">
                                                            <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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

    <script>
        lucide.createIcons();

        let currentRoleFilter = 'ALL';

        function setUserRoleFilter(role) {
            currentRoleFilter = role;
            const buttons = ['ALL', 'founder', 'investor', 'admin', 'suspended'];
            buttons.forEach(b => {
                const btn = document.getElementById('filter-role-' + b);
                if (btn) {
                    if (b === role) {
                        btn.className = 'user-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white shadow-sm transition';
                    } else {
                        btn.className = 'user-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition';
                    }
                }
            });
            filterUsersTable();
        }

        function filterUsersTable() {
            const query = (document.getElementById('user-search')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.user-row');

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                const role = row.getAttribute('data-role') || '';
                const isSuspended = row.getAttribute('data-is-suspended') === 'true';

                let matchesRole = true;
                if (currentRoleFilter === 'founder') matchesRole = (role === 'founder');
                else if (currentRoleFilter === 'investor') matchesRole = (role === 'investor');
                else if (currentRoleFilter === 'admin') matchesRole = (role === 'admin');
                else if (currentRoleFilter === 'suspended') matchesRole = isSuspended;

                const matchesQuery = query === '' || searchData.includes(query);

                if (matchesRole && matchesQuery) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
