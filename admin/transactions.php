<?php
/**
 * Admin Module: Financial Transactions & Escrow Monitor
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Transactions & Escrow Governance';

$transactions = [];
$totalEscrow = 0;

if ($db) {
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

    foreach ($transactions as $t) {
        $totalEscrow += (float)$t['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions & Escrow • <?= APP_NAME ?></title>
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

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="tx-admin-main">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-lg md:text-xl font-extrabold text-slate-900 tracking-tight">Financial Transactions & Escrow Governance</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Real-time auditing of capital inflows, escrow allocations, and transaction references.</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl px-4 py-2.5 shadow-sm">
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider block">Total Escrow Processed</span>
                    <div class="text-lg font-black text-emerald-600"><?= format_inr($totalEscrow) ?></div>
                </div>
            </div>

            <div class="card-clean rounded-2xl p-5 md:p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider text-[10.5px]">
                                <th class="pb-3 font-semibold">Transaction Ref</th>
                                <th class="pb-3 font-semibold">Investor</th>
                                <th class="pb-3 font-semibold">Startup / Round</th>
                                <th class="pb-3 font-semibold">Amount</th>
                                <th class="pb-3 font-semibold">Instrument</th>
                                <th class="pb-3 font-semibold">Status</th>
                                <th class="pb-3 font-semibold">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-xs text-slate-400">No escrow transactions recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $t): ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3 font-mono font-bold text-indigo-600 text-xs"><?= htmlspecialchars($t['transaction_ref']) ?></td>
                                        <td class="py-3">
                                            <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($t['investor_name']) ?></div>
                                            <div class="text-[10.5px] text-slate-400"><?= htmlspecialchars($t['investor_email']) ?></div>
                                        </td>
                                        <td class="py-3">
                                            <div class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($t['company_name']) ?></div>
                                            <div class="text-[10.5px] text-slate-500"><?= htmlspecialchars($t['round_name']) ?></div>
                                        </td>
                                        <td class="py-3 font-bold text-emerald-600 text-xs"><?= format_inr($t['amount']) ?></td>
                                        <td class="py-3 text-slate-600 text-xs"><?= htmlspecialchars($t['payment_mode']) ?></td>
                                        <td class="py-3"><?= render_status_badge($t['status']) ?></td>
                                        <td class="py-3 text-slate-500 text-xs"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></td>
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
        gsap.from("#tx-admin-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
