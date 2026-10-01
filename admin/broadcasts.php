<?php
/**
 * Admin Module: Platform Broadcasts & Compliance Announcement Center
 * Compose, target, and dispatch system-wide regulatory alerts, compliance notices, and deal updates
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Broadcasts';

$error = '';
$flash = get_flash();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['form_action'] ?? '';

        // 1. Create and dispatch new broadcast
        if ($action === 'create_broadcast') {
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $priority = trim($_POST['priority'] ?? 'update');
            $audience = trim($_POST['target_audience'] ?? 'all');
            $showBanner = isset($_POST['show_banner']) ? 1 : 0;
            $ctaLabel = trim($_POST['cta_label'] ?? '');
            $ctaUrl = trim($_POST['cta_url'] ?? '');
            $expiresAt = !empty($_POST['expires_at']) ? date('Y-m-d H:i:s', strtotime($_POST['expires_at'])) : null;

            if (empty($title) || empty($message)) {
                $error = 'Announcement title and message are required.';
            } else {
                try {
                    // Determine targeted users
                    $targetUserIds = [];
                    if ($audience === 'all') {
                        $targetUserIds = $db->query("SELECT id FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
                    } elseif ($audience === 'founder') {
                        $targetUserIds = $db->query("SELECT id FROM users WHERE role = 'founder' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
                    } elseif ($audience === 'investor') {
                        $targetUserIds = $db->query("SELECT id FROM users WHERE role = 'investor' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
                    } elseif ($audience === 'pending_kyc') {
                        $targetUserIds = $db->query("
                            SELECT DISTINCT u.id 
                            FROM users u 
                            LEFT JOIN verification_requests vr ON u.id = vr.user_id 
                            WHERE u.status = 'active' AND (vr.status IS NULL OR vr.status = 'pending')
                        ")->fetchAll(PDO::FETCH_COLUMN);
                    }

                    $recipientsCount = count($targetUserIds);
                    $imageUrl = trim($_POST['image_url'] ?? '');

                    // Insert broadcast
                    $ins = $db->prepare("
                        INSERT INTO broadcasts 
                        (admin_user_id, title, message, priority, target_audience, show_banner, cta_label, cta_url, image_url, recipients_count, is_active, expires_at, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
                    ");
                    $ins->execute([
                        $user['id'], $title, $message, $priority, $audience, $showBanner,
                        $ctaLabel ?: null, $ctaUrl ?: null, $imageUrl ?: null, $recipientsCount, $expiresAt
                    ]);
                    $broadcastId = $db->lastInsertId();

                    // Dispatch into user in-app notifications
                    $notifType = match($priority) {
                        'urgent' => 'warning',
                        'compliance' => 'warning',
                        'opportunity' => 'success',
                        default => 'info'
                    };

                    $notifStmt = $db->prepare("
                        INSERT INTO notifications (user_id, title, message, type, action_url, is_read, created_at)
                        VALUES (?, ?, ?, ?, ?, 0, NOW())
                    ");

                    foreach ($targetUserIds as $uId) {
                        $notifStmt->execute([
                            $uId,
                            $title,
                            $message,
                            $notifType,
                            $ctaUrl ?: null
                        ]);
                    }

                    log_audit($user['id'], 'DISPATCH_BROADCAST', 'broadcasts', $broadcastId, "Dispatched broadcast '{$title}' to {$recipientsCount} users ({$audience})");
                    set_flash('success', "Announcement broadcast dispatched successfully to {$recipientsCount} users!");
                    header('Location: ' . url('admin/broadcasts.php'));
                    exit;
                } catch (Exception $e) {
                    $error = 'Error dispatching broadcast: ' . $e->getMessage();
                }
            }
        }

        // 2. Toggle active state
        if ($action === 'toggle_active') {
            $bId = (int)($_POST['broadcast_id'] ?? 0);
            $newState = (int)($_POST['new_state'] ?? 0);
            $db->prepare("UPDATE broadcasts SET is_active = ? WHERE id = ?")->execute([$newState, $bId]);
            log_audit($user['id'], 'TOGGLE_BROADCAST_STATUS', 'broadcasts', $bId, "Toggled broadcast #{$bId} active state to {$newState}");
            set_flash('success', "Broadcast status updated successfully.");
            header('Location: ' . url('admin/broadcasts.php'));
            exit;
        }

        // 3. Delete broadcast
        if ($action === 'delete_broadcast') {
            $bId = (int)($_POST['broadcast_id'] ?? 0);
            $db->prepare("DELETE FROM broadcasts WHERE id = ?")->execute([$bId]);
            log_audit($user['id'], 'DELETE_BROADCAST', 'broadcasts', $bId, "Deleted broadcast #{$bId}");
            set_flash('success', "Broadcast deleted successfully.");
            header('Location: ' . url('admin/broadcasts.php'));
            exit;
        }
    }
}

// Fetch stats and broadcasts
$broadcasts = [];
$totalBroadcasts = 0;
$activeBanners = 0;
$totalRecipientsReach = 0;
$urgentAlertsCount = 0;

if ($db) {
    init_broadcasts_table($db);

    $totalBroadcasts = (int)$db->query("SELECT COUNT(*) FROM broadcasts")->fetchColumn();
    $activeBanners = (int)$db->query("SELECT COUNT(*) FROM broadcasts WHERE show_banner = 1 AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())")->fetchColumn();
    $totalRecipientsReach = (int)$db->query("SELECT COALESCE(SUM(recipients_count), 0) FROM broadcasts")->fetchColumn();
    $urgentAlertsCount = (int)$db->query("SELECT COUNT(*) FROM broadcasts WHERE priority IN ('urgent', 'compliance')")->fetchColumn();

    $broadcasts = $db->query("
        SELECT b.*, u.name as admin_name
        FROM broadcasts b
        JOIN users u ON b.admin_user_id = u.id
        ORDER BY b.created_at DESC
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Platform Broadcasts & Announcements • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/admin/head.php'; ?>
    <style>
        .stat-card-clean { 
            background: #FFFFFF; 
            border: 1px solid #E2E8F0; 
            border-radius: 1rem; 
            padding: 1.25rem 1.5rem; 
            box-shadow: 0 1px 3px 0 rgba(0,0,0,0.02); 
            transition: all 0.2s ease-in-out;
        }
        .stat-card-clean:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px -4px rgba(0, 0, 0, 0.05);
        }
        .table-card-clean { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 1rem; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.02); overflow: hidden; }
        .filter-bar-clean { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 1rem; padding: 0.875rem 1.25rem; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.02); }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 flex min-h-screen dark:bg-[#0b0f19] dark:text-slate-100 font-sans antialiased">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">

        <!-- Admin Navbar -->
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="broadcasts-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800' ?> flex items-center space-x-2 shadow-xs">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 flex items-center space-x-2 shadow-xs">
                    <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200/80 dark:border-indigo-800/80 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-xs flex-shrink-0">
                        <i data-lucide="megaphone" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                Platform Broadcasts
                            </h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                Global Dispatch
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Publish compliance bulletins, regulatory updates, and platform notices to user inboxes and banners.
                        </p>
                    </div>
                </div>
                
                <div>
                    <button onclick="document.getElementById('composeModal').classList.remove('hidden')" 
                            class="admin-btn-primary">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Compose Broadcast</span>
                    </button>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Dispatched</span>
                        <div class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <i data-lucide="megaphone" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-2"><?= $totalBroadcasts ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Platform announcements</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Active Banners</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="radio" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2"><?= $activeBanners ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Live on user dashboards</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Audience Reach</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-2"><?= number_format($totalRecipientsReach) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cumulative inbox deliveries</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Urgent Bulletins</span>
                        <div class="w-9 h-9 rounded-xl <?= $urgentAlertsCount > 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' ?> flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black <?= $urgentAlertsCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' ?> mt-2"><?= $urgentAlertsCount ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">High-priority compliance</div>
                </div>
            </div>

            <!-- Broadcasts Management Table -->
            <div class="space-y-3">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">
                    Dispatched Announcements & Banners
                </h2>

                <div class="admin-table-container">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Priority</th>
                                    <th>Title & Message</th>
                                    <th>Target Audience</th>
                                    <th>Banner State</th>
                                    <th>Reach</th>
                                    <th>Date</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($broadcasts)): ?>
                                    <tr>
                                        <td colspan="7" class="py-12 text-center text-slate-400">
                                            No platform broadcasts composed yet. Click "Compose Broadcast" above to create an announcement.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($broadcasts as $b): 
                                        $badgeClass = match($b['priority']) {
                                            'urgent' => 'admin-badge-danger',
                                            'compliance' => 'admin-badge-warning',
                                            'opportunity' => 'admin-badge-success',
                                            default => 'admin-badge-primary'
                                        };
                                        $audLabel = match($b['target_audience']) {
                                            'all' => 'All Users',
                                            'founder' => 'Founders',
                                            'investor' => 'Investors',
                                            'pending_kyc' => 'Pending KYC',
                                            default => 'General'
                                        };
                                        $isExpired = $b['expires_at'] && strtotime($b['expires_at']) < time();
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="admin-badge <?= $badgeClass ?> text-[10px]">
                                                    <span class="admin-badge-dot"></span>
                                                    <span><?= strtoupper(htmlspecialchars($b['priority'])) ?></span>
                                                </span>
                                            </td>
                                            <td class="max-w-md">
                                                <div class="font-semibold text-slate-900 text-xs"><?= htmlspecialchars($b['title']) ?></div>
                                                <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1"><?= htmlspecialchars($b['message']) ?></div>
                                                <?php if ($b['cta_label']): ?>
                                                    <div class="text-[10px] text-indigo-600 font-medium mt-1">
                                                        CTA: <?= htmlspecialchars($b['cta_label']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="admin-badge admin-badge-neutral text-[10px]">
                                                    <?= $audLabel ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($b['show_banner'] && $b['is_active'] && !$isExpired): ?>
                                                    <span class="admin-badge admin-badge-success text-[10px]">
                                                        <span class="admin-badge-dot"></span>
                                                        <span>Live Banner</span>
                                                    </span>
                                                <?php elseif ($isExpired): ?>
                                                    <span class="text-slate-400 text-xs">Expired</span>
                                                <?php elseif (!$b['show_banner']): ?>
                                                    <span class="text-slate-400 text-xs">Inbox Only</span>
                                                <?php else: ?>
                                                    <span class="admin-badge admin-badge-neutral text-[10px]">Disabled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="font-mono text-slate-800 text-xs">
                                                <?= number_format($b['recipients_count']) ?> users
                                            </td>
                                            <td class="text-slate-500 text-xs whitespace-nowrap">
                                                <div><?= date('d M Y', strtotime($b['created_at'])) ?></div>
                                            </td>
                                            <td class="text-right whitespace-nowrap">
                                                <!-- Toggle State -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="form_action" value="toggle_active">
                                                    <input type="hidden" name="broadcast_id" value="<?= $b['id'] ?>">
                                                    <input type="hidden" name="new_state" value="<?= $b['is_active'] ? 0 : 1 ?>">
                                                    <button type="submit" 
                                                            class="admin-btn-ghost p-1.5 <?= $b['is_active'] ? 'text-emerald-600' : 'text-slate-400' ?>" 
                                                            title="<?= $b['is_active'] ? 'Disable Banner' : 'Activate Banner' ?>">
                                                        <i data-lucide="<?= $b['is_active'] ? 'eye' : 'eye-off' ?>" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </form>

                                                <!-- Delete -->
                                                <form method="POST" class="inline" onsubmit="return confirm('Delete this broadcast announcement?');">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="form_action" value="delete_broadcast">
                                                    <input type="hidden" name="broadcast_id" value="<?= $b['id'] ?>">
                                                    <button type="submit" class="admin-btn-ghost p-1.5 hover:text-rose-600" title="Delete">
                                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Compose Broadcast Modal -->
    <div id="composeModal" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 relative">
            <button onclick="document.getElementById('composeModal').classList.add('hidden')" 
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="flex items-center space-x-3 mb-4 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center">
                    <i data-lucide="megaphone" class="w-4 h-4 text-white"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Compose Announcement</h3>
                    <p class="text-[11px] text-slate-400">Dispatch message to inboxes and top dashboard banner.</p>
                </div>
            </div>

            <form method="POST" action="<?= url('admin/broadcasts.php') ?>" class="space-y-3.5 text-xs">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="form_action" value="create_broadcast">

                <div>
                    <label class="block font-semibold text-slate-700 mb-1 text-xs">Announcement Title</label>
                    <input type="text" name="title" required placeholder="e.g., Scheduled Maintenance / Regulatory Update" 
                           class="admin-input w-full">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1 text-xs">Message</label>
                    <textarea name="message" rows="3" required placeholder="Type the announcement details here..." 
                              class="admin-input w-full"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1 text-xs">Priority</label>
                        <select name="priority" class="admin-input w-full">
                            <option value="update">General Update</option>
                            <option value="compliance">Compliance Notice</option>
                            <option value="opportunity">Investment Opportunity</option>
                            <option value="urgent">Urgent Alert</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1 text-xs">Target Audience</label>
                        <select name="target_audience" class="admin-input w-full">
                            <option value="all">All Users</option>
                            <option value="founder">Founders Only</option>
                            <option value="investor">Investors Only</option>
                            <option value="pending_kyc">Pending KYC Users</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1 text-xs">CTA Button Label (Optional)</label>
                        <input type="text" name="cta_label" placeholder="e.g., View Round" class="admin-input w-full">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1 text-xs">CTA Target URL</label>
                        <input type="text" name="cta_url" placeholder="e.g., founder/verification.php" class="admin-input w-full">
                    </div>
                </div>

                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" name="show_banner" id="show_banner_check" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="show_banner_check" class="text-xs text-slate-700 font-medium">Show persistent banner on user dashboard</label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('composeModal').classList.add('hidden')" 
                            class="admin-btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="admin-btn-primary">
                        <span>Dispatch Broadcast</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#broadcasts-main", { duration: 0.3, y: 8, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
