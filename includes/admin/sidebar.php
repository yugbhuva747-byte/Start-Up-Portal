<?php
/**
 * Global Admin Sidebar Navigation Component
 * Modern, colorful, and streamlined interface.
 */
if (!defined('APP_NAME')) {
    exit('Direct access not permitted');
}

$currentPage = basename($_SERVER['PHP_SELF']);
$adminUser = $_SESSION['user'] ?? ['name' => 'Admin User', 'email' => 'admin@portal.com', 'avatar_url' => ''];

// Real-time counter caches for badges
$pendingKycCount = 0;
$pendingFundingCount = 0;

if (isset($db) && $db instanceof PDO) {
    try {
        $pendingKycCount = (int)$db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'")->fetchColumn();
        $pendingFundingCount = (int)$db->query("SELECT COUNT(*) FROM funding_rounds WHERE status = 'under_review'")->fetchColumn();
    } catch (Exception $e) {}
}
?>

<!-- Admin Interface Styles (Embedded Directly — No External CSS File Required) -->
<style>
@media (min-width: 1024px) {
    aside#main-sidebar {
        position: sticky !important;
        top: 0 !important;
        height: 100vh !important;
        flex-shrink: 0 !important;
        z-index: 30 !important;
    }
}

.custom-sidebar-scroll::-webkit-scrollbar { width: 4px; }
.custom-sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.25); border-radius: 9999px; }
.custom-sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.4); }

/* Sidebar Rail Collapsed */
aside#main-sidebar.rail-collapsed { width: 4.5rem !important; }
aside#main-sidebar.rail-collapsed .sidebar-text-item,
aside#main-sidebar.rail-collapsed .sidebar-search-box,
aside#main-sidebar.rail-collapsed .sidebar-group-title,
aside#main-sidebar.rail-collapsed .sidebar-badge { display: none !important; }
aside#main-sidebar.rail-collapsed .nav-link { justify-content: center !important; padding-left: 0 !important; padding-right: 0 !important; }
aside#main-sidebar.rail-collapsed #collapse-icon { transform: rotate(180deg); }
aside#main-sidebar.rail-collapsed #sidebar-footer .sidebar-text-item,
aside#main-sidebar.rail-collapsed #sidebar-footer a { display: none !important; }
aside#main-sidebar.rail-collapsed #sidebar-footer { padding: 0.75rem 0 !important; display: flex !important; justify-content: center !important; }

/* Navigation Links */
.nav-link {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    transition: all 0.15s ease;
}
.nav-link:hover { color: #4338CA; background-color: #EEF2FF; }
.nav-link:hover i { color: #4F46E5; }
.nav-link.active {
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%) !important;
    color: #FFFFFF !important;
    font-weight: 700;
    box-shadow: 0 4px 12px -2px rgba(79, 70, 229, 0.35) !important;
}
.nav-link.active i { color: #FFFFFF !important; }
.nav-link.active .sidebar-badge { background-color: rgba(255, 255, 255, 0.25) !important; color: #FFFFFF !important; }

/* Admin UI Components */
.admin-card {
    background: #FFFFFF;
    border: 1px solid rgba(226, 232, 240, 0.85);
    border-radius: 1rem;
    box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.03);
    transition: all 0.2s ease;
}
.admin-stat-card {
    background: #FFFFFF;
    border: 1px solid rgba(226, 232, 240, 0.85);
    border-radius: 1rem;
    padding: 1.25rem;
    box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.03);
    transition: all 0.2s ease;
}
.admin-stat-card:hover {
    border-color: rgba(199, 210, 254, 0.9);
    box-shadow: 0 6px 16px -2px rgba(79, 70, 229, 0.08);
}
.admin-stat-label {
    font-size: 0.6875rem;
    font-weight: 700;
    color: #94A3B8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.admin-stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #0F172A;
    line-height: 1.25;
    margin-top: 0.25rem;
}
.stat-value-indigo { color: #4338CA !important; }
.stat-value-emerald { color: #047857 !important; }
.stat-value-amber { color: #B45309 !important; }
.stat-value-violet { color: #6D28D9 !important; }
.stat-value-sky { color: #0369A1 !important; }
.stat-value-rose { color: #BE123C !important; }

.admin-page-icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.875rem;
    background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
    color: #4F46E5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px 0 rgba(79, 70, 229, 0.15);
    flex-shrink: 0;
}
.admin-page-icon.emerald { background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%); color: #059669; }
.admin-page-icon.amber { background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%); color: #D97706; }
.admin-page-icon.violet { background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%); color: #7C3AED; }

.admin-table-container {
    background: #FFFFFF;
    border: 1px solid rgba(226, 232, 240, 0.85);
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.03);
}
.admin-table {
    width: 100%;
    text-align: left;
    border-collapse: collapse;
    font-size: 0.75rem;
}
.admin-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    padding: 0.75rem 1rem;
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748B;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}
.admin-table tbody td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid rgba(241, 245, 249, 0.8);
    color: #334155;
    line-height: 1.4;
    vertical-align: middle;
}
.admin-table tbody tr:hover { background-color: rgba(248, 250, 252, 0.8); }

.admin-input {
    width: 100%;
    background-color: #FFFFFF;
    border: 1px solid rgba(226, 232, 240, 0.85);
    border-radius: 0.75rem;
    padding: 0.5rem 0.875rem;
    font-size: 0.8125rem;
    color: #1E293B;
    transition: all 0.15s ease-in-out;
    outline: none;
    box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.04);
}
.admin-input:focus {
    border-color: #6366F1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    background-color: #FFFFFF;
}
.admin-input::placeholder { color: #94A3B8; }

.admin-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
    color: #FFFFFF;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.5rem 0.875rem;
    border-radius: 0.75rem;
    box-shadow: 0 2px 4px 0 rgba(79, 70, 229, 0.25);
    transition: all 0.15s ease;
}
.admin-btn-primary:hover {
    background: linear-gradient(135deg, #4338CA 0%, #3730A3 100%);
    box-shadow: 0 4px 8px 0 rgba(79, 70, 229, 0.35);
}
.admin-btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    color: #334155;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.375rem 0.75rem;
    border-radius: 0.625rem;
    box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.05);
    transition: all 0.15s ease;
}
.admin-btn-secondary:hover { background: #F8FAFC; border-color: #94A3B8; }

.admin-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.2rem 0.625rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 600;
    line-height: 1.25;
}
.admin-badge-dot { width: 0.375rem; height: 0.375rem; border-radius: 9999px; flex-shrink: 0; }
.admin-badge-success, .badge-success { background-color: #ECFDF5; color: #065F46; border: 1px solid rgba(167, 243, 208, 0.9); }
.admin-badge-success .admin-badge-dot, .badge-success .admin-badge-dot { background-color: #10B981; }
.admin-badge-danger, .badge-danger { background-color: #FFF1F2; color: #9F1239; border: 1px solid rgba(254, 205, 211, 0.9); }
.admin-badge-danger .admin-badge-dot, .badge-danger .admin-badge-dot { background-color: #F43F5E; }
.admin-badge-neutral, .badge-neutral { background-color: #F8FAFC; color: #475569; border: 1px solid #E2E8F0; }
.admin-badge-neutral .admin-badge-dot, .badge-neutral .admin-badge-dot { background-color: #94A3B8; }
.badge-primary { background-color: #EEF2FF; color: #4338CA; border: 1px solid rgba(199, 210, 254, 0.9); }
.badge-primary .admin-badge-dot { background-color: #6366F1; }

.admin-filter-bar {
    background: #FFFFFF;
    border: 1px solid rgba(226, 232, 240, 0.85);
    border-radius: 0.875rem;
    padding: 0.375rem;
}
.admin-filter-pill {
    padding: 0.375rem 0.75rem;
    border-radius: 0.625rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748B;
    transition: all 0.15s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
}
.admin-filter-pill:hover { color: #4F46E5; background-color: #EEF2FF; }
.admin-filter-pill.active {
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%) !important;
    color: #FFFFFF !important;
    font-weight: 700;
    box-shadow: 0 2px 6px 0 rgba(79, 70, 229, 0.3) !important;
}
.admin-filter-pill.active i { color: #FFFFFF !important; }
</style>

<!-- Mobile Backdrop -->
<div id="mobile-sidebar-backdrop" 
     onclick="toggleMobileSidebar()" 
     class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity duration-200"></div>

<!-- Sidebar Container -->
<aside id="main-sidebar" 
       class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200/90 flex flex-col justify-between transition-all duration-200 ease-in-out -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:flex-shrink-0 select-none">

    <!-- Top Section (Scrollable) -->
    <div class="p-4 overflow-y-auto flex-1 custom-sidebar-scroll" id="sidebar-scrollable">
        
        <!-- Brand Logo & Controls -->
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <a href="<?= url('admin/dashboard.php') ?>" class="flex items-center space-x-3 group min-w-0" title="<?= APP_NAME ?> Admin Console">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white flex items-center justify-center font-bold shadow-sm shadow-indigo-600/30 group-hover:from-indigo-700 group-hover:to-violet-700 transition-all flex-shrink-0">
                    <i data-lucide="shield" class="w-4 h-4 text-white"></i>
                </div>
                <div class="min-w-0 sidebar-text-item">
                    <div class="font-bold text-slate-900 text-sm tracking-tight truncate leading-tight group-hover:text-indigo-600 transition-colors">
                        Startup Portal
                    </div>
                    <div class="text-[10px] text-indigo-600 font-bold tracking-wide uppercase truncate mt-0.5">Admin Console</div>
                </div>
            </a>

            <!-- Desktop Collapse/Expand Rail Toggle -->
            <button type="button" 
                    onclick="toggleDesktopSidebar()" 
                    id="desktop-toggle-btn"
                    class="hidden lg:flex p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition"
                    title="Toggle Compact / Full Sidebar">
                <i data-lucide="chevrons-left" id="collapse-icon" class="w-4 h-4"></i>
            </button>

            <!-- Mobile Close Button -->
            <button type="button" 
                    onclick="toggleMobileSidebar()" 
                    class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition" 
                    title="Close navigation">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- ==============================================
             NAVIGATION GROUPS
             ============================================== -->
        <nav class="space-y-4" id="sidebar-nav-container">

            <!-- ----------------------------------------------------
                 GROUP 1: CORE DESK
                 ---------------------------------------------------- -->
            <div class="nav-group">
                <div class="px-2 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 sidebar-group-title">
                    Core Desk
                </div>
                <div class="space-y-0.5">
                    <!-- Dashboard -->
                    <a href="<?= url('admin/dashboard.php') ?>" 
                       title="Overview & Platform KPIs"
                       class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Overview</span>
                    </a>

                    <!-- KYC Verification Queue -->
                    <a href="<?= url('admin/verification_queue.php') ?>" 
                       title="KYC & Verification Queue"
                       class="nav-link <?= $currentPage === 'verification_queue.php' ? 'active' : '' ?>">
                        <i data-lucide="shield-check" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">KYC Queue</span>
                        <?php if ($pendingKycCount > 0): ?>
                            <span class="sidebar-badge px-2 py-0.5 rounded-full text-[10.5px] font-bold <?= $currentPage === 'verification_queue.php' ? 'bg-white/25 text-white' : 'bg-amber-100 text-amber-800' ?>">
                                <?= $pendingKycCount ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- Funding Review -->
                    <a href="<?= url('admin/funding_review.php') ?>" 
                       title="Funding Approvals"
                       class="nav-link <?= $currentPage === 'funding_review.php' ? 'active' : '' ?>">
                        <i data-lucide="file-check-2" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Funding Approvals</span>
                        <?php if ($pendingFundingCount > 0): ?>
                            <span class="sidebar-badge px-2 py-0.5 rounded-full text-[10.5px] font-bold <?= $currentPage === 'funding_review.php' ? 'bg-white/25 text-white' : 'bg-amber-100 text-amber-800' ?>">
                                <?= $pendingFundingCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- ----------------------------------------------------
                 GROUP 2: DIRECTORY & FINANCE
                 ---------------------------------------------------- -->
            <div class="nav-group">
                <div class="px-2 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 sidebar-group-title">
                    Directory & Finance
                </div>
                <div class="space-y-0.5">
                    <!-- Startup Companies -->
                    <a href="<?= url('admin/companies.php') ?>" 
                       title="Startup Companies"
                       class="nav-link <?= $currentPage === 'companies.php' ? 'active' : '' ?>">
                        <i data-lucide="building-2" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Companies</span>
                    </a>

                    <!-- Users Directory -->
                    <a href="<?= url('admin/users.php') ?>" 
                       title="User Accounts"
                       class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">
                        <i data-lucide="users" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Users Directory</span>
                    </a>

                    <!-- Subscriptions & Priority -->
                    <a href="<?= url('admin/subscriptions.php') ?>" 
                       title="Subscription Plans & Priority Manager"
                       class="nav-link <?= $currentPage === 'subscriptions.php' ? 'active' : '' ?>">
                        <i data-lucide="crown" class="w-4 h-4 flex-shrink-0 text-amber-500"></i>
                        <span class="truncate sidebar-text-item flex-1">Subscriptions &amp; Plans</span>
                        <span class="sidebar-badge px-1.5 py-0.5 rounded-full text-[9.5px] font-extrabold bg-purple-100 text-purple-700">VIP</span>
                    </a>

                    <!-- Share Allotments -->
                    <a href="<?= url('admin/share_allotments.php') ?>" 
                       title="Share Certificates & Cap Table"
                       class="nav-link <?= $currentPage === 'share_allotments.php' ? 'active' : '' ?>">
                        <i data-lucide="award" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Share Allotments</span>
                    </a>

                    <!-- Escrow & Transactions -->
                    <a href="<?= url('admin/transactions.php') ?>" 
                       title="Escrow & Payment Transfers"
                       class="nav-link <?= $currentPage === 'transactions.php' ? 'active' : '' ?>">
                        <i data-lucide="banknote" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Escrow & Transfers</span>
                    </a>

                    <!-- Revenue & Fees -->
                    <a href="<?= url('admin/revenue.php') ?>" 
                       title="Platform Revenue & Invoices"
                       class="nav-link <?= $currentPage === 'revenue.php' ? 'active' : '' ?>">
                        <i data-lucide="receipt" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Revenue & Invoices</span>
                    </a>
                </div>
            </div>

            <!-- ----------------------------------------------------
                 GROUP 3: MANAGEMENT & PLATFORM
                 ---------------------------------------------------- -->
            <div class="nav-group">
                <div class="px-2 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 sidebar-group-title">
                    Management
                </div>
                <div class="space-y-0.5">
                    <!-- Reports & Stats -->
                    <a href="<?= url('admin/reports.php') ?>" 
                       title="Analytics & Reports"
                       class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Reports & Stats</span>
                    </a>

                    <!-- Audit Trail -->
                    <a href="<?= url('admin/audit_logs.php') ?>" 
                       title="Security Audit Trail"
                       class="nav-link <?= $currentPage === 'audit_logs.php' ? 'active' : '' ?>">
                        <i data-lucide="history" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Audit Trail</span>
                    </a>

                    <!-- Broadcasts -->
                    <a href="<?= url('admin/broadcasts.php') ?>" 
                       title="Broadcast Announcements"
                       class="nav-link <?= $currentPage === 'broadcasts.php' ? 'active' : '' ?>">
                        <i data-lucide="megaphone" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Broadcasts</span>
                    </a>

                    <!-- Email Templates -->
                    <a href="<?= url('admin/email_templates.php') ?>" 
                       title="Email Templates & Dispatch"
                       class="nav-link <?= $currentPage === 'email_templates.php' ? 'active' : '' ?>">
                        <i data-lucide="mail" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Email Templates</span>
                    </a>

                    <!-- Settings -->
                    <a href="<?= url('admin/settings.php') ?>" 
                       title="Settings & Security"
                       class="nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
                        <i data-lucide="settings" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="truncate sidebar-text-item flex-1">Settings</span>
                    </a>
                </div>
            </div>

        </nav>
    </div>

    <!-- Bottom User Section -->
    <div class="p-3.5 border-t border-slate-100 bg-slate-50/70" id="sidebar-footer">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2.5 min-w-0">
                <div class="relative flex-shrink-0">
                    <img src="<?= (!empty($adminUser['avatar_url']) ? htmlspecialchars($adminUser['avatar_url']) : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80') ?>" 
                         alt="<?= htmlspecialchars($adminUser['name'] ?? 'Admin') ?>" 
                         class="w-8 h-8 rounded-full object-cover border border-indigo-200">
                    <span class="absolute bottom-0 right-0 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                </div>
                <div class="min-w-0 sidebar-text-item">
                    <div class="text-xs font-bold text-slate-800 truncate"><?= htmlspecialchars($adminUser['name'] ?? 'Compliance Admin') ?></div>
                    <div class="text-[10px] text-indigo-600 font-semibold truncate">Administrator</div>
                </div>
            </div>
            
            <a href="<?= url('auth/logout.php') ?>" 
               title="Sign Out of Portal" 
               class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition flex-shrink-0">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</aside>

<script>
    // Initialize Lucide icons on load
    if (window.lucide) {
        lucide.createIcons();
    }

    // Toggle Mobile Sidebar Drawer
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
</script>
