<?php
/**
 * Admin Top Navigation Bar Component
 */
$currentUser = current_user();
$db = get_db();
$pendingKycCount = 0;
if ($db) {
    $kStmt = $db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'");
    $pendingKycCount = (int)$kStmt->fetchColumn();
}
?>
<header class="h-14 border-b border-slate-200 bg-white/95 backdrop-blur-md px-3 sm:px-6 flex items-center justify-between sticky top-0 z-20">
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
    </div>
</header>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>

