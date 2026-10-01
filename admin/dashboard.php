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

    $totalUsers = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalFounders = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'founder'")->fetchColumn();
    $totalInvestors = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'investor'")->fetchColumn();
    $totalCompanies = (int) $db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
    $totalVolumeRaised = (float) $db->query("SELECT SUM(amount_raised) FROM funding_rounds")->fetchColumn() ?: 0;
    $pendingVerifications = (int) $db->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'")->fetchColumn();
    $pendingRounds = (int) $db->query("SELECT COUNT(*) FROM funding_rounds WHERE status IN ('SUBMITTED', 'UNDER_REVIEW')")->fetchColumn();


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

    <title>Admin & Compliance Dashboard • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/admin/head.php'; ?>
    <style>
        body {
            font-family: "Vay Portal", Sans-serif;
        }

        .card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body class="bg-[#F4F2EE] text-slate-900 flex min-h-screen dark:bg-[#0B0F19] dark:text-slate-100">


    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>


        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="admin-main">

            <?php if ($flash): ?>
                <div
                    class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2">

                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>


            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1
                        class="text-lg md:text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-1.5">
                        <span>Governance & Compliance Dashboard</span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">SEBI Regulatory Framework, DigiLocker Verification & Escrow
                        Oversight</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="<?= url('admin/verification_queue.php') ?>"
                        class="w-full sm:w-auto justify-center px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm shadow-blue-600/20 transition flex items-center space-x-1.5">
                        <i data-lucide="check-square" class="w-4 h-4"></i>
                        <span>Process KYC Queue (<?= $pendingVerifications ?>)</span>
                    </a>

                </div>
            </div>

            <!-- Global Platform KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="stats-grid">

                <div class="card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">Total Volume
                            Raised</span>
                        <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600"><i data-lucide="dollar-sign"
                                class="w-4 h-4"></i></div>

                    </div>
                    <div class="admin-stat-value stat-value-indigo"><?= format_inr($totalVolumeRaised) ?></div>
                    <div class="admin-stat-sub">
                        <span class="text-emerald-600 font-semibold flex items-center gap-1">
                            <i data-lucide="check" class="w-3 h-3"></i> Escrow Secured
                        </span>
                    </div>
                </div>


                <div class="card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">KYC Queue</span>
                        <div class="p-1.5 rounded-lg bg-amber-50 text-amber-600"><i data-lucide="shield-alert"
                                class="w-4 h-4"></i></div>

                    </div>
                </div>


                <div class="card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">Companies</span>
                        <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600"><i data-lucide="building-2"
                                class="w-4 h-4"></i></div>

                    </div>
                </div>


                <div class="card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">Active
                            Investors</span>
                        <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600"><i data-lucide="trending-up"
                                class="w-4 h-4"></i></div>

                    </div>
                </div>

            </div>


            <!-- Two Column: Verification Queue & Live Audit Logs -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                <!-- Pending Verifications Section Card -->
                <div class="card-clean rounded-2xl p-5 sm:p-6 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800 cursor-pointer select-none group"
                        onclick="toggleCollapsibleCard('kyc-section-content', this)"
                        title="Click to expand/collapse section">
                        <div class="flex items-center space-x-2.5 min-w-0 pr-2">
                            <div
                                class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="clock" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider truncate">
                                    Pending KYC Verification Queue
                                </h2>
                                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Applicant identification review queue</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800 whitespace-nowrap">
                                <?= count($pendingVerRequests) ?> Pending
                            </span>
                            <a href="<?= url('admin/verification_queue.php') ?>" onclick="event.stopPropagation()"
                                class="inline-flex items-center space-x-1 text-[11px] text-blue-600 hover:text-blue-700 font-bold px-2 py-1 rounded-lg hover:bg-blue-50 dark:hover:bg-slate-800 transition whitespace-nowrap">
                                <span>Full Queue</span>
                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <i data-lucide="chevron-down" data-chevron
                                    class="w-4 h-4 transition-transform duration-300"></i>
                            </div>
                        </div>
                    </div>

                    <div id="kyc-section-content" class="transition-all duration-300">
                        <?php if (empty($pendingVerRequests)): ?>
                            <div class="py-8 text-center text-slate-400 text-xs">
                                <i data-lucide="check-circle-2" class="w-8 h-8 mx-auto text-emerald-500 mb-2"></i>
                                No pending KYC requests in queue. All applicants are verified.
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-slate-100 text-xs" id="kyc-list-wrapper">
                                <?php foreach ($pendingVerRequests as $idx => $vr): ?>
                                    <div
                                        class="py-3 flex items-center justify-between hover:bg-slate-50/60 transition px-2 rounded-xl <?= $idx >= 3 ? 'kyc-extra-item hidden' : '' ?>">
                                        <div class="min-w-0 pr-3">
                                            <div
                                                class="font-bold text-slate-900 text-xs flex items-center space-x-1.5 truncate">
                                                <span><?= htmlspecialchars($vr['applicant_name']) ?></span>
                                                <span
                                                    class="text-[9.5px] px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 font-semibold uppercase"><?= ucfirst($vr['applicant_role']) ?></span>
                                            </div>
                                            <div class="text-slate-500 text-[11px] truncate mt-0.5">
                                                <?= htmlspecialchars($vr['company_name'] ?? $vr['applicant_email']) ?> •
                                                <?= htmlspecialchars($vr['provider_name']) ?></div>
                                        </div>
                                        <a href="<?= url('admin/verification_queue.php') ?>"
                                            class="flex-shrink-0 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-[11px] transition shadow-xs flex items-center space-x-1">
                                            <span>Review</span>
                                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (count($pendingVerRequests) > 3): ?>
                                <button type="button" id="btn-toggle-kyc"
                                    onclick="toggleListItems('kyc-extra-item', 'btn-toggle-kyc', 'Show More Requests (+<?= count($pendingVerRequests) - 3 ?>)', 'Show Less')"
                                    class="w-full mt-3 py-2 px-3 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-slate-800/50 text-blue-600 dark:text-blue-400 font-semibold text-xs transition flex items-center justify-center space-x-1.5">
                                    <i data-lucide="chevrons-down" class="w-3.5 h-3.5"></i>
                                    <span>Show More Requests (+<?= count($pendingVerRequests) - 3 ?>)</span>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Immutable Security Logs Section Card -->
                <div class="card-clean rounded-2xl p-5 sm:p-6 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800 cursor-pointer select-none group"
                        onclick="toggleCollapsibleCard('audit-section-content', this)"
                        title="Click to expand/collapse section">
                        <div class="flex items-center space-x-2.5 min-w-0 pr-2">
                            <div
                                class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="history" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider truncate">
                                    Live Platform Audit Trail
                                </h2>
                                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Real-time immutable activity stream</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800 whitespace-nowrap">
                                <?= count($recentAuditLogs) ?> Recent Logs
                            </span>
                            <a href="<?= url('admin/audit_logs.php') ?>" onclick="event.stopPropagation()"
                                class="inline-flex items-center space-x-1 text-[11px] text-blue-600 hover:text-blue-700 font-bold px-2 py-1 rounded-lg hover:bg-blue-50 dark:hover:bg-slate-800 transition whitespace-nowrap">
                                <span>View All</span>
                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <i data-lucide="chevron-down" data-chevron
                                    class="w-4 h-4 transition-transform duration-300"></i>
                            </div>
                        </div>
                    </div>

                    <div id="audit-section-content" class="transition-all duration-300">
                        <?php if (empty($recentAuditLogs)): ?>
                            <div class="py-8 text-center text-slate-400 text-xs">
                                No security logs recorded yet.
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-slate-100 text-xs" id="audit-list-wrapper">
                                <?php foreach ($recentAuditLogs as $idx => $log): ?>
                                    <div
                                        class="py-2.5 px-2 rounded-xl hover:bg-slate-50/60 transition <?= $idx >= 4 ? 'audit-extra-item hidden' : '' ?>">
                                        <div class="flex items-start space-x-2.5 cursor-pointer"
                                            onclick="toggleLogDetail('log-detail-<?= $idx ?>', this)"
                                            title="Click to view full event details">
                                            <div class="w-2 h-2 rounded-full bg-blue-600 mt-1.5 flex-shrink-0"></div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <span
                                                        class="font-mono font-bold text-slate-800 text-[11px]"><?= htmlspecialchars($log['action']) ?></span>
                                                    <span
                                                        class="text-[10px] text-slate-400"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                                                </div>
                                                <div class="text-slate-500 text-[10.5px] truncate mt-0.5">
                                                    <?= htmlspecialchars($log['details'] ?? 'No details provided') ?></div>
                                                <div
                                                    class="text-[10px] text-slate-400 mt-0.5 flex items-center justify-between">
                                                    <span><?= htmlspecialchars($log['actor_name'] ?? 'System') ?> •
                                                        <?= htmlspecialchars($log['ip_address']) ?></span>
                                                    <span
                                                        class="text-blue-600 font-semibold text-[10px] hover:underline flex items-center space-x-0.5">
                                                        <span>Details</span>
                                                        <i data-lucide="chevron-down"
                                                            class="w-3 h-3 transition-transform duration-200"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Expandable Full Details Drawer -->
                                        <div id="log-detail-<?= $idx ?>"
                                            class="hidden mt-2 p-2.5 rounded-lg bg-slate-50/80 dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700 text-[11px] space-y-1">
                                            <div class="text-slate-700 dark:text-slate-200 font-semibold">Full Event Payload:
                                            </div>
                                            <div
                                                class="font-mono text-[10px] text-slate-600 dark:text-slate-300 break-all bg-white dark:bg-slate-900 p-2 rounded border border-slate-200 dark:border-slate-800">
                                                <?= htmlspecialchars($log['details'] ?? 'None') ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400 pt-0.5 flex justify-between">
                                                <span>Entity: <?= htmlspecialchars($log['entity_type'] ?? 'N/A') ?>
                                                    #<?= htmlspecialchars($log['entity_id'] ?? '—') ?></span>
                                                <span>Time: <?= htmlspecialchars($log['created_at']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (count($recentAuditLogs) > 4): ?>
                                <button type="button" id="btn-toggle-audit"
                                    onclick="toggleListItems('audit-extra-item', 'btn-toggle-audit', 'Show More Logs (+<?= count($recentAuditLogs) - 4 ?>)', 'Show Less')"
                                    class="w-full mt-3 py-2 px-3 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-slate-800/50 text-blue-600 dark:text-blue-400 font-semibold text-xs transition flex items-center justify-center space-x-1.5">
                                    <i data-lucide="chevrons-down" class="w-3.5 h-3.5"></i>
                                    <span>Show More Logs (+<?= count($recentAuditLogs) - 4 ?>)</span>
                                </button>
                            <?php endif; ?>

                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();

        gsap.from("#admin-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
        gsap.from("#stats-grid > div", { duration: 0.35, y: 10, opacity: 0, stagger: 0.05, ease: "power2.out" });

        // Collapsible Card Section Toggle
        function toggleCollapsibleCard(contentId, headerEl) {
            const content = document.getElementById(contentId);
            if (!content) return;
            const isHidden = content.classList.contains('hidden');
            if (isHidden) {
                content.classList.remove('hidden');
                if (headerEl) {
                    const icon = headerEl.querySelector('[data-chevron]');
                    if (icon) icon.classList.remove('rotate-180');
                }
            } else {
                content.classList.add('hidden');
                if (headerEl) {
                    const icon = headerEl.querySelector('[data-chevron]');
                    if (icon) icon.classList.add('rotate-180');
                }
            }
        }

        // Show More / Show Less Items Engine
        function toggleListItems(itemClass, btnId, moreText, lessText) {
            const items = document.querySelectorAll('.' + itemClass);
            const btn = document.getElementById(btnId);
            if (!items.length || !btn) return;
            const isExpanded = !items[0].classList.contains('hidden');
            items.forEach(el => {
                if (isExpanded) {
                    el.classList.add('hidden');
                } else {
                    el.classList.remove('hidden');
                }
            });
            btn.innerHTML = isExpanded
                ? `<i data-lucide="chevrons-down" class="w-3.5 h-3.5"></i><span>${moreText}</span>`
                : `<i data-lucide="chevrons-up" class="w-3.5 h-3.5"></i><span>${lessText}</span>`;
            if (window.lucide) lucide.createIcons();
        }

        // Expandable Log Detail Drawer
        function toggleLogDetail(detailId, rowEl) {
            const detailBox = document.getElementById(detailId);
            if (!detailBox) return;
            const isHidden = detailBox.classList.contains('hidden');
            if (isHidden) {
                detailBox.classList.remove('hidden');
                if (rowEl) {
                    const arrow = rowEl.querySelector('.lucide-chevron-down');
                    if (arrow) arrow.classList.add('rotate-180');
                }
            } else {
                detailBox.classList.add('hidden');
                if (rowEl) {
                    const arrow = rowEl.querySelector('.lucide-chevron-down');
                    if (arrow) arrow.classList.remove('rotate-180');
                }
            }
        }

    </script>
</body>

</html>