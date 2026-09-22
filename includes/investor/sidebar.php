<?php
/**
 * Investor Sidebar Navigation Component
 * Clean White / Light Theme, Small Crisp Typography
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$investorUser = current_user();
$investorProgress = get_profile_progress($investorUser['id'], 'investor');
?>
<aside class="w-60 bg-white border-r border-slate-200 flex flex-col justify-between h-screen sticky top-0 z-30 select-none">
    <div class="p-5">
        <!-- Brand Logo -->
        <a href="<?= url('investor/dashboard.php') ?>" class="flex items-center space-x-2.5 mb-6 group">
            <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white shadow-sm shadow-emerald-600/20 group-hover:scale-105 transition">
                <i data-lucide="trending-up" class="w-4 h-4"></i>
            </div>
            <div>
                <div class="font-extrabold text-slate-900 text-sm tracking-tight leading-tight flex items-center gap-1">
                    INVESTOR <span class="text-emerald-600">HUB</span>
                </div>
                <div class="text-[8.5px] text-slate-400 font-bold uppercase tracking-wider">Syndicate & Venture</div>
            </div>
        </a>

        <!-- Accreditation Card -->
        <div class="mb-5 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
            <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="text-slate-500 font-semibold text-[10.5px]">Accreditation</span>
                <span class="text-emerald-600 font-bold text-xs"><?= $investorProgress['percentage'] ?>%</span>
            </div>
            <div class="w-full h-1 bg-slate-200 rounded-full overflow-hidden mb-2">
                <div class="h-full bg-emerald-600 rounded-full transition-all duration-500" style="width: <?= $investorProgress['percentage'] ?>%"></div>
            </div>
            <div class="text-[10.5px] text-slate-600 flex items-center space-x-1.5">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span class="font-medium">SEBI & KYC Verified Angel</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1 text-xs font-semibold">
            <a href="<?= url('investor/dashboard.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'dashboard.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="layout-grid" class="w-4 h-4 <?= $currentPage === 'dashboard.php' ? 'text-emerald-600' : 'text-slate-400' ?>"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= url('investor/discover.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'discover.php' || $currentPage === 'startup_detail.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="compass" class="w-4 h-4 <?= $currentPage === 'discover.php' || $currentPage === 'startup_detail.php' ? 'text-emerald-600' : 'text-slate-400' ?>"></i>
                <span>Discover Startups</span>
            </a>

            <a href="<?= url('investor/portfolio.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'portfolio.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="pie-chart" class="w-4 h-4 <?= $currentPage === 'portfolio.php' ? 'text-emerald-600' : 'text-slate-400' ?>"></i>
                <span>My Portfolio</span>
            </a>

            <a href="<?= url('investor/watchlist.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'watchlist.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="bookmark" class="w-4 h-4 <?= $currentPage === 'watchlist.php' ? 'text-emerald-600' : 'text-slate-400' ?>"></i>
                <span>Watchlist</span>
            </a>

            <a href="<?= url('investor/messages.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'messages.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="message-square" class="w-4 h-4 <?= $currentPage === 'messages.php' ? 'text-emerald-600' : 'text-slate-400' ?>"></i>
                <span>Deal Room Chat</span>
            </a>

            <a href="<?= url('investor/verification.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'verification.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="shield-check" class="w-4 h-4 <?= $currentPage === 'verification.php' ? 'text-emerald-600' : 'text-slate-400' ?>"></i>
                <span>KYC & DigiLocker</span>
            </a>

            <a href="<?= url('investor/profile.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'profile.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="sliders" class="w-4 h-4 <?= $currentPage === 'profile.php' ? 'text-emerald-600' : 'text-slate-400' ?>"></i>
                <span>Thesis & Profile</span>
            </a>
        </nav>
    </div>

    <!-- User Footer Profile & Logout -->
    <div class="p-3 border-t border-slate-200 bg-slate-50/60">
        <div class="flex items-center justify-between">
            <a href="<?= url('investor/view.php') ?>" title="View My Profile" class="flex items-center space-x-2.5 overflow-hidden flex-1 p-1 rounded-lg hover:bg-slate-200/60 transition group">
                <img src="<?= $investorUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80' ?>" class="w-8 h-8 rounded-full object-cover border border-slate-200 group-hover:ring-2 group-hover:ring-teal-500 transition">
                <div class="truncate">
                    <div class="text-xs font-bold text-slate-800 truncate group-hover:text-teal-700 transition"><?= htmlspecialchars($investorUser['name']) ?></div>
                    <div class="text-[10px] text-slate-400 truncate flex items-center space-x-1">
                        <span>Investor Profile</span>
                        <i data-lucide="chevron-right" class="w-2.5 h-2.5 opacity-0 group-hover:opacity-100 transition"></i>
                    </div>
                </div>
            </a>
            <a href="<?= url('auth/logout.php') ?>" title="Sign Out" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition ml-1">
                <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
            </a>
        </div>
    </div>
</aside>
