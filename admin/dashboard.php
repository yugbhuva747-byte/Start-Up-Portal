<?php
/**
 * Admin Module: Master Compliance & Platform Dashboard
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Master Compliance & Governance';

$totalUsers = 0;
$totalFounders = 0;
$totalInvestors = 0;
$totalCompanies = 0;
$totalVolumeRaised = 0;
$pendingVerifications = 0;
$pendingRounds = 0;
$recentAuditLogs = [];
$pendingVerRequests = [];

if ($db) {
    $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalFounders = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'founder'")->fetchColumn();
    $totalInvestors = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'investor'")->fetchColumn();
    $totalCompanies = (int)$db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
    $totalVolumeRaised = (float)$db->query("SELECT SUM(amount_raised) FROM funding_rounds")->fetchColumn() ?: 0;
    $pendingVerifications = (int)$db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'")->fetchColumn();
    $pendingRounds = (int)$db->query("SELECT COUNT(*) FROM funding_rounds WHERE status IN ('SUBMITTED', 'UNDER_REVIEW')")->fetchColumn();

    // Recent Audit Logs
    $aStmt = $db->query("
        SELECT al.*, u.name as actor_name, u.role as actor_role
        FROM audit_logs al
        LEFT JOIN users u ON al.actor_user_id = u.id
        ORDER BY al.created_at DESC LIMIT 8
    ");
    $recentAuditLogs = $aStmt->fetchAll();

    // Pending Verification Requests
    $vrStmt = $db->query("
        SELECT vr.*, u.name as applicant_name, u.role as applicant_role, u.email as applicant_email, c.name as company_name
        FROM verification_requests vr
        JOIN users u ON vr.user_id = u.id
        LEFT JOIN companies c ON vr.company_id = c.id
        WHERE vr.status = 'pending'
        ORDER BY vr.created_at DESC LIMIT 5
    ");
    $pendingVerRequests = $vrStmt->fetchAll();
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin & Compliance Dashboard • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="admin-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-lg md:text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-1.5">
                        <span>Governance & Compliance Dashboard</span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">SEBI Regulatory Framework, DigiLocker Verification & Escrow Oversight</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="<?= url('admin/verification_queue.php') ?>" class="w-full sm:w-auto justify-center px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition flex items-center space-x-1.5">
                        <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
                        <span>Process KYC Queue (<?= $pendingVerifications ?>)</span>
                    </a>
                </div>
            </div>

            <!-- Global Platform KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="stats-grid">
                <div class="card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">Total Volume Raised</span>
                        <div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600"><i data-lucide="dollar-sign" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-xl font-black text-slate-900"><?= format_inr($totalVolumeRaised) ?></div>
                    <div class="text-[10.5px] text-emerald-600 font-semibold mt-1 flex items-center space-x-1">
                        <i data-lucide="check" class="w-3 h-3"></i>
                        <span>Escrow Reconciled</span>
                    </div>
                </div>

                <div class="card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">KYC Queue</span>
                        <div class="p-1.5 rounded-lg bg-amber-50 text-amber-600"><i data-lucide="shield-alert" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-xl font-black text-slate-900"><?= $pendingVerifications ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-1">Awaiting Compliance Review</div>
                </div>

                <div class="card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">Companies</span>
                        <div class="p-1.5 rounded-lg bg-purple-50 text-purple-600"><i data-lucide="building-2" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-xl font-black text-slate-900"><?= $totalCompanies ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-1"><?= $totalFounders ?> Verified Founders</div>
                </div>

                <div class="card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">Active Investors</span>
                        <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600"><i data-lucide="trending-up" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-xl font-black text-slate-900"><?= $totalInvestors ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-1">Angels & VC Partners</div>
                </div>
            </div>

            <!-- Two Column: Verification Queue & Live Audit Logs -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Pending Verifications -->
                <div class="card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3.5 pb-2.5 border-b border-slate-100">
                        <h2 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5 uppercase tracking-wider">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-500"></i>
                            <span>Pending KYC Verification Queue</span>
                        </h2>
                        <a href="<?= url('admin/verification_queue.php') ?>" class="text-[11px] text-indigo-600 hover:text-indigo-700 font-semibold">Full Queue →</a>
                    </div>

                    <?php if (empty($pendingVerRequests)): ?>
                        <div class="py-10 text-center text-slate-400 text-xs">
                            No pending KYC requests in queue. All applicants are verified.
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-slate-100 text-xs">
                            <?php foreach ($pendingVerRequests as $vr): ?>
                                <div class="py-3 flex items-center justify-between hover:bg-slate-50/60 transition px-1 rounded-lg">
                                    <div>
                                        <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($vr['applicant_name']) ?> (<?= ucfirst($vr['applicant_role']) ?>)</div>
                                        <div class="text-slate-500 text-[11px]"><?= htmlspecialchars($vr['company_name'] ?? $vr['applicant_email']) ?> • <?= htmlspecialchars($vr['provider_name']) ?></div>
                                    </div>
                                    <a href="<?= url('admin/verification_queue.php') ?>" class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-[11px] transition shadow-sm">
                                        Review
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Immutable Security Logs -->
                <div class="card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3.5 pb-2.5 border-b border-slate-100">
                        <h2 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5 uppercase tracking-wider">
                            <i data-lucide="history" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Live Platform Audit Trail</span>
                        </h2>
                        <a href="<?= url('admin/audit_logs.php') ?>" class="text-[11px] text-indigo-600 hover:text-indigo-700 font-semibold">View All →</a>
                    </div>

                    <div class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($recentAuditLogs as $log): ?>
                            <div class="py-2.5 flex items-start space-x-2.5 hover:bg-slate-50/60 transition px-1 rounded-lg">
                                <div class="w-1.5 h-1.5 rounded-full bg-indigo-600 mt-1.5 flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <span class="font-mono font-bold text-slate-800 text-[11px]"><?= htmlspecialchars($log['action']) ?></span>
                                        <span class="text-[10px] text-slate-400"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                                    </div>
                                    <div class="text-slate-500 text-[10.5px] truncate mt-0.5"><?= htmlspecialchars($log['details'] ?? '') ?></div>
                                    <div class="text-[10px] text-slate-400 mt-0.5"><?= htmlspecialchars($log['actor_name'] ?? 'System') ?> • <?= htmlspecialchars($log['ip_address']) ?></div>
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
        gsap.from("#admin-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
        gsap.from("#stats-grid > div", { duration: 0.35, y: 10, opacity: 0, stagger: 0.05, ease: "power2.out" });
    </script>
</body>
</html>
