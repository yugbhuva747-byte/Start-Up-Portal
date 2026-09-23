<?php
/**
 * B2B GST Tax Invoice Viewer & PDF/Print Generator
 * Compliant with Central Goods and Services Tax (CGST) Act 2017 & Indian B2B standards
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();

$invoiceId = (int)($_GET['id'] ?? 0);
$invoiceNumber = trim($_GET['inv'] ?? '');

if (!$db || (!$invoiceId && empty($invoiceNumber))) {
    set_flash('error', 'Invoice identifier not specified.');
    header('Location: ' . url('admin/revenue.php'));
    exit;
}

$stmt = null;
if ($invoiceId > 0) {
    $stmt = $db->prepare("
        SELECT pi.*, c.name as company_name, c.legal_name, c.cin_number, c.address, c.city, c.state, c.country,
               fr.round_name, fr.target_amount, fr.valuation
        FROM platform_invoices pi
        JOIN companies c ON pi.company_id = c.id
        JOIN funding_rounds fr ON pi.funding_round_id = fr.id
        WHERE pi.id = ?
        LIMIT 1
    ");
    $stmt->execute([$invoiceId]);
} else {
    $stmt = $db->prepare("
        SELECT pi.*, c.name as company_name, c.legal_name, c.cin_number, c.address, c.city, c.state, c.country,
               fr.round_name, fr.target_amount, fr.valuation
        FROM platform_invoices pi
        JOIN companies c ON pi.company_id = c.id
        JOIN funding_rounds fr ON pi.funding_round_id = fr.id
        WHERE pi.invoice_number = ?
        LIMIT 1
    ");
    $stmt->execute([$invoiceNumber]);
}

$inv = $stmt->fetch();

if (!$inv) {
    set_flash('error', 'Tax invoice record not found.');
    header('Location: ' . url('admin/revenue.php'));
    exit;
}

// Calculations
$commAmount = (float)$inv['commission_amount'];
$techFee = (float)$inv['tech_fee'];
$subtotal = (float)$inv['subtotal'];
$gstAmount = (float)$inv['gst_amount'];
$cgst = round($gstAmount / 2, 2);
$sgst = round($gstAmount / 2, 2);
$total = (float)$inv['total_payable'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice #<?= htmlspecialchars($inv['invoice_number']) ?> • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0F172A; }
        .font-mono-num { font-family: 'Space Grotesk', monospace; }
        
        .invoice-paper {
            background: #FFFFFF;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
        }

        @media print {
            body {
                background: transparent !important;
                padding: 0 !important;
                color: #000 !important;
            }
            .no-print {
                display: none !important;
            }
            .invoice-paper {
                box-shadow: none !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                border: none !important;
                padding: 15mm !important;
            }
            @page {
                size: A4 portrait;
                margin: 5mm;
            }
        }
    </style>
</head>
<body class="min-h-screen py-8 px-4 flex flex-col items-center justify-center selection:bg-indigo-100 selection:text-indigo-900">

    <!-- Top Action Toolbar -->
    <header class="no-print max-w-3xl w-full mb-6 flex items-center justify-between bg-slate-900/90 backdrop-blur-md p-4 rounded-2xl border border-slate-800 text-white shadow-xl">
        <div class="flex items-center space-x-3">
            <a href="<?= url('admin/revenue.php') ?>" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition flex items-center space-x-1 text-xs font-semibold">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Revenue Hub</span>
            </a>
            <div class="h-4 w-px bg-slate-700"></div>
            <div>
                <span class="text-xs font-bold text-slate-200"><?= htmlspecialchars($inv['company_name']) ?></span>
                <span class="text-[10px] text-indigo-400 font-mono block">INVOICE #<?= htmlspecialchars($inv['invoice_number']) ?></span>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-md shadow-indigo-600/30">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print / Save Tax Invoice</span>
            </button>
        </div>
    </header>

    <!-- Formal Tax Invoice Document -->
    <article class="invoice-paper max-w-3xl w-full rounded-2xl p-8 md:p-12 text-slate-900 border border-slate-200">
        
        <!-- Header & Supplier Info -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-6 border-b border-slate-200 pb-8 mb-8">
            <div>
                <div class="flex items-center space-x-2.5 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold">
                        <i data-lucide="zap" class="w-4 h-4"></i>
                    </div>
                    <span class="text-base font-extrabold tracking-tight text-slate-900">
                        <?= APP_NAME ?>
                    </span>
                </div>
                <div class="text-xs text-slate-600 font-bold">
                    Startup × Investor Platform Private Limited
                </div>
                <div class="text-[11px] text-slate-500 max-w-xs mt-1 leading-relaxed">
                    Level 4, Tech Park East, Koramangala Outer Ring Rd<br>
                    Bengaluru, Karnataka - 560034, India
                </div>
                <div class="text-[11px] text-slate-600 font-mono mt-2 space-y-0.5">
                    <div>GSTIN: <strong>29AAACS1234F1Z5</strong></div>
                    <div>PAN: <strong>AAACS1234F</strong></div>
                    <div>SAC Category: 997159 (Financial Intermediation)</div>
                </div>
            </div>

            <!-- Invoice Identification -->
            <div class="text-left sm:text-right">
                <div class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider mb-2 <?= $inv['settlement_status'] === 'SETTLED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                    Tax Invoice • <?= $inv['settlement_status'] ?>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight font-mono-num">
                    <?= htmlspecialchars($inv['invoice_number']) ?>
                </h1>
                <div class="text-xs text-slate-500 mt-1">
                    Invoice Date: <strong class="text-slate-800"><?= date('d F, Y', strtotime($inv['invoice_date'])) ?></strong>
                </div>
                <?php if ($inv['settled_at']): ?>
                    <div class="text-xs text-emerald-600 font-semibold mt-0.5">
                        Settled On: <?= date('d M Y, H:i', strtotime($inv['settled_at'])) ?>
                    </div>
                <?php endif; ?>
                <div class="text-[11px] text-slate-400 mt-1 font-mono">
                    Place of Supply: Karnataka (Code 29)
                </div>
            </div>
        </div>

        <!-- Billed To Recipient Details -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 p-4 rounded-xl bg-slate-50 border border-slate-200/80 mb-8 text-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block mb-1">Billed To (Startup Entity)</span>
                <div class="text-sm font-black text-slate-900"><?= htmlspecialchars($inv['legal_name'] ?: $inv['company_name']) ?></div>
                <div class="text-slate-600 font-mono text-[11px] mt-0.5">CIN: <?= htmlspecialchars($inv['cin_number'] ?: 'U72900KA2023PTC156789') ?></div>
                <div class="text-slate-500 mt-1 leading-relaxed">
                    <?= htmlspecialchars($inv['address'] ?: 'Startup Tech Hub') ?><br>
                    <?= htmlspecialchars($inv['city'] ?: 'Bengaluru') ?>, <?= htmlspecialchars($inv['state'] ?: 'Karnataka') ?>, <?= htmlspecialchars($inv['country'] ?: 'India') ?>
                </div>
            </div>

            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block mb-1">Deal & Escrow Context</span>
                <div class="font-bold text-slate-800"><?= htmlspecialchars($inv['round_name']) ?></div>
                <div class="text-slate-500 text-[11px] mt-0.5">
                    Gross Escrow Capital Raised: <strong class="text-emerald-600 font-mono"><?= format_inr($inv['gross_amount_raised']) ?></strong>
                </div>
                <div class="text-slate-500 text-[11px] mt-1">
                    Settlement Method: <strong class="text-slate-700"><?= htmlspecialchars($inv['payment_mode']) ?></strong>
                </div>
                <div class="text-[10.5px] text-slate-400 italic mt-1">
                    <?= htmlspecialchars($inv['notes'] ?? 'Platform carry deducted upon closing.') ?>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto mb-8">
            <table class="w-full text-left text-xs font-sans">
                <thead>
                    <tr class="border-b border-slate-200 text-[10px] uppercase font-bold text-slate-400">
                        <th class="pb-2.5">Item Description</th>
                        <th class="pb-2.5">SAC Code</th>
                        <th class="pb-2.5 text-center">Rate</th>
                        <th class="pb-2.5 text-right">Taxable Value (INR)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="py-3.5 pr-4">
                            <div class="font-bold text-slate-900">Platform Success Carry & Syndication Fee</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">3.00% success commission on gross capital raised (₹<?= number_format($inv['gross_amount_raised'], 2) ?>).</div>
                        </td>
                        <td class="py-3.5 font-mono text-slate-600 text-[11px]">997159</td>
                        <td class="py-3.5 text-center font-mono"><?= $inv['commission_rate_percent'] ?>%</td>
                        <td class="py-3.5 text-right font-mono font-bold text-slate-900">
                            ₹<?= number_format($commAmount, 2) ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-3.5 pr-4">
                            <div class="font-bold text-slate-900">Technical Infrastructure & Due Diligence Fee</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Investor KYC verification, dematerialized share registry, and digital cap table custody.</div>
                        </td>
                        <td class="py-3.5 font-mono text-slate-600 text-[11px]">998313</td>
                        <td class="py-3.5 text-center font-mono">Fixed</td>
                        <td class="py-3.5 text-right font-mono font-bold text-slate-900">
                            ₹<?= number_format($techFee, 2) ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Calculations Summary Table -->
        <div class="flex justify-end mb-8">
            <div class="w-full max-w-xs space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-slate-100 text-slate-600">
                    <span>Taxable Subtotal</span>
                    <span class="font-mono font-bold text-slate-900">₹<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-100 text-slate-600">
                    <span>Central GST (CGST @ 9.00%)</span>
                    <span class="font-mono font-semibold text-slate-800">₹<?= number_format($cgst, 2) ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-100 text-slate-600">
                    <span>State GST (SGST @ 9.00%)</span>
                    <span class="font-mono font-semibold text-slate-800">₹<?= number_format($sgst, 2) ?></span>
                </div>
                <div class="flex justify-between py-2 border-t-2 border-slate-900 text-slate-900 text-sm font-black">
                    <span>Total Amount Payable</span>
                    <span class="font-mono text-indigo-600 text-base">₹<?= number_format($total, 2) ?></span>
                </div>
                <div class="text-[10px] text-slate-400 italic text-right">
                    (Inclusive of 18.00% Statutory GST)
                </div>
            </div>
        </div>

        <!-- Bank & Remittance Instructions -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs mb-8">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Escrow Bank Account Details</span>
                <div class="font-mono text-[11px] text-slate-700 space-y-0.5">
                    <div>Bank: HDFC Bank Ltd, Koramangala</div>
                    <div>A/C Name: Startup Investor Escrow A/C</div>
                    <div>A/C Number: 50200088991122</div>
                    <div>IFSC Code: HDFC0001234</div>
                </div>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Settlement Certification</span>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    This fee has been certified by the SEBI Compliance & Escrow Trustee Desk and deducted from the closed round disbursement tranches.
                </p>
            </div>
        </div>

        <!-- Footer Signatures -->
        <div class="grid grid-cols-2 items-end pt-6 border-t border-slate-200 text-xs">
            <div>
                <div class="text-[10px] text-slate-400 uppercase font-bold">Terms & Conditions</div>
                <div class="text-[10px] text-slate-500 mt-0.5 max-w-xs">
                    Invoices are subject to the terms of the Master Platform Agreement. All disputes are subject to Bengaluru jurisdiction.
                </div>
            </div>
            <div class="text-right">
                <div class="font-script text-xl italic text-slate-800 font-semibold mb-1">
                    Finance Controller
                </div>
                <div class="font-bold text-slate-900">Authorized Signatory</div>
                <div class="text-[10px] text-slate-400">For Startup × Investor Platform Pvt Ltd</div>
            </div>
        </div>

    </article>

    <!-- Disclaimer -->
    <footer class="no-print mt-6 text-center text-xs text-slate-500">
        Electronic B2B Tax Invoice generated under Rule 46 of CGST Rules 2017.
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
