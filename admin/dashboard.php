<?php
/**
 * Admin Module: Master Compliance & Platform Dashboard
 * Clean, Minimalist Executive Overview (Linear / Stripe inspired)
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Dashboard Overview';

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
    try {
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
            ORDER BY al.created_at DESC LIMIT 5
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
    } catch (Exception $e) {
        // Fallback
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Overview • <?= APP_NAME ?></title>
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

        <main class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl w-full mx-auto" id="admin-main">
            
            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="admin-page-icon">
                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            Executive Overview
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">Welcome back, <?= htmlspecialchars($user['name']) ?>. Here is your portfolio pulse.</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 font-medium hidden sm:inline mr-1"><?= date('l, d M Y') ?></span>
                    <a href="<?= url('admin/subscriptions.php') ?>" class="admin-btn-secondary">
                        <i data-lucide="crown" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Subscriptions</span>
                    </a>
                    <?php if ($pendingVerifications > 0): ?>
                        <a href="<?= url('admin/verification_queue.php') ?>" class="admin-btn-primary">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            <span>Review KYC (<?= $pendingVerifications ?>)</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($pendingRounds > 0): ?>
                        <a href="<?= url('admin/funding_review.php') ?>" class="admin-btn-secondary">
                            <i data-lucide="file-check-2" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Rounds (<?= $pendingRounds ?>)</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Global Platform KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="stats-grid">
                
                <!-- Metric 1: Capital Raised -->
                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Capital Raised</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-indigo"><?= format_inr($totalVolumeRaised) ?></div>
                    <div class="admin-stat-sub">
                        <span class="text-emerald-600 font-semibold flex items-center gap-1">
                            <i data-lucide="check" class="w-3 h-3"></i> Escrow Secured
                        </span>
                    </div>
                </div>

                <!-- Metric 2: Registered Companies -->
                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Startups Enrolled</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i data-lucide="building-2" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-sky"><?= $totalCompanies ?></div>
                    <div class="admin-stat-sub">
                        <span class="text-slate-500 font-medium"><?= $totalFounders ?> registered founders</span>
                    </div>
                </div>

                <!-- Metric 3: Active Investors -->
                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Accredited Angels</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-emerald"><?= $totalInvestors ?></div>
                    <div class="admin-stat-sub">
                        <span class="text-slate-500 font-medium">Angels & institutional syndicate</span>
                    </div>
                </div>

                <!-- Metric 4: Compliance Queue -->
                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Pending Reviews</span>
                        <div class="w-8 h-8 rounded-lg <?= ($pendingVerifications + $pendingRounds) > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' ?> flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value <?= ($pendingVerifications + $pendingRounds) > 0 ? 'stat-value-amber' : 'stat-value-emerald' ?>"><?= $pendingVerifications + $pendingRounds ?></div>
                    <div class="admin-stat-sub">
                        <?php if ($pendingVerifications + $pendingRounds > 0): ?>
                            <span class="text-amber-700 font-semibold"><?= $pendingVerifications ?> KYC & <?= $pendingRounds ?> Funding</span>
                        <?php else: ?>
                            <span class="text-emerald-600 font-semibold flex items-center gap-1">
                                <i data-lucide="check" class="w-3 h-3"></i> Queue is all clear
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Two Column Section: Action Items & Activity Trail -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Left: Pending KYC Applications -->
                <div class="admin-card p-5 sm:p-6">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                <i data-lucide="shield-check" class="w-4 h-4 text-indigo-600"></i>
                                <span>KYC Verifications Awaiting Review</span>
                            </h2>
                            <p class="text-[11px] text-slate-400 mt-0.5">Founders and investors awaiting diligence approval.</p>
                        </div>
                        <a href="<?= url('admin/verification_queue.php') ?>" class="admin-btn-ghost text-xs">
                            <span>Open Queue</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>

                    <?php if (empty($pendingVerRequests)): ?>
                        <div class="py-12 text-center text-slate-400 text-xs flex flex-col items-center justify-center">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2">
                                <i data-lucide="check" class="w-5 h-5"></i>
                            </div>
                            <span class="font-bold text-slate-700">All Applicants Verified</span>
                            <span class="text-[11px] text-slate-400 mt-0.5">No pending submissions in the queue right now.</span>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-slate-100">
                            <?php foreach ($pendingVerRequests as $vr): ?>
                                <div class="py-3 flex items-center justify-between hover:bg-slate-50/70 transition px-2 rounded-xl">
                                    <div class="min-w-0 pr-3">
                                        <div class="font-semibold text-slate-900 text-xs truncate flex items-center gap-1.5">
                                            <span><?= htmlspecialchars($vr['applicant_name']) ?></span>
                                            <span class="admin-badge <?= $vr['applicant_role'] === 'founder' ? 'admin-badge-primary' : 'admin-badge-success' ?> text-[10px]">
                                                <?= ucfirst($vr['applicant_role']) ?>
                                            </span>
                                        </div>
                                        <div class="text-slate-500 text-[11px] truncate mt-0.5">
                                            <?= htmlspecialchars($vr['company_name'] ?? $vr['applicant_email']) ?>
                                        </div>
                                    </div>
                                    <a href="<?= url('admin/verification_queue.php') ?>" class="admin-btn-secondary text-[11px] py-1 px-2.5">
                                        Review
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right: Recent Security & Platform Audit Trail -->
                <div class="admin-card p-5 sm:p-6">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                <i data-lucide="history" class="w-4 h-4 text-slate-600"></i>
                                <span>Recent Platform Activity</span>
                            </h2>
                            <p class="text-[11px] text-slate-400 mt-0.5">Real-time immutable administrative audit trail.</p>
                        </div>
                        <a href="<?= url('admin/audit_logs.php') ?>" class="admin-btn-ghost text-xs">
                            <span>View All</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <?php if (empty($recentAuditLogs)): ?>
                            <div class="py-12 text-center text-slate-400 text-xs">No audit events recorded yet.</div>
                        <?php else: ?>
                            <?php foreach ($recentAuditLogs as $log): ?>
                                <div class="py-2.5 flex items-start space-x-2.5 hover:bg-slate-50/70 transition px-2 rounded-xl">
                                    <div class="w-2 h-2 rounded-full bg-slate-400 mt-1.5 flex-shrink-0"></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-medium text-slate-800 text-xs truncate"><?= htmlspecialchars(str_replace('_', ' ', $log['action'])) ?></span>
                                            <span class="text-[11px] text-slate-400 flex-shrink-0"><?= date('H:i', strtotime($log['created_at'])) ?></span>
                                        </div>
                                        <div class="text-slate-500 text-[11px] truncate mt-0.5"><?= htmlspecialchars($log['details'] ?? 'System event') ?></div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><?= htmlspecialchars($log['actor_name'] ?? 'System') ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#admin-main", { duration: 0.3, y: 8, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
