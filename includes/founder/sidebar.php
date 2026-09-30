<?php
/**
 * Founder Sidebar Navigation Component
 * Modern, High-Legibility Typography with Perfect Visibility
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$founderUser = current_user();
$founderProgress = get_profile_progress($founderUser['id'], 'founder');
?>
<!-- Mobile Drawer Backdrop -->
<div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity duration-300"></div>

<aside id="main-sidebar" class="fixed inset-y-0 left-0 z-50 w-72 sm:w-64 bg-white border-r border-slate-200 flex flex-col justify-between h-full transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:w-64 flex-shrink-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none select-none">
    <div class="p-4 sm:p-5 overflow-y-auto flex-1">
        <!-- Brand Logo & Mobile Close Button -->
        <div class="flex items-center justify-between mb-5 sm:mb-6">
            <a href="<?= url('founder/dashboard.php') ?>" class="flex items-center space-x-2.5 group">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-600/25 group-hover:scale-105 transition">
                    <i data-lucide="zap" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="font-black text-slate-900 text-base tracking-tight leading-tight flex items-center gap-1">
                        FOUNDER <span class="text-indigo-600">HUB</span>
                    </div>
                    <div class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">Startup Workspace</div>
                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Close navigation">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Readiness Progress Card -->
        <div class="mb-5 p-3.5 rounded-xl bg-slate-50 border border-slate-200/90 shadow-2xs">
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="text-slate-700 font-bold text-xs uppercase tracking-wider">Profile Readiness</span>
                <span class="text-indigo-600 font-black text-sm"><?= $founderProgress['percentage'] ?>%</span>
            </div>
            <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden mb-2.5">
                <div class="h-full bg-indigo-600 rounded-full transition-all duration-500" style="width: <?= $founderProgress['percentage'] ?>%"></div>
            </div>
            <?php if (!$founderProgress['is_complete']): ?>
                <a href="<?= url('founder/verification.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center space-x-1.5 transition">
                    <span>Complete KYC & Details</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            <?php else: ?>
                <span class="text-xs text-emerald-700 font-bold flex items-center space-x-1.5">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                    <span>Verified for Funding</span>
                </span>
            <?php endif; ?>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1 text-[13px] font-semibold">
            <a href="<?= url('founder/dashboard.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'dashboard.php' ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="layout-dashboard" class="w-4 h-4 <?= $currentPage === 'dashboard.php' ? 'text-indigo-600' : 'text-slate-500' ?>"></i>
                <span>Overview</span>
            </a>

            <a href="<?= url('founder/funding_rounds.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'funding_rounds.php' ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="circle-dollar-sign" class="w-4 h-4 <?= $currentPage === 'funding_rounds.php' ? 'text-indigo-600' : 'text-slate-500' ?>"></i>
                <span>Funding Rounds</span>
            </a>

            <a href="<?= url('founder/cap_table.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'cap_table.php' ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="pie-chart" class="w-4 h-4 <?= $currentPage === 'cap_table.php' ? 'text-indigo-600' : 'text-slate-500' ?>"></i>
                <span>Cap Table & Equity</span>
            </a>

            <a href="<?= url('founder/verification.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'verification.php' ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="shield-check" class="w-4 h-4 <?= $currentPage === 'verification.php' ? 'text-indigo-600' : 'text-slate-500' ?>"></i>
                <span>KYC & DigiLocker</span>
            </a>

            <a href="<?= url('founder/messages.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'messages.php' ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="message-square" class="w-4 h-4 <?= $currentPage === 'messages.php' ? 'text-indigo-600' : 'text-slate-500' ?>"></i>
                <span>Investor Chat</span>
            </a>

            <a href="<?= url('founder/updates.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'updates.php' ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="newspaper" class="w-4 h-4 <?= $currentPage === 'updates.php' ? 'text-indigo-600' : 'text-slate-500' ?>"></i>
                <span>Updates & Milestones</span>
            </a>

            <a href="<?= url('founder/blogs.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'blogs.php' ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="book-open" class="w-4 h-4 <?= $currentPage === 'blogs.php' ? 'text-indigo-600' : 'text-slate-500' ?>"></i>
                <span>Company Blog & Stories</span>
            </a>
        </nav>
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
