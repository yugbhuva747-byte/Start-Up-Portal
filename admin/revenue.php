<?php
/**
 * Admin Module: Platform Commission & Revenue Analytics Hub
 * Tracks platform monetization, GTV, 3% success fees, GST liabilities, and B2B invoices
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Revenue & Invoices';

$error = '';
$flash = get_flash();

// Handle POST actions (Create invoice or update settlement status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'update_settlement') {
            $invoiceId = (int)($_POST['invoice_id'] ?? 0);
            $newStatus = trim($_POST['settlement_status'] ?? 'SETTLED');
            $paymentMode = trim($_POST['payment_mode'] ?? 'Deducted from Escrow Disbursement');
            $settledAt = ($newStatus === 'SETTLED') ? date('Y-m-d H:i:s') : null;

            $upd = $db->prepare("UPDATE platform_invoices SET settlement_status = ?, payment_mode = ?, settled_at = ? WHERE id = ?");
            $upd->execute([$newStatus, $paymentMode, $settledAt, $invoiceId]);

            log_audit($user['id'], 'UPDATE_INVOICE_SETTLEMENT', 'platform_invoices', $invoiceId, "Updated invoice #{$invoiceId} status to {$newStatus}");
            set_flash('success', "Invoice settlement status updated to {$newStatus}.");
            header('Location: ' . url('admin/revenue.php'));
            exit;
        }

        if ($action === 'create_invoice') {
            $companyId = (int)($_POST['company_id'] ?? 0);
            $roundId = (int)($_POST['funding_round_id'] ?? 0);
            $grossRaised = (float)($_POST['gross_amount_raised'] ?? 0);
            $commRate = (float)($_POST['commission_rate_percent'] ?? 3.0);
            $techFee = (float)($_POST['tech_fee'] ?? 25000.0);
            $gstRate = (float)($_POST['gst_rate_percent'] ?? 18.0);
            $settlementStatus = trim($_POST['settlement_status'] ?? 'UNSETTLED');
            $notes = trim($_POST['notes'] ?? '');

            if ($companyId <= 0 || $roundId <= 0 || $grossRaised <= 0) {
                $error = 'Please fill in valid company, round, and gross capital amounts.';
            } else {
                $commAmount = round($grossRaised * ($commRate / 100), 2);
                $subtotal = $commAmount + $techFee;
                $gstAmount = round($subtotal * ($gstRate / 100), 2);
                $totalPayable = $subtotal + $gstAmount;
                $invNumber = 'INV-2026-GST-' . rand(1000, 9999);
                $settledDate = ($settlementStatus === 'SETTLED') ? date('Y-m-d H:i:s') : null;

                $ins = $db->prepare("
                    INSERT INTO platform_invoices 
                    (invoice_number, company_id, funding_round_id, gross_amount_raised, commission_rate_percent, commission_amount, tech_fee, subtotal, gst_rate_percent, gst_amount, total_payable, settlement_status, payment_mode, settled_at, invoice_date, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Deducted from Escrow Disbursement', ?, CURDATE(), ?)
                ");
                $ins->execute([
                    $invNumber, $companyId, $roundId, $grossRaised, $commRate, $commAmount,
                    $techFee, $subtotal, $gstRate, $gstAmount, $totalPayable, $settlementStatus,
                    $settledDate, $notes
                ]);

                $newInvId = $db->lastInsertId();
                log_audit($user['id'], 'GENERATE_PLATFORM_INVOICE', 'platform_invoices', $newInvId, "Generated GST tax invoice {$invNumber} for company #{$companyId}");
                set_flash('success', "Tax Invoice {$invNumber} generated successfully!");
                header('Location: ' . url('admin/revenue.php'));
                exit;
            }
        }
    }
}

// Data aggregation
$gtv = 0;
$totalPlatformRevenue = 0;
$netRevenue = 0;
$gstLiability = 0;
$settledRevenue = 0;
$pendingRevenue = 0;
$invoices = [];
$statusFilter = trim($_GET['status_filter'] ?? 'ALL');
$companiesList = [];
$roundsList = [];

if ($db) {
    // 1. Gross Transaction Value
    $gtv = (float)$db->query("SELECT COALESCE(SUM(amount_raised), 0) FROM funding_rounds")->fetchColumn();

    // 2. Invoice Aggregates
    $agg = $db->query("
        SELECT 
            COALESCE(SUM(total_payable), 0) as total_gross_revenue,
            COALESCE(SUM(subtotal), 0) as total_net_revenue,
            COALESCE(SUM(gst_amount), 0) as total_gst,
            COALESCE(SUM(CASE WHEN settlement_status = 'SETTLED' THEN total_payable ELSE 0 END), 0) as total_settled,
            COALESCE(SUM(CASE WHEN settlement_status IN ('UNSETTLED', 'PROCESSING') THEN total_payable ELSE 0 END), 0) as total_pending
        FROM platform_invoices
    ")->fetch();

    $totalPlatformRevenue = (float)($agg['total_gross_revenue'] ?? 0);
    $netRevenue = (float)($agg['total_net_revenue'] ?? 0);
    $gstLiability = (float)($agg['total_gst'] ?? 0);
    $settledRevenue = (float)($agg['total_settled'] ?? 0);
    $pendingRevenue = (float)($agg['total_pending'] ?? 0);

    // 3. Invoices List
    $sql = "
        SELECT pi.*, c.name as company_name, c.cin_number, fr.round_name
        FROM platform_invoices pi
        JOIN companies c ON pi.company_id = c.id
        JOIN funding_rounds fr ON pi.funding_round_id = fr.id
    ";
    if ($statusFilter === 'SETTLED') {
        $sql .= " WHERE pi.settlement_status = 'SETTLED'";
    } elseif ($statusFilter === 'UNSETTLED') {
        $sql .= " WHERE pi.settlement_status IN ('UNSETTLED', 'PROCESSING')";
    }
    $sql .= " ORDER BY pi.invoice_date DESC, pi.id DESC";

    $invoices = $db->query($sql)->fetchAll();

    // 4. Fetch list for creation modal
    $companiesList = $db->query("SELECT id, name, cin_number FROM companies ORDER BY name ASC")->fetchAll();
    $roundsList = $db->query("SELECT id, company_id, round_name, target_amount, amount_raised FROM funding_rounds ORDER BY id DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Revenue & Commission Hub • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/admin/head.php'; ?>
    <style>
        body { font-family: "Vay Portal", Sans-serif; }
        .card-clean { background: #FFFFFF; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.03); }
    </style>
</head>
<body class="bg-[#F4F2EE] text-slate-900 flex min-h-screen dark:bg-[#0B0F19] dark:text-slate-100">

    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">

        <!-- Admin Navbar -->
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="revenue-main">


            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="admin-page-icon">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            Platform Revenue & Invoices
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">Platform commissions, B2B GST tax invoices, and escrow fee settlements.</p>
                    </div>
                </div>
                
                <div>
                    <button onclick="document.getElementById('newInvoiceModal').classList.remove('hidden')" 
                            class="admin-btn-primary">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Generate Tax Invoice</span>
                    </button>
                </div>
            </div>

            <!-- Revenue KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Capital Volume (GTV)</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-sky"><?= format_inr($gtv) ?></div>
                    <div class="admin-stat-sub">Total capital routed</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Total Invoiced</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="receipt" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-indigo"><?= format_inr($totalPlatformRevenue) ?></div>
                    <div class="admin-stat-sub">Gross fees + GST</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Net Retained Revenue</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-emerald"><?= format_inr($netRevenue) ?></div>
                    <div class="admin-stat-sub">Platform earnings (excl. GST)</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Pending Settlements</span>
                        <div class="w-8 h-8 rounded-lg <?= $pendingRevenue > 0 ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-400' ?> flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value <?= $pendingRevenue > 0 ? 'stat-value-amber' : '' ?>"><?= format_inr($pendingRevenue) ?></div>
                    <div class="admin-stat-sub">Awaiting tranche settlement</div>
                </div>
            </div>

            <!-- Revenue Intelligence Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Monetization Streams -->
                <div class="admin-card p-5 lg:col-span-2">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Monetization Fee Streams</span>
                        </h2>
                        <span class="admin-badge admin-badge-success text-[10px]">
                            Auto Escrow Deduction
                        </span>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-semibold text-slate-800">Success Carry / Platform Commission (3.00%)</span>
                                <span class="font-mono font-bold text-slate-900"><?= format_inr(max(0, $netRevenue - (count($invoices) * 25000))) ?></span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-600 rounded-full" style="width: 82%"></div>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-1">Calculated on gross escrow amount committed upon round closure.</div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-semibold text-slate-800">Technical Diligence & Onboarding Infrastructure</span>
                                <span class="font-mono font-bold text-slate-900"><?= format_inr(count($invoices) * 25000) ?></span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: 18%"></div>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-1">Fixed ₹25,000 per round covering KYC, DigiLocker verification, and digital share demat registry.</div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-semibold text-slate-800">Statutory GST (18.00% Indian Tax Remittance)</span>
                                <span class="font-mono font-bold text-amber-600"><?= format_inr($gstLiability) ?></span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-400 rounded-full" style="width: 100%"></div>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-1">Held in tax liability reserve for monthly GSTR-1 & GSTR-3B filings.</div>
                        </div>
                    </div>
                </div>

                <!-- Settlement Status Card -->
                <div class="admin-card p-5">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-3 mb-4 border-b border-slate-100 flex items-center space-x-1.5">
                        <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Settlement Health</span>
                    </h2>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 mb-4 text-center">
                        <div class="text-[10.5px] uppercase font-bold text-slate-400 tracking-wider">Settled & Realized Funds</div>
                        <div class="text-2xl font-bold text-emerald-600 mt-0.5"><?= format_inr($settledRevenue) ?></div>
                        <div class="text-[11px] text-slate-500 mt-1">
                            <?= round(($totalPlatformRevenue > 0 ? ($settledRevenue / $totalPlatformRevenue) * 100 : 100)) ?>% realized
                        </div>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between p-2.5 rounded-lg bg-emerald-50/70 border border-emerald-100">
                            <span class="text-emerald-800 font-semibold flex items-center space-x-1">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Settled Invoices</span>
                            </span>
                            <span class="font-bold text-emerald-900 font-mono">
                                <?= count(array_filter($invoices, fn($x) => $x['settlement_status'] === 'SETTLED')) ?>
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-100 border border-slate-200">
                            <span class="text-slate-700 font-semibold flex items-center space-x-1">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                <span>Pending Tranches</span>
                            </span>
                            <span class="font-bold text-slate-900 font-mono">
                                <?= count(array_filter($invoices, fn($x) => $x['settlement_status'] !== 'SETTLED')) ?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tax Invoices & Settlements Ledger -->
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h2 class="text-sm font-bold text-slate-900 tracking-tight">
                        B2B GST Tax Invoices
                    </h2>

                    <!-- Filter Tabs -->
                    <div class="admin-filter-bar">
                        <a href="<?= url('admin/revenue.php?status_filter=ALL') ?>" 
                           class="admin-filter-pill <?= $statusFilter === 'ALL' ? 'active' : '' ?>">
                            All
                        </a>
                        <a href="<?= url('admin/revenue.php?status_filter=SETTLED') ?>" 
                           class="admin-filter-pill <?= $statusFilter === 'SETTLED' ? 'active' : '' ?>">
                            Settled
                        </a>
                        <a href="<?= url('admin/revenue.php?status_filter=UNSETTLED') ?>" 
                           class="admin-filter-pill <?= $statusFilter === 'UNSETTLED' ? 'active' : '' ?>">
                            Unsettled
                        </a>
                    </div>
                </div>

                <div class="admin-table-container">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Startup</th>
                                    <th>Round / Gross</th>
                                    <th>Fee Breakdown</th>
                                    <th>GST (18%)</th>
                                    <th>Total Payable</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invoices)): ?>
                                    <tr>
                                        <td colspan="8" class="py-12 text-center text-slate-400">
                                            No platform invoices found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($invoices as $inv): ?>
                                        <tr>
                                            <td class="font-mono font-semibold text-indigo-600">
                                                <?= htmlspecialchars($inv['invoice_number']) ?>
                                                <div class="text-[11px] text-slate-400 font-sans mt-0.5"><?= date('d M Y', strtotime($inv['invoice_date'])) ?></div>
                                            </td>
                                            <td>
                                                <div class="font-semibold text-slate-900"><?= htmlspecialchars($inv['company_name']) ?></div>
                                                <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($inv['cin_number'] ?: 'CIN Verified') ?></div>
                                            </td>
                                            <td>
                                                <div class="font-semibold text-slate-800"><?= htmlspecialchars($inv['round_name']) ?></div>
                                                <div class="text-[11px] text-emerald-600 font-mono mt-0.5">GTV: <?= format_inr($inv['gross_amount_raised']) ?></div>
                                            </td>
                                            <td class="font-mono text-xs">
                                                <span class="font-semibold text-slate-800"><?= format_inr($inv['commission_amount']) ?></span> 
                                                <span class="text-slate-400">(<?= $inv['commission_rate_percent'] ?>%)</span>
                                                <div class="text-[10px] text-slate-400 mt-0.5">+ <?= format_inr($inv['tech_fee']) ?> Tech</div>
                                            </td>
                                            <td class="font-mono text-amber-600 text-xs">
                                                <?= format_inr($inv['gst_amount']) ?>
                                            </td>
                                            <td class="font-mono font-bold text-slate-900 text-xs">
                                                <?= format_inr($inv['total_payable']) ?>
                                            </td>
                                            <td>
                                                <span class="admin-badge <?= $inv['settlement_status'] === 'SETTLED' ? 'admin-badge-success' : 'admin-badge-warning' ?>">
                                                    <span class="admin-badge-dot"></span>
                                                    <span><?= ucfirst(strtolower($inv['settlement_status'])) ?></span>
                                                </span>
                                            </td>
                                            <td class="text-right whitespace-nowrap">
                                                <a href="<?= url('admin/invoice_view.php?id=' . $inv['id']) ?>" target="_blank"
                                                   class="admin-btn-secondary text-[11px] py-1 px-2.5" title="View Tax Invoice">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    <span>Invoice</span>
                                                </a>

                                                <!-- Toggle Settlement Form -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="form_action" value="update_settlement">
                                                    <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                                                    <input type="hidden" name="settlement_status" value="<?= $inv['settlement_status'] === 'SETTLED' ? 'UNSETTLED' : 'SETTLED' ?>">
                                                    <button type="submit" 
                                                            class="admin-btn-ghost p-1 ml-1" 
                                                            title="<?= $inv['settlement_status'] === 'SETTLED' ? 'Mark as Unsettled' : 'Mark as Settled' ?>">
                                                        <i data-lucide="<?= $inv['settlement_status'] === 'SETTLED' ? 'rotate-ccw' : 'check-circle' ?>" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </form>
                                            </td>
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

    <!-- Create Tax Invoice Modal -->
    <div id="newInvoiceModal" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 relative">
            <button onclick="document.getElementById('newInvoiceModal').classList.add('hidden')" 
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="flex items-center space-x-3 mb-4 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center">
                    <i data-lucide="receipt" class="w-4 h-4 text-white"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Generate B2B Tax Invoice</h3>
                    <p class="text-[11px] text-slate-400">Generate a statutory GST invoice for round commission.</p>
                </div>
            </div>

            <form action="<?= url('admin/revenue.php') ?>" method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="form_action" value="create_invoice">

                <div>
                    <label class="block font-semibold text-slate-700 mb-1 text-xs">Billed Startup Company</label>
                    <select name="company_id" id="modal-company-select" required onchange="filterRoundsByCompany(this.value)" class="admin-input w-full">
                        <option value="">Select Company...</option>
                        <?php foreach ($companiesList as $comp): ?>
                            <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?> (<?= htmlspecialchars($comp['cin_number'] ?: 'Unlisted') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1 text-xs">Funding Round</label>
                    <select name="funding_round_id" id="modal-round-select" required class="admin-input w-full">
                        <option value="">Select funding round...</option>
                        <?php foreach ($roundsList as $rnd): ?>
                            <option value="<?= $rnd['id'] ?>" data-company="<?= $rnd['company_id'] ?>" data-raised="<?= $rnd['amount_raised'] ?: $rnd['target_amount'] ?>">
                                <?= htmlspecialchars($rnd['round_name']) ?> (<?= format_inr($rnd['amount_raised'] ?: $rnd['target_amount']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1 text-xs">Gross Raised (₹)</label>
                        <input type="number" step="0.01" name="gross_amount_raised" id="modal-gross-amount" required placeholder="5000000" class="admin-input w-full">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1 text-xs">Commission Rate (%)</label>
                        <input type="number" step="0.01" name="commission_rate_percent" value="3.00" required class="admin-input w-full">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1 text-xs">Tech & DD Fee (₹)</label>
                        <input type="number" step="0.01" name="tech_fee" value="25000.00" required class="admin-input w-full">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1 text-xs">GST Rate (%)</label>
                        <input type="number" step="0.01" name="gst_rate_percent" value="18.00" required class="admin-input w-full">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1 text-xs">Settlement State</label>
                    <select name="settlement_status" class="admin-input w-full">
                        <option value="SETTLED">SETTLED (Deducted directly from escrow release)</option>
                        <option value="UNSETTLED" selected>UNSETTLED (Awaiting milestone disbursement)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1 text-xs">Internal Notes (Optional)</label>
                    <input type="text" name="notes" placeholder="e.g., Tranche 1 closing settlement" class="admin-input w-full">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('newInvoiceModal').classList.add('hidden')" class="admin-btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="admin-btn-primary">
                        <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                        <span>Generate Invoice</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#revenue-main", { duration: 0.3, y: 8, opacity: 0, ease: "power2.out" });

        function filterRoundsByCompany(companyId) {
            const select = document.getElementById('modal-round-select');
            const grossInput = document.getElementById('modal-gross-amount');
            let firstMatched = null;

            Array.from(select.options).forEach(opt => {
                if (!opt.value) return;
                const cId = opt.getAttribute('data-company');
                if (!companyId || cId === companyId) {
                    opt.style.display = '';
                    if (!firstMatched) firstMatched = opt;
                } else {
                    opt.style.display = 'none';
                }
            });

            if (firstMatched) {
                select.value = firstMatched.value;
                const rAmount = firstMatched.getAttribute('data-raised');
                if (rAmount && grossInput) grossInput.value = rAmount;
            } else {
                select.value = '';
            }
        }
    </script>
</body>
</html>
