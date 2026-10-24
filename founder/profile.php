<?php
/**
 * Founder Module: Profile & Account Settings Studio
 * Executive LinkedIn-Style Persona, Credentials, Social Links & Security Management
 * Clean Visual Contrast, Modern Glassmorphic Badges, Full Responsive Layout
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Founder Profile & Settings';

$founderProfile = null;
$companies = [];
$company = null;
$fundingRounds = [];
$error = '';
$flash = get_flash();

if ($db) {
    // 1. Fetch founder profile extension
    $fpStmt = $db->prepare("SELECT * FROM founder_profiles WHERE user_id = ?");
    $fpStmt->execute([$user['id']]);
    $founderProfile = $fpStmt->fetch() ?: [];

    // 2. Fetch all associated companies
    $cStmt = $db->prepare("
        SELECT c.*, cf.equity_percent, cf.is_signatory, cf.designation as founder_comp_designation
        FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $cStmt->execute([$user['id']]);
    $companies = $cStmt->fetchAll();
    $company = $companies[0] ?? null;

    // 3. Fetch funding rounds
    if (!empty($companies)) {
        $compIds = array_column($companies, 'id');
        $placeholders = implode(',', array_fill(0, count($compIds), '?'));
        $frStmt = $db->prepare("
            SELECT fr.*, c.name as company_name, c.logo_url as company_logo
            FROM funding_rounds fr
            JOIN companies c ON fr.company_id = c.id
            WHERE fr.company_id IN ($placeholders)
            ORDER BY fr.created_at DESC
        ");
        $frStmt->execute($compIds);
        $fundingRounds = $frStmt->fetchAll();
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please refresh the page.';
    } else {
        $action = $_POST['form_action'] ?? 'update_profile';

        if ($action === 'update_profile') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $avatarUrl = trim($_POST['avatar_url'] ?? '');
            $designation = trim($_POST['designation'] ?? 'Founder & CEO');
            $bio = trim($_POST['bio'] ?? '');
            $linkedin = trim($_POST['linkedin_url'] ?? '');
            $website = trim($_POST['website_url'] ?? '');
            $pan = strtoupper(trim($_POST['pan_number'] ?? ''));

            // Handle custom avatar photo upload
            if (!empty($_FILES['avatar_file']['name'])) {
                $uploadRes = handle_avatar_upload($_FILES['avatar_file'], $user['id']);
                if ($uploadRes['success']) {
                    $avatarUrl = $uploadRes['url'];
                } elseif (!empty($uploadRes['error'])) {
                    $error = $uploadRes['error'];
                }
            }
            if (empty($avatarUrl)) {
                $avatarUrl = $user['avatar_url'] ?? '';
            }

            if (empty($error)) {
                // Update user record
                $db->prepare("UPDATE users SET name = ?, phone = ?, city = ?, avatar_url = ? WHERE id = ?")
                   ->execute([$name, $phone, $city, $avatarUrl, $user['id']]);

                // Update founder profile record
                $updFP = $db->prepare("
                    INSERT INTO founder_profiles (user_id, designation, bio, linkedin_url, website_url, pan_number)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE designation = VALUES(designation), bio = VALUES(bio), linkedin_url = VALUES(linkedin_url), website_url = VALUES(website_url), pan_number = VALUES(pan_number)
                ");
                $updFP->execute([$user['id'], $designation, $bio, $linkedin, $website, $pan]);

                // Also sync designation into company_founders so public company pages show the latest title
                $db->prepare("UPDATE company_founders SET designation = ? WHERE user_id = ?")->execute([$designation, $user['id']]);

                log_audit($user['id'], 'UPDATE_PROFILE', 'users', $user['id'], 'Founder updated personal profile');
                set_flash('success', 'Profile details updated successfully!');
                header('Location: ' . url('founder/profile.php?tab=overview'));
                exit;
            }

        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (password_verify($currentPass, $user['password_hash'])) {
                if (strlen($newPass) >= 6 && $newPass === $confirmPass) {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                    $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                    log_audit($user['id'], 'PASSWORD_CHANGED', 'users', $user['id'], 'Founder changed password');
                    set_flash('success', 'Password updated successfully.');
                    header('Location: ' . url('founder/profile.php?tab=security'));
                    exit;
                } else {
                    $error = 'New passwords must match and be at least 6 characters long.';
                }
            } else {
                $error = 'Current password entered is incorrect.';
            }
        }
    }
}

// Compute initials
$userInitials = 'F';
if (!empty($user['name'])) {
    $parts = explode(' ', trim($user['name']));
    $userInitials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}

$publicCompanyUrl = $company ? url('company.php?id=' . hash_id_encode($company['id'])) : '';
$currentTab = $_GET['tab'] ?? 'overview';
if (!in_array($currentTab, ['overview', 'edit', 'security'])) {
    $currentTab = 'overview';
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
    <style>
        .linkedin-card {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1.25rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            transition: all 0.2s ease;
        }
        .dark .linkedin-card, html.dark .linkedin-card {
            background-color: #111827 !important;
            border-color: #1E293B !important;
            color: #F8FAFC !important;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.5) !important;
        }

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

        .form-input-clean {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: #F8FAFC;
            border: 1px solid #CBD5E1;
            border-radius: 0.75rem;
            color: #0F172A;
            font-size: 0.875rem;
            line-height: 1.4rem;
            transition: all 0.15s ease;
            outline: none;
        }
        .dark .form-input-clean, html.dark .form-input-clean {
            background-color: #1E293B !important;
            border-color: #334155 !important;
            color: #F8FAFC !important;
        }
        .form-input-clean:hover {
            background-color: #F1F5F9;
            border-color: #94A3B8;
        }
        .dark .form-input-clean:hover, html.dark .form-input-clean:hover {
            background-color: #243047 !important;
            border-color: #475569 !important;
        }
        .form-input-clean:focus {
            background-color: #FFFFFF;
            border-color: #4F46E5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        }
        .dark .form-input-clean:focus, html.dark .form-input-clean:focus {
            background-color: #1E293B !important;
            border-color: #6366F1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25) !important;
        }

        .form-label-clean {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 0.4rem;
        }
        .dark .form-label-clean, html.dark .form-label-clean {
            color: #E2E8F0 !important;
        }

        .tab-btn.active {
            background-color: #4F46E5;
            color: #FFFFFF !important;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
        }
        .dark .tab-btn.active {
            background-color: #6366F1;
            color: #FFFFFF !important;
        }
    </style>
</head>

<body class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Fixed Founder Navbar -->
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <!-- Main Container -->
        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 space-y-6" id="profile-main">
            
            <!-- Flash Feedback -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800' ?> flex items-center justify-between shadow-xs">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0 <?= $flash['type'] === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <span class="text-xs font-bold uppercase opacity-75">Notice</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-2xl text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 flex items-center space-x-3 shadow-xs">
                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 text-rose-600"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- LinkedIn-Style Mode Switcher / Tab Toolbar -->
            <div class="linkedin-card p-2 sm:p-2.5 flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-[#111827]">
                <div class="flex items-center space-x-1.5 p-1 bg-slate-100 dark:bg-slate-800/80 rounded-xl w-full sm:w-auto overflow-x-auto">
                    <button type="button" onclick="switchTab('overview')" id="tab-btn-overview"
                            class="tab-btn flex-1 sm:flex-initial px-4 py-2 rounded-lg text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition flex items-center justify-center space-x-2 cursor-pointer <?= $currentTab === 'overview' ? 'active' : '' ?>">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <span>Profile Overview</span>
                    </button>
                    <button type="button" onclick="switchTab('edit')" id="tab-btn-edit"
                            class="tab-btn flex-1 sm:flex-initial px-4 py-2 rounded-lg text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition flex items-center justify-center space-x-2 cursor-pointer <?= $currentTab === 'edit' ? 'active' : '' ?>">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                        <span>Edit Profile</span>
                    </button>
                    <button type="button" onclick="switchTab('security')" id="tab-btn-security"
                            class="tab-btn flex-1 sm:flex-initial px-4 py-2 rounded-lg text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition flex items-center justify-center space-x-2 cursor-pointer <?= $currentTab === 'security' ? 'active' : '' ?>">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        <span>Security &amp; Password</span>
                    </button>
                </div>

                <div class="flex items-center space-x-2 ml-auto w-full sm:w-auto justify-end">
                    <?php if ($company): ?>
                        <button type="button" onclick="openShareModal('<?= htmlspecialchars($publicCompanyUrl) ?>', '<?= htmlspecialchars(addslashes($company['name'])) ?>')"
                                class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer">
                            <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                            <span>Share Profile</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 1: LINKEDIN-STYLE PROFILE OVERVIEW (READ & VIEW MODE) -->
            <!-- ======================================================== -->
            <div id="tab-content-overview" class="space-y-6 <?= $currentTab !== 'overview' ? 'hidden' : '' ?>">
                
                <!-- Master Executive Hero Card -->
                <div class="linkedin-card rounded-2xl overflow-hidden relative">
                    <!-- Cover Banner Strip -->
                    <div class="h-36 sm:h-44 cover-banner-pattern relative">
                        <div class="absolute top-4 right-4 z-10 flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-white text-[11px] font-bold border border-white/25 flex items-center gap-1.5 shadow-sm">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-300"></i>
                                <span>Verified Venture Founder</span>
                            </span>
                        </div>
                    </div>

                    <!-- Avatar + Details Row -->
                    <div class="px-6 sm:px-8 pb-7 pt-0 relative">
                        <div class="flex flex-col md:flex-row md:items-end justify-between gap-5 border-b border-slate-100 dark:border-slate-800 pb-6">
                            <div class="flex flex-col sm:flex-row sm:items-end gap-5">
                                <!-- Avatar with Camera Overlay -->
                                <div class="relative -mt-16 sm:-mt-20 flex-shrink-0">
                                    <div class="relative group">
                                        <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '')) ?>" 
                                             alt="<?= htmlspecialchars($user['name']) ?>"
                                             class="w-28 h-28 sm:w-32 sm:h-32 rounded-full object-cover object-center border-4 border-white dark:border-[#111827] shadow-xl bg-white dark:bg-slate-800">
                                        
                                        <!-- Quick Upload Button directly on Photo -->
                                        <button type="button" onclick="triggerAvatarUpload()" 
                                                class="absolute bottom-1 right-1 w-9 h-9 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white border-2 border-white dark:border-[#111827] flex items-center justify-center shadow-md transition active:scale-95 cursor-pointer" 
                                                title="Update profile picture">
                                            <i data-lucide="camera" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Text Identity -->
                                <div class="pt-2 sm:pt-0 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2.5">
                                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight">
                                            <?= htmlspecialchars($user['name']) ?>
                                        </h1>
                                        <?php if ($user['is_verified']): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-bold">
                                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                <span>KYC VERIFIED</span>
                                            </span>
                                        <?php else: ?>
                                            <a href="<?= url('founder/verification.php') ?>" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 text-xs font-bold transition">
                                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                                <span>KYC PENDING &rarr;</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Headline / Designation -->
                                    <div class="text-sm sm:text-base font-semibold text-slate-700 dark:text-slate-200 flex flex-wrap items-center gap-2">
                                        <span class="text-indigo-600 dark:text-indigo-400 font-bold"><?= htmlspecialchars($founderProfile['designation'] ?? 'Founder & CEO') ?></span>
                                        <?php if ($company): ?>
                                            <span class="text-slate-400">&bull;</span>
                                            <span class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($company['name']) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Location & Meta Pills -->
                                    <div class="flex flex-wrap items-center gap-3 text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium pt-1">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="map-pin" class="w-4 h-4 text-slate-400"></i>
                                            <span><?= htmlspecialchars($user['city'] ?? 'Mumbai') ?>, <?= htmlspecialchars($user['country'] ?? 'India') ?></span>
                                        </span>
                                        <span>&bull;</span>
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="mail" class="w-4 h-4 text-slate-400"></i>
                                            <span><?= htmlspecialchars($user['email']) ?></span>
                                        </span>
                                        <?php if (!empty($user['phone'])): ?>
                                            <span>&bull;</span>
                                            <span class="flex items-center gap-1.5">
                                                <i data-lucide="phone" class="w-4 h-4 text-slate-400"></i>
                                                <span><?= htmlspecialchars($user['phone']) ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons on Header -->
                            <div class="flex items-center gap-2.5 pt-3 md:pt-0">
                                <button type="button" onclick="switchTab('edit')"
                                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs sm:text-sm shadow-sm shadow-indigo-600/20 transition flex items-center space-x-1.5 cursor-pointer active:scale-95">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    <span>Edit Profile</span>
                                </button>
                                <?php if ($company): ?>
                                    <a href="<?= url('founder/company.php') ?>" 
                                       class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs sm:text-sm transition flex items-center space-x-1.5 shadow-2xs">
                                        <i data-lucide="building-2" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                        <span>Company Profile</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- LinkedIn-Style Metrics Row -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-5">
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800">
                                <span class="text-xs uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">Startups Founded</span>
                                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1"><?= count($companies) ?></div>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800">
                                <span class="text-xs uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">Active Rounds</span>
                                <div class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-1"><?= count($fundingRounds) ?></div>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800">
                                <span class="text-xs uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">KYC Compliance</span>
                                <div class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1 flex items-center space-x-1.5">
                                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                                    <span>Verified</span>
                                </div>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800">
                                <span class="text-xs uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">Corporate Equity</span>
                                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1"><?= !empty($company['equity_percent']) ? $company['equity_percent'] . '%' : 'Founder Stake' ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two-Column LinkedIn-Style Content Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left Column: Main Bio + Experience (2 Columns Wide) -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- CARD 1: ABOUT / SUMMARY -->
                        <div class="linkedin-card p-6 sm:p-7 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3.5">
                                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                                    <i data-lucide="user-check" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                                    <span>About &amp; Entrepreneurial Vision</span>
                                </h2>
                                <button type="button" onclick="switchTab('edit', 'bio')" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer" title="Edit About Bio">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </button>
                            </div>

                            <?php if (!empty($founderProfile['bio'])): ?>
                                <div class="text-sm sm:text-base text-slate-700 dark:text-slate-300 leading-relaxed font-normal whitespace-pre-line">
                                    <?= nl2br(htmlspecialchars($founderProfile['bio'])) ?>
                                </div>
                            <?php else: ?>
                                <div class="p-6 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/30 border border-dashed border-indigo-200 dark:border-indigo-800/80 text-center space-y-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/60 text-indigo-600 dark:text-indigo-300 flex items-center justify-center mx-auto">
                                        <i data-lucide="file-text" class="w-5 h-5"></i>
                                    </div>
                                    <div class="max-w-md mx-auto">
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Write Your Founder Story</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            Highlight your leadership background, core technical patents, prior startups, or mission statement so investors can understand your conviction.
                                        </p>
                                    </div>
                                    <button type="button" onclick="switchTab('edit', 'bio')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition inline-flex items-center space-x-1.5 shadow-sm cursor-pointer">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span>Add About Section</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- CARD 2: EXPERIENCE & VENTURES FOUNDED -->
                        <div class="linkedin-card p-6 sm:p-7 space-y-5">
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3.5">
                                <div>
                                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                                        <i data-lucide="briefcase" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                                        <span>Experience &amp; Ventures Founded</span>
                                    </h2>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Commercial startups under your leadership and governance.</p>
                                </div>
                                <a href="<?= url('founder/company.php') ?>" class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1.5">
                                    <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                                    <span>Manage Startup</span>
                                </a>
                            </div>

                            <?php if (empty($companies)): ?>
                                <div class="p-8 text-center bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
                                    <i data-lucide="building" class="w-8 h-8 text-slate-400 mx-auto mb-2"></i>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No startup entity linked yet</p>
                                    <a href="<?= url('founder/company.php') ?>" class="mt-3 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl shadow-sm">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span>Setup Company Profile</span>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($companies as $comp): 
                                        $cInit = 'SU';
                                        if (!empty($comp['name'])) {
                                            $cParts = preg_split('/\s+/', trim($comp['name']));
                                            $cInit = strtoupper(substr($cParts[0], 0, 1) . (isset($cParts[1]) ? substr($cParts[1], 0, 1) : ''));
                                        }
                                    ?>
                                        <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-4 hover:border-indigo-200 transition">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="flex items-start space-x-3.5">
                                                    <div class="w-13 h-13 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white flex items-center justify-center font-bold text-lg shadow-sm flex-shrink-0">
                                                        <?= htmlspecialchars($cInit) ?>
                                                    </div>
                                                    <div>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <h3 class="font-bold text-slate-900 dark:text-white text-base">
                                                                <?= htmlspecialchars($comp['name']) ?>
                                                            </h3>
                                                            <?= render_status_badge($comp['verified_status']) ?>
                                                        </div>
                                                        <div class="text-xs sm:text-sm font-semibold text-indigo-600 dark:text-indigo-400 mt-0.5">
                                                            <?= htmlspecialchars($comp['founder_comp_designation'] ?? 'Founder & CEO') ?> &bull; Full-time
                                                        </div>
                                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                            <?= htmlspecialchars($comp['industry']) ?> &bull; <?= htmlspecialchars($comp['stage']) ?> Stage &bull; <?= htmlspecialchars($comp['city'] ?? 'Bengaluru') ?>, <?= htmlspecialchars($comp['country'] ?? 'India') ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="text-right flex-shrink-0">
                                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Equity Stake</span>
                                                    <span class="font-extrabold text-indigo-600 dark:text-indigo-400 text-sm sm:text-base">
                                                        <?= !empty($comp['equity_percent']) ? $comp['equity_percent'] . '%' : 'Founder' ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <?php if (!empty($comp['pitch'])): ?>
                                                <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 font-medium leading-relaxed bg-white dark:bg-slate-800/80 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                                                    &ldquo;<?= htmlspecialchars($comp['pitch']) ?>&rdquo;
                                                </p>
                                            <?php endif; ?>

                                            <div class="pt-3 border-t border-slate-200/60 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
                                                <div class="font-mono text-slate-500">
                                                    CIN: <span class="font-bold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($comp['cin_number'] ?? 'PENDING') ?></span>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <a href="<?= url('founder/company.php') ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold flex items-center gap-1">
                                                        <span>Edit Company Profile</span>
                                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- CARD 3: ACTIVE FUNDING ROUNDS -->
                        <?php if (!empty($fundingRounds)): ?>
                            <div class="linkedin-card p-6 sm:p-7 space-y-4">
                                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3.5">
                                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                                        <i data-lucide="circle-dollar-sign" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                                        <span>Active Funding Campaigns (<?= count($fundingRounds) ?>)</span>
                                    </h2>
                                    <a href="<?= url('founder/funding_rounds.php') ?>" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Manage Rounds &rarr;
                                    </a>
                                </div>

                                <div class="space-y-3.5">
                                    <?php foreach ($fundingRounds as $fr): 
                                        $pct = $fr['target_amount'] > 0 ? round(($fr['amount_raised'] / $fr['target_amount']) * 100) : 0;
                                    ?>
                                        <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <span class="font-bold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($fr['round_name']) ?></span>
                                                    <span class="text-xs text-slate-500 ml-1.5">&bull; <?= htmlspecialchars($fr['company_name']) ?></span>
                                                </div>
                                                <?= render_status_badge($fr['status']) ?>
                                            </div>

                                            <div>
                                                <div class="flex justify-between text-xs mb-1 font-medium">
                                                    <span class="text-slate-600 dark:text-slate-400">Raised: <strong class="text-slate-900 dark:text-white"><?= format_inr($fr['amount_raised']) ?></strong></span>
                                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold"><?= $pct ?>%</span>
                                                </div>
                                                <div class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-indigo-600 rounded-full" style="width: <?= min(100, $pct) ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Right Column: Contact, Credentials & Sidebar Info (1 Column Wide) -->
                    <div class="space-y-6">
                        
                        <!-- CARD 4: CONTACT & PROFESSIONAL LINKS -->
                        <div class="linkedin-card p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                                    <i data-lucide="globe" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                    <span>Contact &amp; Web Links</span>
                                </h3>
                                <button type="button" onclick="switchTab('edit', 'linkedin_url')" class="p-1 rounded text-slate-400 hover:text-indigo-600 transition cursor-pointer">
                                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>

                            <div class="space-y-2.5 text-xs sm:text-sm">
                                <?php if (!empty($founderProfile['linkedin_url'])): ?>
                                    <a href="<?= htmlspecialchars($founderProfile['linkedin_url']) ?>" target="_blank" rel="noopener"
                                       class="flex items-center justify-between p-3 rounded-xl bg-blue-50/70 hover:bg-blue-100/70 text-blue-900 dark:bg-blue-950/40 dark:text-blue-300 dark:hover:bg-blue-900/50 transition font-bold border border-blue-100 dark:border-blue-900">
                                        <span class="flex items-center space-x-2.5">
                                            <i data-lucide="linkedin" class="w-4 h-4 text-blue-600"></i>
                                            <span>LinkedIn Profile</span>
                                        </span>
                                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-blue-500"></i>
                                    </a>
                                <?php else: ?>
                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800 flex items-center justify-between text-slate-500">
                                        <span class="flex items-center space-x-2">
                                            <i data-lucide="linkedin" class="w-4 h-4 text-slate-400"></i>
                                            <span class="text-xs">No LinkedIn added</span>
                                        </span>
                                        <button type="button" onclick="switchTab('edit', 'linkedin_url')" class="text-xs text-indigo-600 font-bold hover:underline cursor-pointer">+ Add</button>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($founderProfile['website_url'])): ?>
                                    <a href="<?= htmlspecialchars($founderProfile['website_url']) ?>" target="_blank" rel="noopener"
                                       class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 hover:bg-slate-100 text-slate-800 dark:text-slate-200 transition font-bold border border-slate-200/80 dark:border-slate-800">
                                        <span class="flex items-center space-x-2.5">
                                            <i data-lucide="globe" class="w-4 h-4 text-slate-500"></i>
                                            <span class="truncate">Portfolio / Website</span>
                                        </span>
                                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                                    </a>
                                <?php endif; ?>

                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800 space-y-0.5">
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Official Email</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-xs sm:text-sm block truncate"><?= htmlspecialchars($user['email']) ?></span>
                                </div>

                                <?php if (!empty($user['phone'])): ?>
                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800 space-y-0.5">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Direct Phone</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-xs sm:text-sm block"><?= htmlspecialchars($user['phone']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- CARD 5: VERIFIED CREDENTIALS & COMPLIANCE -->
                        <div class="linkedin-card p-6 space-y-3.5">
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                                    <i data-lucide="shield" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Regulatory Standing</span>
                                </h3>
                                <a href="<?= url('founder/verification.php') ?>" class="text-[11px] font-bold text-indigo-600 hover:underline">
                                    Details &rarr;
                                </a>
                            </div>

                            <div class="space-y-2 text-xs">
                                <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50">
                                    <span class="text-slate-800 dark:text-slate-200 font-bold flex items-center space-x-2">
                                        <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                                        <span>DigiLocker KYC</span>
                                    </span>
                                    <span class="text-emerald-700 dark:text-emerald-400 font-extrabold text-[10px] px-2 py-0.5 bg-emerald-100/70 dark:bg-emerald-900/60 rounded">VERIFIED</span>
                                </div>

                                <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50">
                                    <span class="text-slate-800 dark:text-slate-200 font-bold flex items-center space-x-2">
                                        <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                                        <span>MCA Registry Signatory</span>
                                    </span>
                                    <span class="text-emerald-700 dark:text-emerald-400 font-extrabold text-[10px] px-2 py-0.5 bg-emerald-100/70 dark:bg-emerald-900/60 rounded">VERIFIED</span>
                                </div>

                                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800">
                                    <span class="text-slate-700 dark:text-slate-300 font-bold flex items-center space-x-2">
                                        <i data-lucide="credit-card" class="w-4 h-4 text-slate-400"></i>
                                        <span>Tax PAN Record</span>
                                    </span>
                                    <span class="font-mono text-xs font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($founderProfile['pan_number'] ?? 'VERIFIED') ?></span>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- ======================================================== -->
            <!-- TAB 2: EDIT PROFILE STUDIO (UPDATE & INPUT MODE)         -->
            <!-- ======================================================== -->
            <div id="tab-content-edit" class="space-y-6 <?= $currentTab !== 'edit' ? 'hidden' : '' ?>">
                
                <form id="founder-edit-form" action="<?= url('founder/profile.php') ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="update_profile">

                    <!-- Top Save Bar -->
                    <div class="linkedin-card p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4 bg-indigo-50/50 dark:bg-indigo-950/20 border-indigo-100 dark:border-indigo-900/50">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold">
                                <i data-lucide="user-cog" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">Founder Profile Studio</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Update your executive bio, headline, headshot and contact information.</p>
                            </div>
                        </div>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-md transition flex items-center space-x-2 cursor-pointer active:scale-95">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>

                    <!-- 1. Photo Upload Studio -->
                    <div class="linkedin-card p-6 sm:p-7 space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                            <i data-lucide="camera" class="w-4 h-4 text-indigo-600"></i>
                            <span>1. Executive Headshot Photo</span>
                        </h3>

                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/80 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row items-center gap-5">
                            <div class="relative flex-shrink-0 group">
                                <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '')) ?>" 
                                     alt="Headshot"
                                     class="w-24 h-24 rounded-full object-cover object-center border-4 border-white dark:border-slate-800 shadow-md bg-white dark:bg-slate-900 transition" id="avatar-form-img">
                                
                                <label for="avatar_file" class="absolute inset-0 bg-black/45 rounded-full flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition cursor-pointer" title="Click to choose photo">
                                    <i data-lucide="camera" class="w-5 h-5 mb-0.5"></i>
                                    <span class="text-[9px] font-bold">Change</span>
                                </label>
                            </div>

                            <div class="flex-1 text-center sm:text-left space-y-2 w-full">
                                <div class="font-bold text-slate-800 dark:text-white text-xs flex items-center justify-center sm:justify-start space-x-2">
                                    <span>High-Resolution Headshot</span>
                                    <span class="text-[10px] px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-full font-bold">JPG, PNG, WEBP</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Professional portrait makes a strong impression on venture capital scouts. Max file size: 8MB.</p>
                                
                                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 pt-1">
                                    <label for="avatar_file" class="cursor-pointer inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs shadow-xs transition active:scale-95">
                                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                        <span>Choose New Photo File</span>
                                    </label>
                                    <input type="file" name="avatar_file" id="avatar_file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="previewAvatar(this)">
                                    <span id="file-chosen-name" class="text-xs text-slate-500 italic">No file chosen yet</span>
                                </div>

                                <details class="text-xs text-slate-500 dark:text-slate-400 pt-1 cursor-pointer">
                                    <summary class="hover:text-indigo-600 font-semibold select-none flex items-center gap-1.5">
                                        <i data-lucide="link" class="w-3.5 h-3.5"></i>
                                        <span>Or paste an online photo URL</span>
                                    </summary>
                                    <div class="mt-2">
                                        <input type="text" name="avatar_url" id="avatar_url_input" value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>" placeholder="https://images.unsplash.com/photo-... or uploads/avatars/..."
                                               class="form-input-clean text-xs font-mono"
                                               oninput="previewUrlAvatar(this.value)">
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Personal & Identity Info -->
                    <div class="linkedin-card p-6 sm:p-7 space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                            <i data-lucide="user" class="w-4 h-4 text-indigo-600"></i>
                            <span>2. Identity &amp; Professional Headline</span>
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label-clean">
                                    <span>Legal Full Name <span class="text-rose-500">*</span></span>
                                </label>
                                <input type="text" name="name" id="field-name" required value="<?= htmlspecialchars($user['name']) ?>"
                                       class="form-input-clean font-semibold">
                                <p class="text-[11px] text-slate-400 mt-1">Official name printed on MCA director records &amp; investor contracts.</p>
                            </div>

                            <div>
                                <label class="form-label-clean">
                                    <span>Official Designation / Professional Headline</span>
                                </label>
                                <input type="text" name="designation" id="field-designation" value="<?= htmlspecialchars($founderProfile['designation'] ?? 'Founder & CEO') ?>"
                                       placeholder="e.g. Founder & CEO | AI Engineer"
                                       class="form-input-clean font-semibold">
                                <p class="text-[11px] text-slate-400 mt-1">Shown prominently below your name on discovery portals.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                            <div>
                                <label class="form-label-clean">
                                    <span>Operating City &amp; State</span>
                                </label>
                                <input type="text" name="city" id="field-city" value="<?= htmlspecialchars($user['city'] ?? 'Bengaluru') ?>" placeholder="e.g. Mumbai, Maharashtra"
                                       class="form-input-clean font-medium">
                            </div>

                            <div>
                                <label class="form-label-clean">
                                    <span>Direct Contact Phone</span>
                                </label>
                                <input type="tel" name="phone" id="field-phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+91 98765 43210"
                                       class="form-input-clean font-medium">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Founder Narrative & Bio -->
                    <div class="linkedin-card p-6 sm:p-7 space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                            <i data-lucide="file-text" class="w-4 h-4 text-indigo-600"></i>
                            <span>3. Founder Narrative &amp; Entrepreneurial Bio</span>
                        </h3>

                        <div>
                            <label class="form-label-clean">
                                <span>About Story (Markdown or Clean Text)</span>
                                <span class="text-[11px] text-slate-400 font-normal">Displayed on deal sheets</span>
                            </label>
                            <textarea name="bio" id="field-bio" rows="6" 
                                      placeholder="Detail your background, prior exits, technology domain authority, engineering patents, or reason for building your current startup..."
                                      class="form-input-clean font-normal leading-relaxed text-sm"><?= htmlspecialchars($founderProfile['bio'] ?? '') ?></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">Pro-Tip: Highlight what gives you an unfair advantage in solving your industry's hardest problems.</p>
                        </div>
                    </div>

                    <!-- 4. Online Presence & Social Links -->
                    <div class="linkedin-card p-6 sm:p-7 space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                            <i data-lucide="link-2" class="w-4 h-4 text-indigo-600"></i>
                            <span>4. Social Links &amp; Tax Compliance</span>
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="form-label-clean">
                                    <span>LinkedIn Profile URL</span>
                                </label>
                                <div class="relative">
                                    <input type="url" name="linkedin_url" id="field-linkedin_url" value="<?= htmlspecialchars($founderProfile['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/username"
                                           class="form-input-clean pl-10 font-medium">
                                    <i data-lucide="linkedin" class="w-4 h-4 text-blue-600 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                </div>
                            </div>

                            <div>
                                <label class="form-label-clean">
                                    <span>Personal Website / Blog URL</span>
                                </label>
                                <div class="relative">
                                    <input type="url" name="website_url" id="field-website_url" value="<?= htmlspecialchars($founderProfile['website_url'] ?? '') ?>" placeholder="https://myportfolio.io"
                                           class="form-input-clean pl-10 font-medium">
                                    <i data-lucide="globe" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                </div>
                            </div>

                            <div>
                                <label class="form-label-clean">
                                    <span>Tax PAN Number</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Confidential</span>
                                </label>
                                <input type="text" name="pan_number" id="field-pan_number" value="<?= htmlspecialchars($founderProfile['pan_number'] ?? '') ?>" placeholder="ABCDE1234F"
                                       class="form-input-clean font-mono uppercase">
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Bar -->
                    <div class="linkedin-card p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4 bg-slate-50 dark:bg-slate-900/60">
                        <button type="button" onclick="switchTab('overview')" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 font-bold text-xs transition cursor-pointer">
                            Cancel &amp; View Profile
                        </button>
                        <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-md transition flex items-center space-x-2 cursor-pointer active:scale-95">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>

                </form>

            </div>

            <!-- ======================================================== -->
            <!-- TAB 3: SECURITY & PASSWORD                             -->
            <!-- ======================================================== -->
            <div id="tab-content-security" class="space-y-6 <?= $currentTab !== 'security' ? 'hidden' : '' ?>">
                
                <div class="linkedin-card p-6 sm:p-7 space-y-4">
                    <div class="flex items-center space-x-2.5 border-b border-slate-100 dark:border-slate-800 pb-3.5">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 flex items-center justify-center font-bold">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">Account Security &amp; Password Management</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Keep your founder account protected against unauthorized access.</p>
                        </div>
                    </div>

                    <form action="<?= url('founder/profile.php') ?>" method="POST" class="space-y-4 pt-2">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="form_action" value="change_password">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Current Password -->
                            <div>
                                <label class="form-label-clean"><span>Current Password</span></label>
                                <div class="relative">
                                    <input type="password" id="current_password" name="current_password" required class="form-input-clean pr-10 text-xs">
                                    <button type="button" onclick="togglePass('current_password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- New Password -->
                            <div>
                                <label class="form-label-clean"><span>New Password</span></label>
                                <div class="relative">
                                    <input type="password" id="new_password" name="new_password" required minlength="6" class="form-input-clean pr-10 text-xs">
                                    <button type="button" onclick="togglePass('new_password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <span class="text-[11px] text-slate-400 mt-1 block">At least 6 characters long</span>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="form-label-clean"><span>Confirm New Password</span></label>
                                <div class="relative">
                                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6" class="form-input-clean pr-10 text-xs">
                                    <button type="button" onclick="togglePass('confirm_password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 flex justify-end">
                            <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center gap-1.5 cursor-pointer active:scale-95">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                                <span>Update Security Password</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>

        </main>
    </div>

    <!-- Include Reusable Share Modal -->
    <?php include __DIR__ . '/../includes/share_company_modal.php'; ?>

    <script>
        function refreshIcons() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
        refreshIcons();
        document.addEventListener('DOMContentLoaded', refreshIcons);
        window.addEventListener('load', refreshIcons);

        if (window.gsap) {
            gsap.from("#profile-main", { duration: 0.35, y: 8, opacity: 0, ease: "power2.out" });
        }

        // Tab Switcher Controller
        function switchTab(tabName, focusField = null) {
            const tabs = ['overview', 'edit', 'security'];
            tabs.forEach(t => {
                const content = document.getElementById('tab-content-' + t);
                const btn = document.getElementById('tab-btn-' + t);
                if (content) {
                    if (t === tabName) {
                        content.classList.remove('hidden');
                    } else {
                        content.classList.add('hidden');
                    }
                }
                if (btn) {
                    if (t === tabName) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                }
            });

            // Update URL without page reload
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}

            refreshIcons();

            // Optional focus on specific edit field
            if (focusField) {
                setTimeout(() => {
                    const el = document.getElementById('field-' + focusField);
                    if (el) {
                        el.focus();
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
            }
        }

        // Trigger avatar upload file picker
        function triggerAvatarUpload() {
            switchTab('edit');
            setTimeout(() => {
                const fileInput = document.getElementById('avatar_file');
                if (fileInput) fileInput.click();
            }, 150);
        }

        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const label = document.getElementById('file-chosen-name');
                if (label) {
                    label.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                    label.className = 'text-xs text-emerald-600 font-bold';
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const formImg = document.getElementById('avatar-form-img');
                    if (formImg) {
                        formImg.src = e.target.result;
                    }
                }
                reader.readAsDataURL(file);
            }
        }

        function previewUrlAvatar(url) {
            if (url && url.length > 5) {
                const formImg = document.getElementById('avatar-form-img');
                if (formImg) {
                    formImg.src = url;
                }
            }
        }

        function togglePass(inputId, btn) {
            const el = document.getElementById(inputId);
            if (!el) return;
            if (el.type === 'password') {
                el.type = 'text';
                btn.innerHTML = '<i data-lucide="eye-off" class="w-4 h-4 text-indigo-600"></i>';
            } else {
                el.type = 'password';
                btn.innerHTML = '<i data-lucide="eye" class="w-4 h-4 text-slate-400"></i>';
            }
            refreshIcons();
        }
    </script>
</body>
</html>
