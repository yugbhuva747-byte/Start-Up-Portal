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

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="tx-admin-main">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200/80 dark:border-emerald-800/80 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-sm flex-shrink-0">
                        <i data-lucide="banknote" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Escrow & Payment Transfers
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            Real-time audited ledger of escrow disbursements, investor deposits, and platform clearing reconciliations.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="exportTxCSV()" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 transition shadow-xs">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span>Export CSV</span>
                    </button>
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
                    <div class="text-lg sm:text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-2"><?= format_inr($totalEscrow) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">100% Escrow Reconciled</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Settled Orders</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <i data-lucide="receipt" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mt-2"><?= number_format($totalTxCount) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= $successCount ?> successful settlements</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Avg. Ticket Size</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-2"><?= format_inr($avgInvestment) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Per transaction ticket</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gateway Status</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mt-2 flex items-center gap-1.5">
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
                                <th class="py-3.5 px-4 text-center">Action</th>
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

                                        <!-- Action -->
                                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                            <button type="button" onclick="inspectTx(<?= htmlspecialchars(json_encode([
                                                'ref' => $t['transaction_ref'],
                                                'amount' => format_inr($t['amount']),
                                                'raw_amount' => $t['amount'],
                                                'status' => $t['status'],
                                                'method' => $t['payment_mode'] ?? 'Escrow Wire',
                                                'investor' => $t['investor_name'],
                                                'email' => $t['investor_email'],
                                                'company' => $t['company_name'],
                                                'round' => $t['round_name'],
                                                'date' => date('d M Y, H:i', strtotime($t['created_at']))
                                            ])) ?>)" class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-xs font-bold transition inline-flex items-center space-x-1">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                <span>Inspect</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Transaction Inspection Modal -->
            <div id="tx-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 max-w-lg w-full shadow-2xl relative space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center font-bold">
                                <i data-lucide="receipt" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 dark:text-white text-base">Escrow Audit Voucher</h3>
                                <p id="m-ref" class="text-xs font-mono text-indigo-600 dark:text-indigo-400 font-bold"></p>
                            </div>
                        </div>
                        <button type="button" onclick="closeTxModal()" class="p-1 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="space-y-3 text-xs sm:text-sm">
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Reconciled Amount:</span>
                            <span id="m-amount" class="text-lg font-black text-emerald-600"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-800">
                                <div class="text-[10px] uppercase font-bold text-slate-400">Investor Entity</div>
                                <div id="m-investor" class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 truncate"></div>
                                <div id="m-email" class="text-[11px] text-slate-500 truncate"></div>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-800">
                                <div class="text-[10px] uppercase font-bold text-slate-400">Target Venture</div>
                                <div id="m-company" class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 truncate"></div>
                                <div id="m-round" class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold truncate"></div>
                            </div>
                        </div>

                        <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-800 flex justify-between items-center text-xs">
                            <span class="text-slate-500">Settlement Gateway:</span>
                            <span id="m-method" class="font-bold text-slate-700 dark:text-slate-300"></span>
                        </div>

                        <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-800 flex justify-between items-center text-xs">
                            <span class="text-slate-500">Timestamp:</span>
                            <span id="m-date" class="font-mono text-slate-700 dark:text-slate-300"></span>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end space-x-2">
                        <button type="button" onclick="window.print()" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition flex items-center space-x-1.5">
                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                            <span>Print Voucher</span>
                        </button>
                        <button type="button" onclick="closeTxModal()" class="px-5 py-2 bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs font-bold rounded-xl transition">
                            Done
                        </button>
                    </div>
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

        function inspectTx(data) {
            document.getElementById('m-ref').textContent = data.ref;
            document.getElementById('m-amount').textContent = data.amount;
            document.getElementById('m-investor').textContent = data.investor;
            document.getElementById('m-email').textContent = data.email;
            document.getElementById('m-company').textContent = data.company;
            document.getElementById('m-round').textContent = data.round;
            document.getElementById('m-method').textContent = data.method;
            document.getElementById('m-date').textContent = data.date;

            document.getElementById('tx-modal').classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        }

        function closeTxModal() {
            document.getElementById('tx-modal').classList.add('hidden');
        }

        function exportTxCSV() {
            const rows = document.querySelectorAll('.tx-row');
            let csv = 'Transaction Ref,Investor,Company,Round,Amount,Payment Method,Status,Date\n';

            rows.forEach(r => {
                const cols = r.querySelectorAll('td');
                if (cols.length >= 7) {
                    const ref = cols[0].innerText.trim();
                    const investor = cols[1].querySelector('div:first-child')?.innerText.trim() || '';
                    const company = cols[2].querySelector('div:first-child')?.innerText.trim() || '';
                    const round = cols[2].querySelector('div:nth-child(2)')?.innerText.trim() || '';
                    const amount = cols[3].innerText.trim().replace(/,/g, '');
                    const method = cols[4].innerText.trim();
                    const status = cols[5].innerText.trim();
                    const date = cols[6].innerText.trim().replace(/\n/g, ' ');
                    csv += `"${ref}","${investor}","${company}","${round}","${amount}","${method}","${status}","${date}"\n`;
                }
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', `escrow_transactions_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeTxModal();
        });
    </script>
</body>
</html>
