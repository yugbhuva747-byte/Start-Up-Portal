<?php
/**
 * Founder Top Navigation Bar Component
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
<header class="h-14 border-b border-slate-200 bg-white/95 backdrop-blur-md px-3 sm:px-6 flex items-center justify-between sticky top-0 z-20">
    <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
        <!-- Hamburger Menu Button (Mobile & Tablet) -->
        <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 sm:p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex-shrink-0" aria-label="Open sidebar menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <h2 class="text-xs font-bold text-slate-800 tracking-tight flex items-center space-x-2 truncate">
            <span class="truncate"><?= $pageTitle ?? 'Founder Workspace' ?></span>
        </h2>
    </div>

    <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
        <!-- Status Indicator -->
        <div class="hidden sm:flex items-center space-x-2 px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200 text-xs">
            <span class="w-1.5 h-1.5 rounded-full <?= $currentUser['is_verified'] ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
            <span class="text-slate-600 font-medium text-[10.5px]"><?= $currentUser['is_verified'] ? 'MCA & DigiLocker Verified' : 'KYC Under Review' ?></span>
        </div>

        <!-- Create Round Quick Action -->
        <a href="<?= url('founder/funding_rounds.php?action=new') ?>" class="hidden md:flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition">
            <i data-lucide="plus" class="w-3 h-3"></i>
            <span>New Round</span>
        </a>

        <!-- Notifications Bell -->
        <div class="relative" id="notif-dropdown-wrapper">
            <button onclick="toggleNotifs()" class="relative p-2 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-600 transition">
                <i data-lucide="bell" class="w-3.5 h-3.5"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-indigo-600 text-white text-[8px] font-black flex items-center justify-center">
                        <?= $unreadCount ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Dropdown Menu -->
            <div id="notif-menu" class="hidden absolute right-0 mt-2 w-72 bg-white border border-slate-200 rounded-xl shadow-lg p-3 z-50">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 text-xs font-bold text-slate-900">
                    <span>Notifications</span>
                    <span class="text-indigo-600 font-normal text-[10.5px]"><?= count($notifs) ?> recent</span>
                </div>
                <div class="divide-y divide-slate-100 max-h-60 overflow-y-auto">
                    <?php if (empty($notifs)): ?>
                        <div class="py-5 text-center text-xs text-slate-400">No notifications yet</div>
                    <?php else: ?>
                        <?php foreach ($notifs as $n): ?>
                            <div class="py-2.5 text-xs <?= $n['is_read'] ? 'opacity-70' : 'font-semibold' ?>">
                                <div class="text-slate-800 text-[11px]"><?= htmlspecialchars($n['title']) ?></div>
                                <div class="text-slate-500 text-[10px] mt-0.5"><?= htmlspecialchars($n['message']) ?></div>
                                <div class="text-[9px] text-slate-400 mt-0.5"><?= date('M d, H:i', strtotime($n['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="pt-2 mt-1 border-t border-slate-100 text-center">
                    <a href="<?= url('notifications.php') ?>" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700">View all notifications →</a>
                </div>
            </div>
        </div>

        <!-- Founder User Profile Link -->
        <a href="<?= url('founder/view.php') ?>" title="View My Profile" class="flex items-center space-x-2 pl-2 pr-2.5 py-1 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-slate-50 transition group">
            <img src="<?= $currentUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80' ?>" class="w-6 h-6 rounded-full object-cover border border-slate-200">
            <span class="text-xs font-semibold text-slate-700 group-hover:text-indigo-600 hidden md:inline"><?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?></span>
        </a>
    </div>
</header>
<script>
    function toggleNotifs() {
        const menu = document.getElementById('notif-menu');
        menu.classList.toggle('hidden');
    }
</script>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>

