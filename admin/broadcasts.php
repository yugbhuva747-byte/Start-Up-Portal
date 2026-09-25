<?php
/**
 * Admin Module: Platform Broadcasts & Compliance Announcement Center
 * Compose, target, and dispatch system-wide regulatory alerts, compliance notices, and deal updates
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Platform Broadcasts & Announcements';

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
            log_audit($user['id'], 'TOGGLE_BROADCAST', 'broadcasts', $bId, "Changed active state to {$newState}");
            set_flash('success', 'Broadcast banner display updated.');
            header('Location: ' . url('admin/broadcasts.php'));
            exit;
        }

        // 3. Delete broadcast
        if ($action === 'delete_broadcast') {
            $bId = (int)($_POST['broadcast_id'] ?? 0);
            $db->prepare("DELETE FROM broadcasts WHERE id = ?")->execute([$bId]);
            log_audit($user['id'], 'DELETE_BROADCAST', 'broadcasts', $bId, "Deleted broadcast #{$bId}");
            set_flash('success', 'Broadcast announcement removed.');
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
    $totalBroadcasts = (int)$db->query("SELECT COUNT(*) FROM broadcasts")->fetchColumn();
    $activeBanners = (int)$db->query("SELECT COUNT(*) FROM broadcasts WHERE is_active = 1 AND show_banner = 1 AND (expires_at IS NULL OR expires_at > NOW())")->fetchColumn();
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean { background: #FFFFFF; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.03); }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- Admin Navbar -->
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="broadcasts-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Platform Broadcast & Announcement Center</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Publish targeted compliance bulletins, SEBI regulatory notices, and deal flow announcements.</p>
                </div>
                
                <div class="flex items-center space-x-2.5">
                    <button onclick="document.getElementById('composeModal').classList.remove('hidden')" 
                            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition flex items-center space-x-1.5 shadow-sm shadow-indigo-600/20">
                        <i data-lucide="megaphone" class="w-3.5 h-3.5"></i>
                        <span>Compose Broadcast</span>
                    </button>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Announcements</div>
                    <div class="text-lg font-black text-slate-900"><?= $totalBroadcasts ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">Dispatched platform notices</div>
                </div>

                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Active Dashboard Banners</div>
                    <div class="text-lg font-black text-emerald-600"><?= $activeBanners ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">Live on user workspaces</div>
                </div>

                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Cumulative Audience Reach</div>
                    <div class="text-lg font-black text-indigo-600"><?= number_format($totalRecipientsReach) ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">In-app inbox deliveries</div>
                </div>

                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Compliance & Urgent Alerts</div>
                    <div class="text-lg font-black text-amber-600"><?= $urgentAlertsCount ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">High-priority regulatory notices</div>
                </div>
            </div>

            <!-- Broadcasts Management Table -->
            <div class="card-clean rounded-2xl p-5 md:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="radio" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Broadcast Dispatches & Live Banners</span>
                        </h2>
                        <p class="text-[11px] text-slate-400 mt-0.5">All broadcast notices dispatched to user inboxes and dashboard banners.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="pb-3">Priority</th>
                                <th class="pb-3">Announcement Title & Message</th>
                                <th class="pb-3">Audience Target</th>
                                <th class="pb-3">Banner State</th>
                                <th class="pb-3">Audience Reach</th>
                                <th class="pb-3">Created / Expires</th>
                                <th class="pb-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($broadcasts)): ?>
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                                        No platform broadcasts composed yet. Click "Compose Broadcast" above to create an announcement.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($broadcasts as $b): 
                                    $pColor = match($b['priority']) {
                                        'urgent' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'compliance' => 'bg-amber-50 text-amber-800 border-amber-200',
                                        'opportunity' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        default => 'bg-indigo-50 text-indigo-700 border-indigo-200'
                                    };
                                    $pIcon = match($b['priority']) {
                                        'urgent' => 'alert-triangle',
                                        'compliance' => 'shield-alert',
                                        'opportunity' => 'sparkles',
                                        default => 'info'
                                    };
                                    $audLabel = match($b['target_audience']) {
                                        'all' => 'All Platform Users',
                                        'founder' => 'Founders Only',
                                        'investor' => 'Investors Only',
                                        'pending_kyc' => 'Pending KYC Users',
                                        default => 'General Audience'
                                    };
                                    $isExpired = $b['expires_at'] && strtotime($b['expires_at']) < time();
                                ?>
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3.5">
                                            <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $pColor ?>">
                                                <i data-lucide="<?= $pIcon ?>" class="w-3 h-3"></i>
                                                <span class="uppercase"><?= htmlspecialchars($b['priority']) ?></span>
                                            </span>
                                        </td>
                                        <td class="py-3.5 max-w-md">
                                            <div class="flex items-start space-x-3">
                                                <?php if (!empty($b['image_url'])): ?>
                                                    <img src="<?= htmlspecialchars($b['image_url']) ?>" alt="Banner visual" class="w-12 h-12 rounded-lg object-cover border border-slate-200 flex-shrink-0 shadow-sm">
                                                <?php endif; ?>
                                                <div class="min-w-0 flex-1">
                                                    <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($b['title']) ?></div>
                                                    <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-2"><?= htmlspecialchars($b['message']) ?></div>
                                                    <?php if ($b['cta_label']): ?>
                                                        <div class="text-[10px] text-indigo-600 font-semibold mt-1 flex items-center space-x-1">
                                                            <span>CTA: <?= htmlspecialchars($b['cta_label']) ?></span>
                                                            <span class="text-slate-400 font-mono">(<?= htmlspecialchars($b['cta_url']) ?>)</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3.5">
                                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold text-[10.5px]">
                                                <?= $audLabel ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5">
                                            <?php if ($b['show_banner'] && $b['is_active'] && !$isExpired): ?>
                                                <span class="inline-flex items-center space-x-1 text-emerald-600 font-bold text-[10.5px]">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span>Live Banner</span>
                                                </span>
                                            <?php elseif ($isExpired): ?>
                                                <span class="text-slate-400 text-[10.5px]">Expired</span>
                                            <?php elseif (!$b['show_banner']): ?>
                                                <span class="text-slate-400 text-[10.5px]">Inbox Only</span>
                                            <?php else: ?>
                                                <span class="text-amber-600 text-[10.5px]">Disabled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 font-mono text-slate-800 font-bold">
                                            <?= number_format($b['recipients_count']) ?> Users
                                        </td>
                                        <td class="py-3.5 text-[10.5px] text-slate-500">
                                            <div><?= date('d M Y', strtotime($b['created_at'])) ?></div>
                                            <?php if ($b['expires_at']): ?>
                                                <div class="text-[9.5px] text-slate-400">Exp: <?= date('d M Y', strtotime($b['expires_at'])) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 text-right space-x-1.5 whitespace-nowrap">
                                            <!-- Toggle Active State -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="form_action" value="toggle_active">
                                                <input type="hidden" name="broadcast_id" value="<?= $b['id'] ?>">
                                                <input type="hidden" name="new_state" value="<?= $b['is_active'] ? 0 : 1 ?>">
                                                <button type="submit" 
                                                        class="p-1.5 rounded-lg border border-slate-200 <?= $b['is_active'] ? 'text-emerald-600 hover:bg-emerald-50' : 'text-slate-400 hover:bg-slate-100' ?> transition" 
                                                        title="<?= $b['is_active'] ? 'Disable Banner' : 'Activate Banner' ?>">
                                                    <i data-lucide="<?= $b['is_active'] ? 'eye' : 'eye-off' ?>" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </form>

                                            <!-- Delete Broadcast -->
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this announcement?')">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="form_action" value="delete_broadcast">
                                                <input type="hidden" name="broadcast_id" value="<?= $b['id'] ?>">
                                                <button type="submit" class="p-1.5 rounded-lg border border-slate-200 text-rose-500 hover:bg-rose-50 transition" title="Delete Announcement">
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

        </main>
    </div>

    <!-- Compose Broadcast Modal -->
    <div id="composeModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 relative animate-in fade-in zoom-in-95 duration-200">
            <button onclick="document.getElementById('composeModal').classList.add('hidden')" 
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="flex items-center space-x-2.5 mb-4">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="megaphone" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Compose Platform Broadcast</h3>
                    <p class="text-[11px] text-slate-500">Dispatch in-app notifications and live dashboard alert banners.</p>
                </div>
            </div>

            <form method="POST" action="<?= url('admin/broadcasts.php') ?>" class="space-y-3.5 text-xs">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="form_action" value="create_broadcast">

                <div>
                    <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Announcement Title</label>
                    <input type="text" name="title" required placeholder="e.g. SEBI Mandate: Annual Risk Disclosure Form 14A Update" 
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Priority Classification</label>
                        <select name="priority" required 
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                            <option value="urgent">🔴 URGENT (Critical / Deadline / Outage)</option>
                            <option value="compliance">🟡 COMPLIANCE (SEBI / KYC / Tax / Pan)</option>
                            <option value="update" selected>🔵 UPDATE (Platform / Feature / Policy)</option>
                            <option value="opportunity">🟢 OPPORTUNITY (Featured Deals / Rounds)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Target Audience</label>
                        <select name="target_audience" required 
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                            <option value="all">All Platform Users</option>
                            <option value="founder">Founders Only</option>
                            <option value="investor">Investors Only</option>
                            <option value="pending_kyc">Users with Incomplete/Pending KYC</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Message Content</label>
                    <textarea name="message" rows="3" required placeholder="Detailed message explaining the notice, action items, or upcoming platform changes..."
                              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 outline-none focus:bg-white focus:border-indigo-600"></textarea>
                </div>

                <div>
                    <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Banner Illustrative Image URL (Optional)
                    </label>
                    <input type="url" name="image_url" id="composeImageUrl" placeholder="https://images.unsplash.com/... or choose preset below"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 font-mono outline-none focus:bg-white focus:border-indigo-600">
                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                        <span class="text-[10px] text-slate-400 font-semibold">Quick Presets:</span>
                        <button type="button" onclick="document.getElementById('composeImageUrl').value='https://images.unsplash.com/photo-1450133064473-71024230f91b?w=400&auto=format&fit=crop&q=80'" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-[10px] text-slate-600 font-medium transition border border-slate-200">
                            📜 SEBI / Legal
                        </button>
                        <button type="button" onclick="document.getElementById('composeImageUrl').value='https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&auto=format&fit=crop&q=80'" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-[10px] text-slate-600 font-medium transition border border-slate-200">
                            📊 Funding & Deals
                        </button>
                        <button type="button" onclick="document.getElementById('composeImageUrl').value='https://images.unsplash.com/photo-1563986768609-322da13575f3?w=400&auto=format&fit=crop&q=80'" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-[10px] text-slate-600 font-medium transition border border-slate-200">
                            🔒 Security & KYC
                        </button>
                        <button type="button" onclick="document.getElementById('composeImageUrl').value='https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=400&auto=format&fit=crop&q=80'" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-[10px] text-slate-600 font-medium transition border border-slate-200">
                            🚀 Platform Update
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Call-To-Action Label (Optional)</label>
                        <input type="text" name="cta_label" placeholder="e.g. Complete KYC Now" 
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Call-To-Action Link URL (Optional)</label>
                        <input type="text" name="cta_url" placeholder="e.g. founder/verification.php" 
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 items-center pt-1">
                    <label class="flex items-center space-x-2 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="show_banner" value="1" checked class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                        <span>Show as Banner on User Dashboards</span>
                    </label>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Expires On (Optional)</label>
                        <input type="date" name="expires_at" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('composeModal').classList.add('hidden')" 
                            class="px-3.5 py-2 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 font-semibold transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition shadow-sm">
                        Dispatch Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#broadcasts-main > *", { duration: 0.4, y: 12, opacity: 0, stagger: 0.06, ease: "power2.out" });
    </script>
</body>
</html>
