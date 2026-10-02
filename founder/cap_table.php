<?php
/**
 * Founder Module: Master Cap Table & Equity Allotments Studio
 * Complete Ownership Breakdown, Share Certificates, Dilution Simulator, and Shareholder Register
 * Compliant with Indian Companies Act & SEBI frameworks
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
$allFundingRounds = [];
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
        $authorizedCapital = !empty($company['authorized_capital']) ? (float) $company['authorized_capital'] : 10000000.00;
        $faceValue = !empty($company['face_value_per_share']) ? (float) $company['face_value_per_share'] : 10.00;
        $esopPercent = isset($company['esop_pool_percent']) && $company['esop_pool_percent'] !== '' ? (float) $company['esop_pool_percent'] : 10.00;

        // 2. Founders & co-founders
        $fStmt = $db->prepare("
            SELECT cf.*, u.name as founder_name, u.email, u.avatar_url
            FROM company_founders cf
            JOIN users u ON cf.user_id = u.id
            WHERE cf.company_id = ?
            ORDER BY cf.equity_percent DESC
        ");
        $fStmt->execute([$compId]);
        $founders = $fStmt->fetchAll();

        // 3. Funding rounds
        $frAllStmt = $db->prepare("SELECT id, round_name, valuation, status FROM funding_rounds WHERE company_id = ? ORDER BY id DESC");
        $frAllStmt->execute([$compId]);
        $allFundingRounds = $frAllStmt->fetchAll();

        // 4. Investor allotments (LEFT JOINs so every record shows)
        $invStmt = $db->prepare("
            SELECT inv.*,
                   COALESCE(u.name, 'Allotted Investor') as investor_name,
                   COALESCE(u.email, 'investor@portal.com') as investor_email,
                   COALESCE(fr.round_name, 'Direct Capital Allotment') as round_name,
                   fr.valuation as round_valuation
            FROM investments inv
            LEFT JOIN users u ON inv.investor_user_id = u.id
            LEFT JOIN funding_rounds fr ON inv.funding_round_id = fr.id
            WHERE inv.company_id = ?
            ORDER BY inv.confirmed_at DESC
        ");
        $invStmt->execute([$compId]);
        $investments = $invStmt->fetchAll();
    }
}

// Current totals (used for validation and display)
$foundersTotalEquity = 0;
foreach ($founders as $f)
    $foundersTotalEquity += (float) $f['equity_percent'];

$investorsTotalEquity = 0;
$totalCapitalRaised = 0;
$totalInvestorShares = 0;
foreach ($investments as $inv) {
    $investorsTotalEquity += (float) ($inv['equity_allotted_percent'] ?? 0);
    $totalCapitalRaised += (float) ($inv['amount_invested'] ?? 0);
    $totalInvestorShares += (int) ($inv['number_of_shares'] ?? 0);
}

$unallocatedPercent = max(0, round(100 - ($foundersTotalEquity + $investorsTotalEquity + $esopPercent), 2));
$totalAuthorizedShares = $faceValue > 0 ? (int) ($authorizedCapital / $faceValue) : 1000000;
$founderTotalShares = (int) (($foundersTotalEquity / 100) * $totalAuthorizedShares);
$esopTotalShares = (int) (($esopPercent / 100) * $totalAuthorizedShares);
$unallocatedTotalShares = max(0, $totalAuthorizedShares - ($founderTotalShares + $totalInvestorShares + $esopTotalShares));

// Handle POST: Update Capital Structure or Issue New Allotment
$openModal = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Please refresh and try again.';
    } elseif (!$company) {
        $error = 'Please set up your company profile first.';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'update_capital') {
            $newAuth = (float) ($_POST['authorized_capital'] ?? 0);
            $newFv = (float) ($_POST['face_value'] ?? 0);
            $newEsop = (float) ($_POST['esop_percent'] ?? 0);

            if ($newAuth <= 0) {
                $error = 'Authorized share capital must be greater than zero.';
            } elseif ($newFv <= 0) {
                $error = 'Face value per share must be greater than zero.';
            } elseif ($newEsop < 0 || $newEsop > 50) {
                $error = 'ESOP pool allocation must be between 0% and 50%.';
            } else {
                $upd = $db->prepare("UPDATE companies SET authorized_capital = ?, face_value_per_share = ?, esop_pool_percent = ? WHERE id = ?");
                $upd->execute([$newAuth, $newFv, $newEsop, $company['id']]);

                log_audit($user['id'], 'UPDATE_CAPITAL_STRUCTURE', 'companies', $company['id'], "Updated authorized capital: ₹$newAuth, FV: ₹$newFv, ESOP: $newEsop%");
                set_flash('success', 'Capital structure updated successfully.');
                header('Location: ' . url('founder/cap_table.php'));
                exit;
            }
            $openModal = 'update-capital-modal';

        } elseif ($action === 'issue_allotment') {
            $investorName = trim($_POST['investor_name'] ?? '');
            $investorEmail = trim($_POST['investor_email'] ?? '');
            $shareClass = trim($_POST['share_class'] ?? 'Series Seed Compulsorily Convertible Preference Shares (CCPS)');
            $amount = (float) ($_POST['amount_invested'] ?? 0);
            $numShares = (int) ($_POST['number_of_shares'] ?? 0);
            $equityPct = (float) ($_POST['equity_percent'] ?? 0);
            $roundId = (int) ($_POST['funding_round_id'] ?? 0);

            if ($investorName === '' || $investorEmail === '' || $amount <= 0 || $numShares <= 0 || $equityPct <= 0) {
                $error = 'Please fill all required allotment fields with valid values.';
            } elseif ($equityPct > $unallocatedPercent) {
                $error = 'Equity stake exceeds the unallocated treasury (' . number_format($unallocatedPercent, 3) . '% available).';
            } elseif ($numShares > $unallocatedTotalShares) {
                $error = 'Shares issued exceed the unissued authorized shares (' . number_format($unallocatedTotalShares) . ' available).';
            } else {
                $pricePerShare = round($amount / $numShares, 4);

                // Find or link user
                $invUserStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $invUserStmt->execute([$investorEmail]);
                $existingInv = $invUserStmt->fetch();

                if ($existingInv) {
                    $targetUserId = $existingInv['id'];
                } else {
                    $anyInv = $db->query("SELECT id FROM users WHERE role = 'investor' LIMIT 1")->fetch();
                    $targetUserId = $anyInv ? $anyInv['id'] : $user['id'];
                }

                // If no round provided, use latest or create default round
                if (!$roundId) {
                    $rStmt = $db->prepare("SELECT id FROM funding_rounds WHERE company_id = ? ORDER BY id DESC LIMIT 1");
                    $rStmt->execute([$company['id']]);
                    $roundId = (int) $rStmt->fetchColumn();
                    if (!$roundId) {
                        $insR = $db->prepare("INSERT INTO funding_rounds (company_id, round_name, target_amount, amount_raised, valuation, status, created_at) VALUES (?, 'Angel Allotment', ?, ?, ?, 'CLOSED', NOW())");
                        $insR->execute([$company['id'], $amount, $amount, ($amount * 10)]);
                        $roundId = $db->lastInsertId();
                    }
                }

                // Reference order
                $orderId = rand(1000, 9999);
                try {
                    $insOrd = $db->prepare("INSERT INTO investment_orders (funding_round_id, investor_user_id, amount, status, terms_accepted, created_at) VALUES (?, ?, ?, 'CONFIRMED', 1, NOW())");
                    $insOrd->execute([$roundId, $targetUserId, $amount]);
                    $orderId = $db->lastInsertId();
                } catch (Exception $e) {
                }

                $certNum = 'CERT-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $company['name']), 0, 3)) . '-' . date('Y') . '-' . rand(100, 999);
                $folioNum = 'FOLIO-' . rand(100, 999);

                // Continue distinctive numbers after the highest issued number
                $maxTo = 0;
                foreach ($investments as $iv)
                    $maxTo = max($maxTo, (int) ($iv['distinctive_to'] ?? 0));
                $distFrom = max($maxTo, $founderTotalShares) + 1;
                $distTo = $distFrom + $numShares - 1;

                $insInv = $db->prepare("
                    INSERT INTO investments (order_id, funding_round_id, investor_user_id, company_id, amount_invested, equity_allotted_percent, number_of_shares, price_per_share, distinctive_from, distinctive_to, share_class, folio_number, certificate_number, allotment_status, confirmed_at, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'certificate_issued', NOW(), NOW())
                ");
                $insInv->execute([$orderId, $roundId, $targetUserId, $company['id'], $amount, $equityPct, $numShares, $pricePerShare, $distFrom, $distTo, $shareClass, $folioNum, $certNum]);

                log_audit($user['id'], 'ISSUE_SHARE_ALLOTMENT', 'investments', $db->lastInsertId(), "Issued $numShares shares of $shareClass to $investorName");
                set_flash('success', "Share certificate {$certNum} issued successfully to {$investorName}!");
                header('Location: ' . url('founder/cap_table.php'));
                exit;
            }
            $openModal = 'issue-allotment-modal';
        }
    }
}
$old = ($openModal === 'issue-allotment-modal') ? $_POST : [];
$oldVal = fn($k, $d) => htmlspecialchars((string) ($old[$k] ?? $d));

$flashStyles = [
    'success' => ['bg-emerald-50 text-emerald-800 border-emerald-200', 'check-circle-2', 'text-emerald-600'],
    'info' => ['bg-blue-50 text-blue-800 border-blue-200', 'info', 'text-blue-600'],
    'error' => ['bg-rose-50 text-rose-800 border-rose-200', 'alert-circle', 'text-rose-600'],
];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
    <style>
        html:not(.dark) .hero-cap-banner {
            background: radial-gradient(130% 100% at 0% 0%, #EEF2FF 0%, #F8FAFC 50%, #FAF5FF 100%);
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        }

        .hero-cap-banner {
            border-radius: 1.5rem;
            position: relative;
        }

        html.dark .hero-cap-banner {
            background: radial-gradient(130% 100% at 0% 0%, #17213A 0%, #0F172A 55%, #111827 100%) !important;
            border: 1px solid #1E293B !important;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
        }

        /* All cards solid white (light + dark mode) */
        .section-card {
            background-color: #FFFFFF !important;
            color: #0F172A;
            border: 1px solid #E2E8F0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }

        .form-input-clean {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 0.75rem;
            color: #0F172A;
            font-size: 1rem;
            line-height: 1.5rem;
            transition: all 0.15s ease;
            outline: none;
        }

        .form-input-clean:hover {
            background-color: #F1F5F9;
            border-color: #CBD5E1;
        }

        .form-input-clean:focus {
            background-color: #FFFFFF;
            border-color: #4F46E5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        @media print {

            aside,
            nav,
            button,
            #sh-search {
                display: none !important;
            }
        }
    </style>
</head>

<body
    class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">

    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 md:px-8 py-6 space-y-6" id="founder-cap-main">

            <!-- Flash Feedback -->
            <?php if ($flash):
                $fs = $flashStyles[$flash['type']] ?? $flashStyles['info']; ?>
                <div
                    class="p-4 rounded-2xl text-sm font-semibold border <?= $fs[0] ?> flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="<?= $fs[1] ?>" class="w-5 h-5 flex-shrink-0 <?= $fs[2] ?>"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <span class="text-xs font-bold uppercase opacity-75">Notice</span>
                </div>
            <?php endif; ?>

            <?php if ($error && !$openModal): ?>
                <div
                    class="p-4 rounded-2xl text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-3 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 text-rose-600"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!$company): ?>
                <div class="section-card p-12 text-center">
                    <i data-lucide="building" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
                    <h2 class="text-lg font-bold text-slate-900">No Company Profile Linked</h2>
                    <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Please create and register your startup company
                        profile before accessing the equity Cap Table.</p>
                    <a href="<?= url('founder/company.php') ?>"
                        class="inline-flex items-center space-x-1.5 mt-4 px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold shadow-sm">
                        <span>Setup Company Profile</span>
                    </a>
                </div>
            <?php else: ?>

                <!-- Hero Banner -->
                <div class="hero-cap-banner p-6 sm:p-8 relative overflow-hidden">
                    <div
                        class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none">
                    </div>
                    <div
                        class="absolute right-32 -bottom-16 w-56 h-56 bg-purple-500/10 rounded-full blur-3xl pointer-events-none">
                    </div>

                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-600 text-white shadow-sm">
                                    <i data-lucide="award" class="w-3.5 h-3.5"></i>
                                    <span>Statutory MCA Equity Register</span>
                                </span>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-700">
                                    <i data-lucide="building" class="w-3.5 h-3.5 text-indigo-600"></i>
                                    <span><?= htmlspecialchars($company['name']) ?></span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-xs font-mono text-slate-500">CIN:
                                        <?= htmlspecialchars(($company['cin_number'] ?? '') ?: 'Verified') ?></span>
                                </span>
                            </div>
                            <h1
                                class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                Master Cap Table & Share Allotments
                            </h1>
                            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 max-w-2xl leading-relaxed">
                                Live statutory ownership register, electronic share certificate custody, ESOP option pool
                                reserves, and interactive dilution modeling under the Indian Companies Act & SEBI
                                frameworks.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <button type="button" onclick="openModal('issue-allotment-modal')"
                                class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-md transition flex items-center space-x-1.5">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                <span>Record Allotment / Issue Shares</span>
                            </button>
                            <button type="button" onclick="openModal('update-capital-modal')"
                                class="px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-sm font-bold shadow-sm transition flex items-center space-x-1.5">
                                <i data-lucide="settings-2" class="w-4 h-4 text-indigo-600"></i>
                                <span>Configure Capital</span>
                            </button>
                            <button type="button" onclick="exportCapTableCSV()"
                                class="px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-sm font-bold shadow-sm transition flex items-center space-x-1.5">
                                <i data-lucide="download" class="w-4 h-4 text-emerald-600"></i>
                                <span>Export CSV</span>
                            </button>
                            <button type="button" onclick="window.print()"
                                class="px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-sm font-bold shadow-sm transition flex items-center space-x-1.5">
                                <i data-lucide="printer" class="w-4 h-4 text-slate-600"></i>
                                <span>Print</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Capital Structure Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="section-card p-5">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Authorized Capital</div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900"><?= format_inr($authorizedCapital) ?>
                        </div>
                        <div class="text-xs text-slate-500 mt-1 font-mono"><?= number_format($totalAuthorizedShares) ?> Max
                            Shares @ ₹<?= number_format($faceValue, 2) ?> FV</div>
                    </div>
                    <div class="section-card p-5">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Subscribed Capital
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-emerald-600"><?= format_inr($totalCapitalRaised) ?>
                        </div>
                        <div class="text-xs text-slate-500 mt-1">Raised across <?= count($investments) ?>
                            allotment<?= count($investments) !== 1 ? 's' : '' ?></div>
                    </div>
                    <div class="section-card p-5">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Founder Equity Holdings
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-indigo-600">
                            <?= number_format($foundersTotalEquity, 2) ?>%</div>
                        <div class="text-xs text-slate-500 mt-1">Common voting stock
                            (<?= number_format($founderTotalShares) ?> sh)</div>
                    </div>
                    <div class="section-card p-5">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">ESOP Pool Allocated
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-amber-600"><?= number_format($esopPercent, 2) ?>%
                        </div>
                        <div class="text-xs text-slate-500 mt-1">Talent incentive reserve
                            (<?= number_format($esopTotalShares) ?> sh)</div>
                    </div>
                </div>

                <!-- Equity Distribution Bar -->
                <div class="section-card p-6 sm:p-7 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                            <i data-lucide="pie-chart" class="w-4 h-4 text-indigo-600"></i>
                            <span>Equity Stake & Dilution Distribution</span>
                        </h2>
                        <span class="text-sm font-extrabold text-slate-500 font-mono">Total Capitalized: 100.00%</span>
                    </div>

                    <div
                        class="w-full h-5 bg-slate-100 rounded-full overflow-hidden flex shadow-inner p-0.5 border border-slate-200">
                        <div style="width: <?= min(100, $foundersTotalEquity) ?>%"
                            class="bg-indigo-600 rounded-l-full transition-all duration-700"
                            title="Founders: <?= $foundersTotalEquity ?>%"></div>
                        <div style="width: <?= min(100, $investorsTotalEquity) ?>%"
                            class="bg-emerald-500 transition-all duration-700"
                            title="Investors: <?= $investorsTotalEquity ?>%"></div>
                        <div style="width: <?= min(100, $esopPercent) ?>%" class="bg-amber-400 transition-all duration-700"
                            title="ESOP: <?= $esopPercent ?>%"></div>
                        <?php if ($unallocatedPercent > 0): ?>
                            <div style="width: <?= min(100, $unallocatedPercent) ?>%"
                                class="bg-slate-300 rounded-r-full transition-all duration-700"
                                title="Treasury: <?= $unallocatedPercent ?>%"></div>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                        <div class="p-3.5 rounded-xl bg-indigo-50 border border-indigo-100 space-y-1">
                            <div class="flex items-center space-x-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                                <span class="text-sm font-bold text-indigo-950">Founders</span>
                            </div>
                            <div class="text-xl font-black text-indigo-600"><?= number_format($foundersTotalEquity, 2) ?>%
                            </div>
                            <div class="text-xs text-slate-500"><?= count($founders) ?> Founder Co-owners</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-100 space-y-1">
                            <div class="flex items-center space-x-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span class="text-sm font-bold text-emerald-950">Investors</span>
                            </div>
                            <div class="text-xl font-black text-emerald-600"><?= number_format($investorsTotalEquity, 2) ?>%
                            </div>
                            <div class="text-xs text-slate-500"><?= count($investments) ?> Allottee Investors</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-100 space-y-1">
                            <div class="flex items-center space-x-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                <span class="text-sm font-bold text-amber-950">ESOP Pool</span>
                            </div>
                            <div class="text-xl font-black text-amber-600"><?= number_format($esopPercent, 2) ?>%</div>
                            <div class="text-xs text-slate-500">Employee Options Reserve</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                            <div class="flex items-center space-x-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                                <span class="text-sm font-bold text-slate-700">Treasury Unallocated</span>
                            </div>
                            <div class="text-xl font-black text-slate-700"><?= number_format($unallocatedPercent, 2) ?>%
                            </div>
                            <div class="text-xs text-slate-500">Future Funding Buffer</div>
                        </div>
                    </div>
                </div>

                <!-- Dilution Simulator -->
                <div class="section-card p-6 sm:p-7 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <i data-lucide="calculator" class="w-4 h-4 text-indigo-600"></i>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Interactive Dilution
                                Modeling Simulator</h3>
                        </div>
                        <span
                            class="text-xs font-mono text-indigo-600 font-bold bg-indigo-50 border border-indigo-100 px-2.5 py-0.5 rounded-full">Live
                            What-If Sandbox</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">New Round
                                Target (₹)</label>
                            <input type="number" id="sim-target" value="10000000" step="500000"
                                oninput="runDilutionSimulator()" class="form-input-clean font-semibold">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">Pre-Money
                                Valuation (₹)</label>
                            <input type="number" id="sim-pre" value="90000000" step="1000000"
                                oninput="runDilutionSimulator()" class="form-input-clean font-semibold">
                        </div>
                        <div class="p-3.5 bg-indigo-50 border border-indigo-100 rounded-xl space-y-1">
                            <div class="text-xs font-bold text-indigo-900 uppercase tracking-wider">Projected Post-Money
                                Valuation</div>
                            <div id="sim-post" class="text-base font-extrabold text-indigo-700 font-mono">₹10.00 Cr</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="text-xs font-bold text-slate-500 uppercase">New Investor Dilution</div>
                            <div id="sim-inv-pct" class="text-base font-extrabold text-emerald-600 font-mono mt-0.5">10.00%
                            </div>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="text-xs font-bold text-slate-500 uppercase">Founder Stake Diluted</div>
                            <div id="sim-founder-pct" class="text-base font-extrabold text-indigo-600 font-mono mt-0.5">
                                <?= number_format($foundersTotalEquity * 0.9, 2) ?>%</div>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="text-xs font-bold text-slate-500 uppercase">ESOP Pool Diluted</div>
                            <div id="sim-esop-pct" class="text-base font-extrabold text-amber-600 font-mono mt-0.5">
                                <?= number_format($esopPercent * 0.9, 2) ?>%</div>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="text-xs font-bold text-slate-500 uppercase">Existing Angel Dilution</div>
                            <div id="sim-existing-inv-pct" class="text-base font-extrabold text-slate-700 font-mono mt-0.5">
                                <?= number_format($investorsTotalEquity * 0.9, 2) ?>%</div>
                        </div>
                    </div>
                </div>

                <!-- Shareholder Register -->
                <div class="section-card p-6 sm:p-7 space-y-4">
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div>
                            <h2
                                class="text-base font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                                <i data-lucide="file-badge-2" class="w-4 h-4 text-indigo-600"></i>
                                <span>Statutory Shareholder Register & Issued Share Certificates</span>
                            </h2>
                            <p class="text-sm text-slate-500 mt-0.5">Formal electronic certificates issued under the SEBI &
                                Indian Companies Act electronic custody.</p>
                        </div>
                        <input type="text" id="sh-search" placeholder="Search shareholder or cert..."
                            oninput="filterShareholders()"
                            class="px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 outline-none focus:bg-white focus:border-indigo-600 transition">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm" id="sh-table">
                            <thead>
                                <tr
                                    class="border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    <th class="pb-3 pr-3">Certificate & Folio</th>
                                    <th class="pb-3 pr-3">Shareholder Name</th>
                                    <th class="pb-3 pr-3">Class of Shares</th>
                                    <th class="pb-3 pr-3">Number of Shares</th>
                                    <th class="pb-3 pr-3">Distinctive Range</th>
                                    <th class="pb-3 pr-3">Capital Subscribed</th>
                                    <th class="pb-3 pr-3">Equity Stake</th>
                                    <th class="pb-3 text-right">Certificate Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <!-- 1. Founder Stakes -->
                                <?php $fIdx = 0;
                                foreach ($founders as $f):
                                    $fIdx++;
                                    $fShares = (int) (($f['equity_percent'] / 100) * $totalAuthorizedShares);
                                    ?>
                                    <tr class="bg-indigo-50 sh-row">
                                        <td class="py-3.5 pr-3 font-mono text-indigo-700 font-bold">
                                            CERT-FND-<?= strtoupper(substr($company['name'], 0, 3)) ?>-<?= str_pad($fIdx, 3, '0', STR_PAD_LEFT) ?>
                                            <div class="text-xs text-slate-500 font-sans">
                                                FOLIO-<?= str_pad($fIdx, 4, '0', STR_PAD_LEFT) ?></div>
                                        </td>
                                        <td class="py-3.5 pr-3">
                                            <div class="font-bold text-slate-900 text-base">
                                                <?= htmlspecialchars($f['founder_name']) ?></div>
                                            <div class="text-sm text-indigo-600 font-semibold">
                                                <?= htmlspecialchars($f['designation'] ?? '') ?></div>
                                        </td>
                                        <td class="py-3.5 pr-3 text-slate-600 font-medium">Class A Common Equity (Voting)</td>
                                        <td class="py-3.5 pr-3 font-mono text-slate-700 font-bold">
                                            <?= number_format($fShares) ?> Shares</td>
                                        <td class="py-3.5 pr-3 font-mono text-slate-500 text-sm">000001 –
                                            <?= str_pad($fShares, 6, '0', STR_PAD_LEFT) ?></td>
                                        <td class="py-3.5 pr-3 font-mono text-slate-700">
                                            ₹<?= number_format($fShares * $faceValue, 2) ?></td>
                                        <td class="py-3.5 pr-3 font-extrabold text-indigo-600 text-base">
                                            <?= htmlspecialchars($f['equity_percent']) ?>%</td>
                                        <td class="py-3.5 text-right">
                                            <span
                                                class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">Founding
                                                Common Core</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <!-- 2. Investor Allotments -->
                                <?php foreach ($investments as $inv): ?>
                                    <tr class="hover:bg-slate-50 transition sh-row">
                                        <td class="py-3.5 pr-3 font-mono text-indigo-600 font-bold">
                                            <?= htmlspecialchars($inv['certificate_number'] ?? '—') ?>
                                            <div class="text-xs text-slate-500 font-sans">
                                                <?= htmlspecialchars($inv['folio_number'] ?? '') ?></div>
                                        </td>
                                        <td class="py-3.5 pr-3">
                                            <div class="font-bold text-slate-900 text-base">
                                                <?= htmlspecialchars($inv['investor_name']) ?></div>
                                            <div class="text-sm text-slate-500"><?= htmlspecialchars($inv['investor_email']) ?>
                                            </div>
                                        </td>
                                        <td class="py-3.5 pr-3 text-slate-600 font-medium">
                                            <span><?= htmlspecialchars($inv['share_class'] ?? '') ?></span>
                                            <div class="text-xs text-slate-500"><?= htmlspecialchars($inv['round_name']) ?>
                                            </div>
                                        </td>
                                        <td class="py-3.5 pr-3 font-mono font-bold text-slate-900">
                                            <?= number_format((int) ($inv['number_of_shares'] ?? 0)) ?> Shares
                                            <div class="text-xs text-slate-500 font-normal">@
                                                ₹<?= number_format((float) ($inv['price_per_share'] ?? 0), 2) ?>/sh</div>
                                        </td>
                                        <td class="py-3.5 pr-3 font-mono text-slate-600 text-sm">
                                            <?= str_pad((string) ($inv['distinctive_from'] ?? 0), 6, '0', STR_PAD_LEFT) ?> –
                                            <?= str_pad((string) ($inv['distinctive_to'] ?? 0), 6, '0', STR_PAD_LEFT) ?>
                                        </td>
                                        <td class="py-3.5 pr-3 font-black text-emerald-600 font-mono text-base">
                                            <?= format_inr($inv['amount_invested']) ?></td>
                                        <td class="py-3.5 pr-3 font-extrabold text-emerald-600 text-base">
                                            <?= htmlspecialchars($inv['equity_allotted_percent']) ?>%</td>
                                        <td class="py-3.5 text-right">
                                            <a href="<?= url('certificate.php?id=' . (int) $inv['id']) ?>" target="_blank"
                                                rel="noopener"
                                                class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-sm font-bold transition border border-slate-200 shadow-sm">
                                                <i data-lucide="eye" class="w-4 h-4 text-indigo-600"></i>
                                                <span>View Certificate</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <!-- 3. ESOP -->
                                <tr class="bg-amber-50 sh-row">
                                    <td class="py-3.5 pr-3 font-mono text-amber-700 font-bold">
                                        ESOP-POOL-RES
                                        <div class="text-xs text-slate-500 font-sans">FOLIO-ESOP</div>
                                    </td>
                                    <td class="py-3.5 pr-3">
                                        <div class="font-bold text-slate-900 text-base">Employee Stock Option Plan (ESOP)
                                            Trust</div>
                                        <div class="text-sm text-amber-700 font-semibold">Key Talent Incentive Reserve</div>
                                    </td>
                                    <td class="py-3.5 pr-3 text-slate-600 font-medium">Employee Stock Option Pool</td>
                                    <td class="py-3.5 pr-3 font-mono text-slate-700 font-bold">
                                        <?= number_format($esopTotalShares) ?> Shares</td>
                                    <td class="py-3.5 pr-3 font-mono text-slate-500 text-sm">Option Pool Allocation</td>
                                    <td class="py-3.5 pr-3 font-mono text-slate-700">
                                        ₹<?= number_format($esopTotalShares * $faceValue, 2) ?> FV</td>
                                    <td class="py-3.5 pr-3 font-extrabold text-amber-600 text-base">
                                        <?= number_format($esopPercent, 2) ?>%</td>
                                    <td class="py-3.5 text-right">
                                        <span
                                            class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">Reserved
                                            Pool</span>
                                    </td>
                                </tr>

                                <!-- 4. Treasury -->
                                <?php if ($unallocatedPercent > 0): ?>
                                    <tr class="bg-slate-50 sh-row">
                                        <td class="py-3.5 pr-3 font-mono text-slate-500 font-bold">
                                            TREASURY-UNALLOT
                                            <div class="text-xs text-slate-500 font-sans">FOLIO-UNALLOT</div>
                                        </td>
                                        <td class="py-3.5 pr-3">
                                            <div class="font-bold text-slate-700 text-base">Unissued Treasury Stock</div>
                                            <div class="text-sm text-slate-500">Available For Future Capital Rounds</div>
                                        </td>
                                        <td class="py-3.5 pr-3 text-slate-500 font-medium">Unallocated Authorized Shares</td>
                                        <td class="py-3.5 pr-3 font-mono text-slate-600 font-bold">
                                            <?= number_format($unallocatedTotalShares) ?> Shares</td>
                                        <td class="py-3.5 pr-3 font-mono text-slate-500 text-sm">Unissued Range</td>
                                        <td class="py-3.5 pr-3 font-mono text-slate-500">
                                            ₹<?= number_format($unallocatedTotalShares * $faceValue, 2) ?> FV</td>
                                        <td class="py-3.5 pr-3 font-bold text-slate-600 text-base">
                                            <?= number_format($unallocatedPercent, 2) ?>%</td>
                                        <td class="py-3.5 text-right">
                                            <span
                                                class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-700">Available
                                                Buffer</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal 1: Configure Capital Structure -->
                <div id="update-capital-modal"
                    class="<?= $openModal === 'update-capital-modal' ? '' : 'hidden' ?> fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
                    <div
                        class="bg-white border border-slate-200 shadow-2xl max-w-lg w-full rounded-2xl p-6 relative space-y-4 my-auto max-h-[92vh] overflow-y-auto">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base uppercase tracking-wider">Configure Capital
                                    Structure</h3>
                                <p class="text-sm text-slate-500 mt-0.5">Update authorized capital, face value per share,
                                    and ESOP pool reserve.</p>
                            </div>
                            <button type="button" onclick="closeModal('update-capital-modal')"
                                class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <?php if ($openModal === 'update-capital-modal' && $error): ?>
                            <div class="p-3 rounded-xl text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                                <?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form action="<?= url('founder/cap_table.php') ?>" method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="update_capital">

                            <div>
                                <label
                                    class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">Authorized
                                    Share Capital (₹)</label>
                                <input type="number" name="authorized_capital" required
                                    value="<?= htmlspecialchars((string) $authorizedCapital) ?>" step="100000" min="1"
                                    class="form-input-clean font-semibold">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">Face
                                        Value Per Share (₹)</label>
                                    <input type="number" name="face_value" required
                                        value="<?= htmlspecialchars((string) $faceValue) ?>" step="0.01" min="0.01"
                                        class="form-input-clean font-semibold">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">ESOP
                                        Pool (%)</label>
                                    <input type="number" name="esop_percent" required
                                        value="<?= htmlspecialchars((string) $esopPercent) ?>" step="0.5" min="0" max="50"
                                        class="form-input-clean font-semibold">
                                </div>
                            </div>

                            <div class="pt-2 flex justify-end space-x-2">
                                <button type="button" onclick="closeModal('update-capital-modal')"
                                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-semibold">Cancel</button>
                                <button type="submit"
                                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm shadow-md transition">Save
                                    Capital Settings</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal 2: Record Allotment -->
                <div id="issue-allotment-modal"
                    class="<?= $openModal === 'issue-allotment-modal' ? '' : 'hidden' ?> fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
                    <div
                        class="bg-white border border-slate-200 shadow-2xl max-w-lg w-full rounded-2xl p-6 relative space-y-4 my-auto max-h-[92vh] overflow-y-auto">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base uppercase tracking-wider">Record Share
                                    Allotment</h3>
                                <p class="text-sm text-slate-500 mt-0.5">Issue formal electronic share certificate to an
                                    angel investor or shareholder.</p>
                            </div>
                            <button type="button" onclick="closeModal('issue-allotment-modal')"
                                class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <?php if ($openModal === 'issue-allotment-modal' && $error): ?>
                            <div class="p-3 rounded-xl text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                                <?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form action="<?= url('founder/cap_table.php') ?>" method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="issue_allotment">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">Allottee
                                        / Investor Name *</label>
                                    <input type="text" name="investor_name" required
                                        value="<?= $oldVal('investor_name', '') ?>"
                                        placeholder="e.g. Vikramaditya Singhania" class="form-input-clean font-semibold">
                                </div>
                                <div>
                                    <label
                                        class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">Investor
                                        Email *</label>
                                    <input type="email" name="investor_email" required
                                        value="<?= $oldVal('investor_email', '') ?>" placeholder="investor@domain.com"
                                        class="form-input-clean font-semibold">
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">Share
                                    Class Classification</label>
                                <?php $selClass = $old['share_class'] ?? ''; ?>
                                <select name="share_class" class="form-input-clean font-semibold cursor-pointer">
                                    <?php foreach ([
                                        'Series Seed Compulsorily Convertible Preference Shares (CCPS)' => 'Series Seed CCPS (Preference)',
                                        'Class A Common Equity Shares' => 'Class A Common Equity Shares',
                                        'Advisory & Talent Equity Allotment' => 'Advisory & Talent Equity Allotment',
                                        'Pre-Series A Convertible Preference Shares' => 'Pre-Series A Convertible Shares',
                                    ] as $val => $label): ?>
                                        <option value="<?= htmlspecialchars($val) ?>" <?= $selClass === $val ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if (!empty($allFundingRounds)): ?>
                                <div>
                                    <label class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">Funding
                                        Round</label>
                                    <select name="funding_round_id" class="form-input-clean font-semibold cursor-pointer">
                                        <?php foreach ($allFundingRounds as $fr): ?>
                                            <option value="<?= (int) $fr['id'] ?>" <?= ((int) ($old['funding_round_id'] ?? 0) === (int) $fr['id']) ? 'selected' : '' ?>><?= htmlspecialchars($fr['round_name']) ?>
                                                (<?= htmlspecialchars($fr['status']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label
                                        class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">Capital
                                        (₹)</label>
                                    <input type="number" name="amount_invested" required
                                        value="<?= $oldVal('amount_invested', '2500000') ?>" step="50000" min="1"
                                        class="form-input-clean font-semibold">
                                </div>
                                <div>
                                    <label
                                        class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">Shares
                                        Issued</label>
                                    <input type="number" name="number_of_shares" required
                                        value="<?= $oldVal('number_of_shares', '1000') ?>" step="1" min="1"
                                        class="form-input-clean font-semibold">
                                </div>
                                <div>
                                    <label
                                        class="block font-bold text-slate-700 text-sm mb-1 uppercase tracking-wider">Equity
                                        (%)</label>
                                    <input type="number" name="equity_percent" required
                                        value="<?= $oldVal('equity_percent', '3.125') ?>" step="0.001" min="0.001" max="100"
                                        class="form-input-clean font-semibold">
                                </div>
                            </div>
                            <p class="text-sm text-slate-500">Price per share is calculated automatically (capital ÷ shares
                                issued).</p>

                            <div class="pt-2 flex justify-end space-x-2">
                                <button type="button" onclick="closeModal('issue-allotment-modal')"
                                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-semibold">Cancel</button>
                                <button type="submit"
                                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm shadow-md transition">Issue
                                    Share Certificate</button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php endif; ?>

        </main>
    </div>

    <script>
        function refreshIcons() {
            if (window.lucide && typeof lucide.createIcons === 'function') lucide.createIcons();
        }
        refreshIcons();

        function openModal(id) { const m = document.getElementById(id); if (m) m.classList.remove('hidden'); }
        function closeModal(id) { const m = document.getElementById(id); if (m) m.classList.add('hidden'); }
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { closeModal('issue-allotment-modal'); closeModal('update-capital-modal'); }
        });

        // Dilution Modeling Sandbox
        function runDilutionSimulator() {
            const targetEl = document.getElementById('sim-target');
            if (!targetEl) return;
            const target = parseFloat(targetEl.value) || 0;
            const pre = parseFloat(document.getElementById('sim-pre').value) || 0;
            const post = pre + target;
            const cr = (post / 10000000).toFixed(2);
            document.getElementById('sim-post').innerText = `₹${cr} Cr (₹${post.toLocaleString('en-IN')})`;

            const newInvPct = post > 0 ? (target / post) * 100 : 0;
            const retentionFactor = (100 - newInvPct) / 100;

            const baseFounder = <?= json_encode($foundersTotalEquity) ?>;
            const baseEsop = <?= json_encode($esopPercent) ?>;
            const baseInv = <?= json_encode($investorsTotalEquity) ?>;

            document.getElementById('sim-inv-pct').innerText = newInvPct.toFixed(2) + '%';
            document.getElementById('sim-founder-pct').innerText = (baseFounder * retentionFactor).toFixed(2) + '%';
            document.getElementById('sim-esop-pct').innerText = (baseEsop * retentionFactor).toFixed(2) + '%';
            document.getElementById('sim-existing-inv-pct').innerText = (baseInv * retentionFactor).toFixed(2) + '%';
        }
        runDilutionSimulator();

        // Filter table rows
        function filterShareholders() {
            const q = document.getElementById('sh-search').value.toLowerCase().trim();
            document.querySelectorAll('.sh-row').forEach(r => {
                r.style.display = (!q || r.innerText.toLowerCase().includes(q)) ? '' : 'none';
            });
        }

        // Export Cap Table as CSV (visible rows, without the action column)
        function exportCapTableCSV() {
            const companyName = <?= json_encode($company['name'] ?? 'Startup') ?>;
            const rows = document.querySelectorAll('#sh-table tr');
            const csv = [];
            rows.forEach(r => {
                if (r.style.display === 'none') return;
                const row = [];
                const cols = r.querySelectorAll('th, td');
                cols.forEach((c, idx) => {
                    if (idx < cols.length - 1) {
                        const val = c.innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/"/g, '""').trim();
                        row.push('"' + val + '"');
                    }
                });
                if (row.length > 0) csv.push(row.join(','));
            });

            const blob = new Blob(['\ufeff' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `Cap_Table_${companyName.replace(/\s+/g, '_')}_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>

</html>