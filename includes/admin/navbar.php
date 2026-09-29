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
    } catch (Exception $e) {
        // Fallback gracefully
    }
}
$pendingTotal = $pendingKycCount + $pendingFundingCount;
?>
<header class="h-14 border-b border-slate-200/80 bg-white px-4 sm:px-6 flex items-center justify-between sticky top-0 z-20 select-none">
    <div class="flex items-center space-x-3 min-w-0">
        <!-- Desktop Sidebar Rail Toggle Button -->
        <button type="button" 
                onclick="toggleDesktopSidebar()" 
                class="hidden lg:flex p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition" 
                title="Toggle Sidebar"
                aria-label="Toggle sidebar width">
            <i data-lucide="panel-left" class="w-4 h-4"></i>
        </button>

        <!-- Mobile Drawer Hamburger Menu Button -->
        <button type="button" 
                onclick="toggleMobileSidebar()" 
                class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition flex-shrink-0" 
                aria-label="Open sidebar menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <!-- Clean Breadcrumb -->
        <div class="flex items-center space-x-2 text-xs text-slate-400 min-w-0">
            <span class="hidden sm:inline font-medium hover:text-slate-600 transition">Admin</span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300 hidden sm:inline flex-shrink-0"></i>
            <h1 class="text-xs font-bold text-slate-900 tracking-tight truncate">
                <?= $pageTitle ?? 'Overview' ?>
            </h1>
        </div>
    </div>

    <!-- Right Header Actions -->
    <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
        <!-- Consolidated Action Badge (If pending reviews exist) -->
        <?php if ($pendingTotal > 0): ?>
            <a href="<?= $pendingKycCount > 0 ? url('admin/verification_queue.php') : url('admin/funding_review.php') ?>" 
               class="admin-badge admin-badge-warning hover:bg-amber-100 transition-colors cursor-pointer" 
               title="<?= $pendingKycCount ?> KYC + <?= $pendingFundingCount ?> Funding rounds awaiting review">
                <span class="admin-badge-dot"></span>
                <span><?= $pendingTotal ?> Pending</span>
            </a>
        <?php endif; ?>

        <!-- View Public Portal -->
        <a href="<?= url('index.php') ?>" target="_blank" 
           class="admin-btn-ghost text-xs" 
           title="Preview public portal in new tab">
            <span class="hidden sm:inline">View Portal</span>
            <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
        </a>

        <!-- System Active Status -->
        <div class="hidden xs:flex items-center space-x-1.5 pl-2 border-l border-slate-100 text-xs text-slate-500 font-medium">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span class="hidden md:inline">Online</span>
        </div>
    </div>
</header>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>
