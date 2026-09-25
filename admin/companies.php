<?php
/**
 * Admin Module: Startup Company Master Review & Management
 * Implements Section 14 (Company Management & Verification)
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Company Management & Verification';

$flash = get_flash();
$error = '';
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

// Handle company verification updates
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
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
                    $db->prepare("UPDATE companies SET verified_status = 'verified' WHERE id = ?")->execute([$companyId]);
                    log_audit($user['id'], 'ADMIN_VERIFY_COMPANY', 'companies', $companyId, "Admin verified company: {$comp['name']}");

                    // Notify company founders
                    $fStmt = $db->prepare("SELECT user_id FROM company_founders WHERE company_id = ?");
                    $fStmt->execute([$companyId]);
                    foreach ($fStmt->fetchAll() as $f) {
                        send_notification($f['user_id'], 'Company Profile Verified!', "{$comp['name']} has been officially approved by the compliance team.", 'success', 'founder/company.php');
                    }
                    set_flash('success', "Company '{$comp['name']}' has been marked as verified.");
                } elseif ($action === 'reject_company') {
                    $reason = trim($_POST['reason'] ?? 'Documentation failed compliance guidelines.');
                    $db->prepare("UPDATE companies SET verified_status = 'rejected' WHERE id = ?")->execute([$companyId]);
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
$cStmt = $db->prepare($sql);
$cStmt->execute($params);
$companies = $cStmt->fetchAll();

// Metrics
$totalCompanies = (int)$db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$verifiedCompanies = (int)$db->query("SELECT COUNT(*) FROM companies WHERE verified_status = 'verified'")->fetchColumn();
$pendingCompanies = (int)$db->query("SELECT COUNT(*) FROM companies WHERE verified_status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-6xl w-full mx-auto" id="comp-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center space-x-2.5">
                        <i data-lucide="building-2" class="w-6 h-6 text-purple-600"></i>
                        <span>Startup Company Management</span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">Review legal incorporation CIN references, pitch data, and business eligibility.</p>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div class="card-clean rounded-2xl p-5 flex items-center space-x-4">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                        <i data-lucide="building" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-[10.5px] uppercase font-bold text-slate-400">Total Startups</div>
                        <div class="text-lg font-black text-slate-900 mt-0.5"><?= $totalCompanies ?></div>
                    </div>
                </div>
                <div class="card-clean rounded-2xl p-5 flex items-center space-x-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                        <i data-lucide="badge-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-[10.5px] uppercase font-bold text-slate-400">MCA / CIN Verified</div>
                        <div class="text-lg font-black text-emerald-600 mt-0.5"><?= $verifiedCompanies ?></div>
                    </div>
                </div>
                <div class="card-clean rounded-2xl p-5 flex items-center space-x-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-[10.5px] uppercase font-bold text-slate-400">Pending Review</div>
                        <div class="text-lg font-black text-amber-600 mt-0.5"><?= $pendingCompanies ?></div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="card-clean rounded-2xl p-4">
                <form action="<?= url('admin/companies.php') ?>" method="GET" class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <div class="relative flex-1 w-full">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search company name, CIN reference, founder or city..."
                               class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-purple-600 rounded-lg text-xs outline-none">
                    </div>
                    <div class="flex items-center space-x-2 w-full sm:w-auto">
                        <select name="status" class="px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white rounded-lg text-xs outline-none" onchange="this.form.submit()">
                            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="verified" <?= $statusFilter === 'verified' ? 'selected' : '' ?>>Verified</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg text-xs transition">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Companies Table -->
            <div class="card-clean rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50 text-[10.5px] uppercase font-bold text-slate-400 tracking-wider">
                                <th class="py-3 px-5">Startup</th>
                                <th class="py-3 px-4">CIN / Identifiers</th>
                                <th class="py-3 px-4">Primary Founder</th>
                                <th class="py-3 px-4">Industry & Stage</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Review Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($companies)): ?>
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-slate-400 text-xs">No companies match your search criteria.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($companies as $c): ?>
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-3 px-5">
                                            <div class="flex items-center space-x-3">
                                                <img src="<?= $c['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=60' ?>" 
                                                     class="w-9 h-9 rounded-xl object-cover border border-slate-200 bg-white">
                                                <div>
                                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($c['name']) ?></div>
                                                    <div class="text-[10.5px] text-slate-400"><?= htmlspecialchars($c['city'] ?? 'Bengaluru') ?>, <?= htmlspecialchars($c['country'] ?? 'India') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-mono text-[11px] text-slate-700 font-semibold"><?= htmlspecialchars($c['cin_number'] ?? 'U72900KA2024PTC123456') ?></div>
                                            <div class="text-[10px] text-slate-400">Incorp: <?= $c['incorporation_date'] ?: '2023' ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-semibold text-slate-800"><?= htmlspecialchars($c['primary_founder'] ?? 'Lead Founder') ?></div>
                                            <div class="text-[10.5px] text-slate-400"><?= $c['founder_count'] ?> Founder<?= $c['founder_count'] > 1 ? 's' : '' ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10.5px] font-semibold">
                                                <?= htmlspecialchars($c['industry']) ?>
                                            </span>
                                            <div class="text-[10px] text-slate-400 mt-0.5 font-medium"><?= htmlspecialchars($c['stage']) ?> Stage</div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $c['verified_status'] === 'verified' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($c['verified_status'] === 'rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') ?>">
                                                <?= strtoupper($c['verified_status']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <div class="flex items-center justify-end space-x-1.5">
                                                <a href="<?= url('investor/startup_detail.php?id=' . encode_id($c['id'])) ?>" target="_blank" class="p-1.5 text-slate-400 hover:text-purple-600 rounded-lg hover:bg-slate-100 transition" title="View Public Deal Page">
                                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                                </a>
                                                <?php if ($c['verified_status'] !== 'verified'): ?>
                                                    <form action="<?= url('admin/companies.php?status=' . urlencode($statusFilter) . '&search=' . urlencode($search)) ?>" method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="form_action" value="verify_company">
                                                        <input type="hidden" name="company_id" value="<?= $c['id'] ?>">
                                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-semibold text-[11px] transition">
                                                            Approve
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($c['verified_status'] !== 'rejected'): ?>
                                                    <form action="<?= url('admin/companies.php?status=' . urlencode($statusFilter) . '&search=' . urlencode($search)) ?>" method="POST" class="inline" onsubmit="return confirm('Reject or flag this startup company?');">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="form_action" value="reject_company">
                                                        <input type="hidden" name="company_id" value="<?= $c['id'] ?>">
                                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-[11px] transition">
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
        gsap.from("#comp-main", { duration: 0.35, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
