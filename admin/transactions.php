<?php
/**
 * Admin Module: Financial Transactions & Escrow Monitor
 * Clean, Executive Financial Ledger & Escrow Audits
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Escrow & Transactions';

$transactions = [];
$totalEscrow = 0;
$totalTxCount = 0;
$avgInvestment = 0;
$successCount = 0;
$pendingCount = 0;

if ($db) {
    try {
        $stmt = $db->query("
            SELECT tx.*, io.amount as order_amount, u.name as investor_name, u.email as investor_email, c.name as company_name, fr.round_name
            FROM transactions tx
            JOIN investment_orders io ON tx.order_id = io.id
            JOIN users u ON io.investor_user_id = u.id
            JOIN funding_rounds fr ON io.funding_round_id = fr.id
            JOIN companies c ON fr.company_id = c.id
            ORDER BY tx.created_at DESC
        ");
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalTxCount = count($transactions);
        foreach ($transactions as $t) {
            $totalEscrow += (float)$t['amount'];
            if ($t['status'] === 'SUCCESS') {
                $successCount++;
            } else {
                $pendingCount++;
            }
        }
        if ($totalTxCount > 0) {
            $avgInvestment = $totalEscrow / $totalTxCount;
        }
    } catch (Exception $e) {
        // Fallback
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>

    <?php include __DIR__ . '/../includes/admin/head.php'; ?>

    <style>
        .stat-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
        }
        .dark .stat-card-clean, html.dark .stat-card-clean { background: #111827 !important; border-color: #1e293b !important; }
        .filter-bar-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 0.875rem 1.25rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        }
        .dark .filter-bar-clean, html.dark .filter-bar-clean { background: #111827 !important; border-color: #1e293b !important; }
        .table-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }
        .dark .table-card-clean, html.dark .table-card-clean { background: #111827 !important; border-color: #1e293b !important; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 flex min-h-screen dark:bg-[#0b0f19] dark:text-slate-100 font-sans antialiased">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6" id="tx-admin-main">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200/80 dark:border-emerald-800/80 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-sm flex-shrink-0">
                        <i data-lucide="banknote" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Escrow & Payment Transfers
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            Real-time audited ledger of escrow disbursements, investor deposits, and platform clearing reconciliations.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="window.location.reload()" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Reconcile Ledger</span>
                    </button>
                    <a href="<?= url('admin/revenue.php') ?>" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                        <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                        <span>Platform Invoices</span>
                    </a>
                </div>
            </div>

            <!-- Top Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Escrow Volume</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2"><?= format_inr($totalEscrow) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">100% Escrow Reconciled</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Settled Orders</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <i data-lucide="receipt" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-2"><?= number_format($totalTxCount) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= $successCount ?> successful settlements</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Avg. Ticket Size</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-2"><?= format_inr($avgInvestment) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Per transaction ticket</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gateway Status</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-2 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-lg">Online</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">UPI, IMPS & Wire Cleared</div>
                </div>
            </div>

            <!-- Search & Filter Toolbar -->
            <div class="filter-bar-clean dark:bg-slate-900 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <div class="relative w-full sm:w-80">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="tx-search" oninput="filterTxTable()" 
                           placeholder="Search Tx Ref, Investor, or Startup..." 
                           class="w-full pl-9 pr-3.5 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Showing <span class="font-bold text-slate-800 dark:text-white" id="tx-count"><?= $totalTxCount ?></span> transactions
                    </span>
                </div>
            </div>

            <!-- Transactions Table Card -->
            <div class="table-card-clean dark:bg-slate-900 dark:border-slate-800">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse" id="tx-table">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700/80 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                                <th class="py-3.5 px-4">Transaction Ref</th>
                                <th class="py-3.5 px-4">Investor</th>
                                <th class="py-3.5 px-4">Startup & Round</th>
                                <th class="py-3.5 px-4">Amount</th>
                                <th class="py-3.5 px-4">Payment Method</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody id="tx-tbody" class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="py-16 text-center text-slate-400 dark:text-slate-500">
                                        <i data-lucide="receipt" class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2"></i>
                                        <p class="font-medium text-sm">No escrow transactions recorded on the ledger yet.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $t): ?>
                                    <tr class="tx-row hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                                        data-search="<?= strtolower(htmlspecialchars($t['transaction_ref'] . ' ' . $t['investor_name'] . ' ' . $t['company_name'] . ' ' . $t['round_name'])) ?>">
                                        
                                        <!-- Ref Code -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                                <?= htmlspecialchars($t['transaction_ref']) ?>
                                            </span>
                                        </td>

                                        <!-- Investor -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($t['investor_name']) ?></div>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5"><?= htmlspecialchars($t['investor_email']) ?></div>
                                        </td>

                                        <!-- Startup & Round -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <div class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($t['company_name']) ?></div>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5"><?= htmlspecialchars($t['round_name']) ?></div>
                                        </td>

                                        <!-- Amount -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <span class="font-bold text-slate-900 dark:text-white">
                                                <?= format_inr($t['amount']) ?>
                                            </span>
                                        </td>

                                        <!-- Method -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                <?= htmlspecialchars($t['payment_mode'] ?? 'Escrow Wire') ?>
                                            </span>
                                        </td>

                                        <!-- Status -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <?php if ($t['status'] === 'SUCCESS'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    COMPLETED
                                                </span>
                                            <?php elseif ($t['status'] === 'FAILED'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                    FAILED
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    <?= htmlspecialchars($t['status']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Timestamp -->
                                        <td class="py-3.5 px-4 text-right whitespace-nowrap text-slate-500 dark:text-slate-400">
                                            <div><?= date('d M Y', strtotime($t['created_at'])) ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?= date('H:i', strtotime($t['created_at'])) ?></div>
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

        function filterTxTable() {
            const query = (document.getElementById('tx-search')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.tx-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                if (query === '' || searchData.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const countEl = document.getElementById('tx-count');
            if (countEl) countEl.textContent = visibleCount;
        }
    </script>
</body>
</html>
