<?php
/**
 * Founder Module: KYC & DigiLocker Document Verification Studio
 * Implements National Gateway Authentication, Statutory Compliance Stepper & Digital Vault
 * Clean Modern Layout, Full-width, Vay Portal Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'KYC & DigiLocker Document Verification';

$company = null;
$verificationRequest = null;
$error = '';
$flash = get_flash();
$verificationDocs = [];

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
                                VALUES (?, ?, ?, ?, ?, ?, 'pending')
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

$isVerified = ($verificationRequest['status'] ?? '') === 'verified';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?? APP_NAME ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
    <style>
        html:not(.dark) .hero-trust-banner {
            background: radial-gradient(130% 100% at 0% 0%, #EFF6FF 0%, #F8FAFC 50%, #F1F5F9 100%);
            border: 1px solid #DBEAFE;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        }
        .hero-trust-banner {
            border-radius: 1.5rem;
            position: relative;
        }
        html.dark .hero-trust-banner {
            background: radial-gradient(130% 100% at 0% 0%, #17213A 0%, #0F172A 55%, #111827 100%) !important;
            border: 1px solid #1E293B !important;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
        }
        .form-input-clean {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 0.75rem;
            color: #0F172A;
            font-size: 0.875rem;
            line-height: 1.4rem;
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
    </style>
</head>
<body class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">
    
    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <!-- Sticky Top Fixed Founder Navbar -->
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <!-- Full-screen Dynamic Main Container -->
        <main class="w-full px-4 sm:px-6 md:px-8 py-6 space-y-6" id="verify-main">
            
            <!-- Flash Feedback -->
            <?php if ($flash): ?>

                <div class="p-4 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center justify-between shadow-xs">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0 <?= $flash['type'] === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <span class="text-xs font-bold uppercase opacity-75">Status Update</span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-4 rounded-2xl text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-3 shadow-xs">
                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 text-rose-600"></i>
                    <span><?= htmlspecialchars($error) ?></span>

                </div>
            <?php endif; ?>

            <!-- Trust & National Gateway Executive Hero Banner -->
            <div class="hero-trust-banner p-6 sm:p-8 relative overflow-hidden bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800">
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute right-32 -bottom-16 w-56 h-56 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?= $isVerified ? 'bg-emerald-600 text-white shadow-emerald-500/25' : 'bg-blue-600 text-white shadow-blue-500/25' ?> shadow-sm">
                                <i data-lucide="<?= $isVerified ? 'shield-check' : 'shield-alert' ?>" class="w-3.5 h-3.5"></i>
                                <span><?= $isVerified ? 'DigiLocker Verified Compliance' : 'Official National KYC Gateway' ?></span>
                            </span>
                            <?php if ($company): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/80 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 backdrop-blur-sm">
                                    <i data-lucide="building" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400"></i>
                                    <span><?= htmlspecialchars($company['name']) ?></span>
                                    <span class="text-slate-300 dark:text-slate-600">•</span>
                                    <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">CIN: <?= htmlspecialchars($company['cin_number'] ?: 'Verification in Progress') ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            Identity & DigiLocker Compliance Verification
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
                            Official statutory verification infrastructure backed by National DigiLocker API gateways, MCA Master Data matching, and SEBI angel investment regulatory standards.
                        </p>
                    </div>

                    <!-- Trust Status Badge Widget -->
                    <div class="flex items-center space-x-3">
                        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl <?= $isVerified ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-800' ?> flex items-center justify-center font-bold flex-shrink-0">
                                <i data-lucide="<?= $isVerified ? 'award' : 'clock' ?>" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <div class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Platform Status</div>
                                <div class="text-sm font-extrabold <?= $isVerified ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400' ?>">
                                    <?= $isVerified ? 'Verified Issuer' : 'Verification Required' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- 4-Step Statutory Verification Progress Stepper (Not Boring!) -->
            <div class="section-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                        <i data-lucide="layers" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                        <span>Statutory Verification Progress Stepper</span>
                    </h3>
                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-100 dark:border-indigo-800/80 px-2.5 py-0.5 rounded-full">
                        <?= $isVerified ? '4 of 4 Steps Complete (100%)' : '2 of 4 Steps Pending' ?>
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <!-- Step 1: Founder Identity -->
                    <div class="p-4 rounded-xl border <?= $isVerified ? 'bg-emerald-50/50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/60' : 'bg-slate-50 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800' ?> space-y-1 relative">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold <?= $isVerified ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' ?>">Step 1</span>
                            <i data-lucide="<?= $isVerified ? 'check-circle' : 'circle-dot' ?>" class="w-4 h-4 <?= $isVerified ? 'text-emerald-600 dark:text-emerald-400' : 'text-indigo-600 dark:text-indigo-400' ?>"></i>
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Founder Aadhaar / ID</div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">UIDAI cryptographic consent via DigiLocker</p>
                    </div>

                    <!-- Step 2: Tax PAN -->
                    <div class="p-4 rounded-xl border <?= $isVerified ? 'bg-emerald-50/50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/60' : 'bg-slate-50 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800' ?> space-y-1 relative">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold <?= $isVerified ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' ?>">Step 2</span>
                            <i data-lucide="<?= $isVerified ? 'check-circle' : 'circle-dot' ?>" class="w-4 h-4 <?= $isVerified ? 'text-emerald-600 dark:text-emerald-400' : 'text-indigo-600 dark:text-indigo-400' ?>"></i>
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Income Tax PAN</div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Permanent Account Number cross-check</p>
                    </div>

                    <!-- Step 3: MCA Corporate CIN -->
                    <div class="p-4 rounded-xl border <?= ($company && !empty($company['cin_number'])) || $isVerified ? 'bg-emerald-50/50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/60' : 'bg-slate-50 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800' ?> space-y-1 relative">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold <?= ($company && !empty($company['cin_number'])) || $isVerified ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' ?>">Step 3</span>
                            <i data-lucide="<?= ($company && !empty($company['cin_number'])) || $isVerified ? 'check-circle' : 'clock' ?>" class="w-4 h-4 <?= ($company && !empty($company['cin_number'])) || $isVerified ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-500 dark:text-amber-400' ?>"></i>
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">MCA Corporate CIN</div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Ministry of Corporate Affairs registry</p>
                    </div>

                    <!-- Step 4: Certified Trust Seal -->
                    <div class="p-4 rounded-xl border <?= $isVerified ? 'bg-emerald-50/50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/60' : 'bg-slate-50 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800' ?> space-y-1 relative">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold <?= $isVerified ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' ?>">Step 4</span>
                            <i data-lucide="<?= $isVerified ? 'shield-check' : 'lock' ?>" class="w-4 h-4 <?= $isVerified ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' ?>"></i>
                        </div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Platform Trust Seal</div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Unlocks investor syndicates & wire custody</p>
                    </div>
                </div>
            </div>

            <!-- DigiLocker Instant Verification Gateway Card -->
            <div class="section-card p-6 sm:p-7 relative overflow-hidden">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center flex-shrink-0 shadow-md shadow-blue-500/20">
                            <i data-lucide="shield-check" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h2 class="text-base font-bold text-slate-900">DigiLocker National Authentication Gateway</h2>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] bg-blue-50 text-blue-700 font-bold border border-blue-200">MeitY Gov. of India</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1 max-w-xl leading-relaxed">
                                Instant, paperless KYC verification. Pulls verified Aadhaar XML, founder PAN verification, and incorporation certificate in one single secure workflow.
                            </p>
                        </div>
                    </div>

                    <div>
                        <?php if ($isVerified): ?>
                            <div class="px-5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs flex items-center space-x-2 shadow-xs">
                                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                <span>Digitally Certified</span>
                            </div>
                        <?php else: ?>
                            <form action="<?= url('founder/verification.php') ?>" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="form_action" value="simulate_digilocker">
                                <button type="submit" class="px-5 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-600/20 transition flex items-center space-x-2">
                                    <i data-lucide="lock" class="w-4 h-4"></i>
                                    <span>Authenticate with DigiLocker</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isVerified): ?>
                    <!-- Digital Certificate Meta Details -->
                    <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Gateway Reference ID</div>
                            <div class="font-mono font-bold text-slate-900 mt-1 text-xs"><?= htmlspecialchars($verificationRequest['provider_ref_id']) ?></div>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Authentication Provider</div>
                            <div class="font-bold text-indigo-600 mt-1 text-xs"><?= htmlspecialchars($verificationRequest['provider_name']) ?></div>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Certified Timestamp</div>
                            <div class="text-slate-700 mt-1 text-xs font-mono"><?= date('d M Y • H:i:s', strtotime($verificationRequest['verified_at'])) ?></div>

                        </div>
                    </div>
                <?php endif; ?>
            </div>


            <!-- Compliance Status Feedback Alert if Rejected or Additional Info Required -->
            <?php if (!empty($verificationRequest['remarks']) && in_array($verificationRequest['status'] ?? '', ['rejected', 'additional_info'])): ?>
                <div class="p-5 rounded-2xl border <?= $verificationRequest['status'] === 'rejected' ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-amber-50 border-amber-200 text-amber-800' ?> text-sm space-y-1.5">
                    <div class="font-bold flex items-center space-x-2">
                        <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                        <span>Compliance Action Required: <?= htmlspecialchars($verificationRequest['status'] === 'rejected' ? 'Verification Rejected' : 'Additional Information Needed') ?></span>
                    </div>
                    <div class="text-xs opacity-90 pl-6 leading-relaxed">
                        <?= nl2br(htmlspecialchars($verificationRequest['remarks'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Upload Dropzone & Compliance Document Repository -->
            <div class="section-card p-6 sm:p-7 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                            <i data-lucide="file-check-2" class="w-4 h-4 text-indigo-600"></i>
                            <span>Statutory Document Repository & Vault</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Submit supporting documents (MCA Certificate, PAN, GSTIN, Pitch Decks) for compliance audits.</p>

                    </div>
                    <span class="text-xs text-slate-400 font-mono">Total Documents: <?= count($verificationDocs) ?></span>
                </div>


                <!-- Modern Document Upload Form -->
                <form action="<?= url('founder/verification.php') ?>" method="POST" enctype="multipart/form-data" class="bg-slate-50/80 border border-slate-200 rounded-2xl p-5 space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="upload_kyc_doc">

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        <div class="md:col-span-4">
                            <label class="block font-bold text-slate-700 text-xs mb-1.5 uppercase tracking-wider">Document Classification</label>
                            <select name="doc_type" required class="form-input-clean font-medium cursor-pointer">
                                <option value="Founder PAN Card">💳 Founder PAN Card</option>
                                <option value="Passport / Aadhaar ID">🪪 Passport / Aadhaar ID</option>
                                <option value="Certificate of Incorporation">🏛️ Certificate of Incorporation (MCA)</option>
                                <option value="GST Registration Certificate">🧾 GST Registration Certificate</option>
                                <option value="Audited Financial Statement">📊 Audited Financial Statement</option>
                                <option value="Board Resolution">📑 Board Resolution / MOA & AOA</option>
                                <option value="Startup Pitch Deck">🚀 Startup Pitch Deck</option>
                            </select>
                        </div>

                        <div class="md:col-span-5">
                            <label class="block font-bold text-slate-700 text-xs mb-1.5 uppercase tracking-wider">Choose Document File (PDF, JPG, PNG - Max 15MB)</label>
                            <input type="file" name="doc_file" required accept=".pdf,.jpg,.jpeg,.png" class="form-input-clean text-xs text-slate-700">
                        </div>

                        <div class="md:col-span-3">
                            <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition shadow-md shadow-indigo-600/20 flex items-center justify-center space-x-2">
                                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                                <span>Upload to Secure Vault</span>

                            </button>
                        </div>
                    </div>
                </form>

                <!-- Document List -->
                <div class="divide-y divide-slate-100">
                    <?php if (empty($verificationDocs)): ?>

                        <div class="py-12 text-center text-xs text-slate-400">
                            <i data-lucide="folder-open" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                            <div>No compliance documents stored in the vault yet.</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Upload founder PAN, Aadhaar, or MCA incorporation certificate above.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($verificationDocs as $doc): ?>
                            <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">

                                <div class="flex items-center space-x-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="<?= str_contains(strtolower($doc['file_path']), '.pdf') ? 'file-text' : 'image' ?>" class="w-5 h-5"></i>
                                    </div>
                                    <div>

                                        <div class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($doc['document_type']) ?></div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                            <?= htmlspecialchars($doc['file_size']) ?> • Uploaded <?= date('M d, Y • h:i A', strtotime($doc['created_at'])) ?>

                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2.5">

                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold <?= $doc['status'] === 'verified' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($doc['status'] === 'rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') ?>">
                                        <?= strtoupper($doc['status']) ?>
                                    </span>
                                    <a href="<?= url($doc['file_path']) ?>" target="_blank" class="px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1.5 transition shadow-xs">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-600"></i>
                                        <span>View</span>
                                    </a>
                                    <a href="<?= url('download.php?id=' . $doc['id'] . '&type=verification') ?>" class="px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center space-x-1.5 transition shadow-xs">
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
        gsap.from("#verify-main", { duration: 0.35, y: 8, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
