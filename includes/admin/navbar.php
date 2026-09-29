<?php
/**
 * Admin Top Navigation Bar Component
 */
$currentUser = current_user();
$db = get_db();
$pendingKycCount = 0;
if ($db) {
    try {
        $kStmt = $db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'");
        $pendingKycCount = (int)$kStmt->fetchColumn();
    } catch (\Throwable $e) {
        $pendingKycCount = 0;
    }
}

// Load Global Admin "Vay Portal" Typography & Legibility Suite
require_once __DIR__ . '/theme.php';
?>
<header id="admin-navbar" class="admin-navbar h-14 border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md px-3 sm:px-6 flex items-center justify-between sticky top-0 z-40 w-full shadow-xs">
    <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
        <!-- Hamburger Menu Button (Mobile & Tablet) -->
        <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 sm:p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex-shrink-0" aria-label="Open sidebar menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <h2 class="text-xs font-bold text-slate-800 tracking-tight flex items-center space-x-2 truncate">
            <span class="truncate"><?= $pageTitle ?? 'Admin & Compliance Center' ?></span>
        </h2>
    </div>

    <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
        <?php if ($pendingKycCount > 0): ?>
            <a href="<?= url('admin/verification_queue.php') ?>" class="flex items-center space-x-1 sm:space-x-1.5 px-2 sm:px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-[10px] sm:text-[11px] font-semibold hover:bg-amber-100 transition">
                <i data-lucide="alert-triangle" class="w-3 h-3 text-amber-600 flex-shrink-0"></i>
                <span><?= $pendingKycCount ?><span class="hidden sm:inline"> KYC Request<?= $pendingKycCount > 1 ? 's' : '' ?> Pending</span></span>
            </a>
        <?php endif; ?>

        <div class="hidden xs:flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-[10.5px] text-emerald-700 font-semibold" title="Session authenticated via Two-Factor Verification">
            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
            <span class="hidden sm:inline">2FA Verified</span>
        </div>

        <div class="hidden md:flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200 text-[10.5px] text-slate-600 font-medium">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            <span>Platform Secure</span>
        </div>

        <!-- Platform Settings Button -->
        <a href="<?= url('admin/settings.php') ?>" 
           class="p-2 sm:p-2.5 rounded-xl <?= (basename($_SERVER['PHP_SELF']) === 'settings.php') ? 'bg-blue-50 text-blue-600 border border-blue-200' : 'bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-200 text-slate-700 hover:text-blue-600' ?> transition flex items-center justify-center shadow-xs group" 
           title="Platform Settings" 
           aria-label="Platform Settings">
            <i data-lucide="settings" class="w-4 h-4 group-hover:rotate-45 transition-transform duration-300"></i>
        </a>

        <!-- Dark / Light Theme Toggle Switcher -->
        <button id="admin-theme-toggle-btn" 
                onclick="toggleAdminTheme()" 
                type="button" 
                class="p-2 sm:p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 transition flex items-center justify-center cursor-pointer shadow-xs group" 
                title="Toggle Dark / Light Theme" 
                aria-label="Toggle Dark / Light Theme">
            <!-- Sun Icon Wrapper (Active in Dark Mode) -->
            <span id="admin-theme-sun-wrap" class="hidden flex items-center justify-center">
                <i data-lucide="sun" class="w-4 h-4 text-amber-400 group-hover:rotate-45 transition-transform duration-300"></i>
            </span>
            <!-- Moon Icon Wrapper (Active in Light Mode) -->
            <span id="admin-theme-moon-wrap" class="flex items-center justify-center">
                <i data-lucide="moon" class="w-4 h-4 text-slate-600 group-hover:-rotate-12 transition-transform duration-300"></i>
            </span>
        </button>
    </div>
</header>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>

