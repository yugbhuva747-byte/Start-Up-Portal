<?php
/**
 * Investor Navigation Sidebar
 * Professional Investor Network Experience
 * Font: 'Vay Portal - Regular', Blue: #123B7A, Deep Navy: #0B1F3A, Light Blue: #EAF2FF
 * High Readability & Comfortable Sizing
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$investorUser = current_user();
$investorProgress = get_profile_progress($investorUser['id'], 'investor');

// Load Investor Dark & Light Theme Controller
require_once __DIR__ . '/theme.php';
?>
<!-- Mobile Drawer Backdrop -->
<div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()"
    class="fixed inset-0 bg-[#0B1F3A]/40 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity duration-300"></div>

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
<aside id="main-sidebar" data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
    class="fixed inset-y-0 left-0 z-50 w-72 sm:w-68 bg-white dark:bg-slate-900 border-r border-[#E4E8EF] dark:border-slate-800 flex flex-col justify-between h-full transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:w-68 flex-shrink-0 transition-transform duration-300 ease-in-out select-none shadow-sm"
    style="font-family: 'Vay Portal - Regular', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;">
    <div data-lenis-prevent="true" data-lenis-prevent-wheel="true" class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-6">
        <!-- Brand Identity & Close Button -->
        <div class="flex items-center justify-between pb-5 border-b border-[#E4E8EF] dark:border-slate-800">
            <a href="<?= url('investor/dashboard.php') ?>" class="flex items-center space-x-3.5 group">
                <div
                    class="w-10 h-10 rounded-xl bg-[#123B7A] dark:bg-blue-600 flex items-center justify-center text-white shadow-sm group-hover:bg-[#0B1F3A] dark:group-hover:bg-blue-700 transition">
                    <i data-lucide="compass" class="w-5 h-5"></i>
                </div>
                <div>
                    <div
                        class="font-black text-[#0B1F3A] dark:text-white text-base tracking-tight leading-tight flex items-center gap-1.5">
                        <span>INVESTOR</span>

                    </div>

                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()"
                class="lg:hidden p-2 rounded-xl text-[#4B5563] dark:text-slate-300 hover:text-[#111827] dark:hover:text-white hover:bg-[#FAFBFD] dark:hover:bg-slate-800 transition"
                title="Close navigation">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Investor Identity Strip -->
        <div class="pb-5 border-b border-[#E4E8EF] dark:border-slate-800">
            <div class="flex items-center space-x-3.5 mb-3.5">
                <img src="<?= $investorUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100' ?>"
                    class="w-11 h-11 rounded-full object-cover border-2 border-[#E4E8EF] dark:border-slate-700 shadow-xs">
                <div class="min-w-0">
                    <div class="text-sm font-black text-[#0B1F3A] dark:text-white truncate">
                        <?= htmlspecialchars($investorUser['name']) ?>
                    </div>
                    <div class="text-xs text-[#4B5563] dark:text-slate-400 font-medium truncate mt-0.5">
                        <?= htmlspecialchars($investorUser['email']) ?>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="text-[#4B5563] dark:text-slate-400 font-semibold flex items-center gap-1.5">
                    <i data-lucide="shield-check"
                        class="w-4 h-4 <?= $investorUser['is_verified'] ? 'text-[#123B7A] dark:text-blue-400' : 'text-amber-500' ?>"></i>
                    Accreditation Status
                </span>
                <span
                    class="text-[#123B7A] dark:text-blue-400 font-extrabold"><?= $investorProgress['percentage'] ?>%</span>
            </div>
            <div class="w-full h-1.5 bg-[#E4E8EF] dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-[#123B7A] dark:bg-blue-600 rounded-full transition-all duration-500"
                    style="width: <?= $investorProgress['percentage'] ?>%"></div>
            </div>
        </div>

        <?php
        $navGroups = [
            'Identity & Trust' => [
                [
                    'title' => 'Public Profile',
                    'url' => url('investor/view.php'),
                    'icon' => 'user-check',
                    'active' => ($currentPage === 'view.php')
                ],
                [
                    'title' => 'Thesis & Settings',
                    'url' => url('investor/profile.php'),
                    'icon' => 'sliders-horizontal',
                    'active' => ($currentPage === 'profile.php')
                ],
                [
                    'title' => 'Verification Center',
                    'url' => url('investor/verification.php'),
                    'icon' => 'shield-check',
                    'active' => ($currentPage === 'verification.php')
                ],
            ]
        ];
        ?>

        <!-- Navigation Links with Prominent Active Indicators -->
        <div class="space-y-6">
            <?php foreach ($navGroups as $groupTitle => $items): ?>
                <div>
                    <div
                        class="text-[11px] font-bold text-[#667085] dark:text-slate-400 uppercase tracking-wider px-3.5 mb-2">
                        <?= $groupTitle ?>
                    </div>
                    <nav class="space-y-1">
                        <?php foreach ($items as $item): ?>
                            <a href="<?= $item['url'] ?>"
                                class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm transition font-semibold group border <?= $item['active'] ? 'bg-[#EAF2FF] dark:bg-blue-950/70 text-[#123B7A] dark:text-blue-300 font-bold shadow-xs border-[#123B7A]/20 dark:border-blue-700/60' : 'text-[#4B5563] dark:text-slate-300 hover:text-[#111827] dark:hover:text-white hover:bg-[#FAFBFD] dark:hover:bg-slate-800 border-transparent' ?>">
                                <div class="flex items-center space-x-3 min-w-0">
                                    <i data-lucide="<?= $item['icon'] ?>"
                                        class="w-4.5 h-4.5 flex-shrink-0 <?= $item['active'] ? 'text-[#123B7A] dark:text-blue-400' : 'text-[#667085] dark:text-slate-400 group-hover:text-[#111827] dark:group-hover:text-white' ?>"></i>
                                    <span class="truncate"><?= htmlspecialchars($item['title']) ?></span>
                                </div>
                                <?php if ($item['active']): ?>
                                    <span class="w-1.5 h-4 rounded-full bg-[#123B7A] dark:bg-blue-400 flex-shrink-0"
                                        title="Active Page"></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sign Out Footer -->
    <div class="p-4 sm:p-5 border-t border-[#E4E8EF] dark:border-slate-800 bg-[#FAFBFD] dark:bg-slate-850">
        <div class="flex items-center justify-between">
            <a href="<?= url('investor/view.php') ?>" title="View Profile"
                class="flex items-center space-x-2.5 text-sm text-[#0B1F3A] dark:text-slate-200 font-bold hover:text-[#123B7A] dark:hover:text-blue-400 transition truncate">
                <i data-lucide="external-link" class="w-4 h-4 text-[#4B5563] dark:text-slate-400"></i>
                <span class="truncate">Network Presence</span>
            </a>
            <a href="<?= url('auth/logout.php') ?>" title="Sign Out"
                class="p-2 text-[#4B5563] dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </a>
        </div>
    </div>
</aside>
<script>
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('main-sidebar');
        const backdrop = document.getElementById('mobile-sidebar-backdrop');
        if (sidebar) {
            sidebar.classList.toggle('-translate-x-full');
        }
        if (backdrop) {
            backdrop.classList.toggle('hidden');
        }
    }

    // Direct mouse wheel scroll engine for investor sidebar
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