<?php
/**
 * Investor Navigation Sidebar Component
 * Professional Investor Network Experience
 * Font: Plus Jakarta Sans, Navy / Electric Blue Theme
 * Supports full-expanded mode and ultra-sleek compact icon rail mode with floating tooltips & smooth width transitions.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$investorUser = current_user() ?? ['name' => 'Investor', 'email' => 'investor@portal.com', 'avatar_url' => ''];
$investorProgress = isset($investorUser['id']) ? get_profile_progress($investorUser['id'], 'investor') : ['percentage' => 100];

// Load Investor Dark & Light Theme Controller
require_once __DIR__ . '/theme.php';
?>
<!-- Mobile Drawer Backdrop -->
<div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()"
    class="fixed inset-0 bg-[#0B1F3A]/40 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity duration-300"></div>

<style>
    #main-sidebar .overflow-y-auto {
        scrollbar-width: thin;
        scrollbar-color: rgba(148, 163, 184, 0.4) transparent;
        overscroll-behavior: contain;
        scroll-behavior: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    #main-sidebar .overflow-y-auto::-webkit-scrollbar {
        width: 4px;
    }

    #main-sidebar .overflow-y-auto::-webkit-scrollbar-track {
        background: transparent;
    }

    #main-sidebar .overflow-y-auto::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.35);
        border-radius: 9999px;
    }

    #main-sidebar .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: rgba(148, 163, 184, 0.6);
    }

    /* ── Rail Collapsed Mode (Ultra-sleek Icon Only) ── */
    #main-sidebar {
        transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1), transform 0.28s ease-in-out;
    }

    #main-sidebar.rail-collapsed {
        width: 4.75rem !important;
        min-width: 4.75rem !important;
    }

    #main-sidebar.rail-collapsed .sidebar-scroll-body {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
        padding-top: 1rem !important;
    }

    #main-sidebar.rail-collapsed .sidebar-text-item,
    #main-sidebar.rail-collapsed .investor-identity-card {
        display: none !important;
    }

    #main-sidebar.rail-collapsed .sidebar-brand-header {
        justify-content: center !important;
        padding-bottom: 0.5rem !important;
        margin-bottom: 0.5rem !important;
        border-bottom: 1px solid #E4E8EF !important;
    }

    .dark #main-sidebar.rail-collapsed .sidebar-brand-header {
        border-bottom-color: #1e293b !important;
    }

    #main-sidebar.rail-collapsed .sidebar-brand-link {
        justify-content: center !important;
        margin: 0 auto !important;
    }

    #main-sidebar.rail-collapsed .sidebar-brand-link > div:first-child {
        width: 2.75rem !important;
        height: 2.75rem !important;
        margin: 0 auto !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 0.75rem !important;
    }

    #main-sidebar.rail-collapsed .sidebar-brand-link svg,
    #main-sidebar.rail-collapsed .sidebar-brand-link i {
        width: 1.35rem !important;
        height: 1.35rem !important;
    }

    #main-sidebar.rail-collapsed .sidebar-section-title {
        display: flex !important;
        justify-content: center !important;
        padding: 0.35rem 0.25rem !important;
        margin: 0.35rem 0 !important;
        height: 1px !important;
        overflow: hidden !important;
        color: transparent !important;
        border-top: 1px solid #E4E8EF;
    }

    .dark #main-sidebar.rail-collapsed .sidebar-section-title {
        border-top-color: #1e293b;
    }

    #main-sidebar.rail-collapsed .sidebar-section-title * {
        display: none !important;
    }

    #main-sidebar.rail-collapsed .sidebar-link {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 2.75rem !important;
        height: 2.75rem !important;
        padding: 0 !important;
        margin: 0.35rem auto !important;
        border-radius: 0.75rem !important;
        position: relative !important;
        border: none !important;
    }

    #main-sidebar.rail-collapsed .sidebar-link > div {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    #main-sidebar.rail-collapsed .sidebar-link svg,
    #main-sidebar.rail-collapsed .sidebar-link i,
    #main-sidebar.rail-collapsed .nav-icon {
        width: 1.25rem !important;
        height: 1.25rem !important;
        margin: 0 !important;
        flex-shrink: 0 !important;
    }

    #main-sidebar.rail-collapsed .sidebar-link:hover {
        transform: scale(1.06) !important;
        background: #F8FAFD !important;
        color: #123B7A !important;
    }

    .dark #main-sidebar.rail-collapsed .sidebar-link:hover {
        background: #1e293b !important;
        color: #60a5fa !important;
    }

    /* Active Squircle State - Crisp White Icon with Glowing Background */
    #main-sidebar.rail-collapsed .sidebar-link.is-active {
        background: #123B7A !important;
        box-shadow: 0 4px 14px rgba(18, 59, 122, 0.45) !important;
    }

    #main-sidebar.rail-collapsed .sidebar-link.is-active,
    #main-sidebar.rail-collapsed .sidebar-link.is-active *,
    #main-sidebar.rail-collapsed .sidebar-link.is-active svg,
    #main-sidebar.rail-collapsed .sidebar-link.is-active i {
        color: #ffffff !important;
        stroke: #ffffff !important;
        fill: none !important;
    }

    .dark #main-sidebar.rail-collapsed .sidebar-link.is-active {
        background: #2563eb !important;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.5) !important;
    }

    /* Floating Tooltip in collapsed rail */
    #main-sidebar.rail-collapsed .sidebar-link[data-tooltip]::after,
    #main-sidebar.rail-collapsed .sidebar-footer-user[data-tooltip]::after,
    #main-sidebar.rail-collapsed .sidebar-logout-btn a[data-tooltip]::after {
        content: attr(data-tooltip);
        position: absolute;
        left: calc(100% + 0.65rem);
        top: 50%;
        transform: translateY(-50%) scale(0.92);
        background: #0f172a;
        color: #ffffff;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        padding: 0.4rem 0.75rem;
        border-radius: 0.5rem;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.15s cubic-bezier(0.16, 1, 0.3, 1), transform 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 6px 16px -2px rgba(0, 0, 0, 0.25);
        z-index: 99999;
    }

    .dark #main-sidebar.rail-collapsed .sidebar-link[data-tooltip]::after,
    .dark #main-sidebar.rail-collapsed .sidebar-footer-user[data-tooltip]::after,
    .dark #main-sidebar.rail-collapsed .sidebar-logout-btn a[data-tooltip]::after {
        background: #1e293b;
        color: #f8fafc;
        border: 1px solid #334155;
    }

    #main-sidebar.rail-collapsed .sidebar-link:hover::after,
    #main-sidebar.rail-collapsed .sidebar-footer-user:hover::after,
    #main-sidebar.rail-collapsed .sidebar-logout-btn a:hover::after {
        opacity: 1;
        transform: translateY(-50%) scale(1);
    }

    /* Footer in collapsed rail */
    #main-sidebar.rail-collapsed .sidebar-footer-container {
        padding: 0.75rem 0.5rem !important;
    }

    #main-sidebar.rail-collapsed .sidebar-footer-inner {
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.65rem !important;
    }

    #main-sidebar.rail-collapsed .sidebar-footer-user {
        justify-content: center !important;
        position: relative !important;
    }

    #main-sidebar.rail-collapsed .sidebar-footer-user img {
        width: 2.25rem !important;
        height: 2.25rem !important;
    }

    #main-sidebar.rail-collapsed .sidebar-logout-btn {
        margin: 0 !important;
        position: relative !important;
    }
</style>

<aside id="main-sidebar" data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
    class="fixed inset-y-0 left-0 z-50 w-72 sm:w-68 bg-white dark:bg-slate-900 border-r border-[#E4E8EF] dark:border-slate-800 flex flex-col justify-between h-full transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:w-68 flex-shrink-0 transition-transform duration-300 ease-in-out select-none shadow-sm"
    style="font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;">
    <div data-lenis-prevent="true" data-lenis-prevent-wheel="true" class="sidebar-scroll-body p-5 sm:p-6 overflow-y-auto flex-1 space-y-6">
        <!-- Brand Identity & Close Button -->
        <div class="sidebar-brand-header flex items-center justify-between pb-5 border-b border-[#E4E8EF] dark:border-slate-800">
            <a href="<?= url('investor/discover.php') ?>" class="sidebar-brand-link flex items-center space-x-3.5 group min-w-0" data-tooltip="Investor Network" title="Investor Network">
                <div
                    class="w-10 h-10 rounded-xl bg-[#123B7A] dark:bg-blue-600 flex items-center justify-center text-white shadow-sm group-hover:bg-[#0B1F3A] dark:group-hover:bg-blue-700 transition flex-shrink-0">
                    <i data-lucide="compass" class="w-5 h-5"></i>
                </div>
                <div class="sidebar-text-item min-w-0">
                    <div
                        class="font-black text-[#0B1F3A] dark:text-white text-base tracking-tight leading-tight flex items-center gap-1.5 whitespace-nowrap">
                        <span>INVESTOR</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider whitespace-nowrap">Syndicate &amp; Deals</div>
                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()"
                class="lg:hidden p-2 rounded-xl text-[#4B5563] dark:text-slate-300 hover:text-[#111827] dark:hover:text-white hover:bg-[#FAFBFD] dark:hover:bg-slate-800 transition flex-shrink-0"
                title="Close navigation">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Investor Identity Strip -->
        <div class="investor-identity-card pb-5 border-b border-[#E4E8EF] dark:border-slate-800">
            <div class="flex items-center space-x-3.5 mb-3.5">
                <img src="<?= $investorUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100' ?>"
                    class="w-11 h-11 rounded-full object-cover border-2 border-[#E4E8EF] dark:border-slate-700 shadow-xs flex-shrink-0">
                <div class="min-w-0">
                    <div class="text-sm font-black text-[#0B1F3A] dark:text-white truncate">
                        <?= htmlspecialchars($investorUser['name']) ?>
                    </div>
                    <div class="text-xs text-[#4B5563] dark:text-slate-400 font-medium truncate mt-0.5">
                        <?= htmlspecialchars($investorUser['email']) ?>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="text-[#4B5563] dark:text-slate-400 font-semibold flex items-center gap-1.5">
                    <i data-lucide="shield-check"
                        class="w-4 h-4 <?= !empty($investorUser['is_verified']) ? 'text-[#123B7A] dark:text-blue-400' : 'text-amber-500' ?>"></i>
                    Accreditation Status
                </span>
                <span
                    class="text-[#123B7A] dark:text-blue-400 font-extrabold"><?= $investorProgress['percentage'] ?>%</span>
            </div>
            <div class="w-full h-1.5 bg-[#E4E8EF] dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-[#123B7A] dark:bg-blue-600 rounded-full transition-all duration-500"
                    style="width: <?= $investorProgress['percentage'] ?>%"></div>
            </div>
        </div>

        <?php
        $navGroups = [
           
            'Identity & Trust' => [
                [
                    'title' => 'Public Profile',
                    'url' => url('investor/view.php'),
                    'icon' => 'user-check',
                    'active' => ($currentPage === 'view.php')
                ],
                [
                    'title' => 'Thesis & Settings',
                    'url' => url('investor/profile.php'),
                    'icon' => 'sliders-horizontal',
                    'active' => ($currentPage === 'profile.php')
                ],
                [
                    'title' => 'Verification Center',
                    'url' => url('investor/verification.php'),
                    'icon' => 'shield-check',
                    'active' => ($currentPage === 'verification.php')
                ],
            ]
        ];
        ?>

        <!-- Navigation Links with Prominent Active Indicators -->
        <div class="space-y-5">
            <?php foreach ($navGroups as $groupTitle => $items): ?>
                <div>
                    <div
                        class="sidebar-section-title text-[11px] font-bold text-[#667085] dark:text-slate-400 uppercase tracking-wider px-3.5 mb-2">
                        <span><?= $groupTitle ?></span>
                    </div>
                    <nav class="space-y-1">
                        <?php foreach ($items as $item): ?>
                            <a href="<?= $item['url'] ?>"
                                data-tooltip="<?= htmlspecialchars($item['title']) ?>"
                                title="<?= htmlspecialchars($item['title']) ?>"
                                class="sidebar-link flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm transition font-semibold group border <?= $item['active'] ? 'is-active bg-[#EAF2FF] dark:bg-blue-950/70 text-[#123B7A] dark:text-blue-300 font-bold shadow-xs border-[#123B7A]/20 dark:border-blue-700/60' : 'text-[#4B5563] dark:text-slate-300 hover:text-[#111827] dark:hover:text-white hover:bg-[#FAFBFD] dark:hover:bg-slate-800 border-transparent' ?>">
                                <div class="flex items-center space-x-3 min-w-0">
                                    <i data-lucide="<?= $item['icon'] ?>"
                                        class="nav-icon w-4.5 h-4.5 flex-shrink-0 <?= $item['active'] ? 'text-[#123B7A] dark:text-blue-400' : 'text-[#667085] dark:text-slate-400 group-hover:text-[#111827] dark:group-hover:text-white' ?>"></i>
                                    <span class="truncate sidebar-text-item"><?= htmlspecialchars($item['title']) ?></span>
                                </div>
                                <?php if ($item['active']): ?>
                                    <span class="w-1.5 h-4 rounded-full bg-[#123B7A] dark:bg-blue-400 flex-shrink-0 sidebar-text-item active-indicator"
                                        title="Active Page"></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            <?php endforeach; ?>
        </div>
    </div>


</aside>

<script>
    // Immediate synchronous check to prevent layout jump
    if (localStorage.getItem('investor_sidebar_collapsed') === '1' && window.innerWidth >= 1024) {
        document.getElementById('main-sidebar')?.classList.add('rail-collapsed');
        document.body?.classList.add('sidebar-collapsed');
    }

    function toggleMobileSidebar() {
        const sidebar = document.getElementById('main-sidebar');
        const backdrop = document.getElementById('mobile-sidebar-backdrop');
        if (!sidebar) return;
        const isClosed = sidebar.classList.contains('-translate-x-full');
        if (isClosed) {
            sidebar.classList.remove('-translate-x-full');
            if (backdrop) backdrop.classList.remove('hidden');
            document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
        } else {
            sidebar.classList.add('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
            document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
        }
    }

    // Toggle Desktop Sidebar (Expanded vs Compact Rail)
    function toggleDesktopSidebar() {
        const sidebar = document.getElementById('main-sidebar');
        if (!sidebar) return;
        const isCollapsed = sidebar.classList.contains('rail-collapsed');
        if (isCollapsed) {
            sidebar.classList.remove('rail-collapsed');
            document.body.classList.remove('sidebar-collapsed');
            localStorage.setItem('investor_sidebar_collapsed', '0');
        } else {
            sidebar.classList.add('rail-collapsed');
            document.body.classList.add('sidebar-collapsed');
            localStorage.setItem('investor_sidebar_collapsed', '1');
        }
        window.dispatchEvent(new Event('resize'));
    }

    // Restore desktop sidebar collapsed state on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        const savedState = localStorage.getItem('investor_sidebar_collapsed');
        const sidebar = document.getElementById('main-sidebar');
        if (savedState === '1' && sidebar && window.innerWidth >= 1024) {
            sidebar.classList.add('rail-collapsed');
            document.body.classList.add('sidebar-collapsed');
        }
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    // Keyboard shortcut (Ctrl+B or Cmd+B) to toggle sidebar rail
    window.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b' && window.innerWidth >= 1024) {
            const activeTag = document.activeElement ? document.activeElement.tagName : '';
            if (activeTag !== 'INPUT' && activeTag !== 'TEXTAREA') {
                e.preventDefault();
                toggleDesktopSidebar();
            }
        }
    });

    // Direct mouse wheel scroll engine for investor sidebar
    (function () {
        const sidebar = document.getElementById('main-sidebar');
        if (!sidebar) return;
        const scrollContainer = sidebar.querySelector('.overflow-y-auto');
        if (!scrollContainer) return;

        sidebar.addEventListener('wheel', function (e) {
            if (scrollContainer.scrollHeight > scrollContainer.clientHeight) {
                scrollContainer.scrollTop += e.deltaY;
                e.stopPropagation();
                e.preventDefault();
            }
        }, { passive: false });
    })();

    /* =======================================================
       PERSISTENT INVESTOR DASHBOARD SPA SECTION ROUTER
       Swaps only <main> content via AJAX Fetch without reloading
       Persistent sidebar, header, and active states.
       ======================================================= */
    (function () {
        // 1. Progress Bar Element
        let progressBar = document.getElementById('spa-progress-bar');
        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.id = 'spa-progress-bar';
            progressBar.style.cssText = 'position:fixed;top:0;left:0;height:3px;width:0%;background:linear-gradient(90deg, #123B7A, #3B82F6, #60A5FA);z-index:99999;transition:width 0.22s ease, opacity 0.28s ease;opacity:0;pointer-events:none;box-shadow:0 0 10px rgba(59,130,246,0.65);';
            document.body.appendChild(progressBar);
        }

        let progressTimer = null;
        function startProgress() {
            clearTimeout(progressTimer);
            progressBar.style.transition = 'width 0.22s ease, opacity 0.18s ease';
            progressBar.style.opacity = '1';
            progressBar.style.width = '30%';
            progressTimer = setTimeout(() => {
                progressBar.style.width = '75%';
            }, 120);
        }

        function finishProgress() {
            clearTimeout(progressTimer);
            progressBar.style.width = '100%';
            setTimeout(() => {
                progressBar.style.opacity = '0';
                setTimeout(() => {
                    progressBar.style.width = '0%';
                }, 300);
            }, 180);
        }

        // 2. Helper to extract page identifier or base filename
        function getPageKey(urlStr) {
            try {
                const parsed = new URL(urlStr, window.location.origin);
                const pathParts = parsed.pathname.split('/');
                return pathParts[pathParts.length - 1].toLowerCase();
            } catch (e) {
                return '';
            }
        }

        // List of investor pages handled by SPA
        const INVESTOR_SPA_PAGES = [
            'discover.php',
            'dashboard.php',
            'portfolio.php',
            'watchlist.php',
            'messages.php',
            'verification.php',
            'profile.php',
            'view.php',
            'startup_detail.php',
            'invest.php'
        ];

        function isInvestorSectionUrl(urlStr) {
            try {
                const targetUrl = new URL(urlStr, window.location.origin);
                if (targetUrl.origin !== window.location.origin) return false;
                if (targetUrl.pathname.includes('logout.php')) return false;

                const pageKey = getPageKey(urlStr);
                return INVESTOR_SPA_PAGES.includes(pageKey);
            } catch (e) {
                return false;
            }
        }

        // 3. Update active states on sidebar and navbar
        function updateActiveNavigation(targetUrl) {
            const targetPage = getPageKey(targetUrl);

            // Sidebar links
            const sidebarLinks = document.querySelectorAll('#main-sidebar .sidebar-link');
            sidebarLinks.forEach(link => {
                const linkPage = getPageKey(link.href);
                const isMatch = (linkPage === targetPage) ||
                    (targetPage === 'startup_detail.php' && linkPage === 'discover.php') ||
                    (targetPage === 'invest.php' && linkPage === 'discover.php');

                const navIcon = link.querySelector('.nav-icon');
                let indicator = link.querySelector('.active-indicator');

                if (isMatch) {
                    link.className = 'sidebar-link flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm transition font-semibold group border is-active bg-[#EAF2FF] dark:bg-blue-950/70 text-[#123B7A] dark:text-blue-300 font-bold shadow-xs border-[#123B7A]/20 dark:border-blue-700/60';
                    if (navIcon) {
                        navIcon.className = 'nav-icon w-4.5 h-4.5 flex-shrink-0 text-[#123B7A] dark:text-blue-400';
                    }
                    if (!indicator) {
                        indicator = document.createElement('span');
                        indicator.className = 'w-1.5 h-4 rounded-full bg-[#123B7A] dark:bg-blue-400 flex-shrink-0 sidebar-text-item active-indicator';
                        indicator.title = 'Active Page';
                        link.appendChild(indicator);
                    }
                } else {
                    link.className = 'sidebar-link flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm transition font-semibold group border text-[#4B5563] dark:text-slate-300 hover:text-[#111827] dark:hover:text-white hover:bg-[#FAFBFD] dark:hover:bg-slate-800 border-transparent';
                    if (navIcon) {
                        navIcon.className = 'nav-icon w-4.5 h-4.5 flex-shrink-0 text-[#667085] dark:text-slate-400 group-hover:text-[#111827] dark:group-hover:text-white';
                    }
                    if (indicator) {
                        indicator.remove();
                    }
                }
            });

            // Top navbar links
            const topNavLinks = document.querySelectorAll('#investor-top-nav .top-nav-link, header nav a');
            topNavLinks.forEach(link => {
                const linkPage = getPageKey(link.href);
                const isMatch = (linkPage === targetPage) ||
                    (targetPage === 'startup_detail.php' && linkPage === 'discover.php') ||
                    (targetPage === 'invest.php' && linkPage === 'discover.php');

                if (isMatch) {
                    link.className = 'top-nav-link flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-[#123B7A] text-white shadow-xs dark:bg-blue-600';
                    link.setAttribute('aria-current', 'page');
                } else {
                    link.className = 'top-nav-link flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all text-[#4B5563] dark:text-slate-300 hover:text-[#123B7A] dark:hover:text-white hover:bg-white dark:hover:bg-slate-700/60';
                    link.removeAttribute('aria-current');
                }
            });

            // Back button visibility
            const backBtn = document.getElementById('investor-back-btn');
            if (backBtn) {
                if (targetPage === 'dashboard.php') {
                    backBtn.style.display = 'none';
                } else {
                    backBtn.style.display = 'flex';
                }
            }
        }

        // 4. Section Loader
        let isFetchingSection = false;

        async function loadSection(url, pushHistory = true) {
            if (isFetchingSection) return;

            // Close mobile sidebar if open
            if (window.innerWidth < 1024) {
                const sidebar = document.getElementById('main-sidebar');
                const backdrop = document.getElementById('mobile-sidebar-backdrop');
                if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.add('-translate-x-full');
                    if (backdrop) backdrop.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
                }
            }

            isFetchingSection = true;
            startProgress();

            const currentMain = document.querySelector('main');
            if (currentMain) {
                currentMain.style.transition = 'opacity 0.12s ease';
                currentMain.style.opacity = '0.55';
            }

            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!res.ok) {
                    window.location.href = url;
                    return;
                }

                const html = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newMain = doc.querySelector('main');
                if (!newMain) {
                    window.location.href = url;
                    return;
                }

                // Update document title
                if (doc.title) {
                    document.title = doc.title;
                }

                // Push History State
                if (pushHistory) {
                    history.pushState({ spa: true, url: url }, doc.title || '', url);
                }

                // Swap Main Content
                if (currentMain) {
                    currentMain.replaceWith(newMain);
                } else {
                    const contentContainer = document.querySelector('.flex-1.flex.flex-col');
                    if (contentContainer) {
                        contentContainer.appendChild(newMain);
                    } else {
                        document.body.appendChild(newMain);
                    }
                }

                // Update Active states on sidebar and navbar
                updateActiveNavigation(url);

                // Re-render Lucide icons
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }

                // Smooth entrance animation
                if (typeof gsap !== 'undefined') {
                    gsap.from(newMain, { duration: 0.35, y: 8, opacity: 0, ease: 'power2.out' });
                } else {
                    newMain.style.opacity = '1';
                }

                // Execute inline scripts inside the new main
                const inlineScripts = newMain.querySelectorAll('script');
                inlineScripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });

                // Execute page-specific inline scripts from doc.body (skipping global ones)
                const bodyScripts = doc.querySelectorAll('body > script');
                bodyScripts.forEach(bs => {
                    const txt = bs.textContent || '';
                    if (!txt.includes('toggleMobileSidebar') && !txt.includes('toggleDesktopSidebar') && !txt.includes('investor_sidebar_collapsed')) {
                        try {
                            const runScript = document.createElement('script');
                            runScript.textContent = txt;
                            document.body.appendChild(runScript);
                            setTimeout(() => runScript.remove(), 100);
                        } catch (err) {
                            console.warn('SPA script init error:', err);
                        }
                    }
                });

                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'instant' });

            } catch (error) {
                console.error('SPA section load failed, falling back to full navigation:', error);
                window.location.href = url;
            } finally {
                finishProgress();
                isFetchingSection = false;
            }
        }

        // 5. Global Link Click Interceptor
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a');
            if (!link) return;

            // Skip modified clicks (Ctrl, Cmd, Shift, Alt, middle-click)
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) return;

            // Skip target="_blank", download, or explicit opt-out
            if (link.target === '_blank' || link.hasAttribute('download') || link.dataset.noSpa === 'true') return;

            // Skip non-HTTP links (mailto:, javascript:, tel:, #)
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;

            // Check if destination is an investor section
            if (isInvestorSectionUrl(link.href)) {
                // Same URL check (if pathname and search are identical, scroll top)
                const currentUrlObj = new URL(window.location.href);
                const targetUrlObj = new URL(link.href, window.location.origin);
                if (currentUrlObj.pathname === targetUrlObj.pathname && currentUrlObj.search === targetUrlObj.search) {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }

                e.preventDefault();
                loadSection(link.href, true);
            }
        });

        // 6. Handle Browser Back & Forward Navigation
        window.addEventListener('popstate', function (e) {
            if (isInvestorSectionUrl(window.location.href)) {
                loadSection(window.location.href, false);
            }
        });

        // 7. Expose globally
        window.investorLoadSection = loadSection;
    })();
</script>
