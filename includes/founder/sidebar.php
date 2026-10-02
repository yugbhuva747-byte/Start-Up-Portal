<?php
/**
 * Founder Sidebar Navigation Component
 * Modern, High-Legibility Typography with Clean Design Tokens
 * Supports full-expanded mode and ultra-sleek compact icon rail mode with floating tooltips & smooth width transitions.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$founderUser = current_user();
$founderProgress = get_profile_progress($founderUser['id'], 'founder');

// Include Founder Dark & Light Theme Controller
require_once __DIR__ . '/theme.php';
?>
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
    #main-sidebar.rail-collapsed .founder-progress-card {
        display: none !important;
    }

    #main-sidebar.rail-collapsed .sidebar-brand-header {
        justify-content: center !important;
        margin-bottom: 1.25rem !important;
    }

    #main-sidebar.rail-collapsed .sidebar-brand-link {
        justify-content: center !important;
        margin: 0 auto !important;
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

    #main-sidebar.rail-collapsed .sidebar-link svg,
    #main-sidebar.rail-collapsed .sidebar-link i {
        width: 1.25rem !important;
        height: 1.25rem !important;
        margin: 0 !important;
        flex-shrink: 0 !important;
    }

    #main-sidebar.rail-collapsed .sidebar-link:hover {
        transform: scale(1.06) !important;
        background: #f1f5f9 !important;
        color: #4f46e5 !important;
    }

    .dark #main-sidebar.rail-collapsed .sidebar-link:hover {
        background: #1e293b !important;
        color: #818cf8 !important;
    }

    #main-sidebar.rail-collapsed .sidebar-link.is-active {
        background: #4f46e5 !important;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.45) !important;
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
        background: #6366f1 !important;
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.5) !important;
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
<!-- Mobile Drawer Backdrop -->
<div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity duration-300"></div>

<aside id="main-sidebar" data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
    class="fixed inset-y-0 left-0 z-50 w-72 sm:w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between h-full transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:w-64 flex-shrink-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none select-none">
    <div data-lenis-prevent="true" data-lenis-prevent-wheel="true" class="sidebar-scroll-body p-4 sm:p-5 overflow-y-auto flex-1">
        <!-- Brand Logo & Mobile Close Button -->
        <div class="sidebar-brand-header flex items-center justify-between mb-6">
            <a href="<?= url('founder/dashboard.php') ?>" class="sidebar-brand-link flex items-center space-x-3 group min-w-0" data-tooltip="Founder Hub" title="Founder Hub">
                <div
                    class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-600/20 group-hover:scale-105 transition flex-shrink-0">
                    <i data-lucide="zap" class="w-5 h-5"></i>
                </div>
                <div class="sidebar-text-item min-w-0">
                    <div
                        class="font-black text-slate-900 dark:text-white text-base tracking-tight leading-tight flex items-center gap-1 whitespace-nowrap">
                        FOUNDER <span class="text-indigo-600 dark:text-indigo-400">HUB</span>
                    </div>
                    <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wider whitespace-nowrap">Startup Workspace</div>
                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()"
                class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex-shrink-0"
                title="Close navigation">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Readiness Progress Card -->
        <div class="founder-progress-card mb-6 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="text-slate-600 dark:text-slate-300 font-semibold text-xs">Profile Readiness</span>
                <span class="text-indigo-600 dark:text-indigo-400 font-bold text-sm"><?= $founderProgress['percentage'] ?>%</span>
            </div>
            <div class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden mb-2.5">
                <div class="h-full bg-indigo-600 dark:bg-indigo-500 rounded-full transition-all duration-500"
                    style="width: <?= $founderProgress['percentage'] ?>%"></div>
            </div>
            <?php if (!$founderProgress['is_complete']): ?>
                <a href="<?= url('founder/verification.php') ?>"
                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-semibold flex items-center space-x-1.5 transition">
                    <span>Complete KYC & Details</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            <?php else: ?>
                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold flex items-center space-x-1.5">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                    <span>Verified for Funding</span>
                </span>
            <?php endif; ?>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1.5 text-sm font-medium">
            <a href="<?= url('founder/dashboard.php') ?>"
               data-tooltip="Overview &amp; KPIs"
               title="Overview &amp; KPIs"
               class="sidebar-link flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'dashboard.php' ? 'is-active bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="layout-dashboard"
                    class="w-4.5 h-4.5 flex-shrink-0 <?= $currentPage === 'dashboard.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span class="sidebar-text-item whitespace-nowrap">Overview</span>
            </a>

            <a href="<?= url('founder/company.php') ?>"
               data-tooltip="Company Profile"
               title="Company Profile"
               class="sidebar-link flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'company.php' ? 'is-active bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="building-2"
                    class="w-4.5 h-4.5 flex-shrink-0 <?= $currentPage === 'company.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span class="sidebar-text-item whitespace-nowrap">Company Profile</span>
            </a>

            <a href="<?= url('founder/funding_rounds.php') ?>"
               data-tooltip="Funding Rounds"
               title="Funding Rounds"
               class="sidebar-link flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'funding_rounds.php' ? 'is-active bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="circle-dollar-sign"
                    class="w-4.5 h-4.5 flex-shrink-0 <?= $currentPage === 'funding_rounds.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span class="sidebar-text-item whitespace-nowrap">Funding Rounds</span>
            </a>

            <a href="<?= url('founder/cap_table.php') ?>"
               data-tooltip="Cap Table &amp; Equity"
               title="Cap Table &amp; Equity"
               class="sidebar-link flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'cap_table.php' ? 'is-active bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="pie-chart"
                    class="w-4.5 h-4.5 flex-shrink-0 <?= $currentPage === 'cap_table.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span class="sidebar-text-item whitespace-nowrap">Cap Table & Equity</span>
            </a>

            <a href="<?= url('founder/verification.php') ?>"
               data-tooltip="KYC &amp; DigiLocker"
               title="KYC &amp; DigiLocker"
               class="sidebar-link flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'verification.php' ? 'is-active bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="shield-check"
                    class="w-4.5 h-4.5 flex-shrink-0 <?= $currentPage === 'verification.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span class="sidebar-text-item whitespace-nowrap">KYC & DigiLocker</span>
            </a>

            <a href="<?= url('founder/messages.php') ?>"
               data-tooltip="Investor Chat"
               title="Investor Chat"
               class="sidebar-link flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'messages.php' ? 'is-active bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="message-square"
                    class="w-4.5 h-4.5 flex-shrink-0 <?= $currentPage === 'messages.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span class="sidebar-text-item whitespace-nowrap">Investor Chat</span>
            </a>

            <a href="<?= url('founder/updates.php') ?>"
               data-tooltip="Updates &amp; Milestones"
               title="Updates &amp; Milestones"
               class="sidebar-link flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'updates.php' ? 'is-active bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="newspaper"
                    class="w-4.5 h-4.5 flex-shrink-0 <?= $currentPage === 'updates.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span class="sidebar-text-item whitespace-nowrap">Updates & Milestones</span>
            </a>

            <a href="<?= url('founder/blogs.php') ?>"
               data-tooltip="Company Blog &amp; Stories"
               title="Company Blog &amp; Stories"
               class="sidebar-link flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'blogs.php' ? 'is-active bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="book-open"
                    class="w-4.5 h-4.5 flex-shrink-0 <?= $currentPage === 'blogs.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span class="sidebar-text-item whitespace-nowrap">Company Blog & Stories</span>
            </a>
        </nav>
    </div>

    <!-- User Footer Profile & Logout -->
    <div class="sidebar-footer-container p-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40">
        <div class="sidebar-footer-inner flex items-center justify-between">
            <a href="<?= url('founder/view.php') ?>" title="View Full Profile" data-tooltip="<?= htmlspecialchars($founderUser['name']) ?>"
                class="sidebar-footer-user flex items-center space-x-3 overflow-hidden flex-1 p-1 rounded-xl hover:bg-slate-200/60 dark:hover:bg-slate-750 transition group">
                <img src="<?= $founderUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80' ?>"
                    class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-slate-700 group-hover:ring-2 group-hover:ring-indigo-500 transition flex-shrink-0">
                <div class="truncate sidebar-text-item">
                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                        <?= htmlspecialchars($founderUser['name']) ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 truncate flex items-center space-x-1">
                        <span>Founder Profile</span>
                        <i data-lucide="chevron-right" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition"></i>
                    </div>
                </div>
            </a>
            <div class="sidebar-logout-btn flex items-center">
                <a href="<?= url('auth/logout.php') ?>" title="Sign Out" data-tooltip="Sign Out"
                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition ml-1">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </div>
</aside>

<script>
    // Immediate synchronous check to prevent layout jump
    if (localStorage.getItem('founder_sidebar_collapsed') === '1' && window.innerWidth >= 1024) {
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
            localStorage.setItem('founder_sidebar_collapsed', '0');
        } else {
            sidebar.classList.add('rail-collapsed');
            document.body.classList.add('sidebar-collapsed');
            localStorage.setItem('founder_sidebar_collapsed', '1');
        }
        window.dispatchEvent(new Event('resize'));
    }

    // Restore desktop sidebar collapsed state on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        const savedState = localStorage.getItem('founder_sidebar_collapsed');
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

    // Direct mouse wheel scroll engine for founder sidebar
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
       PERSISTENT FOUNDER DASHBOARD SPA SECTION ROUTER
       Swaps only <main> content via AJAX Fetch without reloading
       Persistent sidebar, header, and active states.
       ======================================================= */
    (function () {
        // 1. Progress Bar Element
        let progressBar = document.getElementById('spa-progress-bar');
        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.id = 'spa-progress-bar';
            progressBar.style.cssText = 'position:fixed;top:0;left:0;height:3px;width:0%;background:linear-gradient(90deg, #4F46E5, #6366F1, #818CF8);z-index:99999;transition:width 0.22s ease, opacity 0.28s ease;opacity:0;pointer-events:none;box-shadow:0 0 10px rgba(99,102,241,0.65);';
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

        const FOUNDER_SPA_PAGES = [
            'dashboard.php',
            'company.php',
            'funding_rounds.php',
            'cap_table.php',
            'verification.php',
            'messages.php',
            'updates.php',
            'blogs.php',
            'profile.php',
            'view.php'
        ];

        function isFounderSectionUrl(urlStr) {
            try {
                const targetUrl = new URL(urlStr, window.location.origin);
                if (targetUrl.origin !== window.location.origin) return false;
                if (targetUrl.pathname.includes('logout.php')) return false;

                const pageKey = getPageKey(urlStr);
                return FOUNDER_SPA_PAGES.includes(pageKey);
            } catch (e) {
                return false;
            }
        }

        // 3. Update active states on sidebar
        function updateActiveNavigation(targetUrl) {
            const targetPage = getPageKey(targetUrl);

            const sidebarLinks = document.querySelectorAll('#main-sidebar .sidebar-link');
            sidebarLinks.forEach(link => {
                const linkPage = getPageKey(link.href);
                const isMatch = (linkPage === targetPage);

                const icon = link.querySelector('[data-lucide], svg');

                if (isMatch) {
                    link.className = 'sidebar-link flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition is-active bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs';
                    if (icon) {
                        icon.className = 'w-4.5 h-4.5 flex-shrink-0 text-indigo-600 dark:text-indigo-400';
                    }
                } else {
                    link.className = 'sidebar-link flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800';
                    if (icon) {
                        icon.className = 'w-4.5 h-4.5 flex-shrink-0 text-slate-400';
                    }
                }
            });
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

                // Update Active states
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
                    if (!txt.includes('toggleMobileSidebar') && !txt.includes('toggleDesktopSidebar') && !txt.includes('founder_sidebar_collapsed')) {
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

            // Check if destination is a founder section
            if (isFounderSectionUrl(link.href)) {
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
            if (isFounderSectionUrl(window.location.href)) {
                loadSection(window.location.href, false);
            }
        });

        // 7. Expose globally
        window.founderLoadSection = loadSection;
    })();
</script>