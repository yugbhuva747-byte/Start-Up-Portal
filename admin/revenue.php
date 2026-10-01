<?php
/**
 * ============================================================================
 * admin/revenue.php
 * ----------------------------------------------------------------------------
 * Platform Revenue & Invoices
 * Platform commissions, B2B GST tax invoices, and escrow fee settlements.
 * ============================================================================
 */

require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Platform Revenue & Invoices';

$error = '';
$flash = get_flash();

if (!function_exists('format_inr_short')) {
    function format_inr_short(float $amount): string {
        if ($amount >= 10000000) {
            $cr = $amount / 10000000;
            return '₹' . (round($cr, 2) == round($cr, 0) ? round($cr, 0) : number_format($cr, 2)) . ' Cr';
        } elseif ($amount >= 100000) {
            $lakh = $amount / 100000;
            return '₹' . (round($lakh, 2) == round($lakh, 0) ? round($lakh, 0) : number_format($lakh, 2)) . ' L';
        } elseif ($amount > 0) {
            return '₹' . number_format($amount);
        } else {
            return '₹0';
        }
    }
}

// Handle POST actions (Create invoice or update settlement status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['form_action'] ?? '';

        // 1. Toggle or update settlement
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

        // 2. Create invoice
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
                $invNumber = 'INV-' . date('Y') . '-GST-' . rand(100, 999);
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

// ----------------------------------------------------------------------------
// Data Aggregation & Queries
// ----------------------------------------------------------------------------
$gtv = 0.0;
$totalPlatformRevenue = 0.0;
$netRevenue = 0.0;
$gstLiability = 0.0;
$settledRevenue = 0.0;
$pendingRevenue = 0.0;
$invoices = [];
$statusFilter = strtoupper(trim($_GET['status_filter'] ?? 'ALL'));
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
            COALESCE(SUM(commission_amount), 0) as total_commission,
            COALESCE(SUM(tech_fee), 0) as total_tech_fee,
            COALESCE(SUM(CASE WHEN settlement_status = 'SETTLED' THEN total_payable ELSE 0 END), 0) as total_settled,
            COALESCE(SUM(CASE WHEN settlement_status IN ('UNSETTLED', 'PROCESSING') THEN total_payable ELSE 0 END), 0) as total_pending
        FROM platform_invoices
    ")->fetch(PDO::FETCH_ASSOC);

    if ($agg) {
        $totalPlatformRevenue = (float)$agg['total_gross_revenue'];
        $netRevenue = (float)$agg['total_net_revenue'];
        $gstLiability = (float)$agg['total_gst'];
        $commTotal = (float)$agg['total_commission'];
        $techTotal = (float)$agg['total_tech_fee'];
        $settledRevenue = (float)$agg['total_settled'];
        $pendingRevenue = (float)$agg['total_pending'];
    }

    // 3. Invoice Rows Query
    $sql = "
        SELECT 
            pi.*, 
            c.name as company_name, 
            c.cin_number,
            fr.round_name
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

    $invoices = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch list for creation modal
    $companiesList = $db->query("SELECT id, name, cin_number FROM companies ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $roundsList = $db->query("SELECT id, company_id, round_name, target_amount, amount_raised FROM funding_rounds ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
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
            border: 1px solid #f1f5f9;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
        }
        .section-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
        }
        .table-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-900 flex min-h-screen font-sans antialiased">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <!-- Main Content Stage -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navbar -->
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl w-full mx-auto" id="revenue-admin-main">
            
            <!-- Page Header (Matching Screenshot Exactly) -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold flex-shrink-0">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Platform Revenue &amp; Invoices
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Platform commissions, B2B GST tax invoices, and escrow fee settlements.
                        </p>
                    </div>
                </div>

                <div>
                    <button type="button" onclick="document.getElementById('newInvoiceModal').classList.remove('hidden')" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#4338ca] hover:bg-[#3730a3] text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <span>+ Generate Tax Invoice</span>
                    </button>
                </div>
            </div>

            <!-- Flash Alert -->
            <?php if (!empty($flash['message'])): ?>
                <div class="p-4 rounded-xl flex items-center gap-3 text-xs sm:text-sm font-semibold shadow-2xs transition-all <?= $flash['type'] === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-xl flex items-center gap-3 text-xs sm:text-sm font-semibold bg-rose-50 border border-rose-200 text-rose-800 shadow-2xs">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- 4 Metric Cards (Matching Screenshot Exactly) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- 1. Capital Volume (GTV) -->
                <div class="stat-card-clean">
                    <div class="flex items-center justify-between">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">CAPITAL VOLUME (GTV)</span>
                        <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        <?= format_inr_short($gtv) ?>
                    </div>
                    <div class="mt-1 text-xs font-medium text-slate-500">
                        Total capital routed
                    </div>
                </div>

                <!-- 2. Total Invoiced -->
                <div class="stat-card-clean">
                    <div class="flex items-center justify-between">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">TOTAL INVOICED</span>
                        <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i data-lucide="receipt" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl sm:text-3xl font-black text-indigo-700 tracking-tight">
                        <?= format_inr_short($totalPlatformRevenue) ?>
                    </div>
                    <div class="mt-1 text-xs font-medium text-slate-500">
                        Gross fees + GST
                    </div>
                </div>

                <!-- 3. Net Retained Revenue -->
                <div class="stat-card-clean">
                    <div class="flex items-center justify-between">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">NET RETAINED REVENUE</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl sm:text-3xl font-black text-emerald-600 tracking-tight">
                        <?= format_inr_short($netRevenue) ?>
                    </div>
                    <div class="mt-1 text-xs font-medium text-slate-500">
                        Platform earnings (excl. GST)
                    </div>
                </div>

                <!-- 4. Pending Settlements -->
                <div class="stat-card-clean">
                    <div class="flex items-center justify-between">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">PENDING SETTLEMENTS</span>
                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        <?= format_inr_short($pendingRevenue) ?>
                    </div>
                    <div class="mt-1 text-xs font-medium text-slate-500">
                        Awaiting tranche settlement
                    </div>
                </div>
            </div>

            <!-- Middle Section: Monetization Fee Streams & Settlement Health (Matching Screenshot) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left: Monetization Fee Streams (2 Cols) -->
                <div class="section-card-clean lg:col-span-2">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <i data-lucide="layers" class="w-4 h-4 text-purple-600"></i>
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                MONETIZATION FEE STREAMS
                            </h2>
                        </div>
                        <span class="px-3 py-0.5 rounded-full bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0] text-xs font-semibold">
                            Auto Escrow Deduction
                        </span>
                    </div>

                    <div class="space-y-4 text-xs">
                        <!-- Stream 1 -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-semibold text-slate-800">Success Carry / Platform Commission (3.00%)</span>
                                <span class="font-bold text-slate-900"><?= format_inr_short($commTotal ?? 150000) ?></span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-[#4338ca] rounded-full" style="width: 78%"></div>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-1">Calculated on gross escrow amount committed upon round closure.</div>
                        </div>

                        <!-- Stream 2 -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-semibold text-slate-800">Technical Diligence &amp; Onboarding Infrastructure</span>
                                <span class="font-bold text-slate-900"><?= format_inr_short($techTotal ?? 25000) ?></span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-[#059669] rounded-full" style="width: 22%"></div>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-1">Fixed ₹25,000 per round covering KYC, DigiLocker verification, and digital share demat registry.</div>
                        </div>

                        <!-- Stream 3 -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-semibold text-slate-800">Statutory GST (18.00% Indian Tax Remittance)</span>
                                <span class="font-bold text-amber-600"><?= format_inr_short($gstLiability ?? 31500) ?></span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-[#eab308] rounded-full" style="width: 100%"></div>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-1">Held in tax liability reserve for monthly GSTR-1 &amp; GSTR-3B filings.</div>
                        </div>
                    </div>
                </div>

                <!-- Right: Settlement Health (1 Col) -->
                <div class="section-card-clean">
                    <div class="pb-3 mb-4 border-b border-slate-100 flex items-center gap-1.5">
                        <i data-lucide="check" class="w-4 h-4 text-[#059669]"></i>
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                            SETTLEMENT HEALTH
                        </h2>
                    </div>

                    <div class="p-4 rounded-xl bg-[#f8fafc] border border-slate-100 mb-3.5 text-center">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">SETTLED &amp; REALIZED FUNDS</div>
                        <div class="text-2xl font-black text-[#059669] mt-1"><?= format_inr_short($settledRevenue) ?></div>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            <?= round(($totalPlatformRevenue > 0 ? ($settledRevenue / $totalPlatformRevenue) * 100 : 100)) ?>% realized
                        </div>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#f0fdf4] border border-[#dcfce7] text-[#166534]">
                            <span class="font-semibold flex items-center gap-1.5">
                                <i data-lucide="check" class="w-4 h-4 text-[#16a34a]"></i>
                                <span>Settled Invoices</span>
                            </span>
                            <span class="font-bold text-sm">
                                <?= count(array_filter($invoices, fn($x) => $x['settlement_status'] === 'SETTLED')) ?>
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#f8fafc] border border-slate-200/80 text-slate-600">
                            <span class="font-semibold flex items-center gap-1.5">
                                <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i>
                                <span>Pending Tranches</span>
                            </span>
                            <span class="font-bold text-sm">
                                <?= count(array_filter($invoices, fn($x) => $x['settlement_status'] !== 'SETTLED')) ?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Section: B2B GST Tax Invoices (Matching Screenshot) -->
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h2 class="text-sm font-bold text-slate-900 tracking-tight">
                        B2B GST Tax Invoices
                    </h2>

                    <!-- Filter Pill Switcher -->
                    <div class="flex items-center gap-1 bg-white border border-slate-200 p-1 rounded-xl shadow-2xs">
                        <a href="<?= url('admin/revenue.php?status_filter=ALL') ?>" 
                           class="px-3.5 py-1 rounded-lg text-xs font-bold transition <?= $statusFilter === 'ALL' ? 'bg-[#3730a3] text-white shadow-2xs' : 'text-slate-500 hover:text-slate-800' ?>">
                            All
                        </a>
                        <a href="<?= url('admin/revenue.php?status_filter=SETTLED') ?>" 
                           class="px-3.5 py-1 rounded-lg text-xs font-bold transition <?= $statusFilter === 'SETTLED' ? 'bg-[#3730a3] text-white shadow-2xs' : 'text-slate-500 hover:text-slate-800' ?>">
                            Settled
                        </a>
                        <a href="<?= url('admin/revenue.php?status_filter=UNSETTLED') ?>" 
                           class="px-3.5 py-1 rounded-lg text-xs font-bold transition <?= $statusFilter === 'UNSETTLED' ? 'bg-[#3730a3] text-white shadow-2xs' : 'text-slate-500 hover:text-slate-800' ?>">
                            Unsettled
                        </a>
                    </div>
                </div>

                <div class="table-card-clean">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-white border-b border-slate-100 text-[10.5px] font-bold uppercase tracking-wider text-slate-400">
                                    <th class="py-3 px-6">INVOICE #</th>
                                    <th class="py-3 px-4">STARTUP</th>
                                    <th class="py-3 px-4">ROUND / GROSS</th>
                                    <th class="py-3 px-4">FEE BREAKDOWN</th>
                                    <th class="py-3 px-4">GST (18%)</th>
                                    <th class="py-3 px-4">TOTAL PAYABLE</th>
                                    <th class="py-3 px-4">STATUS</th>
                                    <th class="py-3 px-6 text-right">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($invoices)): ?>
                                    <tr>
                                        <td colspan="8" class="py-14 text-center text-slate-400">
                                            <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 opacity-40"></i>
                                            <p class="text-xs font-semibold text-slate-600">No platform invoices found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($invoices as $inv): ?>
                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                            <!-- Invoice # -->
                                            <td class="py-4 px-6 whitespace-nowrap">
                                                <div class="font-semibold text-slate-900 text-xs">
                                                    <?= htmlspecialchars($inv['invoice_number']) ?>
                                                </div>
                                                <div class="text-[11px] text-slate-400 mt-0.5">
                                                    <?= date('d M Y', strtotime($inv['invoice_date'])) ?>
                                                </div>
                                            </td>

                                            <!-- Startup -->
                                            <td class="py-4 px-4 whitespace-nowrap">
                                                <div class="font-bold text-slate-900 text-xs">
                                                    <?= htmlspecialchars($inv['company_name']) ?>
                                                </div>
                                                <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                                    <?= htmlspecialchars($inv['cin_number'] ?: 'U72900KA2023PTC156789') ?>
                                                </div>
                                            </td>

                                            <!-- Round / Gross -->
                                            <td class="py-4 px-4 whitespace-nowrap">
                                                <div class="font-semibold text-slate-800 text-xs">
                                                    <?= htmlspecialchars($inv['round_name']) ?>
                                                </div>
                                                <div class="text-[11px] text-emerald-600 font-bold mt-0.5">
                                                    GTV: <?= format_inr_short((float)$inv['gross_amount_raised']) ?>
                                                </div>
                                            </td>

                                            <!-- Fee Breakdown -->
                                            <td class="py-4 px-4 whitespace-nowrap">
                                                <div class="font-bold text-slate-900 text-xs">
                                                    <?= format_inr_short((float)$inv['commission_amount']) ?> 
                                                    <span class="text-slate-400 font-normal">(<?= $inv['commission_rate_percent'] ?>%)</span>
                                                </div>
                                                <div class="text-[10.5px] text-slate-400 mt-0.5">
                                                    + <?= format_inr_short((float)$inv['tech_fee']) ?> Tech
                                                </div>
                                            </td>

                                            <!-- GST -->
                                            <td class="py-4 px-4 font-bold text-slate-900 text-xs whitespace-nowrap">
                                                <?= format_inr_short((float)$inv['gst_amount']) ?>
                                            </td>

                                            <!-- Total Payable -->
                                            <td class="py-4 px-4 font-bold text-slate-900 text-xs whitespace-nowrap">
                                                <?= format_inr_short((float)$inv['total_payable']) ?>
                                            </td>

                                            <!-- Status -->
                                            <td class="py-4 px-4 whitespace-nowrap">
                                                <?php if ($inv['settlement_status'] === 'SETTLED'): ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0] text-xs font-semibold">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#059669]"></span>
                                                        <span>Settled</span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-xs font-semibold">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                        <span>Unsettled</span>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Actions -->
                                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                                <div class="flex items-center justify-end gap-2">
                                                    <!-- Invoice View Link -->
                                                    <a href="<?= url('admin/invoice_view.php?id=' . $inv['id']) ?>" target="_blank"
                                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition"
                                                       title="View Detailed Tax Invoice">
                                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i>
                                                        <span>Invoice</span>
                                                    </a>

                                                    <!-- Quick Toggle Settlement Status -->
                                                    <form method="POST" class="inline" onsubmit="return confirm('Toggle settlement status for this invoice?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="form_action" value="update_settlement">
                                                        <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                                                        <input type="hidden" name="settlement_status" value="<?= $inv['settlement_status'] === 'SETTLED' ? 'UNSETTLED' : 'SETTLED' ?>">
                                                        <button type="submit" 
                                                                class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" 
                                                                title="<?= $inv['settlement_status'] === 'SETTLED' ? 'Mark as Unsettled' : 'Mark as Settled' ?>">
                                                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                    </form>
                                                </div>
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
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 relative">
            <button onclick="document.getElementById('newInvoiceModal').classList.add('hidden')" 
                    class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 font-bold text-sm">
                ✕
            </button>

            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                    <i data-lucide="receipt" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 leading-tight">Generate B2B Tax Invoice</h3>
                    <p class="text-xs text-slate-500">Calculate platform commission &amp; GST liability</p>
                </div>
            </div>

            <form method="POST" action="<?= url('admin/revenue.php') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="create_invoice">

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Startup Company</label>
                    <select name="company_id" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Select Startup --</option>
                        <?php foreach ($companiesList as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['cin_number'] ?: 'No CIN') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Funding Round</label>
                    <select name="funding_round_id" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Select Round --</option>
                        <?php foreach ($roundsList as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['round_name']) ?> &middot; Raised: ₹<?= number_format((float)$r['amount_raised']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Gross Escrow Raised (₹)</label>
                        <input type="number" step="1000" name="gross_amount_raised" value="5000000" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Commission Rate (%)</label>
                        <input type="number" step="0.1" name="commission_rate_percent" value="3.0" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Tech &amp; DD Fee (₹)</label>
                        <input type="number" step="100" name="tech_fee" value="25000" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Settlement Status</label>
                        <select name="settlement_status" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                            <option value="SETTLED">Settled (Auto Escrow)</option>
                            <option value="UNSETTLED">Unsettled (Pending)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('newInvoiceModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#4338ca] hover:bg-[#3730a3] text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition">
                        Create Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
