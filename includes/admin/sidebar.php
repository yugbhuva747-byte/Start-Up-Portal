<?php
/**
 * Admin Sidebar Navigation Component
 * Enhanced – Bigger Fonts, Rich Hover Effects & Premium Aesthetics
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$adminUser = current_user();

// Load Global Admin "Vay Portal" Typography & Legibility Suite
require_once __DIR__ . '/theme.php';
?>
<!-- Mobile Drawer Backdrop -->
<div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity duration-300"></div>

<style>
    /* ── Scrollbar ── */
    #main-sidebar .overflow-y-auto {
        scrollbar-width: thin;
        scrollbar-color: rgba(148, 163, 184, 0.4) transparent;
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

    /* ── Nav link base ── */
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.875rem;
        border-radius: 0.625rem;
        font-size: 0.8125rem;      /* 13 px */
        font-weight: 600;
        letter-spacing: 0.01em;
        color: #475569;
        border-left: 3px solid transparent;
        transition:
            background 0.18s ease,
            color 0.18s ease,
            border-color 0.18s ease,
            box-shadow 0.18s ease,
            transform 0.14s ease;
        text-decoration: none;
        position: relative;
        overflow: hidden;
    }

    /* Shimmer layer on hover */
    .sidebar-link::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent 0%, rgba(59,130,246,0.06) 50%, transparent 100%);
        opacity: 0;
        transition: opacity 0.22s ease;
        pointer-events: none;
        border-radius: inherit;
    }

    /* ── Hover state ── */
    .sidebar-link:hover {
        background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
        color: #1d4ed8;
        border-left-color: #93c5fd;
        box-shadow: inset 0 1px 0 rgba(59,130,246,0.08), 0 1px 4px rgba(59,130,246,0.08);
        transform: translateX(3px);
    }
    .sidebar-link:hover::before { opacity: 1; }
    .sidebar-link:hover .nav-icon {
        color: #2563eb;
        transform: scale(1.13);
    }

    /* ── Active state ── */
    .sidebar-link.is-active {
        background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
        color: #1d4ed8;
        border-left-color: #2563eb;
        font-weight: 700;
        box-shadow: inset 0 1px 0 rgba(59,130,246,0.12), 0 2px 8px rgba(59,130,246,0.1);
    }
    .sidebar-link.is-active .nav-icon {
        color: #2563eb;
        transform: scale(1.1);
    }

    /* ── Icon base ── */
    .nav-icon {
        width: 1.0625rem;
        height: 1.0625rem;
        flex-shrink: 0;
        color: #94a3b8;
        transition: color 0.18s ease, transform 0.18s ease;
    }

    /* ── Section label ── */
    .sidebar-section-label {
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.09em;
        text-transform: uppercase;
        color: #94a3b8;
        padding: 0 0.875rem;
        margin-bottom: 0.25rem;
        margin-top: 1.1rem;
    }

    /* ── Brand ── */
    .brand-title {
        font-size: 0.875rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .brand-sub {
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #94a3b8;
    }

    /* ── Admin badge ── */
    .admin-badge {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 1px solid #bfdbfe;
        border-radius: 0.75rem;
        padding: 0.55rem 0.75rem;
        margin-bottom: 1.1rem;
    }
    .admin-badge-title {
        font-size: 0.6875rem;
        font-weight: 700;
        color: #1e3a8a;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }
    .admin-badge-dot {
        width: 7px; height: 7px;
        border-radius: 9999px;
        background: #2563eb;
        flex-shrink: 0;
        display: inline-block;
    }
    .admin-badge-sub {
        font-size: 0.625rem;
        color: #3b82f6;
        margin-top: 0.125rem;
        font-weight: 600;
    }

    /* ── Footer ── */
    .sidebar-footer {
        padding: 0.875rem 1rem;
        border-top: 1px solid #e2e8f0;
        background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
    }
    .footer-name {
        font-size: 0.8125rem;
        font-weight: 700;
        color: #0f172a;
    }
    .footer-role {
        font-size: 0.6875rem;
        color: #2563eb;
        font-weight: 600;
    }
    .footer-action-btn {
        padding: 0.375rem;
        border-radius: 0.5rem;
        color: #94a3b8;
        transition: color 0.15s ease, background 0.15s ease, transform 0.15s ease;
        border: none;
        background: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .theme-btn:hover  { color: #2563eb; background: #eff6ff; transform: scale(1.15); }
    .logout-btn:hover { color: #e11d48; background: #fff1f2; transform: scale(1.15); }
</style>
<aside id="main-sidebar"
       data-lenis-prevent="true"
       data-lenis-prevent-wheel="true"
       data-lenis-prevent-touch="true"
       class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-slate-200 flex flex-col justify-between h-full transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:w-72 flex-shrink-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none select-none">
    <div data-lenis-prevent="true" data-lenis-prevent-wheel="true" class="p-4 sm:p-5 overflow-y-auto flex-1">
        <!-- Brand Logo & Mobile Close Button -->
        <div class="flex items-center justify-between mb-5 sm:mb-6">
            <a href="<?= url('admin/dashboard.php') ?>" class="flex items-center space-x-2.5 group min-w-0">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-sm shadow-blue-600/20 group-hover:scale-105 transition flex-shrink-0">
                    <i data-lucide="shield" class="w-4 h-4"></i>
                </div>
                <div class="min-w-0">
                    <div class="font-extrabold text-slate-900 text-sm tracking-tight leading-tight flex items-center gap-1 whitespace-nowrap">
                        ADMIN <span class="text-blue-600">CONTROL</span>
                    </div>
                    <div class="text-[8.5px] text-slate-400 font-bold uppercase tracking-wider whitespace-nowrap">Compliance & Audit</div>
                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition flex-shrink-0" title="Close navigation">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Admin Badge -->
        <div class="mb-5 p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/40 text-xs">
            <div class="text-blue-900 dark:text-blue-200 font-bold flex items-center space-x-1.5 text-[10.5px]">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 flex-shrink-0"></span>
                <span class="whitespace-nowrap">Governance Access</span>
            </div>
            <div class="text-[9.5px] text-blue-700 dark:text-blue-400 mt-0.5 whitespace-nowrap">SEBI & KYC Gatekeeper</div>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1 text-xs font-semibold">
            <a href="<?= url('admin/dashboard.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'dashboard.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="gauge" class="w-4 h-4 flex-shrink-0 <?= $currentPage === 'dashboard.php' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                <span class="whitespace-nowrap">Overview & KPIs</span>
            </a>

            <a href="<?= url('admin/verification_queue.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'verification_queue.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="check-square" class="w-4 h-4 flex-shrink-0 <?= $currentPage === 'verification_queue.php' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                <span class="whitespace-nowrap">KYC Queue</span>
            </a>

            <a href="<?= url('admin/funding_review.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'funding_review.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="file-check-2" class="w-4 h-4 flex-shrink-0 <?= $currentPage === 'funding_review.php' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                <span class="whitespace-nowrap">Funding Approvals</span>
            </a>

            <a href="<?= url('admin/users.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'users.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="users" class="w-4 h-4 flex-shrink-0 <?= $currentPage === 'users.php' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                <span class="whitespace-nowrap">Users Directory</span>
            </a>

            <a href="<?= url('admin/companies.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'companies.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="building-2" class="w-4 h-4 flex-shrink-0 <?= $currentPage === 'companies.php' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                <span class="whitespace-nowrap">Startup Companies</span>
            </a>

            <a href="<?= url('admin/share_allotments.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'share_allotments.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="award" class="w-4 h-4 flex-shrink-0 <?= $currentPage === 'share_allotments.php' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                <span class="whitespace-nowrap">Share Allotments</span>
            </a>

            <a href="<?= url('admin/transactions.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'transactions.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="banknote" class="w-4 h-4 flex-shrink-0 <?= $currentPage === 'transactions.php' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                <span class="whitespace-nowrap">Transactions & Escrow</span>
            </a>

            <a href="<?= url('admin/revenue.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'revenue.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="receipt" class="w-4 h-4 flex-shrink-0 <?= $currentPage === 'revenue.php' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                <span class="whitespace-nowrap">Revenue & Commissions</span>
            </a>

            <a href="<?= url('admin/audit_logs.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'audit_logs.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="history" class="w-4 h-4 flex-shrink-0 <?= $currentPage === 'audit_logs.php' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                <span class="whitespace-nowrap">Security Audit Trail</span>
            </a>

            <a href="<?= url('admin/reports.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'reports.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="bar-chart-3" class="w-4 h-4 flex-shrink-0 <?= $currentPage === 'reports.php' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                <span class="whitespace-nowrap">Reports & Analytics</span>
            </a>

            <a href="<?= url('admin/broadcasts.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'broadcasts.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="megaphone" class="w-4 h-4 flex-shrink-0 <?= $currentPage === 'broadcasts.php' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                <span class="whitespace-nowrap">Platform Broadcasts</span>
            </a>
        </nav>
    </div>

    <!-- User Footer Profile & Logout -->
    <div class="sidebar-footer">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5 overflow-hidden">
                <img src="<?= $adminUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80' ?>"
                     class="w-9 h-9 rounded-full object-cover flex-shrink-0"
                     style="border:2px solid #bfdbfe;box-shadow:0 1px 4px rgba(37,99,235,0.15);">
                <div class="truncate">
                    <div class="footer-name truncate"><?= htmlspecialchars($adminUser['name']) ?></div>
                    <div class="footer-role">Compliance Officer</div>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" onclick="toggleAdminTheme()" title="Toggle Dark/Light Mode"
                        class="footer-action-btn theme-btn">
                    <i data-lucide="moon" class="w-4 h-4 hidden dark:inline"></i>
                    <i data-lucide="sun"  class="w-4 h-4 inline dark:hidden" style="color:#f59e0b;"></i>
                </button>
                <a href="<?= url('auth/logout.php') ?>" title="Sign Out"
                   class="footer-action-btn logout-btn">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </div>
</aside>
<script>
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
