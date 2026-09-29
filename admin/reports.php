<?php
/**
 * Admin Module: Reports & Platform Analytics
 * Clean, Minimalist Aggregate Platform Stats & Performance Metrics
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Reports & Analytics';

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
        LIMIT 6
    ")->fetchAll();
    
    // Recent users
    $recentUsers = $db->query("
        SELECT name, email, role, avatar_url, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
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
        LIMIT 6
    ")->fetchAll();
    
    // Recent audit entries
    $recentAudit = $db->query("
        SELECT al.*, u.name as actor_name
        FROM audit_logs al
        LEFT JOIN users u ON al.actor_user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 5
    ")->fetchAll();
}

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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F8FAFC] text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl w-full mx-auto" id="reports-main">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="admin-page-icon">
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Platform Reports & Analytics</h1>
                        <p class="text-xs text-slate-500 mt-0.5">Platform metrics, funding activity, user trends, and verification pipeline health.</p>
                    </div>
                </div>
                <div class="text-xs text-slate-400 font-medium flex items-center space-x-1.5">
                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Updated: <?= date('d M Y, H:i') ?></span>
                </div>
            </div>

            <!-- Platform Primary KPI Row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Total Capital Raised</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-indigo"><?= format_inr($stats['total_raised']) ?></div>
                    <div class="admin-stat-sub">Across <?= $stats['funding_rounds'] ?> funding rounds</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Platform Accounts</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-sky"><?= $stats['total_users'] ?></div>
                    <div class="admin-stat-sub"><?= $stats['founders'] ?> founders • <?= $stats['investors'] ?> investors</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Startup Companies</span>
                        <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                            <i data-lucide="building-2" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-violet"><?= $stats['companies'] ?></div>
                    <div class="admin-stat-sub"><?= $stats['verified_companies'] ?> MCA/CIN verified</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Average Cheque Size</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-emerald"><?= format_inr($stats['avg_ticket']) ?></div>
                    <div class="admin-stat-sub"><?= $stats['total_transactions'] ?> completed orders</div>
                </div>
            </div>

            <!-- Funding & Pipeline Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Funding Performance -->
                <div class="admin-card p-5">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-3 mb-4 border-b border-slate-100 flex items-center space-x-1.5">
                        <i data-lucide="bar-chart-3" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Funding Activity</span>
                    </h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between items-center p-2.5 bg-slate-50 rounded-xl">
                            <span class="text-slate-600 font-medium">Total Registered Rounds</span>
                            <span class="font-bold text-slate-900"><?= $stats['funding_rounds'] ?></span>
                        </div>
                        <div class="flex justify-between items-center p-2.5 bg-emerald-50/70 border border-emerald-100 rounded-xl">
                            <span class="text-emerald-800 font-medium">Live on Discovery</span>
                            <span class="font-bold text-emerald-700"><?= $stats['live_rounds'] ?></span>
                        </div>
                        <div class="flex justify-between items-center p-2.5 bg-indigo-50/70 border border-indigo-100 rounded-xl">
                            <span class="text-indigo-800 font-medium">Total Invested</span>
                            <span class="font-bold text-indigo-700"><?= format_inr($stats['total_invested']) ?></span>
                        </div>
                        <div class="flex justify-between items-center p-2.5 bg-slate-100 rounded-xl">
                            <span class="text-slate-700 font-medium">Orders Executed</span>
                            <span class="font-bold text-slate-900"><?= number_format($stats['total_transactions']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Verification Pipeline -->
                <div class="admin-card p-5">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-3 mb-4 border-b border-slate-100 flex items-center space-x-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Verification Pipeline</span>
                    </h3>
                    <div class="space-y-3.5 text-xs">
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-medium text-slate-700">Pending Review</span>
                                <span class="font-bold text-amber-600"><?= $stats['pending_kyc'] ?></span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: <?= $kycTotal > 0 ? round(($stats['pending_kyc'] / $kycTotal) * 100) : 0 ?>%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-medium text-slate-700">Verified & Approved</span>
                                <span class="font-bold text-emerald-600"><?= $stats['approved_kyc'] ?></span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: <?= $kycTotal > 0 ? round(($stats['approved_kyc'] / $kycTotal) * 100) : 0 ?>%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-medium text-slate-700">Rejected</span>
                                <span class="font-bold text-rose-600"><?= $stats['rejected_kyc'] ?></span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-rose-500 rounded-full" style="width: <?= $kycTotal > 0 ? round(($stats['rejected_kyc'] / $kycTotal) * 100) : 0 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Industry Distribution -->
                <div class="admin-card p-5">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-3 mb-4 border-b border-slate-100 flex items-center space-x-1.5">
                        <i data-lucide="layers" class="w-3.5 h-3.5 text-slate-600"></i>
                        <span>Industry Breakdown</span>
                    </h3>
                    <div class="space-y-2 text-xs">
                        <?php foreach ($industryStats as $is): ?>
                            <div class="flex items-center justify-between py-1">
                                <span class="font-medium text-slate-700 truncate mr-2"><?= htmlspecialchars($is['industry']) ?></span>
                                <span class="font-semibold text-slate-900 font-mono text-[11px] whitespace-nowrap"><?= format_inr($is['total_raised']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Top Startups Table -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 tracking-tight">Top Funded Startups</h3>
                    <a href="<?= url('admin/companies.php') ?>" class="admin-btn-ghost text-xs">
                        <span>View Registry</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="admin-table-container">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Startup</th>
                                    <th>Industry</th>
                                    <th>Stage</th>
                                    <th>Rounds</th>
                                    <th>Total Raised</th>
                                    <th class="text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topStartups)): ?>
                                    <tr>
                                        <td colspan="7" class="py-12 text-center text-slate-400">No startup data available yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($topStartups as $rank => $s): ?>
                                        <tr>
                                            <td class="text-slate-400 font-semibold"><?= $rank + 1 ?></td>
                                            <td>
                                                <div class="flex items-center space-x-2.5">
                                                    <img src="<?= $s['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>" class="w-7 h-7 rounded-lg object-cover border border-slate-200">
                                                    <span class="font-semibold text-slate-900"><?= htmlspecialchars($s['name']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="admin-badge admin-badge-neutral text-[10px]"><?= htmlspecialchars($s['industry']) ?></span>
                                            </td>
                                            <td class="text-slate-600"><?= htmlspecialchars($s['stage']) ?></td>
                                            <td class="font-semibold text-slate-800"><?= $s['round_count'] ?></td>
                                            <td class="font-bold text-slate-900 font-mono"><?= format_inr($s['total_raised']) ?></td>
                                            <td class="text-right"><?= render_status_badge(strtoupper($s['verified_status'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#reports-main", { duration: 0.3, y: 8, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
