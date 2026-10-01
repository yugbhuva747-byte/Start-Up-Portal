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

    <title>Users & Entities • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/admin/head.php'; ?>
    <style>
        body { font-family: "Vay Portal", Sans-serif; }
        .card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-[#F4F2EE] text-slate-900 flex min-h-screen">

    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>


        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="users-admin-main">

            
            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>


            <div>
                <h1 class="text-lg md:text-xl font-extrabold text-slate-900 tracking-tight">Platform Users & Entity Directory</h1>
                <p class="text-xs text-slate-500 mt-0.5">Manage platform participants, role classifications, and active security status.</p>
            </div>

            <!-- Users Table -->
            <div class="card-clean rounded-2xl p-5 md:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-bold text-slate-800 flex items-center space-x-1.5 uppercase tracking-wider">
                        <i data-lucide="users" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Registered User Accounts (<?= count($users) ?>)</span>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider text-[10.5px]">
                                <th class="pb-3 font-semibold">User</th>
                                <th class="pb-3 font-semibold">Role</th>
                                <th class="pb-3 font-semibold">Contact / City</th>
                                <th class="pb-3 font-semibold">KYC Status</th>
                                <th class="pb-3 font-semibold">Account Status</th>
                                <th class="pb-3 font-semibold">Registered</th>
                                <th class="pb-3 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 flex items-center space-x-2.5">
                                        <img src="<?= $u['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80' ?>" class="w-7 h-7 rounded-full object-cover border border-slate-200">
                                        <div>
                                            <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($u['name']) ?></div>
                                            <div class="text-[11px] text-slate-400"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $u['role'] === 'admin' ? 'bg-blue-50 text-blue-700 border border-blue-200' : ($u['role'] === 'founder' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200') ?>">
                                            <?= strtoupper($u['role']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 text-slate-500 text-xs">
                                        <?= htmlspecialchars($u['phone'] ?? '—') ?>
                                        <div class="text-[10.5px] text-slate-400"><?= htmlspecialchars($u['city'] ?? '') ?></div>
                                    </td>
                                    <td class="py-3"><?= render_status_badge($u['is_verified'] ? 'VERIFIED' : 'PENDING') ?></td>
                                    <td class="py-3">
                                        <span class="font-semibold text-xs <?= $u['status'] === 'active' ? 'text-emerald-600' : 'text-rose-600' ?>">
                                            <?= ucfirst($u['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 text-slate-500 text-xs"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                    <td class="py-3 text-right">
                                        <?php if ($u['role'] !== 'admin'): ?>
                                            <form action="<?= url('admin/users.php') ?>" method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="new_status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold <?= $u['status'] === 'active' ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' ?> transition">
                                                    <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

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
