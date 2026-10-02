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
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }

    body {
        background-color: #FAFBFD;
        color: #111827;
        font-size: 1rem;
        line-height: 1.6;
    }

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

    .network-row {
        background: #FFFFFF;
        border-bottom: 1px solid #E4E8EF;
        transition: background-color 0.18s ease, border-color 0.18s ease;
    }

    .network-row:hover {
        background: #F8FAFD;
    }

    .editorial-divider {
        height: 1px;
        background: #E4E8EF;
    }

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
    <!-- Left Section: Hamburger Menu + Back Button -->
    <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
        <!-- Desktop Sidebar Rail Toggle Button -->
        <button type="button" 
                onclick="toggleDesktopSidebar()" 
                id="investor-sidebar-toggle-btn"
                class="hidden lg:flex w-9 h-9 sm:w-10 sm:h-10 rounded-xl border border-[#E4E8EF] dark:border-slate-700 bg-[#FAFBFD] dark:bg-slate-800 text-[#4B5563] hover:text-[#123B7A] dark:text-slate-400 dark:hover:text-blue-400 hover:bg-[#EAF2FF] dark:hover:bg-blue-950/50 hover:border-blue-300 dark:hover:border-blue-700 active:scale-95 transition-all items-center justify-center shadow-2xs" 
                title="Toggle Sidebar Rail (Collapse / Expand)"
                aria-label="Toggle sidebar width">
            <i data-lucide="panel-left" class="w-4.5 h-4.5"></i>
        </button>

        <!-- Hamburger Menu Button (Mobile & Tablet) -->
        <button type="button" onclick="toggleMobileSidebar()"
            class="lg:hidden p-2 rounded-xl text-[#4B5563] dark:text-slate-300 hover:text-[#111827] dark:hover:text-white hover:bg-[#FAFBFD] dark:hover:bg-slate-800 border border-transparent hover:border-[#E4E8EF] dark:hover:border-slate-700 transition flex-shrink-0"
            aria-label="Open sidebar menu">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>

        <?php if (($currentPage ?? basename($_SERVER['PHP_SELF'])) !== 'dashboard.php'): ?>
        <!-- Clean & Bold Back Icon Button -->
        
        <?php endif; ?>

    </div>

    <!-- Center Section: Main Nav (Discover first) -->
    <nav id="investor-top-nav"
        class="hidden md:flex items-center space-x-1 lg:space-x-1.5 px-2 py-1 rounded-2xl bg-[#FAFBFD] dark:bg-slate-800/80 border border-[#E4E8EF] dark:border-slate-800">
        <?php foreach ($navItems as $item): ?>
            <a href="<?= $item['href'] ?>"
                class="top-nav-link flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $item['active'] ? $navActive : $navInactive ?>"
                title="<?= htmlspecialchars($item['title']) ?>" <?= $item['active'] ? 'aria-current="page"' : '' ?>>
                <i data-lucide="<?= $item['icon'] ?>" class="w-3.5 h-3.5 flex-shrink-0"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Right Section: Contact + Notifications + Dark Mode + Profile -->
    <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">

        <!-- Contact & Support Dropdown -->
        <div class="relative hidden xl:block" id="investor-contact-wrapper">
          

            <!-- Contact Dropdown Panel -->
            <div id="investor-contact-menu"
                class="hidden absolute right-0 mt-2.5 w-72 bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl shadow-2xl z-50 overflow-hidden">

                <!-- Panel Header -->
                <div class="px-4 py-3 bg-gradient-to-r from-[#123B7A]/5 to-blue-50/80 dark:from-blue-950/30 dark:to-slate-800/60 border-b border-[#E4E8EF] dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <div class="w-7 h-7 rounded-lg bg-[#123B7A] flex items-center justify-center flex-shrink-0">
                            <i data-lucide="life-buoy" class="w-3.5 h-3.5 text-white"></i>
                        </div>
                        <div>
                            <div class="text-xs font-extrabold text-[#0B1F3A] dark:text-white">Investor Support Hub</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">We typically reply within 2 hours</div>
                        </div>
                    </div>
                </div>

                <!-- Contact Options -->
                <div class="p-2 space-y-0.5">
                    <!-- Email Support -->
                    <a href="mailto:investor.support@startupportal.in"
                        class="flex items-center space-x-3 px-3 py-2.5 rounded-xl hover:bg-blue-50 dark:hover:bg-slate-800 group transition">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-950/50 flex items-center justify-center flex-shrink-0 group-hover:bg-[#123B7A] transition">
                            <i data-lucide="mail" class="w-4 h-4 text-[#123B7A] dark:text-blue-400 group-hover:text-white transition"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-[#123B7A] dark:group-hover:text-blue-400 transition">Email Support</div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate">investor.support@startupportal.in</div>
                        </div>
                    </a>

                    <!-- WhatsApp -->
                    <a href="https://wa.me/919876543210?text=Hi%2C%20I%20need%20investor%20support" target="_blank" rel="noopener noreferrer"
                        class="flex items-center space-x-3 px-3 py-2.5 rounded-xl hover:bg-emerald-50 dark:hover:bg-slate-800 group transition">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/50 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-500 transition">
                            <i data-lucide="message-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 group-hover:text-white transition"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition">WhatsApp Support</div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500">+91 98765 43210 · Instant help</div>
                        </div>
                    </a>

                    <!-- Live Chat / Helpdesk -->
                    <a href="<?= url('investor/support.php') ?>"
                        class="flex items-center space-x-3 px-3 py-2.5 rounded-xl hover:bg-violet-50 dark:hover:bg-slate-800 group transition">
                        <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-950/50 flex items-center justify-center flex-shrink-0 group-hover:bg-violet-600 transition">
                            <i data-lucide="zap" class="w-4 h-4 text-violet-600 dark:text-violet-400 group-hover:text-white transition"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition">Live Helpdesk</div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500">Raise a ticket · Track status</div>
                        </div>
                    </a>

                    <!-- Investor Community -->
                    <a href="<?= url('investor/community.php') ?>"
                        class="flex items-center space-x-3 px-3 py-2.5 rounded-xl hover:bg-amber-50 dark:hover:bg-slate-800 group transition">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-950/50 flex items-center justify-center flex-shrink-0 group-hover:bg-amber-500 transition">
                            <i data-lucide="users" class="w-4 h-4 text-amber-600 dark:text-amber-400 group-hover:text-white transition"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition">Investor Community</div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500">Forum · Peer network · Events</div>
                        </div>
                    </a>

                    <!-- Regulatory / Compliance Help -->
                    <a href="https://www.sebi.gov.in" target="_blank" rel="noopener noreferrer"
                        class="flex items-center space-x-3 px-3 py-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 group transition">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0 group-hover:bg-slate-600 transition">
                            <i data-lucide="shield-check" class="w-4 h-4 text-slate-500 dark:text-slate-400 group-hover:text-white transition"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-slate-600 dark:group-hover:text-slate-300 transition">Regulatory Guidance</div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500">SEBI · DPIIT · Compliance FAQ</div>
                        </div>
                    </a>
                </div>

                <!-- Footer CTA -->
                <div class="px-4 py-3 border-t border-[#E4E8EF] dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40">
                    <a href="<?= url('investor/support.php') ?>"
                        class="flex items-center justify-center space-x-1.5 text-xs font-extrabold text-[#123B7A] dark:text-blue-400 hover:underline transition">
                        <i data-lucide="arrow-right-circle" class="w-3.5 h-3.5"></i>
                        <span>View Full Support Center →</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Notifications Bell -->
        <div class="relative" id="investor-notif-dropdown-wrapper">
            <button id="investor-notif-btn" onclick="toggleInvestorNotifs(event)" type="button"
                class="relative p-2.5 rounded-xl bg-[#FAFBFD] dark:bg-slate-800 hover:bg-[#EAF2FF] dark:hover:bg-slate-700 border border-[#E4E8EF] dark:border-slate-700 text-[#4B5563] dark:text-slate-200 hover:text-[#123B7A] dark:hover:text-white transition cursor-pointer"
                aria-label="Notifications"
                aria-expanded="false">
                <i data-lucide="bell" class="w-4 h-4 sm:w-5 sm:h-5 pointer-events-none"></i>
                <?php if ($unreadCount > 0): ?>
                    <span
                        class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-[#123B7A] dark:bg-blue-600 text-white text-[11px] font-bold flex items-center justify-center pointer-events-none">
                        <?= $unreadCount ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Notifications Dropdown -->
            <div id="investor-notif-menu"
                onclick="event.stopPropagation()"
                class="hidden absolute right-0 mt-2 w-80 sm:w-96 max-w-[calc(100vw-24px)] bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl shadow-2xl p-4 z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                <div
                    class="flex items-center justify-between pb-3 border-b border-[#E4E8EF] dark:border-slate-800 text-sm font-extrabold text-[#0B1F3A] dark:text-white">
                    <span>Deal Alerts & Updates</span>
                    <a href="<?= url('notifications.php') ?>" class="text-xs text-[#123B7A] dark:text-blue-400 hover:underline font-bold">View all</a>
                </div>
                <div class="divide-y divide-[#E4E8EF] dark:divide-slate-800 max-h-72 overflow-y-auto">
                    <?php if (empty($notifs)): ?>
                        <div class="py-8 text-center text-sm text-[#667085] dark:text-slate-400">No unread alerts</div>
                    <?php else: ?>
                        <?php foreach ($notifs as $n): ?>
                            <a href="<?= !empty($n['action_url']) ? url($n['action_url']) : url('notifications.php') ?>" class="block py-3 px-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800/60 rounded-xl transition <?= $n['is_read'] ? 'opacity-70' : 'font-semibold' ?>">
                                <div class="text-[#111827] dark:text-slate-100 text-sm font-bold truncate">
                                    <?= htmlspecialchars($n['title']) ?>
                                </div>
                                <div class="text-[#4B5563] dark:text-slate-300 text-xs sm:text-sm mt-1 leading-relaxed line-clamp-2">
                                    <?= htmlspecialchars($n['message']) ?>
                                </div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500 font-medium mt-1">
                                    <?= function_exists('time_elapsed_string') ? time_elapsed_string($n['created_at']) : date('M d, Y', strtotime($n['created_at'])) ?>
                                </div>
                            </a>
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

        <!-- User Profile Dropdown -->
        <div class="relative" id="investor-profile-wrapper">
            <button onclick="toggleInvestorProfile()" type="button" class="flex items-center space-x-2 h-10 pl-1.5 pr-2.5 rounded-full border border-[#E4E8EF] dark:border-slate-700 hover:bg-[#FAFBFD] dark:hover:bg-slate-800 transition bg-white dark:bg-slate-900 shadow-2xs group cursor-pointer flex-shrink-0" aria-label="Investor Profile Menu">
                <img src="<?= $currentUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100' ?>" 
                     alt="<?= htmlspecialchars($currentUser['name'] ?? 'Investor') ?>" 
                     style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; max-width: 32px; max-height: 32px; border-radius: 9999px; object-fit: cover;"
                     class="w-8 h-8 rounded-full object-cover border border-[#E4E8EF] dark:border-slate-700 flex-shrink-0">
                <div class="hidden sm:flex flex-col text-left">
                    <span class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 group-hover:text-blue-600 transition truncate max-w-[110px] leading-tight"><?= htmlspecialchars(explode(' ', $currentUser['name'] ?? 'Investor')[0]) ?></span>
                    <span class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider leading-none">Angel</span>
                </div>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-200 transition flex-shrink-0"></i>
            </button>

            <!-- Profile Dropdown Menu -->
            <div id="investor-profile-menu" class="hidden absolute right-0 mt-2 w-64 bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl shadow-xl p-2 z-50">
                <!-- User Header Summary -->
                <div class="p-3 border-b border-[#E4E8EF] dark:border-slate-800 flex items-center space-x-3 bg-slate-50/70 dark:bg-slate-800/50 rounded-xl mb-1">
                    <img src="<?= $currentUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100' ?>" 
                         alt="<?= htmlspecialchars($currentUser['name'] ?? 'Investor') ?>"
                         style="width: 40px; height: 40px; min-width: 40px; min-height: 40px; max-width: 40px; max-height: 40px; border-radius: 9999px; object-fit: cover;"
                         class="w-10 h-10 rounded-full object-cover border border-[#E4E8EF] dark:border-slate-700 flex-shrink-0">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-slate-900 dark:text-slate-100 truncate leading-tight"><?= htmlspecialchars($currentUser['name'] ?? 'Investor') ?></div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5"><?= htmlspecialchars($currentUser['email'] ?? '') ?></div>
                        <span class="inline-flex items-center px-1.5 py-0.5 mt-1 rounded text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/40">Angel Investor</span>
                    </div>
                </div>

                <div class="py-1 space-y-0.5 text-sm">
                    <a href="<?= url('investor/profile.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800 font-bold transition">
                        <i data-lucide="sliders" class="w-4 h-4 text-slate-400"></i>
                        <span>Thesis & Settings</span>
                    </a>
                    <a href="<?= url('investor/view.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800 font-bold transition">
                        <i data-lucide="external-link" class="w-4 h-4 text-slate-400"></i>
                        <span>Public Profile Preview</span>
                    </a>
                    <a href="<?= url('investor/portfolio.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800 font-bold transition">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-slate-400"></i>
                        <span>My Portfolio</span>
                    </a>
                </div>

                <div class="pt-1.5 mt-1 border-t border-[#E4E8EF] dark:border-slate-800">
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
    // Toggle Contact & Support Dropdown
    function toggleInvestorContact() {
        const contactMenu = document.getElementById('investor-contact-menu');
        const chevron = document.getElementById('investor-contact-chevron');
        const notifMenu = document.getElementById('investor-notif-menu');
        const profileMenu = document.getElementById('investor-profile-menu');
        if (notifMenu) notifMenu.classList.add('hidden');
        if (profileMenu) profileMenu.classList.add('hidden');
        if (contactMenu) {
            const isHidden = contactMenu.classList.toggle('hidden');
            if (chevron) {
                chevron.style.transform = isHidden ? '' : 'rotate(180deg)';
            }
            if (!isHidden && window.lucide) lucide.createIcons();
        }
    }

    // Toggle Notifications Dropdown
    function toggleInvestorNotifs(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const notifMenu = document.getElementById('investor-notif-menu');
        const notifBtn = document.getElementById('investor-notif-btn');
        const profileMenu = document.getElementById('investor-profile-menu');
        if (profileMenu) profileMenu.classList.add('hidden');
        if (!notifMenu) return;

        const isHidden = notifMenu.classList.contains('hidden');
        if (isHidden) {
            notifMenu.classList.remove('hidden');
            if (notifBtn) notifBtn.setAttribute('aria-expanded', 'true');
        } else {
            notifMenu.classList.add('hidden');
            if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
        }
    }

    // Toggle Profile Dropdown
    function toggleInvestorProfile(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const profileMenu = document.getElementById('investor-profile-menu');
        const notifMenu = document.getElementById('investor-notif-menu');
        const notifBtn = document.getElementById('investor-notif-btn');
        if (notifMenu) notifMenu.classList.add('hidden');
        if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
        if (!profileMenu) return;

        profileMenu.classList.toggle('hidden');
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
        const notifWrapper = document.getElementById('investor-notif-dropdown-wrapper');
        const notifBtn = document.getElementById('investor-notif-btn');
        if (notifMenu && !notifMenu.classList.contains('hidden')) {
            if (!notifWrapper || !notifWrapper.contains(e.target)) {
                notifMenu.classList.add('hidden');
                if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
            }
        }

        const profileWrapper = document.getElementById('investor-profile-wrapper');
        const profileMenu = document.getElementById('investor-profile-menu');
        if (profileWrapper && profileMenu && !profileWrapper.contains(e.target)) {
            profileMenu.classList.add('hidden');
        }

        const contactWrapper = document.getElementById('investor-contact-wrapper');
        const contactMenu = document.getElementById('investor-contact-menu');
        const chevron = document.getElementById('investor-contact-chevron');
        if (contactWrapper && contactMenu && !contactWrapper.contains(e.target)) {
            contactMenu.classList.add('hidden');
            if (chevron) chevron.style.transform = '';
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const notifMenu = document.getElementById('investor-notif-menu');
            const notifBtn = document.getElementById('investor-notif-btn');
            if (notifMenu && !notifMenu.classList.contains('hidden')) {
                notifMenu.classList.add('hidden');
                if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
            }
            const profileMenu = document.getElementById('investor-profile-menu');
            if (profileMenu && !profileMenu.classList.contains('hidden')) {
                profileMenu.classList.add('hidden');
            }
            const contactMenu = document.getElementById('investor-contact-menu');
            const chevron = document.getElementById('investor-contact-chevron');
            if (contactMenu && !contactMenu.classList.contains('hidden')) {
                contactMenu.classList.add('hidden');
                if (chevron) chevron.style.transform = '';
            }
        }
    });

    // Initialize Theme Icon State Immediately
    (function () {
        const isDark = document.documentElement.classList.contains('dark');
        syncInvestorThemeIcons(isDark);
    })();

    // Back Button Functionality with Micro-Animation
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