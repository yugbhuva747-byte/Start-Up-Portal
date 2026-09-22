<?php
/**
 * Admin Module: Users & Companies Directory
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Users & Entities Directory';

$users = [];
$companies = [];
$flash = get_flash();

if ($db) {
    $uStmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $uStmt->fetchAll();

    $cStmt = $db->query("SELECT * FROM companies ORDER BY created_at DESC");
    $companies = $cStmt->fetchAll();
}

// Handle User Status Toggle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token.';
    } else {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? 'active';

        if ($targetUserId > 0 && in_array($newStatus, ['active', 'suspended'])) {
            $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $targetUserId]);
            log_audit($user['id'], 'UPDATE_USER_STATUS', 'users', $targetUserId, "Admin toggled user status to {$newStatus}");
            set_flash('success', "User status updated to {$newStatus}.");
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="users-admin-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-medium border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
                    <?= htmlspecialchars($flash['message']) ?>
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
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $u['role'] === 'admin' ? 'bg-purple-50 text-purple-700 border border-purple-200' : ($u['role'] === 'founder' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200') ?>">
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

            <!-- Companies Table -->
            <div class="card-clean rounded-2xl p-5 md:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-bold text-slate-800 flex items-center space-x-1.5 uppercase tracking-wider">
                        <i data-lucide="building-2" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Registered Companies (<?= count($companies) ?>)</span>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider text-[10.5px]">
                                <th class="pb-3 font-semibold">Startup Name</th>
                                <th class="pb-3 font-semibold">Sector / Stage</th>
                                <th class="pb-3 font-semibold">Corporate CIN</th>
                                <th class="pb-3 font-semibold">Location</th>
                                <th class="pb-3 font-semibold">Verification Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($companies as $c): ?>
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 font-bold text-slate-900 text-xs"><?= htmlspecialchars($c['name']) ?></td>
                                    <td class="py-3 text-slate-500 text-xs"><?= htmlspecialchars($c['industry']) ?> • <?= htmlspecialchars($c['stage']) ?></td>
                                    <td class="py-3 font-mono text-slate-600 text-xs"><?= htmlspecialchars($c['cin_number'] ?? 'PENDING') ?></td>
                                    <td class="py-3 text-slate-500 text-xs"><?= htmlspecialchars($c['city']) ?>, <?= htmlspecialchars($c['country']) ?></td>
                                    <td class="py-3"><?= render_status_badge($c['verified_status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#users-admin-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
