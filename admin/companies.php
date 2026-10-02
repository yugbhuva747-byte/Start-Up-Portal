<?php
/**
 * Admin Module: Startup Company Master Review & Management
 * Implements Section 14 (Company Management & Verification)
 * Clean, Executive Startup Registry
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Startup Companies';

$flash = get_flash();
$error = '';
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

// Handle company verification updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid.';
    } else {
        $action = $_POST['form_action'] ?? '';
        $companyId = (int)($_POST['company_id'] ?? 0);

        if ($companyId > 0) {
            $compStmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
            $compStmt->execute([$companyId]);
            $comp = $compStmt->fetch();

            if ($comp) {
                if ($action === 'verify_company') {
                    $db->prepare("UPDATE companies SET verified_status = 'verified', updated_at = NOW() WHERE id = ?")->execute([$companyId]);
                    log_audit($user['id'], 'ADMIN_VERIFY_COMPANY', 'companies', $companyId, "Admin verified company: {$comp['name']}");

                    // Notify company founders
                    $fStmt = $db->prepare("SELECT user_id FROM company_founders WHERE company_id = ?");
                    $fStmt->execute([$companyId]);
                    foreach ($fStmt->fetchAll() as $f) {
                        send_notification($f['user_id'], 'Company Profile Verified!', "{$comp['name']} has been officially approved by Compliance.", 'success', 'founder/company.php');
                    }
                    set_flash('success', "Company '{$comp['name']}' has been marked as verified.");
                } elseif ($action === 'reject_company') {
                    $reason = trim($_POST['reason'] ?? 'Documentation failed compliance guidelines.');
                    $db->prepare("UPDATE companies SET verified_status = 'rejected', updated_at = NOW() WHERE id = ?")->execute([$companyId]);
                    log_audit($user['id'], 'ADMIN_REJECT_COMPANY', 'companies', $companyId, "Admin rejected company: {$comp['name']}. Reason: $reason");

                    $fStmt = $db->prepare("SELECT user_id FROM company_founders WHERE company_id = ?");
                    $fStmt->execute([$companyId]);
                    foreach ($fStmt->fetchAll() as $f) {
                        send_notification($f['user_id'], 'Company Verification Flagged', "{$comp['name']} verification requires attention: $reason", 'error', 'founder/company.php');
                    }
                    set_flash('success', "Company '{$comp['name']}' status set to rejected.");
                }
            }
        }
        header('Location: ' . url('admin/companies.php?status=' . urlencode($statusFilter) . '&search=' . urlencode($search)));
        exit;
    }
}

// Fetch companies
$sql = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM company_founders WHERE company_id = c.id) as founder_count,
           (SELECT COUNT(*) FROM company_documents WHERE company_id = c.id) as document_count,
           (SELECT COUNT(*) FROM funding_rounds WHERE company_id = c.id) as round_count,
           (SELECT u.name FROM users u JOIN company_founders cf ON u.id = cf.user_id WHERE cf.company_id = c.id LIMIT 1) as primary_founder
    FROM companies c
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.name LIKE ? OR c.cin_number LIKE ? OR c.city LIKE ? OR c.industry LIKE ?)";
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

if ($statusFilter !== 'all') {
    $sql .= " AND c.verified_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY c.created_at DESC";

$companies = [];
if ($db) {
    try {
        $cStmt = $db->prepare($sql);
        $cStmt->execute($params);
        $companies = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        // Metrics
        $totalCompanies = (int)$db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
        $verifiedCompanies = (int)$db->query("SELECT COUNT(*) FROM companies WHERE verified_status = 'verified'")->fetchColumn();
        $pendingCompanies = (int)$db->query("SELECT COUNT(*) FROM companies WHERE verified_status = 'pending'")->fetchColumn();
        $totalRoundsCount = (int)$db->query("SELECT COUNT(*) FROM funding_rounds")->fetchColumn();
    } catch (Exception $e) {
        $error = 'Failed to load companies: ' . $e->getMessage();
    }
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
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease-in-out;
        }
        .dark .stat-card-clean, html.dark .stat-card-clean { background: #111827 !important; border-color: #1e293b !important; }
        .stat-card-clean:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px -4px rgba(0, 0, 0, 0.05);
        }
        .filter-bar-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 0.875rem 1.25rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        }
        .dark .filter-bar-clean, html.dark .filter-bar-clean { background: #111827 !important; border-color: #1e293b !important; }
        .table-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }
        .dark .table-card-clean, html.dark .table-card-clean { background: #111827 !important; border-color: #1e293b !important; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 flex min-h-screen dark:bg-[#0b0f19] dark:text-slate-100 font-sans antialiased">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6" id="comp-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-sm font-medium border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800' ?> flex items-center space-x-3 shadow-sm">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-4 rounded-xl text-sm font-medium border bg-rose-50 text-rose-800 border-rose-200 flex items-center space-x-3 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-950/50 border border-sky-200/80 dark:border-sky-800/80 flex items-center justify-center text-sky-600 dark:text-sky-400 shadow-sm flex-shrink-0">
                        <i data-lucide="building-2" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Startup Company Management
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            Review corporate incorporation records, inspect CIN numbers, and govern syndication permissions.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="window.location.reload()" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Refresh List</span>
                    </button>
                    <a href="<?= url('admin/funding_review.php') ?>" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                        <span>Funding Rounds</span>
                    </a>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Startups</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <i data-lucide="building" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-2"><?= number_format($totalCompanies) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Registered entities</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">CIN Verified</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="badge-check" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2"><?= number_format($verifiedCompanies) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Compliant for funding</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800 <?= $pendingCompanies > 0 ? 'ring-1 ring-amber-400/50' : '' ?>">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Needs Review</span>
                        <div class="w-8 h-8 rounded-lg <?= $pendingCompanies > 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400' : 'bg-slate-100 text-slate-400 dark:bg-slate-800' ?> flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold <?= $pendingCompanies > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' ?> mt-2"><?= number_format($pendingCompanies) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pending verification</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Rounds</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-2"><?= number_format($totalRoundsCount) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Syndicate campaigns</div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="filter-bar-clean dark:bg-slate-900 dark:border-slate-800">
                <form action="<?= url('admin/companies.php') ?>" method="GET" class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <div class="relative w-full flex-1">
                        <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search startup name, CIN number, founder or city..."
                               class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition">
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <select name="status" class="px-3 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" onchange="this.form.submit()">
                            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="verified" <?= $statusFilter === 'verified' ? 'selected' : '' ?>>Verified</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>

                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-xs transition shadow-2xs">
                            Filter
                        </button>
                        <?php if (!empty($search) || $statusFilter !== 'all'): ?>
                            <a href="<?= url('admin/companies.php') ?>" class="px-3 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-semibold text-xs transition" title="Reset filter">
                                Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Companies Table Card -->
            <div class="table-card-clean dark:bg-slate-900 dark:border-slate-800">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700/80 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                                <th class="py-3.5 px-4">Startup</th>
                                <th class="py-3.5 px-4">CIN / Incorp</th>
                                <th class="py-3.5 px-4">Primary Founder</th>
                                <th class="py-3.5 px-4">Industry & Stage</th>
                                <th class="py-3.5 px-4">Rounds</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if (empty($companies)): ?>
                                <tr>
                                    <td colspan="7" class="py-16 text-center text-slate-400 dark:text-slate-500">
                                        <i data-lucide="building" class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2"></i>
                                        <p class="font-medium text-sm">No companies match your search criteria.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($companies as $c): ?>
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                        <!-- Startup & Location -->
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-lg bg-sky-50 dark:bg-sky-950/70 text-sky-600 dark:text-sky-400 font-bold flex items-center justify-center text-xs border border-sky-100 dark:border-sky-800 flex-shrink-0 shadow-xs">
                                                    <?= strtoupper(substr($c['name'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($c['name']) ?></div>
                                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5"><?= htmlspecialchars($c['city'] ?? 'Bengaluru') ?>, <?= htmlspecialchars($c['state'] ?? 'India') ?></div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- CIN Number -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <div class="font-mono text-xs font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($c['cin_number'] ?? 'U72900KA2024PTC123456') ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">Incorp: <?= $c['incorporation_date'] ?: 'Active' ?></div>
                                        </td>

                                        <!-- Founder -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <div class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($c['primary_founder'] ?? 'Lead Founder') ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?= $c['founder_count'] ?> Founder<?= $c['founder_count'] > 1 ? 's' : '' ?></div>
                                        </td>

                                        <!-- Industry & Stage -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                <?= htmlspecialchars($c['industry']) ?>
                                            </span>
                                            <div class="text-[11px] text-slate-400 mt-1"><?= htmlspecialchars($c['stage']) ?> Stage</div>
                                        </td>

                                        <!-- Rounds -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <span class="font-semibold text-indigo-600 dark:text-indigo-400"><?= $c['round_count'] ?></span>
                                            <span class="text-[11px] text-slate-400">rounds</span>
                                        </td>

                                        <!-- Status -->
                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <?php if ($c['verified_status'] === 'verified'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                                    Verified
                                                </span>
                                            <?php elseif ($c['verified_status'] === 'rejected'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                                                    <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                                    Rejected
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">
                                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                                    Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5">
                                                <a href="<?= url('investor/startup_detail.php?id=' . encode_id($c['id'])) ?>" target="_blank" 
                                                   class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition" 
                                                   title="View Public Deal Page">
                                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                                </a>

                                                <?php if ($c['verified_status'] !== 'verified'): ?>
                                                    <form action="<?= url('admin/companies.php?status=' . urlencode($statusFilter) . '&search=' . urlencode($search)) ?>" method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="form_action" value="verify_company">
                                                        <input type="hidden" name="company_id" value="<?= $c['id'] ?>">
                                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-2xs">
                                                            Approve
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($c['verified_status'] !== 'rejected'): ?>
                                                    <form action="<?= url('admin/companies.php?status=' . urlencode($statusFilter) . '&search=' . urlencode($search)) ?>" method="POST" class="inline" onsubmit="return confirm('Flag or reject this company profile?');">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="form_action" value="reject_company">
                                                        <input type="hidden" name="company_id" value="<?= $c['id'] ?>">
                                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 transition">
                                                            Reject
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
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

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
