<?php
/**
 * Admin Module: Master Share Allotments & Cap Table Governance Desk
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Share Allotments';

$error = '';
$flash = get_flash();

/* ---------- POST: create allotment ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } elseif (($_POST['form_action'] ?? '') === 'create_allotment') {
        $companyId = (int) ($_POST['company_id'] ?? 0);
        $roundId = (int) ($_POST['funding_round_id'] ?? 0);
        $investorId = (int) ($_POST['investor_user_id'] ?? 0);
        $amount = (float) ($_POST['amount_invested'] ?? 0);
        $equityPercent = (float) ($_POST['equity_allotted_percent'] ?? 0);
        $numShares = (int) ($_POST['number_of_shares'] ?? 0);
        $shareClass = trim($_POST['share_class'] ?? 'Series Seed CCPS');

        if ($companyId <= 0 || $roundId <= 0 || $investorId <= 0 || $amount <= 0 || $numShares <= 0) {
            $error = 'Please fill in all mandatory allotment fields with valid values.';
        } else {
            try {
                $db->beginTransaction();

                $maxDistinctive = (int) $db->query("SELECT COALESCE(MAX(distinctive_to), 10000) FROM investments")->fetchColumn();
                $distinctiveFrom = $maxDistinctive + 1;
                $distinctiveTo = $distinctiveFrom + $numShares - 1;
                $pricePerShare = round($amount / $numShares, 2);

                $compStmt = $db->prepare("SELECT name FROM companies WHERE id = ?");
                $compStmt->execute([$companyId]);
                $compName = $compStmt->fetchColumn() ?: 'PORT';
                $compCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $compName), 0, 3));
                $certNum = 'SHA-2026-' . $compCode . '-' . rand(100, 999);
                $folio = 'FOLIO-' . str_pad($investorId, 4, '0', STR_PAD_LEFT);
                $token = bin2hex(random_bytes(16));

                $db->prepare("INSERT INTO investment_orders (funding_round_id, investor_user_id, amount, status, terms_accepted) VALUES (?, ?, ?, 'CONFIRMED', 1)")
                    ->execute([$roundId, $investorId, $amount]);
                $orderId = $db->lastInsertId();

                $db->prepare("
                    INSERT INTO investments
                    (order_id, funding_round_id, investor_user_id, company_id, amount_invested, equity_allotted_percent, number_of_shares, price_per_share, distinctive_from, distinctive_to, share_class, folio_number, certificate_number, verification_token, allotment_status, confirmed_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'certificate_issued', NOW())
                ")->execute([
                            $orderId,
                            $roundId,
                            $investorId,
                            $companyId,
                            $amount,
                            $equityPercent,
                            $numShares,
                            $pricePerShare,
                            $distinctiveFrom,
                            $distinctiveTo,
                            $shareClass,
                            $folio,
                            $certNum,
                            $token
                        ]);
                $newInvId = $db->lastInsertId();

                $txRef = 'TX-SHA-' . strtoupper(substr(md5(uniqid()), 0, 8));
                $db->prepare("INSERT INTO transactions (investment_id, order_id, transaction_ref, payment_mode, amount, status) VALUES (?, ?, ?, 'Escrow Wire / Direct Allotment', ?, 'SUCCESS')")
                    ->execute([$newInvId, $orderId, $txRef, $amount]);

                $db->commit();

                send_notification($investorId, 'Share Certificate Issued', "Official Digital Share Certificate #{$certNum} has been issued and credited to your demat portfolio.", 'success', 'certificate.php?id=' . $newInvId);
                log_audit($user['id'], 'ISSUE_SHARE_CERTIFICATE', 'investments', $newInvId, "Issued certificate {$certNum} for {$numShares} shares in company #{$companyId}");
                set_flash('success', "Digital Share Certificate #{$certNum} generated successfully!");
                header('Location: ' . url('admin/share_allotments.php'));
                exit;
            } catch (Exception $e) {
                if ($db->inTransaction())
                    $db->rollBack();
                $error = 'Error issuing allotment: ' . $e->getMessage();
            }
        }
    }
}

/* ---------- Data loading ---------- */
$totalSharesAllotted = 0;
$totalCapitalAllotted = 0;
$totalCertificatesCount = 0;
$allotments = [];
$companiesList = [];
$fundingRoundsList = [];
$investorsList = [];
$selectedCompany = null;
$capTableBreakdown = ['founders' => [], 'investors' => []];
$selectedCompanyId = (int) ($_GET['company_filter'] ?? 0);

if ($db) {
    $agg = $db->query("SELECT COALESCE(SUM(number_of_shares),0) total_shares, COALESCE(SUM(amount_invested),0) total_capital, COUNT(*) total_certs FROM investments WHERE allotment_status = 'certificate_issued'")->fetch();
    $totalSharesAllotted = (int) ($agg['total_shares'] ?? 0);
    $totalCapitalAllotted = (float) ($agg['total_capital'] ?? 0);
    $totalCertificatesCount = (int) ($agg['total_certs'] ?? 0);

    $sql = "SELECT inv.*, c.name company_name, c.cin_number, u.name investor_name, u.email investor_email, fr.round_name
            FROM investments inv
            JOIN companies c ON inv.company_id = c.id
            JOIN users u ON inv.investor_user_id = u.id
            JOIN funding_rounds fr ON inv.funding_round_id = fr.id";
    $params = [];
    if ($selectedCompanyId > 0) {
        $sql .= " WHERE inv.company_id = ?";
        $params[] = $selectedCompanyId;
    }
    $sql .= " ORDER BY inv.confirmed_at DESC, inv.id DESC";
    $st = $db->prepare($sql);
    $st->execute($params);
    $allotments = $st->fetchAll();

    $companiesList = $db->query("SELECT id, name, cin_number, industry FROM companies ORDER BY name ASC")->fetchAll();
    $fundingRoundsList = $db->query("SELECT fr.id, fr.company_id, fr.round_name, c.name company_name FROM funding_rounds fr JOIN companies c ON fr.company_id = c.id ORDER BY fr.id DESC")->fetchAll();
    $investorsList = $db->query("SELECT id, name, email FROM users WHERE role = 'investor' AND status = 'active' ORDER BY name ASC")->fetchAll();

    if ($selectedCompanyId > 0) {
        $s = $db->prepare("SELECT * FROM companies WHERE id = ?");
        $s->execute([$selectedCompanyId]);
        $selectedCompany = $s->fetch();
        if ($selectedCompany) {
            $s = $db->prepare("SELECT cf.*, u.name founder_name FROM company_founders cf JOIN users u ON cf.user_id = u.id WHERE cf.company_id = ?");
            $s->execute([$selectedCompanyId]);
            $capTableBreakdown['founders'] = $s->fetchAll();
            $s = $db->prepare("SELECT inv.*, u.name investor_name, fr.round_name FROM investments inv JOIN users u ON inv.investor_user_id = u.id JOIN funding_rounds fr ON inv.funding_round_id = fr.id WHERE inv.company_id = ? AND inv.allotment_status = 'certificate_issued'");
            $s->execute([$selectedCompanyId]);
            $capTableBreakdown['investors'] = $s->fetchAll();
        }
    }
}

function initials($name)
{
    $p = preg_split('/\s+/', trim($name));
    return strtoupper(substr($p[0] ?? '?', 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : ''));
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
        :root {
            --sa-bg: #F6F8FB;
            --sa-card: #FFFFFF;
            --sa-border: #E5EAF2;
            --sa-text: #0F172A;
            --sa-muted: #64748B;
            --sa-soft: #F1F5F9;
            --sa-accent: #4F46E5;
            --sa-accent-soft: #EEF2FF;
        }

        html.dark {
            --sa-bg: #0B0F19;
            --sa-card: #111827;
            --sa-border: #1E293B;
            --sa-text: #F1F5F9;
            --sa-muted: #94A3B8;
            --sa-soft: #1A2333;
            --sa-accent: #818CF8;
            --sa-accent-soft: #1E1B4B;
        }

        body {
            background: var(--sa-bg);
            color: var(--sa-text);
        }

        .sa-card {
            background: var(--sa-card);
            border: 1px solid var(--sa-border);
            border-radius: 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .sa-muted {
            color: var(--sa-muted);
        }

        .sa-soft {
            background: var(--sa-soft);
        }

        .sa-hero {
            background: linear-gradient(135deg, #312E81 0%, #4F46E5 55%, #7C3AED 100%);
            border-radius: 1.25rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .sa-hero::after {
            content: "";
            position: absolute;
            right: -60px;
            top: -60px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
        }

        .sa-hero::before {
            content: "";
            position: absolute;
            right: 90px;
            bottom: -90px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .06);
        }

        .sa-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .6rem 1rem;
            border-radius: .75rem;
            font-weight: 700;
            font-size: .8125rem;
            transition: all .15s;
            cursor: pointer;
        }

        .sa-btn-light {
            background: #fff;
            color: #3730A3;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
        }

        .sa-btn-light:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, .2);
        }

        .sa-btn-primary {
            background: var(--sa-accent);
            color: #fff;
        }

        .sa-btn-primary:hover {
            filter: brightness(.92);
        }

        .sa-btn-ghost {
            background: var(--sa-soft);
            color: var(--sa-text);
            border: 1px solid var(--sa-border);
        }

        .sa-btn-ghost:hover {
            border-color: var(--sa-accent);
            color: var(--sa-accent);
        }

        .sa-kpi {
            padding: 1.25rem;
            transition: transform .2s, box-shadow .2s;
        }

        .sa-kpi:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px -8px rgba(15, 23, 42, .12);
        }

        .sa-ico {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: .75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sa-input {
            width: 100%;
            padding: .65rem .85rem;
            background: var(--sa-soft);
            border: 1px solid var(--sa-border);
            border-radius: .65rem;
            font-size: .8125rem;
            color: var(--sa-text);
            outline: none;
            transition: all .15s;
        }

        .sa-input:focus {
            background: var(--sa-card);
            border-color: var(--sa-accent);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
        }

        .sa-label {
            display: block;
            font-size: .6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--sa-muted);
            margin-bottom: .35rem;
        }

        .sa-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .8125rem;
        }

        .sa-table th {
            position: sticky;
            top: 0;
            text-align: left;
            padding: .8rem 1rem;
            font-size: .6875rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--sa-muted);
            background: var(--sa-soft);
            border-bottom: 1px solid var(--sa-border);
            white-space: nowrap;
        }

        .sa-table td {
            padding: .9rem 1rem;
            border-bottom: 1px solid var(--sa-border);
            vertical-align: middle;
        }

        .sa-table tbody tr {
            transition: background .12s;
        }

        .sa-table tbody tr:hover {
            background: var(--sa-soft);
        }

        .sa-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .sa-avatar {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: .65rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: .75rem;
            flex-shrink: 0;
        }

        .sa-pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .2rem .6rem;
            border-radius: 999px;
            font-size: .6875rem;
            font-weight: 700;
        }

        .sa-pill-ok {
            background: #DCFCE7;
            color: #166534;
        }

        html.dark .sa-pill-ok {
            background: #052E1B;
            color: #86EFAC;
        }

        .sa-chip {
            font-family: ui-monospace, monospace;
            background: var(--sa-accent-soft);
            color: var(--sa-accent);
            padding: .2rem .5rem;
            border-radius: .45rem;
            font-weight: 700;
            font-size: .75rem;
        }

        .sa-modal-backdrop {
            background: rgba(15, 23, 42, .55);
            backdrop-filter: blur(4px);
        }

        .sa-modal {
            background: var(--sa-card);
            border: 1px solid var(--sa-border);
            border-radius: 1.25rem;
            max-height: 92vh;
            overflow: auto;
            animation: saPop .2s ease-out;
        }

        @keyframes saPop {
            from {
                opacity: 0;
                transform: translateY(12px) scale(.98);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }
    </style>
</head>

<body class="flex min-h-screen font-sans antialiased">

    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="allotment-main">

            <?php if ($flash):
                $ok = ($flash['type'] ?? '') === 'success'; ?>
                <div
                    class="p-4 rounded-xl text-sm font-semibold border flex items-center gap-2 <?= $ok ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800' ?>">
                    <i data-lucide="<?= $ok ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div
                    class="p-4 rounded-xl text-sm font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i><span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Hero -->
            <section class="sa-hero p-6 sm:p-8">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="space-y-2 max-w-2xl">
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/15 text-[11px] font-bold tracking-wide uppercase">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Demat &amp; MCA Registry
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Share Allotments &amp; Cap Table</h1>
                        <p class="text-sm text-indigo-100">Issue digital share certificates, track distinctive share
                            ranges and govern each startup's equity structure under Sec. 56 of the Companies Act, 2013.
                        </p>
                    </div>
                    <button type="button" onclick="openModal()" class="sa-btn sa-btn-light self-start md:self-center">
                        <i data-lucide="plus" class="w-4 h-4"></i> Issue Share Certificate
                    </button>
                </div>
            </section>

            <!-- KPIs -->
            <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="sa-card sa-kpi">
                    <div class="flex items-center justify-between"><span class="sa-label !mb-0">Shares Allotted</span>
                        <div class="sa-ico bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400"><i
                                data-lucide="layers" class="w-5 h-5"></i></div>
                    </div>
                    <div class="text-3xl font-black mt-3"><?= number_format($totalSharesAllotted) ?></div>
                    <div class="text-xs sa-muted mt-1">Units of equity &amp; CCPS</div>
                </div>
                <div class="sa-card sa-kpi">
                    <div class="flex items-center justify-between"><span class="sa-label !mb-0">Allotted Capital</span>
                        <div class="sa-ico bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400"><i
                                data-lucide="trending-up" class="w-5 h-5"></i></div>
                    </div>
                    <div class="text-3xl font-black mt-3 text-emerald-600 dark:text-emerald-400">
                        <?= format_inr($totalCapitalAllotted) ?></div>
                    <div class="text-xs sa-muted mt-1">Investor subscriptions</div>
                </div>
                <div class="sa-card sa-kpi">
                    <div class="flex items-center justify-between"><span class="sa-label !mb-0">Certificates
                            Issued</span>
                        <div class="sa-ico bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400"><i
                                data-lucide="award" class="w-5 h-5"></i></div>
                    </div>
                    <div class="text-3xl font-black mt-3"><?= number_format($totalCertificatesCount) ?></div>
                    <div class="text-xs sa-muted mt-1">Digitally signed &amp; verified</div>
                </div>
                <div class="sa-card sa-kpi">
                    <div class="flex items-center justify-between"><span class="sa-label !mb-0">Compliance</span>
                        <div class="sa-ico bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400"><i
                                data-lucide="shield-check" class="w-5 h-5"></i></div>
                    </div>
                    <div class="text-2xl font-black mt-3 text-violet-600 dark:text-violet-400">SEBI &amp; MCA 2013</div>
                    <div class="text-xs sa-muted mt-1">Sec. 56 Companies Act</div>
                </div>
            </section>

            <!-- Toolbar -->
            <section class="sa-card p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <form action="<?= url('admin/share_allotments.php') ?>" method="GET"
                    class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 text-sm font-bold"><i data-lucide="filter"
                            class="w-4 h-4 sa-muted"></i> Startup</div>
                    <select name="company_filter" onchange="this.form.submit()"
                        class="sa-input !w-auto min-w-[220px] cursor-pointer font-semibold">
                        <option value="0">All startups</option>
                        <?php foreach ($companiesList as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= $selectedCompanyId == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($selectedCompanyId > 0): ?>
                        <a href="<?= url('admin/share_allotments.php') ?>"
                            class="text-xs font-bold text-rose-600 hover:underline inline-flex items-center gap-1"><i
                                data-lucide="x" class="w-3.5 h-3.5"></i> Clear</a>
                    <?php endif; ?>
                </form>
                <div class="flex items-center gap-3 w-full lg:w-auto">
                    <div class="relative flex-1 lg:w-72">
                        <i data-lucide="search" class="w-4 h-4 sa-muted absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" id="tableSearch" placeholder="Search certificate, investor, startup…"
                            class="sa-input !pl-9" oninput="filterRows(this.value)">
                    </div>
                    <span class="text-xs sa-muted whitespace-nowrap"><b id="rowCount"
                            class="text-[color:var(--sa-text)]"><?= count($allotments) ?></b> certificates</span>
                </div>
            </section>

            <!-- Cap Table Inspector -->
            <?php if ($selectedCompany):
                $foundersEquity = 0;
                foreach ($capTableBreakdown['founders'] as $f)
                    $foundersEquity += (float) $f['equity_percent'];
                $investorsEquity = 0;
                foreach ($capTableBreakdown['investors'] as $i)
                    $investorsEquity += (float) $i['equity_allotted_percent'];
                $esop = (float) ($selectedCompany['esop_pool_percent'] ?: 10.00);
                $unallocated = max(0, 100 - ($foundersEquity + $investorsEquity + $esop));
                $segments = [
                    ['Founders', $foundersEquity, '#4F46E5'],
                    ['Investors', $investorsEquity, '#10B981'],
                    ['ESOP Pool', $esop, '#F59E0B'],
                    ['Unallocated', $unallocated, '#CBD5E1'],
                ];
                ?>
                <section class="sa-card overflow-hidden">
                    <button type="button" onclick="toggleCapTable()"
                        class="w-full p-5 flex items-center justify-between text-left hover:bg-[color:var(--sa-soft)] transition">
                        <div class="flex items-center gap-3">
                            <div class="sa-ico bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400"><i
                                    data-lucide="pie-chart" class="w-5 h-5"></i></div>
                            <div>
                                <div
                                    class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                    Cap Table Inspector</div>
                                <h2 class="text-base font-black"><?= htmlspecialchars($selectedCompany['name']) ?> — Equity
                                    Architecture</h2>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="hidden sm:inline-flex sa-chip">Authorized:
                                <?= format_inr($selectedCompany['authorized_capital'] ?: 10000000) ?></span>
                            <i id="cap-chevron" data-lucide="chevron-down"
                                class="w-5 h-5 sa-muted transition-transform duration-200 rotate-180"></i>
                        </div>
                    </button>

                    <div id="cap-table-body" class="px-5 pb-6 pt-4 border-t" style="border-color:var(--sa-border)">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                            <?php foreach ($segments as $s):
                                if ($s[0] === 'Unallocated' && $s[1] <= 0)
                                    continue; ?>
                                <div class="sa-soft rounded-xl p-3">
                                    <div class="flex items-center gap-2 text-xs sa-muted font-semibold"><span
                                            class="w-2.5 h-2.5 rounded-full" style="background:<?= $s[2] ?>"></span><?= $s[0] ?>
                                    </div>
                                    <div class="text-xl font-black mt-1"><?= number_format($s[1], 2) ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="w-full h-3.5 rounded-full overflow-hidden flex sa-soft mb-6">
                            <?php foreach ($segments as $s):
                                if ($s[1] <= 0)
                                    continue; ?>
                                <div style="width:<?= min(100, $s[1]) ?>%; background:<?= $s[2] ?>"
                                    title="<?= $s[0] ?>: <?= number_format($s[1], 2) ?>%"></div>
                            <?php endforeach; ?>
                        </div>

                        <div class="overflow-x-auto rounded-xl border" style="border-color:var(--sa-border)">
                            <table class="sa-table">
                                <thead>
                                    <tr>
                                        <th>Stakeholder</th>
                                        <th>Role / Class</th>
                                        <th>Shares / Stake</th>
                                        <th class="text-right">Ownership</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($capTableBreakdown['founders'] as $f): ?>
                                        <tr>
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="sa-avatar bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                                        <?= htmlspecialchars(initials($f['founder_name'])) ?></div><span
                                                        class="font-bold"><?= htmlspecialchars($f['founder_name']) ?></span>
                                                </div>
                                            </td>
                                            <td class="sa-muted"><?= htmlspecialchars($f['designation']) ?></td>
                                            <td class="font-mono">Common Stock</td>
                                            <td class="text-right font-black text-indigo-600 dark:text-indigo-400">
                                                <?= htmlspecialchars($f['equity_percent']) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php foreach ($capTableBreakdown['investors'] as $i): ?>
                                        <tr>
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="sa-avatar bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                                        <?= htmlspecialchars(initials($i['investor_name'])) ?></div><span
                                                        class="font-bold"><?= htmlspecialchars($i['investor_name']) ?></span>
                                                </div>
                                            </td>
                                            <td class="sa-muted"><?= htmlspecialchars($i['round_name']) ?> ·
                                                <?= htmlspecialchars($i['share_class']) ?></td>
                                            <td class="font-mono text-emerald-600 dark:text-emerald-400">
                                                <?= number_format($i['number_of_shares']) ?> shares</td>
                                            <td class="text-right font-black text-emerald-600 dark:text-emerald-400">
                                                <?= htmlspecialchars($i['equity_allotted_percent']) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="sa-avatar bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                                    <i data-lucide="users" class="w-4 h-4"></i></div><span
                                                    class="font-bold">Employee Stock Option Plan</span>
                                            </div>
                                        </td>
                                        <td class="sa-muted">Talent Pool Reserve</td>
                                        <td class="font-mono sa-muted">Reserved Options</td>
                                        <td class="text-right font-black text-amber-600"><?= number_format($esop, 2) ?>%
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Allotments Table -->
            <section class="sa-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="sa-table" id="allotTable">
                        <thead>
                            <tr>
                                <th>Certificate</th>
                                <th>Startup</th>
                                <th>Investor</th>
                                <th>Shares &amp; Class</th>
                                <th>Distinctive Range</th>
                                <th>Capital &amp; Stake</th>
                                <th>Date</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allotments)): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="py-14 text-center space-y-3">
                                            <div
                                                class="sa-ico bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400 mx-auto !w-14 !h-14">
                                                <i data-lucide="file-badge" class="w-7 h-7"></i></div>
                                            <div class="font-bold">No share allotments yet</div>
                                            <p class="text-xs sa-muted">Issue the first digital share certificate to start
                                                building the registry.</p>
                                            <button type="button" onclick="openModal()" class="sa-btn sa-btn-primary"><i
                                                    data-lucide="plus" class="w-4 h-4"></i> Issue Share Certificate</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($allotments as $a): ?>
                                    <tr
                                        data-search="<?= htmlspecialchars(strtolower($a['certificate_number'] . ' ' . $a['company_name'] . ' ' . $a['investor_name'] . ' ' . $a['investor_email'] . ' ' . $a['folio_number'])) ?>">
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <span class="sa-chip"><?= htmlspecialchars($a['certificate_number']) ?></span>
                                                <button type="button" title="Copy"
                                                    onclick="copyText('<?= htmlspecialchars($a['certificate_number'], ENT_QUOTES) ?>', this)"
                                                    class="sa-muted hover:text-indigo-600"><i data-lucide="copy"
                                                        class="w-3.5 h-3.5"></i></button>
                                            </div>
                                            <div class="text-[11px] sa-muted mt-1"><?= htmlspecialchars($a['folio_number']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?= htmlspecialchars($a['company_name']) ?></div>
                                            <div class="text-[11px] sa-muted mt-0.5">
                                                <?= htmlspecialchars($a['cin_number'] ?: 'CIN Verified') ?></div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="sa-avatar bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                                    <?= htmlspecialchars(initials($a['investor_name'])) ?></div>
                                                <div class="min-w-0">
                                                    <div class="font-semibold truncate">
                                                        <?= htmlspecialchars($a['investor_name']) ?></div>
                                                    <div class="text-[11px] sa-muted truncate">
                                                        <?= htmlspecialchars($a['investor_email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-mono font-bold"><?= number_format($a['number_of_shares']) ?> units
                                            </div>
                                            <div class="text-[11px] sa-muted mt-0.5 truncate max-w-[150px]">
                                                <?= htmlspecialchars($a['share_class']) ?></div>
                                        </td>
                                        <td class="font-mono text-xs sa-muted whitespace-nowrap">
                                            <?= str_pad($a['distinctive_from'], 6, '0', STR_PAD_LEFT) ?> –
                                            <?= str_pad($a['distinctive_to'], 6, '0', STR_PAD_LEFT) ?></td>
                                        <td>
                                            <div class="font-black font-mono text-emerald-600 dark:text-emerald-400">
                                                <?= format_inr($a['amount_invested']) ?></div>
                                            <span
                                                class="sa-pill sa-pill-ok mt-1"><?= htmlspecialchars($a['equity_allotted_percent']) ?>%
                                                equity</span>
                                        </td>
                                        <td class="text-xs sa-muted whitespace-nowrap">
                                            <?= date('d M Y', strtotime($a['confirmed_at'])) ?></td>
                                        <td class="text-right">
                                            <a href="<?= url('certificate.php?id=' . (int) $a['id']) ?>" target="_blank"
                                                class="sa-btn sa-btn-ghost !py-1.5 !px-3 !text-xs">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="noMatch" class="hidden py-10 text-center text-sm sa-muted">No certificates match your search.
                </div>
            </section>
        </main>
    </div>

    <!-- Issue Modal -->
    <div id="issueModal" class="hidden fixed inset-0 z-50 sa-modal-backdrop flex items-center justify-center p-4"
        onclick="if(event.target===this)closeModal()">
        <div class="sa-modal max-w-xl w-full p-6 shadow-2xl relative">
            <button type="button" onclick="closeModal()" class="absolute top-4 right-4 sa-muted hover:text-rose-500"><i
                    data-lucide="x" class="w-5 h-5"></i></button>

            <div class="flex items-center gap-3 mb-5">
                <div class="sa-ico bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400"><i
                        data-lucide="award" class="w-5 h-5"></i></div>
                <div>
                    <h3 class="text-base font-black">Issue Digital Share Certificate</h3>
                    <p class="text-xs sa-muted">Allocate equity shares and generate a signed certificate.</p>
                </div>
            </div>

            <form method="POST" action="<?= url('admin/share_allotments.php') ?>" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="form_action" value="create_allotment">

                <div class="space-y-3">
                    <div class="text-[11px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">1
                        · Parties</div>
                    <div>
                        <label class="sa-label">Startup Company</label>
                        <select name="company_id" id="fCompany" required class="sa-input" onchange="syncRounds()">
                            <option value="">Select startup…</option>
                            <?php foreach ($companiesList as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['name']) ?>
                                    (<?= htmlspecialchars($c['industry']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="sa-label">Funding Round</label>
                            <select name="funding_round_id" id="fRound" required class="sa-input">
                                <option value="">Select startup first…</option>
                                <?php foreach ($fundingRoundsList as $fr): ?>
                                    <option value="<?= (int) $fr['id'] ?>" data-company="<?= (int) $fr['company_id'] ?>"
                                        hidden disabled><?= htmlspecialchars($fr['company_name']) ?> —
                                        <?= htmlspecialchars($fr['round_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="sa-label">Allottee Investor</label>
                            <select name="investor_user_id" required class="sa-input">
                                <option value="">Select investor…</option>
                                <?php foreach ($investorsList as $inv): ?>
                                    <option value="<?= (int) $inv['id'] ?>"><?= htmlspecialchars($inv['name']) ?>
                                        (<?= htmlspecialchars($inv['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="text-[11px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">2
                        · Allotment details</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="sa-label">Capital Invested (₹)</label>
                            <input type="number" step="0.01" min="0" name="amount_invested" id="fAmount" required
                                placeholder="500000" class="sa-input font-mono" oninput="calcPrice()">
                        </div>
                        <div>
                            <label class="sa-label">Equity Allotted (%)</label>
                            <input type="number" step="0.001" min="0" name="equity_allotted_percent" required
                                placeholder="1.250" class="sa-input font-mono">
                        </div>
                        <div>
                            <label class="sa-label">Number of Shares</label>
                            <input type="number" min="1" name="number_of_shares" id="fShares" required
                                placeholder="2000" class="sa-input font-mono" oninput="calcPrice()">
                        </div>
                        <div>
                            <label class="sa-label">Share Class</label>
                            <input type="text" name="share_class" value="Series Seed CCPS" required class="sa-input">
                        </div>
                    </div>
                    <div class="sa-soft rounded-xl px-4 py-3 flex items-center justify-between text-xs">
                        <span class="sa-muted font-semibold">Computed price per share</span>
                        <span id="pricePreview" class="font-mono font-black text-sm">—</span>
                    </div>
                </div>

                <div class="pt-4 border-t flex items-center justify-end gap-2" style="border-color:var(--sa-border)">
                    <button type="button" onclick="closeModal()" class="sa-btn sa-btn-ghost">Cancel</button>
                    <button type="submit" class="sa-btn sa-btn-primary"><i data-lucide="send" class="w-4 h-4"></i>
                        Generate &amp; Dispatch</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        if (window.lucide) lucide.createIcons();
        if (window.gsap) gsap.from("#allotment-main > *", { duration: 0.4, y: 12, opacity: 0, stagger: 0.06, ease: "power2.out", clearProps: "all" });

        const modal = document.getElementById('issueModal');
        function openModal() { modal.classList.remove('hidden'); }
        function closeModal() { modal.classList.add('hidden'); }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        function toggleCapTable() {
            const body = document.getElementById('cap-table-body'), chev = document.getElementById('cap-chevron');
            if (!body || !chev) return;
            chev.classList.toggle('rotate-180', !body.classList.toggle('hidden'));
        }

        function syncRounds() {
            const cid = document.getElementById('fCompany').value, sel = document.getElementById('fRound');
            let first = null;
            sel.querySelectorAll('option[data-company]').forEach(o => {
                const show = o.dataset.company === cid;
                o.hidden = !show; o.disabled = !show;
                if (show && !first) first = o;
            });
            sel.options[0].textContent = cid ? (first ? 'Select funding round…' : 'No rounds for this startup') : 'Select startup first…';
            sel.value = '';
        }

        function calcPrice() {
            const a = parseFloat(document.getElementById('fAmount').value), s = parseInt(document.getElementById('fShares').value);
            document.getElementById('pricePreview').textContent = (a > 0 && s > 0) ? '₹ ' + (a / s).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
        }

        function filterRows(q) {
            q = q.trim().toLowerCase();
            const rows = document.querySelectorAll('#allotTable tbody tr[data-search]');
            let n = 0;
            rows.forEach(r => { const m = r.dataset.search.includes(q); r.classList.toggle('hidden', !m); if (m) n++; });
            document.getElementById('rowCount').textContent = n;
            document.getElementById('noMatch').classList.toggle('hidden', n > 0 || rows.length === 0);
        }

        function copyText(t, btn) {
            navigator.clipboard.writeText(t).then(() => {
                btn.classList.add('text-emerald-600');
                setTimeout(() => btn.classList.remove('text-emerald-600'), 1200);
            });
        }
    </script>
</body>

</html>