<?php
/**
 * Investor Module: Portfolio & Holdings Management
 * Enhanced with aggregate stats, equity tracking, investment timeline, ROI indicators
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
        $totalEquity += (float) $i['equity_allotted_percent'];

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
        body {
            font-family: "Vay Portal", Sans-serif;
        }

        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }

        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            opacity: 0.06;
        }

        .stat-card-indigo::before {
            background: #6366F1;
        }

        .stat-card-emerald::before {
            background: #10B981;
        }

        .stat-card-purple::before {
            background: #8B5CF6;
        }

        .stat-card-amber::before {
            background: #F59E0B;
        }

        .industry-bar {
            height: 6px;
            border-radius: 3px;
            transition: width 1s ease;
        }

        .timeline-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid #6366F1;
            background: white;
            position: relative;
            z-index: 2;
            flex-shrink: 0;
        }

        .timeline-dot.active {
            background: #6366F1;
        }

        .timeline-line {
            position: absolute;
            left: 4px;
            top: 10px;
            width: 2px;
            bottom: 0;
            background: #E2E8F0;
        }
    </style>
</head>

<body class="bg-[#F4F2EE] text-slate-900 flex min-h-screen dark:bg-[#0B0F19] dark:text-slate-100">

    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full p-4 sm:p-6 md:p-8 lg:p-10 space-y-8 sm:space-y-10" id="portfolio-main">

            <?php if ($flash): ?>
                <div
                    class="p-4 rounded-2xl text-xs sm:text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300 border-[#123B7A]/20' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200' ?> flex items-center space-x-2.5 shadow-2xs">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Editorial Header & Identity -->
            <section class="border-b border-[#E4E8EF] dark:border-slate-800 pb-8">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div
                            class="text-xs font-bold text-[#123B7A] dark:text-blue-400 tracking-wider uppercase mb-1.5 flex items-center gap-2">
                            <span>VENTURE CAPITAL ALLOCATIONS</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-beacon"></span>
                            <span class="text-[#667085] dark:text-slate-400 font-semibold">Active Institutional
                                Holdings</span>
                        </div>
                        <h1
                            class="text-2xl sm:text-3xl md:text-4xl font-black text-[#0B1F3A] dark:text-white tracking-tight">
                            Portfolio Holdings & Allotments
                        </h1>
                        <p
                            class="text-sm sm:text-base text-[#667085] dark:text-slate-300 mt-1.5 max-w-3xl leading-relaxed">
                            Verified equity stakes, share allotment records, institutional cap table positions, and
                            escrow transactions across your backed companies.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="<?= url('investor/discover.php') ?>"
                            class="px-5 py-3 rounded-xl bg-[#123B7A] hover:bg-[#0B1F3A] dark:bg-blue-600 dark:hover:bg-blue-700 text-white text-xs sm:text-sm font-bold transition flex items-center space-x-2 shadow-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Invest in New Deal</span>
                        </a>
                    </div>
                </div>

                <!-- Executive Portfolio Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mt-8">
                    <div
                        class="dashboard-card bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl p-6 shadow-xs relative overflow-hidden">
                        <span
                            class="text-xs font-bold text-[#667085] dark:text-slate-400 uppercase tracking-wider block mb-2">Total
                            Capital Deployed</span>
                        <div class="text-2xl sm:text-3xl font-black text-[#0B1F3A] dark:text-white tracking-tight">
                            <?= format_inr($totalInvested) ?></div>
                        <div
                            class="mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            <span>Escrow Confirmed</span>
                        </div>
                    </div>
                    <div
                        class="dashboard-card bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl p-6 shadow-xs relative overflow-hidden">
                        <span
                            class="text-xs font-bold text-[#667085] dark:text-slate-400 uppercase tracking-wider block mb-2">Backed
                            Ventures</span>
                        <div class="text-2xl sm:text-3xl font-black text-[#0B1F3A] dark:text-white tracking-tight">
                            <?= $totalStartups ?> <span
                                class="text-base font-bold text-[#667085] dark:text-slate-400">Companies</span></div>
                        <div class="mt-2 text-xs text-[#667085] dark:text-slate-400 font-medium">Active Cap Table Equity
                        </div>
                    </div>
                    <div
                        class="dashboard-card bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl p-6 shadow-xs relative overflow-hidden">
                        <span
                            class="text-xs font-bold text-[#667085] dark:text-slate-400 uppercase tracking-wider block mb-2">Combined
                            Ownership</span>
                        <div class="text-2xl sm:text-3xl font-black text-[#123B7A] dark:text-blue-400 tracking-tight">
                            <?= number_format($totalEquity, 2) ?>%</div>
                        <div class="mt-2 text-xs text-[#667085] dark:text-slate-400 font-medium">Allotted Share Equity
                        </div>
                    </div>
                    <div
                        class="dashboard-card bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl p-6 shadow-xs relative overflow-hidden">
                        <span
                            class="text-xs font-bold text-[#667085] dark:text-slate-400 uppercase tracking-wider block mb-2">Average
                            Check Size</span>
                        <div class="text-2xl sm:text-3xl font-black text-[#0B1F3A] dark:text-white tracking-tight">
                            <?= format_inr($avgTicket) ?></div>
                        <div class="mt-2 text-xs text-[#667085] dark:text-slate-400 font-medium">Per Syndicate
                            Allocation</div>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 SECTION: INVESTMENT LIST (Horizontal Rows)
                 ========================================== -->
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-[#0B1F3A] dark:text-white">Portfolio Holdings & Allotments
                        </h2>
                        <p class="text-xs text-[#667085] dark:text-slate-400">Detailed share allotment contracts and cap
                            table records</p>
                    </div>
                    <span class="text-xs text-[#667085] dark:text-slate-400 font-semibold"><?= count($investments) ?>
                        Total Entries</span>
                </div>

                <?php if (empty($investments)): ?>
                    <div
                        class="bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-xl p-12 text-center text-xs text-[#667085] dark:text-slate-400">
                        <div
                            class="w-12 h-12 rounded-full bg-[#EAF2FF] dark:bg-blue-950/60 text-[#123B7A] dark:text-blue-400 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="briefcase" class="w-6 h-6"></i>
                        </div>
                        <div class="text-sm font-bold text-[#0B1F3A] dark:text-white mb-1">No venture investments yet</div>
                        <p class="max-w-md mx-auto text-[#667085] dark:text-slate-400 leading-relaxed">
                            Start building your startup investment portfolio. Browse live opportunities, review
                            comprehensive diligence rooms, and commit early-stage capital.
                        </p>
                        <a href="<?= url('investor/discover.php') ?>"
                            class="inline-block mt-4 px-5 py-2.5 rounded-lg bg-[#123B7A] hover:bg-[#0B1F3A] dark:bg-blue-600 dark:hover:bg-blue-500 text-white font-bold text-xs transition">
                            Explore Active Rounds
                        </a>
                    </div>
                <?php else: ?>
                    <div
                        class="bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-xl divide-y divide-[#E4E8EF] dark:divide-slate-800">
                        <?php foreach ($investments as $inv):
                            $encCompId = encode_id($inv['comp_id']);
                            ?>
                            <div
                                class="p-5 sm:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-6 hover:bg-[#FAFBFD] dark:hover:bg-slate-800/80 transition">
                                <!-- Startup Identity -->
                                <div class="flex items-start space-x-4 min-w-0 flex-1">
                                    <img src="<?= $inv['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100' ?>"
                                        class="w-12 h-12 rounded-xl object-cover border border-[#E4E8EF] dark:border-slate-700 flex-shrink-0">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="<?= url('investor/startup_detail.php?id=' . $encCompId) ?>"
                                                class="text-sm font-bold text-[#0B1F3A] dark:text-white hover:text-[#123B7A] dark:hover:text-blue-400 transition truncate">
                                                <?= htmlspecialchars($inv['company_name']) ?>
                                            </a>
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-semibold bg-[#FAFBFD] dark:bg-slate-800 text-[#667085] dark:text-slate-300 border border-[#E4E8EF] dark:border-slate-700">
                                                <?= htmlspecialchars($inv['industry']) ?>
                                            </span>
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-semibold bg-[#EAF2FF] dark:bg-blue-950/60 text-[#123B7A] dark:text-blue-400">
                                                <?= htmlspecialchars($inv['round_name']) ?>
                                            </span>
                                        </div>
                                        <div
                                            class="text-xs text-[#667085] dark:text-slate-400 mt-1 flex flex-wrap items-center gap-3">
                                            <span>CIN: <?= htmlspecialchars($inv['cin_number'] ?? 'Verified') ?></span>
                                            <span>•</span>
                                            <span>Confirmed: <?= date('d M Y', strtotime($inv['confirmed_at'])) ?></span>
                                            <span>•</span>
                                            <span class="font-mono text-[10.5px]">Certificate:
                                                <?= htmlspecialchars($inv['certificate_number'] ?? 'CONFIRMED') ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial Metrics & Action -->
                                <div
                                    class="flex items-center justify-between lg:justify-end gap-6 text-xs flex-shrink-0 pt-3 lg:pt-0 border-t lg:border-t-0 border-[#E4E8EF] dark:border-slate-800">
                                    <div>
                                        <span class="text-[10px] text-[#667085] dark:text-slate-400 block">Committed
                                            Capital</span>
                                        <span
                                            class="font-extrabold text-[#0B1F3A] dark:text-white text-sm"><?= format_inr($inv['amount_invested']) ?></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-[#667085] dark:text-slate-400 block">Equity Stake</span>
                                        <span
                                            class="font-extrabold text-[#123B7A] dark:text-blue-400 text-sm"><?= $inv['equity_allotted_percent'] ?>%</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-[#667085] dark:text-slate-400 block">Round
                                            Valuation</span>
                                        <span
                                            class="font-semibold text-[#111827] dark:text-slate-200"><?= format_inr($inv['valuation'] ?? 0) ?></span>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <a href="<?= url('certificate.php?id=' . $inv['id']) ?>" target="_blank"
                                            class="px-3.5 py-2 rounded-lg bg-[#FAFBFD] dark:bg-slate-800 hover:bg-[#EAF2FF] dark:hover:bg-slate-700 text-[#123B7A] dark:text-blue-400 border border-[#E4E8EF] dark:border-slate-700 font-bold text-xs flex items-center space-x-1.5 transition">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                            <span>Certificate</span>
                                        </a>
                                        <a href="<?= url('investor/startup_detail.php?id=' . $encCompId) ?>"
                                            class="px-3.5 py-2 rounded-lg bg-white dark:bg-slate-800 hover:bg-[#FAFBFD] dark:hover:bg-slate-700 border border-[#E4E8EF] dark:border-slate-700 text-[#111827] dark:text-white font-semibold text-xs transition">
                                            Deal Room →
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ==========================================
                 SECTION: SECTOR ALLOCATION & HISTORY (Editorial Rows)
                 ========================================== -->
            <?php if (!empty($investments)): ?>
                <section class="border-t border-[#E4E8EF] pt-8 space-y-6">
                    <div class="text-[11px] font-bold text-[#667085] uppercase tracking-wider">
                        Portfolio Distribution by Industry
                    </div>

                    <div class="bg-white border border-[#E4E8EF] rounded-xl divide-y divide-[#E4E8EF] text-xs">
                        <?php
                        arsort($industryBreakdown);
                        foreach ($industryBreakdown as $indName => $indAmount):
                            $indPct = $totalInvested > 0 ? round(($indAmount / $totalInvested) * 100) : 0;
                            ?>
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div class="flex items-center space-x-3 w-1/3">
                                    <span class="font-bold text-[#0B1F3A]"><?= htmlspecialchars($indName) ?></span>
                                </div>
                                <div class="flex-1 max-w-md">
                                    <div class="w-full h-1.5 bg-[#E4E8EF] rounded-full overflow-hidden">
                                        <div class="h-full bg-[#123B7A] rounded-full" style="width: <?= $indPct ?>%"></div>
                                    </div>
                                </div>
                                <div class="text-right w-1/4">
                                    <span class="font-bold text-[#111827]"><?= format_inr($indAmount) ?></span>
                                    <span class="text-[#667085] text-[11px] ml-1.5">(<?= $indPct ?>%)</span>
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
        gsap.from("#portfolio-main", { duration: 0.4, y: 8, opacity: 0, ease: "power2.out" });
    </script>
</body>

</html>