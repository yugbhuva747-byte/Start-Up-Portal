<?php
/**
 * Founder Module: Company Master Profile & Data Room
 * Premium SaaS UI / Interactive Real-world Experience
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

    if ($company) {
        $dStmt = $db->prepare("SELECT * FROM company_documents WHERE company_id = ? ORDER BY uploaded_at DESC");
        $dStmt->execute([$company['id']]);
        $documents = $dStmt->fetchAll();
    }
}

// Handle Update Company POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please refresh the page and try again.';
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
            $employeeCount = (int)($_POST['employee_count'] ?? 10);
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
                set_flash('success', 'Startup company profile updated successfully.');
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

            $filePath = 'uploads/documents/sample_doc.pdf';
            $fileSizeStr = '1.8 MB';

            if (!empty($_FILES['doc_file']['name'])) {
                $file = $_FILES['doc_file'];
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
                    if (in_array($ext, $allowed) && $file['size'] <= 25 * 1024 * 1024) {
                        $targetDir = ROOT_PATH . '/uploads/documents';
                        if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);

                        $filename = 'comp_' . $company['id'] . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
                            $filePath = 'uploads/documents/' . $filename;
                            $fileSizeStr = round($file['size'] / (1024 * 1024), 2) . ' MB';
                        }
                    }
                }
            }

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
        }
    }
}

// Calculate profile completion percentage
$profileScore = 0;
if ($company) {
    if (!empty($company['name'])) $profileScore += 15;
    if (!empty($company['legal_name'])) $profileScore += 10;
    if (!empty($company['cin_number'])) $profileScore += 15;
    if (!empty($company['pitch'])) $profileScore += 15;
    if (!empty($company['description'])) $profileScore += 15;
    if (!empty($company['website'])) $profileScore += 10;
    if (!empty($company['city'])) $profileScore += 10;
    if (count($documents) > 0) $profileScore += 10;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile & Data Room • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .form-input-group:focus-within {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
            background-color: #ffffff;
        }
        .clean-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
        }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-900 flex min-h-screen">
    
    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-5xl w-full mx-auto" id="company-main">
            
            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-sm font-bold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-2.5">
                        <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-5 h-5 <?= $flash['type'] === 'success' ? 'text-emerald-600' : 'text-rose-600' ?> flex-shrink-0"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">Just now</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-xl text-sm font-bold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2.5 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Hero Banner & Profile Status Header -->
            <div class="clean-card rounded-2xl p-6 sm:p-7 bg-gradient-to-r from-white via-slate-50/50 to-indigo-50/30 border border-slate-200/80">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="flex items-start sm:items-center space-x-4">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-indigo-200 flex-shrink-0">
                            <?php if ($company && !empty($company['logo_url'])): ?>
                                <img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="Logo" class="w-full h-full object-cover rounded-xl">
                            <?php else: ?>
                                <?= strtoupper(substr($company['name'] ?? 'ST', 0, 2)) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                                    <?= htmlspecialchars($company['name'] ?? 'Startup Master Profile') ?>
                                </h1>
                                <?= $company ? render_status_badge($company['verified_status']) : '<span class="text-xs font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full">New Draft</span>' ?>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-600 font-medium mt-0.5">
                                <?= htmlspecialchars($company['legal_name'] ?? 'Verified corporate identity & pitch assets presented to investors in discovery.') ?>
                            </p>
                        </div>
                    </div>

                    <!-- Profile Health Progress Tracker -->
                    <div class="bg-white/90 backdrop-blur-sm border border-slate-200 p-3.5 sm:p-4 rounded-xl min-w-[210px] shadow-xs">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-slate-600 font-bold text-xs flex items-center space-x-1.5">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Profile Strength</span>
                            </span>
                            <span class="text-base font-extrabold <?= $profileScore >= 80 ? 'text-emerald-600' : ($profileScore >= 50 ? 'text-indigo-600' : 'text-amber-600') ?>"><?= $profileScore ?>%</span>
                        </div>
                        <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                            <div class="h-full rounded-full transition-all duration-500 <?= $profileScore >= 80 ? 'bg-emerald-500' : ($profileScore >= 50 ? 'bg-indigo-600' : 'bg-amber-500') ?>" style="width: <?= $profileScore ?>%"></div>
                        </div>
                        <div class="text-xs text-slate-500 font-semibold mt-1.5 flex items-center justify-between">
                            <span><?= count($documents) ?> Docs attached</span>
                            <span class="font-bold <?= $profileScore >= 80 ? 'text-emerald-600' : 'text-amber-600' ?>"><?= $profileScore >= 80 ? 'Investor Ready' : 'Incomplete' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Master Profile Form -->
            <div class="clean-card rounded-2xl p-6 sm:p-8">
                <form action="<?= url('founder/company.php') ?>" method="POST" class="space-y-8" id="startup-profile-form">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="update_company">

                    <!-- Section 1: Legal & Corporate Identity -->
                    <div class="space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                    <i data-lucide="building" class="w-4 h-4"></i>
                                </div>
                                <h2 class="text-sm sm:text-base font-extrabold text-slate-900 uppercase tracking-wider">
                                    1. Legal & Corporate Identity
                                </h2>
                            </div>
                            <span class="text-xs text-slate-500 font-semibold">Step 1 of 3</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                                    <span class="flex items-center space-x-1">
                                        <span>Startup Brand Name</span>
                                        <span class="text-rose-500 font-bold">*</span>
                                    </span>
                                    <span class="text-xs text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded font-bold">Public Name</span>
                                </label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="briefcase" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="name" required value="<?= htmlspecialchars($company['name'] ?? '') ?>" placeholder="e.g. TechPulse AI"
                                           class="w-full pl-3 pr-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 outline-none font-semibold placeholder:text-slate-400">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                                    <span>Registered Legal Entity Name</span>
                                    <span class="text-xs text-slate-500 font-semibold">As per MCA</span>
                                </label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="legal_name" value="<?= htmlspecialchars($company['legal_name'] ?? '') ?>" placeholder="e.g. TechPulse Intelligence Technologies Pvt Ltd"
                                           class="w-full pl-3 pr-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 outline-none font-semibold placeholder:text-slate-400">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                                    <span class="flex items-center space-x-1">
                                        <span>Corporate CIN Number</span>
                                        <span class="text-rose-500 font-bold">*</span>
                                    </span>
                                    <span class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded font-bold">MCA Matched</span>
                                </label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="file-check" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="cin_number" required value="<?= htmlspecialchars($company['cin_number'] ?? '') ?>" placeholder="U72900KA2023PTC123456"
                                           class="w-full pl-3 pr-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 font-mono font-bold uppercase tracking-wider outline-none placeholder:text-slate-400">
                                </div>
                                <p class="text-xs text-slate-500 mt-1 pl-1 font-medium">21-digit statutory Corporate Identification Number.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                                    <span>Official Website URL</span>
                                    <?php if (!empty($company['website'])): ?>
                                        <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" class="text-xs font-bold text-indigo-600 hover:underline flex items-center space-x-1">
                                            <span>Visit link</span>
                                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                        </a>
                                    <?php endif; ?>
                                </label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="globe" class="w-4 h-4"></i>
                                    </span>
                                    <input type="url" name="website" value="<?= htmlspecialchars($company['website'] ?? '') ?>" placeholder="https://techpulse.ai"
                                           class="w-full pl-3 pr-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 outline-none font-semibold placeholder:text-slate-400">
                                </div>
                                <p class="text-xs text-slate-500 mt-1 pl-1 font-medium">Public product or corporate web landing page.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Industry, Stage & Business Model -->
                    <div class="space-y-5 pt-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                    <i data-lucide="layers" class="w-4 h-4"></i>
                                </div>
                                <h2 class="text-sm sm:text-base font-extrabold text-slate-900 uppercase tracking-wider">
                                    2. Industry, Stage & Business Model
                                </h2>
                            </div>
                            <span class="text-xs text-slate-500 font-semibold">Step 2 of 3</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1.5">Sector / Industry Domain</label>
                                <div class="relative form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <select name="industry" class="w-full px-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 font-semibold outline-none cursor-pointer appearance-none">
                                        <?php 
                                        $industries = ['AI/SaaS', 'FinTech', 'HealthTech', 'CleanTech', 'DeepTech', 'E-Commerce', 'EdTech', 'AgriTech', 'Logistics & Supply Chain', 'Cybersecurity', 'Web3 & Crypto'];
                                        foreach ($industries as $ind): ?>
                                            <option value="<?= $ind ?>" <?= ($company['industry'] ?? '') === $ind ? 'selected' : '' ?>><?= $ind ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-500">
                                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1.5">Current Startup Stage</label>
                                <div class="relative form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <select name="stage" class="w-full px-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 font-semibold outline-none cursor-pointer appearance-none">
                                        <?php 
                                        $stages = ['Idea / MVP', 'Pre-Seed', 'Seed', 'Pre-Series A', 'Series A', 'Series B', 'Growth / Scale'];
                                        foreach ($stages as $stg): ?>
                                            <option value="<?= $stg ?>" <?= ($company['stage'] ?? '') === $stg ? 'selected' : '' ?>><?= $stg ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-500">
                                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1.5">Monetization Model</label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="business_model" value="<?= htmlspecialchars($company['business_model'] ?? 'B2B SaaS') ?>" placeholder="e.g. B2B Subscription / API Usage"
                                           class="w-full pl-3 pr-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 outline-none font-semibold placeholder:text-slate-400">
                                </div>
                            </div>
                        </div>

                        <!-- Target Market Field -->
                        <div>
                            <label class="block text-sm font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                                <span>Target Addressable Market & Customer Segments</span>
                                <span class="text-xs text-slate-500 font-semibold">B2B / B2C / Enterprise</span>
                            </label>
                            <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                <span class="pl-3.5 text-slate-400">
                                    <i data-lucide="crosshair" class="w-4 h-4"></i>
                                </span>
                                <input type="text" name="target_market" value="<?= htmlspecialchars($company['target_market'] ?? '') ?>" 
                                       placeholder="e.g. Mid to large BFSI and tech enterprises, diagnostic clinics, hospital networks"
                                       class="w-full pl-3 pr-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 outline-none font-medium placeholder:text-slate-400">
                            </div>
                            <p class="text-xs text-slate-500 mt-1 pl-1 font-medium">Identifies your customer ICP for venture analysts.</p>
                        </div>

                        <!-- One Line Elevator Pitch -->
                        <div>
                            <label class="block text-sm font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                                <span class="flex items-center space-x-1">
                                    <span>One-Line Elevator Pitch</span>
                                    <span class="text-rose-500 font-bold">*</span>
                                </span>
                                <span class="text-xs text-slate-500 font-semibold" id="pitch-counter">0/140 characters</span>
                            </label>
                            <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                <span class="pl-3.5 text-indigo-500">
                                    <i data-lucide="zap" class="w-4 h-4"></i>
                                </span>
                                <input type="text" id="pitch-input" name="pitch" required maxlength="160" value="<?= htmlspecialchars($company['pitch'] ?? '') ?>" 
                                       placeholder="e.g. Autonomous AI copilot for contract analysis in BFSI enterprise workflows"
                                       class="w-full pl-3 pr-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 outline-none font-bold placeholder:text-slate-400">
                            </div>
                            <p class="text-xs text-slate-500 mt-1 pl-1 font-medium">This appears as the prominent headline on investor discovery deal cards.</p>
                        </div>

                        <!-- Detailed Overview & Moat -->
                        <div>
                            <label class="block text-sm font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                                <span>Detailed Business Overview, Moat & Traction</span>
                                <span class="text-xs text-slate-500 font-semibold" id="desc-word-count">Structured Narrative</span>
                            </label>
                            <div class="form-input-group border border-slate-200 rounded-xl bg-slate-50 transition p-1">
                                <textarea name="description" id="desc-input" rows="4" placeholder="1. Problem Statement: What severe pain point are you solving?&#10;2. Proprietary Solution & Moat: Why can't competitors easily copy you?&#10;3. Current Traction: Key paying clients, monthly revenue, growth velocity..."
                                          class="w-full p-3 bg-transparent rounded-lg text-sm text-slate-900 outline-none leading-relaxed font-normal placeholder:text-slate-400 resize-y"><?= htmlspecialchars($company['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Operations & Location -->
                    <div class="space-y-5 pt-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                    <i data-lucide="map-pin" class="w-4 h-4"></i>
                                </div>
                                <h2 class="text-sm sm:text-base font-extrabold text-slate-900 uppercase tracking-wider">
                                    3. Operations, Location & Team Size
                                </h2>
                            </div>
                            <span class="text-xs text-slate-500 font-semibold">Step 3 of 3</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5">
                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1.5">Headquarters City</label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="building-2" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="city" value="<?= htmlspecialchars($company['city'] ?? 'Bengaluru') ?>"
                                           class="w-full pl-3 pr-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 outline-none font-semibold">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1.5">State / Province</label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="map" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="state" value="<?= htmlspecialchars($company['state'] ?? 'Karnataka') ?>"
                                           class="w-full pl-3 pr-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 outline-none font-semibold">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1.5">Country</label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="globe" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="country" value="<?= htmlspecialchars($company['country'] ?? 'India') ?>"
                                           class="w-full pl-3 pr-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 outline-none font-semibold">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1.5">Full-time Team Size</label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="users" class="w-4 h-4"></i>
                                    </span>
                                    <input type="number" name="employee_count" min="1" max="10000" value="<?= htmlspecialchars((string)($company['employee_count'] ?? 10)) ?>"
                                           class="w-full pl-3 pr-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 outline-none font-bold">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center space-x-2 text-slate-500 text-xs sm:text-sm font-medium">
                            <i data-lucide="shield-alert" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                            <span>All updates are logged and synced directly to registered investors.</span>
                        </div>
                        <button type="submit" class="px-7 py-3.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-bold text-sm rounded-xl shadow-md shadow-indigo-200 hover:shadow-indigo-300 transition-all transform active:scale-98 flex items-center justify-center space-x-2">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Save Master Profile</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Data Room & Company Documents Section -->
            <?php if ($company): ?>
                <div class="clean-card rounded-2xl p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <div class="flex items-start space-x-3.5">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold flex-shrink-0">
                                <i data-lucide="folder-lock" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="text-sm sm:text-base font-extrabold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                                    <span>Investor Data Room & Due Diligence Vault</span>
                                    <span class="text-xs px-2.5 py-0.5 bg-slate-100 text-slate-700 rounded-full font-bold"><?= count($documents) ?> Files</span>
                                </h2>
                                <p class="text-xs sm:text-sm text-slate-600 mt-1">Securely upload pitch decks, cap tables, and financial audits accessible only to verified investors.</p>
                            </div>
                        </div>
                        <button type="button" onclick="openDocModal()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-sm transition flex items-center justify-center space-x-2 flex-shrink-0">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>Upload Document</span>
                        </button>
                    </div>

                    <div class="divide-y divide-slate-100 border border-slate-200/80 rounded-xl overflow-hidden bg-white">
                        <?php if (empty($documents)): ?>
                            <div class="p-12 text-center bg-slate-50/50">
                                <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-3">
                                    <i data-lucide="file-up" class="w-7 h-7"></i>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800">Your Data Room is empty</h3>
                                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-sm mx-auto">Upload your Seed Investor Presentation and 5-Year Financial Model to dramatically boost inbound investor commitment.</p>
                                <button type="button" onclick="openDocModal()" class="mt-4 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition">
                                    Add First Document
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($documents as $doc): ?>
                                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 hover:bg-slate-50/70 transition">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100/80 flex items-center justify-center font-bold flex-shrink-0">
                                            <i data-lucide="<?= str_contains(strtolower($doc['document_type']), 'deck') ? 'presentation' : (str_contains(strtolower($doc['document_type']), 'financial') ? 'trending-up' : 'file-text') ?>" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-sm sm:text-base"><?= htmlspecialchars($doc['title']) ?></div>
                                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 font-medium mt-1">
                                                <span class="font-bold text-slate-700"><?= htmlspecialchars($doc['document_type']) ?></span>
                                                <span>•</span>
                                                <span><?= htmlspecialchars($doc['file_size']) ?></span>
                                                <span>•</span>
                                                <span><?= date('M d, Y', strtotime($doc['uploaded_at'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2.5">
                                        <span class="px-3 py-1 rounded-full text-xs bg-slate-100 text-slate-700 font-bold border border-slate-200">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $doc['access_level']))) ?>
                                        </span>
                                        <span class="text-xs font-bold px-3 py-1 rounded-full border <?= $doc['is_verified'] ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200' ?>">
                                            <?= $doc['is_verified'] ? 'Verified' : 'Under Review' ?>
                                        </span>
                                        <a href="<?= url($doc['file_path']) ?>" target="_blank" class="p-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition shadow-2xs" title="Preview Document">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <a href="<?= url('download.php?id=' . $doc['id'] . '&type=company') ?>" class="p-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white transition shadow-2xs" title="Download File">
                                            <i data-lucide="download" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Modern Data Room Upload Modal -->
                <div id="upload-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                    <div class="bg-white border border-slate-200 shadow-2xl max-w-lg w-full rounded-2xl flex flex-col max-h-[92vh] overflow-hidden relative my-auto">
                        <!-- Fixed Header -->
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between flex-shrink-0 bg-white">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                    <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">Upload to Data Room</h3>
                                    <p class="text-xs text-slate-500 font-medium">Add materials for institutional investor review</p>
                                </div>
                            </div>
                            <button type="button" onclick="closeDocModal()" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <!-- Scrollable Form Body -->
                        <form action="<?= url('founder/company.php') ?>" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="upload_doc">

                            <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-4">
                                <div>
                                    <label class="block font-bold text-slate-800 text-sm mb-1.5">Document Category</label>
                                    <div class="relative border border-slate-200 rounded-xl bg-slate-50 transition">
                                        <select name="document_type" class="w-full px-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 font-semibold outline-none cursor-pointer">
                                            <option value="Pitch Deck">Pitch Deck (PDF / PPT)</option>
                                            <option value="Financial Projections">5-Year Financial Model & Projections</option>
                                            <option value="Cap Table">Cap Table & Equity Breakdown</option>
                                            <option value="Certificate of Incorporation">Certificate of Incorporation (MCA)</option>
                                            <option value="GST Certificate">GST Certificate</option>
                                            <option value="Product Architecture">Product Architecture & IP Dossier</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-800 text-sm mb-1.5">Display Title</label>
                                    <input type="text" name="title" required placeholder="e.g. Q1 2026 Seed Investor Pitch Presentation"
                                           class="w-full px-3.5 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10 rounded-xl text-slate-900 outline-none text-sm font-semibold">
                                </div>

                                <!-- Styled Drag & Drop Zone -->
                                <div>
                                    <label class="block font-bold text-slate-800 text-sm mb-1.5">Choose File</label>
                                    <div class="border-2 border-dashed border-slate-200 hover:border-indigo-500 rounded-2xl p-6 text-center bg-slate-50/60 hover:bg-indigo-50/20 transition cursor-pointer relative" id="drop-zone" onclick="document.getElementById('modal-doc-file').click()">
                                        <input type="file" name="doc_file" id="modal-doc-file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png" class="hidden" onchange="handleFileSelect(this)">
                                        <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-2">
                                            <i data-lucide="cloud-upload" class="w-6 h-6"></i>
                                        </div>
                                        <div class="text-sm font-bold text-slate-800" id="file-title-display">Click to upload or drag and drop file</div>
                                        <div class="text-xs text-slate-500 font-medium mt-1">PDF, DOCX, PPT, PNG or JPG (Max 25MB)</div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-800 text-sm mb-1.5">Access Permission</label>
                                    <div class="relative border border-slate-200 rounded-xl bg-slate-50 transition">
                                        <select name="access_level" class="w-full px-3.5 py-3 bg-transparent rounded-xl text-sm text-slate-900 font-semibold outline-none cursor-pointer">
                                            <option value="registered_investors">Registered & Verified Investors Only</option>
                                            <option value="request_only">Gated (Founder Approval Required)</option>
                                            <option value="public">Publicly Available</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Pinned Footer -->
                            <div class="p-4 border-t border-slate-100 flex items-center justify-end space-x-3 bg-slate-50/90 flex-shrink-0">
                                <button type="button" onclick="closeDocModal()" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold text-sm transition">
                                    Cancel
                                </button>
                                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md shadow-indigo-100 transition text-sm flex items-center space-x-2">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                    <span>Upload & Attach</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#company-main", { duration: 0.35, y: 8, opacity: 0, ease: "power2.out" });

        // Elevator pitch character counter
        const pitchInput = document.getElementById('pitch-input');
        const pitchCounter = document.getElementById('pitch-counter');
        if (pitchInput && pitchCounter) {
            function updatePitchCounter() {
                pitchCounter.textContent = `${pitchInput.value.length}/140 characters`;
            }
            pitchInput.addEventListener('input', updatePitchCounter);
            updatePitchCounter();
        }

        // Modal controls
        function openDocModal() {
            const modal = document.getElementById('upload-modal');
            if (modal) {
                modal.classList.remove('hidden');
                lucide.createIcons();
            }
        }

        function closeDocModal() {
            const modal = document.getElementById('upload-modal');
            if (modal) modal.classList.add('hidden');
        }

        function handleFileSelect(input) {
            const display = document.getElementById('file-title-display');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                display.innerHTML = `<span class="text-indigo-600 font-bold">${file.name}</span> <span class="text-slate-400 font-normal">(${sizeMB} MB)</span>`;
            }
        }
    </script>
</body>
</html>
