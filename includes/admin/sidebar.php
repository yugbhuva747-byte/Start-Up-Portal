<?php
/**
 * Admin Sidebar Navigation Component
 * Clean White / Light Theme, Small Crisp Typography
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$adminUser = current_user();
?>
<!-- Mobile Drawer Backdrop -->
<div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity duration-300"></div>

<aside id="main-sidebar" class="fixed inset-y-0 left-0 z-50 w-72 sm:w-64 bg-white border-r border-slate-200 flex flex-col justify-between h-full transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:w-60 flex-shrink-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none select-none">
    <div class="p-4 sm:p-5 overflow-y-auto flex-1">
        <!-- Brand Logo & Mobile Close Button -->
        <div class="flex items-center justify-between mb-5 sm:mb-6">
            <a href="<?= url('admin/dashboard.php') ?>" class="flex items-center space-x-2.5 group">
                <div class="w-8 h-8 rounded-lg bg-purple-600 flex items-center justify-center text-white shadow-sm shadow-purple-600/20 group-hover:scale-105 transition">
                    <i data-lucide="shield" class="w-4 h-4"></i>
                </div>
                <div>
                    <div class="font-extrabold text-slate-900 text-sm tracking-tight leading-tight flex items-center gap-1">
                        ADMIN <span class="text-purple-600">CONTROL</span>
                    </div>
                    <div class="text-[8.5px] text-slate-400 font-bold uppercase tracking-wider">Compliance & Audit</div>
                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="Close navigation">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Admin Badge -->
        <div class="mb-5 p-2.5 rounded-xl bg-purple-50 border border-purple-100 text-xs">
            <div class="text-purple-900 font-bold flex items-center space-x-1.5 text-[10.5px]">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-600"></span>
                <span>Governance Access</span>
            </div>
            <div class="text-[9.5px] text-purple-700 mt-0.5">SEBI & KYC Gatekeeper</div>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1 text-xs font-semibold">
            <a href="<?= url('admin/dashboard.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'dashboard.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="gauge" class="w-4 h-4 <?= $currentPage === 'dashboard.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Overview & KPIs</span>
            </a>

            <a href="<?= url('admin/verification_queue.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'verification_queue.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="check-square" class="w-4 h-4 <?= $currentPage === 'verification_queue.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>KYC Queue</span>
            </a>

            <a href="<?= url('admin/funding_review.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'funding_review.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="file-check-2" class="w-4 h-4 <?= $currentPage === 'funding_review.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Funding Approvals</span>
            </a>

            <a href="<?= url('admin/users.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'users.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="users" class="w-4 h-4 <?= $currentPage === 'users.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Users Directory</span>
            </a>

            <a href="<?= url('admin/companies.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'companies.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="building-2" class="w-4 h-4 <?= $currentPage === 'companies.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Startup Companies</span>
            </a>

            <a href="<?= url('admin/share_allotments.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'share_allotments.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="award" class="w-4 h-4 <?= $currentPage === 'share_allotments.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Share Allotments</span>
            </a>

            <a href="<?= url('admin/transactions.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'transactions.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="banknote" class="w-4 h-4 <?= $currentPage === 'transactions.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Transactions & Escrow</span>
            </a>

            <a href="<?= url('admin/revenue.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'revenue.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="receipt" class="w-4 h-4 <?= $currentPage === 'revenue.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Revenue & Commissions</span>
            </a>

            <a href="<?= url('admin/audit_logs.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'audit_logs.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="history" class="w-4 h-4 <?= $currentPage === 'audit_logs.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Security Audit Trail</span>
            </a>

            <a href="<?= url('admin/reports.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'reports.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="bar-chart-3" class="w-4 h-4 <?= $currentPage === 'reports.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Reports & Analytics</span>
            </a>

            <a href="<?= url('admin/broadcasts.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'broadcasts.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="megaphone" class="w-4 h-4 <?= $currentPage === 'broadcasts.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Platform Broadcasts</span>
            </a>

            <a href="<?= url('admin/email_templates.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'email_templates.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="mail-check" class="w-4 h-4 <?= $currentPage === 'email_templates.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Email Templates & Logs</span>
            </a>

            <a href="<?= url('admin/settings.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'settings.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="settings" class="w-4 h-4 <?= $currentPage === 'settings.php' ? 'text-purple-600' : 'text-slate-400' ?>"></i>
                <span>Platform Settings</span>
            </a>
        </nav>
    </div>

    <!-- User Footer Profile & Logout -->
    <div class="p-3.5 border-t border-slate-200 bg-slate-50/50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2.5 overflow-hidden">
                <img src="<?= $adminUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80' ?>" class="w-8 h-8 rounded-full object-cover border border-purple-200">
                <div class="truncate">
                    <div class="text-xs font-bold text-slate-800 truncate"><?= htmlspecialchars($adminUser['name']) ?></div>
                    <div class="text-[10px] text-purple-600 font-semibold truncate">Compliance Officer</div>
                </div>
            </div>
            <a href="<?= url('auth/logout.php') ?>" title="Sign Out" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition">
                <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
            </a>
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
</script>
