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
<header class="h-16 border-b border-slate-200 bg-white/95 backdrop-blur-md px-4 sm:px-6 flex items-center justify-between sticky top-0 z-20">
    <div class="flex items-center space-x-3 sm:space-x-4 min-w-0">
        <!-- Hamburger Menu Button (Mobile & Tablet) -->
        <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex-shrink-0" aria-label="Open sidebar menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight flex items-center space-x-2 truncate">
            <span class="truncate"><?= $pageTitle ?? 'Investor Portal' ?></span>
        </h2>
    </div>

    <div class="flex items-center space-x-3 sm:space-x-4 flex-shrink-0">
        <!-- Status Indicator -->
        <div class="hidden sm:flex items-center space-x-2 px-3 py-1.5 rounded-full bg-slate-50 border border-slate-200 text-xs shadow-2xs">
            <span class="w-2 h-2 rounded-full <?= $currentUser['is_verified'] ? 'bg-emerald-500 ring-2 ring-emerald-100' : 'bg-amber-500 ring-2 ring-amber-100' ?>"></span>
            <span class="text-slate-700 font-bold text-xs"><?= $currentUser['is_verified'] ? 'SEBI & KYC Verified' : 'Accreditation Pending' ?></span>
        </div>

        <!-- Quick Discover Deal Flow -->
        <a href="<?= url('investor/discover.php') ?>" class="hidden md:flex items-center space-x-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold shadow-sm transition">
            <i data-lucide="compass" class="w-4 h-4"></i>
            <span>Discover Deals</span>
        </a>

        <!-- Notifications Bell -->
        <div class="relative" id="investor-notif-dropdown-wrapper">
            <button onclick="toggleInvestorNotifs()" type="button" class="relative w-10 h-10 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 transition cursor-pointer flex items-center justify-center flex-shrink-0" aria-label="Notifications">
                <i data-lucide="bell" class="w-4.5 h-4.5"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-emerald-600 text-white text-[10px] font-black flex items-center justify-center ring-2 ring-white">
                        <?= $unreadCount ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Dropdown -->
            <div id="investor-notif-menu" class="hidden absolute right-0 mt-2 w-84 sm:w-92 bg-white border border-slate-200 rounded-2xl shadow-xl p-4 z-50">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 text-sm font-bold text-slate-900">
                    <span>Deal Alerts & Updates</span>
                    <a href="<?= url('notifications.php') ?>" class="text-xs text-emerald-600 hover:underline font-bold">View all</a>
                </div>
                <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto mt-1">
                    <?php if (empty($notifs)): ?>
                        <div class="py-5 text-center text-sm text-slate-500 font-medium">No new alerts</div>
                    <?php else: ?>
                        <?php foreach ($notifs as $n): ?>
                            <a href="<?= !empty($n['action_url']) ? url($n['action_url']) : url('notifications.php') ?>" class="block py-3 px-2.5 hover:bg-slate-50 rounded-xl transition">
                                <div class="text-sm font-bold text-slate-900 truncate"><?= htmlspecialchars($n['title']) ?></div>
                                <div class="text-xs sm:text-sm text-slate-600 line-clamp-2 mt-0.5"><?= htmlspecialchars($n['message']) ?></div>
                                <div class="text-xs text-slate-500 font-medium mt-1"><?= time_elapsed_string($n['created_at']) ?></div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Profile Dropdown (Side-by-side with Notification Bell) -->
        <div class="relative" id="investor-profile-wrapper">
            <button onclick="toggleInvestorProfile()" type="button" class="flex items-center space-x-2.5 h-10 pl-1.5 pr-3 rounded-full border border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition bg-white shadow-2xs group cursor-pointer flex-shrink-0" aria-label="Investor Profile Menu">
                <img src="<?= $currentUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100' ?>" 
                     alt="<?= htmlspecialchars($currentUser['name']) ?>" 
                     style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; max-width: 32px; max-height: 32px; border-radius: 9999px; object-fit: cover;"
                     class="w-8 h-8 rounded-full object-cover border border-slate-200 flex-shrink-0">
                <div class="hidden sm:flex flex-col text-left">
                    <span class="text-xs sm:text-sm font-bold text-slate-800 group-hover:text-emerald-700 transition truncate max-w-[120px] leading-tight"><?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?></span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-none">Angel</span>
                </div>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 transition flex-shrink-0"></i>
            </button>

            <!-- Profile Dropdown Menu -->
            <div id="investor-profile-menu" class="hidden absolute right-0 mt-2 w-64 bg-white border border-slate-200 rounded-2xl shadow-xl p-2 z-50">
                <!-- User Header Summary -->
                <div class="p-3 border-b border-slate-100 flex items-center space-x-3 bg-slate-50/70 rounded-xl mb-1">
                    <img src="<?= $currentUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100' ?>" 
                         alt="<?= htmlspecialchars($currentUser['name']) ?>"
                         style="width: 40px; height: 40px; min-width: 40px; min-height: 40px; max-width: 40px; max-height: 40px; border-radius: 9999px; object-fit: cover;"
                         class="w-10 h-10 rounded-full object-cover border border-slate-200 flex-shrink-0">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-slate-900 truncate leading-tight"><?= htmlspecialchars($currentUser['name']) ?></div>
                        <div class="text-xs text-slate-500 truncate mt-0.5"><?= htmlspecialchars($currentUser['email']) ?></div>
                        <span class="inline-flex items-center px-1.5 py-0.5 mt-1 rounded text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100">Angel Investor</span>
                    </div>
                </div>

                <div class="py-1 space-y-0.5 text-sm">
                    <a href="<?= url('investor/profile.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 hover:text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i data-lucide="sliders" class="w-4 h-4 text-slate-400"></i>
                        <span>Thesis & Settings</span>
                    </a>
                    <a href="<?= url('investor/view.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 hover:text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i data-lucide="external-link" class="w-4 h-4 text-slate-400"></i>
                        <span>Public Profile Preview</span>
                    </a>
                    <a href="<?= url('investor/portfolio.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 hover:text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-slate-400"></i>
                        <span>My Portfolio</span>
                    </a>
                </div>

                <div class="pt-1.5 mt-1 border-t border-slate-100">
                    <a href="<?= url('auth/logout.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-rose-600 hover:bg-rose-50 font-bold text-sm transition">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
<script>
    function toggleInvestorNotifs() {
        const notifMenu = document.getElementById('investor-notif-menu');
        const profileMenu = document.getElementById('investor-profile-menu');
        if (profileMenu) profileMenu.classList.add('hidden');
        if (notifMenu) {
            notifMenu.classList.toggle('hidden');
            if (!notifMenu.classList.contains('hidden') && window.lucide) {
                lucide.createIcons();
            }
        }
    }
    function toggleInvestorProfile() {
        const profileMenu = document.getElementById('investor-profile-menu');
        const notifMenu = document.getElementById('investor-notif-menu');
        if (notifMenu) notifMenu.classList.add('hidden');
        if (profileMenu) {
            profileMenu.classList.toggle('hidden');
            if (!profileMenu.classList.contains('hidden') && window.lucide) {
                lucide.createIcons();
            }
        }
    }
    document.addEventListener('click', function(e) {
        const notifWrapper = document.getElementById('investor-notif-dropdown-wrapper');
        const notifMenu = document.getElementById('investor-notif-menu');
        if (notifWrapper && notifMenu && !notifWrapper.contains(e.target)) {
            notifMenu.classList.add('hidden');
        }

        const profileWrapper = document.getElementById('investor-profile-wrapper');
        const profileMenu = document.getElementById('investor-profile-menu');
        if (profileWrapper && profileMenu && !profileWrapper.contains(e.target)) {
            profileMenu.classList.add('hidden');
        }
    });
</script>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>

