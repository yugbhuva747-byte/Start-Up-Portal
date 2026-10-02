<?php
/**
 * Admin Sidebar Navigation Component
 * Enhanced – Categorized Page Sections, Dynamic Database Badges, Rich Hover Effects, Dark Mode & Premium Aesthetics
 * Supports full-expanded mode and ultra-sleek compact icon rail mode with floating tooltips & instant width transitions.
 */
if (!defined('APP_NAME')) {
    exit('Direct access not permitted');
}

$currentPage = basename($_SERVER['PHP_SELF']);
$adminUser = current_user() ?? ['name' => 'Admin User', 'email' => 'admin@portal.com', 'avatar_url' => ''];

$pendingKycCount = 0;
$pendingFundingCount = 0;
$activeSubsCount = 0;
$totalCompaniesCount = 0;
$pendingInvoicesCount = 0;

if (isset($db) && $db instanceof PDO) {
    try {
        $pendingKycCount = (int)$db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'")->fetchColumn();
        $pendingFundingCount = (int)$db->query("SELECT COUNT(*) FROM funding_rounds WHERE status IN ('SUBMITTED', 'UNDER_REVIEW', 'under_review')")->fetchColumn();
        $activeSubsCount = (int)$db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
        $totalCompaniesCount = (int)$db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
        $pendingInvoicesCount = (int)$db->query("SELECT COUNT(*) FROM platform_invoices WHERE settlement_status != 'SETTLED'")->fetchColumn();
    } catch (Exception $e) {}
}

// Load Global Admin Typography & Legibility Suite
require_once __DIR__ . '/theme.php';
?>
<!-- Mobile Drawer Backdrop -->
<div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity duration-300"></div>

<style>
    /* ── Scrollbar ── */
    #main-sidebar .overflow-y-auto {
        scrollbar-width: thin;
        scrollbar-color: rgba(148, 163, 184, 0.35) transparent;
        overscroll-behavior: contain;
        scroll-behavior: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    #main-sidebar .overflow-y-auto::-webkit-scrollbar { width: 4px; }
    #main-sidebar .overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
    #main-sidebar .overflow-y-auto::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.35);
        border-radius: 9999px;
    }
    #main-sidebar .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: rgba(148, 163, 184, 0.6);
    }

    /* ── Section Title ── */
    .sidebar-section-title {
        font-size: 0.6875rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #94a3b8;
        padding: 0.85rem 0.75rem 0.35rem 0.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .dark .sidebar-section-title {
        color: #64748b;
    }

    /* ── Nav link base ── */
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.55rem 0.8rem;
        border-radius: 0.625rem;
        font-size: 0.8125rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        color: #475569;
        border-left: 3px solid transparent;
        transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
        position: relative;
    }

    .dark .sidebar-link {
        color: #94a3b8;
    }

    .sidebar-link:hover {
        background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
        color: #1d4ed8;
        border-left-color: #93c5fd;
        box-shadow: inset 0 1px 0 rgba(59,130,246,0.08), 0 1px 4px rgba(59,130,246,0.08);
        transform: translateX(3px);
    }
    .dark .sidebar-link:hover {
        background: #1e293b;
        color: #60a5fa;
        border-left-color: #3b82f6;
    }

    .sidebar-link.is-active {
        background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
        color: #1d4ed8;
        border-left-color: #2563eb;
        font-weight: 700;
        box-shadow: inset 0 1px 0 rgba(59,130,246,0.12), 0 2px 8px rgba(59,130,246,0.1);
    }
    .dark .sidebar-link.is-active {
        background: rgba(37,99,235,0.2);
        color: #93c5fd;
        border-left-color: #3b82f6;
    }

    .nav-icon {
        width: 1.125rem;
        height: 1.125rem;
        flex-shrink: 0;
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
    #main-sidebar.rail-collapsed .sidebar-badge-item {
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
    #main-sidebar.rail-collapsed .sidebar-section-title {
        display: flex !important;
        justify-content: center !important;
        padding: 0.35rem 0.25rem !important;
        margin: 0.35rem 0 !important;
        height: 1px !important;
        overflow: hidden !important;
        color: transparent !important;
        border-top: 1px solid #e2e8f0;
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
        border-left: none !important;
        border-radius: 0.75rem !important;
        transform: none !important;
        position: relative !important;
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
        background: #f1f5f9 !important;
        color: #2563eb !important;
    }
    .dark #main-sidebar.rail-collapsed .sidebar-link:hover {
        background: #1e293b !important;
        color: #60a5fa !important;
    }
    #main-sidebar.rail-collapsed .sidebar-link.is-active {
        background: #2563eb !important;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4) !important;
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
        background: #3b82f6 !important;
        box-shadow: 0 4px 14px rgba(59, 130, 246, 0.45) !important;
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

    /* Small badge dot on icon in collapsed rail */
    .sidebar-badge-dot {
        display: none;
    }
    #main-sidebar.rail-collapsed .sidebar-badge-dot {
        display: block !important;
        position: absolute;
        top: 6px;
        right: 10px;
        width: 8px;
        height: 8px;
        border-radius: 9999px;
        background: #ef4444;
        border: 2px solid #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    .dark #main-sidebar.rail-collapsed .sidebar-badge-dot {
        border-color: #0f172a;
    }

    /* Footer styling in collapsed rail */
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
    class="fixed inset-y-0 left-0 z-50 w-72 sm:w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between h-full transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:w-64 flex-shrink-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none select-none">
    
    <div data-lenis-prevent="true" data-lenis-prevent-wheel="true" class="sidebar-scroll-body p-4 sm:p-5 overflow-y-auto flex-1">
        <!-- Brand Logo & Mobile Close Button -->
        <div class="sidebar-brand-header flex items-center justify-between mb-5 sm:mb-6">
            <a href="<?= url('admin/dashboard.php') ?>" class="sidebar-brand-link flex items-center space-x-2.5 group min-w-0" data-tooltip="Admin Control" title="Admin Control">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-sm shadow-blue-600/20 group-hover:scale-105 transition flex-shrink-0">
                    <i data-lucide="shield" class="w-4 h-4"></i>
                </div>
                <div class="min-w-0 sidebar-text-item">
                    <div class="font-extrabold text-slate-900 dark:text-white text-sm tracking-tight leading-tight flex items-center gap-1 whitespace-nowrap">
                        ADMIN <span class="text-blue-600 dark:text-blue-400">CONTROL</span>
                    </div>
                    <div class="text-[8.5px] text-slate-400 font-bold uppercase tracking-wider whitespace-nowrap">Compliance &amp; Audit Hub</div>
                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex-shrink-0" title="Close navigation">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        

        <!-- Navigation Links Grouped by Sections -->
        <nav class="space-y-1 text-xs font-semibold">
            
            <!-- SECTION 1: CORE OVERVIEW -->
            <div class="sidebar-section-title">
                <span>Core Overview</span>
            </div>

            <a href="<?= url('admin/dashboard.php') ?>" 
               data-tooltip="Overview &amp; KPIs"
               title="Overview &amp; KPIs"
               class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'is-active' : '' ?>">
                <i data-lucide="gauge" class="nav-icon text-blue-600"></i>
                <span class="whitespace-nowrap sidebar-text-item">Overview &amp; KPIs</span>
            </a>

            <a href="<?= url('admin/reports.php') ?>" 
               data-tooltip="Reports &amp; Analytics"
               title="Reports &amp; Analytics"
               class="sidebar-link <?= $currentPage === 'reports.php' ? 'is-active' : '' ?>">
                <i data-lucide="bar-chart-3" class="nav-icon text-indigo-500"></i>
                <span class="whitespace-nowrap sidebar-text-item">Reports &amp; Analytics</span>
            </a>

            <!-- SECTION 2: DEALFLOW & GOVERNANCE -->
            <div class="sidebar-section-title mt-2">
                <span>Dealflow &amp; Compliance</span>
            </div>

            <a href="<?= url('admin/verification_queue.php') ?>" 
               data-tooltip="KYC Queue<?= $pendingKycCount > 0 ? " ({$pendingKycCount})" : '' ?>"
               title="KYC Queue"
               class="sidebar-link <?= $currentPage === 'verification_queue.php' ? 'is-active' : '' ?>">
                <i data-lucide="check-square" class="nav-icon text-amber-500"></i>
                <span class="whitespace-nowrap sidebar-text-item flex-1">KYC Queue</span>
                <?php if ($pendingKycCount > 0): ?>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 sidebar-text-item"><?= $pendingKycCount ?></span>
                    <span class="sidebar-badge-dot"></span>
                <?php endif; ?>
            </a>

            <a href="<?= url('admin/funding_review.php') ?>" 
               data-tooltip="Funding Review<?= $pendingFundingCount > 0 ? " ({$pendingFundingCount})" : '' ?>"
               title="Funding Review"
               class="sidebar-link <?= $currentPage === 'funding_review.php' ? 'is-active' : '' ?>">
                <i data-lucide="file-check-2" class="nav-icon text-sky-500"></i>
                <span class="whitespace-nowrap sidebar-text-item flex-1">Funding Review</span>
                <?php if ($pendingFundingCount > 0): ?>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 sidebar-text-item"><?= $pendingFundingCount ?></span>
                    <span class="sidebar-badge-dot"></span>
                <?php endif; ?>
            </a>

            <a href="<?= url('admin/companies.php') ?>" 
               data-tooltip="Startup Companies (<?= $totalCompaniesCount ?>)"
               title="Startup Companies"
               class="sidebar-link <?= $currentPage === 'companies.php' ? 'is-active' : '' ?>">
                <i data-lucide="building-2" class="nav-icon text-cyan-600"></i>
                <span class="whitespace-nowrap sidebar-text-item flex-1">Startup Companies</span>
                <?php if ($totalCompaniesCount > 0): ?>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 sidebar-text-item"><?= $totalCompaniesCount ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= url('admin/users.php') ?>" 
               data-tooltip="Users Directory"
               title="Users Directory"
               class="sidebar-link <?= $currentPage === 'users.php' ? 'is-active' : '' ?>">
                <i data-lucide="users" class="nav-icon text-slate-500"></i>
                <span class="whitespace-nowrap sidebar-text-item">Users Directory</span>
            </a>

            <!-- SECTION 3: CAPITAL & REVENUE -->
            <div class="sidebar-section-title mt-2">
                <span>Capital &amp; Monetization</span>
            </div>

            <a href="<?= url('admin/subscriptions.php') ?>" 
               data-tooltip="Subscriptions &amp; Plans"
               title="Subscription Plans & Priority Manager"
               class="sidebar-link <?= $currentPage === 'subscriptions.php' ? 'is-active' : '' ?>">
                <i data-lucide="crown" class="nav-icon text-amber-500"></i>
                <span class="whitespace-nowrap sidebar-text-item flex-1">Subscriptions &amp; Plans</span>
                <span class="px-1.5 py-0.5 rounded-full text-[9.5px] font-extrabold bg-purple-100 text-purple-700 sidebar-text-item">
                    VIP &middot; <?= $activeSubsCount ?>
                </span>
            </a>

            <a href="<?= url('admin/revenue.php') ?>" 
               data-tooltip="Revenue &amp; Invoices<?= $pendingInvoicesCount > 0 ? " ({$pendingInvoicesCount})" : '' ?>"
               title="Revenue &amp; Invoices"
               class="sidebar-link <?= ($currentPage === 'revenue.php' || $currentPage === 'invoice_view.php') ? 'is-active' : '' ?>">
                <i data-lucide="receipt" class="nav-icon text-emerald-600"></i>
                <span class="whitespace-nowrap sidebar-text-item flex-1">Revenue &amp; Invoices</span>
                <?php if ($pendingInvoicesCount > 0): ?>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 sidebar-text-item"><?= $pendingInvoicesCount ?></span>
                    <span class="sidebar-badge-dot"></span>
                <?php endif; ?>
            </a>

            <a href="<?= url('admin/transactions.php') ?>" 
               data-tooltip="Transactions &amp; Escrow"
               title="Transactions &amp; Escrow"
               class="sidebar-link <?= $currentPage === 'transactions.php' ? 'is-active' : '' ?>">
                <i data-lucide="banknote" class="nav-icon text-emerald-500"></i>
                <span class="whitespace-nowrap sidebar-text-item">Transactions &amp; Escrow</span>
            </a>

            <a href="<?= url('admin/share_allotments.php') ?>" 
               data-tooltip="Share Allotments"
               title="Share Certificates &amp; Cap Table"
               class="sidebar-link <?= $currentPage === 'share_allotments.php' ? 'is-active' : '' ?>">
                <i data-lucide="award" class="nav-icon text-purple-500"></i>
                <span class="whitespace-nowrap sidebar-text-item">Share Allotments</span>
            </a>

            <!-- SECTION 4: COMMUNICATIONS & SYSTEM -->
            <div class="sidebar-section-title mt-2">
                <span>Communications &amp; System</span>
            </div>

            <a href="<?= url('admin/broadcasts.php') ?>" 
               data-tooltip="Broadcasts"
               title="Broadcasts"
               class="sidebar-link <?= $currentPage === 'broadcasts.php' ? 'is-active' : '' ?>">
                <i data-lucide="megaphone" class="nav-icon text-rose-500"></i>
                <span class="whitespace-nowrap sidebar-text-item">Broadcasts</span>
            </a>


           

           

        </nav>
    </div>

   
</aside>

<script>
    // Immediate synchronous check to prevent layout jump
    if (localStorage.getItem('admin_sidebar_collapsed') === '1' && window.innerWidth >= 1024) {
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
            localStorage.setItem('admin_sidebar_collapsed', '0');
        } else {
            sidebar.classList.add('rail-collapsed');
            document.body.classList.add('sidebar-collapsed');
            localStorage.setItem('admin_sidebar_collapsed', '1');
        }
        // Trigger resize event so charts and tables re-adjust smoothly
        window.dispatchEvent(new Event('resize'));
    }

    // Restore desktop sidebar collapsed state on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        const savedState = localStorage.getItem('admin_sidebar_collapsed');
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

    // Direct mouse wheel scroll engine for sidebar
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
</script>
