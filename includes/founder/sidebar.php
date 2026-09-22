<?php
/**
 * Founder Sidebar Navigation Component
 * Clean White / Light Theme, Small Crisp Typography
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$founderUser = current_user();
$founderProgress = get_profile_progress($founderUser['id'], 'founder');
?>
<aside class="w-60 bg-white border-r border-slate-200 flex flex-col justify-between h-screen sticky top-0 z-30 select-none">
    <div class="p-5">
        <!-- Brand Logo -->
        <a href="<?= url('founder/dashboard.php') ?>" class="flex items-center space-x-2.5 mb-6 group">
            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-600/20 group-hover:scale-105 transition">
                <i data-lucide="zap" class="w-4 h-4"></i>
            </div>
            <div>
                <div class="font-extrabold text-slate-900 text-sm tracking-tight leading-tight flex items-center gap-1">
                    FOUNDER <span class="text-indigo-600">HUB</span>
                </div>
                <div class="text-[8.5px] text-slate-400 font-bold uppercase tracking-wider">Startup Workspace</div>
            </div>
        </a>

        <!-- Readiness Progress Card -->
        <div class="mb-5 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
            <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="text-slate-500 font-semibold text-[10.5px]">Profile Readiness</span>
                <span class="text-indigo-600 font-bold text-xs"><?= $founderProgress['percentage'] ?>%</span>
            </div>
            <div class="w-full h-1 bg-slate-200 rounded-full overflow-hidden mb-2">
                <div class="h-full bg-indigo-600 rounded-full transition-all duration-500" style="width: <?= $founderProgress['percentage'] ?>%"></div>
            </div>
            <?php if (!$founderProgress['is_complete']): ?>
                <a href="<?= url('founder/verification.php') ?>" class="text-[10.5px] text-indigo-600 hover:text-indigo-700 font-semibold flex items-center space-x-1 transition">
                    <span>Complete KYC & Details</span>
                    <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </a>
            <?php else: ?>
                <span class="text-[10.5px] text-emerald-600 font-semibold flex items-center space-x-1">
                    <i data-lucide="check-circle" class="w-3 h-3"></i>
                    <span>Verified for Funding</span>
                </span>
            <?php endif; ?>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1 text-xs font-semibold">
            <a href="<?= url('founder/dashboard.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'dashboard.php' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="layout-dashboard" class="w-4 h-4 <?= $currentPage === 'dashboard.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                <span>Overview</span>
            </a>

            <a href="<?= url('founder/company.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'company.php' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="building-2" class="w-4 h-4 <?= $currentPage === 'company.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                <span>Company Profile</span>
            </a>

            <a href="<?= url('founder/funding_rounds.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'funding_rounds.php' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="circle-dollar-sign" class="w-4 h-4 <?= $currentPage === 'funding_rounds.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                <span>Funding Rounds</span>
            </a>

            <a href="<?= url('founder/verification.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'verification.php' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="shield-check" class="w-4 h-4 <?= $currentPage === 'verification.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                <span>KYC & DigiLocker</span>
            </a>

            <a href="<?= url('founder/messages.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'messages.php' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="message-square" class="w-4 h-4 <?= $currentPage === 'messages.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                <span>Investor Chat</span>
            </a>

            <a href="<?= url('founder/updates.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'updates.php' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="newspaper" class="w-4 h-4 <?= $currentPage === 'updates.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                <span>Updates & Milestones</span>
            </a>

            <a href="<?= url('founder/blogs.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'blogs.php' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="book-open" class="w-4 h-4 <?= $currentPage === 'blogs.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                <span>Company Blog & Stories</span>
            </a>

            <a href="<?= url('founder/profile.php') ?>" 
               class="flex items-center space-x-2.5 px-3 py-2 rounded-lg transition <?= $currentPage === 'profile.php' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i data-lucide="user" class="w-4 h-4 <?= $currentPage === 'profile.php' ? 'text-indigo-600' : 'text-slate-400' ?>"></i>
                <span>Founder Profile</span>
            </a>
        </nav>
    </div>

    <!-- User Footer Profile & Logout -->
    <div class="p-3 border-t border-slate-200 bg-slate-50/60">
        <div class="flex items-center justify-between">
            <a href="<?= url('founder/view.php') ?>" title="View Full Profile" class="flex items-center space-x-2.5 overflow-hidden flex-1 p-1 rounded-lg hover:bg-slate-200/60 transition group">
                <img src="<?= $founderUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80' ?>" class="w-8 h-8 rounded-full object-cover border border-slate-200 group-hover:ring-2 group-hover:ring-indigo-500 transition">
                <div class="truncate">
                    <div class="text-xs font-bold text-slate-800 truncate group-hover:text-indigo-600 transition"><?= htmlspecialchars($founderUser['name']) ?></div>
                    <div class="text-[10px] text-slate-400 truncate flex items-center space-x-1">
                        <span>Founder Profile</span>
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
