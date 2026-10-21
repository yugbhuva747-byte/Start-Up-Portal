<?php
/**
 * Founder Module: Main Dashboard
 * User-Friendly, Streamlined, Clean & High-Contrast Interface
 * Focused on ease of use, immediate capital actions, and zero-friction sharing.
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Founder Dashboard';

$company = null;
$activeRound = null;
$allRounds = [];
$recentOrders = [];
$recentMessages = [];
$recentNotifs = [];
$pendingDocs = 0;
$pendingInterests = 0;
$stats = [
    'total_raised' => 0,
    'investors_count' => 0,
    'round_progress' => 0,
    'target_amount' => 0,
    'total_rounds' => 0,
    'total_views' => 0,
    'blog_count' => 0,
    'doc_count' => 0
];

if ($db) {
    // 1. Get Company
    $cStmt = $db->prepare("
        SELECT c.* FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ? LIMIT 1
    ");
    $cStmt->execute([$user['id']]);
    $company = $cStmt->fetch();

    if ($company) {
        // 2. All Funding Rounds
        $frStmt = $db->prepare("SELECT * FROM funding_rounds WHERE company_id = ? ORDER BY created_at DESC");
        $frStmt->execute([$company['id']]);
        $allRounds = $frStmt->fetchAll();
        $stats['total_rounds'] = count($allRounds);

        // Active/latest round
        $activeRound = !empty($allRounds) ? $allRounds[0] : null;

        // Total raised across ALL rounds
        foreach ($allRounds as $r) {
            $stats['total_raised'] += (float) $r['amount_raised'];
        }

        if ($activeRound) {
            $stats['target_amount'] = (float) $activeRound['target_amount'];
            $stats['round_progress'] = $activeRound['target_amount'] > 0
                ? min(100, round(($activeRound['amount_raised'] / $activeRound['target_amount']) * 100))
                : 0;

            // 3. Recent Investment Orders for active round
            $ordStmt = $db->prepare("
                SELECT io.*, u.name as investor_name, u.avatar_url, u.email as investor_email,
                       ip.investor_type, ip.experience_years
                FROM investment_orders io
                JOIN users u ON io.investor_user_id = u.id
                LEFT JOIN investor_profiles ip ON u.id = ip.user_id
                WHERE io.funding_round_id = ?
                ORDER BY io.created_at DESC LIMIT 6
            ");
            $ordStmt->execute([$activeRound['id']]);
            $recentOrders = $ordStmt->fetchAll();
        }

        // 4. Total unique investors across all rounds
        $allInvStmt = $db->prepare("
            SELECT COUNT(DISTINCT io.investor_user_id)
            FROM investment_orders io
            JOIN funding_rounds fr ON io.funding_round_id = fr.id
            WHERE fr.company_id = ?
        ");
        $allInvStmt->execute([$company['id']]);
        $stats['investors_count'] = (int) $allInvStmt->fetchColumn();

        // 5. Document count
        $dStmt = $db->prepare("SELECT COUNT(*) FROM company_documents WHERE company_id = ?");
        $dStmt->execute([$company['id']]);
        $stats['doc_count'] = (int) $dStmt->fetchColumn();

        // 6. Blog count
        $blogStmt = $db->prepare("SELECT COUNT(*) FROM company_blogs WHERE company_id = ?");
        $blogStmt->execute([$company['id']]);
        $stats['blog_count'] = (int) $blogStmt->fetchColumn();

        // 7. Pending documents
        $docStmt = $db->prepare("SELECT COUNT(*) FROM verification_documents WHERE user_id = ? AND status = 'pending'");
        $docStmt->execute([$user['id']]);
        $pendingDocs = (int) $docStmt->fetchColumn();

        // 8. Pending investor interests count
        try {
            $intStmt = $db->prepare("SELECT COUNT(*) FROM investor_interests WHERE company_id = ? AND status = 'pending'");
            $intStmt->execute([$company['id']]);
            $pendingInterests = (int) $intStmt->fetchColumn();
        } catch (\Throwable $e) {
            $pendingInterests = 0;
        }
    }

    // 9. Recent messages
    $msgStmt = $db->prepare("
        SELECT m.message_text, m.created_at, u.name as sender_name, u.avatar_url as sender_avatar
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        JOIN users u ON m.sender_user_id = u.id
        WHERE (c.user_one_id = ? OR c.user_two_id = ?) AND m.sender_user_id != ?
        ORDER BY m.created_at DESC LIMIT 4
    ");
    $msgStmt->execute([$user['id'], $user['id'], $user['id']]);
    $recentMessages = $msgStmt->fetchAll();

    // 10. Recent notifications
    $nStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
    $nStmt->execute([$user['id']]);
    $recentNotifs = $nStmt->fetchAll();
}

$progress = get_profile_progress($user['id'], 'founder');
$flash = get_flash();

$flashStyles = [
    'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
    'info' => 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800',
    'error' => 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
];

$compHash = $company ? hash_id_encode($company['id']) : '';
$publicCompUrl = $compHash ? url('company.php?id=' . $compHash) : '';

// Company initials
$compInitials = 'SU';
if ($company && !empty($company['name'])) {
    $cParts = preg_split('/\s+/', trim($company['name']));
    $compInitials = strtoupper(substr($cParts[0], 0, 1) . (isset($cParts[1]) ? substr($cParts[1], 0, 1) : ''));
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
    <style>
        /* Clean card styles with full dark mode contrast */
        .card-clean {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1.25rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            transition: all 0.2s ease;
        }
        .dark .card-clean, html.dark .card-clean {
            background-color: #111827 !important;
            border-color: #1E293B !important;
            color: #F8FAFC !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
        }
        .card-clean:hover {
            border-color: #CBD5E1;
        }
        .dark .card-clean:hover {
            border-color: #334155 !important;
        }
    </style>
</head>

<body class="bg-[#F8FAFC] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navigation -->
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="dashboard-content">

            <!-- Flash notification -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-semibold border <?= $flashStyles[$flash['type']] ?? $flashStyles['info'] ?> flex items-center space-x-3 shadow-xs">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Streamlined Smart Notification Strip -->
            <?php if (!$progress['is_complete'] || $pendingInterests > 0): ?>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3.5 sm:p-4 rounded-2xl bg-gradient-to-r from-blue-50 via-indigo-50 to-slate-50 dark:from-indigo-950/40 dark:via-slate-900 dark:to-slate-900 border border-indigo-200/80 dark:border-indigo-900/50 shadow-2xs">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 animate-pulse flex-shrink-0"></span>
                        <div class="text-xs sm:text-sm text-slate-700 dark:text-slate-200 truncate">
                            <?php if ($pendingInterests > 0): ?>
                                <strong><?= $pendingInterests ?> Investor Interest<?= $pendingInterests > 1 ? 's' : '' ?></strong> received for your startup!
                            <?php else: ?>
                                Profile Readiness: <strong><?= (int)$progress['percentage'] ?>%</strong> &mdash; Complete KYC verification to unlock all institutional allocators.
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 flex-shrink-0 self-end sm:self-auto">
                        <?php if ($pendingInterests > 0): ?>
                            <a href="<?= url('founder/investor_interests.php') ?>" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-xs flex items-center gap-1">
                                <span>Review Interests</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?= url('founder/verification.php') ?>" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-xs flex items-center gap-1">
                                <span>Verify Now</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Friendly Welcome Hero Header -->
            <div class="card-clean p-6 sm:p-8 relative overflow-hidden bg-gradient-to-br from-white via-slate-50/50 to-indigo-50/20 dark:from-[#111827] dark:via-[#111827] dark:to-indigo-950/20">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    
                    <!-- Left: Greetings & Status -->
                    <div class="flex items-start sm:items-center gap-4 min-w-0 flex-1">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-violet-600 text-white flex items-center justify-center font-black text-xl shadow-md shadow-blue-500/20 flex-shrink-0">
                            <?= htmlspecialchars($compInitials) ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                    Hello, <?= htmlspecialchars(explode(' ', trim($user['name']))[0]) ?>!
                                </h1>
                                <?php if ($company): ?>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        <?= htmlspecialchars($company['name']) ?>
                                    </span>
                                <?php endif; ?>
                                <?= $user['is_verified'] ? '<span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">KYC Verified</span>' : '' ?>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium leading-relaxed max-w-xl">
                                Welcome to your command center. Track live investor interest, update your company data room, and share your public profile with zero login friction.
                            </p>
                        </div>
                    </div>

                    <!-- Right: Clear, Primary Actions -->
                    <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto flex-shrink-0">
                        <?php if ($company): ?>
                            <button type="button" onclick="openShareModal('<?= htmlspecialchars($publicCompUrl) ?>', '<?= htmlspecialchars(addslashes($company['name'])) ?>')"
                                class="flex-1 sm:flex-initial justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs sm:text-sm font-bold shadow-sm shadow-blue-500/25 transition flex items-center space-x-2">
                                <i data-lucide="share-2" class="w-4 h-4"></i>
                                <span>Share Company Link</span>
                            </button>
                        <?php endif; ?>

                        <a href="<?= url('founder/company.php') ?>"
                            class="flex-1 sm:flex-initial justify-center px-4 py-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs sm:text-sm font-bold shadow-2xs transition flex items-center space-x-2">
                            <i data-lucide="folder-lock" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                            <span>Data Room</span>
                        </a>

                        <a href="<?= url('founder/funding_rounds.php?action=new') ?>"
                            class="flex-1 sm:flex-initial justify-center px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-bold shadow-sm transition flex items-center space-x-1.5">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Launch Round</span>
                        </a>
                    </div>

                </div>
            </div>

            <!-- 4 Clean, Breathing KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" id="stats-grid">
                
                <!-- 1. Total Raised -->
                <div class="card-clean p-5 sm:p-6 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Raised</span>
                        <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                            <?= format_inr($stats['total_raised']) ?>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">
                            Across <?= $stats['total_rounds'] ?> round<?= $stats['total_rounds'] !== 1 ? 's' : '' ?>
                        </div>
                    </div>
                </div>

                <!-- 2. Active Backers -->
                <div class="card-clean p-5 sm:p-6 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Active Investors</span>
                        <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                            <?= (int) $stats['investors_count'] ?>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">
                            Committed allocators
                        </div>
                    </div>
                </div>

                <!-- 3. Round Status -->
                <div class="card-clean p-5 sm:p-6 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Funding Stage</span>
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-900 dark:text-white truncate">
                            <?= $activeRound ? htmlspecialchars($activeRound['round_name']) : 'Privately Held' ?>
                        </div>
                        <div class="mt-1">
                            <?= $activeRound ? render_status_badge($activeRound['status']) : '<span class="text-xs text-slate-500 font-medium">Ready to raise</span>' ?>
                        </div>
                    </div>
                </div>

                <!-- 4. Data Room & Share Status -->
                <div class="card-clean p-5 sm:p-6 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Public Dossier</span>
                        <div class="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400">
                            <i data-lucide="file-check-2" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                            <?= $stats['doc_count'] ?> Files
                        </div>
                        <div class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1 flex items-center gap-1">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            <span>Zero-Login Link Active</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Active Round Live Progress Bar (High-End Fintech View) -->
            <?php if ($activeRound): ?>
                <div class="card-clean p-6 sm:p-7">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars($activeRound['round_name']) ?>
                                </h2>
                                <?= render_status_badge($activeRound['status']) ?>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mt-1">
                                Valuation: <strong class="text-slate-900 dark:text-white"><?= format_inr($activeRound['valuation']) ?></strong> &bull; Equity: <strong class="text-slate-900 dark:text-white"><?= htmlspecialchars($activeRound['equity_offered']) ?>%</strong>
                            </p>
                        </div>

                        <div class="text-left md:text-right">
                            <span class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400">
                                <?= $stats['round_progress'] ?>%
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 block font-medium">
                                of <?= format_inr($activeRound['target_amount']) ?> target
                            </span>
                        </div>
                    </div>

                    <!-- Modern Smooth Progress Bar -->
                    <div class="w-full h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 mb-3.5">
                        <div class="h-full rounded-full transition-all duration-1000 bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-500"
                            style="width: <?= $stats['round_progress'] ?>%"></div>
                    </div>

                    <!-- 3 Clean Metrics in Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs sm:text-sm">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 block text-[11px] font-bold uppercase tracking-wider">Raised So Far</span>
                            <strong class="text-slate-900 dark:text-white font-bold text-sm sm:text-base"><?= format_inr($activeRound['amount_raised']) ?></strong>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 block text-[11px] font-bold uppercase tracking-wider">Minimum Cheque</span>
                            <strong class="text-slate-900 dark:text-white font-bold text-sm sm:text-base"><?= format_inr($activeRound['min_investment']) ?></strong>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 block text-[11px] font-bold uppercase tracking-wider">Remaining to Close</span>
                            <strong class="text-emerald-600 dark:text-emerald-400 font-bold text-sm sm:text-base"><?= format_inr(max(0, $activeRound['target_amount'] - $activeRound['amount_raised'])) ?></strong>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-clean p-6 sm:p-8 text-center flex flex-col items-center justify-center">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-3">
                        <i data-lucide="circle-dollar-sign" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Ready to Raise Capital?</h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                        Launch your verified fundraising round to display real-time commitment progress and receive term sheets from active angels &amp; VCs.
                    </p>
                    <a href="<?= url('founder/funding_rounds.php?action=new') ?>"
                        class="inline-flex items-center space-x-2 mt-4 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-bold shadow-sm transition">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Create Fundraising Round</span>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Balanced 2-Column Section: Main Feed + Side Tools -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left Column (2 Cols): Investor Activity Feed -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <div class="card-clean p-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                                <i data-lucide="handshake" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                <span>Recent Investor Activity</span>
                            </h2>
                            <a href="<?= url('founder/funding_rounds.php') ?>" class="text-xs sm:text-sm text-indigo-600 hover:text-indigo-700 font-semibold flex items-center gap-1">
                                <span>All Rounds</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>

                        <?php if (empty($recentOrders)): ?>
                            <div class="py-12 text-center text-slate-400">
                                <i data-lucide="inbox" class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-2.5"></i>
                                <div class="font-bold text-slate-700 dark:text-slate-300 text-sm mb-1">No commitments yet</div>
                                <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                                    Share your company dossier link with prospective backers to start receiving commitments here.
                                </div>
                                <?php if ($company): ?>
                                    <button type="button" onclick="openShareModal('<?= htmlspecialchars($publicCompUrl) ?>', '<?= htmlspecialchars(addslashes($company['name'])) ?>')" class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition inline-flex items-center gap-1.5 shadow-xs">
                                        <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                                        <span>Share Company Link Now</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 dark:border-slate-700 text-slate-500 uppercase tracking-wider text-xs">
                                            <th class="pb-3 font-semibold">Investor</th>
                                            <th class="pb-3 font-semibold">Type</th>
                                            <th class="pb-3 font-semibold">Amount</th>
                                            <th class="pb-3 font-semibold">Status</th>
                                            <th class="pb-3 font-semibold">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <?php foreach ($recentOrders as $ord): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                                <td class="py-3.5 pr-2">
                                                    <a href="<?= url('investor/view.php?id=' . encode_id($ord['investor_user_id'])) ?>"
                                                        class="flex items-center space-x-2.5 hover:text-indigo-600 transition group"
                                                        title="View Investor Profile">
                                                        <img src="<?= htmlspecialchars($ord['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80') ?>"
                                                            alt=""
                                                            class="w-8 h-8 rounded-full object-cover border border-slate-200 group-hover:ring-2 group-hover:ring-indigo-500 transition">
                                                        <div>
                                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-sm group-hover:text-indigo-600 transition block">
                                                                <?= htmlspecialchars($ord['investor_name']) ?>
                                                            </span>
                                                            <?php if (!empty($ord['experience_years'])): ?>
                                                                <span class="text-xs text-slate-400 block"><?= (int) $ord['experience_years'] ?>+ yrs exp.</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td class="py-3.5 pr-2 text-slate-600 dark:text-slate-300 font-medium text-xs sm:text-sm">
                                                    <?= htmlspecialchars($ord['investor_type'] ?? 'Angel') ?>
                                                </td>
                                                <td class="py-3.5 pr-2 font-bold text-slate-900 dark:text-white text-sm sm:text-base">
                                                    <?= format_inr($ord['amount']) ?>
                                                </td>
                                                <td class="py-3.5 pr-2"><?= render_status_badge($ord['status']) ?></td>
                                                <td class="py-3.5 text-slate-500 text-xs sm:text-sm font-medium whitespace-nowrap">
                                                    <?= date('d M Y', strtotime($ord['created_at'])) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Platform Announcements & Regulatory Safeguards -->
                    <?php include __DIR__ . '/../includes/announcement_banner.php'; ?>

                </div>

                <!-- Right Column (1 Col): Direct Share Box + Quick Actions + Messages -->
                <div class="space-y-6">

                    <!-- 1. Dedicated Fast Share Box -->
                    <?php if ($company): ?>
                        <div class="card-clean p-5 sm:p-6 bg-gradient-to-br from-blue-50/60 to-indigo-50/40 dark:from-blue-950/20 dark:to-indigo-950/20 border-blue-200/80 dark:border-blue-900/50">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="p-1.5 rounded-lg bg-blue-600 text-white">
                                    <i data-lucide="share-2" class="w-4 h-4"></i>
                                </div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-blue-950 dark:text-blue-300">
                                    Zero-Login Share Link
                                </h3>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-medium mb-3">
                                Share your company dossier, deck &amp; metrics with team, hires, or investors &mdash; no login needed.
                            </p>
                            
                            <div class="flex items-center gap-2">
                                <input type="text" id="fastShareInput" readonly value="<?= htmlspecialchars($publicCompUrl) ?>" class="w-full px-3 py-2 text-xs font-mono bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-300 select-all outline-none">
                                <button type="button" onclick="copyFastShareLink(this)" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition flex-shrink-0 shadow-xs">
                                    Copy
                                </button>
                            </div>

                            <div class="mt-3 pt-3 border-t border-blue-200/60 dark:border-blue-900/40 flex items-center justify-between">
                                <a href="https://api.whatsapp.com/send?text=<?= urlencode('View ' . $company['name'] . ' dossier & data room: ' . $publicCompUrl) ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-emerald-700 dark:text-emerald-400 hover:underline flex items-center gap-1">
                                    <span>Share via WhatsApp</span>
                                    <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                                </a>
                                <a href="<?= $publicCompUrl ?>" target="_blank" class="text-xs font-bold text-blue-700 dark:text-blue-400 hover:underline flex items-center gap-1">
                                    <span>Preview</span>
                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 2. Fast Navigation Tools -->
                    <div class="card-clean p-5 sm:p-6 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                            <i data-lucide="compass" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                            <span>Quick Founder Tools</span>
                        </h3>
                        
                        <div class="space-y-2 text-xs sm:text-sm font-semibold">
                            <a href="<?= url('founder/company.php') ?>" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-750 text-slate-800 dark:text-slate-200 flex items-center justify-between transition group">
                                <div class="flex items-center gap-2.5">
                                    <i data-lucide="folder-lock" class="w-4 h-4 text-blue-600"></i>
                                    <span>Manage Data Room (<?= $stats['doc_count'] ?>)</span>
                                </div>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition"></i>
                            </a>

                            <a href="<?= url('founder/cap_table.php') ?>" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-750 text-slate-800 dark:text-slate-200 flex items-center justify-between transition group">
                                <div class="flex items-center gap-2.5">
                                    <i data-lucide="pie-chart" class="w-4 h-4 text-indigo-600"></i>
                                    <span>Equity &amp; Cap Table</span>
                                </div>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition"></i>
                            </a>

                            <a href="<?= url('founder/updates.php') ?>" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-750 text-slate-800 dark:text-slate-200 flex items-center justify-between transition group">
                                <div class="flex items-center gap-2.5">
                                    <i data-lucide="rss" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Post Investor Update</span>
                                </div>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition"></i>
                            </a>

                            <a href="<?= url('founder/profile.php') ?>" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-750 text-slate-800 dark:text-slate-200 flex items-center justify-between transition group">
                                <div class="flex items-center gap-2.5">
                                    <i data-lucide="user-cog" class="w-4 h-4 text-slate-500"></i>
                                    <span>Profile &amp; Security Settings</span>
                                </div>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition"></i>
                            </a>
                        </div>
                    </div>

                    <!-- 3. Direct Messages -->
                    <div class="card-clean p-5 sm:p-6">
                        <div class="flex items-center justify-between mb-3.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                                <i data-lucide="message-circle" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                <span>Recent Messages</span>
                            </h3>
                            <a href="<?= url('founder/messages.php') ?>" class="text-indigo-600 dark:text-indigo-400 font-semibold text-xs hover:underline">All &rarr;</a>
                        </div>

                        <?php if (empty($recentMessages)): ?>
                            <p class="text-xs text-slate-400 py-4 text-center">No unread messages</p>
                        <?php else: ?>
                            <div class="space-y-2.5">
                                <?php foreach ($recentMessages as $msg): ?>
                                    <a href="<?= url('founder/messages.php') ?>" class="flex items-start space-x-3 p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <img src="<?= htmlspecialchars($msg['sender_avatar'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80') ?>"
                                            alt=""
                                            class="w-8 h-8 rounded-full object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0 mt-0.5">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200 truncate">
                                                <?= htmlspecialchars($msg['sender_name']) ?>
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                                <?= htmlspecialchars(mb_substr($msg['message_text'], 0, 50)) ?>
                                            </div>
                                        </div>
                                        <span class="text-[11px] text-slate-400 flex-shrink-0"><?= date('d M', strtotime($msg['created_at'])) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>

        </main>
    </div>

    <script>
        function refreshIcons() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
        refreshIcons();
        document.addEventListener('DOMContentLoaded', refreshIcons);
        window.addEventListener('load', refreshIcons);

        function copyFastShareLink(btn) {
            const input = document.getElementById('fastShareInput');
            if (!input) return;
            input.select();
            input.setSelectionRange(0, 99999);
            const originalText = btn.innerText;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(() => {
                    btn.innerText = 'Copied!';
                    setTimeout(() => { btn.innerText = originalText; }, 2000);
                }).catch(() => {
                    document.execCommand('copy');
                    btn.innerText = 'Copied!';
                    setTimeout(() => { btn.innerText = originalText; }, 2000);
                });
            } else {
                document.execCommand('copy');
                btn.innerText = 'Copied!';
                setTimeout(() => { btn.innerText = originalText; }, 2000);
            }
        }
    </script>
    <?php include __DIR__ . '/../includes/share_company_modal.php'; ?>
</body>

</html>