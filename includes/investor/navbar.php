<?php
/**
 * Investor Top Navigation Bar Component
 * Clean White / Light Theme, Small Crisp Typography
 */
$currentUser = current_user();
$db = get_db();

$unreadCount = 0;
$notifs = [];
if ($db) {
    $nStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $nStmt->execute([$currentUser['id']]);
    $notifs = $nStmt->fetchAll();

    $cStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $cStmt->execute([$currentUser['id']]);
    $unreadCount = (int)$cStmt->fetchColumn();
}
?>
<header class="h-14 border-b border-slate-200 bg-white/95 backdrop-blur-md px-6 flex items-center justify-between sticky top-0 z-20">
    <div class="flex items-center space-x-3">
        <h2 class="text-xs font-bold text-slate-800 tracking-tight flex items-center space-x-2">
            <span><?= $pageTitle ?? 'Investor Portal' ?></span>
        </h2>
    </div>

    <div class="flex items-center space-x-3">
        <!-- Quick Search Link -->
        <a href="<?= url('investor/discover.php') ?>" class="hidden md:flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-xs text-slate-600 border border-slate-200 transition font-medium">
            <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400"></i>
            <span>Search Deal Flow...</span>
        </a>

        <!-- Notifications Bell -->
        <div class="relative">
            <button onclick="toggleInvestorNotifs()" class="relative p-2 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-600 transition">
                <i data-lucide="bell" class="w-3.5 h-3.5"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-600 text-white text-[8px] font-black flex items-center justify-center">
                        <?= $unreadCount ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Dropdown -->
            <div id="investor-notif-menu" class="hidden absolute right-0 mt-2 w-72 bg-white border border-slate-200 rounded-xl shadow-lg p-3 z-50">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 text-xs font-bold text-slate-900">
                    <span>Deal Alerts</span>
                    <span class="text-emerald-600 font-normal text-[10.5px]"><?= count($notifs) ?> recent</span>
                </div>
                <div class="divide-y divide-slate-100 max-h-60 overflow-y-auto">
                    <?php if (empty($notifs)): ?>
                        <div class="py-5 text-center text-xs text-slate-400">No new alerts</div>
                    <?php else: ?>
                        <?php foreach ($notifs as $n): ?>
                            <div class="py-2.5 text-xs <?= $n['is_read'] ? 'opacity-70' : 'font-semibold' ?>">
                                <div class="text-slate-800 text-[11px]"><?= htmlspecialchars($n['title']) ?></div>
                                <div class="text-slate-500 text-[10px] mt-0.5"><?= htmlspecialchars($n['message']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="pt-2 mt-1 border-t border-slate-100 text-center">
                    <a href="<?= url('notifications.php') ?>" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700">View all notifications →</a>
                </div>
            </div>
        </div>

        <!-- Investor Profile Link -->
        <a href="<?= url('investor/view.php') ?>" title="View My Profile" class="flex items-center space-x-2 pl-2 pr-2.5 py-1 rounded-lg border border-slate-200 hover:border-teal-300 hover:bg-slate-50 transition group">
            <img src="<?= $currentUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80' ?>" class="w-6 h-6 rounded-full object-cover border border-slate-200">
            <span class="text-xs font-semibold text-slate-700 group-hover:text-teal-700 hidden md:inline"><?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?></span>
        </a>
    </div>
</header>
<script>
    function toggleInvestorNotifs() {
        const menu = document.getElementById('investor-notif-menu');
        menu.classList.toggle('hidden');
    }
</script>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>

