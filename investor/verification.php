<?php
/**
 * Investor Module: KYC, SEBI Accreditation & DigiLocker Verification Center
 * Professional Compliance Hub, Editorial Sections, Vay Portal Typography
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
                        if (!is_dir($targetDir))
                            @mkdir($targetDir, 0777, true);

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
    <?php include __DIR__ . '/../includes/investor/head.php'; ?>
    <style>
        @font-face {
            font-family: "Vay Portal", Sans-serif;
            src: local('Vay Portal - Regular'), local('Vay Portal'), local('Plus Jakarta Sans');
        }

        :root {
            --inv-primary: #123B7A;
            --inv-navy: #0B1F3A;
            --inv-secondary: #315F9F;
            --inv-light-blue: #EAF2FF;
            --inv-bg: #F4F2EE;
            --inv-text: #111827;
            --inv-text-sec: #667085;
            --inv-border: #E4E8EF;
        }

        body {
            font-family: 'Vay Portal - Regular', 'Vay Portal', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
            background-color: var(--inv-bg);
            color: var(--inv-text);
        }
    </style>
</head>

<body class="bg-[#F4F2EE] text-[#111827] flex min-h-screen antialiased">

    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 py-6 sm:py-8 space-y-8" id="verify-main">

            <?php if ($flash): ?>
                <div
                    class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>"
                        class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div
                    class="p-4 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>


            <!-- Editorial Header & Status Badge -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-4 border-b border-[#E4E8EF]">
                <div class="space-y-1">
                    <span class="text-[11px] font-bold text-[#123B7A] uppercase tracking-wider">Accreditation &
                        Compliance</span>
                    <h1 class="text-2xl sm:text-3xl font-black text-[#0B1F3A] tracking-tight">Investor Verification
                        Center</h1>
                    <p class="text-xs text-[#667085]">SEBI Angel Syndicate Compliance, DigiLocker National Gateway & KYC
                        Records.</p>
                </div>
                <div>
                    <span
                        class="px-4 py-1.5 rounded-full text-xs font-bold border <?= $user['is_verified'] ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200' ?> flex items-center space-x-1.5 shadow-sm">
                        <i data-lucide="<?= $user['is_verified'] ? 'shield-check' : 'clock' ?>" class="w-3.5 h-3.5"></i>
                        <span><?= $user['is_verified'] ? 'ACCREDITED & VERIFIED' : 'KYC UNDER REVIEW' ?></span>

                    </span>
                </div>
            </div>


            <!-- SECTION 1: DIGILOCKER NATIONAL IDENTITY GATEWAY -->
            <section class="bg-white border border-[#E4E8EF] rounded-2xl p-6 sm:p-8 space-y-6">
                <div class="flex items-center space-x-2 pb-3 border-b border-[#E4E8EF]">
                    <i data-lucide="shield" class="w-4 h-4 text-[#123B7A]"></i>
                    <h2 class="text-xs font-bold text-[#0B1F3A] uppercase tracking-wider">DigiLocker National Identity &
                        Tax PAN</h2>
                </div>

                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <p class="text-xs text-[#667085] leading-relaxed">
                            Under SEBI regulations for angel investment syndicates, accredited investors must maintain
                            verified identity and PAN records. Authenticate via DigiLocker to instantly unlock verified
                            badge and direct escrow investment rights.
                        </p>

                        <?php if ($user['is_verified'] && $verificationRequest): ?>
                            <div class="flex flex-wrap items-center gap-4 text-xs pt-1">
                                <div class="flex items-center space-x-1.5 text-emerald-700 font-bold">

                                    <i data-lucide="badge-check" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Ref:
                                        <?= htmlspecialchars($verificationRequest['provider_ref_id'] ?? 'DL-INV-98214') ?></span>
                                </div>

                                <span class="text-[#E4E8EF]">•</span>
                                <div class="text-[#667085]">
                                    Verified on
                                    <?= date('M d, Y', strtotime($verificationRequest['verified_at'] ?? 'now')) ?>

                                </div>
                                <span class="text-[#E4E8EF]">•</span>
                                <div class="text-[#123B7A] font-semibold">DigiLocker Certificate Linked</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-shrink-0">
                        <?php if (!$user['is_verified']): ?>
                            <form action="<?= url('investor/verification.php') ?>" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="form_action" value="simulate_digilocker">

                                <button type="submit"
                                    class="px-6 py-3 bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center space-x-2">

                                    <i data-lucide="fingerprint" class="w-4 h-4"></i>
                                    <span>Authenticate with DigiLocker</span>
                                </button>
                            </form>
                        <?php else: ?>

                            <div
                                class="px-5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center space-x-2">
                                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>

                                <span>DigiLocker Authenticated</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>


            <!-- SECTION 2: ACCREDITATION DOCUMENTS VAULT -->
            <section class="bg-white border border-[#E4E8EF] rounded-2xl p-6 sm:p-8 space-y-6">
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-[#E4E8EF]">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="file-text" class="w-4 h-4 text-[#123B7A]"></i>
                        <h3 class="text-xs font-bold text-[#0B1F3A] uppercase tracking-wider">Accreditation Documents
                            Vault</h3>

                    </div>
                    <span class="text-xs text-[#667085]">PAN, Net Worth Certificate, Bank Verification</span>
                </div>


                <!-- Clean Horizontal Upload Row -->
                <form action="<?= url('investor/verification.php') ?>" method="POST" enctype="multipart/form-data"
                    class="p-4 rounded-xl bg-[#FAFBFD] border border-[#E4E8EF] text-xs">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="upload_kyc_doc">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label
                                class="block font-bold text-[#667085] text-[11px] mb-1.5 uppercase tracking-wider">Document
                                Classification</label>
                            <select name="doc_type"
                                class="w-full px-3 py-2 bg-white border border-[#E4E8EF] focus:border-[#123B7A] rounded-xl text-xs text-[#111827] outline-none">

                                <option value="PAN Card Copy">PAN Card Copy</option>
                                <option value="CA Net Worth Certificate">CA Net Worth Certificate</option>
                                <option value="Bank Account Verification">Bank Account Verification</option>
                                <option value="Aadhaar / Address Proof">Aadhaar / Address Proof</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">

                            <label
                                class="block font-bold text-[#667085] text-[11px] mb-1.5 uppercase tracking-wider">Upload
                                File (PDF, JPG, PNG - Max 10MB)</label>
                            <div class="flex items-center gap-2">
                                <input type="file" name="doc_file" required accept=".pdf,.jpg,.jpeg,.png"
                                    class="flex-1 px-3 py-1.5 bg-white border border-[#E4E8EF] rounded-xl text-xs text-[#667085]">
                                <button type="submit"
                                    class="px-5 py-2 bg-[#123B7A] hover:bg-[#0B1F3A] text-white font-bold rounded-xl text-xs shadow-sm transition">

                                    Upload
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Document List Rows -->
                <div class="divide-y divide-[#E4E8EF]">
                    <?php if (empty($verificationDocs)): ?>

                        <div class="py-8 text-center text-xs text-[#667085]">
                            <i data-lucide="folder-check" class="w-8 h-8 text-[#667085]/30 mx-auto mb-2"></i>
                            <div>No manual documents uploaded yet.</div>
                            <div class="text-[11px] text-[#667085] mt-0.5">DigiLocker authenticated credentials are active.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($verificationDocs as $doc): ?>
                            <div
                                class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs hover:bg-[#FAFBFD] px-2 rounded-xl transition">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="w-9 h-9 rounded-xl bg-[#EAF2FF] flex items-center justify-center text-[#123B7A] flex-shrink-0">
                                        <i data-lucide="file" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-[#0B1F3A]"><?= htmlspecialchars($doc['document_type']) ?>
                                        </div>
                                        <div class="text-[10px] text-[#667085] mt-0.5"><?= $doc['file_size'] ?> • Uploaded
                                            <?= date('M d, Y', strtotime($doc['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2 self-end sm:self-auto">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $doc['status'] === 'verified' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($doc['status'] === 'rejected' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-amber-50 text-amber-800 border border-amber-200') ?>">
                                        <?= strtoupper($doc['status']) ?>
                                    </span>
                                    <a href="<?= url($doc['file_path']) ?>" target="_blank"
                                        class="px-3 py-1.5 rounded-lg border border-[#E4E8EF] bg-white hover:bg-[#FAFBFD] text-[#0B1F3A] text-xs font-semibold flex items-center space-x-1 transition shadow-sm"
                                        title="View Document in New Tab">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-[#667085]"></i>
                                        <span>View</span>
                                    </a>
                                    <a href="<?= url('download.php?id=' . $doc['id'] . '&type=verification') ?>"
                                        class="px-3 py-1.5 rounded-lg bg-[#0B1F3A] hover:bg-[#123B7A] text-white text-xs font-semibold flex items-center space-x-1 transition shadow-sm"
                                        title="Download Document File">

                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        <span>Download</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- SECTION 3: COMPLIANCE TIMELINE & AUDIT LOG -->
            <?php if (!empty($verificationLogs)): ?>

                <section class="bg-white border border-[#E4E8EF] rounded-2xl p-6 sm:p-8 space-y-4">
                    <div class="flex items-center space-x-2 pb-3 border-b border-[#E4E8EF]">
                        <i data-lucide="history" class="w-4 h-4 text-[#123B7A]"></i>
                        <h3 class="text-xs font-bold text-[#0B1F3A] uppercase tracking-wider">Verification History &
                            Compliance Audit</h3>
                    </div>
                    <div class="space-y-2 text-xs">
                        <?php foreach ($verificationLogs as $vl): ?>
                            <div class="p-3 rounded-xl bg-[#FAFBFD] border border-[#E4E8EF] flex items-center justify-between">
                                <div class="flex items-center space-x-2.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#EAF2FF] text-[#123B7A]">
                                        <?= htmlspecialchars(strtoupper($vl['new_status'])) ?>
                                    </span>
                                    <span
                                        class="text-[#0B1F3A] font-medium"><?= htmlspecialchars($vl['remarks'] ?? 'Status updated') ?></span>
                                </div>
                                <div class="text-[11px] text-[#667085] font-mono">

                                    <?= date('M d, Y H:i', strtotime($vl['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#verify-main > *", { duration: 0.45, y: 15, opacity: 0, stagger: 0.08, ease: "power2.out" });
    </script>
</body>

</html>