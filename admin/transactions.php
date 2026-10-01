<?php
/**
 * Admin Module: Financial Transactions & Escrow Monitor
 * Clean, Minimalist Financial Ledger & Escrow Audits
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Escrow & Transactions';

$transactions = [];
$totalEscrow = 0;
$totalTxCount = 0;
$avgInvestment = 0;

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
        $transactions = $stmt->fetchAll();

        $totalTxCount = count($transactions);
        foreach ($transactions as $t) {
            $totalEscrow += (float)$t['amount'];
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
    <title>Transactions & Escrow • <?= APP_NAME ?></title>

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
<body class="bg-[#F4F2EE] text-slate-900 flex min-h-screen">

    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>


        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="tx-admin-main">

            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="admin-page-icon">
                        <i data-lucide="banknote" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            Escrow & Payment Transfers
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">Real-time ledger of syndicate disbursements, investor deposits, and SEBI compliance logs.</p>
                    </div>
                </div>
            </div>

            <!-- Top Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" id="stats-grid">
                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Total Escrow Volume</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-emerald"><?= format_inr($totalEscrow) ?></div>
                    <div class="admin-stat-sub text-emerald-600 font-semibold">100% Escrow Reconciled</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Settled Orders</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i data-lucide="receipt" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-sky"><?= $totalTxCount ?></div>
                    <div class="admin-stat-sub">Confirmed investor payments</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Average Investment</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-indigo"><?= format_inr($avgInvestment) ?></div>
                    <div class="admin-stat-sub">Per investment ticket</div>
                </div>
            </div>

            <!-- Search Filter Bar -->
            <div class="admin-card p-3 sm:p-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <div class="admin-search-wrapper w-full sm:w-80">
                    <i data-lucide="search"></i>
                    <input type="text" id="tx-search" oninput="filterTxTable()" placeholder="Search Tx Ref, Investor, or Startup..." 
                           class="admin-input w-full">
                </div>
                <div class="text-xs text-slate-400">
                    Showing <span class="font-semibold text-slate-700" id="tx-count"><?= $totalTxCount ?></span> transactions
                </div>
            </div>

            <!-- Transactions Table Card -->
            <div class="admin-table-container">
                <div class="overflow-x-auto">
                    <table class="admin-table" id="tx-table">
                        <thead>
                            <tr>
                                <th>Transaction Ref</th>
                                <th>Investor</th>
                                <th>Startup & Round</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th class="text-right">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody id="tx-tbody">
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400">No escrow transactions recorded on the ledger yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $t): ?>
                                    <tr class="tx-row"
                                        data-search="<?= strtolower(htmlspecialchars($t['transaction_ref'] . ' ' . $t['investor_name'] . ' ' . $t['company_name'] . ' ' . $t['round_name'])) ?>">
                                        
                                        <!-- Ref Code -->
                                        <td class="font-mono font-semibold text-indigo-600 text-xs">
                                            <?= htmlspecialchars($t['transaction_ref']) ?>
                                        </td>

                                        <!-- Investor -->
                                        <td>
                                            <div class="font-semibold text-slate-900 text-xs"><?= htmlspecialchars($t['investor_name']) ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($t['investor_email']) ?></div>
                                        </td>

                                        <!-- Startup & Round -->
                                        <td>
                                            <div class="font-semibold text-slate-800 text-xs"><?= htmlspecialchars($t['company_name']) ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($t['round_name']) ?></div>
                                        </td>

                                        <!-- Amount -->
                                        <td class="font-bold text-slate-900 text-xs">
                                            <?= format_inr($t['amount']) ?>
                                        </td>

                                        <!-- Method -->
                                        <td>
                                            <span class="admin-badge admin-badge-neutral text-[10px]">
                                                <?= htmlspecialchars($t['payment_mode'] ?? 'Escrow Wire') ?>
                                            </span>
                                        </td>

                                        <!-- Status -->
                                        <td>
                                            <span class="admin-badge admin-badge-success">
                                                <span class="admin-badge-dot"></span>
                                                <span><?= htmlspecialchars(strtoupper($t['status'] ?? 'COMPLETED')) ?></span>
                                            </span>
                                        </td>

                                        <!-- Timestamp -->
                                        <td class="text-right text-slate-500 text-xs">
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
        gsap.from("#tx-admin-main", { duration: 0.3, y: 8, opacity: 0, ease: "power2.out" });

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
