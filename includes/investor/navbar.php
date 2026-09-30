<?php
/**
 * Investor Top Navigation Bar Component
 * Professional Investor Network Header
 * Order: Discover (default) -> Dashboard -> Portfolio -> Watchlist -> Messages
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

// Nav items in display order: Discover is FIRST (default landing page)
$navActive = 'bg-[#123B7A] text-white shadow-xs dark:bg-blue-600';
$navInactive = 'text-[#4B5563] dark:text-slate-300 hover:text-[#123B7A] dark:hover:text-white hover:bg-white dark:hover:bg-slate-700/60';

$navItems = [
    [
        'href' => url('investor/discover.php'),
        'icon' => 'sparkles',
        'label' => 'Discover',
        'title' => 'Discover Startups',
        'active' => in_array($currentPage, ['discover.php', 'startup_detail.php', 'invest.php'], true),
    ],
    [
        'href' => url('investor/dashboard.php'),
        'icon' => 'layout-dashboard',
        'label' => 'Dashboard',
        'title' => 'Investor Dashboard',
        'active' => $currentPage === 'dashboard.php',
    ],
    [
        'href' => url('investor/portfolio.php'),
        'icon' => 'briefcase',
        'label' => 'Portfolio',
        'title' => 'Portfolio Holdings',
        'active' => $currentPage === 'portfolio.php',
    ],
    [
        'href' => url('investor/watchlist.php'),
        'icon' => 'bookmark',
        'label' => 'Watchlist',
        'title' => 'Saved Companies',
        'active' => $currentPage === 'watchlist.php',
    ],
    [
        'href' => url('investor/messages.php'),
        'icon' => 'message-circle',
        'label' => 'Messages',
        'title' => 'Deal Conversations',
        'active' => $currentPage === 'messages.php',
    ],
];

// Load Investor Dark & Light Theme Controller
require_once __DIR__ . '/theme.php';
?>
<!-- Global Investor Network Styling & Design Tokens -->
<style id="investor-network-tokens">
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

    html {
        font-size: 16px !important;
    }

    html,
    body,
    button,
    input,
    select,
    textarea,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    span,
    a,
    label,
    table,
    th,
    td {
        font-family: "Vay Portal", Sans-serif;
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }

    body {
        background-color: #FAFBFD;
        color: #111827;
        font-size: 1rem;
        line-height: 1.6;
    }

    /* Elevate small font classes for effortless readability */
    .text-\[9px\],
    .text-\[9\.5px\],
    .text-\[10px\],
    .text-\[10\.5px\],
    .text-\[11px\] {
        font-size: 0.8125rem !important;
        /* 13px */
        line-height: 1.45 !important;
    }

    .text-xs {
        font-size: 0.875rem !important;
        /* 14px */
        line-height: 1.5 !important;
    }

    .text-sm {
        font-size: 1rem !important;
        /* 16px */
        line-height: 1.6 !important;
    }

    .text-base {
        font-size: 1.125rem !important;
        /* 18px */
        line-height: 1.65 !important;
    }

    /* Boost secondary text contrast for high legibility */
    .text-\[\#667085\],
    .text-slate-500 {
        color: #4B5563 !important;
    }

    /* Clean subtle link underline hover */
    .link-hover-blue {
        position: relative;
        text-decoration: none;
    }

    .link-hover-blue::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -2px;
        left: 0;
        background-color: #123B7A;
        transition: width 0.2s ease-in-out;
    }

    .link-hover-blue:hover::after {
        width: 100%;
    }

    /* Clean horizontal item rows with smooth hover */
    .network-row {
        background: #FFFFFF;
        border-bottom: 1px solid #E4E8EF;
        transition: background-color 0.18s ease, border-color 0.18s ease;
    }

    .network-row:hover {
        background: #F8FAFD;
    }

    /* Clean section dividers */
    .editorial-divider {
        height: 1px;
        background: #E4E8EF;
    }

    /* Sticky / Fixed Navigation Bar with blur */
    .investor-navbar {
        position: sticky !important;
        top: 0 !important;
        z-index: 50 !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
    }
</style>

<header
    class="investor-navbar h-16 sm:h-20 border-b border-[#E4E8EF] dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md sticky top-0 z-50 px-3 sm:px-6 md:px-8 flex items-center justify-between transition-colors duration-200 shadow-2xs">
    <!-- Left Section: Hamburger Menu + Search -->
    <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
        <!-- Hamburger Menu Button (Mobile & Tablet) -->
        <button type="button" onclick="toggleMobileSidebar()"
            class="lg:hidden p-2 rounded-xl text-[#4B5563] dark:text-slate-300 hover:text-[#111827] dark:hover:text-white hover:bg-[#FAFBFD] dark:hover:bg-slate-800 border border-transparent hover:border-[#E4E8EF] dark:hover:border-slate-700 transition flex-shrink-0"
            aria-label="Open sidebar menu">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>

        <?php if (($currentPage ?? basename($_SERVER['PHP_SELF'])) !== 'dashboard.php'): ?>
        <!-- Clean & Bold Back Icon Button -->
        <button type="button" onclick="investorGoBack(this)"
                id="investor-back-btn"
                title="Go Back" aria-label="Go Back"
                class="flex-shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#FAFBFD] hover:bg-[#123B7A] dark:bg-slate-800 dark:hover:bg-blue-600 border border-[#E4E8EF] dark:border-slate-700 hover:border-[#123B7A] dark:hover:border-blue-600 text-[#4B5563] dark:text-slate-200 hover:text-white dark:hover:text-white shadow-xs hover:shadow-md hover:shadow-blue-900/20 transition-all duration-200 flex items-center justify-center group cursor-pointer active:scale-95">
            <i data-lucide="arrow-left" class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform duration-200"></i>
        </button>
        <?php endif; ?>

        <!-- Search Option Bar -->
        <form action="<?= url('investor/discover.php') ?>" method="GET" class="relative flex items-center">
            <div class="relative w-44 sm:w-60 md:w-72 lg:w-80">
                <i data-lucide="search"
                    class="w-4 h-4 text-[#667085] dark:text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    placeholder="Search startups, founders, sector..."
                    class="w-full pl-9 pr-3.5 py-2 text-xs sm:text-sm rounded-xl bg-[#FAFBFD] dark:bg-slate-800/90 border border-[#E4E8EF] dark:border-slate-700 text-[#111827] dark:text-slate-100 placeholder-[#667085] dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#123B7A]/20 dark:focus:ring-blue-500/20 focus:border-[#123B7A] dark:focus:border-blue-400 transition shadow-2xs">
            </div>
        </form>
    </div>

    <!-- Center Section: Main Nav (Discover first) -->
    <nav
        class="hidden md:flex items-center space-x-1 lg:space-x-1.5 px-2 py-1 rounded-2xl bg-[#FAFBFD] dark:bg-slate-800/80 border border-[#E4E8EF] dark:border-slate-800">
        <?php foreach ($navItems as $item): ?>
            <a href="<?= $item['href'] ?>"
                class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $item['active'] ? $navActive : $navInactive ?>"
                title="<?= htmlspecialchars($item['title']) ?>" <?= $item['active'] ? 'aria-current="page"' : '' ?>>
                <i data-lucide="<?= $item['icon'] ?>" class="w-3.5 h-3.5 flex-shrink-0"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Right Section: Notifications + Dark/Light Mode -->
    <div class="flex items-center space-x-2.5 sm:space-x-3.5 flex-shrink-0">

        <!-- Notifications Bell -->
        <div class="relative" id="investor-notif-wrapper">
            <button onclick="toggleInvestorNotifs()"
                class="relative p-2.5 rounded-xl bg-[#FAFBFD] dark:bg-slate-800 hover:bg-[#EAF2FF] dark:hover:bg-slate-700 border border-[#E4E8EF] dark:border-slate-700 text-[#4B5563] dark:text-slate-200 hover:text-[#123B7A] dark:hover:text-white transition cursor-pointer"
                aria-label="Notifications">
                <i data-lucide="bell" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                <?php if ($unreadCount > 0): ?>
                    <span
                        class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-[#123B7A] dark:bg-blue-600 text-white text-[11px] font-bold flex items-center justify-center">
                        <?= $unreadCount ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Notifications Dropdown -->
            <div id="investor-notif-menu"
                class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl shadow-2xl p-4 z-50">
                <div
                    class="flex items-center justify-between pb-3 border-b border-[#E4E8EF] dark:border-slate-800 text-sm font-extrabold text-[#0B1F3A] dark:text-white">
                    <span>Deal Alerts & Updates</span>
                    <span
                        class="text-[#123B7A] dark:text-blue-400 font-bold text-xs bg-[#EAF2FF] dark:bg-blue-900/40 px-2.5 py-0.5 rounded-full"><?= count($notifs) ?>
                        recent</span>
                </div>
                <div class="divide-y divide-[#E4E8EF] dark:divide-slate-800 max-h-72 overflow-y-auto">
                    <?php if (empty($notifs)): ?>
                        <div class="py-8 text-center text-sm text-[#667085] dark:text-slate-400">No unread alerts</div>
                    <?php else: ?>
                        <?php foreach ($notifs as $n): ?>
                            <div class="py-3 text-sm <?= $n['is_read'] ? 'opacity-70' : 'font-semibold' ?>">
                                <div class="text-[#111827] dark:text-slate-100 text-sm font-bold">
                                    <?= htmlspecialchars($n['title']) ?>
                                </div>
                                <div class="text-[#4B5563] dark:text-slate-300 text-xs sm:text-sm mt-1 leading-relaxed">
                                    <?= htmlspecialchars($n['message']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="pt-3 mt-1 border-t border-[#E4E8EF] dark:border-slate-800 text-center">
                    <a href="<?= url('notifications.php') ?>"
                        class="text-xs sm:text-sm font-extrabold text-[#123B7A] dark:text-blue-400 hover:underline transition">View
                        all activity →</a>
                </div>
            </div>
        </div>

        <!-- Dark / Light Theme Mode Toggle Button -->
        <button id="investor-theme-toggle-btn" onclick="toggleInvestorTheme()" type="button"
            class="relative p-2.5 rounded-xl bg-[#FAFBFD] dark:bg-slate-800 hover:bg-[#EAF2FF] dark:hover:bg-slate-700 border border-[#E4E8EF] dark:border-slate-700 text-[#4B5563] dark:text-slate-200 transition flex items-center justify-center cursor-pointer shadow-xs group"
            title="Toggle Dark / Light Theme" aria-label="Toggle Dark / Light Theme">
            <!-- Sun Icon (Active in Dark Mode) -->
            <span id="investor-sun-wrap" class="hidden items-center justify-center">
                <i data-lucide="sun"
                    class="w-4 h-4 sm:w-5 sm:h-5 text-amber-400 group-hover:rotate-45 transition-transform duration-300"></i>
            </span>
            <!-- Moon Icon (Active in Light Mode) -->
            <span id="investor-moon-wrap" class="flex items-center justify-center">
                <i data-lucide="moon"
                    class="w-4 h-4 sm:w-5 sm:h-5 text-[#123B7A] dark:text-blue-300 group-hover:-rotate-12 transition-transform duration-300"></i>
            </span>
        </button>
    </div>
</header>

<script>
    // Toggle Notifications Dropdown
    function toggleInvestorNotifs() {
        const notifMenu = document.getElementById('investor-notif-menu');
        if (notifMenu) {
            notifMenu.classList.toggle('hidden');
        }
    }

    // Toggle Dark / Light Theme
    function toggleInvestorTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        const themeName = isDark ? 'dark' : 'light';
        try {
            localStorage.setItem('startup_portal_theme', themeName);
            document.cookie = "startup_portal_theme=" + themeName + ";path=/;max-age=31536000";
        } catch (e) { }
        syncInvestorThemeIcons(isDark);
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: themeName } }));
    }

    // Synchronize Sun and Moon Icons
    function syncInvestorThemeIcons(isDark) {
        const sun = document.getElementById('investor-sun-wrap');
        const moon = document.getElementById('investor-moon-wrap');
        const btn = document.getElementById('investor-theme-toggle-btn');
        if (sun && moon) {
            if (isDark) {
                sun.classList.remove('hidden');
                sun.classList.add('flex');
                moon.classList.add('hidden');
                moon.classList.remove('flex');
                if (btn) {
                    btn.setAttribute('title', 'Switch to Light Mode');
                    btn.setAttribute('aria-label', 'Switch to Light Mode');
                }
            } else {
                sun.classList.add('hidden');
                sun.classList.remove('flex');
                moon.classList.remove('hidden');
                moon.classList.add('flex');
                if (btn) {
                    btn.setAttribute('title', 'Switch to Dark Mode');
                    btn.setAttribute('aria-label', 'Switch to Dark Mode');
                }
            }
        }
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    // Close Dropdowns on Click Outside
    document.addEventListener('click', function (e) {
        const notifMenu = document.getElementById('investor-notif-menu');
        const notifWrap = document.getElementById('investor-notif-wrapper');
        if (notifMenu && !notifMenu.contains(e.target) && notifWrap && !notifWrap.contains(e.target)) {
            notifMenu.classList.add('hidden');
        }
    });

    // Initialize Theme Icon State Immediately
    (function () {
        const isDark = document.documentElement.classList.contains('dark');
        syncInvestorThemeIcons(isDark);
    })();
    function investorGoBack(btn) {
        if (btn) {
            btn.style.transform = 'scale(0.92)';
            setTimeout(() => { btn.style.transform = ''; }, 120);
        }
        setTimeout(() => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '<?= url('investor/dashboard.php') ?>';
            }
        }, 80);
    }
</script>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>