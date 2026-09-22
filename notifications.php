<?php
/**
 * Global In-App Notifications Center
 * Implements Section 13 (Founder & Investor Notifications)
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/config.php';
$user = require_auth();
$db = get_db();
$pageTitle = 'Notifications & Activity Alerts';

$role = $user['role'];
$filter = $_GET['filter'] ?? 'all';
$flash = get_flash();

// Handle POST actions (Mark as read, mark all, delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security token invalid.');
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'mark_all_read') {
            $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user['id']]);
            set_flash('success', 'All notifications marked as read.');
            header('Location: ' . url('notifications.php?filter=' . urlencode($filter)));
            exit;
        } elseif ($action === 'mark_read') {
            $notifId = (int)($_POST['notif_id'] ?? 0);
            $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$notifId, $user['id']]);
            set_flash('success', 'Notification marked as read.');
            header('Location: ' . url('notifications.php?filter=' . urlencode($filter)));
            exit;
        } elseif ($action === 'delete') {
            $notifId = (int)($_POST['notif_id'] ?? 0);
            $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")->execute([$notifId, $user['id']]);
            set_flash('success', 'Notification removed.');
            header('Location: ' . url('notifications.php?filter=' . urlencode($filter)));
            exit;
        }
    }
}

// Fetch notifications with filter
$query = "SELECT * FROM notifications WHERE user_id = ?";
$params = [$user['id']];

if ($filter === 'unread') {
    $query .= " AND is_read = 0";
} elseif ($filter === 'investment') {
    $query .= " AND (title LIKE '%Invest%' OR title LIKE '%Funding%' OR title LIKE '%Capital%' OR type = 'investment')";
} elseif ($filter === 'verification') {
    $query .= " AND (title LIKE '%Verify%' OR title LIKE '%KYC%' OR title LIKE '%DigiLocker%' OR type = 'verification')";
}

$query .= " ORDER BY created_at DESC LIMIT 50";
$nStmt = $db->prepare($query);
$nStmt->execute($params);
$notifications = $nStmt->fetchAll();

// Counts
$totalCount = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$totalCount->execute([$user['id']]);
$total = (int)$totalCount->fetchColumn();

$unreadCount = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadCount->execute([$user['id']]);
$unread = (int)$unreadCount->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">

    <!-- Role-Adapted Sidebar -->
    <?php if ($role === 'founder'): ?>
        <?php include __DIR__ . '/includes/founder/sidebar.php'; ?>
    <?php elseif ($role === 'investor'): ?>
        <?php include __DIR__ . '/includes/investor/sidebar.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/includes/admin/sidebar.php'; ?>
    <?php endif; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- Role-Adapted Navbar -->
        <?php if ($role === 'founder'): ?>
            <?php include __DIR__ . '/includes/founder/navbar.php'; ?>
        <?php elseif ($role === 'investor'): ?>
            <?php include __DIR__ . '/includes/investor/navbar.php'; ?>
        <?php else: ?>
            <?php include __DIR__ . '/includes/admin/navbar.php'; ?>
        <?php endif; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-5xl w-full mx-auto" id="notif-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center space-x-2.5">
                        <i data-lucide="bell" class="w-6 h-6 text-indigo-600"></i>
                        <span>Notifications & Alerts</span>
                        <?php if ($unread > 0): ?>
                            <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-black">
                                <?= $unread ?> Unread
                            </span>
                        <?php endif; ?>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">Real-time alerts on deals, funding milestones, KYC approvals, and chat inquiries.</p>
                </div>

                <?php if ($unread > 0): ?>
                    <form action="<?= url('notifications.php?filter=' . urlencode($filter)) ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="form_action" value="mark_all_read">
                        <button type="submit" class="px-3.5 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-sm transition flex items-center space-x-1.5">
                            <i data-lucide="check-check" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span>Mark All as Read</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Filter Tabs -->
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3 text-xs font-semibold">
                <a href="<?= url('notifications.php?filter=all') ?>" 
                   class="px-3 py-1.5 rounded-lg transition <?= $filter === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                    All (<?= $total ?>)
                </a>
                <a href="<?= url('notifications.php?filter=unread') ?>" 
                   class="px-3 py-1.5 rounded-lg transition <?= $filter === 'unread' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                    Unread (<?= $unread ?>)
                </a>
                <a href="<?= url('notifications.php?filter=investment') ?>" 
                   class="px-3 py-1.5 rounded-lg transition <?= $filter === 'investment' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                    Funding & Deals
                </a>
                <a href="<?= url('notifications.php?filter=verification') ?>" 
                   class="px-3 py-1.5 rounded-lg transition <?= $filter === 'verification' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                    KYC & Verification
                </a>
            </div>

            <!-- Notifications List -->
            <div class="card-clean rounded-2xl overflow-hidden divide-y divide-slate-100">
                <?php if (empty($notifications)): ?>
                    <div class="p-12 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-3">
                            <i data-lucide="bell-off" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">No notifications found</h3>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">You're all caught up! When transactions, inquiries, or round milestones occur, alerts will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <div class="p-4 sm:p-5 flex items-start justify-between gap-4 hover:bg-slate-50/70 transition <?= !$n['is_read'] ? 'bg-indigo-50/20' : '' ?>">
                            <div class="flex items-start space-x-3.5 flex-1 min-w-0">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5 <?= !$n['is_read'] ? 'bg-indigo-100 text-indigo-700 font-bold' : 'bg-slate-100 text-slate-500' ?>">
                                    <?php if (str_contains(strtolower($n['title']), 'invest')): ?>
                                        <i data-lucide="circle-dollar-sign" class="w-4 h-4 text-emerald-600"></i>
                                    <?php elseif (str_contains(strtolower($n['title']), 'verif') || str_contains(strtolower($n['title']), 'kyc')): ?>
                                        <i data-lucide="shield-check" class="w-4 h-4 text-indigo-600"></i>
                                    <?php elseif (str_contains(strtolower($n['title']), 'message') || str_contains(strtolower($n['title']), 'chat')): ?>
                                        <i data-lucide="message-square" class="w-4 h-4 text-sky-600"></i>
                                    <?php else: ?>
                                        <i data-lucide="bell" class="w-4 h-4 text-slate-600"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-xs font-bold text-slate-900 tracking-tight"><?= htmlspecialchars($n['title']) ?></h3>
                                        <?php if (!$n['is_read']): ?>
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 flex-shrink-0"></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-slate-600 mt-0.5 leading-relaxed"><?= htmlspecialchars($n['message']) ?></p>
                                    <div class="flex flex-wrap items-center gap-3 text-[10.5px] text-slate-400 mt-2">
                                        <span class="flex items-center space-x-1">
                                            <i data-lucide="clock" class="w-3 h-3"></i>
                                            <span><?= date('M d, Y • h:i A', strtotime($n['created_at'])) ?></span>
                                        </span>
                                        <?php if (!empty($n['action_url'])): ?>
                                            <span>•</span>
                                            <a href="<?= url($n['action_url']) ?>" class="font-bold text-indigo-600 hover:text-indigo-700 flex items-center space-x-1">
                                                <span>View details</span>
                                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center space-x-1 flex-shrink-0">
                                <?php if (!$n['is_read']): ?>
                                    <form action="<?= url('notifications.php?filter=' . urlencode($filter)) ?>" method="POST" title="Mark as Read">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="form_action" value="mark_read">
                                        <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form action="<?= url('notifications.php?filter=' . urlencode($filter)) ?>" method="POST" title="Delete notification" onsubmit="return confirm('Remove this notification?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#notif-main", { duration: 0.35, y: 10, opacity: 0, ease: "power2.out" });
    </script>
    <?php include_once __DIR__ . '/includes/smooth_scroll.php'; ?>
</body>
</html>
