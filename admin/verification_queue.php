<?php
/**
 * Admin Module: Verification Queue & KYC Document Review
 * Clean White / Light Theme, Small Crisp Typography, Professional Compliance Workflow
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'KYC & DigiLocker Verification Queue';

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
                        'Compliance Administration has verified your identity and submitted documents. Your account is now fully verified with all platform features unlocked.', 
                        'success', 
                        $destUrl
                    );

                    log_audit($user['id'], 'APPROVE_VERIFICATION_REQUEST', 'verification_requests', $reqId, "Admin verified KYC & documents for {$targetReq['applicant_name']}. Remarks: {$remarks}");
                    set_flash('success', "Application for {$targetReq['applicant_name']} has been APPROVED & VERIFIED. Documents marked verified and notification dispatched.");

                } elseif ($decision === 'rejected') {
                    $db->prepare("UPDATE users SET is_verified = 0 WHERE id = ?")->execute([$targetReq['user_id']]);
                    $db->prepare("UPDATE verification_documents SET status = 'rejected' WHERE user_id = ? OR verification_request_id = ?")
                       ->execute([$targetReq['user_id'], $reqId]);

                    $reapplyUrl = ($targetReq['applicant_role'] === 'founder') ? 'founder/verification.php' : 'investor/verification.php';
                    send_notification(
                        $targetReq['user_id'], 
                        'KYC Verification Rejected', 
                        'Compliance review remarks: ' . ($remarks ?: 'Documents could not be authenticated. Please re-submit valid credentials.'), 
                        'error', 
                        $reapplyUrl
                    );

                    log_audit($user['id'], 'REJECT_VERIFICATION_REQUEST', 'verification_requests', $reqId, "Admin rejected request for {$targetReq['applicant_name']}. Reason: {$remarks}");
                    set_flash('success', "Application for {$targetReq['applicant_name']} marked REJECTED. Notification sent with remarks.");

                } elseif ($decision === 'additional_info') {
                    $reapplyUrl = ($targetReq['applicant_role'] === 'founder') ? 'founder/verification.php' : 'investor/verification.php';
                    send_notification(
                        $targetReq['user_id'], 
                        'Additional Compliance Documents Required', 
                        'Compliance notice: ' . ($remarks ?: 'Please upload updated identity and financial verification files.'), 
                        'info', 
                        $reapplyUrl
                    );

                    log_audit($user['id'], 'REQUEST_ADDITIONAL_INFO', 'verification_requests', $reqId, "Admin requested more info from {$targetReq['applicant_name']}. Remarks: {$remarks}");
                    set_flash('success', "Requested additional information from {$targetReq['applicant_name']}.");
                }

                header('Location: ' . url('admin/verification_queue.php'));
                exit;
            }
        }
    }
}

// Fetch Verification Requests
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

$activeTab = $_GET['tab'] ?? 'queue'; // 'queue' or 'documents'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Queue & Document Review • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="queue-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Header & Key Metrics -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">KYC & Document Review Center</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Inspect applicant identification documents, download files, and enforce compliance verification.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="switchTab('queue')" id="tab-btn-queue" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 <?= $activeTab === 'queue' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        <span>Verification Requests (<?= count($requests) ?>)</span>
                    </button>
                    <button onclick="switchTab('documents')" id="tab-btn-documents" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 <?= $activeTab === 'documents' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                        <i data-lucide="files" class="w-3.5 h-3.5"></i>
                        <span>All Uploaded Documents (<?= $stats['total_docs'] ?>)</span>
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pending KYC Queue</div>
                    <div class="text-xl font-extrabold text-amber-600 mt-1 flex items-center justify-between">
                        <span><?= $stats['pending_reqs'] ?></span>
                        <i data-lucide="clock" class="w-4 h-4 text-amber-400"></i>
                    </div>
                </div>
                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total KYC Requests</div>
                    <div class="text-xl font-extrabold text-slate-900 mt-1 flex items-center justify-between">
                        <span><?= $stats['total_reqs'] ?></span>
                        <i data-lucide="user-check" class="w-4 h-4 text-slate-400"></i>
                    </div>
                </div>
                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Documents Uploaded</div>
                    <div class="text-xl font-extrabold text-indigo-600 mt-1 flex items-center justify-between">
                        <span><?= $stats['total_docs'] ?></span>
                        <i data-lucide="file-text" class="w-4 h-4 text-indigo-400"></i>
                    </div>
                </div>
                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Docs Awaiting Review</div>
                    <div class="text-xl font-extrabold text-rose-600 mt-1 flex items-center justify-between">
                        <span><?= $stats['pending_docs'] ?></span>
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400"></i>
                    </div>
                </div>
            </div>

            <!-- TAB 1: VERIFICATION APPLICATIONS QUEUE -->
            <div id="tab-content-queue" class="<?= $activeTab === 'queue' ? '' : 'hidden' ?> space-y-4">
                <div class="card-clean rounded-2xl p-5 md:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="inbox" class="w-4 h-4 text-indigo-600"></i>
                            <span>Applicant KYC Applications</span>
                        </h2>
                        <span class="text-[11px] text-slate-400">Click 'Inspect & Review' to check uploaded files and approve</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider text-[10.5px]">
                                    <th class="pb-3 font-semibold">Applicant</th>
                                    <th class="pb-3 font-semibold">Role / Entity</th>
                                    <th class="pb-3 font-semibold">Attached Docs</th>
                                    <th class="pb-3 font-semibold">Gateway / Ref</th>
                                    <th class="pb-3 font-semibold">Current Status</th>
                                    <th class="pb-3 font-semibold">Submitted</th>
                                    <th class="pb-3 font-semibold text-right">Compliance Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($requests)): ?>
                                    <tr>
                                        <td colspan="7" class="py-8 text-center text-xs text-slate-400">No verification applications found in the queue.</td>
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
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="py-3.5">
                                                <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($r['applicant_name']) ?></div>
                                                <div class="text-[11px] text-slate-500"><?= htmlspecialchars($r['applicant_email']) ?> • <?= htmlspecialchars($r['applicant_phone']) ?></div>
                                            </td>
                                            <td class="py-3.5">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $r['applicant_role'] === 'founder' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
                                                    <?= ucfirst($r['applicant_role']) ?>
                                                </span>
                                                <?php if ($r['company_name']): ?>
                                                    <div class="text-slate-800 font-semibold text-xs mt-1"><?= htmlspecialchars($r['company_name']) ?></div>
                                                    <div class="text-[10px] font-mono text-slate-500"><?= htmlspecialchars($r['cin_number'] ?? '') ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5">
                                                <?php if ($totalAttached > 0): ?>
                                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold text-[10.5px]">
                                                        <i data-lucide="paperclip" class="w-3 h-3"></i>
                                                        <span><?= $totalAttached ?> File<?= $totalAttached > 1 ? 's' : '' ?></span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-slate-400 text-[11px]">No docs attached</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5">
                                                <div class="font-mono text-xs text-slate-700 font-medium"><?= htmlspecialchars($r['provider_ref_id'] ?? 'MANUAL_KYC') ?></div>
                                                <div class="text-[10.5px] text-slate-400"><?= htmlspecialchars($r['provider_name']) ?></div>
                                            </td>
                                            <td class="py-3.5">
                                                <?= render_status_badge($r['status']) ?>
                                            </td>
                                            <td class="py-3.5 text-slate-500 text-xs"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                                            <td class="py-3.5 text-right">
                                                <button onclick="openReviewModal(<?= htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') ?>)" class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm transition flex items-center space-x-1.5 ml-auto">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    <span>Inspect & Review</span>
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
                <div class="card-clean rounded-2xl p-5 md:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                                <i data-lucide="files" class="w-4 h-4 text-indigo-600"></i>
                                <span>All Uploaded Platform Documents (KYC & Data Room)</span>
                            </h2>
                            <p class="text-[11px] text-slate-500 mt-0.5">Directly download, inspect, and approve or reject any uploaded file.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider text-[10.5px]">
                                    <th class="pb-3 font-semibold">Document Title / Type</th>
                                    <th class="pb-3 font-semibold">Uploader / Entity</th>
                                    <th class="pb-3 font-semibold">File Size & Uploaded</th>
                                    <th class="pb-3 font-semibold">Status</th>
                                    <th class="pb-3 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($allVerificationDocs) && empty($allCompanyDocs)): ?>
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-xs text-slate-400">No documents have been uploaded to the platform yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <!-- 1. Verification Documents -->
                                    <?php foreach ($allVerificationDocs as $doc): ?>
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="py-3.5">
                                                <div class="flex items-center space-x-2.5">
                                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center flex-shrink-0">
                                                        <i data-lucide="<?= str_contains(strtolower($doc['file_path']), '.pdf') ? 'file-text' : 'image' ?>" class="w-4 h-4"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($doc['document_type']) ?></div>
                                                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars(basename($doc['file_path'])) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3.5">
                                                <div class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($doc['applicant_name']) ?></div>
                                                <div class="text-[10.5px] text-slate-500">
                                                    <span class="font-semibold text-slate-700"><?= ucfirst($doc['applicant_role']) ?></span>
                                                    <?= $doc['company_name'] ? ' • ' . htmlspecialchars($doc['company_name']) : '' ?>
                                                </div>
                                            </td>
                                            <td class="py-3.5">
                                                <div class="text-slate-800 font-medium"><?= htmlspecialchars($doc['file_size']) ?></div>
                                                <div class="text-[10.5px] text-slate-400"><?= date('d M Y, h:i A', strtotime($doc['created_at'])) ?></div>
                                            </td>
                                            <td class="py-3.5">
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $doc['status'] === 'verified' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($doc['status'] === 'rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') ?>">
                                                    <?= strtoupper($doc['status']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3.5 text-right">
                                                <div class="flex items-center justify-end space-x-1.5">
                                                    <!-- View Link -->
                                                    <a href="<?= url($doc['file_path']) ?>" target="_blank" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="View in New Tab">
                                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                        <span>View</span>
                                                    </a>

                                                    <!-- Download Link -->
                                                    <a href="<?= url('download.php?id=' . $doc['id'] . '&type=verification') ?>" class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="Download File">
                                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                        <span>Download</span>
                                                    </a>

                                                    <!-- Quick Approve Button -->
                                                    <?php if ($doc['status'] !== 'verified'): ?>
                                                        <form action="<?= url('admin/verification_queue.php') ?>" method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="form_action" value="update_doc_status">
                                                            <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                                            <input type="hidden" name="doc_scope" value="verification">
                                                            <input type="hidden" name="new_status" value="verified">
                                                            <input type="hidden" name="active_tab" value="documents">
                                                            <button type="submit" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition" title="Mark Verified">
                                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <!-- Quick Reject Button -->
                                                    <?php if ($doc['status'] !== 'rejected'): ?>
                                                        <form action="<?= url('admin/verification_queue.php') ?>" method="POST" class="inline" onsubmit="return confirm('Reject this document?');">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="form_action" value="update_doc_status">
                                                            <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                                            <input type="hidden" name="doc_scope" value="verification">
                                                            <input type="hidden" name="new_status" value="rejected">
                                                            <input type="hidden" name="doc_remarks" value="Document rejected during administrative review.">
                                                            <input type="hidden" name="active_tab" value="documents">
                                                            <button type="submit" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition" title="Reject Document">
                                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- 2. Company Data Room Documents -->
                                    <?php foreach ($allCompanyDocs as $cdoc): ?>
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="py-3.5">
                                                <div class="flex items-center space-x-2.5">
                                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center flex-shrink-0">
                                                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($cdoc['title']) ?></div>
                                                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($cdoc['document_type']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3.5">
                                                <div class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($cdoc['company_name']) ?></div>
                                                <div class="text-[10.5px] text-slate-500">Founder: <?= htmlspecialchars($cdoc['founder_name'] ?? 'N/A') ?></div>
                                            </td>
                                            <td class="py-3.5">
                                                <div class="text-slate-800 font-medium"><?= htmlspecialchars($cdoc['file_size']) ?></div>
                                                <div class="text-[10.5px] text-slate-400"><?= date('d M Y, h:i A', strtotime($cdoc['uploaded_at'])) ?></div>
                                            </td>
                                            <td class="py-3.5">
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $cdoc['is_verified'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                                                    <?= $cdoc['is_verified'] ? 'VERIFIED' : 'PENDING' ?>
                                                </span>
                                            </td>
                                            <td class="py-3.5 text-right">
                                                <div class="flex items-center justify-end space-x-1.5">
                                                    <!-- View Link -->
                                                    <a href="<?= url($cdoc['file_path']) ?>" target="_blank" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="View in New Tab">
                                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                        <span>View</span>
                                                    </a>

                                                    <!-- Download Link -->
                                                    <a href="<?= url('download.php?id=' . $cdoc['id'] . '&type=company') ?>" class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="Download File">
                                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                        <span>Download</span>
                                                    </a>

                                                    <!-- Quick Approve Button -->
                                                    <?php if (!$cdoc['is_verified']): ?>
                                                        <form action="<?= url('admin/verification_queue.php') ?>" method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="form_action" value="update_doc_status">
                                                            <input type="hidden" name="doc_id" value="<?= $cdoc['id'] ?>">
                                                            <input type="hidden" name="doc_scope" value="company">
                                                            <input type="hidden" name="new_status" value="verified">
                                                            <input type="hidden" name="active_tab" value="documents">
                                                            <button type="submit" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition" title="Mark Verified">
                                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
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

            <!-- Comprehensive Inspection & Review Evaluation Modal -->
            <div id="review-modal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
                <div class="bg-white border border-slate-200 max-w-2xl w-full rounded-2xl p-6 shadow-2xl relative my-8">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">KYC & Compliance Evaluation</h3>
                                <p class="text-[11px] text-slate-500">Examine applicant credentials, download attachments, and issue compliance verdict.</p>
                            </div>
                        </div>
                        <button onclick="document.getElementById('review-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Applicant Profile Card -->
                    <div id="modal-applicant-info" class="mb-5 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs"></div>

                    <!-- Uploaded Documents Preview & Download Box -->
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block font-bold text-slate-800 text-[11px] uppercase tracking-wider">
                                Attached Compliance Documents (<span id="modal-doc-count">0</span>)
                            </label>
                            <span class="text-[10px] text-slate-400">Download or open files before approving</span>
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
                            <label class="block font-semibold text-slate-700 mb-1 text-[11px] uppercase tracking-wider">Compliance Verdict</label>
                            <select name="decision" id="modal-decision-select" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-xl text-slate-900 text-xs outline-none">
                                <option value="verified">Approve & Verify (Grant Verified Badge, Unlock Platform Privileges)</option>
                                <option value="additional_info">Request Additional Documents / Corrections</option>
                                <option value="rejected">Reject Application</option>
                            </select>
                            <div class="mt-1 text-[10.5px] text-slate-500">
                                Approving this application will mark all submitted documents as verified, unlock platform features, and dispatch an instant user notification.
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1 text-[11px] uppercase tracking-wider">Compliance Remarks & Audit Notes</label>
                            <textarea name="remarks" id="modal-remarks-input" rows="3" required placeholder="State regulatory review remarks, MCA verification notes, or reason for decision..."
                                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-xl text-slate-900 text-xs outline-none"></textarea>
                        </div>

                        <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                            <button type="button" onclick="document.getElementById('review-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center space-x-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Submit Compliance Verdict</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#queue-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });

        function switchTab(tab) {
            document.getElementById('tab-content-queue').classList.toggle('hidden', tab !== 'queue');
            document.getElementById('tab-content-documents').classList.toggle('hidden', tab !== 'documents');

            const btnQueue = document.getElementById('tab-btn-queue');
            const btnDocs = document.getElementById('tab-btn-documents');

            if (tab === 'queue') {
                btnQueue.className = "px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 bg-slate-900 text-white shadow-sm";
                btnDocs.className = "px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50";
            } else {
                btnDocs.className = "px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 bg-slate-900 text-white shadow-sm";
                btnQueue.className = "px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50";
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
            document.getElementById('modal-remarks-input').value = req.remarks || 'Identity credentials, PAN, and corporate documents inspected and confirmed.';
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
                        <div class="font-bold text-slate-900 text-xs">${escapeHtml(req.applicant_name)} <span class="px-2 py-0.5 rounded text-[10px] bg-slate-200 text-slate-700 uppercase font-semibold">${escapeHtml(req.applicant_role)}</span></div>
                        <div class="text-slate-500 text-[11px] mt-0.5">${escapeHtml(req.applicant_email)} • ${escapeHtml(req.applicant_phone || 'No phone')}</div>
                        <div class="text-slate-500 text-[11px]">Location: ${escapeHtml(req.applicant_city || 'India')}</div>
                    </div>
                    <div>
                        <div class="font-bold text-slate-800 text-xs">Entity: ${escapeHtml(req.company_name || 'Individual Profile')}</div>
                        <div class="text-slate-500 text-[11px]">CIN: <span class="font-mono text-slate-700">${escapeHtml(req.cin_number || 'N/A')}</span></div>
                        <div class="text-indigo-600 font-mono text-[10.5px] mt-0.5">Gateway Ref: ${escapeHtml(req.provider_ref_id || 'MANUAL_KYC')}</div>
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

                // User KYC Documents
                userDocs.forEach(d => {
                    const statusClass = d.status === 'verified' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (d.status === 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200');
                    const cleanPath = d.file_path.replace(/^\//, '');
                    html += `
                        <div class="p-3 flex items-center justify-between text-xs hover:bg-slate-50/80 transition">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900">${escapeHtml(d.document_type)}</div>
                                    <div class="text-[10px] text-slate-400">${escapeHtml(d.file_size)} • Uploaded ${d.created_at}</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border ${statusClass}">${d.status.toUpperCase()}</span>
                                <a href="${escapeHtml(APP_URL + '/' + cleanPath)}" target="_blank" class="px-2 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1 transition shadow-sm">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>View</span>
                                </a>
                                <a href="${escapeHtml(APP_URL + '/download.php?id=' + d.id + '&type=verification')}" class="px-2 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center space-x-1 transition shadow-sm">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    <span>Download</span>
                                </a>
                            </div>
                        </div>
                    `;
                });

                // Company Data Room Documents
                compDocs.forEach(cd => {
                    const statusClass = cd.is_verified ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200';
                    const statusText = cd.is_verified ? 'VERIFIED' : 'PENDING';
                    const cleanPath = cd.file_path.replace(/^\//, '');
                    html += `
                        <div class="p-3 flex items-center justify-between text-xs hover:bg-slate-50/80 transition">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900">${escapeHtml(cd.title)} (${escapeHtml(cd.document_type)})</div>
                                    <div class="text-[10px] text-slate-400">${escapeHtml(cd.file_size)} • Data Room</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border ${statusClass}">${statusText}</span>
                                <a href="${escapeHtml(APP_URL + '/' + cleanPath)}" target="_blank" class="px-2 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1 transition shadow-sm">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>View</span>
                                </a>
                                <a href="${escapeHtml(APP_URL + '/download.php?id=' + cd.id + '&type=company')}" class="px-2 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center space-x-1 transition shadow-sm">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
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
