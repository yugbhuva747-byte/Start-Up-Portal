<?php
/**
 * Investor Top Navigation Bar Component
 * Professional Investor Network Header
 */
$currentUser = current_user();
$db = get_db();
$currentPage = basename($_SERVER['PHP_SELF']);

$unreadCount = 0;
$notifs = [];
if ($db) {
    $nStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $nStmt->execute([$currentUser['id']]);
    $notifs = $nStmt->fetchAll();

    $cStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $cStmt->execute([$currentUser['id']]);
    $unreadCount = (int) $cStmt->fetchColumn();
}

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
        font-family: 'Vay Portal - Regular', 'Vay Portal', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
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
    /* Sticky / Fixed Navigation Bar with blur */
    .investor-navbar {
        position: sticky !important;
        top: 0 !important;
        z-index: 50 !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
    }
</style>

<header class="investor-navbar h-16 sm:h-20 border-b border-[#E4E8EF] dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md sticky top-0 z-50 px-3 sm:px-6 md:px-8 flex items-center justify-between transition-colors duration-200 shadow-2xs">
    <!-- Left Section: Hamburger Menu + Section Selector Options -->
    <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
        <!-- Hamburger Menu Button (Mobile & Tablet) -->
        <button type="button" onclick="toggleMobileSidebar()"
            class="lg:hidden p-2 rounded-xl text-[#4B5563] dark:text-slate-300 hover:text-[#111827] dark:hover:text-white hover:bg-[#FAFBFD] dark:hover:bg-slate-800 border border-transparent hover:border-[#E4E8EF] dark:hover:border-slate-700 transition flex-shrink-0"
            aria-label="Open sidebar menu">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>

        <!-- Section Dropdown Options Menu -->
        <div class="relative" id="investor-sections-wrapper">
            <button type="button" onclick="toggleInvestorSectionsDropdown()"
                class="flex items-center space-x-2 px-3 sm:px-3.5 py-2 rounded-xl border border-[#E4E8EF] dark:border-slate-700 bg-[#FAFBFD] dark:bg-slate-800 text-[#0B1F3A] dark:text-slate-100 hover:border-[#123B7A] dark:hover:border-blue-400 hover:bg-white dark:hover:bg-slate-750 transition font-bold text-xs sm:text-sm shadow-xs group"
                aria-label="Toggle Portal Sections">
                <i data-lucide="layout-grid"
                    class="w-4 h-4 text-[#123B7A] dark:text-blue-400 group-hover:scale-110 transition-transform"></i>
                <span class="tracking-tight">Sections</span>
                <i data-lucide="chevron-down"
                    class="w-3.5 h-3.5 text-[#667085] dark:text-slate-400 group-hover:translate-y-0.5 transition-transform"></i>
            </button>

            <!-- Sections Quick Directory Dropdown -->
            <div id="investor-sections-menu"
                class="hidden absolute left-0 mt-2 w-72 sm:w-80 bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl shadow-2xl p-3 z-50 transition-all">
                <div
                    class="px-2.5 py-2 border-b border-[#E4E8EF] dark:border-slate-800 flex items-center justify-between">
                    <span
                        class="text-[11px] font-extrabold uppercase tracking-wider text-[#667085] dark:text-slate-400">Portal
                        Sections</span>
                    <span
                        class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#EAF2FF] dark:bg-blue-900/50 text-[#123B7A] dark:text-blue-300">Investor
                        Access</span>
                </div>

                <div class="py-2 space-y-1">
                    <div
                        class="px-2.5 pt-1.5 pb-1 text-[10px] font-extrabold text-[#94A3B8] dark:text-slate-500 uppercase tracking-wider">
                        Discovery & Overview</div>
                    <a href="<?= url('investor/discover.php') ?>"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#111827] dark:text-slate-200 hover:bg-[#FAFBFD] dark:hover:bg-slate-800 hover:text-[#123B7A] dark:hover:text-blue-400 transition <?= in_array($currentPage, ['discover.php', 'startup_detail.php']) ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300' : '' ?>">
                        <i data-lucide="sparkles" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                        <div class="flex-1">
                            <div class="leading-tight">Discover Opportunities</div>
                            <div class="text-[11px] text-[#667085] dark:text-slate-400 font-normal">Explore verified
                                startups</div>
                        </div>
                    </a>
                    <a href="<?= url('investor/dashboard.php') ?>"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#111827] dark:text-slate-200 hover:bg-[#FAFBFD] dark:hover:bg-slate-800 hover:text-[#123B7A] dark:hover:text-blue-400 transition <?= $currentPage === 'dashboard.php' ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300' : '' ?>">
                        <i data-lucide="home" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                        <div class="flex-1">
                            <div class="leading-tight">Investor Home</div>
                            <div class="text-[11px] text-[#667085] dark:text-slate-400 font-normal">Workspace dashboard
                            </div>
                        </div>
                    </a>

                    <div
                        class="px-2.5 pt-2 pb-1 text-[10px] font-extrabold text-[#94A3B8] dark:text-slate-500 uppercase tracking-wider">
                        Investments & Network</div>
                    <a href="<?= url('investor/portfolio.php') ?>"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#111827] dark:text-slate-200 hover:bg-[#FAFBFD] dark:hover:bg-slate-800 hover:text-[#123B7A] dark:hover:text-blue-400 transition <?= $currentPage === 'portfolio.php' ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300' : '' ?>">
                        <i data-lucide="briefcase" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                        <div class="flex-1">
                            <div class="leading-tight">Portfolio Holdings</div>
                            <div class="text-[11px] text-[#667085] dark:text-slate-400 font-normal">Active equity stakes
                            </div>
                        </div>
                    </a>
                    <a href="<?= url('investor/watchlist.php') ?>"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#111827] dark:text-slate-200 hover:bg-[#FAFBFD] dark:hover:bg-slate-800 hover:text-[#123B7A] dark:hover:text-blue-400 transition <?= $currentPage === 'watchlist.php' ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300' : '' ?>">
                        <i data-lucide="bookmark" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                        <div class="flex-1">
                            <div class="leading-tight">Saved Companies</div>
                            <div class="text-[11px] text-[#667085] dark:text-slate-400 font-normal">Monitored pipeline
                            </div>
                        </div>
                    </a>
                    <a href="<?= url('investor/messages.php') ?>"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#111827] dark:text-slate-200 hover:bg-[#FAFBFD] dark:hover:bg-slate-800 hover:text-[#123B7A] dark:hover:text-blue-400 transition <?= $currentPage === 'messages.php' ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300' : '' ?>">
                        <i data-lucide="message-circle" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                        <div class="flex-1">
                            <div class="leading-tight">Deal Conversations</div>
                            <div class="text-[11px] text-[#667085] dark:text-slate-400 font-normal">Direct founder chats
                            </div>
                        </div>
                    </a>

                    <div
                        class="px-2.5 pt-2 pb-1 text-[10px] font-extrabold text-[#94A3B8] dark:text-slate-500 uppercase tracking-wider">
                        Account & Compliance</div>
                    <a href="<?= url('investor/view.php') ?>"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#111827] dark:text-slate-200 hover:bg-[#FAFBFD] dark:hover:bg-slate-800 hover:text-[#123B7A] dark:hover:text-blue-400 transition <?= $currentPage === 'view.php' ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300' : '' ?>">
                        <i data-lucide="user-check" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                        <div class="flex-1">
                            <div class="leading-tight">Public Profile</div>
                            <div class="text-[11px] text-[#667085] dark:text-slate-400 font-normal">Investor network
                                presence</div>
                        </div>
                    </a>
                    <a href="<?= url('investor/profile.php') ?>"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#111827] dark:text-slate-200 hover:bg-[#FAFBFD] dark:hover:bg-slate-800 hover:text-[#123B7A] dark:hover:text-blue-400 transition <?= $currentPage === 'profile.php' ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300' : '' ?>">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                        <div class="flex-1">
                            <div class="leading-tight">Thesis & Settings</div>
                            <div class="text-[11px] text-[#667085] dark:text-slate-400 font-normal">Investment
                                preferences</div>
                        </div>
                    </a>
                    <a href="<?= url('investor/verification.php') ?>"
                        class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#111827] dark:text-slate-200 hover:bg-[#FAFBFD] dark:hover:bg-slate-800 hover:text-[#123B7A] dark:hover:text-blue-400 transition <?= $currentPage === 'verification.php' ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300' : '' ?>">
                        <i data-lucide="shield-check" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                        <div class="flex-1">
                            <div class="leading-tight">Verification Center</div>
                            <div class="text-[11px] text-[#667085] dark:text-slate-400 font-normal">Accreditation status
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Center Section: Side Nav Bar Important Options -->
    <nav
        class="hidden md:flex items-center space-x-1 lg:space-x-1.5 px-2 py-1 rounded-2xl bg-[#FAFBFD] dark:bg-slate-800/80 border border-[#E4E8EF] dark:border-slate-800">
        <!-- Discover Opportunities -->
        <a href="<?= url('investor/discover.php') ?>"
            class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= in_array($currentPage, ['discover.php', 'startup_detail.php']) ? 'bg-[#123B7A] text-white shadow-xs dark:bg-blue-600' : 'text-[#4B5563] dark:text-slate-300 hover:text-[#123B7A] dark:hover:text-white hover:bg-white dark:hover:bg-slate-700/60' ?>"
            title="Discover Startups">
            <i data-lucide="sparkles" class="w-3.5 h-3.5 flex-shrink-0"></i>
            <span>Discover</span>
        </a>

        <!-- Investor Home / Dashboard -->
        <a href="<?= url('investor/dashboard.php') ?>"
            class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $currentPage === 'dashboard.php' ? 'bg-[#123B7A] text-white shadow-xs dark:bg-blue-600' : 'text-[#4B5563] dark:text-slate-300 hover:text-[#123B7A] dark:hover:text-white hover:bg-white dark:hover:bg-slate-700/60' ?>"
            title="Investor Home">
            <i data-lucide="home" class="w-3.5 h-3.5 flex-shrink-0"></i>
            <span>Home</span>
        </a>

        <!-- Portfolio Holdings -->
        <a href="<?= url('investor/portfolio.php') ?>"
            class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $currentPage === 'portfolio.php' ? 'bg-[#123B7A] text-white shadow-xs dark:bg-blue-600' : 'text-[#4B5563] dark:text-slate-300 hover:text-[#123B7A] dark:hover:text-white hover:bg-white dark:hover:bg-slate-700/60' ?>"
            title="Portfolio Holdings">
            <i data-lucide="briefcase" class="w-3.5 h-3.5 flex-shrink-0"></i>
            <span>Portfolio</span>
        </a>

        <!-- Saved Watchlist -->
        <a href="<?= url('investor/watchlist.php') ?>"
            class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $currentPage === 'watchlist.php' ? 'bg-[#123B7A] text-white shadow-xs dark:bg-blue-600' : 'text-[#4B5563] dark:text-slate-300 hover:text-[#123B7A] dark:hover:text-white hover:bg-white dark:hover:bg-slate-700/60' ?>"
            title="Saved Companies">
            <i data-lucide="bookmark" class="w-3.5 h-3.5 flex-shrink-0"></i>
            <span>Watchlist</span>
        </a>

        <!-- Deal Conversations -->
        <a href="<?= url('investor/messages.php') ?>"
            class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $currentPage === 'messages.php' ? 'bg-[#123B7A] text-white shadow-xs dark:bg-blue-600' : 'text-[#4B5563] dark:text-slate-300 hover:text-[#123B7A] dark:hover:text-white hover:bg-white dark:hover:bg-slate-700/60' ?>"
            title="Deal Conversations">
            <i data-lucide="message-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
            <span>Messages</span>
        </a>
    </nav>

    <!-- Right Section: Quick Search + Notifications + Dark/Light Mode Option -->
    <div class="flex items-center space-x-2.5 sm:space-x-3.5 flex-shrink-0">
        <!-- Quick Deal Flow Search -->
        <a href="<?= url('investor/discover.php') ?>"
            class="hidden lg:flex items-center space-x-2 px-3 py-2 rounded-xl bg-[#FAFBFD] dark:bg-slate-800 hover:bg-[#EAF2FF] dark:hover:bg-slate-700/80 text-xs text-[#4B5563] dark:text-slate-300 hover:text-[#123B7A] dark:hover:text-white border border-[#E4E8EF] dark:border-slate-700 transition font-medium">
            <i data-lucide="search" class="w-3.5 h-3.5 text-[#4B5563] dark:text-slate-400"></i>
            <span>Search Startups...</span>
        </a>

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
                                    <?= htmlspecialchars($n['title']) ?></div>
                                <div class="text-[#4B5563] dark:text-slate-300 text-xs sm:text-sm mt-1 leading-relaxed">
                                    <?= htmlspecialchars($n['message']) ?></div>
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

        <!-- Dark / Light Theme Mode Toggle Button (Replaced Profile next to notification) -->
        <button id="investor-theme-toggle-btn" onclick="toggleInvestorTheme()" type="button"
            class="relative p-2.5 rounded-xl bg-[#FAFBFD] dark:bg-slate-800 hover:bg-[#EAF2FF] dark:hover:bg-slate-700 border border-[#E4E8EF] dark:border-slate-700 text-[#4B5563] dark:text-slate-200 transition flex items-center justify-center cursor-pointer shadow-xs group"
            title="Toggle Dark / Light Theme" aria-label="Toggle Dark / Light Theme">
            <!-- Sun Icon (Active in Dark Mode) -->
            <span id="investor-sun-wrap" class="hidden flex items-center justify-center">
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
    // Toggle Sections Dropdown
    function toggleInvestorSectionsDropdown() {
        const menu = document.getElementById('investor-sections-menu');
        const notifMenu = document.getElementById('investor-notif-menu');
        if (notifMenu && !notifMenu.classList.contains('hidden')) {
            notifMenu.classList.add('hidden');
        }
        if (menu) {
            menu.classList.toggle('hidden');
        }
    }

    // Toggle Notifications Dropdown
    function toggleInvestorNotifs() {
        const notifMenu = document.getElementById('investor-notif-menu');
        const sectionsMenu = document.getElementById('investor-sections-menu');
        if (sectionsMenu && !sectionsMenu.classList.contains('hidden')) {
            sectionsMenu.classList.add('hidden');
        }
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
                moon.classList.add('hidden');
                if (btn) {
                    btn.setAttribute('title', 'Switch to Light Mode');
                    btn.setAttribute('aria-label', 'Switch to Light Mode');
                }
            } else {
                sun.classList.add('hidden');
                moon.classList.remove('hidden');
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

        const sectionsMenu = document.getElementById('investor-sections-menu');
        const sectionsWrap = document.getElementById('investor-sections-wrapper');
        if (sectionsMenu && !sectionsMenu.contains(e.target) && sectionsWrap && !sectionsWrap.contains(e.target)) {
            sectionsMenu.classList.add('hidden');
        }
    });

    // Initialize Theme Icon State Immediately
    (function () {
        const isDark = document.documentElement.classList.contains('dark');
        syncInvestorThemeIcons(isDark);
    })();
    document.addEventListener('DOMContentLoaded', function () {
        const isDark = document.documentElement.classList.contains('dark');
        syncInvestorThemeIcons(isDark);
    });
</script>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>