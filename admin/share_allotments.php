<?php
/**
 * Admin Module: Master Share Allotments & Cap Table Governance Desk
 * Issue digital share certificates, manage company cap tables, audit demat share allocations
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Share Allotments';

$error = '';
$flash = get_flash();

// Handle new allotment POST action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'create_allotment') {
            $companyId = (int)($_POST['company_id'] ?? 0);
            $roundId = (int)($_POST['funding_round_id'] ?? 0);
            $investorId = (int)($_POST['investor_user_id'] ?? 0);
            $amount = (float)($_POST['amount_invested'] ?? 0);
            $equityPercent = (float)($_POST['equity_allotted_percent'] ?? 0);
            $numShares = (int)($_POST['number_of_shares'] ?? 0);
            $shareClass = trim($_POST['share_class'] ?? 'Series Seed CCPS');

            if ($companyId <= 0 || $roundId <= 0 || $investorId <= 0 || $amount <= 0 || $numShares <= 0) {
                $error = 'Please fill in all mandatory allotment fields with valid values.';
            } else {
                try {
                    // Find max distinctive number
                    $maxDistinctive = (int)$db->query("SELECT COALESCE(MAX(distinctive_to), 10000) FROM investments")->fetchColumn();
                    $distinctiveFrom = $maxDistinctive + 1;
                    $distinctiveTo = $distinctiveFrom + $numShares - 1;
                    $pricePerShare = round($amount / $numShares, 2);

                    // Fetch company code for certificate serial
                    $compStmt = $db->prepare("SELECT name FROM companies WHERE id = ?");
                    $compStmt->execute([$companyId]);
                    $compName = $compStmt->fetchColumn() ?: 'PORT';
                    $compCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $compName), 0, 3));
                    $certNum = 'SHA-2026-' . $compCode . '-' . rand(100, 999);
                    $folio = 'FOLIO-' . str_pad($investorId, 4, '0', STR_PAD_LEFT);
                    $token = bin2hex(random_bytes(16));

                    // First create dummy investment order if needed or link to round
                    $insOrder = $db->prepare("INSERT INTO investment_orders (funding_round_id, investor_user_id, amount, status, terms_accepted) VALUES (?, ?, ?, 'CONFIRMED', 1)");
                    $insOrder->execute([$roundId, $investorId, $amount]);
                    $orderId = $db->lastInsertId();

                    // Insert investment allotment
                    $insInv = $db->prepare("
                        INSERT INTO investments 
                        (order_id, funding_round_id, investor_user_id, company_id, amount_invested, equity_allotted_percent, number_of_shares, price_per_share, distinctive_from, distinctive_to, share_class, folio_number, certificate_number, verification_token, allotment_status, confirmed_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'certificate_issued', NOW())
                    ");
                    $insInv->execute([
                        $orderId, $roundId, $investorId, $companyId, $amount, $equityPercent,
                        $numShares, $pricePerShare, $distinctiveFrom, $distinctiveTo, $shareClass,
                        $folio, $certNum, $token
                    ]);
                    $newInvId = $db->lastInsertId();

                    // Create transaction ref
                    $txRef = 'TX-SHA-' . strtoupper(substr(md5(uniqid()), 0, 8));
                    $insTx = $db->prepare("INSERT INTO transactions (investment_id, order_id, transaction_ref, payment_mode, amount, status) VALUES (?, ?, ?, 'Escrow Wire / Direct Allotment', ?, 'SUCCESS')");
                    $insTx->execute([$newInvId, $orderId, $txRef, $amount]);

                    // Send notification to investor
                    send_notification($investorId, 'Share Certificate Issued', "Official Digital Share Certificate #{$certNum} has been issued and credited to your demat portfolio.", 'success', 'certificate.php?id=' . $newInvId);

                    log_audit($user['id'], 'ISSUE_SHARE_CERTIFICATE', 'investments', $newInvId, "Issued certificate {$certNum} for {$numShares} shares in company #{$companyId}");
                    set_flash('success', "Digital Share Certificate #{$certNum} generated successfully!");
                    header('Location: ' . url('admin/share_allotments.php'));
                    exit;
                } catch (Exception $e) {
                    $error = 'Error issuing allotment: ' . $e->getMessage();
                }
            }
        }
    }
}

// Aggregates & Data loading
$totalSharesAllotted = 0;
$totalCapitalAllotted = 0;
$totalCertificatesCount = 0;
$allotments = [];
$companiesList = [];
$fundingRoundsList = [];
$investorsList = [];
$selectedCompanyId = (int)($_GET['company_filter'] ?? 0);

if ($db) {
    // 1. Overall Metrics
    $agg = $db->query("
        SELECT 
            COALESCE(SUM(number_of_shares), 0) as total_shares,
            COALESCE(SUM(amount_invested), 0) as total_capital,
            COUNT(*) as total_certs
        FROM investments
        WHERE allotment_status = 'certificate_issued'
    ")->fetch();

    $totalSharesAllotted = (int)($agg['total_shares'] ?? 0);
    $totalCapitalAllotted = (float)($agg['total_capital'] ?? 0);
    $totalCertificatesCount = (int)($agg['total_certs'] ?? 0);

    // 2. Fetch allotments with joined data
    $sql = "
        SELECT inv.*, 
               c.name as company_name, c.cin_number,
               u.name as investor_name, u.email as investor_email,
               fr.round_name, fr.target_amount, fr.valuation
        FROM investments inv
        JOIN companies c ON inv.company_id = c.id
        JOIN users u ON inv.investor_user_id = u.id
        JOIN funding_rounds fr ON inv.funding_round_id = fr.id
    ";
    if ($selectedCompanyId > 0) {
        $sql .= " WHERE inv.company_id = {$selectedCompanyId}";
    }
    $sql .= " ORDER BY inv.confirmed_at DESC, inv.id DESC";

    $allotments = $db->query($sql)->fetchAll();

    // 3. Dropdowns for Issue Modal
    $companiesList = $db->query("SELECT id, name, cin_number, industry FROM companies ORDER BY name ASC")->fetchAll();
    $fundingRoundsList = $db->query("SELECT fr.id, fr.company_id, fr.round_name, c.name as company_name FROM funding_rounds fr JOIN companies c ON fr.company_id = c.id ORDER BY fr.id DESC")->fetchAll();
    $investorsList = $db->query("SELECT id, name, email FROM users WHERE role = 'investor' AND status = 'active' ORDER BY name ASC")->fetchAll();

    // 4. Cap Table breakdown for selected company
    $selectedCompany = null;
    $capTableBreakdown = ['founders' => [], 'investors' => []];
    if ($selectedCompanyId > 0) {
        $selectedCompany = $db->query("SELECT * FROM companies WHERE id = {$selectedCompanyId}")->fetch();
        if ($selectedCompany) {
            $fStmt = $db->query("SELECT cf.*, u.name as founder_name FROM company_founders cf JOIN users u ON cf.user_id = u.id WHERE cf.company_id = {$selectedCompanyId}");
            $capTableBreakdown['founders'] = $fStmt->fetchAll();

            $iStmt = $db->query("SELECT inv.*, u.name as investor_name, fr.round_name FROM investments inv JOIN users u ON inv.investor_user_id = u.id JOIN funding_rounds fr ON inv.funding_round_id = fr.id WHERE inv.company_id = {$selectedCompanyId} AND inv.allotment_status = 'certificate_issued'");
            $capTableBreakdown['investors'] = $iStmt->fetchAll();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Share Allotments & Cap Table Registry • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/admin/head.php'; ?>
    <style>
        .stat-card-clean { 
            background: #FFFFFF; 
            border: 1px solid #E2E8F0; 
            border-radius: 1rem; 
            padding: 1.25rem 1.5rem; 
            box-shadow: 0 1px 3px 0 rgba(0,0,0,0.02); 
            transition: all 0.2s ease-in-out;
        }
        .stat-card-clean:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px -4px rgba(0, 0, 0, 0.05);
        }
        .table-card-clean { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 1rem; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.02); overflow: hidden; }
        .filter-bar-clean { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 1rem; padding: 0.875rem 1.25rem; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.02); }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 flex min-h-screen dark:bg-[#0b0f19] dark:text-slate-100 font-sans antialiased">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">

        <!-- Admin Navbar -->
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="allotment-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800' ?> flex items-center space-x-2 shadow-xs">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 flex items-center space-x-2 shadow-xs">
                    <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-violet-50 dark:bg-violet-950/50 border border-violet-200/80 dark:border-violet-800/80 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-xs flex-shrink-0">
                        <i data-lucide="award" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                Share Allotments &amp; Cap Table
                            </h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                                Demat &amp; MCA
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Digital share certificates, distinctive share ranges, and statutory Cap Table governance.
                        </p>
                    </div>
                </div>
                <div>
                    <button onclick="document.getElementById('issueModal').classList.remove('hidden')" 
                            class="admin-btn-primary">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Issue Share Certificate</span>
                    </button>
                </div>
            </div>

            <!-- Metric Cards (Consistent 4 KPIs) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Shares Allotted</span>
                        <div class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-2"><?= number_format($totalSharesAllotted) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Units of equity &amp; CCPS</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Allotted Capital</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2"><?= format_inr($totalCapitalAllotted) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Investor subscriptions</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Certificates Issued</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <i data-lucide="award" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-2"><?= number_format($totalCertificatesCount) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Digitally signed &amp; verified</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Compliance Standard</span>
                        <div class="w-9 h-9 rounded-xl bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-xl font-black text-violet-600 dark:text-violet-400 mt-2">SEBI &amp; MCA 2013</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Sec. 56 Companies Act</div>
                </div>
            </div>


            <!-- Filter & Cap Table Explorer -->
            <div class="card-clean rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <form action="<?= url('admin/share_allotments.php') ?>" method="GET" class="flex flex-wrap items-center gap-3 text-xs w-full md:w-auto">
                    <span class="font-bold text-slate-700">Filter by Startup:</span>
                    <select name="company_filter" onchange="this.form.submit()" 
                            class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 outline-none focus:border-blue-600 focus:bg-white">
                        <option value="0">All Startups (<?= count($allotments) ?> records)</option>

                        <?php foreach ($companiesList as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $selectedCompanyId == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($selectedCompanyId > 0): ?>

                        <a href="<?= url('admin/share_allotments.php') ?>" class="text-blue-600 font-bold hover:underline">

                            Clear Filter
                        </a>
                    <?php endif; ?>
                </form>

                <div class="text-xs text-slate-400">
                    Showing <span class="font-semibold text-slate-700"><?= count($allotments) ?></span> certificates
                </div>
            </div>

            <!-- Master Cap Table Inspector (Shown when a company is filtered) -->
            <?php if ($selectedCompany): 
                $foundersEquity = 0;
                foreach ($capTableBreakdown['founders'] as $f) $foundersEquity += (float)$f['equity_percent'];
                $investorsEquity = 0;
                foreach ($capTableBreakdown['investors'] as $i) $investorsEquity += (float)$i['equity_allotted_percent'];
                $esop = (float)($selectedCompany['esop_pool_percent'] ?: 10.00);
                $unallocated = max(0, 100 - ($foundersEquity + $investorsEquity + $esop));
            ?>

            <div class="card-clean rounded-2xl border-blue-200 dark:border-slate-800 overflow-hidden">
                <button type="button" onclick="toggleCapTable()" class="w-full p-5 md:p-6 flex items-center justify-between text-left hover:bg-slate-50/80 transition">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <i data-lucide="pie-chart" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-blue-600">Company Cap Table Inspector</div>
                            <h2 class="text-sm md:text-base font-black text-slate-900"><?= htmlspecialchars($selectedCompany['name']) ?> — Equity Architecture</h2>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="hidden sm:inline-block px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 font-bold text-xs">
                            Authorized: <?= format_inr($selectedCompany['authorized_capital'] ?: 10000000) ?>
                        </span>
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500">
                            <i id="cap-chevron" data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200 rotate-180"></i>
                        </div>
                    </div>
                </button>

                <div id="cap-table-body" class="px-6 pb-6 pt-2 border-t border-slate-100">
                    <!-- Visual Stacked Bar -->
                    <div class="mb-4 pt-2">
                        <div class="w-full h-4 bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                            <div style="width: <?= min(100, $foundersEquity) ?>%" class="bg-blue-600" title="Founders: <?= $foundersEquity ?>%"></div>
                            <div style="width: <?= min(100, $investorsEquity) ?>%" class="bg-emerald-500" title="Investors: <?= $investorsEquity ?>%"></div>
                            <div style="width: <?= min(100, $esop) ?>%" class="bg-amber-400" title="ESOP: <?= $esop ?>%"></div>
                            <?php if ($unallocated > 0): ?>
                                <div style="width: <?= min(100, $unallocated) ?>%" class="bg-slate-300" title="Unallocated: <?= $unallocated ?>%"></div>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 text-[11px] mt-2 font-medium">
                            <span class="flex items-center space-x-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                <span class="text-slate-600">Founders (<?= number_format($foundersEquity, 2) ?>%)</span>
                            </span>
                            <span class="flex items-center space-x-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span class="text-slate-600">Investors (<?= number_format($investorsEquity, 2) ?>%)</span>
                            </span>
                            <span class="flex items-center space-x-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                <span class="text-slate-600">ESOP Pool (<?= number_format($esop, 2) ?>%)</span>
                            </span>
                            <?php if ($unallocated > 0): ?>
                                <span class="flex items-center space-x-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                                    <span class="text-slate-400">Unallocated Treasury (<?= number_format($unallocated, 2) ?>%)</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Cap Table Breakdown Mini-Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs font-sans">
                            <thead>
                                <tr class="border-b border-slate-200/80 text-[10px] uppercase font-bold text-slate-400">
                                    <th class="pb-2">Stakeholder</th>
                                    <th class="pb-2">Role / Class</th>
                                    <th class="pb-2">Shares / Stake</th>
                                    <th class="pb-2">Ownership %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($capTableBreakdown['founders'] as $f): ?>
                                    <tr>
                                        <td class="py-2 font-bold text-slate-800"><?= htmlspecialchars($f['founder_name']) ?></td>
                                        <td class="py-2 text-slate-500"><?= htmlspecialchars($f['designation']) ?></td>
                                        <td class="py-2 font-mono text-slate-700">Common Stock</td>
                                        <td class="py-2 font-bold text-blue-600"><?= $f['equity_percent'] ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php foreach ($capTableBreakdown['investors'] as $i): ?>
                                    <tr>
                                        <td class="py-2 font-bold text-slate-800"><?= htmlspecialchars($i['investor_name']) ?></td>
                                        <td class="py-2 text-slate-500"><?= htmlspecialchars($i['round_name']) ?> (<?= htmlspecialchars($i['share_class']) ?>)</td>
                                        <td class="py-2 font-mono text-emerald-600"><?= number_format($i['number_of_shares']) ?> Shares</td>
                                        <td class="py-2 font-bold text-emerald-600"><?= $i['equity_allotted_percent'] ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td class="py-2 font-bold text-amber-700">Employee Stock Option Plan</td>
                                    <td class="py-2 text-slate-500">Talent Pool Reserve</td>
                                    <td class="py-2 font-mono text-slate-600">Reserved Options</td>
                                    <td class="py-2 font-bold text-amber-600"><?= number_format($esop, 2) ?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
            <?php endif; ?>

            <!-- Share Allotments Master Table -->
            <div class="admin-table-container">
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Certificate #</th>
                                <th>Startup Company</th>
                                <th>Investor</th>
                                <th>Shares & Class</th>
                                <th>Distinctive Range</th>
                                <th>Capital & Stake</th>
                                <th>Date</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allotments)): ?>
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-slate-400">
                                        No share allotments found. Click "Issue Share Certificate" above to create an allotment.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allotments as $a): ?>

                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3.5 font-mono font-bold text-blue-600">

                                            <?= htmlspecialchars($a['certificate_number']) ?>
                                            <div class="text-[11px] text-slate-400 font-sans mt-0.5"><?= htmlspecialchars($a['folio_number']) ?></div>
                                        </td>
                                        <td>
                                            <div class="font-semibold text-slate-900"><?= htmlspecialchars($a['company_name']) ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($a['cin_number'] ?: 'CIN Verified') ?></div>
                                        </td>
                                        <td>
                                            <div class="font-semibold text-slate-800"><?= htmlspecialchars($a['investor_name']) ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($a['investor_email']) ?></div>
                                        </td>
                                        <td>
                                            <div class="font-bold text-slate-900 font-mono"><?= number_format($a['number_of_shares']) ?> Units</div>
                                            <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-[140px]"><?= htmlspecialchars($a['share_class']) ?></div>
                                        </td>
                                        <td class="font-mono text-slate-600 text-xs">
                                            <?= str_pad($a['distinctive_from'], 6, '0', STR_PAD_LEFT) ?> – <?= str_pad($a['distinctive_to'], 6, '0', STR_PAD_LEFT) ?>
                                        </td>

                                        <td class="py-3.5">
                                            <div class="font-black text-emerald-600 font-mono"><?= format_inr($a['amount_invested']) ?></div>
                                            <div class="text-[10px] text-blue-600 font-bold"><?= $a['equity_allotted_percent'] ?>% equity</div>

                                        </td>
                                        <td class="text-slate-500 text-xs">
                                            <?= date('d M Y', strtotime($a['confirmed_at'])) ?>
                                        </td>
                                        <td class="text-right">
                                            <a href="<?= url('certificate.php?id=' . $a['id']) ?>" target="_blank"

                                               class="inline-flex items-center space-x-1 px-3 py-1.5 bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-slate-700 text-xs font-semibold rounded-lg transition border border-slate-200">

                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                <span>View</span>
                                            </a>
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

    <!-- Issue Share Certificate Modal -->
    <div id="issueModal" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 relative">
            <button onclick="document.getElementById('issueModal').classList.add('hidden')" 
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>


            <div class="flex items-center space-x-2.5 mb-4">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="award" class="w-5 h-5"></i>

                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Issue Digital Share Certificate</h3>
                    <p class="text-[11px] text-slate-400">Allocate formal equity shares and generate certificate.</p>
                </div>
            </div>

            <form method="POST" action="<?= url('admin/share_allotments.php') ?>" class="space-y-3.5 text-xs">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="form_action" value="create_allotment">

                <div>

                    <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Startup Company</label>
                    <select name="company_id" required 
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 outline-none focus:bg-white focus:border-blue-600">

                        <option value="">Select Startup...</option>
                        <?php foreach ($companiesList as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['industry']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>

                    <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Funding Round</label>
                    <select name="funding_round_id" required 
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 outline-none focus:bg-white focus:border-blue-600">

                        <option value="">Select Funding Round...</option>
                        <?php foreach ($fundingRoundsList as $fr): ?>
                            <option value="<?= $fr['id'] ?>"><?= htmlspecialchars($fr['company_name']) ?> — <?= htmlspecialchars($fr['round_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>

                    <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Allottee Investor</label>
                    <select name="investor_user_id" required 
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 outline-none focus:bg-white focus:border-blue-600">

                        <option value="">Select Investor...</option>
                        <?php foreach ($investorsList as $inv): ?>
                            <option value="<?= $inv['id'] ?>"><?= htmlspecialchars($inv['name']) ?> (<?= htmlspecialchars($inv['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>

                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Capital Invested (INR)</label>
                        <input type="number" step="0.01" name="amount_invested" required placeholder="e.g. 500000" 
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 outline-none focus:bg-white focus:border-blue-600">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Equity Allotted (%)</label>
                        <input type="number" step="0.001" name="equity_allotted_percent" required placeholder="e.g. 1.250" 
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 outline-none focus:bg-white focus:border-blue-600">

                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>

                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Number of Shares</label>
                        <input type="number" name="number_of_shares" required placeholder="e.g. 2000" 
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 outline-none focus:bg-white focus:border-blue-600">

                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1 text-xs">Share Class</label>
                        <input type="text" name="share_class" value="Series Seed CCPS" required 

                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 outline-none focus:bg-white focus:border-blue-600">

                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('issueModal').classList.add('hidden')" 
                            class="admin-btn-secondary">
                        Cancel
                    </button>

                    <button type="submit" 
                            class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-sm">
                        Generate & Dispatch Certificate

                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        gsap.from("#allotment-main > *", { duration: 0.4, y: 12, opacity: 0, stagger: 0.06, ease: "power2.out" });

        function toggleCapTable() {
            const body = document.getElementById('cap-table-body');
            const chev = document.getElementById('cap-chevron');
            if (!body || !chev) return;
            const isHidden = body.classList.toggle('hidden');
            if (isHidden) {
                chev.classList.remove('rotate-180');
            } else {
                chev.classList.add('rotate-180');
            }
        }

    </script>
</body>
</html>
