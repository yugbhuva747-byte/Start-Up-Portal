<?php
/**
 * Admin Module: Reports & Platform Analytics
 * Aggregate platform stats, funding metrics, user trends, verification pipeline
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Platform Reports & Analytics';

$stats = [
    'total_users' => 0, 'founders' => 0, 'investors' => 0,
    'companies' => 0, 'verified_companies' => 0,
    'funding_rounds' => 0, 'live_rounds' => 0,
    'total_raised' => 0, 'total_invested' => 0,
    'avg_ticket' => 0, 'total_transactions' => 0,
    'pending_kyc' => 0, 'approved_kyc' => 0, 'rejected_kyc' => 0
];
$topStartups = [];
$recentUsers = [];
$industryStats = [];
$monthlyRegistrations = [];
$recentAudit = [];

if ($db) {
    // User counts
    $stats['total_users'] = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['founders'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'founder'")->fetchColumn();
    $stats['investors'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'investor'")->fetchColumn();
    
    // Company counts
    $stats['companies'] = (int)$db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
    $stats['verified_companies'] = (int)$db->query("SELECT COUNT(*) FROM companies WHERE verified_status = 'verified'")->fetchColumn();
    
    // Funding counts
    $stats['funding_rounds'] = (int)$db->query("SELECT COUNT(*) FROM funding_rounds")->fetchColumn();
    $stats['live_rounds'] = (int)$db->query("SELECT COUNT(*) FROM funding_rounds WHERE status IN ('LIVE', 'PARTIALLY_FUNDED')")->fetchColumn();
    
    // Financial aggregates
    $stats['total_raised'] = (float)($db->query("SELECT COALESCE(SUM(amount_raised), 0) FROM funding_rounds")->fetchColumn());
    $stats['total_invested'] = (float)($db->query("SELECT COALESCE(SUM(amount_invested), 0) FROM investments")->fetchColumn());
    $invCount = (int)$db->query("SELECT COUNT(*) FROM investments")->fetchColumn();
    $stats['avg_ticket'] = $invCount > 0 ? $stats['total_invested'] / $invCount : 0;
    $stats['total_transactions'] = (int)$db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    
    // KYC pipeline
    $stats['pending_kyc'] = (int)$db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'")->fetchColumn();
    $stats['approved_kyc'] = (int)$db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'verified'")->fetchColumn();
    $stats['rejected_kyc'] = (int)$db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'rejected'")->fetchColumn();
    
    // Top startups by amount raised
    $topStartups = $db->query("
        SELECT c.name, c.industry, c.logo_url, c.stage, c.verified_status,
               COALESCE(SUM(fr.amount_raised), 0) as total_raised,
               COUNT(DISTINCT fr.id) as round_count
        FROM companies c
        LEFT JOIN funding_rounds fr ON c.id = fr.company_id
        GROUP BY c.id
        ORDER BY total_raised DESC
        LIMIT 8
    ")->fetchAll();
    
    // Recent users
    $recentUsers = $db->query("
        SELECT name, email, role, avatar_url, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 6
    ")->fetchAll();
    
    // Industry distribution
    $industryStats = $db->query("
        SELECT industry, COUNT(*) as count, 
               COALESCE(SUM(fr_data.total_raised), 0) as total_raised
        FROM companies c
        LEFT JOIN (
            SELECT company_id, SUM(amount_raised) as total_raised 
            FROM funding_rounds GROUP BY company_id
        ) fr_data ON c.id = fr_data.company_id
        GROUP BY industry
        ORDER BY count DESC
        LIMIT 8
    ")->fetchAll();
    
    // Monthly user registrations (last 6 months)
    $monthlyRegistrations = $db->query("
        SELECT DATE_FORMAT(created_at, '%b %Y') as month, 
               COUNT(*) as count,
               SUM(CASE WHEN role = 'founder' THEN 1 ELSE 0 END) as founders,
               SUM(CASE WHEN role = 'investor' THEN 1 ELSE 0 END) as investors
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY MIN(created_at) ASC
    ")->fetchAll();
    
    // Recent audit entries
    $recentAudit = $db->query("
        SELECT al.*, u.name as actor_name
        FROM audit_logs al
        LEFT JOIN users u ON al.actor_user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 8
    ")->fetchAll();
}

$flash = get_flash();
$kycTotal = $stats['pending_kyc'] + $stats['approved_kyc'] + $stats['rejected_kyc'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean { background: #fff; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.03); }
        .mini-bar { height: 24px; border-radius: 4px; transition: width 0.8s ease; }
        .donut-ring { fill: none; stroke-width: 4; stroke-linecap: round; }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="reports-main">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Platform Reports & Analytics</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Aggregate platform metrics, funding activity, user trends, and verification pipeline health.</p>
                </div>
                <div class="flex items-center space-x-2 text-[10px] text-slate-400 font-medium">
                    <i data-lucide="clock" class="w-3 h-3"></i>
                    <span>Last updated: <?= date('d M Y, H:i') ?></span>
                </div>
            </div>

            <!-- Platform KPI Row -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <?php
                $kpis = [
                    ['label' => 'Total Users', 'value' => $stats['total_users'], 'icon' => 'users', 'color' => 'indigo', 'fmt' => false],
                    ['label' => 'Founders', 'value' => $stats['founders'], 'icon' => 'rocket', 'color' => 'emerald', 'fmt' => false],
                    ['label' => 'Investors', 'value' => $stats['investors'], 'icon' => 'briefcase', 'color' => 'purple', 'fmt' => false],
                    ['label' => 'Companies', 'value' => $stats['companies'], 'icon' => 'building-2', 'color' => 'amber', 'fmt' => false],
                    ['label' => 'Total Raised', 'value' => $stats['total_raised'], 'icon' => 'wallet', 'color' => 'teal', 'fmt' => true],
                    ['label' => 'Avg Ticket', 'value' => $stats['avg_ticket'], 'icon' => 'trending-up', 'color' => 'rose', 'fmt' => true],
                ];
                foreach ($kpis as $kpi):
                ?>
                <div class="card-clean rounded-xl p-4">
                    <div class="flex items-center space-x-1.5 mb-1.5">
                        <div class="p-1 rounded-md bg-<?= $kpi['color'] ?>-50 text-<?= $kpi['color'] ?>-600">
                            <i data-lucide="<?= $kpi['icon'] ?>" class="w-3 h-3"></i>
                        </div>
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider"><?= $kpi['label'] ?></span>
                    </div>
                    <div class="text-lg font-black text-slate-900">
                        <?= $kpi['fmt'] ? format_inr($kpi['value']) : number_format($kpi['value']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Funding & Verification Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Funding Overview -->
                <div class="card-clean rounded-2xl p-6">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center space-x-1.5">
                        <i data-lucide="bar-chart-3" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Funding Overview</span>
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg border border-slate-100">
                            <span class="text-xs text-slate-600 font-medium">Total Rounds</span>
                            <span class="text-sm font-black text-slate-900"><?= $stats['funding_rounds'] ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                            <span class="text-xs text-emerald-700 font-medium">Live Rounds</span>
                            <span class="text-sm font-black text-emerald-600"><?= $stats['live_rounds'] ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                            <span class="text-xs text-indigo-700 font-medium">Total Invested</span>
                            <span class="text-sm font-black text-indigo-600"><?= format_inr($stats['total_invested']) ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg border border-purple-100">
                            <span class="text-xs text-purple-700 font-medium">Transactions</span>
                            <span class="text-sm font-black text-purple-600"><?= number_format($stats['total_transactions']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Verification Pipeline -->
                <div class="card-clean rounded-2xl p-6">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center space-x-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Verification Pipeline</span>
                    </h3>
                    <div class="space-y-3">
                        <?php 
                        $pipeItems = [
                            ['label' => 'Pending Review', 'value' => $stats['pending_kyc'], 'color' => 'amber', 'icon' => 'clock'],
                            ['label' => 'Approved / Verified', 'value' => $stats['approved_kyc'], 'color' => 'emerald', 'icon' => 'check-circle'],
                            ['label' => 'Rejected', 'value' => $stats['rejected_kyc'], 'color' => 'rose', 'icon' => 'x-circle'],
                        ];
                        foreach ($pipeItems as $pi):
                            $barPct = $kycTotal > 0 ? round(($pi['value'] / $kycTotal) * 100) : 0;
                        ?>
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-semibold text-slate-700 flex items-center space-x-1">
                                    <i data-lucide="<?= $pi['icon'] ?>" class="w-3 h-3 text-<?= $pi['color'] ?>-500"></i>
                                    <span><?= $pi['label'] ?></span>
                                </span>
                                <span class="text-xs font-bold text-slate-900"><?= $pi['value'] ?></span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-<?= $pi['color'] ?>-500 rounded-full transition-all duration-700" style="width: <?= $barPct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($stats['pending_kyc'] > 0): ?>
                        <a href="<?= url('admin/verification_queue.php') ?>" class="mt-4 inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-[11px] font-semibold hover:bg-amber-100 transition">
                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            <span>Review Pending Queue</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Industry Distribution -->
                <div class="card-clean rounded-2xl p-6">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center space-x-1.5">
                        <i data-lucide="layers" class="w-3.5 h-3.5 text-purple-600"></i>
                        <span>Industry Distribution</span>
                    </h3>
                    <div class="space-y-2.5">
                        <?php 
                        $indColors = ['indigo', 'emerald', 'purple', 'amber', 'rose', 'teal', 'blue', 'orange'];
                        foreach ($industryStats as $idx => $is): 
                            $color = $indColors[$idx % count($indColors)];
                        ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="w-2 h-2 rounded-full bg-<?= $color ?>-500 flex-shrink-0"></span>
                                <span class="text-xs font-medium text-slate-700"><?= htmlspecialchars($is['industry']) ?></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-[10px] text-slate-400"><?= $is['count'] ?> co.</span>
                                <span class="text-xs font-bold text-slate-900"><?= format_inr($is['total_raised']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Top Startups Table -->
            <div class="card-clean rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                        <i data-lucide="trophy" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Top Startups by Capital Raised</span>
                    </h3>
                    <a href="<?= url('admin/companies.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">View All →</a>
                </div>
                <?php if (empty($topStartups)): ?>
                    <div class="py-8 text-center text-xs text-slate-400">No startup data available yet.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    <th class="pb-2.5">#</th>
                                    <th class="pb-2.5">Startup</th>
                                    <th class="pb-2.5">Industry</th>
                                    <th class="pb-2.5">Stage</th>
                                    <th class="pb-2.5">Rounds</th>
                                    <th class="pb-2.5">Total Raised</th>
                                    <th class="pb-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($topStartups as $rank => $s): ?>
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3 text-slate-400 font-bold"><?= $rank + 1 ?></td>
                                        <td class="py-3 flex items-center space-x-2.5">
                                            <img src="<?= $s['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>" class="w-7 h-7 rounded-lg object-cover border border-slate-200">
                                            <span class="font-bold text-slate-900"><?= htmlspecialchars($s['name']) ?></span>
                                        </td>
                                        <td class="py-3">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-medium border border-slate-200"><?= htmlspecialchars($s['industry']) ?></span>
                                        </td>
                                        <td class="py-3 text-slate-600 font-medium"><?= htmlspecialchars($s['stage']) ?></td>
                                        <td class="py-3 font-bold text-slate-800"><?= $s['round_count'] ?></td>
                                        <td class="py-3 font-black text-emerald-600"><?= format_inr($s['total_raised']) ?></td>
                                        <td class="py-3"><?= render_status_badge(strtoupper($s['verified_status'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bottom Row: Recent Users + Recent Audit -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Recent User Registrations -->
                <div class="card-clean rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="user-plus" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Recent Registrations</span>
                        </h3>
                        <a href="<?= url('admin/users.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">View All →</a>
                    </div>
                    <div class="space-y-2.5">
                        <?php foreach ($recentUsers as $ru): ?>
                            <div class="flex items-center justify-between p-2.5 rounded-lg hover:bg-slate-50 transition">
                                <div class="flex items-center space-x-2.5">
                                    <img src="<?= $ru['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80' ?>" class="w-7 h-7 rounded-full object-cover border border-slate-200">
                                    <div>
                                        <div class="text-xs font-bold text-slate-900"><?= htmlspecialchars($ru['name']) ?></div>
                                        <div class="text-[10px] text-slate-400"><?= htmlspecialchars($ru['email']) ?></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <?= render_status_badge(strtoupper($ru['role'])) ?>
                                    <div class="text-[10px] text-slate-400 mt-0.5"><?= date('d M', strtotime($ru['created_at'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Audit Trail -->
                <div class="card-clean rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="history" class="w-3.5 h-3.5 text-purple-600"></i>
                            <span>Recent Audit Activity</span>
                        </h3>
                        <a href="<?= url('admin/audit_logs.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">View All →</a>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($recentAudit as $al): ?>
                            <div class="flex items-start space-x-2.5 p-2 rounded-lg hover:bg-slate-50 transition">
                                <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i data-lucide="activity" class="w-3 h-3 text-slate-500"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[11px] text-slate-700">
                                        <strong class="text-slate-900"><?= htmlspecialchars($al['actor_name'] ?? 'System') ?></strong>
                                        <span class="text-slate-500"><?= htmlspecialchars($al['action']) ?></span>
                                        <span class="text-indigo-600 font-medium"><?= htmlspecialchars($al['entity_type']) ?></span>
                                    </div>
                                    <div class="text-[10px] text-slate-400"><?= date('d M Y, H:i', strtotime($al['created_at'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#reports-main > *", { duration: 0.5, y: 15, opacity: 0, stagger: 0.08, ease: "power2.out" });
    </script>
</body>
</html>
