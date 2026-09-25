<?php
/**
 * Admin Module: Platform Commission & Revenue Analytics Hub
 * Tracks platform monetization, GTV, 3% success fees, GST liabilities, and B2B invoices
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Commission & Platform Revenue Analytics';

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
    // 1. Gross Transaction Value (Total Escrow / Total Raised)
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

    // 3. Companies & Rounds for modal
    $companiesList = $db->query("SELECT id, name, cin_number FROM companies ORDER BY name ASC")->fetchAll();
    $roundsList = $db->query("SELECT fr.id, fr.company_id, fr.round_name, fr.amount_raised, c.name as company_name FROM funding_rounds fr JOIN companies c ON fr.company_id = c.id ORDER BY fr.id DESC")->fetchAll();

    // 4. Invoices query
    $query = "
        SELECT pi.*, c.name as company_name, c.legal_name, c.cin_number, fr.round_name
        FROM platform_invoices pi
        JOIN companies c ON pi.company_id = c.id
        JOIN funding_rounds fr ON pi.funding_round_id = fr.id
    ";
    $params = [];
    if ($statusFilter !== 'ALL') {
        $query .= " WHERE pi.settlement_status = ?";
        $params[] = $statusFilter;
    }
    $query .= " ORDER BY pi.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue & Commission Hub • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean { background: #FFFFFF; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.03); }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- Admin Navbar -->
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="revenue-main">

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
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Commission & Platform Revenue Analytics</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Monetization tracking, 3% success fees, B2B GST tax invoices, and escrow commission settlements.</p>
                </div>
                
                <div class="flex items-center space-x-2.5">
                    <button onclick="document.getElementById('newInvoiceModal').classList.remove('hidden')" 
                            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition flex items-center space-x-1.5 shadow-sm shadow-indigo-600/20">
                        <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                        <span>Generate Tax Invoice</span>
                    </button>
                </div>
            </div>

            <!-- Revenue KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Gross Capital Volume (GTV)</div>
                    <div class="text-lg font-black text-slate-900"><?= format_inr($gtv) ?></div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Total capital routed</div>
                </div>

                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Platform Invoices</div>
                    <div class="text-lg font-black text-indigo-600"><?= format_inr($totalPlatformRevenue) ?></div>
                    <div class="text-[10px] text-indigo-600/80 mt-0.5 font-semibold">Gross fees + GST</div>
                </div>

                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Net Platform Revenue</div>
                    <div class="text-lg font-black text-emerald-600"><?= format_inr($netRevenue) ?></div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Retained earnings (Excl. GST)</div>
                </div>

                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">GST Tax Collected (18%)</div>
                    <div class="text-lg font-black text-amber-600"><?= format_inr($gstLiability) ?></div>
                    <div class="text-[10px] text-slate-500 mt-0.5">CGST 9% + SGST 9%</div>
                </div>

                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Pending Settlements</div>
                    <div class="text-lg font-black text-rose-600"><?= format_inr($pendingRevenue) ?></div>
                    <div class="text-[10px] text-rose-500 mt-0.5 font-semibold">Escrow clearance buffer</div>
                </div>
            </div>

            <!-- Revenue Intelligence Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Monetization Streams -->
                <div class="card-clean rounded-2xl p-6 lg:col-span-2">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Platform Monetization Streams</span>
                        </h2>
                        <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                            Automated Escrow Deduction
                        </span>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-slate-800">Success Carry / Platform Commission (3.00%)</span>
                                <span class="font-mono font-black text-slate-900"><?= format_inr(max(0, $netRevenue - (count($invoices) * 25000))) ?></span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-600 rounded-full" style="width: 82%"></div>
                            </div>
                            <div class="text-[10.5px] text-slate-400 mt-1">Calculated on gross escrow amount committed upon round closure.</div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-slate-800">Technical Diligence & Onboarding Infrastructure Fee</span>
                                <span class="font-mono font-black text-slate-900"><?= format_inr(count($invoices) * 25000) ?></span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: 18%"></div>
                            </div>
                            <div class="text-[10.5px] text-slate-400 mt-1">Fixed ₹25,000 per round covering KYC, DigiLocker verification, and digital share demat registry.</div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-slate-800">Statutory GST (18.00% Indian Tax Remittance)</span>
                                <span class="font-mono font-black text-amber-600"><?= format_inr($gstLiability) ?></span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-400 rounded-full" style="width: 100%"></div>
                            </div>
                            <div class="text-[10.5px] text-slate-400 mt-1">Held in tax liability reserve for monthly GSTR-1 & GSTR-3B filings.</div>
                        </div>
                    </div>
                </div>

                <!-- Settlement Status Card -->
                <div class="card-clean rounded-2xl p-6">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center space-x-1.5">
                        <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Settlement Health & Pipeline</span>
                    </h2>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 mb-4 text-center">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Settled & Realized Funds</div>
                        <div class="text-2xl font-black text-emerald-600 mt-0.5"><?= format_inr($settledRevenue) ?></div>
                        <div class="text-[10px] text-slate-500 mt-1">
                            <?= round(($totalPlatformRevenue > 0 ? ($settledRevenue / $totalPlatformRevenue) * 100 : 100)) ?>% of total invoiced revenue realized
                        </div>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between p-2 rounded-lg bg-emerald-50/60 border border-emerald-100">
                            <span class="text-emerald-800 font-semibold flex items-center space-x-1">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Settled Invoices</span>
                            </span>
                            <span class="font-bold text-emerald-900 font-mono">
                                <?= count(array_filter($invoices, fn($x) => $x['settlement_status'] === 'SETTLED')) ?>
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-2 rounded-lg bg-rose-50/60 border border-rose-100">
                            <span class="text-rose-800 font-semibold flex items-center space-x-1">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                <span>Unsettled / Pending</span>
                            </span>
                            <span class="font-bold text-rose-900 font-mono">
                                <?= count(array_filter($invoices, fn($x) => $x['settlement_status'] !== 'SETTLED')) ?>
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 text-[10px] text-slate-400">
                        Commission is automatically deducted during founder milestone tranche disbursements.
                    </div>
                </div>

            </div>

            <!-- Tax Invoices & Settlements Ledger -->
            <div class="card-clean rounded-2xl p-6">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="file-text" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>B2B GST Tax Invoices & Settlement Ledger</span>
                        </h2>
                        <p class="text-[11px] text-slate-400 mt-0.5">Statutory invoices generated for platform commission and due diligence services.</p>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="flex items-center space-x-1.5 bg-slate-100 p-1 rounded-xl text-xs font-semibold">
                        <a href="<?= url('admin/revenue.php?status_filter=ALL') ?>" 
                           class="px-3 py-1 rounded-lg transition <?= $statusFilter === 'ALL' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800' ?>">
                            All
                        </a>
                        <a href="<?= url('admin/revenue.php?status_filter=SETTLED') ?>" 
                           class="px-3 py-1 rounded-lg transition <?= $statusFilter === 'SETTLED' ? 'bg-white text-emerald-600 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800' ?>">
                            Settled
                        </a>
                        <a href="<?= url('admin/revenue.php?status_filter=UNSETTLED') ?>" 
                           class="px-3 py-1 rounded-lg transition <?= $statusFilter === 'UNSETTLED' ? 'bg-white text-rose-600 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800' ?>">
                            Unsettled
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="pb-3">Invoice Number</th>
                                <th class="pb-3">Billed Startup</th>
                                <th class="pb-3">Round / Gross Raised</th>
                                <th class="pb-3">Fee Breakdown</th>
                                <th class="pb-3">GST (18%)</th>
                                <th class="pb-3">Total Payable</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-xs text-slate-400">
                                        No platform invoices found. Click "Generate Tax Invoice" above to create an invoice.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3.5 font-mono font-bold text-indigo-600">
                                            <?= htmlspecialchars($inv['invoice_number']) ?>
                                            <div class="text-[9.5px] text-slate-400 font-sans"><?= date('d M Y', strtotime($inv['invoice_date'])) ?></div>
                                        </td>
                                        <td class="py-3.5">
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($inv['company_name']) ?></div>
                                            <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($inv['cin_number'] ?: 'CIN Verified') ?></div>
                                        </td>
                                        <td class="py-3.5">
                                            <div class="font-bold text-slate-800"><?= htmlspecialchars($inv['round_name']) ?></div>
                                            <div class="text-[10px] text-emerald-600 font-mono font-semibold">GTV: <?= format_inr($inv['gross_amount_raised']) ?></div>
                                        </td>
                                        <td class="py-3.5 font-mono text-[11px]">
                                            <span class="font-bold text-slate-800"><?= format_inr($inv['commission_amount']) ?></span> 
                                            <span class="text-[10px] text-slate-400">(<?= $inv['commission_rate_percent'] ?>%)</span>
                                            <div class="text-[10px] text-slate-400">+ <?= format_inr($inv['tech_fee']) ?> Tech</div>
                                        </td>
                                        <td class="py-3.5 font-mono text-amber-600 font-semibold text-[11px]">
                                            <?= format_inr($inv['gst_amount']) ?>
                                            <div class="text-[9.5px] text-slate-400 font-sans">9% CGST + 9% SGST</div>
                                        </td>
                                        <td class="py-3.5 font-mono font-black text-slate-900 text-xs">
                                            <?= format_inr($inv['total_payable']) ?>
                                        </td>
                                        <td class="py-3.5">
                                            <?php if ($inv['settlement_status'] === 'SETTLED'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    ✓ Settled
                                                </span>
                                            <?php elseif ($inv['settlement_status'] === 'PROCESSING'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                    Processing
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                    Unsettled
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 text-right space-x-1.5 whitespace-nowrap">
                                            <a href="<?= url('admin/invoice_view.php?id=' . $inv['id']) ?>" target="_blank"
                                               class="inline-flex items-center space-x-1 px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 text-xs font-semibold transition border border-slate-200">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                <span>View Invoice</span>
                                            </a>

                                            <!-- Toggle Settlement Form -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="form_action" value="update_settlement">
                                                <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                                                <input type="hidden" name="settlement_status" value="<?= $inv['settlement_status'] === 'SETTLED' ? 'UNSETTLED' : 'SETTLED' ?>">
                                                <button type="submit" 
                                                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition inline-flex items-center" 
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

        </main>
    </div>

    <!-- Create Tax Invoice Modal -->
    <div id="newInvoiceModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 relative animate-in fade-in zoom-in-95 duration-200">
            <button onclick="document.getElementById('newInvoiceModal').classList.add('hidden')" 
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="flex items-center space-x-2.5 mb-4">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="receipt" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Generate B2B GST Platform Invoice</h3>
                    <p class="text-[11px] text-slate-500">Calculate platform commission, due diligence tech fees, and GST tax.</p>
                </div>
            </div>

            <form method="POST" action="<?= url('admin/revenue.php') ?>" class="space-y-3.5 text-xs">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="form_action" value="create_invoice">

                <div>
                    <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Select Startup</label>
                    <select name="company_id" id="modalCompanySelect" required onchange="filterRounds(this.value)"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                        <option value="">Choose Startup...</option>
                        <?php foreach ($companiesList as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Funding Round</label>
                    <select name="funding_round_id" id="modalRoundSelect" required 
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                        <option value="">Select Round...</option>
                        <?php foreach ($roundsList as $r): ?>
                            <option value="<?= $r['id'] ?>" data-company="<?= $r['company_id'] ?>" data-raised="<?= $r['amount_raised'] ?>">
                                <?= htmlspecialchars($r['company_name']) ?> — <?= htmlspecialchars($r['round_name']) ?> (<?= format_inr($r['amount_raised']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Gross Capital Raised (INR)</label>
                        <input type="number" step="0.01" name="gross_amount_raised" id="modalGrossRaised" required placeholder="e.g. 5000000" 
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Commission Rate (%)</label>
                        <input type="number" step="0.1" name="commission_rate_percent" value="3.0" required 
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Tech & DD Fee (INR)</label>
                        <input type="number" step="0.01" name="tech_fee" value="25000.00" required 
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">GST Tax Rate (%)</label>
                        <input type="number" step="0.1" name="gst_rate_percent" value="18.0" required 
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                    </div>
                </div>

                <div>
                    <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Settlement Status</label>
                    <select name="settlement_status" 
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 outline-none focus:bg-white focus:border-indigo-600">
                        <option value="SETTLED">SETTLED (Deducted from Escrow)</option>
                        <option value="UNSETTLED">UNSETTLED (Awaiting Payout)</option>
                        <option value="PROCESSING">PROCESSING</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Notes / Terms (Optional)</label>
                    <textarea name="notes" rows="2" placeholder="e.g. Platform success carry on Seed Round completion."
                              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 outline-none focus:bg-white focus:border-indigo-600"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('newInvoiceModal').classList.add('hidden')" 
                            class="px-3.5 py-2 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 font-semibold transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition shadow-sm">
                        Create & Issue Tax Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#revenue-main > *", { duration: 0.4, y: 12, opacity: 0, stagger: 0.06, ease: "power2.out" });

        function filterRounds(companyId) {
            const select = document.getElementById('modalRoundSelect');
            const grossInput = document.getElementById('modalGrossRaised');
            for (let i = 0; i < select.options.length; i++) {
                const opt = select.options[i];
                if (!opt.value) continue;
                if (!companyId || opt.getAttribute('data-company') === companyId) {
                    opt.style.display = 'block';
                } else {
                    opt.style.display = 'none';
                }
            }
        }

        document.getElementById('modalRoundSelect')?.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const raised = opt.getAttribute('data-raised');
            if (raised && parseFloat(raised) > 0) {
                document.getElementById('modalGrossRaised').value = raised;
            }
        });
    </script>
</body>
</html>
