<?php
/**
 * Admin Top Navigation Bar Component
 * Clean, Minimalist Header with Breadcrumbs, Live Status & Concise Actions
 */
$currentUser = current_user();
$db = get_db();
$pendingKycCount = 0;
$pendingFundingCount = 0;
if ($db) {
    try {
        $kStmt = $db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'");
        $pendingKycCount = (int)$kStmt->fetchColumn();

        $fStmt = $db->query("SELECT COUNT(*) FROM funding_rounds WHERE status IN ('SUBMITTED', 'UNDER_REVIEW')");
        $pendingFundingCount = (int)$fStmt->fetchColumn();
    } catch (\Throwable $e) {
        $pendingKycCount = 0;
        $pendingFundingCount = 0;
    }
}
$pendingTotal = $pendingKycCount + $pendingFundingCount;

// Load Global Admin Typography & Legibility Suite
require_once __DIR__ . '/theme.php';
?>
<header id="admin-navbar" class="admin-navbar h-14 border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md px-3 sm:px-6 flex items-center justify-between sticky top-0 z-40 w-full shadow-xs">
    <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
        <!-- Desktop Sidebar Rail Toggle Button -->
        <button type="button" 
                onclick="toggleDesktopSidebar()" 
                id="sidebar-toggle-btn"
                class="hidden lg:flex w-8 h-8 rounded-lg border border-slate-200/80 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-800 text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:border-blue-300 dark:hover:border-blue-700 active:scale-95 transition-all items-center justify-center shadow-2xs" 
                title="Toggle Sidebar Rail (Collapse / Expand)"
                aria-label="Toggle sidebar width">
            <i data-lucide="panel-left" class="w-4 h-4"></i>
        </button>

        <!-- Mobile Drawer Hamburger Menu Button -->
        <button type="button" 
                onclick="toggleMobileSidebar()" 
                class="lg:hidden p-1.5 sm:p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition flex-shrink-0" 
                aria-label="Open sidebar menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <?php if (($currentPage ?? basename($_SERVER['PHP_SELF'])) !== 'dashboard.php'): ?>
        <!-- Clean & Bold Back Icon Button -->
        <button type="button" onclick="adminGoBack(this)"
                id="admin-back-btn"
                title="Go Back" aria-label="Go Back"
                class="flex-shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-50 hover:bg-blue-600 dark:bg-slate-800 dark:hover:bg-blue-600 border border-slate-200/90 dark:border-slate-700 hover:border-blue-600 dark:hover:border-blue-600 text-slate-700 dark:text-slate-200 hover:text-white dark:hover:text-white shadow-xs hover:shadow-md hover:shadow-blue-500/25 transition-all duration-200 flex items-center justify-center group cursor-pointer active:scale-95">
            <i data-lucide="arrow-left" class="w-4.5 h-4.5 group-hover:-translate-x-0.5 transition-transform duration-200"></i>
        </button>
        <?php endif; ?>

        <!-- Clean Breadcrumb -->
        <div class="flex items-center space-x-2 text-xs text-slate-400 min-w-0">
            <!-- <span class="hidden sm:inline font-medium hover:text-slate-600 dark:hover:text-slate-300 transition">Admin</span> -->
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300 dark:text-slate-600 hidden sm:inline flex-shrink-0"></i>
            <h1 class="text-xs font-bold text-slate-900 dark:text-slate-100 tracking-tight truncate">
                <?= $pageTitle ?? 'Overview' ?>
            </h1>
        </div>

        <?php if (function_exists('is_maintenance_mode') && is_maintenance_mode()): ?>
        <a href="<?= url('admin/settings.php#maintenance-card') ?>" 
           class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-amber-500/15 border border-amber-500/40 text-amber-700 dark:text-amber-300 text-[11px] font-bold hover:bg-amber-500/25 transition animate-pulse" 
           title="Platform Maintenance is ACTIVE. Only Admins can access. Click to manage.">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            <span>Maintenance Mode ON</span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Right Header Actions -->
    <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
       
      
        <!-- Email Templates & Logs Button -->
        <a href="<?= url('admin/email_templates.php') ?>" 
           class="p-2 sm:p-2.5 rounded-xl <?= (basename($_SERVER['PHP_SELF']) === 'email_templates.php') ? 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-400 dark:border-blue-800' : 'bg-slate-50 hover:bg-blue-50 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 hover:border-blue-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-blue-600' ?> transition flex items-center justify-center shadow-xs group" 
           title="Email Templates & Logs" 
           aria-label="Email Templates & Logs">
            <i data-lucide="mail" class="w-4 h-4 group-hover:scale-110 transition-transform duration-200"></i>
        </a>

        <!-- Platform Settings Button -->
        <a href="<?= url('admin/settings.php') ?>" 
           class="p-2 sm:p-2.5 rounded-xl <?= (basename($_SERVER['PHP_SELF']) === 'settings.php') ? 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-400 dark:border-blue-800' : 'bg-slate-50 hover:bg-blue-50 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 hover:border-blue-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-blue-600' ?> transition flex items-center justify-center shadow-xs group" 
           title="Platform Settings" 
           aria-label="Platform Settings">
            <i data-lucide="settings" class="w-4 h-4 group-hover:rotate-45 transition-transform duration-300"></i>
        </a>

        <!-- Dark / Light Theme Toggle Switcher -->
        <button id="admin-theme-toggle-btn" 
                onclick="toggleAdminTheme()" 
                type="button" 
                class="p-2 sm:p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 transition flex items-center justify-center cursor-pointer shadow-xs group" 
                title="Toggle Dark / Light Theme" 
                aria-label="Toggle Dark / Light Theme">
            <!-- Sun Icon Wrapper (Active in Dark Mode) -->
            <span id="admin-theme-sun-wrap" class="hidden items-center justify-center">
                <i data-lucide="sun" class="w-4 h-4 text-amber-400 group-hover:rotate-45 transition-transform duration-300"></i>
            </span>
            <!-- Moon Icon Wrapper (Active in Light Mode) -->
            <span id="admin-theme-moon-wrap" class="flex items-center justify-center">
                <i data-lucide="moon" class="w-4 h-4 text-slate-600 dark:text-blue-300 group-hover:-rotate-12 transition-transform duration-300"></i>
            </span>
        </button>

        <!-- Admin Profile Icon & Dropdown Menu -->
        <div class="relative pl-1" id="admin-profile-wrapper">
            <button onclick="toggleAdminProfileMenu()" 
                    type="button" 
                    id="admin-profile-btn"
                    class="flex items-center space-x-2 h-9 sm:h-10 pl-1 pr-2 sm:pr-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-slate-50 dark:hover:bg-slate-800 transition bg-white dark:bg-slate-900 shadow-2xs group cursor-pointer flex-shrink-0" 
                    aria-label="Admin Profile Menu"
                    aria-expanded="false">
                <img src="<?= (!empty($currentUser['avatar_url']) ? htmlspecialchars($currentUser['avatar_url']) : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100') ?>" 
                     alt="<?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?>" 
                     style="width: 28px; height: 28px; min-width: 28px; min-height: 28px; max-width: 28px; max-height: 28px; border-radius: 9999px; object-fit: cover;"
                     class="w-7 h-7 rounded-full object-cover border border-blue-200 dark:border-slate-700 flex-shrink-0">
                <div class="hidden sm:flex flex-col text-left">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-blue-600 transition truncate max-w-[100px] leading-tight">
                        <?= htmlspecialchars(explode(' ', $currentUser['name'] ?? 'Admin')[0]) ?>
                    </span>
                    <span class="text-[9px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider leading-none">Admin</span>
                </div>
                <i data-lucide="chevron-down" id="admin-profile-chevron" class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-200 transition-transform duration-200 flex-shrink-0"></i>
            </button>

            <!-- Admin Dropdown Menu Drawer -->
            <div id="admin-profile-menu" class="hidden absolute right-0 mt-2 w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                <!-- Profile Header -->
                <div class="p-3 border-b border-slate-100 dark:border-slate-800 flex items-center space-x-3 bg-slate-50/80 dark:bg-slate-800/60 rounded-xl mb-1.5">
                    <img src="<?= (!empty($currentUser['avatar_url']) ? htmlspecialchars($currentUser['avatar_url']) : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100') ?>" 
                         alt="<?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?>"
                         style="width: 38px; height: 38px; min-width: 38px; min-height: 38px; max-width: 38px; max-height: 38px; border-radius: 9999px; object-fit: cover;"
                         class="w-9 h-9 rounded-full object-cover border border-blue-200 dark:border-slate-700 flex-shrink-0">
                    <div class="min-w-0 flex-1">
                        <div class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 truncate leading-tight">
                            <?= htmlspecialchars($currentUser['name'] ?? 'Administrator') ?>
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                            <?= htmlspecialchars($currentUser['email'] ?? 'admin@portal.com') ?>
                        </div>
                       
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="py-1 space-y-0.5 text-xs font-semibold">
                    
                    <a href="<?= url('admin/audit_logs.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800 transition">
                        <i data-lucide="history" class="w-4 h-4 text-slate-400"></i>
                        <span>Security Audit Trail</span>
                    </a>
                    <a href="<?= url('index.php') ?>" target="_blank" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800 transition">
                        <i data-lucide="external-link" class="w-4 h-4 text-slate-400"></i>
                        <span>View Public Portal</span>
                    </a>
                </div>

                <!-- Sign Out -->
                <div class="pt-1.5 mt-1 border-t border-slate-100 dark:border-slate-800">
                    <a href="<?= url('auth/logout.php') ?>" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 font-bold text-xs transition">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>
<script>
    function toggleAdminProfileMenu() {
        const menu = document.getElementById('admin-profile-menu');
        const chevron = document.getElementById('admin-profile-chevron');
        const btn = document.getElementById('admin-profile-btn');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.style.transform = 'rotate(180deg)';
            if (btn) btn.setAttribute('aria-expanded', 'true');
        } else {
            menu.classList.add('hidden');
            if (chevron) chevron.style.transform = 'rotate(0deg)';
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    }

    document.addEventListener('click', function(e) {
        const wrapper = document.getElementById('admin-profile-wrapper');
        const menu = document.getElementById('admin-profile-menu');
        const chevron = document.getElementById('admin-profile-chevron');
        const btn = document.getElementById('admin-profile-btn');
        if (wrapper && !wrapper.contains(e.target) && menu && !menu.classList.contains('hidden')) {
            menu.classList.add('hidden');
            if (chevron) chevron.style.transform = 'rotate(0deg)';
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const menu = document.getElementById('admin-profile-menu');
            const chevron = document.getElementById('admin-profile-chevron');
            const btn = document.getElementById('admin-profile-btn');
            if (menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
        }
    });

    function adminGoBack(btn) {
        if (btn) {
            btn.style.transform = 'scale(0.92)';
            setTimeout(() => { btn.style.transform = ''; }, 120);
        }
        setTimeout(() => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '<?= url('admin/dashboard.php') ?>';
            }
        }, 80);
    }
</script>
