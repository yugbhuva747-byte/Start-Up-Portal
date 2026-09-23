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
<header class="h-14 border-b border-slate-200 bg-white/95 backdrop-blur-md px-6 flex items-center justify-between sticky top-0 z-20">
    <div class="flex items-center space-x-3">
        <h2 class="text-xs font-bold text-slate-800 tracking-tight flex items-center space-x-2">
            <span><?= $pageTitle ?? 'Admin & Compliance Center' ?></span>
        </h2>
    </div>

    <div class="flex items-center space-x-3">
        <?php if ($pendingKycCount > 0): ?>
            <a href="<?= url('admin/verification_queue.php') ?>" class="flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-[11px] font-semibold hover:bg-amber-100 transition">
                <i data-lucide="alert-triangle" class="w-3 h-3 text-amber-600"></i>
                <span><?= $pendingKycCount ?> KYC Request<?= $pendingKycCount > 1 ? 's' : '' ?> Pending</span>
            </a>
        <?php endif; ?>

        <div class="flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-[10.5px] text-emerald-700 font-semibold" title="Session authenticated via Two-Factor Verification">
            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
            <span>2FA Verified</span>
        </div>

        <div class="flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200 text-[10.5px] text-slate-600 font-medium">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            <span>Platform Secure</span>
        </div>
    </div>
</header>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>

