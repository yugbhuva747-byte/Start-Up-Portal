<?php
/**
 * Founder Module: Master Cap Table & Equity Allotments
 * Manage share capital, ESOP pool, investor equity allocations, and official share certificates
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Master Cap Table & Share Allotments';

$flash = get_flash();
$error = '';

$company = null;
$founders = [];
$investments = [];
$authorizedCapital = 10000000.00;
$faceValue = 10.00;
$esopPercent = 10.00;

if ($db) {
    // 1. Fetch founder's company
    $stmt = $db->prepare("
        SELECT c.* 
        FROM companies c 
        JOIN company_founders cf ON c.id = cf.company_id 
        WHERE cf.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $company = $stmt->fetch();

    if ($company) {
        $compId = $company['id'];
        $authorizedCapital = !empty($company['authorized_capital']) ? (float)$company['authorized_capital'] : 10000000.00;
        $faceValue = !empty($company['face_value_per_share']) ? (float)$company['face_value_per_share'] : 10.00;
        $esopPercent = isset($company['esop_pool_percent']) && $company['esop_pool_percent'] !== '' ? (float)$company['esop_pool_percent'] : 10.00;

        // Handle POST: Update Capital Structure
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? '')) {
                $error = 'Security validation failed. Please refresh and try again.';
            } else {
                $action = $_POST['form_action'] ?? '';
                if ($action === 'update_capital') {
                    $newAuth = (float)($_POST['authorized_capital'] ?? 10000000);
                    $newFace = (float)($_POST['face_value_per_share'] ?? 10);
                    $newEsop = (float)($_POST['esop_pool_percent'] ?? 10);

                    if ($newAuth <= 0) {
                        $error = 'Authorized share capital must be greater than zero.';
                    } elseif ($newFace <= 0) {
                        $error = 'Face value per share must be greater than zero.';
                    } elseif ($newEsop < 0 || $newEsop > 50) {
                        $error = 'ESOP pool allocation must be between 0% and 50%.';
                    } else {
                        $upStmt = $db->prepare("UPDATE companies SET authorized_capital = ?, face_value_per_share = ?, esop_pool_percent = ? WHERE id = ?");
                        $upStmt->execute([$newAuth, $newFace, $newEsop, $compId]);

                        log_audit($user['id'], 'UPDATE_CAP_STRUCTURE', 'companies', $compId, "Updated cap structure: Auth ₹{$newAuth}, FV ₹{$newFace}, ESOP {$newEsop}%");
                        set_flash('success', 'Capital structure parameters updated successfully.');
                        header('Location: ' . url('founder/cap_table.php'));
                        exit;
                    }
                }
            }
        }

        // 2. Fetch founders
        $fStmt = $db->prepare("
            SELECT cf.*, u.name as founder_name, u.email, u.avatar_url
            FROM company_founders cf
            JOIN users u ON cf.user_id = u.id
            WHERE cf.company_id = ?
        ");
        $fStmt->execute([$compId]);
        $founders = $fStmt->fetchAll();

        // 3. Fetch confirmed investor share allotments
        $invStmt = $db->prepare("
            SELECT inv.*, u.name as investor_name, u.email as investor_email,
                   fr.round_name, fr.valuation as round_valuation
            FROM investments inv
            JOIN users u ON inv.investor_user_id = u.id
            JOIN funding_rounds fr ON inv.funding_round_id = fr.id
            WHERE inv.company_id = ?
            ORDER BY inv.confirmed_at DESC
        ");
        $invStmt->execute([$compId]);
        $investments = $invStmt->fetchAll();

        // Handle CSV Export
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $cleanComp = preg_replace('/[^a-zA-Z0-9_-]/', '_', $company['name']);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=cap_table_' . $cleanComp . '_' . date('Ymd') . '.csv');
            $output = fopen('php://output', 'w');

            fputcsv($output, ['Certificate / Folio', 'Shareholder Name', 'Designation / Entity', 'Share Class', 'Number of Shares', 'Price Per Share (INR)', 'Distinctive Range', 'Capital Subscribed (INR)', 'Equity Stake (%)']);

            foreach ($founders as $f) {
                fputcsv($output, [
                    'FOLIO-0001',
                    $f['founder_name'] ?? 'Founder',
                    $f['designation'] ?? 'Co-Founder',
                    'Common Equity Shares',
                    'Core Common',
                    number_format($faceValue, 2),
                    '000001 - 010000',
                    'Initial Subscription',
                    number_format((float)($f['equity_percent'] ?? 0), 2) . '%'
                ]);
            }

            foreach ($investments as $inv) {
                $from = str_pad((string)($inv['distinctive_from'] ?? 1), 6, '0', STR_PAD_LEFT);
                $to = str_pad((string)($inv['distinctive_to'] ?? 1), 6, '0', STR_PAD_LEFT);
                fputcsv($output, [
                    $inv['certificate_number'] ?: ('FOLIO-' . ($inv['folio_number'] ?? '001')),
                    $inv['investor_name'] ?? 'Investor',
                    $inv['round_name'] ?? 'Funding Round',
                    $inv['share_class'] ?? 'CCPS',
                    (int)($inv['number_of_shares'] ?? 0),
                    (float)($inv['price_per_share'] ?? 0),
                    "{$from} - {$to}",
                    (float)($inv['amount_invested'] ?? 0),
                    number_format((float)($inv['equity_allotted_percent'] ?? 0), 3) . '%'
                ]);
            }

            fclose($output);
            exit;
        }
    }
}

// Calculate totals
$foundersTotalEquity = 0;
foreach ($founders as $f) $foundersTotalEquity += (float)$f['equity_percent'];

$investorsTotalEquity = 0;
$totalCapitalRaised = 0;
$totalSharesIssued = 0;
foreach ($investments as $inv) {
    $investorsTotalEquity += (float)($inv['equity_allotted_percent'] ?? 0);
    $totalCapitalRaised += (float)($inv['amount_invested'] ?? 0);
    $totalSharesIssued += (int)($inv['number_of_shares'] ?? 0);
}

$unallocatedPercent = max(0, 100 - ($foundersTotalEquity + $investorsTotalEquity + $esopPercent));
$totalAuthorizedShares = $faceValue > 0 ? (int)($authorizedCapital / $faceValue) : 1000000;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cap Table & Share Allotments • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean { background: #FFFFFF; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.03); }
        @media print {
            aside, nav, #founder-cap-actions, #mobile-sidebar-backdrop, .no-print { display: none !important; }
            body { background: #FFFFFF !important; color: #000000 !important; }
            main { padding: 0 !important; max-width: 100% !important; margin: 0 !important; }
            .card-clean { border: 1px solid #CBD5E1 !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- Founder Navbar -->
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="founder-cap-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border bg-rose-50 text-rose-700 border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!$company): ?>
                <div class="card-clean rounded-2xl p-12 text-center">
                    <i data-lucide="building" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
                    <h2 class="text-base font-bold text-slate-900">No Company Profile Linked</h2>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Please create and register your startup company profile before accessing the equity Cap Table.</p>
                    <a href="<?= url('founder/company.php') ?>" class="inline-flex items-center space-x-1.5 mt-4 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold shadow-sm">
                        <span>Setup Company Profile</span>
                    </a>
                </div>
            <?php else: ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-full">
                            Statutory MCA Register
                        </span>
                        <span class="text-xs text-slate-400 font-mono">CIN: <?= htmlspecialchars($company['cin_number'] ?: 'Verified') ?></span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight mt-1">
                        <?= htmlspecialchars($company['name']) ?> — Master Cap Table
                    </h1>
                    <p class="text-xs text-slate-500">Live ownership register, share certificate tracking, and dilution modeling.</p>
                </div>
                <div class="flex items-center flex-wrap gap-2" id="founder-cap-actions">
                    <button type="button" onclick="openSimModal()" class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 text-xs font-bold shadow-sm transition">
                        <i data-lucide="calculator" class="w-3.5 h-3.5"></i>
                        <span>Dilution Simulator</span>
                    </button>
                    <button type="button" onclick="openCapModal()" class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold shadow-sm transition">
                        <i data-lucide="sliders" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Edit Capital Structure</span>
                    </button>
                    <a href="<?= url('founder/cap_table.php?export=csv') ?>" class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold shadow-sm transition">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span>Export CSV</span>
                    </a>
                    <button type="button" onclick="window.print()" class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>Print Register</span>
                    </button>
                </div>
            </div>

            <!-- Capital Structure Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Authorized Share Capital</div>
                    <div class="text-lg font-black text-slate-900"><?= format_inr($authorizedCapital) ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5 font-mono"><?= number_format($totalAuthorizedShares) ?> Max Shares @ ₹<?= number_format($faceValue, 2) ?> FV</div>
                </div>

                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Capital Subscribed</div>
                    <div class="text-lg font-black text-emerald-600"><?= format_inr($totalCapitalRaised) ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">Raised across <?= count($investments) ?> allotment<?= count($investments) !== 1 ? 's' : '' ?></div>
                </div>

                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Founder Equity Holdings</div>
                    <div class="text-lg font-black text-indigo-600"><?= number_format($foundersTotalEquity, 2) ?>%</div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">Common voting stock</div>
                </div>

                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">ESOP Pool Allocated</div>
                    <div class="text-lg font-black text-amber-600"><?= number_format($esopPercent, 2) ?>%</div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">Talent incentive reserve</div>
                </div>
            </div>

            <!-- Visual Equity Breakdown Bar -->
            <div class="card-clean rounded-2xl p-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                        <i data-lucide="pie-chart" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Equity Dilution & Stake Distribution</span>
                    </h2>
                    <span class="text-xs font-bold text-slate-400">Total 100.00%</span>
                </div>

                <div class="w-full h-5 bg-slate-100 rounded-full overflow-hidden flex shadow-inner mb-3">
                    <div style="width: <?= min(100, max(0, $foundersTotalEquity)) ?>%" class="bg-indigo-600 transition-all duration-700" title="Founders: <?= $foundersTotalEquity ?>%"></div>
                    <div style="width: <?= min(100, max(0, $investorsTotalEquity)) ?>%" class="bg-emerald-500 transition-all duration-700" title="Investors: <?= $investorsTotalEquity ?>%"></div>
                    <div style="width: <?= min(100, max(0, $esopPercent)) ?>%" class="bg-amber-400 transition-all duration-700" title="ESOP: <?= $esopPercent ?>%"></div>
                    <?php if ($unallocatedPercent > 0): ?>
                        <div style="width: <?= min(100, max(0, $unallocatedPercent)) ?>%" class="bg-slate-300 transition-all duration-700" title="Unallocated: <?= $unallocatedPercent ?>%"></div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs pt-2">
                    <div class="p-2.5 rounded-xl bg-indigo-50/60 border border-indigo-100">
                        <div class="flex items-center space-x-1.5 mb-0.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            <span class="text-[11px] font-bold text-indigo-950">Founders</span>
                        </div>
                        <div class="text-base font-black text-indigo-600"><?= number_format($foundersTotalEquity, 2) ?>%</div>
                        <div class="text-[10px] text-slate-500"><?= count($founders) ?> Founder Co-owners</div>
                    </div>

                    <div class="p-2.5 rounded-xl bg-emerald-50/60 border border-emerald-100">
                        <div class="flex items-center space-x-1.5 mb-0.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-[11px] font-bold text-emerald-950">Investors</span>
                        </div>
                        <div class="text-base font-black text-emerald-600"><?= number_format($investorsTotalEquity, 2) ?>%</div>
                        <div class="text-[10px] text-slate-500"><?= count($investments) ?> Allottee Investors</div>
                    </div>

                    <div class="p-2.5 rounded-xl bg-amber-50/60 border border-amber-100">
                        <div class="flex items-center space-x-1.5 mb-0.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span class="text-[11px] font-bold text-amber-950">ESOP Pool</span>
                        </div>
                        <div class="text-base font-black text-amber-600"><?= number_format($esopPercent, 2) ?>%</div>
                        <div class="text-[10px] text-slate-500">Employee Options</div>
                    </div>

                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="flex items-center space-x-1.5 mb-0.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                            <span class="text-[11px] font-bold text-slate-700">Treasury Unallocated</span>
                        </div>
                        <div class="text-base font-black text-slate-700"><?= number_format($unallocatedPercent, 2) ?>%</div>
                        <div class="text-[10px] text-slate-400">Future Funding Buffer</div>
                    </div>
                </div>
            </div>

            <!-- Shareholder Register & Allotment Certificates Table -->
            <div class="card-clean rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="award" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Statutory Shareholder Register & Issued Certificates</span>
                        </h2>
                        <p class="text-[11px] text-slate-400 mt-0.5">Formal certificates issued under SEBI & Indian Companies Act electronic custody.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="pb-3">Certificate & Folio</th>
                                <th class="pb-3">Shareholder Name</th>
                                <th class="pb-3">Class of Shares</th>
                                <th class="pb-3">Number of Shares</th>
                                <th class="pb-3">Distinctive Range</th>
                                <th class="pb-3">Capital Subscribed</th>
                                <th class="pb-3">Equity Stake</th>
                                <th class="pb-3 text-right no-print">Certificate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <!-- Founder Stakes First -->
                            <?php foreach ($founders as $f): ?>
                                <tr class="bg-indigo-50/20">
                                    <td class="py-3.5 font-mono text-indigo-700 font-bold">
                                        FOUNDER-ORIG
                                        <div class="text-[9.5px] text-slate-400 font-sans">FOLIO-0001</div>
                                    </td>
                                    <td class="py-3.5">
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($f['founder_name']) ?></div>
                                        <div class="text-[10px] text-indigo-600 font-semibold"><?= htmlspecialchars($f['designation'] ?? 'Co-Founder') ?></div>
                                    </td>
                                    <td class="py-3.5 text-slate-600">Common Equity Shares</td>
                                    <td class="py-3.5 font-mono text-slate-700 font-bold">— Common Core —</td>
                                    <td class="py-3.5 font-mono text-slate-400 text-[11px]">000001 – 010000</td>
                                    <td class="py-3.5 font-mono text-slate-700">Initial Subscription</td>
                                    <td class="py-3.5 font-bold text-indigo-600"><?= number_format((float)($f['equity_percent'] ?? 0), 2) ?>%</td>
                                    <td class="py-3.5 text-right no-print">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">
                                            Founding Stake
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Investor Allotments -->
                            <?php if (empty($investments)): ?>
                                <tr>
                                    <td colspan="8" class="py-6 text-center text-xs text-slate-400">
                                        No external investor share certificates issued yet. When a funding round completes, allottee certificates will appear here.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($investments as $inv): ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5 font-mono text-indigo-600 font-bold">
                                            <?= htmlspecialchars($inv['certificate_number'] ?? 'CERT-PENDING') ?>
                                            <div class="text-[9.5px] text-slate-400 font-sans"><?= htmlspecialchars($inv['folio_number'] ?? 'FOLIO-001') ?></div>
                                        </td>
                                        <td class="py-3.5">
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($inv['investor_name'] ?? 'Investor') ?></div>
                                            <div class="text-[10px] text-slate-400"><?= htmlspecialchars($inv['investor_email'] ?? '') ?></div>
                                        </td>
                                        <td class="py-3.5 text-slate-600">
                                            <span class="text-xs"><?= htmlspecialchars($inv['share_class'] ?? 'CCPS') ?></span>
                                            <div class="text-[10px] text-slate-400"><?= htmlspecialchars($inv['round_name'] ?? '') ?></div>
                                        </td>
                                        <td class="py-3.5 font-mono font-bold text-slate-900">
                                            <?= number_format((int)($inv['number_of_shares'] ?? 0)) ?> Shares
                                            <div class="text-[10px] text-slate-400 font-normal">@ ₹<?= number_format((float)($inv['price_per_share'] ?? 0), 2) ?>/sh</div>
                                        </td>
                                        <td class="py-3.5 font-mono text-slate-600 text-[11px]">
                                            <?= str_pad((string)($inv['distinctive_from'] ?? 1), 6, '0', STR_PAD_LEFT) ?> – <?= str_pad((string)($inv['distinctive_to'] ?? 1), 6, '0', STR_PAD_LEFT) ?>
                                        </td>
                                        <td class="py-3.5 font-black text-emerald-600 font-mono">
                                            <?= format_inr((float)($inv['amount_invested'] ?? 0)) ?>
                                        </td>
                                        <td class="py-3.5 font-bold text-emerald-600">
                                            <?= number_format((float)($inv['equity_allotted_percent'] ?? 0), 3) ?>%
                                        </td>
                                        <td class="py-3.5 text-right no-print">
                                            <a href="<?= url('certificate.php?id=' . $inv['id']) ?>" target="_blank"
                                               class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 text-xs font-semibold transition border border-slate-200">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                <span>View Certificate</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal: Edit Capital Structure -->
            <div id="cap-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 relative">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <i data-lucide="sliders" class="w-4 h-4"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">Configure Capital Structure</h3>
                        </div>
                        <button type="button" onclick="closeCapModal()" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <form method="POST" action="<?= url('founder/cap_table.php') ?>" class="space-y-4 mt-4 text-xs">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="update_capital">

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Authorized Share Capital (₹ INR)</label>
                            <input type="number" step="1000" min="10000" name="authorized_capital" value="<?= htmlspecialchars((string)$authorizedCapital) ?>" required
                                   class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition outline-none font-mono">
                            <p class="text-[10.5px] text-slate-400 mt-1">Maximum capital company is authorized to raise under MoA.</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Face Value Per Share (₹ INR)</label>
                            <input type="number" step="0.01" min="1" name="face_value_per_share" value="<?= htmlspecialchars((string)$faceValue) ?>" required
                                   class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition outline-none font-mono">
                            <p class="text-[10.5px] text-slate-400 mt-1">Nominal statutory value per share (usually ₹10 or ₹100).</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">ESOP Pool Allocation (%)</label>
                            <input type="number" step="0.1" min="0" max="50" name="esop_pool_percent" value="<?= htmlspecialchars((string)$esopPercent) ?>" required
                                   class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition outline-none font-mono">
                            <p class="text-[10.5px] text-slate-400 mt-1">Percentage of total capital reserved for employee option pools.</p>
                        </div>

                        <div class="pt-2 flex items-center justify-end space-x-2">
                            <button type="button" onclick="closeCapModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition shadow-sm">
                                Save Capital Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal: Interactive Dilution Simulator -->
            <div id="sim-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
                <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 relative max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <i data-lucide="calculator" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Future Round Dilution Simulator</h3>
                                <p class="text-[11px] text-slate-400">Interactive "What-If" post-money ownership and dilution modeling</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeSimModal()" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <div class="mt-4 space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">New Capital to Raise (₹)</label>
                                <input type="number" id="sim-raise" value="10000000" step="500000" min="100000" oninput="calculateDilution()"
                                       class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition outline-none font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Pre-Money Valuation (₹)</label>
                                <input type="number" id="sim-val" value="50000000" step="1000000" min="500000" oninput="calculateDilution()"
                                       class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition outline-none font-mono">
                            </div>
                        </div>

                        <!-- Real-time Summary Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-1">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-bold text-slate-400 uppercase">Post-Money Valuation</div>
                                <div class="text-sm font-black text-slate-900 mt-0.5" id="sim-post-val">₹6,00,00,000</div>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-100">
                                <div class="text-[10px] font-bold text-emerald-800 uppercase">New Investor Dilution</div>
                                <div class="text-sm font-black text-emerald-600 mt-0.5" id="sim-new-equity">16.67%</div>
                            </div>
                            <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-100 col-span-2 sm:col-span-1">
                                <div class="text-[10px] font-bold text-indigo-800 uppercase">Founder Stake (Post)</div>
                                <div class="text-sm font-black text-indigo-600 mt-0.5" id="sim-founder-post">--%</div>
                            </div>
                        </div>

                        <!-- Visual Comparison Bar -->
                        <div class="pt-2">
                            <div class="text-[10.5px] font-bold text-slate-500 mb-1.5 flex items-center justify-between">
                                <span>Simulated Post-Round Ownership Bar</span>
                                <span class="text-slate-400 font-normal">Includes Founders, Existing, New & ESOP</span>
                            </div>
                            <div class="w-full h-4 bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                                <div id="sim-bar-founder" class="bg-indigo-600 transition-all duration-300" title="Founders"></div>
                                <div id="sim-bar-existing" class="bg-blue-500 transition-all duration-300" title="Existing Investors"></div>
                                <div id="sim-bar-new" class="bg-emerald-500 transition-all duration-300" title="New Round"></div>
                                <div id="sim-bar-esop" class="bg-amber-400 transition-all duration-300" title="ESOP"></div>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 text-[11px] space-y-1 text-slate-600 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <span>Current Founder Stake:</span>
                                <strong class="text-slate-900"><?= number_format($foundersTotalEquity, 2) ?>%</strong>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Estimated Founder Stake After Dilution:</span>
                                <strong class="text-indigo-600" id="sim-founder-est">--%</strong>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Dilution Effect on Founders:</span>
                                <strong class="text-rose-600 font-mono" id="sim-dilution-drop">--%</strong>
                            </div>
                        </div>

                        <div class="pt-1 flex items-center justify-end">
                            <button type="button" onclick="closeSimModal()" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition shadow-sm">
                                Close Simulator
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#founder-cap-main > *", { duration: 0.4, y: 12, opacity: 0, stagger: 0.06, ease: "power2.out" });

        const baseFoundersEquity = <?= json_encode((float)$foundersTotalEquity) ?>;
        const baseInvestorsEquity = <?= json_encode((float)$investorsTotalEquity) ?>;
        const baseEsopPercent = <?= json_encode((float)$esopPercent) ?>;

        function openCapModal() {
            const modal = document.getElementById('cap-modal');
            if (modal) {
                modal.classList.remove('hidden');
                lucide.createIcons();
            }
        }

        function closeCapModal() {
            const modal = document.getElementById('cap-modal');
            if (modal) modal.classList.add('hidden');
        }

        function openSimModal() {
            const modal = document.getElementById('sim-modal');
            if (modal) {
                modal.classList.remove('hidden');
                calculateDilution();
                lucide.createIcons();
            }
        }

        function closeSimModal() {
            const modal = document.getElementById('sim-modal');
            if (modal) modal.classList.add('hidden');
        }

        function formatInrJs(num) {
            return '₹' + Number(num).toLocaleString('en-IN', { maximumFractionDigits: 0 });
        }

        function calculateDilution() {
            const raise = parseFloat(document.getElementById('sim-raise').value) || 0;
            const preVal = parseFloat(document.getElementById('sim-val').value) || 0;
            const postVal = preVal + raise;

            if (postVal <= 0 || raise <= 0) return;

            const newInvestorPercent = (raise / postVal) * 100;
            const retentionFactor = 1 - (newInvestorPercent / 100);

            const postFounderEquity = baseFoundersEquity * retentionFactor;
            const postExistingInvestors = baseInvestorsEquity * retentionFactor;
            const postEsop = baseEsopPercent * retentionFactor;
            const dilutionDrop = baseFoundersEquity - postFounderEquity;

            document.getElementById('sim-post-val').innerText = formatInrJs(postVal);
            document.getElementById('sim-new-equity').innerText = newInvestorPercent.toFixed(2) + '%';
            document.getElementById('sim-founder-post').innerText = postFounderEquity.toFixed(2) + '%';
            document.getElementById('sim-founder-est').innerText = postFounderEquity.toFixed(2) + '%';
            document.getElementById('sim-dilution-drop').innerText = '-' + dilutionDrop.toFixed(2) + '%';

            document.getElementById('sim-bar-founder').style.width = Math.min(100, Math.max(0, postFounderEquity)) + '%';
            document.getElementById('sim-bar-existing').style.width = Math.min(100, Math.max(0, postExistingInvestors)) + '%';
            document.getElementById('sim-bar-new').style.width = Math.min(100, Math.max(0, newInvestorPercent)) + '%';
            document.getElementById('sim-bar-esop').style.width = Math.min(100, Math.max(0, postEsop)) + '%';
        }
    </script>
</body>
</html>
