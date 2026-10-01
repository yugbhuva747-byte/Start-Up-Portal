<?php
/**
 * Investor Sidebar Navigation Component
 * Clean White / Light Theme, Small Crisp Typography
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$investorUser = current_user();
$investorProgress = get_profile_progress($investorUser['id'], 'investor');
?>
<!-- Mobile Drawer Backdrop -->
<div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity duration-300"></div>

<aside id="main-sidebar" class="fixed inset-y-0 left-0 z-50 w-72 sm:w-64 bg-white border-r border-slate-200 flex flex-col justify-between h-full transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:w-64 flex-shrink-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none select-none">
    <div class="p-4 sm:p-5 overflow-y-auto flex-1">
        <!-- Brand Logo & Mobile Close Button -->
        <div class="flex items-center justify-between mb-5 sm:mb-6">
            <a href="<?= url('investor/dashboard.php') ?>" class="flex items-center space-x-2.5 group">
                <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-sm shadow-emerald-600/25 group-hover:scale-105 transition">
                    <i data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="font-black text-slate-900 text-base tracking-tight leading-tight flex items-center gap-1">
                        INVESTOR <span class="text-emerald-600">HUB</span>
                    </div>
                    <div class="text-xs text-slate-500 font-bold uppercase tracking-wider">Syndicate & Venture</div>
                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Close navigation">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Accreditation Card -->
        <div class="mb-5 p-3.5 rounded-xl bg-slate-50 border border-slate-200/90 shadow-2xs">
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="text-slate-700 font-bold text-xs uppercase tracking-wider">Accreditation</span>
                <span class="text-emerald-600 font-black text-sm"><?= $investorProgress['percentage'] ?>%</span>
            </div>
            <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden mb-2.5">
                <div class="h-full bg-emerald-600 rounded-full transition-all duration-500" style="width: <?= $investorProgress['percentage'] ?>%"></div>
            </div>
            <?php if (!$investorProgress['is_complete']): ?>
                <a href="<?= url('investor/verification.php') ?>" class="text-xs text-emerald-600 hover:text-emerald-800 font-bold flex items-center space-x-1.5 transition">
                    <span>Complete KYC & Accreditation</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            <?php else: ?>
                <span class="text-xs text-emerald-700 font-bold flex items-center space-x-1.5">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    <span>SEBI & KYC Verified Angel</span>
                </span>
            <?php endif; ?>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1 text-[13px] font-semibold">
            <a href="<?= url('investor/dashboard.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'dashboard.php' ? 'bg-emerald-50 text-emerald-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="layout-grid" class="w-4 h-4 <?= $currentPage === 'dashboard.php' ? 'text-emerald-600' : 'text-slate-500' ?>"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= url('investor/discover.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'discover.php' || $currentPage === 'startup_detail.php' ? 'bg-emerald-50 text-emerald-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="compass" class="w-4 h-4 <?= $currentPage === 'discover.php' || $currentPage === 'startup_detail.php' ? 'text-emerald-600' : 'text-slate-500' ?>"></i>
                <span>Discover Startups</span>
            </a>

            <a href="<?= url('investor/watchlist.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'watchlist.php' ? 'bg-emerald-50 text-emerald-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="bookmark" class="w-4 h-4 <?= $currentPage === 'watchlist.php' ? 'text-emerald-600' : 'text-slate-500' ?>"></i>
                <span>Watchlist</span>
            </a>

            <a href="<?= url('investor/messages.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'messages.php' ? 'bg-emerald-50 text-emerald-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="message-square" class="w-4 h-4 <?= $currentPage === 'messages.php' ? 'text-emerald-600' : 'text-slate-500' ?>"></i>
                <span>Deal Room Chat</span>
            </a>

            <a href="<?= url('investor/verification.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-xl transition <?= $currentPage === 'verification.php' ? 'bg-emerald-50 text-emerald-700 font-bold shadow-2xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/70' ?>">
                <i data-lucide="shield-check" class="w-4 h-4 <?= $currentPage === 'verification.php' ? 'text-emerald-600' : 'text-slate-500' ?>"></i>
                <span>KYC & DigiLocker</span>
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

