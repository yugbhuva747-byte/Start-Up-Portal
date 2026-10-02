<?php
/**
 * Founder Top Navigation Bar Component
 * High-Legibility Theme with Modern Design Tokens
 * Enhanced layout with Back button, Quick search, Dark/Light Mode, Notifications & Profile dropdown
 */
$currentUser = current_user();
$db = get_db();
$currentPage = basename($_SERVER['PHP_SELF']);

$unreadCount = 0;
$notifs = [];
if ($db && !empty($currentUser['id'])) {
    try {
        $nStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $nStmt->execute([$currentUser['id']]);
        $notifs = $nStmt->fetchAll();

        $cStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $cStmt->execute([$currentUser['id']]);
        $unreadCount = (int) $cStmt->fetchColumn();
    } catch (\Throwable $e) {
        $notifs = [];
        $unreadCount = 0;
    }
}

// Ensure Founder Dark & Light Theme Controller is loaded
require_once __DIR__ . '/theme.php';
?>
<style>
    .founder-navbar {
        position: sticky !important;
        top: 0 !important;
        z-index: 40 !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
    }
</style>
<header
    class="founder-navbar h-16 sm:h-20 border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md px-3 sm:px-6 md:px-8 flex items-center justify-between sticky top-0 z-40 transition-colors duration-200 shadow-2xs">
    <!-- Left Section: Mobile Menu + Back Button + Title + Quick Search -->
    <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
        <!-- Desktop Sidebar Rail Toggle Button -->
        <button type="button" 
                onclick="toggleDesktopSidebar()" 
                id="founder-sidebar-toggle-btn"
                class="hidden lg:flex w-9 h-9 sm:w-10 sm:h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:border-indigo-300 dark:hover:border-indigo-700 active:scale-95 transition-all items-center justify-center shadow-2xs" 
                title="Toggle Sidebar Rail (Collapse / Expand)"
                aria-label="Toggle sidebar width">
            <i data-lucide="panel-left" class="w-4.5 h-4.5"></i>
        </button>

        <!-- Hamburger Menu Button (Mobile & Tablet) -->
        <button type="button" onclick="toggleMobileSidebar()"
            class="lg:hidden p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition flex-shrink-0 border border-slate-200 dark:border-slate-700"
            aria-label="Open sidebar menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <!-- Quick Platform Search -->
        <form action="<?= url('founder/funding_rounds.php') ?>" method="GET"
            class="relative hidden sm:block w-52 md:w-64 lg:w-72 m-0">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            <input type="text" name="q" placeholder="Search rounds, investors, documents..."
                class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/70 focus:bg-white dark:focus:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 rounded-xl text-xs sm:text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 outline-none transition" />
        </form>
    </div>

    <!-- Right Section: Verification Badge + Actions + Theme Toggle + Notifs + Profile Dropdown -->
    <div class="flex items-center space-x-2 sm:space-x-3.5 flex-shrink-0">



        <!-- Dark / Light Theme Toggle Switcher -->
        <button id="founder-theme-toggle-btn" onclick="toggleFounderTheme()" type="button"
            class="relative p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 transition flex items-center justify-center cursor-pointer shadow-xs group"
            title="Toggle Dark / Light Theme" aria-label="Toggle Dark / Light Theme">
            <!-- Sun Icon Wrapper (Active in Dark Mode) -->
            <span id="theme-sun-wrap" class="hidden items-center justify-center">
                <i data-lucide="sun"
                    class="w-4 h-4 text-amber-400 group-hover:rotate-45 transition-transform duration-300"></i>
            </span>
            <!-- Moon Icon Wrapper (Active in Light Mode) -->
            <span id="theme-moon-wrap" class="flex items-center justify-center">
                <i data-lucide="moon"
                    class="w-4 h-4 text-slate-600 dark:text-indigo-300 group-hover:-rotate-12 transition-transform duration-300"></i>
            </span>
        </button>

        <!-- Notifications Bell -->
        <div class="relative" id="notif-dropdown-wrapper">
            <button onclick="toggleNotifs()" type="button"
                class="relative p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 transition cursor-pointer"
                aria-label="Notifications">
                <i data-lucide="bell" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                <?php if ($unreadCount > 0): ?>
                    <span
                        class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white dark:ring-slate-900">
                        <?= $unreadCount ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Notifications Dropdown Menu -->
            <div id="notif-menu"
                class="hidden absolute right-0 mt-2 w-80 sm:w-92 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-4 z-50">
                <div
                    class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 text-sm font-bold text-slate-900 dark:text-white">
                    <span>Notifications</span>
                    <a href="<?= url('notifications.php') ?>" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-bold">View all</a>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-72 overflow-y-auto mt-1">
                    <?php if (empty($notifs)): ?>
                        <div class="py-5 text-center text-sm text-slate-400">No new notifications</div>
                    <?php else: ?>
                        <?php foreach ($notifs as $n): ?>
                            <a href="<?= !empty($n['action_url']) ? url($n['action_url']) : url('notifications.php') ?>" class="block py-3 px-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800/60 rounded-xl transition <?= $n['is_read'] ? 'opacity-70' : 'font-semibold' ?>">
                                <div class="text-slate-800 dark:text-slate-100 text-sm leading-snug font-bold truncate"><?= htmlspecialchars($n['title']) ?></div>
                                <div class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1 leading-relaxed line-clamp-2"><?= htmlspecialchars($n['message']) ?></div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500 font-medium mt-1">
                                    <?= function_exists('time_elapsed_string') ? time_elapsed_string($n['created_at']) : date('M d, H:i', strtotime($n['created_at'])) ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="pt-3 mt-2 border-t border-slate-100 dark:border-slate-800 text-center">
                    <a href="<?= url('notifications.php') ?>"
                        class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700">View all notifications →</a>
                </div>
            </div>
        </div>

        <!-- User Profile Dropdown -->
        <div class="relative" id="user-profile-wrapper">
            <button onclick="toggleUserProfile()" type="button" class="flex items-center space-x-2 h-10 pl-1.5 pr-2.5 rounded-full border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition bg-white dark:bg-slate-900 shadow-2xs group cursor-pointer flex-shrink-0" aria-label="Founder Profile Menu">
                <img src="<?= $currentUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' ?>" 
                     alt="<?= htmlspecialchars($currentUser['name'] ?? 'Founder') ?>" 
                     style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; max-width: 32px; max-height: 32px; border-radius: 9999px; object-fit: cover;"
                     class="w-8 h-8 rounded-full object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0">
                <div class="hidden sm:flex flex-col text-left">
                    <span class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 group-hover:text-indigo-600 transition truncate max-w-[110px] leading-tight"><?= htmlspecialchars(explode(' ', $currentUser['name'] ?? 'Founder')[0]) ?></span>
                    <span class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider leading-none">Founder</span>
                </div>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-200 transition flex-shrink-0"></i>
            </button>

            <!-- Profile Dropdown Menu -->
            <div id="user-profile-menu" class="hidden absolute right-0 mt-2 w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-2 z-50">
                <!-- User Header Summary -->
                <div class="p-3 border-b border-slate-100 dark:border-slate-800 flex items-center space-x-3 bg-slate-50/70 dark:bg-slate-800/50 rounded-xl mb-1">
                    <img src="<?= $currentUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' ?>" 
                         alt="<?= htmlspecialchars($currentUser['name'] ?? 'Founder') ?>"
                         style="width: 40px; height: 40px; min-width: 40px; min-height: 40px; max-width: 40px; max-height: 40px; border-radius: 9999px; object-fit: cover;"
                         class="w-10 h-10 rounded-full object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-slate-900 dark:text-slate-100 truncate leading-tight"><?= htmlspecialchars($currentUser['name'] ?? 'Founder') ?></div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5"><?= htmlspecialchars($currentUser['email'] ?? '') ?></div>
                        <span class="inline-flex items-center px-1.5 py-0.5 mt-1 rounded text-[10px] font-extrabold uppercase tracking-wider bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/40">Founder</span>
                    </div>
                </div>

                <div class="py-1 space-y-0.5 text-sm">
                    <a href="<?= url('founder/profile.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 font-bold transition">
                        <i data-lucide="user" class="w-4 h-4 text-slate-400"></i>
                        <span>Founder Profile & Settings</span>
                    </a>
                    <a href="<?= url('founder/view.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 font-bold transition">
                        <i data-lucide="external-link" class="w-4 h-4 text-slate-400"></i>
                        <span>Public Profile Preview</span>
                    </a>
                    <a href="<?= url('founder/company.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 font-bold transition">
                        <i data-lucide="building-2" class="w-4 h-4 text-slate-400"></i>
                        <span>Company Profile</span>
                    </a>
                </div>

                <div class="pt-1.5 mt-1 border-t border-slate-100 dark:border-slate-800">
                    <a href="<?= url('auth/logout.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 font-bold text-sm transition">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
<script>
    function toggleNotifs() {
        const notifMenu = document.getElementById('notif-menu');
        const profileMenu = document.getElementById('user-profile-menu');
        if (profileMenu) profileMenu.classList.add('hidden');
        if (notifMenu) {
            notifMenu.classList.toggle('hidden');
            if (!notifMenu.classList.contains('hidden') && window.lucide) {
                lucide.createIcons();
            }
        }
    }

    function toggleUserProfile() {
        const profileMenu = document.getElementById('user-profile-menu');
        const notifMenu = document.getElementById('notif-menu');
        if (notifMenu) notifMenu.classList.add('hidden');
        if (profileMenu) {
            profileMenu.classList.toggle('hidden');
            if (!profileMenu.classList.contains('hidden') && window.lucide) {
                lucide.createIcons();
            }
        }
    }

    function toggleFounderTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        const themeName = isDark ? 'dark' : 'light';
        try {
            localStorage.setItem('startup_portal_theme', themeName);
            document.cookie = "startup_portal_theme=" + themeName + ";path=/;max-age=31536000";
        } catch (e) { }
        syncFounderThemeIcons(isDark);
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: themeName } }));
    }

    function syncFounderThemeIcons(isDark) {
        const sun = document.getElementById('theme-sun-wrap');
        const moon = document.getElementById('theme-moon-wrap');
        const btn = document.getElementById('founder-theme-toggle-btn');
        if (sun && moon) {
            if (isDark) {
                sun.classList.remove('hidden');
                sun.classList.add('flex');
                moon.classList.add('hidden');
                moon.classList.remove('flex');
                if (btn) {
                    btn.setAttribute('title', 'Switch to Light Theme');
                    btn.setAttribute('aria-label', 'Switch to Light Theme');
                }
            } else {
                sun.classList.add('hidden');
                sun.classList.remove('flex');
                moon.classList.remove('hidden');
                moon.classList.add('flex');
                if (btn) {
                    btn.setAttribute('title', 'Switch to Dark Theme');
                    btn.setAttribute('aria-label', 'Switch to Dark Theme');
                }
            }
        }
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    // Sync icon state on page load
    (function () {
        const isDark = document.documentElement.classList.contains('dark');
        syncFounderThemeIcons(isDark);
    })();

    function founderGoBack(btn) {
        if (btn) {
            btn.style.transform = 'scale(0.92)';
            setTimeout(() => { btn.style.transform = ''; }, 120);
        }
        setTimeout(() => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '<?= url('founder/dashboard.php') ?>';
            }
        }, 80);
    }

    document.addEventListener('click', function(e) {
        const notifWrapper = document.getElementById('notif-dropdown-wrapper');
        const notifMenu = document.getElementById('notif-menu');
        if (notifWrapper && notifMenu && !notifWrapper.contains(e.target)) {
            notifMenu.classList.add('hidden');
        }

        const profileWrapper = document.getElementById('user-profile-wrapper');
        const profileMenu = document.getElementById('user-profile-menu');
        if (profileWrapper && profileMenu && !profileWrapper.contains(e.target)) {
            profileMenu.classList.add('hidden');
        }
    });
</script>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>
