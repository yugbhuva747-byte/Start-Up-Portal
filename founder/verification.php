<?php
/**
 * Founder Module: KYC & DigiLocker Document Verification
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'KYC & DigiLocker Document Verification';

$company = null;
$verificationRequest = null;
$error = '';
$flash = get_flash();

if ($db) {
    // Get Company
    $cStmt = $db->prepare("
        SELECT c.* FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ? LIMIT 1
    ");
    $cStmt->execute([$user['id']]);
    $company = $cStmt->fetch();

    $vrStmt = $db->prepare("SELECT * FROM verification_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $vrStmt->execute([$user['id']]);
    $verificationRequest = $vrStmt->fetch();

    $docStmt = $db->prepare("SELECT * FROM verification_documents WHERE user_id = ? ORDER BY created_at DESC");
    $docStmt->execute([$user['id']]);
    $verificationDocs = $docStmt->fetchAll();
}

// Handle DigiLocker Auth Simulation & KYC Document Upload POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token.';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'simulate_digilocker') {
            $refId = 'DL-KYC-' . rand(100000, 999999);
            $digiUri = 'in.gov.digilocker:aadhaar:' . hash('crc32b', $user['email']);

            if ($verificationRequest) {
                $upd = $db->prepare("
                    UPDATE verification_requests 
                    SET status = 'verified', provider_name = 'DigiLocker National Gateway', provider_ref_id = ?, digilocker_uri = ?, remarks = 'Aadhaar, PAN & Corporate CIN verified with DigiLocker API certificate.', verified_at = NOW()
                    WHERE id = ?
                ");
                $upd->execute([$refId, $digiUri, $verificationRequest['id']]);
                $reqId = $verificationRequest['id'];
            } else {
                $ins = $db->prepare("
                    INSERT INTO verification_requests (user_id, company_id, verification_type, status, provider_name, provider_ref_id, digilocker_uri, remarks, verified_at, created_at)
                    VALUES (?, ?, 'digilocker_kyc', 'verified', 'DigiLocker National Gateway', ?, ?, 'Aadhaar, PAN & Corporate CIN verified with DigiLocker API certificate.', NOW(), NOW())
                ");
                $ins->execute([$user['id'], $company['id'] ?? null, $refId, $digiUri]);
                $reqId = $db->lastInsertId();
            }

            // Update user & company status
            $db->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")->execute([$user['id']]);
            if ($company) {
                $db->prepare("UPDATE companies SET verified_status = 'verified' WHERE id = ?")->execute([$company['id']]);
            }

            // Log status transition
            $db->prepare("INSERT INTO verification_logs (verification_request_id, actor_user_id, old_status, new_status, remarks) VALUES (?, ?, 'pending', 'verified', ?)")
               ->execute([$reqId, $user['id'], 'DigiLocker instant verification approved']);

            log_audit($user['id'], 'DIGILOCKER_VERIFIED', 'verification_requests', $reqId, "DigiLocker verification completed. Ref: $refId");
            set_flash('success', 'DigiLocker KYC verification succeeded! Your founder identity and company are now verified.');
            header('Location: ' . url('founder/verification.php'));
            exit;

        } elseif ($action === 'upload_kyc_doc') {
            $docType = trim($_POST['doc_type'] ?? 'Founder PAN Card');
            if (!empty($_FILES['doc_file']['name'])) {
                $file = $_FILES['doc_file'];
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                    if (in_array($ext, $allowed) && $file['size'] <= 15 * 1024 * 1024) {
                        $targetDir = ROOT_PATH . '/uploads/documents';
                        if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);

                        $filename = 'founder_kyc_' . $user['id'] . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
                            $publicPath = 'uploads/documents/' . $filename;
                            $sizeStr = round($file['size'] / (1024 * 1024), 2) . ' MB';

                            $reqId = $verificationRequest['id'] ?? null;
                            if (!$reqId) {
                                $refId = 'KYC-FND-' . rand(100000, 999999);
                                $insReq = $db->prepare("
                                    INSERT INTO verification_requests (user_id, company_id, verification_type, status, provider_name, provider_ref_id, remarks, created_at)
                                    VALUES (?, ?, 'document_kyc', 'pending', 'Manual KYC Submission', ?, 'Founder submitted compliance documents for admin review.', NOW())
                                ");
                                $insReq->execute([$user['id'], $company['id'] ?? null, $refId]);
                                $reqId = $db->lastInsertId();
                            } else {
                                if (in_array($verificationRequest['status'], ['rejected', 'additional_info'])) {
                                    $db->prepare("UPDATE verification_requests SET status = 'pending', remarks = 'Updated with newly uploaded documents.' WHERE id = ?")->execute([$reqId]);
                                }
                            }

                            $db->prepare("
                                INSERT INTO verification_documents (verification_request_id, user_id, company_id, document_type, file_path, file_size, status)
                                VALUES (?, ?, ?, ?, ?, 'pending')
                            ")->execute([$reqId, $user['id'], $company['id'] ?? null, $docType, $publicPath, $sizeStr]);

                            $newDocId = $db->lastInsertId();
                            log_audit($user['id'], 'UPLOAD_FOUNDER_KYC_DOC', 'verification_documents', $newDocId, "Founder uploaded $docType");
                            send_notification(1, 'New KYC Document Uploaded', "Founder {$user['name']} submitted {$docType} for compliance verification.", 'info', 'admin/verification_queue.php');
                            set_flash('success', "$docType uploaded for compliance review.");
                        } else {
                            $error = 'Failed to save file.';
                        }
                    } else {
                        $error = 'File must be PDF, JPG, or PNG under 15MB.';
                    }
                } else {
                    $error = 'Upload error: ' . $file['error'];
                }
            } else {
                $error = 'Please select a document file.';
            }
            header('Location: ' . url('founder/verification.php'));
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
    <title>KYC & DigiLocker • <?= APP_NAME ?></title>
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
    
    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-5xl w-full mx-auto" id="verify-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Identity & Document Verification</h1>
                    <p class="text-xs text-slate-500 mt-0.5">DigiLocker-supported KYC workflow ensuring platform trust and legal compliance.</p>
                </div>
                <div>
                    <?= render_status_badge($verificationRequest['status'] ?? 'PENDING') ?>
                </div>
            </div>

            <!-- DigiLocker Integration Card -->
            <div class="card-clean rounded-2xl p-6 relative">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="shield-check" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h2 class="text-sm font-bold text-slate-900">DigiLocker Instant Verification</h2>
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-blue-50 text-blue-700 font-semibold border border-blue-200">Official Gateway</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">Authenticate Aadhaar, PAN, and MCA corporate registration automatically with verified consent.</p>
                        </div>
                    </div>

                    <?php if (($verificationRequest['status'] ?? '') === 'verified'): ?>
                        <div class="px-3.5 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs flex items-center space-x-1.5">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                            <span>KYC Verified</span>
                        </div>
                    <?php else: ?>
                        <form action="<?= url('founder/verification.php') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="simulate_digilocker">
                            <button type="submit" class="px-4 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm transition flex items-center space-x-1.5">
                                <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                <span>Authenticate with DigiLocker</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (($verificationRequest['status'] ?? '') === 'verified'): ?>
                    <div class="mt-5 pt-5 border-t border-slate-100 grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Verification Ref</div>
                            <div class="font-mono font-bold text-slate-800 mt-0.5 text-xs"><?= htmlspecialchars($verificationRequest['provider_ref_id']) ?></div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Gateway Provider</div>
                            <div class="font-bold text-indigo-600 mt-0.5 text-xs"><?= htmlspecialchars($verificationRequest['provider_name']) ?></div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Timestamp</div>
                            <div class="text-slate-700 mt-0.5 text-xs"><?= date('d M Y, H:i', strtotime($verificationRequest['verified_at'])) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Compliance Status Feedback Alert if Rejected or Additional Info Required -->
            <?php if (!empty($verificationRequest['remarks']) && in_array($verificationRequest['status'] ?? '', ['rejected', 'additional_info'])): ?>
                <div class="p-4 rounded-2xl border <?= $verificationRequest['status'] === 'rejected' ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-amber-50 border-amber-200 text-amber-800' ?> text-xs space-y-1">
                    <div class="font-bold flex items-center space-x-1.5">
                        <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        <span>Compliance Review Notice (<?= ucfirst($verificationRequest['status']) ?>)</span>
                    </div>
                    <p class="text-[11.5px] leading-relaxed pl-5"><?= htmlspecialchars($verificationRequest['remarks']) ?></p>
                    <p class="text-[10.5px] opacity-80 pl-5">Please upload the requested or revised documents below to submit for re-evaluation.</p>
                </div>
            <?php endif; ?>

            <!-- Required Verification Checklist -->
            <div class="card-clean rounded-2xl p-6">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                    <i data-lucide="list-checks" class="w-3.5 h-3.5 text-indigo-600"></i>
                    <span>Founder Verification Checklist</span>
                </h3>

                <div class="divide-y divide-slate-100 text-xs">
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800 text-xs">1. Individual Identity KYC (Aadhaar / Passport)</div>
                            <div class="text-slate-500 text-[11px] mt-0.5">Confirms legal founder persona and citizenship</div>
                        </div>
                        <span class="<?= ($verificationRequest['status'] ?? '') === 'verified' ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-amber-700 bg-amber-50 border-amber-200' ?> border px-2 py-0.5 rounded-full text-[11px] font-semibold flex items-center space-x-1">
                            <i data-lucide="<?= ($verificationRequest['status'] ?? '') === 'verified' ? 'check' : 'clock' ?>" class="w-3 h-3"></i>
                            <span><?= ($verificationRequest['status'] ?? '') === 'verified' ? 'Completed' : 'Pending Review' ?></span>
                        </span>
                    </div>

                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800 text-xs">2. Tax Identification (PAN Card)</div>
                            <div class="text-slate-500 text-[11px] mt-0.5">Essential for Indian direct tax and compliance reporting</div>
                        </div>
                        <span class="<?= ($verificationRequest['status'] ?? '') === 'verified' ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-amber-700 bg-amber-50 border-amber-200' ?> border px-2 py-0.5 rounded-full text-[11px] font-semibold flex items-center space-x-1">
                            <i data-lucide="<?= ($verificationRequest['status'] ?? '') === 'verified' ? 'check' : 'clock' ?>" class="w-3 h-3"></i>
                            <span><?= ($verificationRequest['status'] ?? '') === 'verified' ? 'Completed' : 'Pending Review' ?></span>
                        </span>
                    </div>

                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800 text-xs">3. MCA Corporate Registration & CIN</div>
                            <div class="text-slate-500 text-[11px] mt-0.5">Corporate entity verification with Ministry of Corporate Affairs</div>
                        </div>
                        <span class="<?= $company && !empty($company['cin_number']) ? 'text-emerald-700 bg-emerald-50 border border-emerald-200' : 'text-amber-700 bg-amber-50 border border-amber-200' ?> px-2 py-0.5 rounded-full text-[11px] font-semibold flex items-center space-x-1">
                            <i data-lucide="<?= $company && !empty($company['cin_number']) ? 'check' : 'clock' ?>" class="w-3 h-3"></i>
                            <span><?= $company && !empty($company['cin_number']) ? 'CIN Matched' : 'Pending CIN Input' ?></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- KYC Document Upload & Repository -->
            <div class="card-clean rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="file-text" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>KYC & Corporate Documents</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Upload official identity and incorporation records for administrative compliance inspection.</p>
                    </div>
                </div>

                <!-- Upload Form -->
                <form action="<?= url('founder/verification.php') ?>" method="POST" enctype="multipart/form-data" class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 mb-5">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="upload_kyc_doc">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Document Type</label>
                            <select name="doc_type" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-indigo-600">
                                <option value="Founder PAN Card">Founder PAN Card</option>
                                <option value="Passport / Aadhaar ID">Passport / Aadhaar ID</option>
                                <option value="Certificate of Incorporation">Certificate of Incorporation (MCA)</option>
                                <option value="GST Registration Certificate">GST Registration Certificate</option>
                                <option value="Audited Financial Statement">Audited Financial Statement</option>
                                <option value="Board Resolution">Board Resolution / MOA & AOA</option>
                                <option value="Startup Pitch Deck">Startup Pitch Deck</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Select File (PDF, JPG, PNG - Max 15MB)</label>
                            <input type="file" name="doc_file" required accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-700">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-xs transition shadow-sm flex items-center justify-center space-x-1.5">
                                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                                <span>Upload Document</span>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Documents List -->
                <div class="divide-y divide-slate-100">
                    <?php if (empty($verificationDocs)): ?>
                        <div class="py-8 text-center text-xs text-slate-400">
                            No compliance documents uploaded yet. Submit your founder PAN or company incorporation above.
                        </div>
                    <?php else: ?>
                        <?php foreach ($verificationDocs as $doc): ?>
                            <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="<?= str_contains(strtolower($doc['file_path']), '.pdf') ? 'file-text' : 'image' ?>" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($doc['document_type']) ?></div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            <?= htmlspecialchars($doc['file_size']) ?> • Uploaded <?= date('M d, Y, h:i A', strtotime($doc['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2.5">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $doc['status'] === 'verified' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($doc['status'] === 'rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') ?>">
                                        <?= strtoupper($doc['status']) ?>
                                    </span>
                                    <a href="<?= url($doc['file_path']) ?>" target="_blank" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="View Document in New Tab">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>View</span>
                                    </a>
                                    <a href="<?= url('download.php?id=' . $doc['id'] . '&type=verification') ?>" class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="Download Document File">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        <span>Download</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#verify-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
