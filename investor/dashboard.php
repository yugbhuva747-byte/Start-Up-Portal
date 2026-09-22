<?php
/**
 * Investor Module: Main Dashboard & Deal Flow
 * Clean White / Light Theme, Small Crisp Typography, Professional Look
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Investor Dashboard';

$portfolioInvestments = [];
$recommendedStartups = [];
$totalInvested = 0;
$activeDealsCount = 0;
$watchlistCount = 0;

if ($db) {
    // 1. Get confirmed investments
    $invStmt = $db->prepare("
        SELECT inv.*, c.name as company_name, c.industry, c.logo_url, fr.round_name, fr.status as round_status
        FROM investments inv
        JOIN companies c ON inv.company_id = c.id
        JOIN funding_rounds fr ON inv.funding_round_id = fr.id
        WHERE inv.investor_user_id = ?
        ORDER BY inv.confirmed_at DESC
    ");
    $invStmt->execute([$user['id']]);
    $portfolioInvestments = $invStmt->fetchAll();

    foreach ($portfolioInvestments as $p) {
        $totalInvested += (float)$p['amount_invested'];
    }

    // 2. Watchlist count
    $wStmt = $db->prepare("SELECT COUNT(*) FROM watchlists WHERE investor_user_id = ?");
    $wStmt->execute([$user['id']]);
    $watchlistCount = (int)$wStmt->fetchColumn();

    // 3. Recommended Live Funding Rounds matching investor thesis
    $recStmt = $db->query("
        SELECT c.*, fr.id as round_id, fr.round_name, fr.target_amount, fr.amount_raised, fr.min_investment, fr.valuation, fr.equity_offered, fr.status as round_status
        FROM companies c
        JOIN funding_rounds fr ON c.id = fr.company_id
        WHERE fr.status IN ('LIVE', 'PARTIALLY_FUNDED')
        ORDER BY fr.created_at DESC LIMIT 3
    ");
    $recommendedStartups = $recStmt->fetchAll();
    $activeDealsCount = count($recommendedStartups);
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investor Dashboard • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #FAFAFB;
            color: #0F172A;
        }
        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-6xl w-full mx-auto" id="investor-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-1.5">
                        <span>Welcome, <?= htmlspecialchars($user['name']) ?></span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5 font-medium">Accredited Angel & Syndicate Portfolio Workspace</p>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="<?= url('investor/view.php') ?>" class="px-3.5 py-2 rounded-lg bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold shadow-sm transition flex items-center space-x-1.5">
                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>View Public Profile</span>
                    </a>
                    <a href="<?= url('investor/discover.php') ?>" class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition flex items-center space-x-1.5">
                        <i data-lucide="compass" class="w-3.5 h-3.5"></i>
                        <span>Explore Startups</span>
                    </a>
                </div>
            </div>

            <!-- Key Portfolio Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="stats-grid">
                <div class="card-clean rounded-xl p-5">
                    <div class="flex items-center justify-between text-slate-400 mb-1.5">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500">Total Invested</span>
                        <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600"><i data-lucide="wallet" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-xl font-black text-slate-900"><?= format_inr($totalInvested) ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">Across <?= count($portfolioInvestments) ?> Startups</div>
                </div>

                <div class="card-clean rounded-xl p-5">
                    <div class="flex items-center justify-between text-slate-400 mb-1.5">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500">Active Deals</span>
                        <div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600"><i data-lucide="zap" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-xl font-black text-slate-900"><?= $activeDealsCount ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">Live Rounds Open</div>
                </div>

                <div class="card-clean rounded-xl p-5">
                    <div class="flex items-center justify-between text-slate-400 mb-1.5">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500">Watchlist</span>
                        <div class="p-1.5 rounded-lg bg-purple-50 text-purple-600"><i data-lucide="bookmark" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-xl font-black text-slate-900"><?= $watchlistCount ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">Pinned Deal Rooms</div>
                </div>

                <div class="card-clean rounded-xl p-5">
                    <div class="flex items-center justify-between text-slate-400 mb-1.5">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500">Accreditation</span>
                        <div class="p-1.5 rounded-lg bg-teal-50 text-teal-600"><i data-lucide="shield-check" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="mt-1"><?= render_status_badge($user['is_verified'] ? 'VERIFIED' : 'PENDING') ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-1.5">SEBI Compliant Angel</div>
                </div>
            </div>

            <!-- Recommended Deal Flow Spotlight -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-1.5">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Live Startups Raising Now</span>
                    </h2>
                    <a href="<?= url('investor/discover.php') ?>" class="text-xs text-emerald-600 hover:text-emerald-700 font-semibold">View All →</a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <?php if (empty($recommendedStartups)): ?>
                        <div class="col-span-3 card-clean rounded-xl p-8 text-center text-slate-400 text-xs">
                            No live funding rounds currently active.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recommendedStartups as $startup): 
                            $pct = ($startup['target_amount'] ?? 0) > 0 ? round(($startup['amount_raised'] / $startup['target_amount']) * 100) : 0;
                            $hashId = hash_id_encode($startup['id']);
                        ?>
                            <div class="card-clean rounded-xl p-5 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-start justify-between mb-3">
                                        <img src="<?= $startup['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <?= htmlspecialchars($startup['industry']) ?>
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900 mb-1"><?= htmlspecialchars($startup['name']) ?></h3>
                                    <p class="text-xs text-slate-500 line-clamp-2 mb-3"><?= htmlspecialchars($startup['pitch']) ?></p>
                                </div>

                                <div class="pt-3 border-t border-slate-100">
                                    <div class="flex justify-between text-[11px] mb-1 font-semibold">
                                        <span class="text-slate-500">Target: <?= format_inr($startup['target_amount']) ?></span>
                                        <span class="text-emerald-700 font-bold"><?= $pct ?>%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mb-3 border border-slate-200">
                                        <div class="h-full bg-emerald-600 rounded-full" style="width: <?= min(100, $pct) ?>%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[10.5px] text-slate-500 mb-3">
                                        <span>Min: <strong class="text-slate-800"><?= format_inr($startup['min_investment']) ?></strong></span>
                                        <span>Val: <strong class="text-slate-800"><?= format_inr($startup['valuation']) ?></strong></span>
                                    </div>
                                    <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>" class="w-full py-2 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-center text-xs font-semibold text-slate-800 block transition">
                                        View Data Room →
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Portfolio Table -->
            <div class="card-clean rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-emerald-600"></i>
                        <span>Recent Portfolio Allocations</span>
                    </h2>
                    <a href="<?= url('investor/portfolio.php') ?>" class="text-xs text-emerald-600 hover:text-emerald-700 font-semibold">Full Portfolio →</a>
                </div>

                <?php if (empty($portfolioInvestments)): ?>
                    <div class="py-8 text-center text-slate-400 text-xs">
                        No portfolio investments made yet. Start by discovering verified startups and committing capital.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider text-[10px]">
                                    <th class="pb-2.5 font-semibold">Startup</th>
                                    <th class="pb-2.5 font-semibold">Industry</th>
                                    <th class="pb-2.5 font-semibold">Round</th>
                                    <th class="pb-2.5 font-semibold">Amount</th>
                                    <th class="pb-2.5 font-semibold">Equity %</th>
                                    <th class="pb-2.5 font-semibold">Certificate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($portfolioInvestments as $inv): ?>
                                    <tr>
                                        <td class="py-3 flex items-center space-x-2">
                                            <img src="<?= $inv['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>" class="w-6 h-6 rounded-lg object-cover border border-slate-200">
                                            <span class="font-bold text-slate-800"><?= htmlspecialchars($inv['company_name']) ?></span>
                                        </td>
                                        <td class="py-3 text-slate-500 text-[11px]"><?= htmlspecialchars($inv['industry']) ?></td>
                                        <td class="py-3 text-slate-600"><?= htmlspecialchars($inv['round_name']) ?></td>
                                        <td class="py-3 font-bold text-slate-900"><?= format_inr($inv['amount_invested']) ?></td>
                                        <td class="py-3 text-slate-800 font-bold"><?= $inv['equity_allotted_percent'] ?>%</td>
                                        <td class="py-3 font-mono text-[10.5px] text-slate-500"><?= htmlspecialchars($inv['certificate_number'] ?? 'PENDING') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#investor-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
