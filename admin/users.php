<?php
/**
 * Admin Module: Users Directory & Governance
 * Clean, Minimalist User Directory & Permissions Management
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Users Directory';

$users = [];
$flash = get_flash();

$totalUsers = 0;
$totalFounders = 0;
$totalInvestors = 0;
$totalSuspended = 0;

if ($db) {
    try {
        $uStmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
        $users = $uStmt->fetchAll();

        foreach ($users as $u) {
            $totalUsers++;
            if ($u['role'] === 'founder') $totalFounders++;
            if ($u['role'] === 'investor') $totalInvestors++;
            if ($u['status'] === 'suspended') $totalSuspended++;
        }
    } catch (Exception $e) {
        // Fallback
    }
}

// Handle User Status Toggle POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? 'active';

        if ($targetUserId > 0 && in_array($newStatus, ['active', 'suspended'])) {
            if ($targetUserId === (int)$user['id'] && $newStatus === 'suspended') {
                set_flash('error', 'Action Aborted: You cannot suspend your own administrative account.');
                header('Location: ' . url('admin/users.php'));
                exit;
            }
            $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $targetUserId]);
            log_audit($user['id'], 'UPDATE_USER_STATUS', 'users', $targetUserId, "Admin toggled user status to {$newStatus}");
            set_flash('success', "User account status successfully updated to " . ucfirst($newStatus) . ".");
            header('Location: ' . url('admin/users.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Directory • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F8FAFC] text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl w-full mx-auto" id="users-admin-main">
            
            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="admin-page-icon">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            Users Directory
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">Platform founders, angel investors, and account access governance.</p>
                    </div>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" id="stats-grid">
                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Total Users</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-indigo"><?= $totalUsers ?></div>
                    <div class="admin-stat-sub">Registered accounts</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Founders</span>
                        <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                            <i data-lucide="rocket" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-violet"><?= $totalFounders ?></div>
                    <div class="admin-stat-sub">Startup operators</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Investors</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-emerald"><?= $totalInvestors ?></div>
                    <div class="admin-stat-sub">Accredited angels</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Suspended</span>
                        <div class="w-8 h-8 rounded-lg <?= $totalSuspended > 0 ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-400' ?> flex items-center justify-center">
                            <i data-lucide="user-x" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value <?= $totalSuspended > 0 ? 'stat-value-rose' : '' ?>"><?= $totalSuspended ?></div>
                    <div class="admin-stat-sub">Access disabled</div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="admin-card p-3 sm:p-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <!-- Search Input -->
                <div class="admin-search-wrapper w-full sm:w-80">
                    <i data-lucide="search"></i>
                    <input type="text" id="user-search" oninput="filterUsersTable()" placeholder="Search name, email, or city..." 
                           class="admin-input w-full">
                </div>

                <!-- Role Filter Tabs -->
                <div class="admin-filter-bar w-full sm:w-auto overflow-x-auto">
                    <button type="button" onclick="setUserRoleFilter('ALL')" id="filter-role-ALL" class="admin-filter-pill active">
                        All (<?= $totalUsers ?>)
                    </button>
                    <button type="button" onclick="setUserRoleFilter('founder')" id="filter-role-founder" class="admin-filter-pill">
                        Founders (<?= $totalFounders ?>)
                    </button>
                    <button type="button" onclick="setUserRoleFilter('investor')" id="filter-role-investor" class="admin-filter-pill">
                        Investors (<?= $totalInvestors ?>)
                    </button>
                    <button type="button" onclick="setUserRoleFilter('admin')" id="filter-role-admin" class="admin-filter-pill">
                        Admins
                    </button>
                </div>
            </div>

            <!-- Users Table Card -->
            <div class="admin-table-container">
                <div class="overflow-x-auto">
                    <table class="admin-table" id="users-table">
                        <thead>
                            <tr>
                                <th>User Profile</th>
                                <th>Role</th>
                                <th>Plan &amp; Priority</th>
                                <th>Location / Contact</th>
                                <th>KYC Status</th>
                                <th>State</th>
                                <th>Joined</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr class="user-row" 
                                        data-role="<?= htmlspecialchars($u['role']) ?>"
                                        data-search="<?= strtolower(htmlspecialchars($u['name'] . ' ' . $u['email'] . ' ' . ($u['city'] ?? ''))) ?>">
                                        
                                        <!-- User Profile with Avatar -->
                                        <td>
                                            <div class="flex items-center space-x-3">
                                                <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase flex-shrink-0">
                                                    <?= strtoupper(substr($u['name'], 0, 2)) ?>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-semibold text-slate-900 text-xs truncate"><?= htmlspecialchars($u['name']) ?></div>
                                                    <div class="text-[11px] text-slate-400 truncate"><?= htmlspecialchars($u['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Role Pill -->
                                        <td>
                                            <span class="admin-badge <?= $u['role'] === 'admin' ? 'admin-badge-neutral' : ($u['role'] === 'founder' ? 'admin-badge-primary' : 'admin-badge-success') ?>">
                                                <?= ucfirst(htmlspecialchars($u['role'])) ?>
                                            </span>
                                        </td>

                                        <!-- Plan & Priority Level -->
                                        <td>
                                            <?php 
                                                $pLevel = (int)($u['priority_level'] ?? 1);
                                                $plan = $u['current_plan'] ?? 'free_trial';
                                            ?>
                                            <div class="flex items-center gap-1.5">
                                                <?php if ($pLevel === 4): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-gradient-to-r from-amber-500 to-pink-500 text-white shadow-2xs">
                                                        👑 Level 4 (VIP)
                                                    </span>
                                                <?php elseif ($pLevel === 3): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                                                        ⭐ Level 3 (Featured)
                                                    </span>
                                                <?php elseif ($pLevel === 2): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                                        ⚡ Level 2
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700">
                                                        🌱 Level 1
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-[10px] text-slate-500 capitalize mt-0.5 font-medium">
                                                <?= htmlspecialchars(str_replace('_', ' ', $plan)) ?>
                                            </div>
                                        </td>

                                        <!-- Contact / City -->
                                        <td>
                                            <div class="text-slate-800 text-xs"><?= htmlspecialchars($u['city'] ?? 'Bengaluru') ?>, <?= htmlspecialchars($u['country'] ?? 'India') ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($u['phone'] ?: 'No Phone') ?></div>
                                        </td>

                                        <!-- KYC Status -->
                                        <td>
                                            <?php if ($u['is_verified']): ?>
                                                <span class="admin-badge admin-badge-success">
                                                    <span class="admin-badge-dot"></span>
                                                    <span>Verified</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="admin-badge admin-badge-neutral">
                                                    <span class="admin-badge-dot"></span>
                                                    <span>Unverified</span>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Account Status -->
                                        <td>
                                            <span class="admin-badge <?= $u['status'] === 'active' ? 'admin-badge-success' : 'admin-badge-danger' ?>">
                                                <span class="admin-badge-dot"></span>
                                                <span><?= ucfirst($u['status']) ?></span>
                                            </span>
                                        </td>

                                        <!-- Joined Date -->
                                        <td class="text-slate-500 text-xs">
                                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                                        </td>

                                        <!-- Actions -->
                                        <td class="text-right whitespace-nowrap">
                                            <?php if ($u['role'] !== 'admin'): ?>
                                                <a href="<?= url('admin/subscriptions.php?q=' . urlencode($u['email'])) ?>" 
                                                   class="admin-btn-secondary text-[11px] py-1 px-2 hover:text-purple-600 hover:border-purple-200 inline-flex items-center gap-1 mr-1" 
                                                   title="Manage Plan & Priority Level">
                                                    <i data-lucide="crown" class="w-3 h-3 text-amber-500"></i>
                                                    <span>Plan</span>
                                                </a>
                                                <form action="<?= url('admin/users.php') ?>" method="POST" class="inline" 
                                                      onsubmit="return confirm('Change status for <?= addslashes($u['name']) ?> to <?= $u['status'] === 'active' ? 'suspended' : 'active' ?>?');">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="new_status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                                    <button type="submit" 
                                                            class="admin-btn-secondary text-[11px] py-1 px-2.5 <?= $u['status'] === 'active' ? 'hover:text-rose-600 hover:border-rose-200' : 'hover:text-emerald-600 hover:border-emerald-200' ?>">
                                                        <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-slate-400 text-[11px]">Protected</span>
                                            <?php endif; ?>
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
        gsap.from("#users-admin-main", { duration: 0.3, y: 8, opacity: 0, ease: "power2.out" });

        let currentRoleFilter = 'ALL';

        function setUserRoleFilter(role) {
            currentRoleFilter = role;
            const roles = ['ALL', 'founder', 'investor', 'admin'];
            roles.forEach(r => {
                const btn = document.getElementById('filter-role-' + r);
                if (btn) {
                    if (r === role) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
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
                const userRole = row.getAttribute('data-role') || '';

                let matchesRole = currentRoleFilter === 'ALL' || userRole === currentRoleFilter;
                let matchesQuery = query === '' || searchData.includes(query);

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
