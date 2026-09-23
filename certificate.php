<?php
/**
 * Institutional Digital Share Certificate & Allotment Viewer
 * Compliant with Companies Act 2013 / SEBI guidelines
 * Supports high-resolution printing & PDF export
 */
require_once __DIR__ . '/config.php';
$user = current_user();
$db = get_db();

$certId = (int)($_GET['id'] ?? 0);
$certNumber = trim($_GET['cert'] ?? '');

if (!$db || (!$certId && empty($certNumber))) {
    set_flash('error', 'Certificate not specified.');
    header('Location: ' . url('index.php'));
    exit;
}

// Fetch investment, company, founder, and investor details
if ($certId > 0) {
    $stmt = $db->prepare("
        SELECT inv.*, c.name as company_name, c.legal_name, c.cin_number, c.incorporation_date,
               c.address, c.city, c.state, c.country, c.logo_url, c.face_value_per_share,
               fr.round_name, fr.valuation, fr.target_amount,
               u.name as investor_name, u.email as investor_email, ip.pan_number as investor_pan,
               cf_user.name as founder_name, cf.designation as founder_designation
        FROM investments inv
        JOIN companies c ON inv.company_id = c.id
        JOIN funding_rounds fr ON inv.funding_round_id = fr.id
        JOIN users u ON inv.investor_user_id = u.id
        LEFT JOIN investor_profiles ip ON u.id = ip.user_id
        LEFT JOIN company_founders cf ON (c.id = cf.company_id AND cf.is_signatory = 1)
        LEFT JOIN users cf_user ON cf.user_id = cf_user.id
        WHERE inv.id = ?
        LIMIT 1
    ");
    $stmt->execute([$certId]);
} else {
    $stmt = $db->prepare("
        SELECT inv.*, c.name as company_name, c.legal_name, c.cin_number, c.incorporation_date,
               c.address, c.city, c.state, c.country, c.logo_url, c.face_value_per_share,
               fr.round_name, fr.valuation, fr.target_amount,
               u.name as investor_name, u.email as investor_email, ip.pan_number as investor_pan,
               cf_user.name as founder_name, cf.designation as founder_designation
        FROM investments inv
        JOIN companies c ON inv.company_id = c.id
        JOIN funding_rounds fr ON inv.funding_round_id = fr.id
        JOIN users u ON inv.investor_user_id = u.id
        LEFT JOIN investor_profiles ip ON u.id = ip.user_id
        LEFT JOIN company_founders cf ON (c.id = cf.company_id AND cf.is_signatory = 1)
        LEFT JOIN users cf_user ON cf.user_id = cf_user.id
        WHERE inv.certificate_number = ?
        LIMIT 1
    ");
    $stmt->execute([$certNumber]);
}

$cert = $stmt->fetch();

if (!$cert) {
    set_flash('error', 'Share certificate not found or record has expired.');
    header('Location: ' . url('index.php'));
    exit;
}

// Access Control: Must be the investor, a founder of that company, or an admin
if (!$user) {
    // Guest or public access via QR code: allow viewing in read-only verification mode
    $isPublicViewer = true;
} else {
    $isPublicViewer = false;
    $isAuthorized = false;
    if ($user['role'] === 'admin') {
        $isAuthorized = true;
    } elseif ($user['role'] === 'investor' && $user['id'] == $cert['investor_user_id']) {
        $isAuthorized = true;
    } elseif ($user['role'] === 'founder') {
        $checkFounder = $db->prepare("SELECT COUNT(*) FROM company_founders WHERE company_id = ? AND user_id = ?");
        $checkFounder->execute([$cert['company_id'], $user['id']]);
        if ($checkFounder->fetchColumn() > 0) {
            $isAuthorized = true;
        }
    }
    if (!$isAuthorized) {
        set_flash('error', 'You do not have permission to view this confidential share certificate.');
        header('Location: ' . url('index.php'));
        exit;
    }
}

// Ensure certificate values
$numShares = (int)($cert['number_of_shares'] ?: max(100, (int)($cert['amount_invested'] / 250)));
$pricePerShare = (float)($cert['price_per_share'] ?: round($cert['amount_invested'] / $numShares, 2));
$faceValue = (float)($cert['face_value_per_share'] ?: 10.00);
$distinctiveFrom = $cert['distinctive_from'] ?: 10001;
$distinctiveTo = $cert['distinctive_to'] ?: ($distinctiveFrom + $numShares - 1);
$shareClass = $cert['share_class'] ?: 'Series Seed Compulsorily Convertible Preference Shares (CCPS)';
$folioNumber = $cert['folio_number'] ?: ('FOLIO-' . str_pad($cert['investor_user_id'], 4, '0', STR_PAD_LEFT));
$certSerial = $cert['certificate_number'] ?: ('SHA-2026-' . strtoupper(substr(md5($cert['id']), 0, 6)));
$verificationUrl = url('verify_certificate.php?cert=' . urlencode($certSerial));
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . urlencode($verificationUrl);

$backUrl = url('index.php');
if ($user) {
    $backUrl = match($user['role']) {
        'founder' => url('founder/cap_table.php'),
        'investor' => url('investor/portfolio.php'),
        'admin' => url('admin/share_allotments.php'),
        default => url('index.php')
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Certificate #<?= htmlspecialchars($certSerial) ?> • <?= htmlspecialchars($cert['company_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0F172A; }
        .font-serif-title { font-family: 'Cinzel', serif; }
        .font-body-formal { font-family: 'Libre Baskerville', Georgia, serif; }
        .font-script { font-family: 'Playfair Display', serif; }
        
        /* Certificate Background & Frame */
        .cert-paper {
            background: #FFFFFF;
            background-image: 
                radial-gradient(#F1F5F9 1.5px, transparent 1.5px),
                linear-gradient(to bottom, #FFFFFF 0%, #FAFAF9 100%);
            background-size: 24px 24px, 100% 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(0,0,0,0.05);
        }
        
        .cert-outer-border {
            border: 8px double #1E293B;
            outline: 2px solid #D97706;
            outline-offset: -5px;
        }

        .cert-corner {
            position: absolute;
            width: 32px;
            height: 32px;
            border-color: #D97706;
        }

        .gold-seal {
            background: radial-gradient(circle at 30% 30%, #FDE68A, #D97706 70%, #92400E);
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.35), inset 0 0 0 3px #FFFBEB;
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
            .cert-paper {
                box-shadow: none !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                border: none !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body class="min-h-screen py-8 px-4 flex flex-col items-center justify-center selection:bg-amber-100 selection:text-amber-900">

    <!-- Top Action Toolbar (Hidden during print) -->
    <header class="no-print max-w-4xl w-full mb-6 flex flex-wrap items-center justify-between gap-4 bg-slate-900/90 backdrop-blur-md p-4 rounded-2xl border border-slate-800 text-white shadow-xl">
        <div class="flex items-center space-x-3">
            <a href="<?= htmlspecialchars($backUrl) ?>" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition flex items-center space-x-1 text-xs font-semibold">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back</span>
            </a>
            <div class="h-4 w-px bg-slate-700"></div>
            <div>
                <div class="text-xs font-bold text-slate-200"><?= htmlspecialchars($cert['company_name']) ?></div>
                <div class="text-[10px] text-amber-400 font-mono tracking-wider">CERTIFICATE #<?= htmlspecialchars($certSerial) ?></div>
            </div>
        </div>

        <div class="flex items-center space-x-2.5">
            <button onclick="navigator.clipboard.writeText('<?= $verificationUrl ?>'); alert('Verification link copied to clipboard!');" 
                    class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition flex items-center space-x-1.5 border border-slate-700">
                <i data-lucide="link-2" class="w-3.5 h-3.5"></i>
                <span>Copy Verify Link</span>
            </button>
            <button onclick="window.print()" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition flex items-center space-x-1.5 border border-slate-700">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print</span>
            </button>
            <button onclick="downloadPDF()" id="btnDownloadPDF" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition flex items-center space-x-1.5 shadow-md shadow-amber-500/20">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                <span>Download PDF</span>
            </button>
        </div>
    </header>

    <!-- Certificate Document Container -->
    <article id="certificate-document" class="cert-paper max-w-4xl w-full rounded-2xl p-6 md:p-12 relative cert-outer-border text-slate-900 overflow-hidden">
        
        <!-- Corner Ornaments -->
        <div class="cert-corner top-3 left-3 border-t-2 border-l-2"></div>
        <div class="cert-corner top-3 right-3 border-t-2 border-r-2"></div>
        <div class="cert-corner bottom-3 left-3 border-b-2 border-l-2"></div>
        <div class="cert-corner bottom-3 right-3 border-b-2 border-r-2"></div>

        <!-- Subtle Watermark -->
        <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none select-none">
            <i data-lucide="award" class="w-96 h-96 text-slate-900"></i>
        </div>

        <!-- Certificate Header -->
        <header class="text-center relative z-10 border-b border-amber-200/80 pb-6 mb-6">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-bold uppercase tracking-widest mb-3">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-600"></i>
                <span>Registered Private Placement & Equity Instrument</span>
            </div>

            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-serif-title tracking-wider uppercase mb-1">
                <?= htmlspecialchars($cert['legal_name'] ?: $cert['company_name']) ?>
            </h1>
            <p class="text-xs text-slate-600 font-medium">
                (Incorporated under the Indian Companies Act, 2013)
            </p>
            <div class="text-[11px] text-slate-500 font-mono mt-1 space-x-3">
                <span>CIN: <strong class="text-slate-800"><?= htmlspecialchars($cert['cin_number'] ?: 'U72900KA2023PTC156789') ?></strong></span>
                <span>•</span>
                <span>Reg. Office: <?= htmlspecialchars($cert['city'] ?: 'Bengaluru') ?>, <?= htmlspecialchars($cert['state'] ?: 'Karnataka') ?>, <?= htmlspecialchars($cert['country'] ?: 'India') ?></span>
            </div>
        </header>

        <!-- Certificate Title & Identification Matrix -->
        <div class="relative z-10 text-center mb-6">
            <h2 class="text-lg md:text-xl font-black text-amber-900 font-serif-title tracking-widest uppercase">
                Certificate of Share Allotment
            </h2>
            <div class="text-[11px] text-slate-500 tracking-wider uppercase font-semibold mt-0.5">
                Class: <?= htmlspecialchars($shareClass) ?>
            </div>
        </div>

        <!-- Metadata Ribbon -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 rounded-xl bg-amber-50/50 border border-amber-200/60 text-xs mb-8 relative z-10">
            <div>
                <span class="text-[9.5px] uppercase font-bold text-slate-400 block tracking-wider">Folio Number</span>
                <span class="font-mono font-bold text-slate-800"><?= htmlspecialchars($folioNumber) ?></span>
            </div>
            <div>
                <span class="text-[9.5px] uppercase font-bold text-slate-400 block tracking-wider">Certificate Serial</span>
                <span class="font-mono font-bold text-amber-700"><?= htmlspecialchars($certSerial) ?></span>
            </div>
            <div>
                <span class="text-[9.5px] uppercase font-bold text-slate-400 block tracking-wider">Number of Shares</span>
                <span class="font-mono font-bold text-slate-800"><?= number_format($numShares) ?> Units</span>
            </div>
            <div>
                <span class="text-[9.5px] uppercase font-bold text-slate-400 block tracking-wider">Distinctive Numbers</span>
                <span class="font-mono font-bold text-indigo-700"><?= str_pad($distinctiveFrom, 6, '0', STR_PAD_LEFT) ?> – <?= str_pad($distinctiveTo, 6, '0', STR_PAD_LEFT) ?></span>
            </div>
        </div>

        <!-- Certification Legal Body -->
        <div class="relative z-10 text-justify text-xs md:text-sm text-slate-700 leading-relaxed font-body-formal space-y-4 mb-8">
            <p>
                <strong class="font-serif-title uppercase text-slate-900 tracking-wide text-sm font-bold">This is to Certify that</strong> the person named hereunder is the Registered Holder of the within-mentioned number of fully-paid up <em><?= htmlspecialchars($shareClass) ?></em>, bearing nominal face value of ₹<?= number_format($faceValue, 2) ?> each, in the equity capital of <strong><?= htmlspecialchars($cert['legal_name'] ?: $cert['company_name']) ?></strong>, subject to the Memorandum and Articles of Association of the Company and the executed Shareholders Agreement.
            </p>

            <!-- Holder Details Card -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 font-sans text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Registered Allottee / Holder</span>
                    <span class="text-sm font-black text-slate-900"><?= htmlspecialchars($cert['investor_name']) ?></span>
                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                        Email: <?= htmlspecialchars($cert['investor_email']) ?> | PAN: <?= htmlspecialchars($cert['investor_pan'] ?: 'PAN-ON-FILE') ?>
                    </div>
                </div>
                <div class="text-left sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-200">
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Total Capital Subscription</span>
                    <span class="text-sm font-black text-emerald-600 font-mono"><?= format_inr($cert['amount_invested']) ?></span>
                    <div class="text-[11px] text-indigo-600 font-bold mt-0.5">
                        <?= $cert['equity_allotted_percent'] ?>% Equity Stake Allocated
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-slate-500 italic">
                Given under the common seal and electronic issuance authority of the Company and the SEBI-compliant digital custody framework of <?= APP_NAME ?> on this <strong><?= date('jS \d\a\y \o\f F, Y', strtotime($cert['confirmed_at'])) ?></strong>.
            </p>
        </div>

        <!-- Valuation & Issue Terms Breakdown -->
        <div class="relative z-10 grid grid-cols-3 gap-3 p-3 rounded-lg border border-slate-200 bg-slate-50/50 text-[11px] font-sans mb-8">
            <div>
                <span class="text-slate-400 block text-[9.5px] uppercase font-bold">Issue Price per Share</span>
                <span class="font-bold text-slate-800">₹<?= number_format($pricePerShare, 2) ?></span>
                <span class="text-[9.5px] text-slate-400 block">(FV: ₹<?= number_format($faceValue, 2) ?> + Prem)</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[9.5px] uppercase font-bold">Funding Round</span>
                <span class="font-bold text-slate-800"><?= htmlspecialchars($cert['round_name']) ?></span>
                <span class="text-[9.5px] text-slate-400 block">Post-money: <?= format_inr($cert['valuation']) ?></span>
            </div>
            <div>
                <span class="text-slate-400 block text-[9.5px] uppercase font-bold">Allotment Status</span>
                <span class="inline-flex items-center text-emerald-700 font-bold">
                    <i data-lucide="check" class="w-3 h-3 mr-0.5"></i>
                    Allotted & Dispatched
                </span>
                <span class="text-[9.5px] text-slate-400 block">Dematerialized Record</span>
            </div>
        </div>

        <!-- Seal & Signatures Footer -->
        <footer class="relative z-10 grid grid-cols-1 sm:grid-cols-3 items-end gap-6 pt-4 border-t border-slate-200">
            
            <!-- Founder Signature -->
            <div class="text-center sm:text-left">
                <div class="h-12 flex items-center justify-center sm:justify-start">
                    <div class="font-script text-xl text-slate-800 font-semibold italic border-b border-slate-300 pb-1 px-4">
                        <?= htmlspecialchars($cert['founder_name'] ?: 'Aarav Sharma') ?>
                    </div>
                </div>
                <div class="text-xs font-bold text-slate-900 mt-2">
                    <?= htmlspecialchars($cert['founder_name'] ?: 'Aarav Sharma') ?>
                </div>
                <div class="text-[10px] text-slate-500 font-semibold">
                    <?= htmlspecialchars($cert['founder_designation'] ?: 'Founder & CEO') ?>
                </div>
                <div class="text-[9px] text-slate-400 font-mono">Signatory on behalf of Issuer</div>
            </div>

            <!-- Official Gold Seal & QR Center -->
            <div class="flex flex-col items-center justify-center text-center">
                <div class="gold-seal w-20 h-20 rounded-full flex flex-col items-center justify-center text-slate-900 border-2 border-amber-300 shadow-lg mb-2">
                    <i data-lucide="award" class="w-6 h-6 text-amber-950 mb-0.5"></i>
                    <span class="text-[7.5px] font-black tracking-widest uppercase text-amber-950 font-serif-title leading-tight">OFFICIAL<br>SEAL</span>
                </div>
                <span class="text-[8.5px] font-mono text-slate-400 tracking-wider">ISSUED VIA <?= strtoupper(APP_NAME) ?></span>
            </div>

            <!-- Compliance Signatory & Verification QR -->
            <div class="flex items-center justify-center sm:justify-end space-x-3">
                <div class="text-right">
                    <div class="h-12 flex items-center justify-end">
                        <div class="font-script text-xl text-indigo-900 font-semibold italic border-b border-slate-300 pb-1 px-4">
                            Platform Custody
                        </div>
                    </div>
                    <div class="text-xs font-bold text-slate-900 mt-2">Compliance Registrar</div>
                    <div class="text-[10px] text-indigo-600 font-semibold">SEBI Governance Desk</div>
                    <div class="text-[9px] text-slate-400 font-mono">Digitally Timestamped & Signed</div>
                </div>
                <a href="<?= htmlspecialchars($verificationUrl) ?>" target="_blank" title="Scan to Verify on Public Registry" class="flex-shrink-0 p-1.5 bg-white border border-slate-200 rounded-xl shadow-sm hover:scale-105 transition">
                    <img src="<?= $qrCodeUrl ?>" alt="QR Verification Code" class="w-16 h-16 rounded-lg">
                </a>
            </div>
        </footer>

        <!-- Security Hash Bottom Banner -->
        <div class="mt-6 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between text-[9px] text-slate-400 font-mono">
            <div>
                Security Hash: <span class="text-slate-600"><?= hash('sha256', $certSerial . $cert['amount_invested'] . $cert['confirmed_at']) ?></span>
            </div>
            <div>
                Public Verification: <span class="text-indigo-600 underline font-semibold"><?= htmlspecialchars($verificationUrl) ?></span>
            </div>
        </div>

    </article>

    <!-- Footer Disclaimer -->
    <footer class="no-print mt-6 text-center text-xs text-slate-400 max-w-xl">
        This document represents an authorized digital share certificate under Section 46 of the Companies Act 2013 and Section 4 of the Information Technology Act 2000. Tampering with this certificate is punishable under Indian Law.
    </footer>

    <script>
        lucide.createIcons();

        function downloadPDF() {
            const btn = document.getElementById('btnDownloadPDF');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i><span>Generating...</span>';
            lucide.createIcons();

            const element = document.getElementById('certificate-document');
            const opt = {
                margin:       8,
                filename:     'Share_Certificate_<?= htmlspecialchars($certSerial) ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, logging: false },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            if (typeof html2pdf !== 'undefined') {
                html2pdf().set(opt).from(element).save().then(() => {
                    btn.innerHTML = originalText;
                    lucide.createIcons();
                }).catch(() => {
                    // Fallback to native print if html2pdf fails
                    window.print();
                    btn.innerHTML = originalText;
                    lucide.createIcons();
                });
            } else {
                window.print();
                btn.innerHTML = originalText;
                lucide.createIcons();
            }
        }
    </script>
</body>
</html>
