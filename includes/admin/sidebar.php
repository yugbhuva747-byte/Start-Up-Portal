<?php
/**
 * Admin Sidebar Navigation Component
 * Enhanced – Bigger Fonts, Rich Hover Effects, Dark Mode & Premium Aesthetics
 */
if (!defined('APP_NAME')) {
    exit('Direct access not permitted');
}

$currentPage = basename($_SERVER['PHP_SELF']);
$adminUser = current_user() ?? ['name' => 'Admin User', 'email' => 'admin@portal.com', 'avatar_url' => ''];

$pendingKycCount = 0;
$pendingFundingCount = 0;

if (isset($db) && $db instanceof PDO) {
    try {
        $pendingKycCount = (int)$db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'")->fetchColumn();
        $pendingFundingCount = (int)$db->query("SELECT COUNT(*) FROM funding_rounds WHERE status IN ('SUBMITTED', 'UNDER_REVIEW', 'under_review')")->fetchColumn();
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
        font-size: 0.8125rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        color: #475569;
        border-left: 3px solid transparent;
        transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, transform 0.14s ease;
        text-decoration: none;
        position: relative;
        overflow: hidden;
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
        width: 1.0625rem;
        height: 1.0625rem;
    }

    /* Rail collapsed support */
    #main-sidebar.rail-collapsed {
        width: 4.5rem !important;
    }
    #main-sidebar.rail-collapsed .sidebar-text-item,
    #main-sidebar.rail-collapsed .sidebar-group-title,
    #main-sidebar.rail-collapsed .sidebar-badge-item {
        display: none !important;
    }
</style>

<aside id="main-sidebar" data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
    class="fixed inset-y-0 left-0 z-50 w-72 sm:w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between h-full transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:w-64 flex-shrink-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none select-none">
    
    <div data-lenis-prevent="true" data-lenis-prevent-wheel="true" class="p-4 sm:p-5 overflow-y-auto flex-1">
        <!-- Brand Logo & Mobile Close Button -->
        <div class="flex items-center justify-between mb-5 sm:mb-6">
            <a href="<?= url('admin/dashboard.php') ?>" class="flex items-center space-x-2.5 group min-w-0">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-sm shadow-blue-600/20 group-hover:scale-105 transition flex-shrink-0">
                    <i data-lucide="shield" class="w-4 h-4"></i>
                </div>
                <div class="min-w-0 sidebar-text-item">
                    <div class="font-extrabold text-slate-900 dark:text-white text-sm tracking-tight leading-tight flex items-center gap-1 whitespace-nowrap">
                        ADMIN <span class="text-blue-600 dark:text-blue-400">CONTROL</span>
                    </div>
                    <div class="text-[8.5px] text-slate-400 font-bold uppercase tracking-wider whitespace-nowrap">Compliance & Audit</div>
                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex-shrink-0" title="Close navigation">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Admin Badge -->
        <div class="mb-5 p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/40 text-xs sidebar-badge-item">
            <div class="text-blue-900 dark:text-blue-200 font-bold flex items-center space-x-1.5 text-[10.5px]">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 flex-shrink-0"></span>
                <span class="whitespace-nowrap">Governance Access</span>
            </div>
            <div class="text-[9.5px] text-blue-700 dark:text-blue-400 mt-0.5 whitespace-nowrap">SEBI & KYC Gatekeeper</div>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1 text-xs font-semibold">
            <a href="<?= url('admin/dashboard.php') ?>" 
               class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'is-active' : '' ?>">
                <i data-lucide="gauge" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item">Overview & KPIs</span>
            </a>

            <a href="<?= url('admin/verification_queue.php') ?>" 
               class="sidebar-link <?= $currentPage === 'verification_queue.php' ? 'is-active' : '' ?>">
                <i data-lucide="check-square" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item flex-1">KYC Queue</span>
                <?php if ($pendingKycCount > 0): ?>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 sidebar-text-item"><?= $pendingKycCount ?></span>
                <?php endif; ?>
            </a>


            <!-- Subscriptions & Priority -->
            <a href="<?= url('admin/subscriptions.php') ?>" 
               title="Subscription Plans & Priority Manager"
               class="sidebar-link <?= $currentPage === 'subscriptions.php' ? 'is-active' : '' ?>">
                <i data-lucide="crown" class="nav-icon flex-shrink-0 text-amber-500"></i>
                <span class="whitespace-nowrap sidebar-text-item flex-1">Subscriptions &amp; Plans</span>
                <span class="px-1.5 py-0.5 rounded-full text-[9.5px] font-extrabold bg-purple-100 text-purple-700 sidebar-text-item">VIP</span>
            </a>

            <a href="<?= url('admin/users.php') ?>" 
               class="sidebar-link <?= $currentPage === 'users.php' ? 'is-active' : '' ?>">
                <i data-lucide="users" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item">Users Directory</span>
            </a>

            <a href="<?= url('admin/companies.php') ?>" 
               class="sidebar-link <?= $currentPage === 'companies.php' ? 'is-active' : '' ?>">
                <i data-lucide="building-2" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item">Startup Companies</span>
            </a>

            <!-- Share Allotments -->
            <a href="<?= url('admin/share_allotments.php') ?>" 
               title="Share Certificates & Cap Table"
               class="sidebar-link <?= $currentPage === 'share_allotments.php' ? 'is-active' : '' ?>">
                <i data-lucide="award" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item">Share Allotments</span>
            </a>

            <a href="<?= url('admin/transactions.php') ?>" 
               class="sidebar-link <?= $currentPage === 'transactions.php' ? 'is-active' : '' ?>">
                <i data-lucide="banknote" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item">Transactions & Escrow</span>
            </a>

            <a href="<?= url('admin/revenue.php') ?>" 
               class="sidebar-link <?= $currentPage === 'revenue.php' ? 'is-active' : '' ?>">
                <i data-lucide="receipt" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item">Revenue & Fees</span>
            </a>

            <a href="<?= url('admin/reports.php') ?>" 
               class="sidebar-link <?= $currentPage === 'reports.php' ? 'is-active' : '' ?>">
                <i data-lucide="bar-chart-3" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item">Reports & Stats</span>
            </a>

            <a href="<?= url('admin/audit_logs.php') ?>" 
               class="sidebar-link <?= $currentPage === 'audit_logs.php' ? 'is-active' : '' ?>">
                <i data-lucide="history" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item">Audit Trail</span>
            </a>

            <a href="<?= url('admin/broadcasts.php') ?>" 
               class="sidebar-link <?= $currentPage === 'broadcasts.php' ? 'is-active' : '' ?>">
                <i data-lucide="megaphone" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item">Broadcasts</span>
            </a>

            <a href="<?= url('admin/email_templates.php') ?>" 
               class="sidebar-link <?= $currentPage === 'email_templates.php' ? 'is-active' : '' ?>">
                <i data-lucide="mail" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item">Email Templates</span>
            </a>

            <a href="<?= url('admin/settings.php') ?>" 
               class="sidebar-link <?= $currentPage === 'settings.php' ? 'is-active' : '' ?>">
                <i data-lucide="settings" class="nav-icon flex-shrink-0"></i>
                <span class="whitespace-nowrap sidebar-text-item">Settings & Security</span>
            </a>
        </nav>
    </div>

    <!-- User Footer Profile & Logout -->
    <div class="p-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5 overflow-hidden min-w-0">
                <img src="<?= (!empty($adminUser['avatar_url']) ? htmlspecialchars($adminUser['avatar_url']) : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80') ?>"
                     class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-indigo-200 dark:border-slate-700">
                <div class="truncate sidebar-text-item">
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate"><?= htmlspecialchars($adminUser['name'] ?? 'Admin') ?></div>
                    <div class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold truncate">Administrator</div>
                </div>
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
                <a href="<?= url('auth/logout.php') ?>" title="Sign Out"
                   class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition">
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

    // Toggle Desktop Sidebar (Expanded vs Compact Rail)
    function toggleDesktopSidebar() {
        const sidebar = document.getElementById('main-sidebar');
        if (!sidebar) return;
        const isCollapsed = sidebar.classList.contains('rail-collapsed');
        if (isCollapsed) {
            sidebar.classList.remove('rail-collapsed');
            localStorage.setItem('admin_sidebar_collapsed', '0');
        } else {
            sidebar.classList.add('rail-collapsed');
            localStorage.setItem('admin_sidebar_collapsed', '1');
        }
    }

    // Restore desktop sidebar collapsed state
    document.addEventListener('DOMContentLoaded', () => {
        const savedState = localStorage.getItem('admin_sidebar_collapsed');
        const sidebar = document.getElementById('main-sidebar');
        if (savedState === '1' && sidebar && window.innerWidth >= 1024) {
            sidebar.classList.add('rail-collapsed');
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
