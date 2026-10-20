<?php
/**
 * Investor Module: Portfolio & Holdings Management
 * Premium, User-Friendly & Intuitive Venture Experience
 * Enhanced with aggregate stats, equity tracking, cap table metrics, and certificate downloads
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

    // Count pending orders
    $pendStmt = $db->prepare("SELECT COUNT(*) FROM investment_orders WHERE investor_user_id = ? AND status = 'PENDING'");
    $pendStmt->execute([$user['id']]);
    $pendingOrders = (int) $pendStmt->fetchColumn();
}

$avgTicket = count($investments) > 0 ? $totalInvested / count($investments) : 0;
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Holdings • <?= APP_NAME ?></title>
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
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs relative overflow-hidden">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="text-xs font-bold text-blue-600 dark:text-blue-400 tracking-wider uppercase mb-1.5 flex items-center gap-2">
                            <span>Venture Capital Allocations</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-slate-500 dark:text-slate-400 font-semibold">Active Institutional Holdings</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            Portfolio Holdings & Allotments
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                            Verified equity stakes, share allotment records, institutional cap table positions, and escrow transactions across your backed ventures.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 flex-shrink-0">
                        <a href="<?= url('investor/discover.php') ?>"
                            class="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold transition flex items-center space-x-2 shadow-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Invest in New Deal</span>
                        </a>
                    </div>
                </div>

                <!-- Executive Portfolio Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
                    <div class="metric-card bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-5">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1">Total Capital Deployed</span>
                        <div class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            <?= format_inr($totalInvested) ?>
                        </div>
                        <div class="mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            <span>Escrow Confirmed</span>
                        </div>
                    </div>

                    <div class="metric-card bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-5">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1">Backed Ventures</span>
                        <div class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            <?= $totalStartups ?> <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">Companies</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 font-medium">Active Cap Table Stakes</div>
                    </div>

                    <div class="metric-card bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-5">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1">Combined Ownership</span>
                        <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 tracking-tight">
                            <?= number_format($totalEquity, 2) ?>%
                        </div>
                        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 font-medium">Allotted Share Equity</div>
                    </div>

                    <div class="metric-card bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-5">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1">Average Check Size</span>
                        <div class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            <?= format_inr($avgTicket) ?>
                        </div>
                        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 font-medium">Per Syndicate Allocation</div>
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
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Share allotment certificates and verifiable cap table records</p>
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
                            Start building your startup investment portfolio. Browse live opportunities, review comprehensive diligence rooms, and commit early-stage capital.
                        </p>
                        <a href="<?= url('investor/discover.php') ?>"
                            class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shadow-xs">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                            <span>Explore Active Rounds</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl divide-y divide-slate-100 dark:divide-slate-800 overflow-hidden shadow-xs">
                        <?php foreach ($investments as $inv):
                            $encCompId = hash_id_encode($inv['comp_id']);
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
                                <div class="flex items-center justify-between lg:justify-end gap-6 text-xs flex-shrink-0 pt-3 lg:pt-0 border-t lg:border-t-0 border-slate-100 dark:border-slate-800">
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Committed Capital</span>
                                        <span class="font-extrabold text-slate-900 dark:text-white text-sm"><?= format_inr($inv['amount_invested']) ?></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Equity Stake</span>
                                        <span class="font-extrabold text-blue-600 dark:text-blue-400 text-sm"><?= $inv['equity_allotted_percent'] ?>%</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Round Valuation</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-200"><?= format_inr($inv['valuation'] ?? 0) ?></span>
                                    </div>

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
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Capital concentration across emerging technology sectors</p>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        <?php
                        arsort($industryBreakdown);
                        foreach ($industryBreakdown as $indName => $indAmount):
                            $indPct = $totalInvested > 0 ? round(($indAmount / $totalInvested) * 100) : 0;
                        ?>
                            <div class="py-3.5 flex items-center justify-between gap-4">
                                <div class="w-1/3">
                                    <span class="font-bold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($indName) ?></span>
                                </div>
                                <div class="flex-1 max-w-md">
                                    <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full" style="width: <?= $indPct ?>%"></div>
                                    </div>
                                </div>
                                <div class="text-right w-1/4">
                                    <span class="font-extrabold text-slate-900 dark:text-white"><?= format_inr($indAmount) ?></span>
                                    <span class="text-slate-400 dark:text-slate-500 text-[11px] ml-1.5 font-semibold">(<?= $indPct ?>%)</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        if (window.gsap) {
            gsap.from("#portfolio-main", { duration: 0.4, y: 8, opacity: 0, ease: "power2.out" });
        }
    </script>
</body>

</html>