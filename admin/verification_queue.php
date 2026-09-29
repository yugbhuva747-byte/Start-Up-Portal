<?php
/**
 * Admin Module: Verification Queue & KYC Document Review
 * Clean, Minimalist Compliance & Document Review Desk
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
    $requests = $stmt->fetchAll();

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
    $allVerificationDocs = $vdStmt->fetchAll();

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
    $allCompanyDocs = $cdStmt->fetchAll();

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
    <title>Verification Queue • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F8FAFC] text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl w-full mx-auto" id="queue-main">
            
            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="admin-page-icon">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            KYC & Document Review
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">Review applicant identity credentials, verify corporate attachments, and manage compliance.</p>
                    </div>
                </div>
                <div class="admin-filter-bar">
                    <button onclick="switchTab('queue')" id="tab-btn-queue" class="admin-filter-pill <?= $activeTab === 'queue' ? 'active' : '' ?>">
                        Applications (<?= count($requests) ?>)
                    </button>
                    <button onclick="switchTab('documents')" id="tab-btn-documents" class="admin-filter-pill <?= $activeTab === 'documents' ? 'active' : '' ?>">
                        All Documents (<?= $stats['total_docs'] ?>)
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Pending Reviews</span>
                        <div class="w-8 h-8 rounded-lg <?= $stats['pending_reqs'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-400' ?> flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value <?= $stats['pending_reqs'] > 0 ? 'stat-value-amber' : '' ?>"><?= $stats['pending_reqs'] ?></div>
                    <div class="admin-stat-sub">Awaiting verification</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Total Applications</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i data-lucide="user-check" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-sky"><?= $stats['total_reqs'] ?></div>
                    <div class="admin-stat-sub">Founders & Angels</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Total Files</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-indigo"><?= $stats['total_docs'] ?></div>
                    <div class="admin-stat-sub">Uploaded attachments</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Docs Needing Action</span>
                        <div class="w-8 h-8 rounded-lg <?= $stats['pending_docs'] > 0 ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-400' ?> flex items-center justify-center">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value <?= $stats['pending_docs'] > 0 ? 'stat-value-rose' : '' ?>"><?= $stats['pending_docs'] ?></div>
                    <div class="admin-stat-sub">Unverified files</div>
                </div>
            </div>

            <!-- TAB 1: VERIFICATION APPLICATIONS QUEUE -->
            <div id="tab-content-queue" class="<?= $activeTab === 'queue' ? '' : 'hidden' ?> space-y-4">
                <div class="admin-table-container">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Role / Entity</th>
                                    <th>Attached Files</th>
                                    <th>Provider Ref</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                    <tr>
                                        <td colspan="7" class="py-12 text-center text-slate-400">No verification applications found in the queue.</td>
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
                                        <tr>
                                            <td>
                                                <div class="font-semibold text-slate-900 text-xs"><?= htmlspecialchars($r['applicant_name']) ?></div>
                                                <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($r['applicant_email']) ?></div>
                                            </td>
                                            <td>
                                                <span class="admin-badge <?= $r['applicant_role'] === 'founder' ? 'admin-badge-primary' : 'admin-badge-success' ?> text-[10px]">
                                                    <?= ucfirst($r['applicant_role']) ?>
                                                </span>
                                                <?php if ($r['company_name']): ?>
                                                    <div class="text-slate-800 text-xs mt-1 font-medium"><?= htmlspecialchars($r['company_name']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($totalAttached > 0): ?>
                                                    <span class="admin-badge admin-badge-primary">
                                                        <i data-lucide="paperclip" class="w-3 h-3"></i>
                                                        <span><?= $totalAttached ?> File<?= $totalAttached > 1 ? 's' : '' ?></span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-slate-400 text-xs">No files</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="font-mono text-xs text-slate-700"><?= htmlspecialchars($r['provider_ref_id'] ?? 'MANUAL_KYC') ?></div>
                                                <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($r['provider_name']) ?></div>
                                            </td>
                                            <td>
                                                <?= render_status_badge($r['status']) ?>
                                            </td>
                                            <td class="text-slate-500 text-xs">
                                                <?= date('d M Y', strtotime($r['created_at'])) ?>
                                            </td>
                                            <td class="text-right">
                                                <button onclick="openReviewModal(<?= htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') ?>)" class="admin-btn-secondary text-[11px] py-1 px-2.5 ml-auto">
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
                <div class="admin-table-container">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Document Title</th>
                                    <th>Uploader / Company</th>
                                    <th>Size & Date</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allVerificationDocs) && empty($allCompanyDocs)): ?>
                                    <tr>
                                        <td colspan="5" class="py-12 text-center text-slate-400">No documents have been uploaded to the platform yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <!-- 1. Verification Documents -->
                                    <?php foreach ($allVerificationDocs as $doc): ?>
                                        <tr>
                                            <td>
                                                <div class="flex items-center space-x-2.5">
                                                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                                                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-semibold text-slate-900 text-xs"><?= htmlspecialchars($doc['document_type']) ?></div>
                                                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars(basename($doc['file_path'])) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="font-semibold text-slate-800 text-xs"><?= htmlspecialchars($doc['applicant_name']) ?></div>
                                                <div class="text-[11px] text-slate-400"><?= ucfirst($doc['applicant_role']) ?></div>
                                            </td>
                                            <td>
                                                <div class="text-slate-800 text-xs"><?= htmlspecialchars($doc['file_size']) ?></div>
                                                <div class="text-[11px] text-slate-400 mt-0.5"><?= date('d M Y', strtotime($doc['created_at'])) ?></div>
                                            </td>
                                            <td>
                                                <span class="admin-badge <?= $doc['status'] === 'verified' ? 'admin-badge-success' : ($doc['status'] === 'rejected' ? 'admin-badge-danger' : 'admin-badge-warning') ?>">
                                                    <span class="admin-badge-dot"></span>
                                                    <span><?= ucfirst($doc['status']) ?></span>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <div class="flex items-center justify-end space-x-1.5">
                                                    <a href="<?= url($doc['file_path']) ?>" target="_blank" class="admin-btn-ghost p-1" title="View in New Tab">
                                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    </a>
                                                    <a href="<?= url('download.php?id=' . $doc['id'] . '&type=verification') ?>" class="admin-btn-ghost p-1" title="Download File">
                                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                    </a>
                                                    <?php if ($doc['status'] !== 'verified'): ?>
                                                        <form action="<?= url('admin/verification_queue.php') ?>" method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="form_action" value="update_doc_status">
                                                            <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                                            <input type="hidden" name="doc_scope" value="verification">
                                                            <input type="hidden" name="new_status" value="verified">
                                                            <input type="hidden" name="active_tab" value="documents">
                                                            <button type="submit" class="admin-btn-secondary text-[11px] py-1 px-2 text-emerald-700 hover:text-emerald-800" title="Mark Verified">
                                                                Approve
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- 2. Company Data Room Documents -->
                                    <?php foreach ($allCompanyDocs as $cdoc): ?>
                                        <tr>
                                            <td>
                                                <div class="flex items-center space-x-2.5">
                                                    <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center flex-shrink-0">
                                                        <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-semibold text-slate-900 text-xs"><?= htmlspecialchars($cdoc['title']) ?></div>
                                                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($cdoc['document_type']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="font-semibold text-slate-800 text-xs"><?= htmlspecialchars($cdoc['company_name']) ?></div>
                                                <div class="text-[11px] text-slate-400">Founder: <?= htmlspecialchars($cdoc['founder_name'] ?? 'N/A') ?></div>
                                            </td>
                                            <td>
                                                <div class="text-slate-800 text-xs"><?= htmlspecialchars($cdoc['file_size']) ?></div>
                                                <div class="text-[11px] text-slate-400 mt-0.5"><?= date('d M Y', strtotime($cdoc['uploaded_at'])) ?></div>
                                            </td>
                                            <td>
                                                <span class="admin-badge <?= $cdoc['is_verified'] ? 'admin-badge-success' : 'admin-badge-warning' ?>">
                                                    <span class="admin-badge-dot"></span>
                                                    <span><?= $cdoc['is_verified'] ? 'Verified' : 'Pending' ?></span>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <div class="flex items-center justify-end space-x-1.5">
                                                    <a href="<?= url($cdoc['file_path']) ?>" target="_blank" class="admin-btn-ghost p-1" title="View in New Tab">
                                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    </a>
                                                    <a href="<?= url('download.php?id=' . $cdoc['id'] . '&type=company') ?>" class="admin-btn-ghost p-1" title="Download File">
                                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                    </a>
                                                    <?php if (!$cdoc['is_verified']): ?>
                                                        <form action="<?= url('admin/verification_queue.php') ?>" method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="form_action" value="update_doc_status">
                                                            <input type="hidden" name="doc_id" value="<?= $cdoc['id'] ?>">
                                                            <input type="hidden" name="doc_scope" value="company">
                                                            <input type="hidden" name="new_status" value="verified">
                                                            <input type="hidden" name="active_tab" value="documents">
                                                            <button type="submit" class="admin-btn-secondary text-[11px] py-1 px-2 text-emerald-700 hover:text-emerald-800" title="Mark Verified">
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
            <div id="review-modal" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto">
                <div class="bg-white border border-slate-200 max-w-2xl w-full rounded-2xl p-6 shadow-2xl relative my-8">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center font-bold">
                                <i data-lucide="shield-check" class="w-4 h-4 text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Review KYC Application</h3>
                                <p class="text-[11px] text-slate-400">Examine applicant credentials, verify documents, and submit decision.</p>
                            </div>
                        </div>
                        <button onclick="document.getElementById('review-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Applicant Profile Card -->
                    <div id="modal-applicant-info" class="mb-4 p-4 rounded-xl bg-slate-50 border border-slate-200/80 text-xs"></div>

                    <!-- Uploaded Documents Preview & Download Box -->
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block font-semibold text-slate-700 text-xs">
                                Attached Documents (<span id="modal-doc-count">0</span>)
                            </label>
                            <span class="text-[11px] text-slate-400">Review files before deciding</span>
                        </div>

                        <div id="modal-docs-list" class="border border-slate-200 rounded-xl divide-y divide-slate-100 max-h-56 overflow-y-auto bg-white p-1">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- Decision Form -->
                    <form action="<?= url('admin/verification_queue.php') ?>" method="POST" class="space-y-4 text-xs">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="request_id" id="modal-req-id">

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1 text-xs">Compliance Decision</label>
                            <select name="decision" id="modal-decision-select" required class="admin-input w-full">
                                <option value="verified">Approve & Grant Verified Status</option>
                                <option value="additional_info">Request Additional Documents / Corrections</option>
                                <option value="rejected">Reject Application</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1 text-xs">Remarks / Notes</label>
                            <textarea name="remarks" id="modal-remarks-input" rows="3" required placeholder="State review remarks or feedback for applicant..."
                                      class="admin-input w-full"></textarea>
                        </div>

                        <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                            <button type="button" onclick="document.getElementById('review-modal').classList.add('hidden')" class="admin-btn-secondary">
                                Cancel
                            </button>
                            <button type="submit" class="admin-btn-primary">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Submit Verdict</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#queue-main", { duration: 0.3, y: 8, opacity: 0, ease: "power2.out" });

        function switchTab(tab) {
            document.getElementById('tab-content-queue').classList.toggle('hidden', tab !== 'queue');
            document.getElementById('tab-content-documents').classList.toggle('hidden', tab !== 'documents');

            const btnQueue = document.getElementById('tab-btn-queue');
            const btnDocs = document.getElementById('tab-btn-documents');

            if (tab === 'queue') {
                btnQueue.classList.add('active');
                btnDocs.classList.remove('active');
            } else {
                btnDocs.classList.add('active');
                btnQueue.classList.remove('active');
            }
            lucide.createIcons();
        }

        const APP_URL = <?= json_encode(url('')) ?>;

        function openReviewModal(payload) {
            const req = payload.req;
            const userDocs = payload.userDocs || [];
            const compDocs = payload.compDocs || [];
            const totalDocs = userDocs.length + compDocs.length;

            document.getElementById('modal-req-id').value = req.id;
            document.getElementById('modal-doc-count').innerText = totalDocs;

            // Pre-fill remarks if existing
            document.getElementById('modal-remarks-input').value = req.remarks || 'Identity credentials and submitted documentation verified.';
            if (req.status) {
                const sel = document.getElementById('modal-decision-select');
                if (['verified', 'rejected', 'additional_info'].includes(req.status)) {
                    sel.value = req.status;
                }
            }

            // Render applicant details
            document.getElementById('modal-applicant-info').innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <div class="font-semibold text-slate-900 text-xs">${escapeHtml(req.applicant_name)} <span class="admin-badge admin-badge-neutral text-[10px] ml-1">${escapeHtml(req.applicant_role)}</span></div>
                        <div class="text-slate-500 text-[11px] mt-0.5">${escapeHtml(req.applicant_email)} • ${escapeHtml(req.applicant_phone || 'No phone')}</div>
                        <div class="text-slate-400 text-[11px]">Location: ${escapeHtml(req.applicant_city || 'India')}</div>
                    </div>
                    <div>
                        <div class="font-semibold text-slate-800 text-xs">Entity: ${escapeHtml(req.company_name || 'Individual Profile')}</div>
                        <div class="text-slate-400 text-[11px] mt-0.5">CIN: <span class="font-mono text-slate-700">${escapeHtml(req.cin_number || 'N/A')}</span></div>
                        <div class="text-indigo-600 font-mono text-[11px] mt-0.5">Ref: ${escapeHtml(req.provider_ref_id || 'MANUAL_KYC')}</div>
                    </div>
                </div>
            `;

            // Render documents list with View & Download
            const docsListEl = document.getElementById('modal-docs-list');
            if (totalDocs === 0) {
                docsListEl.innerHTML = `
                    <div class="p-6 text-center text-xs text-slate-400">
                        No digital documents uploaded by this applicant yet.
                    </div>
                `;
            } else {
                let html = '';

                userDocs.forEach(d => {
                    const statusClass = d.status === 'verified' ? 'admin-badge-success' : (d.status === 'rejected' ? 'admin-badge-danger' : 'admin-badge-warning');
                    const cleanPath = d.file_path.replace(/^\//, '');
                    html += `
                        <div class="p-3 flex items-center justify-between text-xs hover:bg-slate-50/70 transition">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">${escapeHtml(d.document_type)}</div>
                                    <div class="text-[10px] text-slate-400">${escapeHtml(d.file_size)} • Uploaded ${d.created_at}</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <span class="admin-badge ${statusClass} text-[10px]">${d.status.toUpperCase()}</span>
                                <a href="${escapeHtml(APP_URL + '/' + cleanPath)}" target="_blank" class="admin-btn-secondary text-[11px] py-1 px-2">
                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                    <span>View</span>
                                </a>
                                <a href="${escapeHtml(APP_URL + '/download.php?id=' + d.id + '&type=verification')}" class="admin-btn-secondary text-[11px] py-1 px-2">
                                    <i data-lucide="download" class="w-3 h-3"></i>
                                    <span>Download</span>
                                </a>
                            </div>
                        </div>
                    `;
                });

                compDocs.forEach(cd => {
                    const statusClass = cd.is_verified ? 'admin-badge-success' : 'admin-badge-warning';
                    const statusText = cd.is_verified ? 'VERIFIED' : 'PENDING';
                    const cleanPath = cd.file_path.replace(/^\//, '');
                    html += `
                        <div class="p-3 flex items-center justify-between text-xs hover:bg-slate-50/70 transition">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
                                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">${escapeHtml(cd.title)} (${escapeHtml(cd.document_type)})</div>
                                    <div class="text-[10px] text-slate-400">${escapeHtml(cd.file_size)} • Data Room</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <span class="admin-badge ${statusClass} text-[10px]">${statusText}</span>
                                <a href="${escapeHtml(APP_URL + '/' + cleanPath)}" target="_blank" class="admin-btn-secondary text-[11px] py-1 px-2">
                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                    <span>View</span>
                                </a>
                                <a href="${escapeHtml(APP_URL + '/download.php?id=' + cd.id + '&type=company')}" class="admin-btn-secondary text-[11px] py-1 px-2">
                                    <i data-lucide="download" class="w-3 h-3"></i>
                                    <span>Download</span>
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

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>
</html>
