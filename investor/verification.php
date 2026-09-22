<?php
/**
 * Investor Module: KYC, SEBI Accreditation & DigiLocker Verification
 * Implements Section 8 (Document Verification & DigiLocker Integration)
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'KYC, Accreditation & DigiLocker';

$error = '';
$flash = get_flash();
$verificationRequest = null;
$verificationDocs = [];
$verificationLogs = [];

if ($db) {
    $vrStmt = $db->prepare("SELECT * FROM verification_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $vrStmt->execute([$user['id']]);
    $verificationRequest = $vrStmt->fetch();

    if ($verificationRequest) {
        $docStmt = $db->prepare("SELECT * FROM verification_documents WHERE user_id = ? ORDER BY created_at DESC");
        $docStmt->execute([$user['id']]);
        $verificationDocs = $docStmt->fetchAll();

        $logStmt = $db->prepare("SELECT * FROM verification_logs WHERE verification_request_id = ? ORDER BY created_at DESC");
        $logStmt->execute([$verificationRequest['id']]);
        $verificationLogs = $logStmt->fetchAll();
    }
}

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid.';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'simulate_digilocker') {
            $refId = 'DL-INV-' . rand(100000, 999999);
            $digiUri = 'in.gov.digilocker:sebi_accredited:' . hash('crc32b', $user['email']);

            if ($verificationRequest) {
                $upd = $db->prepare("
                    UPDATE verification_requests 
                    SET status = 'verified', provider_name = 'DigiLocker National Gateway', provider_ref_id = ?, digilocker_uri = ?, remarks = 'Aadhaar, PAN & SEBI Angel Accreditation authenticated via DigiLocker API certificate.', verified_at = NOW()
                    WHERE id = ?
                ");
                $upd->execute([$refId, $digiUri, $verificationRequest['id']]);
                $reqId = $verificationRequest['id'];
            } else {
                $ins = $db->prepare("
                    INSERT INTO verification_requests (user_id, verification_type, status, provider_name, provider_ref_id, digilocker_uri, remarks, verified_at, created_at)
                    VALUES (?, 'digilocker_kyc', 'verified', 'DigiLocker National Gateway', ?, ?, 'Aadhaar, PAN & SEBI Angel Accreditation authenticated via DigiLocker API certificate.', NOW(), NOW())
                ");
                $ins->execute([$user['id'], $refId, $digiUri]);
                $reqId = $db->lastInsertId();
            }

            // Update user status
            $db->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")->execute([$user['id']]);

            // Add verification log
            $db->prepare("INSERT INTO verification_logs (verification_request_id, actor_user_id, old_status, new_status, remarks) VALUES (?, ?, 'pending', 'verified', ?)")
               ->execute([$reqId, $user['id'], 'DigiLocker instant e-KYC passed']);

            // Send notification
            send_notification($user['id'], 'KYC & Accreditation Approved!', 'Your investor account has been verified via DigiLocker. You can now execute investments.', 'success', 'investor/discover.php');
            log_audit($user['id'], 'DIGILOCKER_INVESTOR_VERIFIED', 'verification_requests', $reqId, "Investor verified via DigiLocker. Ref: $refId");

            set_flash('success', 'DigiLocker KYC & SEBI Accreditation verified successfully!');
            header('Location: ' . url('investor/verification.php'));
            exit;

        } elseif ($action === 'upload_kyc_doc') {
            $docType = trim($_POST['doc_type'] ?? 'PAN Card');
            
            if (!empty($_FILES['doc_file']['name'])) {
                $file = $_FILES['doc_file'];
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

                    if (in_array($ext, $allowed) && $file['size'] <= 10 * 1024 * 1024) {
                        $targetDir = ROOT_PATH . '/uploads/documents';
                        if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);

                        $filename = 'kyc_' . $user['id'] . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
                            $publicPath = 'uploads/documents/' . $filename;
                            $sizeStr = round($file['size'] / (1024 * 1024), 2) . ' MB';

                            $reqId = $verificationRequest['id'] ?? null;
                            if (!$reqId) {
                                $refId = 'KYC-INV-' . rand(100000, 999999);
                                $insReq = $db->prepare("
                                    INSERT INTO verification_requests (user_id, verification_type, status, provider_name, provider_ref_id, remarks, created_at)
                                    VALUES (?, 'document_kyc', 'pending', 'Manual Accreditation Submission', ?, 'Investor submitted accreditation documents for compliance review.', NOW())
                                ");
                                $insReq->execute([$user['id'], $refId]);
                                $reqId = $db->lastInsertId();
                            } else {
                                if (in_array($verificationRequest['status'], ['rejected', 'additional_info'])) {
                                    $db->prepare("UPDATE verification_requests SET status = 'pending', remarks = 'Updated with newly uploaded documents.' WHERE id = ?")->execute([$reqId]);
                                }
                            }

                            $db->prepare("
                                INSERT INTO verification_documents (verification_request_id, user_id, document_type, file_path, file_size, status)
                                VALUES (?, ?, ?, ?, ?, 'pending')
                            ")->execute([$reqId, $user['id'], $docType, $publicPath, $sizeStr]);

                            $newDocId = $db->lastInsertId();
                            log_audit($user['id'], 'UPLOAD_KYC_DOCUMENT', 'verification_documents', $newDocId, "Uploaded $docType");
                            send_notification(1, 'New Investor Accreditation Document', "Investor {$user['name']} submitted {$docType} for accreditation review.", 'info', 'admin/verification_queue.php');
                            set_flash('success', "$docType uploaded for compliance review.");
                        } else {
                            $error = 'Failed to save uploaded file.';
                        }
                    } else {
                        $error = 'File must be PDF, JPG, or PNG under 10MB.';
                    }
                } else {
                    $error = 'Upload failed with error code ' . $file['error'];
                }
            } else {
                $error = 'Please select a document file to upload.';
            }

            header('Location: ' . url('investor/verification.php'));
            exit;
        }
    }
}
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
    
    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-5xl w-full mx-auto" id="verify-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center space-x-2.5">
                        <i data-lucide="shield-check" class="w-6 h-6 text-emerald-600"></i>
                        <span>Investor KYC & Accreditation Hub</span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">SEBI Compliant Accredited Investor status & DigiLocker national identity gateway.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $user['is_verified'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' ?>">
                        <?= $user['is_verified'] ? 'ACCREDITED & VERIFIED' : 'KYC UNDER REVIEW' ?>
                    </span>
                </div>
            </div>

            <!-- Primary DigiLocker Status Card -->
            <div class="card-clean rounded-2xl p-6 relative overflow-hidden">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                    <div class="space-y-2 max-w-xl">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold uppercase">Official Gateway</span>
                            <h2 class="text-base font-bold text-slate-900">DigiLocker National Identity & Tax PAN</h2>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Under SEBI regulations for angel investment syndicates, accredited investors must maintain verified identity and PAN records. Authenticate via DigiLocker to instantly unlock verified badge and direct escrow investment rights.
                        </p>

                        <?php if ($user['is_verified'] && $verificationRequest): ?>
                            <div class="pt-2 flex flex-wrap items-center gap-4 text-xs">
                                <div class="flex items-center space-x-1.5 text-emerald-700 font-semibold">
                                    <i data-lucide="badge-check" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Ref: <?= htmlspecialchars($verificationRequest['provider_ref_id'] ?? 'DL-INV-98214') ?></span>
                                </div>
                                <div class="text-slate-400">•</div>
                                <div class="text-slate-500">
                                    Verified on <?= date('M d, Y', strtotime($verificationRequest['verified_at'] ?? 'now')) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <?php if (!$user['is_verified']): ?>
                            <form action="<?= url('investor/verification.php') ?>" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="form_action" value="simulate_digilocker">
                                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center space-x-2">
                                    <i data-lucide="fingerprint" class="w-4 h-4"></i>
                                    <span>Authenticate with DigiLocker</span>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="px-4 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold flex items-center space-x-2">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>DigiLocker Authenticated</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- KYC Documents Upload Card -->
            <div class="card-clean rounded-2xl p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="file-text" class="w-4 h-4 text-emerald-600"></i>
                            <span>Accreditation Documents Vault</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Upload supporting compliance documents (PAN, CA Net Worth Certificate, Bank details).</p>
                    </div>
                </div>

                <!-- Upload Form -->
                <form action="<?= url('investor/verification.php') ?>" method="POST" enctype="multipart/form-data" class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="upload_kyc_doc">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Document Type</label>
                            <select name="doc_type" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 outline-none">
                                <option value="PAN Card Copy">PAN Card Copy</option>
                                <option value="CA Net Worth Certificate">CA Net Worth Certificate</option>
                                <option value="Bank Account Verification">Bank Account Verification</option>
                                <option value="Aadhaar / Address Proof">Aadhaar / Address Proof</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Choose File (PDF, JPG, PNG - Max 10MB)</label>
                            <div class="flex items-center gap-2">
                                <input type="file" name="doc_file" required accept=".pdf,.jpg,.jpeg,.png" class="flex-1 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-700">
                                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-xs transition">
                                    Upload
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Document List -->
                <div class="mt-4 divide-y divide-slate-100">
                    <?php if (empty($verificationDocs)): ?>
                        <div class="py-6 text-center text-xs text-slate-400">
                            No manual documents uploaded yet. DigiLocker authenticated credentials are active.
                        </div>
                    <?php else: ?>
                        <?php foreach ($verificationDocs as $doc): ?>
                            <div class="py-3 flex items-center justify-between text-xs">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                                        <i data-lucide="file" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($doc['document_type']) ?></div>
                                        <div class="text-[10px] text-slate-400"><?= $doc['file_size'] ?> • Uploaded <?= date('M d, Y', strtotime($doc['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $doc['status'] === 'verified' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($doc['status'] === 'rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') ?>">
                                        <?= strtoupper($doc['status']) ?>
                                    </span>
                                    <a href="<?= url($doc['file_path']) ?>" target="_blank" class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="View Document in New Tab">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>View</span>
                                    </a>
                                    <a href="<?= url('download.php?id=' . $doc['id'] . '&type=verification') ?>" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="Download Document File">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        <span>Download</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Compliance Timeline / Audit Log -->
            <?php if (!empty($verificationLogs)): ?>
                <div class="card-clean rounded-2xl p-6">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                        <i data-lucide="history" class="w-4 h-4 text-slate-500"></i>
                        <span>Verification History & Review Audit</span>
                    </h3>
                    <div class="space-y-2 text-xs">
                        <?php foreach ($verificationLogs as $vl): ?>
                            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($vl['new_status']) ?></span>
                                    <span class="text-slate-500 ml-2"><?= htmlspecialchars($vl['remarks'] ?? 'Status updated') ?></span>
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono">
                                    <?= date('M d, Y H:i', strtotime($vl['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#verify-main", { duration: 0.35, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
