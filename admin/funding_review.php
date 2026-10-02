<?php
/**
 * Admin Module: Funding Round Review & Approvals
 * Premium Dealflow Approvals & Compliance Desk
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Funding Round Approvals';

$rounds = [];
$error = '';
$flash = get_flash();

$totalRounds = 0;
$pendingReviewCount = 0;
$liveCount = 0;
$totalTargetCapital = 0;
$totalRaisedCapital = 0;

// Handle Status Change POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $roundId = (int)($_POST['round_id'] ?? 0);
        $newStatus = trim($_POST['new_status'] ?? '');
        $reviewNotes = trim($_POST['review_notes'] ?? '');

        if ($roundId > 0 && in_array($newStatus, ['LIVE', 'APPROVED', 'UNDER_REVIEW', 'REJECTED', 'CLOSED'])) {
            try {
                $db->prepare("UPDATE funding_rounds SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $roundId]);
                log_audit($user['id'], 'UPDATE_ROUND_STATUS', 'funding_rounds', $roundId, "Admin updated round status to {$newStatus}. Notes: {$reviewNotes}");

                // Automatically dispatch status update email to registered founders
                send_funding_round_status_email($db, $roundId, $newStatus);

                // Also send in-app notification to founder
                $fStmt = $db->prepare("
                    SELECT cf.user_id, c.name as company_name, fr.round_name 
                    FROM funding_rounds fr 
                    JOIN companies c ON fr.company_id = c.id 
                    LEFT JOIN company_founders cf ON c.id = cf.company_id 
                    WHERE fr.id = ?
                ");
                $fStmt->execute([$roundId]);
                $fData = $fStmt->fetch();

                if ($fData && !empty($fData['user_id'])) {
                    $notifType = ($newStatus === 'LIVE' || $newStatus === 'APPROVED') ? 'success' : (($newStatus === 'REJECTED') ? 'error' : 'info');
                    $notifMsg = "Your funding round '{$fData['round_name']}' has been updated to {$newStatus} by Compliance.";
                    if (!empty($reviewNotes)) {
                        $notifMsg .= " Remarks: " . $reviewNotes;
                    }
                    send_notification($fData['user_id'], "Funding Round " . ucfirst(strtolower($newStatus)), $notifMsg, $notifType, 'founder/funding.php');
                }

                set_flash('success', "Funding round successfully updated to {$newStatus}. Automatic email & in-app alerts dispatched to founder.");
                header('Location: ' . url('admin/funding_review.php'));
                exit;
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all funding rounds with company, founder & investment stats
if ($db) {
    try {
        $stmt = $db->query("
            SELECT fr.*, 
                   c.name as company_name, c.cin_number, c.industry, c.stage, c.city, c.state, c.website as website_url, c.website, c.logo_url,
                   u.id as founder_user_id, u.name as founder_name, u.email as founder_email, u.phone as founder_phone,
                   COALESCE(inv.investor_count, 0) as investor_count,
                   COALESCE(inv.actual_invested, 0) as actual_invested
            FROM funding_rounds fr
            JOIN companies c ON fr.company_id = c.id
            LEFT JOIN company_founders cf ON c.id = cf.company_id
            LEFT JOIN users u ON cf.user_id = u.id
            LEFT JOIN (
                SELECT funding_round_id, 
                       COUNT(DISTINCT investor_user_id) as investor_count, 
                       SUM(amount_invested) as actual_invested
                FROM investments 
                WHERE allotment_status != 'cancelled' 
                GROUP BY funding_round_id
            ) inv ON inv.funding_round_id = fr.id
            ORDER BY 
                CASE 
                    WHEN fr.status IN ('SUBMITTED', 'UNDER_REVIEW') THEN 1 
                    WHEN fr.status IN ('APPROVED', 'LIVE') THEN 2 
                    WHEN fr.status IN ('PARTIALLY_FUNDED', 'FULLY_FUNDED') THEN 3 
                    ELSE 4 
                END, 
                fr.created_at DESC
        ");
        $rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rounds as $r) {
            $totalRounds++;
            $totalTargetCapital += (float)$r['target_amount'];
            $raised = max((float)$r['amount_raised'], (float)$r['actual_invested']);
            $totalRaisedCapital += $raised;

            if (in_array($r['status'], ['SUBMITTED', 'UNDER_REVIEW'])) {
                $pendingReviewCount++;
            } elseif (in_array($r['status'], ['LIVE', 'APPROVED', 'PARTIALLY_FUNDED'])) {
                $liveCount++;
            }
        }
    } catch (Exception $e) {
        $error = 'Failed to load funding rounds: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>

    <?php include __DIR__ . '/../includes/admin/head.php'; ?>

    <style>
        .stat-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
        }
        .dark .stat-card-clean, html.dark .stat-card-clean { background: #111827 !important; border-color: #1e293b !important; }
        .filter-bar-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 0.875rem 1.25rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        }
        .dark .filter-bar-clean, html.dark .filter-bar-clean { background: #111827 !important; border-color: #1e293b !important; }
        .table-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }
        .dark .table-card-clean, html.dark .table-card-clean { background: #111827 !important; border-color: #1e293b !important; }
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 flex min-h-screen dark:bg-[#0b0f19] dark:text-slate-100 font-sans antialiased">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6" id="funding-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-sm font-medium border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800' ?> flex items-center space-x-3 shadow-sm">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-4 rounded-xl text-sm font-medium border bg-rose-50 text-rose-800 border-rose-200 flex items-center space-x-3 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-950/50 border border-sky-200/80 dark:border-sky-800/80 flex items-center justify-center text-sky-600 dark:text-sky-400 shadow-sm flex-shrink-0">
                        <i data-lucide="trending-up" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Funding Round Approvals
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            Verify company valuations, review dilution equity terms, and approve campaigns for investor syndication.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <button onclick="window.location.reload()" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Refresh Deals</span>
                    </button>
                    <a href="<?= url('admin/companies.php') ?>" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                        <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
                        <span>Startups Directory</span>
                    </a>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Rounds</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-2"><?= number_format($totalRounds) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                        <span>Across active startups</span>
                    </div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800 <?= $pendingReviewCount > 0 ? 'ring-1 ring-amber-400/50 dark:ring-amber-500/30' : '' ?>">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Needs Review</span>
                        <div class="w-8 h-8 rounded-lg <?= $pendingReviewCount > 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' ?> flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold <?= $pendingReviewCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' ?> mt-2 flex items-center gap-2">
                        <?= number_format($pendingReviewCount) ?>
                        <?php if ($pendingReviewCount > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 animate-pulse">Action Required</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pending admin approval</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Live On Portal</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2"><?= number_format($liveCount) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Available for syndication</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Target Capital</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="indian-rupee" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-2"><?= format_inr($totalTargetCapital) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Raised: <?= format_inr($totalRaisedCapital) ?></div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="filter-bar-clean dark:bg-slate-900 dark:border-slate-800 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                <div class="flex-1 flex flex-col sm:flex-row items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-80">
                        <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="round-search" oninput="filterRoundsTable()" 
                               placeholder="Search startup, founder, round, CIN..." 
                               class="w-full pl-9 pr-3.5 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition">
                    </div>

                    <!-- Stage Filter -->
                    <select id="stage-filter" onchange="filterRoundsTable()" class="w-full sm:w-auto px-3 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <option value="">All Stages</option>
                        <option value="Idea">Idea</option>
                        <option value="Seed">Seed</option>
                        <option value="Pre-Series A">Pre-Series A</option>
                        <option value="Series A">Series A</option>
                        <option value="Growth">Growth</option>
                    </select>
                </div>

                <!-- Status Filter Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                    <button type="button" onclick="setRoundFilter('ALL')" id="filter-btn-ALL" 
                            class="round-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white shadow-sm transition">
                        All (<?= $totalRounds ?>)
                    </button>
                    <button type="button" onclick="setRoundFilter('PENDING')" id="filter-btn-PENDING" 
                            class="round-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Needs Review (<?= $pendingReviewCount ?>)
                    </button>
                    <button type="button" onclick="setRoundFilter('LIVE')" id="filter-btn-LIVE" 
                            class="round-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Live Deals (<?= $liveCount ?>)
                    </button>
                    <button type="button" onclick="setRoundFilter('CLOSED')" id="filter-btn-CLOSED" 
                            class="round-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Closed / Inactive
                    </button>
                </div>
            </div>

            <!-- Rounds Master Table -->
            <div class="table-card-clean dark:bg-slate-900 dark:border-slate-800">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700/80 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                                <th class="py-3.5 px-4">Startup & Industry</th>
                                <th class="py-3.5 px-4">Founder / Lead</th>
                                <th class="py-3.5 px-4">Round Name</th>
                                <th class="py-3.5 px-4">Target Capital & Progress</th>
                                <th class="py-3.5 px-4">Valuation (Pre-Money)</th>
                                <th class="py-3.5 px-4">Equity Offered</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rounds-tbody" class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if (empty($rounds)): ?>
                                <tr>
                                    <td colspan="8" class="py-16 text-center text-slate-400 dark:text-slate-500">
                                        <i data-lucide="layers" class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2"></i>
                                        <p class="font-medium text-sm">No funding rounds registered yet.</p>
                                        <p class="text-xs mt-0.5">Founders will submit rounds for review through their company workspace.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rounds as $r): 
                                    $target = (float)$r['target_amount'];
                                    $raised = max((float)$r['amount_raised'], (float)$r['actual_invested']);
                                    $percent = $target > 0 ? min(100, round(($raised / $target) * 100, 1)) : 0;
                                    $isPending = in_array($r['status'], ['SUBMITTED', 'UNDER_REVIEW']);
                                    $isLive = in_array($r['status'], ['LIVE', 'APPROVED', 'PARTIALLY_FUNDED']);
                                    $isClosed = in_array($r['status'], ['CLOSED', 'FULLY_FUNDED', 'REJECTED']);

                                    // JSON data for Modal
                                    $roundJson = htmlspecialchars(json_encode([
                                        'id' => $r['id'],
                                        'round_name' => $r['round_name'],
                                        'company_name' => $r['company_name'],
                                        'cin_number' => $r['cin_number'] ?? 'N/A',
                                        'industry' => $r['industry'] ?? 'N/A',
                                        'stage' => $r['stage'] ?? 'Seed',
                                        'city' => $r['city'] ?? '',
                                        'state' => $r['state'] ?? '',
                                        'founder_name' => $r['founder_name'] ?? 'Founder',
                                        'founder_email' => $r['founder_email'] ?? 'N/A',
                                        'founder_phone' => $r['founder_phone'] ?? 'N/A',
                                        'target_amount' => format_inr($r['target_amount']),
                                        'amount_raised' => format_inr($raised),
                                        'valuation' => format_inr($r['valuation']),
                                        'equity_offered' => $r['equity_offered'] . '%',
                                        'min_investment' => format_inr($r['min_investment']),
                                        'status' => $r['status'],
                                        'purpose' => $r['purpose'] ?? 'General working capital & operational runway scaling.',
                                        'start_date' => $r['start_date'] ? date('M d, Y', strtotime($r['start_date'])) : 'Not set',
                                        'end_date' => $r['end_date'] ? date('M d, Y', strtotime($r['end_date'])) : 'Open until filled',
                                        'investor_count' => (int)$r['investor_count'],
                                    ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr class="round-row hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                                        data-status="<?= htmlspecialchars($r['status']) ?>"
                                        data-stage="<?= htmlspecialchars($r['stage'] ?? '') ?>"
                                        data-is-pending="<?= $isPending ? 'true' : 'false' ?>"
                                        data-is-live="<?= $isLive ? 'true' : 'false' ?>"
                                        data-is-closed="<?= $isClosed ? 'true' : 'false' ?>"
                                        data-search="<?= strtolower(htmlspecialchars($r['company_name'] . ' ' . $r['round_name'] . ' ' . ($r['founder_name'] ?? '') . ' ' . ($r['cin_number'] ?? '') . ' ' . ($r['industry'] ?? ''))) ?>">
                                        
                                        <!-- Startup & Industry -->
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center text-xs border border-indigo-100 dark:border-indigo-800/70 flex-shrink-0 shadow-xs">
                                                    <?= strtoupper(substr($r['company_name'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                                        <span><?= htmlspecialchars($r['company_name']) ?></span>
                                                        <?php if (!empty($r['stage'])): ?>
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                                                <?= htmlspecialchars($r['stage']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
                                                        <span><?= htmlspecialchars($r['industry'] ?? 'Enterprise') ?></span>
                                                        <?php if (!empty($r['cin_number'])): ?>
                                                            <span class="text-slate-300 dark:text-slate-600">•</span>
                                                            <span class="font-mono text-[10px] text-slate-400"><?= htmlspecialchars($r['cin_number']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Founder / Lead -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <div class="font-medium text-slate-800 dark:text-slate-200">
                                                <?= htmlspecialchars($r['founder_name'] ?? 'Founder') ?>
                                            </div>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                <?= htmlspecialchars($r['founder_email'] ?? 'registered founder') ?>
                                            </div>
                                        </td>

                                        <!-- Round Name -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <span class="font-semibold text-slate-900 dark:text-white">
                                                <?= htmlspecialchars($r['round_name']) ?>
                                            </span>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                Min: <?= format_inr($r['min_investment']) ?>
                                            </div>
                                        </td>

                                        <!-- Target Capital & Progress -->
                                        <td class="py-3.5 px-4 min-w-[160px]">
                                            <div class="flex items-center justify-between text-xs mb-1">
                                                <span class="font-bold text-slate-900 dark:text-white"><?= format_inr($target) ?></span>
                                                <span class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400"><?= $percent ?>%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-500" style="width: <?= $percent ?>%"></div>
                                            </div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 flex items-center justify-between">
                                                <span>Raised: <?= format_inr($raised) ?></span>
                                                <span><?= (int)$r['investor_count'] ?> investors</span>
                                            </div>
                                        </td>

                                        <!-- Pre-Money Valuation -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <div class="font-semibold text-slate-800 dark:text-slate-200">
                                                <?= format_inr($r['valuation']) ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400">Pre-money cap</div>
                                        </td>

                                        <!-- Equity Offered -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                                <?= $r['equity_offered'] ?>%
                                            </span>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <?php if ($r['status'] === 'LIVE'): ?>
                                                <span class="badge-status bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    LIVE
                                                </span>
                                            <?php elseif (in_array($r['status'], ['SUBMITTED', 'UNDER_REVIEW'])): ?>
                                                <span class="badge-status bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    <?= htmlspecialchars(str_replace('_', ' ', $r['status'])) ?>
                                                </span>
                                            <?php elseif ($r['status'] === 'APPROVED'): ?>
                                                <span class="badge-status bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                                    APPROVED
                                                </span>
                                            <?php elseif ($r['status'] === 'REJECTED'): ?>
                                                <span class="badge-status bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                    REJECTED
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-status bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                                    <?= htmlspecialchars(str_replace('_', ' ', $r['status'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5">
                                                <button type="button" 
                                                        onclick="openReviewModal(<?= $roundJson ?>)" 
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-2xs">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-500"></i>
                                                    <span>Review</span>
                                                </button>

                                                <?php if ($isPending): ?>
                                                    <form action="<?= url('admin/funding_review.php') ?>" method="POST" class="inline" onsubmit="return confirm('Approve this funding round and publish live to accredited investors?');">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="round_id" value="<?= $r['id'] ?>">
                                                        <input type="hidden" name="new_status" value="LIVE">
                                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-2xs">
                                                            <i data-lucide="check" class="w-3 h-3"></i>
                                                            <span>Approve</span>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Deal Review & Decision Modal -->
    <div id="review-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeReviewModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200 dark:border-slate-800">
                <form action="<?= url('admin/funding_review.php') ?>" method="POST" id="modal-review-form">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="round_id" id="modal-round-id" value="">

                    <!-- Modal Header -->
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                <i data-lucide="file-text" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white" id="modal-round-name">
                                    Round Term Review
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400" id="modal-company-name">Company Name</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeReviewModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-5 space-y-5 text-xs max-h-[75vh] overflow-y-auto">
                        
                        <!-- Financial Summary Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 dark:bg-slate-800/60 p-4 rounded-xl border border-slate-200/70 dark:border-slate-700/60">
                            <div>
                                <span class="text-slate-400 text-[10px] uppercase font-bold">Target Ask</span>
                                <div class="text-sm font-bold text-slate-900 dark:text-white mt-0.5" id="modal-target-amount">₹0</div>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] uppercase font-bold">Valuation</span>
                                <div class="text-sm font-bold text-slate-900 dark:text-white mt-0.5" id="modal-valuation">₹0</div>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] uppercase font-bold">Equity Offered</span>
                                <div class="text-sm font-bold text-indigo-600 dark:text-indigo-400 mt-0.5" id="modal-equity">0%</div>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] uppercase font-bold">Min Ticket</span>
                                <div class="text-sm font-bold text-slate-900 dark:text-white mt-0.5" id="modal-min-ticket">₹0</div>
                            </div>
                        </div>

                        <!-- Company & Diligence Metadata -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 space-y-2">
                                <h4 class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <i data-lucide="building" class="w-3.5 h-3.5 text-slate-400"></i>
                                    Corporate Credentials
                                </h4>
                                <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                                    <span class="text-slate-400">CIN Number:</span>
                                    <span class="font-mono font-medium text-slate-700 dark:text-slate-300" id="modal-cin">N/A</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                                    <span class="text-slate-400">Industry & Stage:</span>
                                    <span class="font-medium text-slate-700 dark:text-slate-300" id="modal-industry-stage">N/A</span>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-slate-400">Location:</span>
                                    <span class="font-medium text-slate-700 dark:text-slate-300" id="modal-location">N/A</span>
                                </div>
                            </div>

                            <div class="border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 space-y-2">
                                <h4 class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <i data-lucide="user-check" class="w-3.5 h-3.5 text-slate-400"></i>
                                    Founder & Syndicate
                                </h4>
                                <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                                    <span class="text-slate-400">Lead Founder:</span>
                                    <span class="font-semibold text-slate-700 dark:text-slate-300" id="modal-founder-name">N/A</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                                    <span class="text-slate-400">Email:</span>
                                    <span class="font-medium text-slate-700 dark:text-slate-300" id="modal-founder-email">N/A</span>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-slate-400">Investor Backers:</span>
                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400" id="modal-investor-count">0 investors</span>
                                </div>
                            </div>
                        </div>

                        <!-- Purpose & Use of Capital -->
                        <div class="border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 space-y-1.5">
                            <h4 class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <i data-lucide="compass" class="w-3.5 h-3.5 text-slate-400"></i>
                                Use of Funds & Pitch Justification
                            </h4>
                            <p class="text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg border border-slate-100 dark:border-slate-800" id="modal-purpose">
                                N/A
                            </p>
                        </div>

                        <!-- Status Decision Control -->
                        <div class="space-y-3 pt-2">
                            <label class="block font-bold text-slate-800 dark:text-slate-200">
                                Administrative Status Decision
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <label class="border border-slate-200 dark:border-slate-700 rounded-lg p-2.5 flex items-center gap-2 cursor-pointer hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30 transition">
                                    <input type="radio" name="new_status" value="LIVE" class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-semibold text-emerald-700 dark:text-emerald-400">Go LIVE</span>
                                </label>
                                <label class="border border-slate-200 dark:border-slate-700 rounded-lg p-2.5 flex items-center gap-2 cursor-pointer hover:bg-sky-50/50 dark:hover:bg-sky-950/30 transition">
                                    <input type="radio" name="new_status" value="APPROVED" class="text-sky-600 focus:ring-sky-500">
                                    <span class="font-semibold text-sky-700 dark:text-sky-400">Approved</span>
                                </label>
                                <label class="border border-slate-200 dark:border-slate-700 rounded-lg p-2.5 flex items-center gap-2 cursor-pointer hover:bg-amber-50/50 dark:hover:bg-amber-950/30 transition">
                                    <input type="radio" name="new_status" value="UNDER_REVIEW" class="text-amber-600 focus:ring-amber-500">
                                    <span class="font-semibold text-amber-700 dark:text-amber-400">Under Review</span>
                                </label>
                                <label class="border border-slate-200 dark:border-slate-700 rounded-lg p-2.5 flex items-center gap-2 cursor-pointer hover:bg-rose-50/50 dark:hover:bg-rose-950/30 transition">
                                    <input type="radio" name="new_status" value="REJECTED" class="text-rose-600 focus:ring-rose-500">
                                    <span class="font-semibold text-rose-700 dark:text-rose-400">Reject</span>
                                </label>
                            </div>

                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Compliance Notes / Founder Feedback
                                </label>
                                <textarea name="review_notes" rows="2" 
                                          placeholder="Optional comments or stipulations dispatched to the founder upon status update..."
                                          class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="closeReviewModal()" class="px-4 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                            Save & Dispatch Notification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let currentFilter = 'ALL';

        function setRoundFilter(filter) {
            currentFilter = filter;
            const buttons = ['ALL', 'PENDING', 'LIVE', 'CLOSED'];
            buttons.forEach(b => {
                const btn = document.getElementById('filter-btn-' + b);
                if (btn) {
                    if (b === filter) {
                        btn.className = 'round-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white shadow-sm transition';
                    } else {
                        btn.className = 'round-filter-btn px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition';
                    }
                }
            });
            filterRoundsTable();
        }

        function filterRoundsTable() {
            const query = (document.getElementById('round-search')?.value || '').toLowerCase().trim();
            const stageFilter = (document.getElementById('stage-filter')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.round-row');

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                const rowStage = (row.getAttribute('data-stage') || '').toLowerCase();
                const isPending = row.getAttribute('data-is-pending') === 'true';
                const isLive = row.getAttribute('data-is-live') === 'true';
                const isClosed = row.getAttribute('data-is-closed') === 'true';

                let matchesFilter = true;
                if (currentFilter === 'PENDING') matchesFilter = isPending;
                if (currentFilter === 'LIVE') matchesFilter = isLive;
                if (currentFilter === 'CLOSED') matchesFilter = isClosed;

                const matchesQuery = query === '' || searchData.includes(query);
                const matchesStage = stageFilter === '' || rowStage === stageFilter;

                if (matchesFilter && matchesQuery && matchesStage) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function openReviewModal(data) {
            document.getElementById('modal-round-id').value = data.id;
            document.getElementById('modal-round-name').innerText = data.round_name;
            document.getElementById('modal-company-name').innerText = data.company_name;
            document.getElementById('modal-target-amount').innerText = data.target_amount;
            document.getElementById('modal-valuation').innerText = data.valuation;
            document.getElementById('modal-equity').innerText = data.equity_offered;
            document.getElementById('modal-min-ticket').innerText = data.min_investment;
            document.getElementById('modal-cin').innerText = data.cin_number;
            document.getElementById('modal-industry-stage').innerText = data.industry + ' • ' + data.stage;
            document.getElementById('modal-location').innerText = (data.city && data.state) ? (data.city + ', ' + data.state) : 'India';
            document.getElementById('modal-founder-name').innerText = data.founder_name;
            document.getElementById('modal-founder-email').innerText = data.founder_email;
            document.getElementById('modal-investor-count').innerText = data.investor_count + ' investors committed';
            document.getElementById('modal-purpose').innerText = data.purpose || 'General working capital & operational runway scaling.';

            // Select radio button matching current status
            const radios = document.getElementsByName('new_status');
            for (let r of radios) {
                if (r.value === data.status) {
                    r.checked = true;
                }
            }

            document.getElementById('review-modal').classList.remove('hidden');
            lucide.createIcons();
        }

        function closeReviewModal() {
            document.getElementById('review-modal').classList.add('hidden');
        }
    </script>
</body>
</html>
