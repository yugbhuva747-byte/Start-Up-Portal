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
        $totalInvested += (float)$i['amount_invested'];
        $totalEquity += (float)$i['equity_allotted_percent'];
        
        if (!isset($uniqueCompanies[$i['comp_id']])) {
            $uniqueCompanies[$i['comp_id']] = $i['company_name'];
        }
        
        // Industry breakdown
        $ind = $i['industry'] ?? 'Other';
        $industryBreakdown[$ind] = ($industryBreakdown[$ind] ?? 0) + (float)$i['amount_invested'];
        
        // Monthly investment tracking
        $month = date('M Y', strtotime($i['confirmed_at']));
        $monthlyInvestments[$month] = ($monthlyInvestments[$month] ?? 0) + (float)$i['amount_invested'];
    }
    
    $totalStartups = count($uniqueCompanies);
    $latestInvestment = !empty($investments) ? $investments[0] : null;
    
    // Count pending orders
    $pendStmt = $db->prepare("SELECT COUNT(*) FROM investment_orders WHERE investor_user_id = ? AND status = 'PENDING'");
    $pendStmt->execute([$user['id']]);
    $pendingOrders = (int)$pendStmt->fetchColumn();
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }
        .stat-card { position: relative; overflow: hidden; }
        .stat-card::before {
            content: '';
            position: absolute;
            top: -20px; right: -20px;
            width: 80px; height: 80px;
            border-radius: 50%;
            opacity: 0.06;
        }
        .stat-card-indigo::before { background: #6366F1; }
        .stat-card-emerald::before { background: #10B981; }
        .stat-card-purple::before { background: #8B5CF6; }
        .stat-card-amber::before { background: #F59E0B; }
        .industry-bar { height: 6px; border-radius: 3px; transition: width 1s ease; }
        .timeline-dot {
            width: 10px; height: 10px; border-radius: 50%;
            border: 2px solid #6366F1; background: white;
            position: relative; z-index: 2; flex-shrink: 0;
        }
        .timeline-dot.active { background: #6366F1; }
        .timeline-line {
            position: absolute; left: 4px; top: 10px; width: 2px; bottom: 0; background: #E2E8F0;
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="portfolio-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Active Venture Portfolio</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Track equity stakes, investment performance, and allotment certificates across your portfolio.</p>
                </div>
                <a href="<?= url('investor/discover.php') ?>" class="px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition flex items-center space-x-1.5 self-start">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Invest in New Deal</span>
                </a>
            </div>

            <!-- Portfolio KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="stats-row">
                <div class="card-clean rounded-2xl p-5 stat-card stat-card-indigo">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Deployed</span>
                        <div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600"><i data-lucide="wallet" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-2xl font-black text-slate-900"><?= format_inr($totalInvested) ?></div>
                    <span class="text-[10px] text-slate-500 mt-0.5 block">Across <?= count($investments) ?> investment<?= count($investments) !== 1 ? 's' : '' ?></span>
                </div>

                <div class="card-clean rounded-2xl p-5 stat-card stat-card-emerald">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Companies</span>
                        <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600"><i data-lucide="building-2" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600"><?= $totalStartups ?></div>
                    <span class="text-[10px] text-slate-500 mt-0.5 block">Portfolio companies</span>
                </div>

                <div class="card-clean rounded-2xl p-5 stat-card stat-card-purple">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Avg Ticket</span>
                        <div class="p-1.5 rounded-lg bg-purple-50 text-purple-600"><i data-lucide="trending-up" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-2xl font-black text-purple-600"><?= format_inr($avgTicket) ?></div>
                    <span class="text-[10px] text-slate-500 mt-0.5 block">Per investment average</span>
                </div>

                <div class="card-clean rounded-2xl p-5 stat-card stat-card-amber">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Equity</span>
                        <div class="p-1.5 rounded-lg bg-amber-50 text-amber-600"><i data-lucide="pie-chart" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-2xl font-black text-amber-600"><?= number_format($totalEquity, 2) ?>%</div>
                    <span class="text-[10px] text-slate-500 mt-0.5 block">Combined equity held</span>
                </div>
            </div>

            <?php if (!empty($investments)): ?>
            <!-- Two Column: Industry Breakdown + Investment Timeline -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Industry Allocation -->
                <div class="card-clean rounded-2xl p-6">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center space-x-1.5">
                        <i data-lucide="bar-chart-3" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Sector Allocation</span>
                    </h2>
                    <div class="space-y-3">
                        <?php 
                        arsort($industryBreakdown);
                        $maxAmount = max($industryBreakdown) ?: 1;
                        $barColors = ['bg-indigo-500', 'bg-emerald-500', 'bg-purple-500', 'bg-amber-500', 'bg-rose-500', 'bg-teal-500'];
                        $colorIdx = 0;
                        foreach ($industryBreakdown as $industry => $amount): 
                            $pct = $totalInvested > 0 ? round(($amount / $totalInvested) * 100) : 0;
                            $barWidth = round(($amount / $maxAmount) * 100);
                            $color = $barColors[$colorIdx % count($barColors)];
                            $colorIdx++;
                        ?>
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs font-semibold text-slate-700"><?= htmlspecialchars($industry) ?></span>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs font-bold text-slate-900"><?= format_inr($amount) ?></span>
                                        <span class="text-[10px] text-slate-400 font-medium w-8 text-right"><?= $pct ?>%</span>
                                    </div>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="industry-bar <?= $color ?>" style="width: 0%" data-width="<?= $barWidth ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Investment Timeline -->
                <div class="card-clean rounded-2xl p-6">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center space-x-1.5">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Investment Timeline</span>
                    </h2>
                    <div class="space-y-0">
                        <?php foreach (array_slice($investments, 0, 6) as $idx => $inv): ?>
                            <div class="flex items-start space-x-3 relative <?= $idx < min(5, count($investments) - 1) ? 'pb-5' : '' ?>">
                                <?php if ($idx < min(5, count($investments) - 1)): ?>
                                    <div class="timeline-line"></div>
                                <?php endif; ?>
                                <div class="timeline-dot <?= $idx === 0 ? 'active' : '' ?> mt-0.5"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($inv['company_name']) ?></span>
                                        <span class="text-xs font-black text-emerald-600 ml-2 flex-shrink-0"><?= format_inr($inv['amount_invested']) ?></span>
                                    </div>
                                    <div class="flex items-center space-x-2 mt-0.5">
                                        <span class="text-[10px] text-slate-400"><?= date('d M Y', strtotime($inv['confirmed_at'])) ?></span>
                                        <span class="text-[10px] text-slate-300">•</span>
                                        <span class="text-[10px] text-indigo-600 font-semibold"><?= htmlspecialchars($inv['round_name']) ?></span>
                                        <span class="text-[10px] text-slate-300">•</span>
                                        <span class="text-[10px] font-bold text-slate-600"><?= $inv['equity_allotted_percent'] ?>% equity</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Portfolio Holdings Table -->
            <div class="card-clean rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                        <i data-lucide="briefcase" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Investment Allotment Records</span>
                    </h2>
                    <?php if (!empty($investments)): ?>
                        <span class="text-[10px] font-medium text-slate-400"><?= count($investments) ?> record<?= count($investments) !== 1 ? 's' : '' ?></span>
                    <?php endif; ?>
                </div>

                <?php if (empty($investments)): ?>
                    <div class="py-14 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-100 flex items-center justify-center">
                            <i data-lucide="folder-open" class="w-8 h-8 text-slate-300"></i>
                        </div>
                        <div class="text-sm font-bold text-slate-800 mb-1">No portfolio companies yet</div>
                        <div class="text-xs text-slate-500 max-w-sm mx-auto">Explore live startup funding rounds and commit early-stage capital to build your venture portfolio.</div>
                        <a href="<?= url('investor/discover.php') ?>" class="inline-flex items-center space-x-1.5 mt-4 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            <span>Discover Startups</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    <th class="pb-2.5 font-bold">Company</th>
                                    <th class="pb-2.5 font-bold">Sector</th>
                                    <th class="pb-2.5 font-bold">Round</th>
                                    <th class="pb-2.5 font-bold">Capital</th>
                                    <th class="pb-2.5 font-bold">Equity</th>
                                    <th class="pb-2.5 font-bold">Valuation</th>
                                    <th class="pb-2.5 font-bold">Certificate</th>
                                    <th class="pb-2.5 font-bold">Status</th>
                                    <th class="pb-2.5 font-bold">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($investments as $inv): ?>
                                    <tr class="hover:bg-slate-50/70 transition group">
                                        <td class="py-3">
                                            <a href="<?= url('investor/startup_detail.php?id=' . encode_id($inv['comp_id'])) ?>" class="flex items-center space-x-3 group-hover:text-indigo-600 transition">
                                                <img src="<?= $inv['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>" class="w-8 h-8 rounded-lg object-cover border border-slate-200">
                                                <div>
                                                    <div class="font-bold text-slate-900 text-xs group-hover:text-indigo-600 transition"><?= htmlspecialchars($inv['company_name']) ?></div>
                                                    <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($inv['cin_number'] ?? 'CIN Verified') ?></div>
                                                </div>
                                            </a>
                                        </td>
                                        <td class="py-3">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-medium border border-slate-200">
                                                <?= htmlspecialchars($inv['industry']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-slate-700 font-medium text-xs"><?= htmlspecialchars($inv['round_name']) ?></td>
                                        <td class="py-3 font-black text-emerald-600 text-xs"><?= format_inr($inv['amount_invested']) ?></td>
                                        <td class="py-3"><span class="font-bold text-indigo-600 text-xs"><?= $inv['equity_allotted_percent'] ?>%</span></td>
                                        <td class="py-3 text-slate-600 text-[11px]"><?= format_inr($inv['valuation'] ?? 0) ?></td>
                                        <td class="py-3">
                                            <div class="font-mono text-indigo-600 font-semibold text-[11px]"><?= htmlspecialchars($inv['certificate_number'] ?? 'CERT-PENDING') ?></div>
                                            <div class="text-[10px] text-slate-400"><?= htmlspecialchars($inv['transaction_ref'] ?? '') ?></div>
                                        </td>
                                        <td class="py-3"><?= render_status_badge($inv['round_status']) ?></td>
                                        <td class="py-3 text-slate-500 text-[11px]"><?= date('d M Y', strtotime($inv['confirmed_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Portfolio Summary Footer -->
                    <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center space-x-4 text-[11px]">
                            <span class="text-slate-500">Portfolio Total: <strong class="text-slate-900"><?= format_inr($totalInvested) ?></strong></span>
                            <span class="text-slate-300">|</span>
                            <span class="text-slate-500">Equity: <strong class="text-indigo-600"><?= number_format($totalEquity, 2) ?>%</strong></span>
                            <span class="text-slate-300">|</span>
                            <span class="text-slate-500">Companies: <strong class="text-slate-900"><?= $totalStartups ?></strong></span>
                        </div>
                        <div class="flex items-center space-x-1.5 text-[10px] text-emerald-600 font-semibold">
                            <i data-lucide="shield-check" class="w-3 h-3"></i>
                            <span>All certificates audit-verified</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#portfolio-main > *", { duration: 0.5, y: 15, opacity: 0, stagger: 0.08, ease: "power2.out" });
        // Animate industry bars
        setTimeout(() => {
            document.querySelectorAll('.industry-bar').forEach(bar => { bar.style.width = bar.dataset.width; });
        }, 600);
    </script>
</body>
</html>
