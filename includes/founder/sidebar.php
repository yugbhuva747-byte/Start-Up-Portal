<?php
/**
 * Founder Sidebar Navigation Component
 * Modern, High-Legibility Typography with Clean Design Tokens
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
        width: 5px;
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
</style>
<!-- Mobile Drawer Backdrop -->
<div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity duration-300"></div>

<aside id="main-sidebar" data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
    class="fixed inset-y-0 left-0 z-50 w-72 sm:w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between h-full transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:w-64 flex-shrink-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none select-none">
    <div data-lenis-prevent="true" data-lenis-prevent-wheel="true" class="p-4 sm:p-5 overflow-y-auto flex-1">
        <!-- Brand Logo & Mobile Close Button -->
        <div class="flex items-center justify-between mb-6">
            <a href="<?= url('founder/dashboard.php') ?>" class="flex items-center space-x-3 group">
                <div
                    class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-600/20 group-hover:scale-105 transition">
                    <i data-lucide="zap" class="w-5 h-5"></i>
                </div>
                <div>
                    <div
                        class="font-black text-slate-900 dark:text-white text-base tracking-tight leading-tight flex items-center gap-1">
                        FOUNDER <span class="text-indigo-600 dark:text-indigo-400">HUB</span>
                    </div>
                    <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Startup Workspace</div>
                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()"
                class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                title="Close navigation">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Readiness Progress Card -->
        <div class="mb-6 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
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
                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'dashboard.php' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="layout-dashboard"
                    class="w-4.5 h-4.5 <?= $currentPage === 'dashboard.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span>Overview</span>
            </a>

            <a href="<?= url('founder/company.php') ?>"
                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'company.php' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="building-2"
                    class="w-4.5 h-4.5 <?= $currentPage === 'company.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span>Company Profile</span>
            </a>

            <a href="<?= url('founder/funding_rounds.php') ?>"
                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'funding_rounds.php' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="circle-dollar-sign"
                    class="w-4.5 h-4.5 <?= $currentPage === 'funding_rounds.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span>Funding Rounds</span>
            </a>

            <a href="<?= url('founder/cap_table.php') ?>"
                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'cap_table.php' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="pie-chart"
                    class="w-4.5 h-4.5 <?= $currentPage === 'cap_table.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span>Cap Table & Equity</span>
            </a>

            <a href="<?= url('founder/verification.php') ?>"
                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'verification.php' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="shield-check"
                    class="w-4.5 h-4.5 <?= $currentPage === 'verification.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span>KYC & DigiLocker</span>
            </a>

            <a href="<?= url('founder/messages.php') ?>"
                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'messages.php' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="message-square"
                    class="w-4.5 h-4.5 <?= $currentPage === 'messages.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span>Investor Chat</span>
            </a>

            <a href="<?= url('founder/updates.php') ?>"
                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'updates.php' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="newspaper"
                    class="w-4.5 h-4.5 <?= $currentPage === 'updates.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span>Updates & Milestones</span>
            </a>

            <a href="<?= url('founder/blogs.php') ?>"
                class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'blogs.php' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                <i data-lucide="book-open"
                    class="w-4.5 h-4.5 <?= $currentPage === 'blogs.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' ?>"></i>
                <span>Company Blog & Stories</span>
            </a>
        </nav>
    </div>

    <!-- User Footer Profile & Logout -->
    <div class="p-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40">
        <div class="flex items-center justify-between">
            <a href="<?= url('founder/view.php') ?>" title="View Full Profile"
                class="flex items-center space-x-3 overflow-hidden flex-1 p-1 rounded-xl hover:bg-slate-200/60 dark:hover:bg-slate-750 transition group">
                <img src="<?= $founderUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80' ?>"
                    class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-slate-700 group-hover:ring-2 group-hover:ring-indigo-500 transition">
                <div class="truncate">
                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                        <?= htmlspecialchars($founderUser['name']) ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 truncate flex items-center space-x-1">
                        <span>Founder Profile</span>
                        <i data-lucide="chevron-right" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition"></i>
                    </div>
                </div>
            </a>
            <a href="<?= url('auth/logout.php') ?>" title="Sign Out"
                class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition ml-1">
                <i data-lucide="log-out" class="w-4 h-4"></i>
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
</script>