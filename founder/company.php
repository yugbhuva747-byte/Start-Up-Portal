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

        // Quick Auto Upload from Gallery / Camera click on Company Logo
        if ($action === 'quick_upload_logo' && $company) {
            if (!empty($_FILES['company_logo']['name'])) {
                $logoRes = handle_company_logo_upload($_FILES['company_logo'], (int) $company['id']);
                if ($logoRes['success']) {
                    $u = $db->prepare("UPDATE companies SET logo_url = ? WHERE id = ?");
                    $u->execute([$logoRes['url'], $company['id']]);
                    log_audit($user['id'], 'UPDATE_COMPANY_LOGO', 'companies', $company['id'], 'Founder updated company logo from gallery');
                    set_flash('success', 'Company logo picture updated successfully!');
                } else {
                    set_flash('error', $logoRes['error'] ?? 'Logo upload failed.');
                }
            } else {
                set_flash('error', 'Please select an image file to upload as company logo.');
            }
            header('Location: ' . url('founder/company.php'));
            exit;
        }

        // Quick Auto Upload for Cover Banner
        if ($action === 'quick_upload_banner' && $company) {
            if (!empty($_FILES['company_banner']['name'])) {
                $bannerRes = handle_company_banner_upload($_FILES['company_banner'], (int) $company['id']);
                if ($bannerRes['success']) {
                    $u = $db->prepare("UPDATE companies SET banner_url = ? WHERE id = ?");
                    $u->execute([$bannerRes['url'], $company['id']]);
                    log_audit($user['id'], 'UPDATE_COMPANY_BANNER', 'companies', $company['id'], 'Founder updated company cover banner');
                    set_flash('success', 'Company cover banner updated successfully!');
                } else {
                    set_flash('error', $bannerRes['error'] ?? 'Banner upload failed.');
                }
            } else {
                set_flash('error', 'Please select an image file for company cover banner.');
            }
            header('Location: ' . url('founder/company.php'));
            exit;
        }

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

            $logoUrl = trim($_POST['logo_url'] ?? ($company['logo_url'] ?? ''));
            if (!empty($_FILES['company_logo']['name']) && $company) {
                $logoRes = handle_company_logo_upload($_FILES['company_logo'], (int) $company['id']);
                if ($logoRes['success']) {
                    $logoUrl = $logoRes['url'];
                }
            }

            $bannerUrl = trim($_POST['banner_url'] ?? ($company['banner_url'] ?? ''));
            if (!empty($_FILES['company_banner']['name']) && $company) {
                $bannerRes = handle_company_banner_upload($_FILES['company_banner'], (int) $company['id']);
                if ($bannerRes['success']) {
                    $bannerUrl = $bannerRes['url'];
                }
            }

            // Fallback for required non-null fields to allow partial section saves
            if (empty($name)) {
                $name = $company['name'] ?? 'My Startup';
            }
            if (empty($pitch)) {
                $pitch = !empty($company['pitch']) ? $company['pitch'] : 'Innovative venture building high-impact technology solutions.';
            }
            if (empty($description)) {
                $description = !empty($company['description']) ? $company['description'] : 'Company overview, business model and market strategy.';
            }

            if ($company) {
                $uStmt = $db->prepare("
                    UPDATE companies SET name = ?, legal_name = ?, cin_number = ?, industry = ?, stage = ?,
                    business_model = ?, pitch = ?, description = ?, target_market = ?, employee_count = ?,
                    website = ?, city = ?, state = ?, country = ?, logo_url = ?, banner_url = ?
                    WHERE id = ?
                ");
                $uStmt->execute([$name, $legalName, $cin, $industry, $stage, $businessModel, $pitch, $description, $targetMarket, $employeeCount, $website, $city, $state, $country, $logoUrl, $bannerUrl, $company['id']]);

                log_audit($user['id'], 'UPDATE_COMPANY_PROFILE', 'companies', $company['id'], 'Founder updated company profile');
                set_flash('success', 'Company profile updated successfully.');
            } else {
                $iStmt = $db->prepare("
                    INSERT INTO companies (name, legal_name, cin_number, industry, stage, business_model, pitch, description, target_market, employee_count, website, city, state, country, logo_url, banner_url, verified_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $iStmt->execute([$name, $legalName, $cin, $industry, $stage, $businessModel, $pitch, $description, $targetMarket, $employeeCount, $website, $city, $state, $country, $logoUrl, $bannerUrl]);
                $newCompId = $db->lastInsertId();

                if (!empty($_FILES['company_logo']['name'])) {
                    $logoRes = handle_company_logo_upload($_FILES['company_logo'], (int) $newCompId);
                    if ($logoRes['success']) {
                        $db->prepare("UPDATE companies SET logo_url = ? WHERE id = ?")->execute([$logoRes['url'], $newCompId]);
                    }
                }

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
        } elseif ($action === 'delete_doc' && $company) {
            $docId = (int) ($_POST['doc_id'] ?? 0);
            if ($docId > 0) {
                $docQuery = $db->prepare("SELECT * FROM company_documents WHERE id = ? AND company_id = ?");
                $docQuery->execute([$docId, $company['id']]);
                $docToDelete = $docQuery->fetch();
                if ($docToDelete) {
                    if (!empty($docToDelete['file_path'])) {
                        $fullFilePath = ROOT_PATH . '/' . ltrim($docToDelete['file_path'], '/\\');
                        if (file_exists($fullFilePath)) {
                            @unlink($fullFilePath);
                        }
                    }
                    $delStmt = $db->prepare("DELETE FROM company_documents WHERE id = ? AND company_id = ?");
                    $delStmt->execute([$docId, $company['id']]);
                    log_audit($user['id'], 'DELETE_DOCUMENT', 'company_documents', $docId, "Deleted document: " . ($docToDelete['title'] ?? ''));
                    set_flash('success', 'Document deleted successfully from data room.');
                }
            }
            header('Location: ' . url('founder/company.php#company-data-room'));
            exit;
        } elseif ($action === 'add_award' && $company) {
            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? 'Industry Award');
            $issuer = trim($_POST['issuer'] ?? '');
            $awardYear = trim($_POST['award_year'] ?? date('Y'));
            $description = trim($_POST['description'] ?? '');
            $verificationLink = trim($_POST['verification_link'] ?? '');
            $imageUrl = trim($_POST['image_url'] ?? '');

            // Handle file upload
            if (!empty($_FILES['award_file']['name'])) {
                $upRes = handle_award_upload($_FILES['award_file'], $company['id']);
                if (!empty($upRes['success'])) {
                    $imageUrl = $upRes['url'];
                } elseif (!empty($upRes['error'])) {
                    $error = $upRes['error'];
                }
            }

            if (empty($title)) {
                $title = 'Certificate of Recognition';
            }
            if (empty($category)) {
                $category = 'Award & Recognition';
            }

            if (empty($imageUrl) && empty($error)) {
                $error = 'Please choose a certificate/award photo file or paste an image URL.';
            }

            if (empty($error)) {
                $ins = $db->prepare("
                    INSERT INTO company_awards (company_id, title, category, issuer, award_year, description, image_url, verification_link, is_featured)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $ins->execute([$company['id'], $title, $category, $issuer, $awardYear, $description, $imageUrl, $verificationLink]);
                $newAwardId = $db->lastInsertId();

                log_audit($user['id'], 'ADD_COMPANY_AWARD', 'company_awards', $newAwardId, "Added certificate photo: $title");
                set_flash('success', 'Certificate photo successfully framed and uploaded.');
                header('Location: ' . url('founder/company.php#sec-awards'));
                exit;
            }
        } elseif ($action === 'delete_award' && $company) {
            $awardId = (int) ($_POST['award_id'] ?? 0);
            if ($awardId > 0) {
                $aQuery = $db->prepare("SELECT * FROM company_awards WHERE id = ? AND company_id = ?");
                $aQuery->execute([$awardId, $company['id']]);
                $awardToDelete = $aQuery->fetch();
                if ($awardToDelete) {
                    if (!empty($awardToDelete['image_url']) && str_contains($awardToDelete['image_url'], 'uploads/awards/')) {
                        $parsedPath = parse_url($awardToDelete['image_url'], PHP_URL_PATH);
                        $pos = strpos($parsedPath, 'uploads/awards/');
                        if ($pos !== false) {
                            $localRel = substr($parsedPath, $pos);
                            $localFile = ROOT_PATH . '/' . $localRel;
                            if (file_exists($localFile)) {
                                @unlink($localFile);
                            }
                        }
                    }
                    $db->prepare("DELETE FROM company_awards WHERE id = ? AND company_id = ?")->execute([$awardId, $company['id']]);
                    log_audit($user['id'], 'DELETE_COMPANY_AWARD', 'company_awards', $awardId, "Deleted trust award: " . ($awardToDelete['title'] ?? ''));
                    set_flash('success', 'Award/trust item successfully deleted.');
                }
            }
            header('Location: ' . url('founder/company.php#sec-awards'));
            exit;
        }
    }
}

// Load documents and awards AFTER POST handling
$awards = [];
$documents = [];
if ($db && $company) {
    try {
        $dStmt = $db->prepare("SELECT * FROM company_documents WHERE company_id = ? ORDER BY uploaded_at DESC");
        $dStmt->execute([$company['id']]);
        $documents = $dStmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        $documents = [];
    }

    try {
        $aStmt = $db->prepare("SELECT * FROM company_awards WHERE company_id = ? ORDER BY is_featured DESC, created_at DESC");
        $aStmt->execute([$company['id']]);
        $awards = $aStmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        $awards = [];
    }
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
    'documents' => !empty($documents),
    'awards' => !empty($awards)
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
    'success' => ['bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800', 'check-circle-2', 'text-emerald-600 dark:text-emerald-400'],
    'info' => ['bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800', 'info', 'text-blue-600 dark:text-blue-400'],
    'error' => ['bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800', 'alert-circle', 'text-rose-600 dark:text-rose-400'],
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
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 0.35rem;
        }

        .dark .form-label-clean,
        html.dark .form-label-clean {
            color: #E2E8F0 !important;
        }

        .form-hint {
            font-size: 0.75rem;
            color: #64748B;
            margin-top: 0.3rem;
            line-height: 1.25rem;
        }

        .dark .form-hint,
        html.dark .form-hint {
            color: #94A3B8 !important;
        }

        /* Cover Banner Pattern - LinkedIn style */
        .cover-banner-pattern {
            background: linear-gradient(135deg, #1E1B4B 0%, #312E81 35%, #4338CA 70%, #6366F1 100%);
            position: relative;
            overflow: hidden;
        }

        .cover-banner-pattern::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.15) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.4;
        }

        /* Cards clean styling */
        .section-card {
            background-color: #FFFFFF;
            color: #0F172A;
            border: 1px solid #E2E8F0;
            border-radius: 1.25rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            transition: all 0.2s ease;
        }

        .dark .section-card,
        html.dark .section-card {
            background-color: #111827 !important;
            color: #F8FAFC !important;
            border-color: #1E293B !important;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.5) !important;
        }
    </style>
</head>

<body
    class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-5 space-y-5" id="company-main">

            <!-- Flash Notification -->
            <?php if ($flash):
                $fs = $flashStyles[$flash['type']] ?? $flashStyles['info']; ?>
                <div id="flash-alert"
                    class="px-4 py-3 rounded-2xl text-xs sm:text-sm font-semibold border <?= $fs[0] ?> flex items-center justify-between gap-3 shadow-xs transition-all duration-300">
                    <div class="flex items-center gap-2.5">
                        <i data-lucide="<?= $fs[1] ?>" class="w-4 h-4 flex-shrink-0 <?= $fs[2] ?>"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <button type="button" onclick="document.getElementById('flash-alert')?.remove()"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition cursor-pointer"
                        title="Dismiss">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div
                    class="px-4 py-3 rounded-2xl text-xs sm:text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 flex items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 text-rose-600"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                    <button type="button" onclick="this.parentElement?.remove()"
                        class="p-1 rounded-lg text-rose-400 hover:text-rose-600 transition cursor-pointer">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Sleek Compact Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                <div>
                    <!-- Clean Breadcrumb -->
                    <nav class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium mb-1">
                        <a href="<?= url('founder/dashboard.php') ?>"
                            class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Founder Portal</a>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span class="text-slate-900 dark:text-slate-200 font-semibold">Company Profile</span>
                    </nav>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                        <?= !empty($company['name']) ? htmlspecialchars($company['name']) : 'Company Master Profile' ?>
                    </h1>
                </div>

                <!-- Right Action Buttons -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <?php if ($company):
                        $publicCompUrl = url('company.php?id=' . hash_id_encode($company['id']));
                        ?>
                        <!-- Preview Public Page Link -->
                        <a href="<?= htmlspecialchars($publicCompUrl) ?>" target="_blank" rel="noopener"
                            class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                            <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span>View Public Page</span>
                        </a>

                        <!-- Share Public Modal -->
                        <button type="button"
                            onclick="openShareModal('<?= htmlspecialchars($publicCompUrl) ?>', '<?= htmlspecialchars(addslashes($company['name'])) ?>')"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm shadow-indigo-500/20 active:scale-95 cursor-pointer">
                            <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                            <span>Share Profile</span>
                        </button>
                    <?php endif; ?>
                    <a href="#company-data-room"
                        class="px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/50 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition flex items-center space-x-1.5 border border-indigo-200 dark:border-indigo-800 shadow-2xs">
                        <i data-lucide="folder-lock" class="w-3.5 h-3.5"></i>
                        <span>Data Room (<?= count($documents) ?>)</span>
                    </a>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- LINKEDIN-STYLE COMPANY HERO & COVER BANNER CARD -->
            <!-- ======================================================== -->
            <div
                class="section-card rounded-2xl overflow-hidden relative shadow-sm border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#111827]">

                <!-- LinkedIn Cover Banner -->
                <div id="company-hero-banner-container"
                    class="h-44 sm:h-56 <?= !empty($company['banner_url']) ? 'bg-slate-900' : 'cover-banner-pattern' ?> relative group overflow-hidden">
                    <?php if (!empty($company['banner_url'])): ?>
                        <img id="company-hero-banner-img"
                            src="<?= htmlspecialchars(get_company_banner_url($company['banner_url'])) ?>"
                            alt="Company Banner" class="w-full h-full object-cover object-center">
                    <?php else: ?>
                        <img id="company-hero-banner-img" src="" alt="Company Banner"
                            class="w-full h-full object-cover object-center hidden">
                    <?php endif; ?>

                    <!-- Cover Banner Action Badges -->
                    <div class="absolute top-4 right-4 z-10 flex items-center gap-2">
                        <!-- Quick Banner Change Button -->
                        <button type="button" onclick="triggerCompanyBannerUpload()"
                            class="px-3.5 py-1.5 rounded-xl bg-black/60 hover:bg-black/80 backdrop-blur-md text-white text-xs font-bold border border-white/25 flex items-center gap-1.5 shadow-md transition active:scale-95 cursor-pointer"
                            title="Change Cover Banner">
                            <i data-lucide="image" class="w-3.5 h-3.5"></i>
                            <span class="hidden sm:inline">Change Cover Banner</span>
                        </button>

                        <span
                            class="px-3.5 py-1.5 rounded-xl bg-black/60 backdrop-blur-md text-white text-xs font-bold border border-white/25 flex items-center gap-1.5 shadow-md">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>Verified Company Page</span>
                        </span>
                    </div>
                </div>

                <!-- Company Details & Overlapping Logo Section -->
                <div class="px-6 sm:px-10 pb-8 sm:pb-9 pt-0 relative">
                    <div
                        class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 sm:gap-8 border-b border-slate-100 dark:border-slate-800/80 pb-7 sm:pb-8">

                        <!-- Left: Overlapping Logo & Identity -->
                        <div class="flex flex-col sm:flex-row sm:items-end gap-6">

                            <!-- Company Logo Box (LinkedIn Style Square with Rounded Corners + Camera Upload) -->
                            <div class="relative -mt-16 sm:-mt-22 flex-shrink-0">
                                <div class="relative group cursor-pointer" onclick="triggerCompanyLogoUpload()"
                                    title="Click to add / change company picture from gallery">
                                    <div
                                        class="w-32 h-32 sm:w-36 sm:h-36 rounded-2xl p-1 bg-white dark:bg-[#111827] border-4 border-white dark:border-[#111827] shadow-xl overflow-hidden flex items-center justify-center relative">
                                        <img id="company-hero-logo-img"
                                            src="<?= !empty($company['logo_url']) ? htmlspecialchars(get_company_logo_url($company['logo_url'])) : '' ?>"
                                            alt="<?= htmlspecialchars($company['name'] ?? 'Startup') ?>"
                                            class="w-full h-full object-cover object-center rounded-xl transition duration-300 group-hover:scale-105 <?= empty($company['logo_url']) ? 'hidden' : '' ?>">

                                        <?php if (empty($company['logo_url'])): ?>
                                            <div id="company-hero-logo-fallback"
                                                class="w-full h-full rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-600 text-white font-extrabold text-3xl sm:text-4xl flex items-center justify-center shadow-inner">
                                                <?= htmlspecialchars($companyInitials) ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Hover darken overlay indicating click to upload from gallery -->
                                        <div
                                            class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-xl flex flex-col items-center justify-center text-white text-xs font-bold gap-1.5 text-center px-2">
                                            <i data-lucide="camera" class="w-7 h-7"></i>
                                            <span>Update Logo</span>
                                        </div>
                                    </div>

                                    <!-- Camera Action Floating Badge -->
                                    <div class="absolute -bottom-1.5 -right-1.5 w-10 h-10 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white border-2 border-white dark:border-[#111827] flex items-center justify-center shadow-lg transition transform group-hover:scale-110 active:scale-95"
                                        title="Add / Change Company Picture from Gallery">
                                        <i data-lucide="camera" class="w-4 h-4"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Headline & Name Info -->
                            <div class="pt-2 sm:pt-0 space-y-3 min-w-0">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h1
                                        class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                                        <?= htmlspecialchars($company['name'] ?? 'Your Startup Name') ?>
                                    </h1>
                                    <?php if (!empty($company['legal_name'])): ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 text-xs sm:text-sm px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold border border-slate-200/80 dark:border-slate-700/80 shadow-2xs">
                                            <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <?= htmlspecialchars($company['legal_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Pitch / Tagline (Larger & More Breathable) -->
                                <p
                                    class="text-sm sm:text-base text-slate-700 dark:text-slate-200 font-normal leading-relaxed max-w-3xl">
                                    <?= !empty($company['pitch']) ? htmlspecialchars($company['pitch']) : '<span class="italic text-slate-400">Add an elevator pitch below to summarize your value proposition to investors.</span>' ?>
                                </p>

                                <!-- Modern Icon Badges (Clean, Visual, High Readability) -->
                                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                    <?php if (!empty($company['industry'])): ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50/90 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-semibold border border-indigo-200/80 dark:border-indigo-800/80 text-xs sm:text-sm shadow-2xs">
                                            <i data-lucide="briefcase" class="w-4 h-4 text-indigo-500"></i>
                                            <span><?= htmlspecialchars($company['industry']) ?></span>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($company['stage'])): ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 font-semibold border border-amber-200/80 dark:border-amber-800/80 text-xs sm:text-sm shadow-2xs">
                                            <i data-lucide="trending-up" class="w-4 h-4 text-amber-500"></i>
                                            <span><?= htmlspecialchars($company['stage']) ?></span>
                                        </span>
                                    <?php endif; ?>

                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100/90 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 font-semibold border border-slate-200/80 dark:border-slate-700/80 text-xs sm:text-sm shadow-2xs">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-rose-500"></i>
                                        <span><?= htmlspecialchars($company['city'] ?? 'Bengaluru') ?><?= !empty($company['country']) ? ', ' . htmlspecialchars($company['country']) : '' ?></span>
                                    </span>

                                    <?php if (!empty($company['employee_count'])): ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100/90 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 font-semibold border border-slate-200/80 dark:border-slate-700/80 text-xs sm:text-sm shadow-2xs">
                                            <i data-lucide="users" class="w-4 h-4 text-cyan-500"></i>
                                            <span><?= htmlspecialchars($company['employee_count']) ?>+ Team</span>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($company['website'])): ?>
                                        <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 hover:bg-blue-100/90 dark:hover:bg-blue-900/50 font-semibold border border-blue-200/80 dark:border-blue-800/70 text-xs sm:text-sm transition shadow-2xs group">
                                            <i data-lucide="globe"
                                                class="w-4 h-4 text-blue-500 group-hover:rotate-12 transition-transform"></i>
                                            <span><?= htmlspecialchars(preg_replace('#^https?://#', '', rtrim($company['website'], '/'))) ?></span>
                                            <i data-lucide="external-link"
                                                class="w-3.5 h-3.5 text-blue-400 group-hover:translate-x-0.5 transition-transform"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Profile Strength Meter + Gallery Upload Action -->
                        <div class="lg:w-80 flex flex-col justify-between gap-3.5 flex-shrink-0">
                            <div
                                class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-4 sm:p-5 border border-slate-200/90 dark:border-slate-700/80 shadow-xs">
                                <div class="flex items-center justify-between mb-2">
                                    <span
                                        class="font-bold text-slate-700 dark:text-slate-300 text-xs sm:text-sm flex items-center gap-1.5">
                                        <i data-lucide="sparkles"
                                            class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                        Profile Strength
                                    </span>
                                    <span
                                        class="font-black text-indigo-600 dark:text-indigo-400 text-base sm:text-lg"><?= $profileStrength ?>%</span>
                                </div>
                                <div
                                    class="w-full bg-slate-200 dark:bg-slate-700 h-2.5 rounded-full overflow-hidden mb-2">
                                    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-full rounded-full transition-all duration-700"
                                        style="width: <?= $profileStrength ?>%"></div>
                                </div>
                                <div
                                    class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-medium">
                                    <span><?= $completedChecks ?> of <?= count($checks) ?> fields ready</span>
                                    <span
                                        class="<?= $profileStrength >= 80 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-amber-600 dark:text-amber-400 font-medium' ?>">
                                        <?= $profileStrength >= 80 ? '<span class="inline-flex items-center gap-1"><i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Investor Ready</span>' : 'Details Pending' ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Direct Click Button to Add / Change Picture from Gallery -->
                            <button type="button" onclick="triggerCompanyLogoUpload()"
                                class="w-full py-3 px-4 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-xs sm:text-sm font-bold border border-indigo-200 dark:border-indigo-800 transition flex items-center justify-center gap-2 cursor-pointer shadow-xs active:scale-98">
                                <i data-lucide="image-plus" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                <span>Add / Change Company Picture</span>
                            </button>
                        </div>
                    </div>

                    <!-- Navigation Jump Bar -->
                    <div
                        class="mt-4 pt-3.5 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <span class="text-slate-400 font-bold mr-1 text-[10.5px] uppercase tracking-wider">Quick
                            Jump:</span>
                        <a href="#sec-identity"
                            class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:border-indigo-300 hover:text-indigo-600 dark:hover:text-indigo-400 shadow-2xs transition flex items-center gap-1">
                            <i data-lucide="building" class="w-3 h-3 text-slate-400"></i>
                            <span>1. Corporate Identity</span>
                        </a>
                        <a href="#sec-market"
                            class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:border-indigo-300 hover:text-indigo-600 dark:hover:text-indigo-400 shadow-2xs transition flex items-center gap-1">
                            <i data-lucide="pie-chart" class="w-3 h-3 text-slate-400"></i>
                            <span>2. Market &amp; Model</span>
                        </a>
                        <a href="#sec-pitch"
                            class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:border-indigo-300 hover:text-indigo-600 dark:hover:text-indigo-400 shadow-2xs transition flex items-center gap-1">
                            <i data-lucide="message-square" class="w-3 h-3 text-slate-400"></i>
                            <span>3. Pitch &amp; Traction</span>
                        </a>
                        <a href="#sec-team"
                            class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:border-indigo-300 hover:text-indigo-600 dark:hover:text-indigo-400 shadow-2xs transition flex items-center gap-1">
                            <i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i>
                            <span>4. Headquarters &amp; Team</span>
                        </a>
                        <a href="#company-data-room"
                            class="px-2.5 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 text-xs font-bold hover:bg-indigo-100 dark:hover:bg-indigo-900/60 shadow-2xs transition flex items-center gap-1">
                            <i data-lucide="lock" class="w-3 h-3 text-indigo-500"></i>
                            <span>5. Confidential Data Room</span>
                        </a>
                    </div>
                </div>

                <!-- Profile Form -->
                <form id="company-profile-form" action="<?= url('founder/company.php') ?>" method="POST"
                    enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="update_company">

                    <!-- SECTION 1 -->
                    <div id="sec-identity"
                        class="section-card p-6 sm:p-7 space-y-6 scroll-mt-24 transition-all duration-200">
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
                                    <p class="text-sm text-slate-500 mt-0.5">Foundational legal incorporation and
                                        commercial
                                        branding details.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="text-xs sm:text-sm font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600">Required
                                    for KYC</span>
                                <button type="submit"
                                    class="section-save-btn px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                    <span>Save Section 1</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                            <div>
                                <label class="form-label-clean"><span>Startup Brand Name <span
                                            class="text-rose-500">*</span></span></label>
                                <input type="text" name="name" required
                                    value="<?= htmlspecialchars($company['name'] ?? '') ?>"
                                    placeholder="e.g. TechPulse AI" class="form-input-clean">
                                <p class="form-hint">The primary commercial brand name presented in discovery
                                    catalogues.
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
                                <p class="form-hint">The official registered company name shown on investment term
                                    sheets.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6 pt-1">
                            <div>
                                <label class="form-label-clean">
                                    <span>Corporate CIN / Registration Number</span>
                                    <span class="text-xs text-slate-500 font-normal">MCA 21-digit</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="cin_number"
                                        value="<?= htmlspecialchars($company['cin_number'] ?? '') ?>"
                                        placeholder="U72900KA2023PTC123456"
                                        class="form-input-clean font-mono uppercase tracking-wide pr-10">
                                    <i data-lucide="hash"
                                        class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2"></i>
                                </div>
                                <p class="form-hint">21-character Corporate Identification Number issued by the Ministry
                                    of
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
                                <p class="form-hint">Public domain address of your active product or marketing website.
                                </p>
                            </div>
                        </div>

                        <!-- Company Logo & Banner Media Section -->
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <!-- Company Logo Upload & Preview -->
                            <div
                                class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/80 space-y-3">
                                <label class="form-label-clean">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="image" class="w-4 h-4 text-indigo-600"></i>
                                        <span>Company Logo / Picture</span>
                                    </span>
                                    <span class="text-xs text-slate-500 font-normal">PNG, JPG, WEBP (Max 10MB)</span>
                                </label>

                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-16 h-16 rounded-xl bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 p-1 flex-shrink-0 shadow-sm flex items-center justify-center overflow-hidden">
                                        <img id="sec1-logo-preview"
                                            src="<?= htmlspecialchars(get_company_logo_url($company['logo_url'] ?? '')) ?>"
                                            alt="Company Logo"
                                            class="w-full h-full object-cover object-center rounded-lg">
                                    </div>
                                    <div class="flex-1 space-y-2">
                                        <label for="sec1-logo-file"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-200 dark:border-indigo-800 transition cursor-pointer">
                                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                            <span>Choose from Gallery / Files</span>
                                        </label>
                                        <input type="file" id="sec1-logo-file" name="company_logo" accept="image/*"
                                            class="hidden" onchange="previewSec1Logo(this)">
                                        <p id="sec1-logo-filename" class="text-[11px] text-slate-500 truncate">Select
                                            file or enter URL below</p>
                                    </div>
                                </div>

                                <div>
                                    <input type="url" name="logo_url" id="sec1-logo-url"
                                        value="<?= htmlspecialchars($company['logo_url'] ?? '') ?>"
                                        placeholder="https://example.com/logo.png"
                                        class="form-input-clean text-xs font-mono"
                                        oninput="previewSec1LogoUrl(this.value)">
                                </div>
                            </div>

                            <!-- Company Cover Banner Upload & Preview -->
                            <div
                                class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/80 space-y-3">
                                <label class="form-label-clean">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="layout-template" class="w-4 h-4 text-indigo-600"></i>
                                        <span>Company Cover Banner (LinkedIn Style)</span>
                                    </span>
                                    <span class="text-xs text-slate-500 font-normal">PNG, JPG (Max 12MB)</span>
                                </label>

                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-24 h-14 rounded-xl bg-slate-100 dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 flex-shrink-0 shadow-sm overflow-hidden relative">
                                        <?php if (!empty($company['banner_url'])): ?>
                                            <img id="sec1-banner-preview"
                                                src="<?= htmlspecialchars(get_company_banner_url($company['banner_url'])) ?>"
                                                alt="Banner Preview" class="w-full h-full object-cover object-center">
                                        <?php else: ?>
                                            <div id="sec1-banner-preview-fallback"
                                                class="w-full h-full flex items-center justify-center text-[10px] text-slate-400 font-bold uppercase bg-slate-200 dark:bg-slate-800">
                                                Default
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 space-y-2">
                                        <label for="sec1-banner-file"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-200 dark:border-indigo-800 transition cursor-pointer">
                                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                            <span>Choose Banner File</span>
                                        </label>
                                        <input type="file" id="sec1-banner-file" name="company_banner" accept="image/*"
                                            class="hidden" onchange="previewSec1Banner(this)">
                                        <p id="sec1-banner-filename" class="text-[11px] text-slate-500 truncate">Select
                                            file or enter URL below</p>
                                    </div>
                                </div>

                                <div>
                                    <input type="url" name="banner_url" id="sec1-banner-url"
                                        value="<?= htmlspecialchars($company['banner_url'] ?? '') ?>"
                                        placeholder="https://example.com/cover-banner.jpg"
                                        class="form-input-clean text-xs font-mono"
                                        oninput="previewSec1BannerUrl(this.value)">
                                </div>
                            </div>
                        </div>

                        <!-- Section 1 Card Footer Save Bar -->
                        <div class="border-t border-slate-100 pt-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
                                <span>Corporate & Legal Identity details</span>
                            </div>
                            <button type="submit"
                                class="section-save-btn px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm transition active:scale-95 flex items-center gap-2 cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Save Section 1</span>
                            </button>
                        </div>
                    </div>

                    <!-- SECTION 2 -->
                    <div id="sec-market"
                        class="section-card p-6 sm:p-7 space-y-6 scroll-mt-24 transition-all duration-200">
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
                                    <p class="text-sm text-slate-500 mt-0.5">Classification filters used by syndicate
                                        leads
                                        and venture funds.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="text-xs sm:text-sm font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600">Discovery
                                    Filters</span>
                                <button type="submit"
                                    class="section-save-btn px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                    <span>Save Section 2</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 sm:gap-6">
                            <div>
                                <label class="form-label-clean">
                                    <span>Industry / Sector <span class="text-rose-500">*</span></span>
                                    <span class="text-xs text-indigo-600 dark:text-indigo-400 font-normal">Custom
                                        text</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="industry" required list="industry-suggestions"
                                        value="<?= htmlspecialchars($company['industry'] ?? '') ?>"
                                        placeholder="e.g. Media Production, Music, AI/SaaS, FinTech"
                                        class="form-input-clean pr-10">
                                    <i data-lucide="briefcase"
                                        class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2"></i>
                                </div>
                                <datalist id="industry-suggestions">
                                    <option value="Media & Entertainment">
                                    <option value="Music & Audio Production">
                                    <option value="Film & Video Production">
                                    <option value="AI / Generative Tech">
                                    <option value="SaaS & Cloud Software">
                                    <option value="FinTech & Banking">
                                    <option value="HealthTech & Biotech">
                                    <option value="E-Commerce & D2C">
                                    <option value="EdTech & Learning">
                                    <option value="DeepTech & Hardware">
                                    <option value="CleanTech & Energy">
                                    <option value="Logistics & Supply Chain">
                                    <option value="Cybersecurity">
                                    <option value="Gaming & Esports">
                                    <option value="Consumer Tech">
                                </datalist>
                                <p class="form-hint">Type your exact industry or sector. You can write any custom
                                    domain.</p>
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

                        <!-- Section 2 Card Footer Save Bar -->
                        <div class="border-t border-slate-100 pt-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <i data-lucide="layers" class="w-4 h-4 text-indigo-500"></i>
                                <span>Sector, Stage & Market Model details</span>
                            </div>
                            <button type="submit"
                                class="section-save-btn px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm transition active:scale-95 flex items-center gap-2 cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Save Section 2</span>
                            </button>
                        </div>
                    </div>

                    <!-- SECTION 3 -->
                    <div id="sec-pitch"
                        class="section-card p-6 sm:p-7 space-y-6 scroll-mt-24 transition-all duration-200">
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
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="text-xs sm:text-sm font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600">Core
                                    Narrative</span>
                                <button type="submit"
                                    class="section-save-btn px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                    <span>Save Section 3</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="form-label-clean">
                                <span>One-Line Elevator Pitch</span>
                                <span class="text-xs text-indigo-600 font-semibold">Keep it punchy & clear</span>
                            </label>
                            <input type="text" name="pitch" value="<?= htmlspecialchars($company['pitch'] ?? '') ?>"
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
                            <p class="form-hint">Provide investors with crisp insights into your customer economics,
                                product
                                roadmap, and unfair advantages.</p>
                        </div>

                        <!-- Section 3 Card Footer Save Bar -->
                        <div class="border-t border-slate-100 pt-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <i data-lucide="sparkles" class="w-4 h-4 text-amber-500"></i>
                                <span>Pitch & Traction Story details</span>
                            </div>
                            <button type="submit"
                                class="section-save-btn px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm transition active:scale-95 flex items-center gap-2 cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Save Section 3</span>
                            </button>
                        </div>
                    </div>

                    <!-- SECTION 4 -->
                    <div id="sec-team"
                        class="section-card p-6 sm:p-7 space-y-6 scroll-mt-24 transition-all duration-200">
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
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="text-xs sm:text-sm font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600">Team
                                    &
                                    Geo</span>
                                <button type="submit"
                                    class="section-save-btn px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                    <span>Save Section 4</span>
                                </button>
                            </div>
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
                                    value="<?= htmlspecialchars($company['country'] ?? 'India') ?>"
                                    placeholder="e.g. India" class="form-input-clean">
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

                        <!-- Section 4 Card Footer Save Bar -->
                        <div class="border-t border-slate-100 pt-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <i data-lucide="map-pin" class="w-4 h-4 text-rose-500"></i>
                                <span>Headquarters & Operational Footprint details</span>
                            </div>
                            <button type="submit"
                                class="section-save-btn px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm transition active:scale-95 flex items-center gap-2 cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Save Section 4</span>
                            </button>
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
                            class="w-full sm:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-base rounded-xl shadow-md transition flex items-center justify-center space-x-2 cursor-pointer">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>

                <!-- SECTION 5: Trust, Accreditations & Verified Awards -->
                <div id="sec-awards" class="section-card p-6 sm:p-7 space-y-6 scroll-mt-24">
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
                        <div class="flex items-start space-x-3">
                            <div
                                class="w-11 h-11 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-600 text-white flex items-center justify-center shadow-sm flex-shrink-0">
                                <i data-lucide="award" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Verified
                                        Awards, Accreditations & Trust Proof</h2>
                                    <span
                                        class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        <?= count($awards) ?> Verified
                                    </span>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                                    Share trophy photos, DPIIT certificates, ISO/compliance badges, incubation proof,
                                    and patents to establish undeniable trust with institutional and angel investors.
                                </p>
                            </div>
                        </div>

                        <?php if ($company): ?>
                            <button type="button" onclick="openAwardModal()"
                                class="px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold transition flex items-center justify-center space-x-1.5 shadow-sm flex-shrink-0">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                <span>Add Award or Document</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (!$company): ?>
                        <div
                            class="p-8 text-center bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                            <div
                                class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="award" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-white">Save Company Profile First</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1">
                                Save your company basics above to start attaching award certificates, trophy photos, and
                                trust credentials.
                            </p>
                        </div>
                    <?php elseif (empty($awards)): ?>
                        <div
                            class="p-10 text-center bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700">
                            <div
                                class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="medal" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-white">No Awards or Trust Credentials
                                Added Yet</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1 leading-relaxed">
                                Showcase recognition from DPIIT, NASSCOM, MeitY, university incubators, or ISO
                                certifications. Verified credentials increase investor response rates by 68%.
                            </p>
                            <button type="button" onclick="openAwardModal()"
                                class="mt-4 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold transition inline-flex items-center space-x-1.5 shadow-sm">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span>Add First Award / Trust Badge</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($awards as $award):
                                $isPdf = str_ends_with(strtolower($award['image_url']), '.pdf');
                                ?>
                                <!-- Certificate Photo Wall Frame -->
                                <div class="group relative cursor-pointer"
                                    onclick="<?= $isPdf ? "window.open('" . htmlspecialchars($award['image_url']) . "', '_blank')" : "openAwardLightbox('" . htmlspecialchars($award['image_url']) . "', '" . htmlspecialchars(addslashes($award['title'] ?? '')) . "', '" . htmlspecialchars(addslashes($award['issuer'] ?? '')) . "')" ?>"
                                    title="Click to view full certificate">
                                    <!-- Outer Frame Molding (Top, Right, Bottom, Left all framed) -->
                                    <div
                                        class="p-3 sm:p-3.5 rounded-xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 dark:from-slate-800 dark:via-slate-900 dark:to-black shadow-[0_10px_25px_-5px_rgba(0,0,0,0.25)] border-2 border-amber-600/30 group-hover:border-amber-500/70 transition-all duration-300 group-hover:scale-[1.02] group-hover:shadow-[0_16px_35px_-5px_rgba(0,0,0,0.35)] relative">
                                        <!-- Delete Button on Hover -->
                                        <form action="<?= url('founder/company.php') ?>" method="POST"
                                            class="absolute top-2 right-2 z-20 opacity-0 group-hover:opacity-100 transition-opacity"
                                            onclick="event.stopPropagation();"
                                            onsubmit="event.stopPropagation(); return confirm('Are you sure you want to remove this certificate photo?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="form_action" value="delete_award">
                                            <input type="hidden" name="award_id" value="<?= (int) $award['id'] ?>">
                                            <button type="submit"
                                                class="p-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white shadow-md transition"
                                                title="Delete certificate">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>

                                        <!-- Inner Matting (passe-partout on all sides) -->
                                        <div
                                            class="p-2 sm:p-2.5 rounded-lg bg-[#faf8f5] dark:bg-[#141820] border border-amber-200/40 dark:border-amber-900/40 shadow-inner">
                                            <!-- Photo Mount Container -->
                                            <div
                                                class="relative w-full h-52 sm:h-60 rounded bg-white dark:bg-slate-950 overflow-hidden flex items-center justify-center border border-slate-300/80 dark:border-slate-800 shadow-xs">
                                                <?php if ($isPdf): ?>
                                                    <div class="text-center p-4">
                                                        <div
                                                            class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center mx-auto mb-2 border border-rose-100 dark:border-rose-900 shadow-xs">
                                                            <i data-lucide="file-text" class="w-6 h-6"></i>
                                                        </div>
                                                        <span
                                                            class="text-xs font-bold text-slate-800 dark:text-slate-200 block">Certificate
                                                            (PDF)</span>
                                                        <span
                                                            class="block text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold mt-1">Click
                                                            to view &rarr;</span>
                                                    </div>
                                                <?php else: ?>
                                                    <img src="<?= htmlspecialchars($award['image_url']) ?>" alt="Award Certificate"
                                                        class="w-full h-full object-contain p-1 group-hover:brightness-105 transition duration-300"
                                                        loading="lazy"
                                                        onerror="this.src='https://images.unsplash.com/photo-1567427017947-545c5f8d16ad?w=800&auto=format&fit=crop&q=80'">
                                                    <div
                                                        class="absolute inset-0 bg-slate-950/20 opacity-0 group-hover:opacity-100 transition duration-200 flex items-center justify-center">
                                                        <span
                                                            class="px-3 py-1.5 rounded-full bg-slate-900/90 text-white text-[11px] font-bold shadow-lg backdrop-blur-xs flex items-center gap-1.5">
                                                            <i data-lucide="maximize-2" class="w-3.5 h-3.5 text-amber-400"></i>
                                                            <span>View Full Photo</span>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECTION 6: Data Room -->
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
                                    Securely gated compliance repository for Pitch Decks, Financial Models, and Cap
                                    Tables
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
                                Please fill in and save your legal company details above to unlock your secure startup data
                                room
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
                                Startups with an uploaded Pitch Deck and Cap Table receive 4x more meeting requests from
                                active
                                institutional investors.
                            </p>
                            <button type="button" onclick="openUploadModal()"
                                class="mt-4 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition inline-flex items-center space-x-1.5 shadow-sm">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span>Add Pitch Deck or Model</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3.5">
                            <?php foreach ($documents as $doc): ?>
                                <div
                                    class="p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#111827] shadow-xs hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-900/60 transition-all duration-200 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                    <!-- Left: Document Details -->
                                    <div class="flex items-start sm:items-center gap-4 min-w-0 flex-1">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0 border border-indigo-100 dark:border-indigo-800/60 shadow-xs">
                                            <?php if (stripos($doc['document_type'], 'deck') !== false): ?>
                                                <i data-lucide="presentation"
                                                    class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                                            <?php elseif (stripos($doc['document_type'], 'financial') !== false || stripos($doc['document_type'], 'cap') !== false): ?>
                                                <i data-lucide="table" class="w-6 h-6 text-emerald-600 dark:text-emerald-400"></i>
                                            <?php elseif (stripos($doc['document_type'], 'incorporation') !== false || stripos($doc['document_type'], 'gst') !== false): ?>
                                                <i data-lucide="shield" class="w-6 h-6 text-violet-600 dark:text-violet-400"></i>
                                            <?php else: ?>
                                                <i data-lucide="file-text" class="w-6 h-6 text-slate-600 dark:text-slate-400"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0 flex-1 space-y-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4
                                                    class="font-bold text-slate-900 dark:text-white text-sm sm:text-base leading-snug break-words">
                                                    <?= htmlspecialchars(!empty($doc['title']) ? $doc['title'] : ($doc['document_type'] ?: 'Confidential Document')) ?>
                                                </h4>
                                                <span
                                                    class="text-xs px-2.5 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-semibold border border-indigo-100 dark:border-indigo-800/60 flex-shrink-0">
                                                    <?= htmlspecialchars($doc['document_type']) ?>
                                                </span>
                                            </div>
                                            <div
                                                class="text-xs text-slate-500 dark:text-slate-400 flex flex-wrap items-center gap-x-2.5 gap-y-1">
                                                <span
                                                    class="font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">Size:
                                                    <?= htmlspecialchars($doc['file_size'] ?: '1.8 MB') ?></span>
                                                <span class="text-slate-300 dark:text-slate-600">•</span>
                                                <span class="whitespace-nowrap">Uploaded:
                                                    <?= date('M d, Y', strtotime($doc['uploaded_at'] ?? 'now')) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: Access Badge, Status & Actions -->
                                    <div
                                        class="flex flex-wrap items-center gap-2 sm:gap-2.5 lg:justify-end flex-shrink-0 pt-3 lg:pt-0 border-t lg:border-t-0 border-slate-100 dark:border-slate-800">
                                        <span
                                            class="px-2.5 py-1 rounded-full text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
                                            <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $doc['access_level'] ?? 'registered_investors'))) ?></span>
                                        </span>

                                        <span
                                            class="text-xs font-bold px-2.5 py-1 rounded-full border flex items-center gap-1.5 whitespace-nowrap <?= $doc['is_verified'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800' : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800' ?>">
                                            <span
                                                class="w-1.5 h-1.5 rounded-full <?= $doc['is_verified'] ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                                            <span><?= $doc['is_verified'] ? 'Verified' : 'Under Review' ?></span>
                                        </span>

                                        <div class="flex items-center gap-2 flex-wrap">
                                            <a href="<?= htmlspecialchars(url($doc['file_path'])) ?>" target="_blank"
                                                rel="noopener"
                                                class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs sm:text-sm font-bold flex items-center gap-1.5 transition shadow-2xs">
                                                <i data-lucide="eye" class="w-4 h-4 text-slate-500"></i>
                                                <span>Preview</span>
                                            </a>

                                            <a href="<?= htmlspecialchars(url('download.php?id=' . (int) $doc['id'] . '&type=company')) ?>"
                                                class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-700 text-white text-xs sm:text-sm font-bold flex items-center gap-1.5 transition shadow-2xs">
                                                <i data-lucide="download" class="w-4 h-4"></i>
                                                <span>Download</span>
                                            </a>

                                            <form action="<?= url('founder/company.php') ?>" method="POST" class="inline"
                                                onsubmit="return confirm('Are you sure you want to permanently remove this document from the Data Room?');">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="form_action" value="delete_doc">
                                                <input type="hidden" name="doc_id" value="<?= (int) $doc['id'] ?>">
                                                <button type="submit"
                                                    class="p-2 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-white dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/50 text-rose-600 dark:text-rose-400 transition shadow-2xs cursor-pointer"
                                                    title="Delete document">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
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
                                    <p class="text-sm text-slate-500 mt-0.5">Attach investor decks, certificates or
                                        financial
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
                                        <option value="Certificate of Incorporation">Certificate of Incorporation (ROC)
                                        </option>
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
                                        placeholder="e.g. Q1 2026 Seed Investor Pitch Presentation"
                                        class="form-input-clean">
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
                                        <option value="registered_investors">Registered & Verified Investors Only
                                            (Recommended)
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

        // Trigger Company Logo File Picker (opens device photo gallery or file explorer)
        function triggerCompanyLogoUpload() {
            const input = document.getElementById('quick-company-logo-file');
            if (input) {
                input.click();
            } else {
                const sec1Input = document.getElementById('sec1-logo-file');
                if (sec1Input) {
                    sec1Input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    sec1Input.click();
                }
            }
        }

        // Trigger Company Cover Banner File Picker
        function triggerCompanyBannerUpload() {
            const input = document.getElementById('quick-company-banner-file');
            if (input) {
                input.click();
            } else {
                const sec1Banner = document.getElementById('sec1-banner-file');
                if (sec1Banner) {
                    sec1Banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    sec1Banner.click();
                }
            }
        }

        // Automatic submission when file selected from gallery for Logo
        function autoSubmitCompanyLogo(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                // Instant optimistic preview on the hero card
                const reader = new FileReader();
                reader.onload = function (e) {
                    const heroImg = document.getElementById('company-hero-logo-img');
                    if (heroImg) {
                        heroImg.src = e.target.result;
                        heroImg.classList.remove('hidden');
                    }
                    const fallback = document.getElementById('company-hero-logo-fallback');
                    if (fallback) {
                        fallback.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);

                // Auto submit form
                const form = document.getElementById('quick-logo-form');
                if (form) {
                    form.submit();
                }
            }
        }

        // Automatic submission when file selected from gallery for Banner
        function autoSubmitCompanyBanner(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();
                reader.onload = function (e) {
                    const bannerImg = document.getElementById('company-hero-banner-img');
                    if (bannerImg) {
                        bannerImg.src = e.target.result;
                        bannerImg.classList.remove('hidden');
                    }
                    const bannerContainer = document.getElementById('company-hero-banner-container');
                    if (bannerContainer) {
                        bannerContainer.classList.remove('cover-banner-pattern');
                        bannerContainer.classList.add('bg-slate-900');
                    }
                };
                reader.readAsDataURL(file);

                const form = document.getElementById('quick-banner-form');
                if (form) {
                    form.submit();
                }
            }
        }

        // Section 1 Live Preview Handlers
        function previewSec1Logo(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const label = document.getElementById('sec1-logo-filename');
                if (label) {
                    label.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                    label.className = 'text-[11px] text-emerald-600 font-bold';
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    const prev = document.getElementById('sec1-logo-preview');
                    if (prev) prev.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        function previewSec1LogoUrl(url) {
            if (url && url.length > 5) {
                const prev = document.getElementById('sec1-logo-preview');
                if (prev) prev.src = url;
            }
        }

        function previewSec1Banner(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const label = document.getElementById('sec1-banner-filename');
                if (label) {
                    label.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                    label.className = 'text-[11px] text-emerald-600 font-bold';
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    const prev = document.getElementById('sec1-banner-preview');
                    if (prev) {
                        prev.src = e.target.result;
                    }
                    const fallback = document.getElementById('sec1-banner-preview-fallback');
                    if (fallback) fallback.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }

        function previewSec1BannerUrl(url) {
            if (url && url.length > 5) {
                const prev = document.getElementById('sec1-banner-preview');
                if (prev) {
                    prev.src = url;
                }
                const fallback = document.getElementById('sec1-banner-preview-fallback');
                if (fallback) fallback.style.display = 'none';
            }
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

    <?php include __DIR__ . '/../includes/share_company_modal.php'; ?>
</body>

</html>