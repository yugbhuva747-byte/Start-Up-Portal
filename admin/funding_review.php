<?php
/**
 * Admin Module: Funding Round Review & Approvals
 * Clean, Minimalist Funding Approvals Desk
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Funding Round Approvals';

$rounds = [];
$error = '';
$flash = get_flash();

$totalRounds = 0;
$pendingReviewCount = 0;
$liveCount = 0;
$totalTargetCapital = 0;

if ($db) {
    try {
        $stmt = $db->query("
            SELECT fr.*, c.name as company_name, c.cin_number, c.industry, c.stage, u.name as founder_name
            FROM funding_rounds fr
            JOIN companies c ON fr.company_id = c.id
            LEFT JOIN company_founders cf ON c.id = cf.company_id
            LEFT JOIN users u ON cf.user_id = u.id
            ORDER BY FIELD(fr.status, 'SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'LIVE', 'PARTIALLY_FUNDED', 'FULLY_FUNDED', 'CLOSED', 'REJECTED'), fr.created_at DESC
        ");
        $rounds = $stmt->fetchAll();

        foreach ($rounds as $r) {
            $totalRounds++;
            $totalTargetCapital += (float)$r['target_amount'];
            if (in_array($r['status'], ['SUBMITTED', 'UNDER_REVIEW'])) {
                $pendingReviewCount++;
            } elseif (in_array($r['status'], ['LIVE', 'APPROVED', 'PARTIALLY_FUNDED'])) {
                $liveCount++;
            }
        }
    } catch (Exception $e) {
        // Fallback
    }
}

// Handle Status Change POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $roundId = (int)($_POST['round_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';

        if ($roundId > 0 && in_array($newStatus, ['LIVE', 'APPROVED', 'REJECTED', 'CLOSED'])) {
            $db->prepare("UPDATE funding_rounds SET status = ? WHERE id = ?")->execute([$newStatus, $roundId]);
            log_audit($user['id'], 'UPDATE_ROUND_STATUS', 'funding_rounds', $roundId, "Admin updated round status to {$newStatus}");

            // Automatically dispatch status update email to registered founders
            send_funding_round_status_email($db, $roundId, $newStatus);

            set_flash('success', "Funding round status updated to {$newStatus}, and notification emails dispatched to founders!");
            header('Location: ' . url('admin/funding_review.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funding Approvals • <?= APP_NAME ?></title>

    <?php include __DIR__ . '/../includes/admin/head.php'; ?>
    <style>
        body { font-family: "Vay Portal", Sans-serif; }
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


        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="rounds-admin-main">

            
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
                        <i data-lucide="file-check-2" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            Funding Round Approvals
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">Review valuation justifications, cap table terms, and publish rounds to investors.</p>
                    </div>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" id="stats-grid">
                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Total Rounds</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-sky"><?= $totalRounds ?></div>
                    <div class="admin-stat-sub">Across all startups</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Awaiting Review</span>
                        <div class="w-8 h-8 rounded-lg <?= $pendingReviewCount > 0 ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-400' ?> flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value <?= $pendingReviewCount > 0 ? 'stat-value-amber' : '' ?>"><?= $pendingReviewCount ?></div>
                    <div class="admin-stat-sub">Needs admin decision</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Live on Portal</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-emerald"><?= $liveCount ?></div>
                    <div class="admin-stat-sub">Active for investment</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Target Capital</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-indigo"><?= format_inr($totalTargetCapital) ?></div>
                    <div class="admin-stat-sub">Cumulative funding asks</div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="admin-card p-3 sm:p-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <!-- Search Input -->
                <div class="admin-search-wrapper w-full sm:w-80">
                    <i data-lucide="search"></i>
                    <input type="text" id="round-search" oninput="filterRoundsTable()" placeholder="Filter startup or round name..." 
                           class="admin-input w-full">
                </div>

                <!-- Status Filter Tabs -->
                <div class="admin-filter-bar w-full sm:w-auto overflow-x-auto">
                    <button type="button" onclick="setRoundFilter('ALL')" id="filter-btn-ALL" class="admin-filter-pill active">
                        All (<?= $totalRounds ?>)
                    </button>
                    <button type="button" onclick="setRoundFilter('PENDING')" id="filter-btn-PENDING" class="admin-filter-pill">
                        Needs Review (<?= $pendingReviewCount ?>)
                    </button>
                    <button type="button" onclick="setRoundFilter('LIVE')" id="filter-btn-LIVE" class="admin-filter-pill">
                        Live (<?= $liveCount ?>)
                    </button>
                </div>
            </div>

            <!-- Rounds Table -->
            <div class="admin-table-container">
                <div class="overflow-x-auto">
                    <table class="admin-table" id="rounds-table">
                        <thead>
                            <tr>
                                <th>Startup & Founder</th>
                                <th>Round Name</th>
                                <th>Target Capital</th>
                                <th>Pre-Money Val.</th>
                                <th>Equity</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="rounds-tbody">
                            <?php if (empty($rounds)): ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400">No funding rounds registered on the portal yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rounds as $r): ?>
                                    <tr class="round-row" 
                                        data-status="<?= htmlspecialchars($r['status']) ?>"
                                        data-is-pending="<?= in_array($r['status'], ['SUBMITTED', 'UNDER_REVIEW']) ? 'true' : 'false' ?>"
                                        data-is-live="<?= in_array($r['status'], ['LIVE', 'APPROVED', 'PARTIALLY_FUNDED']) ? 'true' : 'false' ?>"
                                        data-search="<?= strtolower(htmlspecialchars($r['company_name'] . ' ' . $r['round_name'] . ' ' . ($r['founder_name'] ?? ''))) ?>">
                                        
                                        <td>
                                            <div class="font-semibold text-slate-900 text-xs"><?= htmlspecialchars($r['company_name']) ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                <?= htmlspecialchars($r['founder_name'] ?? 'Founder') ?> • <?= htmlspecialchars($r['industry']) ?>
                                            </div>
                                        </td>
                                        
                                        <td class="font-medium text-slate-800 text-xs">
                                            <?= htmlspecialchars($r['round_name']) ?>
                                        </td>
                                        
                                        <td class="font-bold text-slate-900 text-xs">
                                            <?= format_inr($r['target_amount']) ?>
                                        </td>
                                        
                                        <td class="text-slate-600 text-xs">
                                            <?= format_inr($r['valuation']) ?>
                                        </td>
                                        
                                        <td class="font-semibold text-indigo-600 text-xs">
                                            <?= $r['equity_offered'] ?>%
                                        </td>
                                        
                                        <td>
                                            <?= render_status_badge($r['status']) ?>
                                        </td>
                                        
                                        <td class="text-right whitespace-nowrap">
                                            <?php if (in_array($r['status'], ['SUBMITTED', 'UNDER_REVIEW'])): ?>
                                                <form action="<?= url('admin/funding_review.php') ?>" method="POST" class="inline" onsubmit="return confirm('Approve and publish this round to accredited investors?');">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="round_id" value="<?= $r['id'] ?>">
                                                    <input type="hidden" name="new_status" value="LIVE">
                                                    <button type="submit" class="admin-btn-primary text-[11px] py-1 px-2.5">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form action="<?= url('admin/funding_review.php') ?>" method="POST" class="inline" onsubmit="return confirm('Reject this funding round?');">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="round_id" value="<?= $r['id'] ?>">
                                                    <input type="hidden" name="new_status" value="REJECTED">
                                                    <button type="submit" class="admin-btn-secondary text-[11px] py-1 px-2.5 text-rose-700 hover:text-rose-800 hover:border-rose-300">
                                                        Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-slate-400 text-[11px]">Published</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#rounds-admin-main", { duration: 0.3, y: 8, opacity: 0, ease: "power2.out" });

        let currentFilter = 'ALL';

        function setRoundFilter(filter) {
            currentFilter = filter;
            const buttons = ['ALL', 'PENDING', 'LIVE'];
            buttons.forEach(b => {
                const btn = document.getElementById('filter-btn-' + b);
                if (btn) {
                    if (b === filter) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                }
            });
            filterRoundsTable();
        }

        function filterRoundsTable() {
            const query = (document.getElementById('round-search')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.round-row');

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                const isPending = row.getAttribute('data-is-pending') === 'true';
                const isLive = row.getAttribute('data-is-live') === 'true';

                let matchesFilter = true;
                if (currentFilter === 'PENDING') matchesFilter = isPending;
                if (currentFilter === 'LIVE') matchesFilter = isLive;

                const matchesQuery = query === '' || searchData.includes(query);

                if (matchesFilter && matchesQuery) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
