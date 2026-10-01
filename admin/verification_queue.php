<?php
/**
 * Admin Module: Verification Queue & KYC Document Review
 * Clean, Executive Compliance & Document Review Desk
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'KYC Verification Queue';

$error = '';
$flash = get_flash();

// Handle Individual Document Status Update (Approve / Reject single doc)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'update_doc_status') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $docId = (int)($_POST['doc_id'] ?? 0);
        $docScope = $_POST['doc_scope'] ?? 'verification'; // 'verification' or 'company'
        $newStatus = $_POST['new_status'] ?? 'verified';
        $docRemarks = trim($_POST['doc_remarks'] ?? '');

        if ($docId > 0 && in_array($newStatus, ['verified', 'rejected', 'pending'])) {
            if ($docScope === 'company') {
                $compStmt = $db->prepare("SELECT cd.*, c.name as company_name, cf.user_id FROM company_documents cd JOIN companies c ON cd.company_id = c.id LEFT JOIN company_founders cf ON c.id = cf.company_id WHERE cd.id = ?");
                $compStmt->execute([$docId]);
                $cDoc = $compStmt->fetch();

                if ($cDoc) {
                    $isVer = ($newStatus === 'verified') ? 1 : 0;
                    $db->prepare("UPDATE company_documents SET is_verified = ? WHERE id = ?")->execute([$isVer, $docId]);

                    if ($cDoc['user_id']) {
                        $notifType = ($newStatus === 'verified') ? 'success' : 'error';
                        $notifMsg = ($newStatus === 'verified') 
                            ? "Your company document '{$cDoc['title']}' has been verified by Compliance." 
                            : "Your company document '{$cDoc['title']}' was rejected: " . ($docRemarks ?: 'Does not meet regulatory standards.');
                        send_notification($cDoc['user_id'], "Company Document " . ucfirst($newStatus), $notifMsg, $notifType, 'founder/company.php');
                    }

                    log_audit($user['id'], 'VERIFY_COMPANY_DOC', 'company_documents', $docId, "Admin marked company doc as {$newStatus}. Remarks: {$docRemarks}");
                    set_flash('success', "Company document '{$cDoc['title']}' updated to " . ucfirst($newStatus) . ".");
                }
            } else {
                // Verification document
                $vStmt = $db->prepare("SELECT vd.*, u.name as user_name FROM verification_documents vd JOIN users u ON vd.user_id = u.id WHERE vd.id = ?");
                $vStmt->execute([$docId]);
                $vDoc = $vStmt->fetch();

                if ($vDoc) {
                    $db->prepare("UPDATE verification_documents SET status = ?, verified_at = IF(? = 'verified', NOW(), NULL) WHERE id = ?")
                       ->execute([$newStatus, $newStatus, $docId]);

                    $notifType = ($newStatus === 'verified') ? 'success' : 'error';
                    $notifMsg = ($newStatus === 'verified')
                        ? "Your KYC document '{$vDoc['document_type']}' has been approved and verified by Compliance Administration."
                        : "Your KYC document '{$vDoc['document_type']}' was rejected: " . ($docRemarks ?: 'Please re-upload a clear copy.');
                    send_notification($vDoc['user_id'], "KYC Document " . ucfirst($newStatus), $notifMsg, $notifType, 'founder/verification.php');

                    log_audit($user['id'], 'VERIFY_KYC_DOC', 'verification_documents', $docId, "Admin marked KYC doc as {$newStatus}. Remarks: {$docRemarks}");
                    set_flash('success', "KYC document '{$vDoc['document_type']}' updated to " . ucfirst($newStatus) . ".");
                }
            }

            header('Location: ' . url('admin/verification_queue.php?tab=' . urlencode($_POST['active_tab'] ?? 'documents')));
            exit;
        }
    }
}

// Handle Full Verification Application Decision POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['form_action'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $reqId = (int)($_POST['request_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');

        if ($reqId > 0 && in_array($decision, ['verified', 'rejected', 'additional_info'])) {
            $fReq = $db->prepare("SELECT vr.*, u.name as applicant_name, u.role as applicant_role FROM verification_requests vr JOIN users u ON vr.user_id = u.id WHERE vr.id = ?");
            $fReq->execute([$reqId]);
            $targetReq = $fReq->fetch();

            if ($targetReq) {
                $oldStatus = $targetReq['status'];
                $upd = $db->prepare("
                    UPDATE verification_requests 
                    SET status = ?, remarks = ?, verified_at = IF(? = 'verified', NOW(), verified_at)
                    WHERE id = ?
                ");
                $upd->execute([$decision, $remarks, $decision, $reqId]);

                // Insert into verification_logs audit trail
                $db->prepare("
                    INSERT INTO verification_logs (verification_request_id, actor_user_id, old_status, new_status, remarks, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ")->execute([$reqId, $user['id'], $oldStatus, $decision, $remarks]);

                $destUrl = ($targetReq['applicant_role'] === 'founder') ? 'founder/dashboard.php' : 'investor/discover.php';

                if ($decision === 'verified') {
                    // Update user verification
                    $db->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")->execute([$targetReq['user_id']]);

                    // Update all attached verification_documents to verified
                    $db->prepare("UPDATE verification_documents SET status = 'verified', verified_at = NOW() WHERE user_id = ? OR verification_request_id = ?")
                       ->execute([$targetReq['user_id'], $reqId]);

                    // If company associated, update company status and its documents
                    if ($targetReq['company_id']) {
                        $db->prepare("UPDATE companies SET verified_status = 'verified' WHERE id = ?")->execute([$targetReq['company_id']]);
                        $db->prepare("UPDATE company_documents SET is_verified = 1 WHERE company_id = ?")->execute([$targetReq['company_id']]);
                    }

                    // Send high-priority notification to user
                    send_notification(
                        $targetReq['user_id'], 
                        'KYC & Documents Approved!', 
                        "Congratulations! Your platform KYC credentials and corporate data have been verified by Compliance.", 
                        'success', 
                        $destUrl
                    );

                    log_audit($user['id'], 'APPROVE_VERIFICATION_REQUEST', 'verification_requests', $reqId, "Admin verified KYC for user #{$targetReq['user_id']}. Remarks: {$remarks}");
                    set_flash('success', "Application for {$targetReq['applicant_name']} successfully approved and verified!");
                } elseif ($decision === 'rejected') {
                    send_notification(
                        $targetReq['user_id'], 
                        'KYC Verification Rejected', 
                        "Your verification submission was rejected: " . ($remarks ?: 'Documents failed regulatory standards.'), 
                        'error', 
                        $destUrl
                    );
                    log_audit($user['id'], 'REJECT_VERIFICATION_REQUEST', 'verification_requests', $reqId, "Admin rejected KYC for user #{$targetReq['user_id']}. Remarks: {$remarks}");
                    set_flash('warning', "Application for {$targetReq['applicant_name']} was rejected.");
                } else {
                    send_notification(
                        $targetReq['user_id'], 
                        'Additional Information Required', 
                        "Compliance requires updates for your KYC application: " . ($remarks ?: 'Please re-submit required files.'), 
                        'warning', 
                        $destUrl
                    );
                    log_audit($user['id'], 'FLAG_VERIFICATION_REQUEST', 'verification_requests', $reqId, "Admin requested more info for user #{$targetReq['user_id']}. Remarks: {$remarks}");
                    set_flash('info', "Requested additional information from {$targetReq['applicant_name']}.");
                }
            }
            header('Location: ' . url('admin/verification_queue.php?tab=' . urlencode($_POST['active_tab'] ?? 'queue')));
            exit;
        }
    }
}

// Fetch requests & documents
$requests = [];
$allVerificationDocs = [];
$allCompanyDocs = [];
$stats = ['total_reqs' => 0, 'pending_reqs' => 0, 'total_docs' => 0, 'pending_docs' => 0];

if ($db) {
    // 1. Fetch Requests with applicant info and document counts
    $stmt = $db->query("
        SELECT vr.*, 
               u.name as applicant_name, u.email as applicant_email, u.phone as applicant_phone, u.role as applicant_role, u.city as applicant_city,
               c.name as company_name, c.cin_number, c.industry,
               (SELECT COUNT(*) FROM verification_documents vd WHERE vd.user_id = vr.user_id OR vd.verification_request_id = vr.id) as kyc_doc_count,
               (SELECT COUNT(*) FROM company_documents cd WHERE cd.company_id = vr.company_id) as comp_doc_count
        FROM verification_requests vr
        JOIN users u ON vr.user_id = u.id
        LEFT JOIN companies c ON vr.company_id = c.id
        ORDER BY FIELD(vr.status, 'pending', 'additional_info', 'verified', 'rejected'), vr.created_at DESC
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch All Verification Documents
    $vdStmt = $db->query("
        SELECT vd.*, 
               u.name as applicant_name, u.email as applicant_email, u.role as applicant_role,
               c.name as company_name, c.cin_number
        FROM verification_documents vd
        JOIN users u ON vd.user_id = u.id
        LEFT JOIN companies c ON vd.company_id = c.id
        ORDER BY FIELD(vd.status, 'pending', 'rejected', 'verified'), vd.created_at DESC
    ");
    $allVerificationDocs = $vdStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch All Company Documents
    $cdStmt = $db->query("
        SELECT cd.*, 
               c.name as company_name, c.cin_number,
               u.name as founder_name, u.email as founder_email, u.id as founder_user_id
        FROM company_documents cd
        JOIN companies c ON cd.company_id = c.id
        LEFT JOIN company_founders cf ON c.id = cf.company_id
        LEFT JOIN users u ON cf.user_id = u.id
        ORDER BY cd.is_verified ASC, cd.uploaded_at DESC
    ");
    $allCompanyDocs = $cdStmt->fetchAll(PDO::FETCH_ASSOC);

    // Compute stats
    $stats['total_reqs'] = count($requests);
    $stats['pending_reqs'] = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
    $stats['total_docs'] = count($allVerificationDocs) + count($allCompanyDocs);
    $stats['pending_docs'] = count(array_filter($allVerificationDocs, fn($d) => $d['status'] === 'pending')) + count(array_filter($allCompanyDocs, fn($d) => empty($d['is_verified'])));
}

// Group verification documents by user_id for fast modal inspection
$docsByUser = [];
foreach ($allVerificationDocs as $doc) {
    $docsByUser[$doc['user_id']][] = $doc;
}

// Group company documents by company_id
$docsByCompany = [];
foreach ($allCompanyDocs as $cdoc) {
    if (!empty($cdoc['company_id'])) {
        $docsByCompany[$cdoc['company_id']][] = $cdoc;
    }
}

$activeTab = $_GET['tab'] ?? 'queue';
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
        }
        .filter-bar-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 0.875rem 1.25rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        }
        .table-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }
        .tab-btn-clean.active {
            background-color: #4f46e5;
            color: #ffffff;
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 flex min-h-screen dark:bg-[#0b0f19] dark:text-slate-100 font-sans antialiased">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-7xl mx-auto" id="queue-main">

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

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200/80 dark:border-amber-800/80 flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-sm flex-shrink-0">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            KYC & Document Review
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            Verify applicant identity credentials, examine corporate attachments, and enforce AML/KYC compliance.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="switchTab('queue')" id="tab-btn-queue" class="tab-btn-clean px-3.5 py-2 text-xs font-semibold rounded-lg <?= $activeTab === 'queue' ? 'active' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300' ?> shadow-sm transition">
                        Applications (<?= count($requests) ?>)
                    </button>
                    <button onclick="switchTab('documents')" id="tab-btn-documents" class="tab-btn-clean px-3.5 py-2 text-xs font-semibold rounded-lg <?= $activeTab === 'documents' ? 'active' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300' ?> shadow-sm transition">
                        All Documents (<?= $stats['total_docs'] ?>)
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800 <?= $stats['pending_reqs'] > 0 ? 'ring-1 ring-amber-400/50 dark:ring-amber-500/30' : '' ?>">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending Reviews</span>
                        <div class="w-8 h-8 rounded-lg <?= $stats['pending_reqs'] > 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' ?> flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold <?= $stats['pending_reqs'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' ?> mt-2 flex items-center gap-2">
                        <?= number_format($stats['pending_reqs']) ?>
                        <?php if ($stats['pending_reqs'] > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 animate-pulse">Needs Action</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Awaiting verification</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Applications</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <i data-lucide="user-check" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-2"><?= number_format($stats['total_reqs']) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Founders & Angels</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Attached Documents</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="files" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-2"><?= number_format($stats['total_docs']) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Identity & Company files</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Unverified Files</span>
                        <div class="w-8 h-8 rounded-lg <?= $stats['pending_docs'] > 0 ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' ?> flex items-center justify-center">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold <?= $stats['pending_docs'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' ?> mt-2"><?= number_format($stats['pending_docs']) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pending review</div>
                </div>
            </div>

            <!-- TAB 1: VERIFICATION APPLICATIONS QUEUE -->
            <div id="tab-content-queue" class="<?= $activeTab === 'queue' ? '' : 'hidden' ?> space-y-4">
                
                <!-- Search & Filter bar for Applications -->
                <div class="filter-bar-clean dark:bg-slate-900 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="relative w-full sm:w-80">
                        <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="app-search" oninput="filterApplications()" 
                               placeholder="Search applicant name, email, company..." 
                               class="w-full pl-9 pr-3.5 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition">
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <select id="app-status-filter" onchange="filterApplications()" class="w-full sm:w-auto px-3 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="verified">Verified</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="table-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700/80 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                                    <th class="py-3.5 px-4">Applicant</th>
                                    <th class="py-3.5 px-4">Role & Entity</th>
                                    <th class="py-3.5 px-4">Attached Files</th>
                                    <th class="py-3.5 px-4">Provider Ref</th>
                                    <th class="py-3.5 px-4">Status</th>
                                    <th class="py-3.5 px-4">Submitted</th>
                                    <th class="py-3.5 px-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody id="app-tbody" class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php if (empty($requests)): ?>
                                    <tr>
                                        <td colspan="7" class="py-16 text-center text-slate-400 dark:text-slate-500">
                                            <i data-lucide="inbox" class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2"></i>
                                            <p class="font-medium text-sm">No verification applications found in the queue.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($requests as $r): 
                                        $userDocs = $docsByUser[$r['user_id']] ?? [];
                                        $compDocs = !empty($r['company_id']) ? ($docsByCompany[$r['company_id']] ?? []) : [];
                                        $totalAttached = count($userDocs) + count($compDocs);
                                        $payload = [
                                            'req' => $r,
                                            'userDocs' => $userDocs,
                                            'compDocs' => $compDocs
                                        ];
                                    ?>
                                        <tr class="app-row hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                                            data-status="<?= htmlspecialchars($r['status']) ?>"
                                            data-search="<?= strtolower(htmlspecialchars($r['applicant_name'] . ' ' . $r['applicant_email'] . ' ' . ($r['company_name'] ?? '') . ' ' . ($r['provider_ref_id'] ?? ''))) ?>">
                                            
                                            <td class="py-3.5 px-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center text-xs">
                                                        <?= strtoupper(substr($r['applicant_name'], 0, 2)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($r['applicant_name']) ?></div>
                                                        <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5"><?= htmlspecialchars($r['applicant_email']) ?></div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $r['applicant_role'] === 'founder' ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' ?>">
                                                    <?= ucfirst($r['applicant_role']) ?>
                                                </span>
                                                <?php if (!empty($r['company_name'])): ?>
                                                    <div class="text-slate-800 dark:text-slate-200 text-xs mt-1 font-medium"><?= htmlspecialchars($r['company_name']) ?></div>
                                                <?php endif; ?>
                                            </td>

                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <?php if ($totalAttached > 0): ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 font-bold text-[11px]">
                                                        <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                                                        <span><?= $totalAttached ?> File<?= $totalAttached > 1 ? 's' : '' ?></span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-slate-400 text-xs">No files</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <div class="font-mono text-xs text-slate-700 dark:text-slate-300"><?= htmlspecialchars($r['provider_ref_id'] ?? 'MANUAL_KYC') ?></div>
                                                <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($r['provider_name']) ?></div>
                                            </td>

                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <?php if ($r['status'] === 'verified'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                                        Verified
                                                    </span>
                                                <?php elseif ($r['status'] === 'rejected'): ?>
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

                                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400 text-xs">
                                                <?= date('d M Y', strtotime($r['created_at'])) ?>
                                            </td>

                                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                                <button onclick="openReviewModal(<?= htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') ?>)" 
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm transition">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    <span>Inspect</span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: ALL UPLOADED DOCUMENTS REPOSITORY -->
            <div id="tab-content-documents" class="<?= $activeTab === 'documents' ? '' : 'hidden' ?> space-y-4">
                
                <div class="table-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="folder-lock" class="w-4 h-4 text-indigo-600"></i>
                                <span>Platform Documents Repository (KYC & Diligence Room)</span>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Inspect uploaded credentials, download compliance certificates, and approve/reject individual files.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700/80 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                                    <th class="py-3.5 px-4">Document Title</th>
                                    <th class="py-3.5 px-4">Uploader / Company</th>
                                    <th class="py-3.5 px-4">Size & Date</th>
                                    <th class="py-3.5 px-4">Status</th>
                                    <th class="py-3.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php if (empty($allVerificationDocs) && empty($allCompanyDocs)): ?>
                                    <tr>
                                        <td colspan="5" class="py-16 text-center text-slate-400 dark:text-slate-500">No documents have been uploaded to the platform yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <!-- 1. Verification Documents -->
                                    <?php foreach ($allVerificationDocs as $doc): ?>
                                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                            <td class="py-3.5 px-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-100 dark:border-sky-800 flex items-center justify-center flex-shrink-0">
                                                        <i data-lucide="<?= str_contains(strtolower($doc['file_path']), '.pdf') ? 'file-text' : 'image' ?>" class="w-4 h-4"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-slate-900 dark:text-white text-xs"><?= htmlspecialchars($doc['document_type']) ?></div>
                                                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars(basename($doc['file_path'])) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <div class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($doc['applicant_name']) ?></div>
                                                <div class="text-[11px] text-slate-400"><?= ucfirst($doc['applicant_role']) ?></div>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <div class="text-slate-800 dark:text-slate-300"><?= htmlspecialchars($doc['file_size']) ?></div>
                                                <div class="text-[11px] text-slate-400 mt-0.5"><?= date('d M Y', strtotime($doc['created_at'])) ?></div>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <?php if ($doc['status'] === 'verified'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                        VERIFIED
                                                    </span>
                                                <?php elseif ($doc['status'] === 'rejected'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                                        REJECTED
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                        PENDING
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                                <div class="inline-flex items-center gap-1.5">
                                                    <a href="<?= url(ltrim($doc['file_path'], '/')) ?>" target="_blank" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                                        View
                                                    </a>
                                                    <?php if ($doc['status'] !== 'verified'): ?>
                                                        <form action="<?= url('admin/verification_queue.php') ?>" method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="form_action" value="update_doc_status">
                                                            <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                                            <input type="hidden" name="doc_scope" value="verification">
                                                            <input type="hidden" name="new_status" value="verified">
                                                            <input type="hidden" name="active_tab" value="documents">
                                                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition">
                                                                Approve
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- 2. Company Documents -->
                                    <?php foreach ($allCompanyDocs as $cdoc): ?>
                                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                            <td class="py-3.5 px-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800 flex items-center justify-center flex-shrink-0">
                                                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-slate-900 dark:text-white text-xs"><?= htmlspecialchars($cdoc['title']) ?></div>
                                                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($cdoc['document_type']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <div class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($cdoc['company_name']) ?></div>
                                                <div class="text-[11px] text-slate-400"><?= htmlspecialchars($cdoc['founder_name'] ?? 'Corporate Entity') ?></div>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <div class="text-slate-800 dark:text-slate-300"><?= htmlspecialchars($cdoc['file_size']) ?></div>
                                                <div class="text-[11px] text-slate-400 mt-0.5"><?= date('d M Y', strtotime($cdoc['uploaded_at'])) ?></div>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <?php if (!empty($cdoc['is_verified'])): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                        VERIFIED
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                        PENDING
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                                <div class="inline-flex items-center gap-1.5">
                                                    <a href="<?= url(ltrim($cdoc['file_path'], '/')) ?>" target="_blank" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                                        View
                                                    </a>
                                                    <?php if (empty($cdoc['is_verified'])): ?>
                                                        <form action="<?= url('admin/verification_queue.php') ?>" method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="form_action" value="update_doc_status">
                                                            <input type="hidden" name="doc_id" value="<?= $cdoc['id'] ?>">
                                                            <input type="hidden" name="doc_scope" value="company">
                                                            <input type="hidden" name="new_status" value="verified">
                                                            <input type="hidden" name="active_tab" value="documents">
                                                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition">
                                                                Approve
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
            </div>

            <!-- Inspection & Review Evaluation Modal -->
            <div id="review-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeReviewModal()"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200 dark:border-slate-800">
                        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/70 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-sm">
                                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Review KYC Application</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Examine applicant credentials, verify documents, and submit decision.</p>
                                </div>
                            </div>
                            <button type="button" onclick="closeReviewModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <div class="px-6 py-5 space-y-4 text-xs max-h-[75vh] overflow-y-auto">
                            <!-- Applicant Profile Card -->
                            <div id="modal-applicant-info" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 text-xs"></div>

                            <!-- Uploaded Documents Preview & Download Box -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block font-bold text-slate-800 dark:text-slate-200">
                                        Attached Documents (<span id="modal-doc-count">0</span>)
                                    </label>
                                    <span class="text-[11px] text-slate-400">Review all files before final verdict</span>
                                </div>

                                <div id="modal-docs-list" class="border border-slate-200 dark:border-slate-700 rounded-xl divide-y divide-slate-100 dark:divide-slate-800 max-h-56 overflow-y-auto bg-white dark:bg-slate-900">
                                    <!-- Populated dynamically -->
                                </div>
                            </div>

                            <!-- Decision Form -->
                            <form action="<?= url('admin/verification_queue.php') ?>" method="POST" class="space-y-4 pt-2">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="request_id" id="modal-req-id">

                                <div>
                                    <label class="block font-bold text-slate-800 dark:text-slate-200 mb-1.5 uppercase tracking-wider text-[11px]">Compliance Verdict</label>
                                    <select name="decision" id="modal-decision-select" required class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 rounded-xl text-slate-900 dark:text-white text-xs outline-none">
                                        <option value="verified">Approve & Verify (Grant Verified Badge, Unlock Platform Privileges)</option>
                                        <option value="additional_info">Request Additional Documents / Corrections</option>
                                        <option value="rejected">Reject Application</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-800 dark:text-slate-200 mb-1.5 uppercase tracking-wider text-[11px]">Compliance Remarks & Audit Notes</label>
                                    <textarea name="remarks" id="modal-remarks-input" rows="3" required placeholder="State regulatory review remarks, MCA verification notes, or reason for decision..."
                                              class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 rounded-xl text-slate-900 dark:text-white text-xs outline-none"></textarea>
                                </div>

                                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                                    <button type="button" onclick="closeReviewModal()" class="px-4 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" class="px-5 py-2 text-xs font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition flex items-center gap-1.5">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                        <span>Submit Verdict</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();

        function switchTab(tab) {
            document.getElementById('tab-content-queue').classList.toggle('hidden', tab !== 'queue');
            document.getElementById('tab-content-documents').classList.toggle('hidden', tab !== 'documents');

            const btnQueue = document.getElementById('tab-btn-queue');
            const btnDocs = document.getElementById('tab-btn-documents');

            if (tab === 'queue') {
                btnQueue.className = 'tab-btn-clean active px-3.5 py-2 text-xs font-semibold rounded-lg shadow-sm transition';
                btnDocs.className = 'tab-btn-clean px-3.5 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 shadow-sm transition';
            } else {
                btnDocs.className = 'tab-btn-clean active px-3.5 py-2 text-xs font-semibold rounded-lg shadow-sm transition';
                btnQueue.className = 'tab-btn-clean px-3.5 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 shadow-sm transition';
            }
            lucide.createIcons();
        }

        function filterApplications() {
            const query = (document.getElementById('app-search')?.value || '').toLowerCase().trim();
            const status = (document.getElementById('app-status-filter')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.app-row');

            rows.forEach(row => {
                const search = (row.getAttribute('data-search') || '').toLowerCase();
                const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();

                const matchQuery = !query || search.includes(query);
                const matchStatus = !status || rowStatus === status;

                row.style.display = (matchQuery && matchStatus) ? '' : 'none';
            });
        }

        const APP_URL = <?= json_encode(url('')) ?>;

        function openReviewModal(payload) {
            const req = payload.req;
            const userDocs = payload.userDocs || [];
            const compDocs = payload.compDocs || [];
            const totalDocs = userDocs.length + compDocs.length;

            document.getElementById('modal-req-id').value = req.id;
            document.getElementById('modal-doc-count').innerText = totalDocs;
            document.getElementById('modal-remarks-input').value = req.remarks || 'Identity credentials and submitted documentation verified.';
            
            if (req.status) {
                const sel = document.getElementById('modal-decision-select');
                if (['verified', 'rejected', 'additional_info'].includes(req.status)) {
                    sel.value = req.status;
                }
            }

            document.getElementById('modal-applicant-info').innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <div class="font-bold text-slate-900 dark:text-white text-xs">${escapeHtml(req.applicant_name)} <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 ml-1">${escapeHtml(req.applicant_role)}</span></div>
                        <div class="text-slate-500 text-[11px] mt-0.5">${escapeHtml(req.applicant_email)} • ${escapeHtml(req.applicant_phone || 'No phone')}</div>
                        <div class="text-slate-400 text-[11px]">Location: ${escapeHtml(req.applicant_city || 'India')}</div>
                    </div>
                    <div>
                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">Entity: ${escapeHtml(req.company_name || 'Individual Profile')}</div>
                        <div class="text-slate-500 text-[11px]">CIN: <span class="font-mono text-slate-700 dark:text-slate-300">${escapeHtml(req.cin_number || 'N/A')}</span></div>
                        <div class="text-indigo-600 dark:text-indigo-400 font-mono text-[10.5px] mt-0.5">Gateway Ref: ${escapeHtml(req.provider_ref_id || 'MANUAL_KYC')}</div>
                    </div>
                </div>
            `;

            const docsListEl = document.getElementById('modal-docs-list');
            if (totalDocs === 0) {
                docsListEl.innerHTML = `<div class="p-6 text-center text-xs text-slate-400">No digital documents uploaded by this applicant yet.</div>`;
            } else {
                let html = '';
                userDocs.forEach(d => {
                    const cleanPath = d.file_path.replace(/^\//, '');
                    html += `
                        <div class="p-3 flex items-center justify-between text-xs hover:bg-slate-50 dark:hover:bg-slate-800/60 transition">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white">${escapeHtml(d.document_type)}</div>
                                    <div class="text-[10px] text-slate-400">${escapeHtml(d.file_size)} • Uploaded ${d.created_at}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <a href="${escapeHtml(APP_URL + '/' + cleanPath)}" target="_blank" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50">
                                    View
                                </a>
                                <a href="${escapeHtml(APP_URL + '/download.php?id=' + d.id + '&type=verification')}" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                                    Download
                                </a>
                            </div>
                        </div>
                    `;
                });

                compDocs.forEach(cd => {
                    const cleanPath = cd.file_path.replace(/^\//, '');
                    html += `
                        <div class="p-3 flex items-center justify-between text-xs hover:bg-slate-50 dark:hover:bg-slate-800/60 transition">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white">${escapeHtml(cd.title)} (${escapeHtml(cd.document_type)})</div>
                                    <div class="text-[10px] text-slate-400">${escapeHtml(cd.file_size)} • Data Room</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <a href="${escapeHtml(APP_URL + '/' + cleanPath)}" target="_blank" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50">
                                    View
                                </a>
                                <a href="${escapeHtml(APP_URL + '/download.php?id=' + cd.id + '&type=company')}" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                                    Download
                                </a>
                            </div>
                        </div>
                    `;
                });

                docsListEl.innerHTML = html;
            }

            document.getElementById('review-modal').classList.remove('hidden');
            lucide.createIcons();
        }

        function closeReviewModal() {
            document.getElementById('review-modal').classList.add('hidden');
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>
</html>
