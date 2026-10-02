<?php
/**
 * Founder Module: Company Master Profile & Data Room
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Startup & Company Master Profile';

$company = null;
$documents = [];
$error = '';
$flash = get_flash();

if ($db) {
    // 1. Get Company
    $cStmt = $db->prepare("
        SELECT c.* FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ? LIMIT 1
    ");
    $cStmt->execute([$user['id']]);
    $company = $cStmt->fetch();
}

// Handle Update Company POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid.';
    } else {
        $action = $_POST['form_action'] ?? 'update_company';

        if ($action === 'update_company') {
            $name = trim($_POST['name'] ?? '');
            $legalName = trim($_POST['legal_name'] ?? '');
            $cin = strtoupper(trim($_POST['cin_number'] ?? ''));
            $industry = trim($_POST['industry'] ?? 'AI/SaaS');
            $stage = trim($_POST['stage'] ?? 'Seed');
            $businessModel = trim($_POST['business_model'] ?? 'B2B SaaS');
            $pitch = trim($_POST['pitch'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $targetMarket = trim($_POST['target_market'] ?? '');
            $employeeCount = (int) ($_POST['employee_count'] ?? 10);
            $website = trim($_POST['website'] ?? '');
            $city = trim($_POST['city'] ?? 'Bengaluru');
            $state = trim($_POST['state'] ?? 'Karnataka');
            $country = trim($_POST['country'] ?? 'India');

            if ($company) {
                $uStmt = $db->prepare("
                    UPDATE companies SET name = ?, legal_name = ?, cin_number = ?, industry = ?, stage = ?,
                    business_model = ?, pitch = ?, description = ?, target_market = ?, employee_count = ?,
                    website = ?, city = ?, state = ?, country = ?
                    WHERE id = ?
                ");
                $uStmt->execute([$name, $legalName, $cin, $industry, $stage, $businessModel, $pitch, $description, $targetMarket, $employeeCount, $website, $city, $state, $country, $company['id']]);

                log_audit($user['id'], 'UPDATE_COMPANY_PROFILE', 'companies', $company['id'], 'Founder updated company profile');
                set_flash('success', 'Company profile updated successfully.');
            } else {
                $iStmt = $db->prepare("
                    INSERT INTO companies (name, legal_name, cin_number, industry, stage, business_model, pitch, description, target_market, employee_count, website, city, state, country, verified_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $iStmt->execute([$name, $legalName, $cin, $industry, $stage, $businessModel, $pitch, $description, $targetMarket, $employeeCount, $website, $city, $state, $country]);
                $newCompId = $db->lastInsertId();

                $cfStmt = $db->prepare("INSERT INTO company_founders (company_id, user_id, designation, is_signatory) VALUES (?, ?, 'Founder & CEO', 1)");
                $cfStmt->execute([$newCompId, $user['id']]);

                log_audit($user['id'], 'CREATE_COMPANY', 'companies', $newCompId, 'Founder created new company master record');
                set_flash('success', 'Company record created successfully.');
            }
            header('Location: ' . url('founder/company.php'));
            exit;

        } elseif ($action === 'upload_doc' && $company) {
            $docType = trim($_POST['document_type'] ?? 'Pitch Deck');
            $docTitle = trim($_POST['title'] ?? 'Company Document');
            $accessLevel = $_POST['access_level'] ?? 'registered_investors';

            $filePath = '';
            $fileSizeStr = '';

            if (empty($_FILES['doc_file']['name'])) {
                $error = 'Please select a document file to upload.';
            } else {
                $file = $_FILES['doc_file'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'File upload failed with error code ' . $file['error'];
                } else {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
                    if (!in_array($ext, $allowed)) {
                        $error = 'Unsupported file type. Allowed formats: PDF, DOC, DOCX, PPT, PPTX, JPG, PNG.';
                    } elseif ($file['size'] > 25 * 1024 * 1024) {
                        $error = 'File size exceeds maximum limit of 25MB.';
                    } else {
                        $targetDir = ROOT_PATH . '/uploads/documents';
                        if (!is_dir($targetDir))
                            @mkdir($targetDir, 0777, true);

                        $filename = 'comp_' . $company['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
                            $filePath = 'uploads/documents/' . $filename;
                            $fileSizeStr = round($file['size'] / (1024 * 1024), 2) . ' MB';

                            $insDoc = $db->prepare("
                                INSERT INTO company_documents (company_id, document_type, title, file_path, file_size, access_level, is_verified)
                                VALUES (?, ?, ?, ?, ?, ?, 0)
                            ");
                            $insDoc->execute([$company['id'], $docType, $docTitle, $filePath, $fileSizeStr, $accessLevel]);
                            $compDocId = $db->lastInsertId();

                            // Also register in verification_documents for Admin review queue
                            $db->prepare("
                                INSERT INTO verification_documents (user_id, company_id, document_type, file_path, file_size, status)
                                VALUES (?, ?, ?, ?, ?, 'pending')
                            ")->execute([$user['id'], $company['id'], $docType, $filePath, $fileSizeStr]);

                            log_audit($user['id'], 'UPLOAD_DOCUMENT', 'company_documents', $compDocId, "Uploaded company document: $docTitle ($docType)");
                            send_notification(1, 'New Company Document Uploaded', "Startup {$company['name']} uploaded {$docTitle} ({$docType}) for compliance review.", 'info', 'admin/verification_queue.php');
                            set_flash('success', 'Document uploaded successfully and queued for compliance review.');
                            header('Location: ' . url('founder/company.php'));
                            exit;
                        } else {
                            $error = 'Failed to save uploaded file to storage. Please check directory permissions.';
                        }
                    }
                }
            }
        }
    }
}

// Load documents AFTER POST handling
if ($db && $company) {
    $dStmt = $db->prepare("SELECT * FROM company_documents WHERE company_id = ? ORDER BY uploaded_at DESC");
    $dStmt->execute([$company['id']]);
    $documents = $dStmt->fetchAll();
}

// Calculate Profile Strength Metrics
$checks = [
    'brand_name' => !empty($company['name']),
    'legal_name' => !empty($company['legal_name']),
    'cin' => !empty($company['cin_number']),
    'website' => !empty($company['website']),
    'industry' => !empty($company['industry']),
    'stage' => !empty($company['stage']),
    'business_model' => !empty($company['business_model']),
    'pitch' => !empty($company['pitch']),
    'description' => !empty($company['description']),
    'target_market' => !empty($company['target_market']),
    'location' => !empty($company['city']),
    'documents' => !empty($documents)
];
$completedChecks = count(array_filter($checks));
$profileStrength = round(($completedChecks / count($checks)) * 100);

$companyInitials = 'SU';
if (!empty($company['name'])) {
    $words = preg_split('/\s+/', trim($company['name']));
    if (count($words) >= 2) {
        $companyInitials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        $companyInitials = strtoupper(substr($words[0], 0, 2));
    }
}

$flashStyles = [
    'success' => ['bg-emerald-50 text-emerald-800 border-emerald-200', 'check-circle', 'text-emerald-600'],
    'info' => ['bg-blue-50 text-blue-800 border-blue-200', 'info', 'text-blue-600'],
    'error' => ['bg-rose-50 text-rose-800 border-rose-200', 'alert-circle', 'text-rose-600'],
];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
    <style>
        /* Comfortable reading size across the page */
        #company-main {
            font-size: 1rem;
            line-height: 1.6;
        }

        .form-input-clean {
            width: 100%;
            padding: 0.8rem 1rem;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 0.75rem;
            color: #0F172A;
            font-size: 1rem;
            line-height: 1.5rem;
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

        .form-label-clean {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 0.45rem;
        }

        .form-hint {
            font-size: 0.875rem;
            color: #64748B;
            margin-top: 0.4rem;
            line-height: 1.4rem;
        }

        /* Cards stay solid white */
        .section-card {
            background-color: #FFFFFF;
            color: #0F172A;
            border: 1px solid #E2E8F0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }
    </style>
</head>

<body
    class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 md:px-8 py-6 space-y-6" id="company-main">

            <!-- Flash Notification -->
            <?php if ($flash):
                $fs = $flashStyles[$flash['type']] ?? $flashStyles['info']; ?>
                <div
                    class="p-4 rounded-2xl text-base font-semibold border <?= $fs[0] ?> flex items-center space-x-3 shadow-sm">
                    <i data-lucide="<?= $fs[1] ?>" class="w-5 h-5 flex-shrink-0 <?= $fs[2] ?>"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div
                    class="p-4 rounded-2xl text-base font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-3 shadow-sm">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 text-rose-600"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Title Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div
                        class="flex items-center space-x-2 text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-1">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        <span>Investor Due Diligence Core</span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Startup Company Profile</h1>
                    <p class="text-base text-slate-600 dark:text-slate-300 mt-1.5 max-w-2xl leading-relaxed">
                        Verified master company dossier, market positioning, and confidential data room reviewed by
                        institutional and angel investors.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <?= $company ? render_status_badge($company['verified_status']) : '<span class="text-sm font-bold text-slate-600 bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-full flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-400"></span>Not Initialized</span>' ?>
                    <a href="#company-data-room"
                        class="px-4 py-2.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-bold transition flex items-center space-x-1.5 border border-indigo-200">
                        <i data-lucide="folder-lock" class="w-4 h-4"></i>
                        <span>Data Room (<?= count($documents) ?>)</span>
                    </a>
                </div>
            </div>

            <!-- Executive Startup Card with Profile Readiness Bar -->
            <div class="section-card p-5 sm:p-6 relative overflow-hidden">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <!-- Left: Brand Identity & Chips -->
                    <div class="flex items-start sm:items-center space-x-4">
                        <div
                            class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 text-white flex items-center justify-center font-black text-2xl sm:text-3xl shadow-md flex-shrink-0">
                            <?= htmlspecialchars($companyInitials) ?>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                                    <?= htmlspecialchars($company['name'] ?? 'Your Startup Name') ?>
                                </h2>
                                <?php if (!empty($company['legal_name'])): ?>
                                    <span
                                        class="text-sm px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-600 font-medium border border-slate-200">
                                        <?= htmlspecialchars($company['legal_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p class="text-base text-slate-600 mt-1 max-w-xl line-clamp-2 leading-relaxed">
                                <?= !empty($company['pitch']) ? htmlspecialchars($company['pitch']) : '<span class="italic text-slate-400">Add an elevator pitch below to summarize your value proposition to investors.</span>' ?>
                            </p>

                            <!-- Quick Attribute Tags -->
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                <?php if (!empty($company['industry'])): ?>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-sm font-semibold border border-indigo-100">
                                        <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                                        <?= htmlspecialchars($company['industry']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($company['stage'])): ?>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-amber-50 text-amber-800 text-sm font-semibold border border-amber-200">
                                        <i data-lucide="trending-up" class="w-3.5 h-3.5 text-amber-600"></i>
                                        <?= htmlspecialchars($company['stage']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($company['city'])): ?>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <?= htmlspecialchars($company['city']) ?>    <?= !empty($company['country']) ? ', ' . htmlspecialchars($company['country']) : '' ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($company['cin_number'])): ?>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-100 text-slate-600 font-mono text-sm">
                                        <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-500"></i>
                                        CIN: <?= htmlspecialchars($company['cin_number']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Profile Strength Meter -->
                    <div
                        class="lg:w-80 bg-slate-50 rounded-xl p-4 border border-slate-200 flex flex-col justify-between flex-shrink-0">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="font-bold text-slate-700 flex items-center gap-1.5">
                                <i data-lucide="sparkles" class="w-4 h-4 text-indigo-600"></i>
                                Profile Completeness
                            </span>
                            <span class="font-extrabold text-indigo-600 text-lg"><?= $profileStrength ?>%</span>
                        </div>
                        <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden mb-2">
                            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-full rounded-full transition-all duration-700"
                                style="width: <?= $profileStrength ?>%"></div>
                        </div>
                        <div class="flex items-center justify-between text-sm text-slate-600">
                            <span><?= $completedChecks ?> of <?= count($checks) ?> fields ready</span>
                            <span
                                class="<?= $profileStrength >= 80 ? 'text-emerald-600 font-bold' : 'text-amber-600 font-medium' ?>">
                                <?= $profileStrength >= 80 ? 'Investor Ready ✓' : 'Details Pending' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Jump Bar -->
                <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-2 text-sm">
                    <span class="text-slate-500 font-medium mr-1 text-xs uppercase tracking-wider">Quick Jump:</span>
                    <a href="#sec-identity"
                        class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 font-semibold hover:border-indigo-300 hover:text-indigo-600 transition flex items-center gap-1.5">
                        <i data-lucide="building" class="w-3.5 h-3.5"></i>
                        <span>1. Corporate Identity</span>
                    </a>
                    <a href="#sec-market"
                        class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 font-semibold hover:border-indigo-300 hover:text-indigo-600 transition flex items-center gap-1.5">
                        <i data-lucide="pie-chart" class="w-3.5 h-3.5"></i>
                        <span>2. Market & Model</span>
                    </a>
                    <a href="#sec-pitch"
                        class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 font-semibold hover:border-indigo-300 hover:text-indigo-600 transition flex items-center gap-1.5">
                        <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                        <span>3. Pitch & Traction</span>
                    </a>
                    <a href="#sec-team"
                        class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 font-semibold hover:border-indigo-300 hover:text-indigo-600 transition flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                        <span>4. Headquarters & Team</span>
                    </a>
                    <a href="#company-data-room"
                        class="px-3 py-2 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold hover:bg-indigo-100 transition flex items-center gap-1.5">
                        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                        <span>5. Confidential Data Room</span>
                    </a>
                </div>
            </div>

            <!-- Profile Form -->
            <form action="<?= url('founder/company.php') ?>" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="form_action" value="update_company">

                <!-- SECTION 1 -->
                <div id="sec-identity" class="section-card p-6 sm:p-7 space-y-6 scroll-mt-24">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xl">
                                1</div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                                    <span>Corporate & Legal Identity</span>
                                    <i data-lucide="badge-check" class="w-5 h-5 text-indigo-600"></i>
                                </h2>
                                <p class="text-sm text-slate-500 mt-0.5">Foundational legal incorporation and commercial
                                    branding details.</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600">Required
                            for KYC</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                        <div>
                            <label class="form-label-clean"><span>Startup Brand Name <span
                                        class="text-rose-500">*</span></span></label>
                            <input type="text" name="name" required
                                value="<?= htmlspecialchars($company['name'] ?? '') ?>" placeholder="e.g. TechPulse AI"
                                class="form-input-clean">
                            <p class="form-hint">The primary commercial brand name presented in discovery catalogues.
                            </p>
                        </div>
                        <div>
                            <label class="form-label-clean">
                                <span>Registered Entity Legal Name</span>
                                <span class="text-xs text-slate-500 font-normal">As per ROC / MCA</span>
                            </label>
                            <input type="text" name="legal_name"
                                value="<?= htmlspecialchars($company['legal_name'] ?? '') ?>"
                                placeholder="e.g. TechPulse Intelligence Pvt Ltd" class="form-input-clean">
                            <p class="form-hint">The official registered company name shown on investment term sheets.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6 pt-1">
                        <div>
                            <label class="form-label-clean">
                                <span>Corporate CIN / Registration Number <span class="text-rose-500">*</span></span>
                            </label>
                            <div class="relative">
                                <input type="text" name="cin_number" required
                                    value="<?= htmlspecialchars($company['cin_number'] ?? '') ?>"
                                    placeholder="U72900KA2023PTC123456"
                                    class="form-input-clean font-mono uppercase tracking-wide pr-10">
                                <i data-lucide="hash"
                                    class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2"></i>
                            </div>
                            <p class="form-hint">21-character Corporate Identification Number issued by the Ministry of
                                Corporate Affairs (MCA).</p>
                        </div>
                        <div>
                            <label class="form-label-clean">
                                <span>Official Company Website</span>
                                <span class="text-xs text-slate-500 font-normal">https://</span>
                            </label>
                            <div class="relative">
                                <input type="url" name="website"
                                    value="<?= htmlspecialchars($company['website'] ?? '') ?>"
                                    placeholder="https://techpulse.ai" class="form-input-clean pr-10">
                                <i data-lucide="globe"
                                    class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2"></i>
                            </div>
                            <p class="form-hint">Live product demo, documentation, or landing page for investors.</p>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2 -->
                <div id="sec-market" class="section-card p-6 sm:p-7 space-y-6 scroll-mt-24">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xl">
                                2</div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                                    <span>Sector, Stage & Market Model</span>
                                    <i data-lucide="layers" class="w-5 h-5 text-indigo-600"></i>
                                </h2>
                                <p class="text-sm text-slate-500 mt-0.5">Classification filters used by syndicate leads
                                    and venture funds.</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600">Discovery
                            Filters</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 sm:gap-6">
                        <div>
                            <label class="form-label-clean"><span>Industry / Sector <span
                                        class="text-rose-500">*</span></span></label>
                            <select name="industry" class="form-input-clean cursor-pointer">
                                <?php
                                $industries = ['AI/SaaS', 'FinTech', 'HealthTech', 'CleanTech', 'DeepTech', 'E-Commerce', 'EdTech', 'AgriTech', 'Logistics/Mobility', 'Cybersecurity', 'Web3/Crypto', 'BioTech', 'Consumer Tech'];
                                foreach ($industries as $ind): ?>
                                    <option value="<?= htmlspecialchars($ind) ?>" <?= ($company['industry'] ?? '') === $ind ? 'selected' : '' ?>><?= htmlspecialchars($ind) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-hint">Primary operational sector matching investor mandate.</p>
                        </div>
                        <div>
                            <label class="form-label-clean"><span>Current Funding Stage <span
                                        class="text-rose-500">*</span></span></label>
                            <select name="stage" class="form-input-clean cursor-pointer">
                                <?php
                                $stages = ['Idea / MVP', 'Pre-Seed', 'Seed', 'Pre-Series A', 'Series A', 'Series B+', 'Growth'];
                                foreach ($stages as $stg): ?>
                                    <option value="<?= htmlspecialchars($stg) ?>" <?= ($company['stage'] ?? '') === $stg ? 'selected' : '' ?>><?= htmlspecialchars($stg) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-hint">Maturity level of your product and round structure.</p>
                        </div>
                        <div>
                            <label class="form-label-clean"><span>Monetization Model</span></label>
                            <input type="text" name="business_model"
                                value="<?= htmlspecialchars($company['business_model'] ?? 'B2B SaaS') ?>"
                                placeholder="e.g. B2B SaaS, Marketplace, Usage-based" class="form-input-clean">
                            <p class="form-hint">How your business generates recurring revenue.</p>
                        </div>
                    </div>

                    <div class="pt-1">
                        <label class="form-label-clean">
                            <span>Target Addressable Market (TAM) & Ideal Customer Profile</span>
                        </label>
                        <input type="text" name="target_market"
                            value="<?= htmlspecialchars($company['target_market'] ?? '') ?>"
                            placeholder="e.g. Mid-to-Large Enterprise BFSI Institutions across India & SEA ($14B Total TAM)"
                            class="form-input-clean">
                        <p class="form-hint">Specify customer segments, geography, and estimated serviceable market
                            size.</p>
                    </div>
                </div>

                <!-- SECTION 3 -->
                <div id="sec-pitch" class="section-card p-6 sm:p-7 space-y-6 scroll-mt-24">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xl">
                                3</div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                                    <span>Investor Pitch & Traction Story</span>
                                    <i data-lucide="sparkles" class="w-5 h-5 text-indigo-600"></i>
                                </h2>
                                <p class="text-sm text-slate-500 mt-0.5">High-impact summary and competitive moat
                                    presented on investor discovery cards.</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600">Core
                            Narrative</span>
                    </div>

                    <div>
                        <label class="form-label-clean">
                            <span>One-Line Elevator Pitch <span class="text-rose-500">*</span></span>
                            <span class="text-xs text-indigo-600 font-semibold">Keep it punchy & clear</span>
                        </label>
                        <input type="text" name="pitch" required
                            value="<?= htmlspecialchars($company['pitch'] ?? '') ?>"
                            placeholder="e.g. Autonomous AI copilot that accelerates legal contract diligence by 10x for corporate teams."
                            class="form-input-clean font-medium">
                        <p class="form-hint">Appears in bold at the top of your public deal page and investor weekly
                            digests.</p>
                    </div>

                    <div>
                        <label class="form-label-clean">
                            <span>Comprehensive Business Overview, Moat & Traction</span>
                            <span class="text-xs text-slate-500 font-normal">Markdown / Clean Text</span>
                        </label>
                        <textarea name="description" rows="6"
                            placeholder="Highlight:&#10;• The Problem: What painful inefficiency exists today?&#10;• The Solution & Moat: Why can't competitors easily replicate your product?&#10;• Current Traction: MRR, customer logo count, retention rates, MoM growth.&#10;• 12-Month Milestones: What this funding round achieves."
                            class="form-input-clean font-normal leading-relaxed"><?= htmlspecialchars($company['description'] ?? '') ?></textarea>
                        <p class="form-hint">Provide investors with crisp insights into your customer economics, product
                            roadmap, and unfair advantages.</p>
                    </div>
                </div>

                <!-- SECTION 4 -->
                <div id="sec-team" class="section-card p-6 sm:p-7 space-y-6 scroll-mt-24">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xl">
                                4</div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                                    <span>Headquarters & Operational Footprint</span>
                                    <i data-lucide="map-pin" class="w-5 h-5 text-indigo-600"></i>
                                </h2>
                                <p class="text-sm text-slate-500 mt-0.5">Location and current full-time headcount
                                    details.</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600">Team &
                            Geo</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <div>
                            <label class="form-label-clean"><span>Headquarters City</span></label>
                            <input type="text" name="city"
                                value="<?= htmlspecialchars($company['city'] ?? 'Bengaluru') ?>"
                                placeholder="e.g. Bengaluru" class="form-input-clean">
                        </div>
                        <div>
                            <label class="form-label-clean"><span>State / Province</span></label>
                            <input type="text" name="state"
                                value="<?= htmlspecialchars($company['state'] ?? 'Karnataka') ?>"
                                placeholder="e.g. Karnataka" class="form-input-clean">
                        </div>
                        <div>
                            <label class="form-label-clean"><span>Country</span></label>
                            <input type="text" name="country"
                                value="<?= htmlspecialchars($company['country'] ?? 'India') ?>" placeholder="e.g. India"
                                class="form-input-clean">
                        </div>
                        <div>
                            <label class="form-label-clean"><span>Full-Time Team Size</span></label>
                            <div class="relative">
                                <input type="number" name="employee_count" min="1" max="10000"
                                    value="<?= htmlspecialchars($company['employee_count'] ?? 10) ?>"
                                    class="form-input-clean pr-10">
                                <i data-lucide="users"
                                    class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Action Bar -->
                <div
                    class="section-card p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50">
                    <div class="flex items-center space-x-2 text-sm text-slate-600">
                        <i data-lucide="info" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                        <span>Changes propagate to verified investor discovery cards immediately upon saving.</span>
                    </div>
                    <button type="submit"
                        class="w-full sm:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-base rounded-xl shadow-md transition flex items-center justify-center space-x-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save Profile Changes</span>
                    </button>
                </div>
            </form>

            <!-- SECTION 5: Data Room -->
            <div id="company-data-room" class="section-card p-6 sm:p-7 space-y-6 scroll-mt-24">
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                    <div class="flex items-start space-x-3">
                        <div
                            class="w-11 h-11 rounded-xl bg-gradient-to-tr from-indigo-500 to-indigo-700 text-white flex items-center justify-center shadow-sm flex-shrink-0">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Confidential Data Room &
                                    Documents</h2>
                                <span
                                    class="px-2.5 py-0.5 text-sm font-bold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    <?= count($documents) ?> Files
                                </span>
                            </div>
                            <p class="text-sm text-slate-500 mt-1 max-w-xl leading-relaxed">
                                Securely gated compliance repository for Pitch Decks, Financial Models, and Cap Tables
                                accessible only to accredited investors.
                            </p>
                        </div>
                    </div>

                    <?php if ($company): ?>
                        <button type="button" onclick="openUploadModal()"
                            class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition flex items-center justify-center space-x-1.5 shadow-sm flex-shrink-0">
                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                            <span>Upload Document</span>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!$company): ?>
                    <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <div
                            class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="building" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800">Save Company Profile First</h3>
                        <p class="text-sm text-slate-500 max-w-md mx-auto mt-1">
                            Please fill in and save your legal company details above to unlock your secure startup data room
                            vault.
                        </p>
                    </div>
                <?php elseif (empty($documents)): ?>
                    <div class="p-10 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                        <div
                            class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="folder-plus" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800">Your Data Room is Currently Empty</h3>
                        <p class="text-sm text-slate-500 max-w-md mx-auto mt-1 leading-relaxed">
                            Startups with an uploaded Pitch Deck and Cap Table receive 4x more meeting requests from active
                            institutional investors.
                        </p>
                        <button type="button" onclick="openUploadModal()"
                            class="mt-4 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition inline-flex items-center space-x-1.5 shadow-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Add Pitch Deck or Model</span>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="overflow-hidden border border-slate-200 rounded-xl divide-y divide-slate-100 bg-white">
                        <?php foreach ($documents as $doc): ?>
                            <div
                                class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50 transition">
                                <div class="flex items-start sm:items-center space-x-3.5 min-w-0">
                                    <div
                                        class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 border border-indigo-100">
                                        <?php if (stripos($doc['document_type'], 'deck') !== false): ?>
                                            <i data-lucide="presentation" class="w-5 h-5 text-indigo-600"></i>
                                        <?php elseif (stripos($doc['document_type'], 'financial') !== false || stripos($doc['document_type'], 'cap') !== false): ?>
                                            <i data-lucide="table" class="w-5 h-5 text-emerald-600"></i>
                                        <?php elseif (stripos($doc['document_type'], 'incorporation') !== false || stripos($doc['document_type'], 'gst') !== false): ?>
                                            <i data-lucide="shield" class="w-5 h-5 text-violet-600"></i>
                                        <?php else: ?>
                                            <i data-lucide="file-text" class="w-5 h-5 text-slate-600"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 text-base flex flex-wrap items-center gap-2">
                                            <span class="truncate"><?= htmlspecialchars($doc['title']) ?></span>
                                            <span
                                                class="text-xs px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold border border-slate-200 flex-shrink-0">
                                                <?= htmlspecialchars($doc['document_type']) ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                                            <span>Size: <?= htmlspecialchars($doc['file_size'] ?: '1.8 MB') ?></span>
                                            <span>•</span>
                                            <span>Uploaded:
                                                <?= date('M d, Y', strtotime($doc['uploaded_at'] ?? 'now')) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2.5 md:justify-end flex-shrink-0">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs bg-slate-100 text-slate-600 font-semibold border border-slate-200 flex items-center gap-1">
                                        <i data-lucide="lock" class="w-3 h-3 text-slate-400"></i>
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $doc['access_level'] ?? 'registered_investors'))) ?>
                                    </span>

                                    <span
                                        class="text-xs font-bold px-2.5 py-1 rounded-full border flex items-center gap-1 <?= $doc['is_verified'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' ?>">
                                        <span
                                            class="w-1.5 h-1.5 rounded-full <?= $doc['is_verified'] ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                                        <?= $doc['is_verified'] ? 'Verified' : 'Under Review' ?>
                                    </span>

                                    <a href="<?= htmlspecialchars(url($doc['file_path'])) ?>" target="_blank" rel="noopener"
                                        class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-bold flex items-center space-x-1.5 transition shadow-sm">
                                        <i data-lucide="eye" class="w-4 h-4 text-slate-500"></i>
                                        <span>Preview</span>
                                    </a>

                                    <a href="<?= htmlspecialchars(url('download.php?id=' . (int) $doc['id'] . '&type=company')) ?>"
                                        class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold flex items-center space-x-1.5 transition shadow-sm">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                        <span>Download</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upload Modal -->
            <?php if ($company): ?>
                <div id="upload-modal"
                    class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
                    <div
                        class="bg-white border border-slate-200 shadow-2xl max-w-lg w-full rounded-2xl p-6 sm:p-7 relative my-auto max-h-[92vh] overflow-y-auto">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                            <div>
                                <h3 class="font-bold text-slate-900 text-lg">Add Document to Data Room</h3>
                                <p class="text-sm text-slate-500 mt-0.5">Attach investor decks, certificates or financial
                                    models.</p>
                            </div>
                            <button type="button" onclick="closeUploadModal()"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <form action="<?= url('founder/company.php') ?>" method="POST" enctype="multipart/form-data"
                            class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="upload_doc">

                            <div>
                                <label class="form-label-clean"><span>Document Category <span
                                            class="text-rose-500">*</span></span></label>
                                <select name="document_type" class="form-input-clean cursor-pointer">
                                    <option value="Pitch Deck">Pitch Deck (PDF / PPT)</option>
                                    <option value="Certificate of Incorporation">Certificate of Incorporation (ROC)</option>
                                    <option value="Financial Projections">5-Year Financial Model</option>
                                    <option value="Cap Table">Cap Table & Equity Breakdown</option>
                                    <option value="GST Certificate">GST Registration Certificate</option>
                                    <option value="Due Diligence Report">Due Diligence Report / Audit</option>
                                    <option value="Other Document">Other Diligence Document</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label-clean"><span>Display Title <span
                                            class="text-rose-500">*</span></span></label>
                                <input type="text" name="title" required
                                    placeholder="e.g. Q1 2026 Seed Investor Pitch Presentation" class="form-input-clean">
                            </div>

                            <div>
                                <label class="form-label-clean">
                                    <span>Choose Document File <span class="text-rose-500">*</span></span>
                                    <span class="text-xs text-slate-500 font-normal">Max 25 MB</span>
                                </label>
                                <input type="file" name="doc_file" required
                                    accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png"
                                    class="form-input-clean file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                                <p class="form-hint">Accepted formats: PDF, PPTX, DOCX, PNG, JPG.</p>
                            </div>

                            <div>
                                <label class="form-label-clean"><span>Privacy & Access Level</span></label>
                                <select name="access_level" class="form-input-clean cursor-pointer">
                                    <option value="registered_investors">Registered & Verified Investors Only (Recommended)
                                    </option>
                                    <option value="request_only">Gated (Requires Founder Approval per request)</option>
                                    <option value="public">Public (Visible to all platform visitors)</option>
                                </select>
                            </div>

                            <div class="pt-3 flex items-center justify-end space-x-3">
                                <button type="button" onclick="closeUploadModal()"
                                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold text-sm transition">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-sm transition text-sm flex items-center space-x-1.5">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                    <span>Upload & Submit for Review</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        if (window.lucide && typeof lucide.createIcons === 'function') lucide.createIcons();
        if (window.gsap) {
            gsap.fromTo("#company-main", { y: 8, opacity: 0 }, { duration: 0.35, y: 0, opacity: 1, ease: "power2.out", clearProps: "all" });
        }

        function openUploadModal() {
            const m = document.getElementById('upload-modal');
            if (m) m.classList.remove('hidden');
        }
        function closeUploadModal() {
            const m = document.getElementById('upload-modal');
            if (m) m.classList.add('hidden');
        }
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeUploadModal(); });
    </script>
</body>

</html>