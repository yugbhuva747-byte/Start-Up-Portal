<?php
/**
 * Investor Module: Portfolio & Holdings Management
 * Premium, User-Friendly & Intuitive Venture Experience
 * Enhanced with total investment capital pool, deployment breakdown, dry powder tracking, and equity analytics
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Portfolio & Venture Holdings';

$investments = [];
$totalInvested = 0;
$totalEquity = 0;
$totalStartups = 0;
$uniqueCompanies = [];
$industryBreakdown = [];
$monthlyInvestments = [];
$latestInvestment = null;

if ($db) {
    // 1. Fetch confirmed investments
    $stmt = $db->prepare("
        SELECT inv.*, c.name as company_name, c.industry, c.stage, c.logo_url, c.cin_number, c.id as comp_id,
               fr.round_name, fr.status as round_status, fr.valuation, fr.target_amount, fr.amount_raised,
               tx.transaction_ref, tx.status as tx_status, tx.payment_mode
        FROM investments inv
        JOIN companies c ON inv.company_id = c.id
        JOIN funding_rounds fr ON inv.funding_round_id = fr.id
        LEFT JOIN transactions tx ON inv.order_id = tx.order_id
        WHERE inv.investor_user_id = ?
        ORDER BY inv.confirmed_at DESC
    ");
    $stmt->execute([$user['id']]);
    $investments = $stmt->fetchAll();

    foreach ($investments as $i) {
        $totalInvested += (float) $i['amount_invested'];
        $totalEquity += (float) ($i['equity_allotted_percent'] ?? 0);

        if (!isset($uniqueCompanies[$i['comp_id']])) {
            $uniqueCompanies[$i['comp_id']] = $i['company_name'];
        }

        // Industry breakdown
        $ind = $i['industry'] ?? 'Other';
        $industryBreakdown[$ind] = ($industryBreakdown[$ind] ?? 0) + (float) $i['amount_invested'];

        // Monthly investment tracking
        $month = date('M Y', strtotime($i['confirmed_at']));
        $monthlyInvestments[$month] = ($monthlyInvestments[$month] ?? 0) + (float) $i['amount_invested'];
    }

    $totalStartups = count($uniqueCompanies);
    $latestInvestment = !empty($investments) ? $investments[0] : null;

    // 2. Fetch investor profile for total investment budget/capital fund
    $ipStmt = $db->prepare("SELECT * FROM investor_profiles WHERE user_id = ?");
    $ipStmt->execute([$user['id']]);
    $investorProfile = $ipStmt->fetch();

    $totalBudget = !empty($investorProfile['investment_budget']) ? (float) $investorProfile['investment_budget'] : 5000000.00;

    // 3. Handle Capital Pool / Budget Updates (Set or Add Funds)
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_budget') {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            set_flash('Security validation failed. Please try again.', 'danger');
            redirect('investor/portfolio.php');
        }

        $budgetMode = $_POST['budget_mode'] ?? 'set'; // 'set' or 'add'
        $rawAmount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $amount = (float) $rawAmount;

        if ($amount <= 0) {
            set_flash('Please enter a valid capital amount greater than zero.', 'danger');
            redirect('investor/portfolio.php');
        }

        $newBudget = ($budgetMode === 'add') ? ($totalBudget + $amount) : $amount;

        if ($newBudget < $totalInvested) {
            set_flash('Total capital fund cannot be lower than your current deployed investments (' . format_inr($totalInvested) . ').', 'danger');
            redirect('investor/portfolio.php');
        }

        if ($investorProfile) {
            $upStmt = $db->prepare("UPDATE investor_profiles SET investment_budget = ? WHERE user_id = ?");
            $upStmt->execute([$newBudget, $user['id']]);
        } else {
            $upStmt = $db->prepare("INSERT INTO investor_profiles (user_id, investment_budget) VALUES (?, ?)");
            $upStmt->execute([$user['id'], $newBudget]);
        }

        log_audit($user['id'], 'UPDATE_INVESTMENT_BUDGET', 'investor_profiles', $user['id'], 'Updated capital fund to ' . $newBudget);
        set_flash('Capital Fund Pool updated successfully to ' . format_inr($newBudget) . '!', 'success');
        redirect('investor/portfolio.php');
    }

    // Auto-scale displayed total budget if user's deployed investments exceed default/saved budget
    if ($totalBudget < $totalInvested) {
        $totalBudget = $totalInvested;
    }

    // Count pending orders
    $pendStmt = $db->prepare("SELECT COUNT(*) FROM investment_orders WHERE investor_user_id = ? AND status = 'PENDING'");
    $pendStmt->execute([$user['id']]);
    $pendingOrders = (int) $pendStmt->fetchColumn();
} else {
    $totalBudget = 5000000.00;
}

$remainingBudget = max(0, $totalBudget - $totalInvested);
$deployedPct = $totalBudget > 0 ? min(100, round(($totalInvested / $totalBudget) * 100, 1)) : 0;
$remainingPct = max(0, round(100 - $deployedPct, 1));
$avgTicket = count($investments) > 0 ? $totalInvested / count($investments) : 0;
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Holdings & Capital Pool • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/investor/head.php'; ?>
    <style>
        .metric-card {
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 flex min-h-screen dark:bg-[#0B0F19] dark:text-slate-100 antialiased selection:bg-blue-600 selection:text-white">

    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6 sm:space-y-8" id="portfolio-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-xs sm:text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60' ?> flex items-center space-x-2.5 shadow-xs">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Editorial Header & Identity -->
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-xs relative overflow-hidden">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 sm:gap-6">
                    <div>
                        <div class="text-[11px] font-bold text-blue-600 dark:text-blue-400 tracking-wider uppercase mb-1 flex items-center gap-1.5">
                            <span>Venture Capital Allocations</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-slate-500 dark:text-slate-400 font-semibold">Fund Capacity & Cap Table</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            Portfolio Holdings & Fund Allocations
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                            Monitor your total investment capital fund, deployed capital across startups, remaining dry powder, and verified equity ownership.
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 flex-shrink-0 flex-wrap sm:flex-nowrap">
                        <button type="button" onclick="openBudgetModal('add')"
                            class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100 text-xs font-semibold transition flex items-center space-x-1.5 shadow-2xs border border-slate-200/60 dark:border-slate-700/60">
                            <i data-lucide="wallet" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                            <span>Manage Capital Pool</span>
                        </button>
                        <a href="<?= url('investor/discover.php') ?>"
                            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Invest in New Deal</span>
                        </a>
                    </div>
                </div>

                <!-- Executive Portfolio Metric Cards (4 Pillars - Scaled Down) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-6 pt-5 border-t border-slate-100 dark:border-slate-800">
                    <!-- Total Capital Fund -->
                    <div class="metric-card bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 rounded-xl sm:rounded-2xl p-4 sm:p-4.5 relative group">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Capital Pool</span>
                            <button type="button" onclick="openBudgetModal('set')"
                                class="text-[10px] font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-0.5">
                                <span>Adjust</span>
                                <i data-lucide="pencil" class="w-2.5 h-2.5"></i>
                            </button>
                        </div>
                        <div class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                            <?= format_inr($totalBudget) ?>
                        </div>
                        <div class="mt-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                            <i data-lucide="piggy-bank" class="w-3 h-3 text-blue-600 dark:text-blue-400"></i>
                            <span>Total Allocation Budget</span>
                        </div>
                    </div>

                    <!-- Total Capital Deployed -->
                    <div class="metric-card bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 rounded-xl sm:rounded-2xl p-4 sm:p-4.5">
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-1">Capital Invested</span>
                        <div class="text-lg sm:text-xl font-bold text-blue-600 dark:text-blue-400 tracking-tight">
                            <?= format_inr($totalInvested) ?>
                        </div>
                        <div class="mt-1.5 text-[11px] font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <i data-lucide="pie-chart" class="w-3 h-3"></i>
                            <span><?= $deployedPct ?>% of Pool Deployed</span>
                        </div>
                    </div>

                    <!-- Remaining Dry Powder -->
                    <div class="metric-card bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 rounded-xl sm:rounded-2xl p-4 sm:p-4.5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Available Dry Powder</span>
                            <button type="button" onclick="openBudgetModal('add')"
                                class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-0.5">
                                <span>+ Add Funds</span>
                            </button>
                        </div>
                        <div class="text-lg sm:text-xl font-bold text-emerald-600 dark:text-emerald-400 tracking-tight">
                            <?= format_inr($remainingBudget) ?>
                        </div>
                        <div class="mt-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1">
                            <i data-lucide="sparkles" class="w-3 h-3 text-amber-500"></i>
                            <span><?= $remainingPct ?>% Available to Invest</span>
                        </div>
                    </div>

                    <!-- Combined Equity Ownership -->
                    <div class="metric-card bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 rounded-xl sm:rounded-2xl p-4 sm:p-4.5">
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-1">Combined Ownership</span>
                        <div class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                            <?= number_format($totalEquity, 2) ?>%
                        </div>
                        <div class="mt-1.5 text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                            Across <?= $totalStartups ?> Active <?= $totalStartups === 1 ? 'Startup' : 'Startups' ?>
                        </div>
                    </div>
                </div>

                <!-- Visual Capital Allocation & Deployment Bar (Compact) -->
                <div class="mt-5 p-3.5 sm:p-4 rounded-xl sm:rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 space-y-2.5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs gap-1.5">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[10px] sm:text-[11px]">Fund Utilization & Deployment</span>
                            <span class="px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300">
                                <?= $deployedPct ?>% Deployed
                            </span>
                        </div>
                        <div class="text-slate-500 dark:text-slate-400 font-medium text-[11px]">
                            <span><?= format_inr($totalInvested) ?> Deployed</span>
                            <span class="mx-1">•</span>
                            <span><?= format_inr($remainingBudget) ?> Remaining</span>
                        </div>
                    </div>

                    <!-- Multi-segment visual bar -->
                    <div class="w-full h-2.5 bg-slate-200 dark:bg-slate-700/80 rounded-full overflow-hidden flex shadow-inner">
                        <div class="h-full bg-gradient-to-r from-blue-600 to-indigo-600 transition-all duration-500"
                            style="width: <?= $deployedPct ?>%"
                            title="Deployed: <?= format_inr($totalInvested) ?> (<?= $deployedPct ?>%)"></div>
                        <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500"
                            style="width: <?= $remainingPct ?>%"
                            title="Available Dry Powder: <?= format_inr($remainingBudget) ?> (<?= $remainingPct ?>%)"></div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between text-[10.5px] sm:text-[11px] font-medium text-slate-500 dark:text-slate-400 pt-0.5 gap-2">
                        <div class="flex items-center gap-3.5 flex-wrap">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                <span>Deployed: <strong class="text-slate-800 dark:text-slate-200 font-semibold"><?= format_inr($totalInvested) ?></strong> (<?= $deployedPct ?>%)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>Dry Powder: <strong class="text-slate-800 dark:text-slate-200 font-semibold"><?= format_inr($remainingBudget) ?></strong> (<?= $remainingPct ?>%)</span>
                            </div>
                        </div>
                        <button type="button" onclick="openBudgetModal('add')"
                            class="text-blue-600 dark:text-blue-400 hover:text-blue-700 font-bold hover:underline flex items-center gap-1">
                            <i data-lucide="plus-circle" class="w-3 h-3"></i>
                            <span>Add Capital to Pool</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 SECTION: INVESTMENT LIST (Cards / Rows)
                 ========================================== -->
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight">Active Share Holdings</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Share allotment certificates, investment amounts, and portfolio allocation shares</p>
                    </div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-bold"><?= count($investments) ?> Total Positions</span>
                </div>

                <?php if (empty($investments)): ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-12 text-center text-xs text-slate-500 dark:text-slate-400 shadow-xs">
                        <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="briefcase" class="w-7 h-7"></i>
                        </div>
                        <div class="text-base font-extrabold text-slate-900 dark:text-white mb-1">No venture investments yet</div>
                        <p class="max-w-md mx-auto text-slate-500 dark:text-slate-400 leading-relaxed">
                            Start building your startup investment portfolio. You have <strong class="text-emerald-600 dark:text-emerald-400"><?= format_inr($remainingBudget) ?></strong> available dry powder ready to deploy. Browse active opportunities and commit capital today.
                        </p>
                        <a href="<?= url('investor/discover.php') ?>"
                            class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shadow-xs">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                            <span>Explore Active Deals</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl divide-y divide-slate-100 dark:divide-slate-800 overflow-hidden shadow-xs">
                        <?php foreach ($investments as $inv):
                            $encCompId = hash_id_encode($inv['comp_id']);
                            // Share of investor's total capital pool allocated to this startup
                            $compShareOfBudget = $totalBudget > 0 ? round(((float)$inv['amount_invested'] / $totalBudget) * 100, 1) : 0;
                            $compShareOfDeployed = $totalInvested > 0 ? round(((float)$inv['amount_invested'] / $totalInvested) * 100, 1) : 0;
                        ?>
                            <div class="p-5 sm:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-6 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <!-- Startup Identity -->
                                <div class="flex items-start space-x-4 min-w-0 flex-1">
                                    <img src="<?= $inv['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100' ?>"
                                        class="w-12 h-12 rounded-2xl object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0 shadow-2xs">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="<?= url('investor/startup_detail.php?id=' . $encCompId) ?>"
                                                class="text-base font-bold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition truncate">
                                                <?= htmlspecialchars($inv['company_name']) ?>
                                            </a>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                                <?= htmlspecialchars($inv['industry']) ?>
                                            </span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300">
                                                <?= htmlspecialchars($inv['round_name']) ?>
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-3">
                                            <span>CIN: <?= htmlspecialchars($inv['cin_number'] ?? 'Verified') ?></span>
                                            <span>•</span>
                                            <span>Confirmed: <?= date('d M Y', strtotime($inv['confirmed_at'])) ?></span>
                                            <span>•</span>
                                            <span class="font-mono text-[11px] text-blue-600 dark:text-blue-400">
                                                Cert #<?= htmlspecialchars($inv['certificate_number'] ?? 'CONFIRMED') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial Metrics & Action -->
                                <div class="flex items-center justify-between lg:justify-end gap-5 sm:gap-6 text-xs flex-shrink-0 pt-3 lg:pt-0 border-t lg:border-t-0 border-slate-100 dark:border-slate-800 flex-wrap sm:flex-nowrap">
                                    <!-- Committed Capital -->
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Invested Amount</span>
                                        <span class="font-extrabold text-slate-900 dark:text-white text-sm"><?= format_inr($inv['amount_invested']) ?></span>
                                    </div>

                                    <!-- Share of Total Capital Pool -->
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Fund Share</span>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-blue-50 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 border border-blue-200/50 dark:border-blue-800/50">
                                                <?= $compShareOfBudget ?>%
                                            </span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium hidden sm:inline">of Fund</span>
                                        </div>
                                    </div>

                                    <!-- Equity Stake -->
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Equity Stake</span>
                                        <span class="font-extrabold text-blue-600 dark:text-blue-400 text-sm"><?= $inv['equity_allotted_percent'] ?>%</span>
                                    </div>

                                    <!-- Valuation -->
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Valuation</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-200"><?= format_inr($inv['valuation'] ?? 0) ?></span>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= url('certificate.php?id=' . $inv['id']) ?>" target="_blank"
                                            class="px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60 font-bold text-xs flex items-center space-x-1.5 transition">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                            <span>Certificate</span>
                                        </a>
                                        <a href="<?= url('investor/startup_detail.php?id=' . $encCompId) ?>"
                                            class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs transition">
                                            Deal Room →
                                        </a>
                                        <a href="<?= url('company.php?id=' . $encCompId) ?>" target="_blank"
                                            class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 transition"
                                            title="Public Dossier">
                                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ==========================================
                 SECTION: SECTOR ALLOCATION BREAKDOWN
                 ========================================== -->
            <?php if (!empty($investments)): ?>
                <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs space-y-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Portfolio Distribution by Industry</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Capital concentration and industry diversification</p>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        <?php
                        arsort($industryBreakdown);
                        foreach ($industryBreakdown as $indName => $indAmount):
                            $indPctOfTotal = $totalBudget > 0 ? round(($indAmount / $totalBudget) * 100, 1) : 0;
                            $indPctOfDeployed = $totalInvested > 0 ? round(($indAmount / $totalInvested) * 100) : 0;
                        ?>
                            <div class="py-3.5 flex items-center justify-between gap-4">
                                <div class="w-1/3">
                                    <span class="font-bold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($indName) ?></span>
                                    <span class="text-[11px] text-slate-400 block font-normal"><?= $indPctOfTotal ?>% of total fund pool</span>
                                </div>
                                <div class="flex-1 max-w-md">
                                    <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full" style="width: <?= $indPctOfDeployed ?>%"></div>
                                    </div>
                                </div>
                                <div class="text-right w-1/4">
                                    <span class="font-extrabold text-slate-900 dark:text-white"><?= format_inr($indAmount) ?></span>
                                    <span class="text-slate-400 dark:text-slate-500 text-[11px] ml-1.5 font-semibold">(<?= $indPctOfDeployed ?>%)</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        </main>
    </div>

    <!-- ==============================================================
         MODAL: MANAGE TOTAL CAPITAL POOL & ADD FUNDS
         ============================================================== -->
    <div id="budget-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-lg p-6 sm:p-7 shadow-2xl relative animate-in fade-in zoom-in duration-200">
            <!-- Close Button -->
            <button type="button" onclick="closeBudgetModal()"
                class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <!-- Modal Header -->
            <div class="flex items-center space-x-3 mb-5">
                <div class="w-11 h-11 rounded-2xl bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-200/50 dark:border-blue-800/50 shadow-2xs">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight">Investment Capital Pool</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Configure total available capital or add funds to your dry powder</p>
                </div>
            </div>

            <!-- Current Financial Summary Strip -->
            <div class="grid grid-cols-3 gap-2.5 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 mb-5 text-center">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Pool</span>
                    <span class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white"><?= format_inr($totalBudget) ?></span>
                </div>
                <div class="border-x border-slate-200 dark:border-slate-700/60">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Invested</span>
                    <span class="text-xs sm:text-sm font-extrabold text-blue-600 dark:text-blue-400"><?= format_inr($totalInvested) ?></span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Dry Powder</span>
                    <span class="text-xs sm:text-sm font-extrabold text-emerald-600 dark:text-emerald-400"><?= format_inr($remainingBudget) ?></span>
                </div>
            </div>

            <!-- Form -->
            <form method="POST" action="" class="space-y-4" id="budget-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_budget">

                <!-- Mode Selector (Tabs) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Action Type</label>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl">
                        <label class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl cursor-pointer text-xs font-bold transition mode-tab active-tab bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-2xs">
                            <input type="radio" name="budget_mode" value="add" checked class="hidden" onchange="toggleBudgetMode('add')">
                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                            <span>Add Funds (+ Top-up)</span>
                        </label>
                        <label class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl cursor-pointer text-xs font-bold transition mode-tab text-slate-600 dark:text-slate-400">
                            <input type="radio" name="budget_mode" value="set" class="hidden" onchange="toggleBudgetMode('set')">
                            <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                            <span>Set Total Capital</span>
                        </label>
                    </div>
                </div>

                <!-- Quick Preset Pills -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5" id="presets-label">
                        Quick Preset Amounts:
                    </label>
                    <div class="grid grid-cols-4 gap-2 text-xs">
                        <button type="button" onclick="setPresetAmount(1000000)"
                            class="py-2 px-2 rounded-xl bg-slate-100 hover:bg-blue-50 hover:text-blue-600 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold border border-slate-200/60 dark:border-slate-700/60 transition text-center text-[11px]">
                            ₹10 Lakhs
                        </button>
                        <button type="button" onclick="setPresetAmount(2500000)"
                            class="py-2 px-2 rounded-xl bg-slate-100 hover:bg-blue-50 hover:text-blue-600 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold border border-slate-200/60 dark:border-slate-700/60 transition text-center text-[11px]">
                            ₹25 Lakhs
                        </button>
                        <button type="button" onclick="setPresetAmount(5000000)"
                            class="py-2 px-2 rounded-xl bg-slate-100 hover:bg-blue-50 hover:text-blue-600 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold border border-slate-200/60 dark:border-slate-700/60 transition text-center text-[11px]">
                            ₹50 Lakhs
                        </button>
                        <button type="button" onclick="setPresetAmount(10000000)"
                            class="py-2 px-2 rounded-xl bg-slate-100 hover:bg-blue-50 hover:text-blue-600 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold border border-slate-200/60 dark:border-slate-700/60 transition text-center text-[11px]">
                            ₹1 Crore
                        </button>
                    </div>
                </div>

                <!-- Amount Input -->
                <div>
                    <label for="budget-amount-input" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1" id="amount-input-label">
                        Amount to Add (₹)
                    </label>
                    <div class="relative rounded-2xl shadow-2xs">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 font-bold text-sm">
                            ₹
                        </div>
                        <input type="number" name="amount" id="budget-amount-input" required min="10000" step="10000"
                            placeholder="e.g. 1000000"
                            oninput="updateCalculationPreview()"
                            class="block w-full rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 py-3 pl-8 pr-4 text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20">
                    </div>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 block" id="amount-formatted-hint">Enter amount in INR (Minimum ₹10,000)</span>
                </div>

                <!-- Dynamic Calculation Preview Box -->
                <div id="calculation-preview" class="p-3.5 rounded-2xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/60 dark:border-blue-800/60 text-xs space-y-1.5">
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                        <span>New Total Capital Pool:</span>
                        <strong class="text-blue-600 dark:text-blue-400 font-extrabold text-sm" id="preview-new-total"><?= format_inr($totalBudget) ?></strong>
                    </div>
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                        <span>New Available Dry Powder:</span>
                        <strong class="text-emerald-600 dark:text-emerald-400 font-extrabold" id="preview-new-powder"><?= format_inr($remainingBudget) ?></strong>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-2">
                    <button type="button" onclick="closeBudgetModal()"
                        class="px-5 py-2.5 rounded-2xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm flex items-center space-x-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Confirm & Update Pool</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        if (window.gsap) {
            gsap.from("#portfolio-main", { duration: 0.4, y: 8, opacity: 0, ease: "power2.out" });
        }

        // --- Budget Modal Logic ---
        const currentBudget = <?= json_encode($totalBudget) ?>;
        const currentInvested = <?= json_encode($totalInvested) ?>;
        let activeMode = 'add';

        const budgetModal = document.getElementById('budget-modal');
        const amountInput = document.getElementById('budget-amount-input');
        const amountLabel = document.getElementById('amount-input-label');
        const previewNewTotal = document.getElementById('preview-new-total');
        const previewNewPowder = document.getElementById('preview-new-powder');
        const amountFormattedHint = document.getElementById('amount-formatted-hint');

        function openBudgetModal(mode = 'add') {
            activeMode = mode;
            // update radio
            const radio = document.querySelector(`input[name="budget_mode"][value="${mode}"]`);
            if (radio) {
                radio.checked = true;
                toggleBudgetMode(mode);
            }
            if (budgetModal) {
                budgetModal.classList.remove('hidden');
            }
            if (amountInput) {
                amountInput.focus();
            }
        }

        function closeBudgetModal() {
            if (budgetModal) {
                budgetModal.classList.add('hidden');
            }
        }

        function toggleBudgetMode(mode) {
            activeMode = mode;
            const tabs = document.querySelectorAll('.mode-tab');
            tabs.forEach(tab => {
                const input = tab.querySelector('input');
                if (input && input.value === mode) {
                    tab.classList.add('bg-white', 'dark:bg-slate-700', 'text-blue-600', 'dark:text-blue-400', 'shadow-2xs');
                    tab.classList.remove('text-slate-600', 'dark:text-slate-400');
                } else {
                    tab.classList.remove('bg-white', 'dark:bg-slate-700', 'text-blue-600', 'dark:text-blue-400', 'shadow-2xs');
                    tab.classList.add('text-slate-600', 'dark:text-slate-400');
                }
            });

            if (mode === 'add') {
                amountLabel.textContent = 'Amount to Add to Fund (₹)';
                amountInput.placeholder = 'e.g. 1000000';
            } else {
                amountLabel.textContent = 'New Total Capital Fund (₹)';
                amountInput.placeholder = 'e.g. 10000000';
                if (!amountInput.value) {
                    amountInput.value = currentBudget;
                }
            }
            updateCalculationPreview();
        }

        function setPresetAmount(val) {
            amountInput.value = val;
            updateCalculationPreview();
        }

        function formatINR(val) {
            return new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                maximumFractionDigits: 0
            }).format(val);
        }

        function updateCalculationPreview() {
            const inputVal = parseFloat(amountInput.value) || 0;
            let newTotal = currentBudget;

            if (activeMode === 'add') {
                newTotal = currentBudget + inputVal;
            } else {
                newTotal = inputVal > 0 ? inputVal : currentBudget;
            }

            const newPowder = Math.max(0, newTotal - currentInvested);

            previewNewTotal.textContent = formatINR(newTotal);
            previewNewPowder.textContent = formatINR(newPowder);

            if (inputVal > 0) {
                amountFormattedHint.textContent = `Selected: ${formatINR(inputVal)}`;
            } else {
                amountFormattedHint.textContent = 'Enter amount in INR (Minimum ₹10,000)';
            }
        }

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && budgetModal && !budgetModal.classList.contains('hidden')) {
                closeBudgetModal();
            }
        });
    </script>
</body>

</html>