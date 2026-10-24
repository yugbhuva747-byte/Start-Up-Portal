<?php
/**
 * Founder Module: Dedicated Edit Profile Studio
 * Personal Information, Executive Headshot, Bio, Social Links & Security Credentials
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Edit Founder Profile';

$founderProfile = null;
$company = null;
$error = '';
$flash = get_flash();

if ($db) {
    $fpStmt = $db->prepare("SELECT * FROM founder_profiles WHERE user_id = ?");
    $fpStmt->execute([$user['id']]);
    $founderProfile = $fpStmt->fetch();

    $cStmt = $db->prepare("
        SELECT c.*, cf.equity_percent, cf.is_signatory
        FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ?
        LIMIT 1
    ");
    $cStmt->execute([$user['id']]);
    $company = $cStmt->fetch();
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
            $coverUrl = trim($_POST['cover_url'] ?? '');
            $designation = trim($_POST['designation'] ?? 'Founder & CEO');
            $bio = trim($_POST['bio'] ?? '');
            $linkedin = trim($_POST['linkedin_url'] ?? '');
            $website = trim($_POST['website_url'] ?? '');
            $pan = strtoupper(trim($_POST['pan_number'] ?? ''));
            $skills = trim($_POST['skills'] ?? '');
            $passions = trim($_POST['passions'] ?? '');
            $hobbies = trim($_POST['hobbies'] ?? '');
            $superpower = trim($_POST['superpower'] ?? '');
            $education = trim($_POST['education'] ?? '');

            if (empty($name)) {
                $error = 'Legal Full Name cannot be empty.';
            }

            // Handle custom avatar photo upload
            if (empty($error) && !empty($_FILES['avatar_file']['name'])) {
                $uploadRes = handle_avatar_upload($_FILES['avatar_file'], $user['id']);
                if (!empty($uploadRes['success'])) {
                    $avatarUrl = $uploadRes['url'];
                } elseif (!empty($uploadRes['error'])) {
                    $error = $uploadRes['error'];
                }
            }
            if (empty($avatarUrl)) {
                $avatarUrl = $user['avatar_url'] ?? '';
            }

            // Handle custom cover banner upload
            if (empty($error) && !empty($_FILES['cover_file']['name'])) {
                $coverRes = handle_cover_upload($_FILES['cover_file'], $user['id']);
                if (!empty($coverRes['success'])) {
                    $coverUrl = $coverRes['url'];
                } elseif (!empty($coverRes['error'])) {
                    $error = $coverRes['error'];
                }
            }
            if (empty($coverUrl)) {
                $coverUrl = $founderProfile['cover_url'] ?? '';
            }

            if (empty($error)) {
                // Update user record
                $db->prepare("UPDATE users SET name = ?, phone = ?, city = ?, avatar_url = ? WHERE id = ?")
                   ->execute([$name, $phone, $city, $avatarUrl, $user['id']]);

                // Update founder profile record including persona fields
                $updFP = $db->prepare("
                    INSERT INTO founder_profiles (user_id, designation, bio, linkedin_url, website_url, cover_url, pan_number, skills, passions, hobbies, superpower, education)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        designation = VALUES(designation),
                        bio = VALUES(bio),
                        linkedin_url = VALUES(linkedin_url),
                        website_url = VALUES(website_url),
                        cover_url = VALUES(cover_url),
                        pan_number = VALUES(pan_number),
                        skills = VALUES(skills),
                        passions = VALUES(passions),
                        hobbies = VALUES(hobbies),
                        superpower = VALUES(superpower),
                        education = VALUES(education)
                ");
                $updFP->execute([$user['id'], $designation, $bio, $linkedin, $website, $coverUrl, $pan, $skills, $passions, $hobbies, $superpower, $education]);

                log_audit($user['id'], 'UPDATE_PROFILE', 'users', $user['id'], 'Founder updated personal profile');
                set_flash('success', 'Profile details and persona points successfully updated.');
                header('Location: ' . url('founder/profile.php'));
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
                    set_flash('success', 'Security password successfully updated.');
                    header('Location: ' . url('founder/profile.php'));
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
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
    <style>
        .hero-profile-banner {
            border-radius: 1.5rem;
            position: relative;
            background: radial-gradient(130% 100% at 0% 0%, #EEF2FF 0%, #F8FAFC 50%, #F1F5F9 100%);
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        }
        .dark .hero-profile-banner, html.dark .hero-profile-banner {
            background: radial-gradient(130% 100% at 0% 0%, #17213A 0%, #0F172A 55%, #111827 100%) !important;
            border-color: #1E293B !important;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
        }

        .section-card {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1.25rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }
        .dark .section-card, html.dark .section-card {
            background-color: #111827 !important;
            border-color: #1E293B !important;
            color: #F8FAFC !important;
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
        .dark .form-input-clean, html.dark .form-input-clean {
            background-color: #1E293B !important;
            border-color: #334155 !important;
            color: #F8FAFC !important;
        }
        .form-input-clean:hover {
            background-color: #F1F5F9;
            border-color: #CBD5E1;
        }
        .dark .form-input-clean:hover, html.dark .form-input-clean:hover {
            background-color: #243047 !important;
            border-color: #475569 !important;
        }
        .form-input-clean:focus {
            background-color: #FFFFFF;
            border-color: #4F46E5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .dark .form-input-clean:focus, html.dark .form-input-clean:focus {
            background-color: #1E293B !important;
            border-color: #6366F1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2) !important;
        }

        .form-label-clean {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 0.4rem;
        }
        .dark .form-label-clean, html.dark .form-label-clean {
            color: #E2E8F0 !important;
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
        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 space-y-6" id="edit-profile-main">
            
            <!-- Breadcrumb Navigation -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400 font-medium pb-1">
                <div class="flex items-center space-x-2">
                    <a href="<?= url('founder/dashboard.php') ?>" class="hover:text-slate-900 dark:hover:text-white transition flex items-center space-x-1">
                        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
                        <span>Dashboard</span>
                    </a>
                    <span>/</span>
                    <a href="<?= url('founder/profile.php') ?>" class="hover:text-slate-900 dark:hover:text-white transition">Founder Profile</a>
                    <span>/</span>
                    <span class="text-indigo-600 dark:text-indigo-400 font-bold">Edit Profile Options</span>
                </div>

                <a href="<?= url('founder/profile.php') ?>" class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl shadow-2xs transition self-start sm:self-auto">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to Profile Overview</span>
                </a>
            </div>

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

            <!-- Compact Header Banner -->
            <div class="hero-profile-banner p-5 sm:p-6 relative overflow-hidden bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="relative flex-shrink-0">
                            <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Avatar" class="w-14 h-14 rounded-2xl object-cover border-2 border-white dark:border-slate-800 shadow-md bg-white dark:bg-slate-900">
                            <?php else: ?>
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white flex items-center justify-center font-bold text-lg border-2 border-white dark:border-slate-800 shadow-md">
                                    <?= htmlspecialchars($userInitials) ?>
                                </div>
                            <?php endif; ?>
                            <span class="absolute bottom-0 right-0 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-900 <?= $user['is_verified'] ? 'bg-emerald-500' : 'bg-amber-400' ?>"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Edit Profile &amp; Settings</h1>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    <?= htmlspecialchars($user['name']) ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Modify executive bio, official designation, contact details, headshot, and authentication password.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="<?= url('founder/profile.php') ?>" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition flex items-center space-x-1.5">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            <span>View Profile</span>
                        </a>
                        <a href="<?= url('founder/view.php') ?>" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/50 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-xs font-bold rounded-xl transition flex items-center space-x-1.5">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            <span>Public View</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Profile Info Edit Form Studio -->
            <div class="section-card p-6 sm:p-7 space-y-6">

                <form action="<?= url('founder/edit_profile.php') ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="update_profile">

                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                                <i data-lucide="user-check" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                <span>Personal Information &amp; Bio</span>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Visible to angel syndicates and venture investors reviewing your leadership track record.</p>
                        </div>
                    </div>

                    <!-- Profile Cover Banner Background Studio -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/80 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <div class="font-bold text-slate-800 dark:text-white text-xs flex items-center space-x-2">
                                    <span>Profile Cover Banner Background</span>
                                    <span class="text-[10px] px-2 py-0.5 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded-full font-bold">Default: 3D Tech Mesh</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Customize your profile top background with a custom graphic or banner photo. Recommended: 1200x320px.</p>
                            </div>
                        </div>

                        <!-- Live Banner Preview Strip -->
                        <div class="relative h-28 sm:h-32 w-full rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 profile-cover-mesh flex items-center justify-center">
                            <?php if (!empty($founderProfile['cover_url'])): ?>
                                <img src="<?= htmlspecialchars($founderProfile['cover_url']) ?>" alt="Banner Preview" id="cover-form-img" class="absolute inset-0 w-full h-full object-cover">
                            <?php else: ?>
                                <img src="" alt="Banner Preview" id="cover-form-img" class="absolute inset-0 w-full h-full object-cover hidden">
                                <div class="text-xs text-white/80 font-bold uppercase tracking-wider flex items-center gap-1.5" id="cover-form-placeholder">
                                    <i data-lucide="sparkles" class="w-4 h-4 text-indigo-400"></i>
                                    <span>3D Geometric Mesh Wireframe Active</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <label for="cover_file" class="cursor-pointer inline-flex items-center space-x-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold text-xs shadow-xs transition">
                                <i data-lucide="image" class="w-3.5 h-3.5"></i>
                                <span>Upload Custom Banner</span>
                            </label>
                            <input type="file" name="cover_file" id="cover_file" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewCover(this)">
                            <span id="cover-chosen-name" class="text-xs text-slate-500 italic">No custom file chosen</span>
                        </div>

                        <details class="text-xs text-slate-500 dark:text-slate-400 pt-0.5 cursor-pointer">
                            <summary class="hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold select-none flex items-center gap-1.5">
                                <i data-lucide="link" class="w-3.5 h-3.5"></i>
                                <span>Or paste online banner image URL</span>
                            </summary>
                            <div class="mt-2">
                                <input type="url" name="cover_url" id="cover_url_input" value="<?= htmlspecialchars($founderProfile['cover_url'] ?? '') ?>" placeholder="https://images.unsplash.com/your-cover.jpg"
                                       class="form-input-clean text-xs font-mono"
                                       oninput="previewUrlCover(this.value)">
                            </div>
                        </details>
                    </div>

                    <!-- Clean Photo Upload Studio -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/80 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row items-center gap-5">
                        <div class="relative flex-shrink-0 group">
                            <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" 
                                     alt="Headshot"
                                     class="w-20 h-20 rounded-2xl object-cover border-2 border-white dark:border-slate-800 shadow-md bg-white dark:bg-slate-900 transition group-hover:brightness-95" id="avatar-form-img">
                            <?php else: ?>
                                <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 text-white flex items-center justify-center font-bold text-xl border-2 border-white dark:border-slate-800 shadow-md" id="avatar-form-placeholder">
                                    <?= htmlspecialchars($userInitials) ?>
                                </div>
                                <img src="" alt="Headshot" class="w-20 h-20 rounded-2xl object-cover border-2 border-white dark:border-slate-800 shadow-md bg-white dark:bg-slate-900 hidden" id="avatar-form-img">
                            <?php endif; ?>
                            <label for="avatar_file" class="absolute inset-0 bg-black/40 rounded-2xl flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition cursor-pointer" title="Click to choose photo">
                                <i data-lucide="camera" class="w-5 h-5 mb-0.5"></i>
                                <span class="text-[9px] font-bold">Change</span>
                            </label>
                        </div>

                        <div class="flex-1 text-center sm:text-left space-y-2 w-full">
                            <div class="font-bold text-slate-800 dark:text-white text-xs flex items-center justify-center sm:justify-start space-x-2">
                                <span>Executive Headshot</span>
                                <span class="text-[10px] px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-full font-bold">JPG, PNG, WEBP</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Upload a professional headshot. Max file size: 8MB.</p>
                            
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 pt-1">
                                <label for="avatar_file" class="cursor-pointer inline-flex items-center space-x-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs shadow-xs transition">
                                    <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                    <span>Choose New Photo</span>
                                </label>
                                <input type="file" name="avatar_file" id="avatar_file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="previewAvatar(this)">
                                <span id="file-chosen-name" class="text-xs text-slate-500 italic">No file chosen</span>
                            </div>

                            <!-- Single, Clean Web Link Toggle -->
                            <details class="text-xs text-slate-500 dark:text-slate-400 pt-1 cursor-pointer">
                                <summary class="hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold select-none flex items-center gap-1.5">
                                    <i data-lucide="link" class="w-3.5 h-3.5"></i>
                                    <span>Or paste an online photo URL</span>
                                </summary>
                                <div class="mt-2">
                                    <input type="url" name="avatar_url" id="avatar_url_input" value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>" placeholder="https://images.unsplash.com/your-photo.jpg"
                                           class="form-input-clean text-xs font-mono"
                                           oninput="previewUrlAvatar(this.value)">
                                </div>
                            </details>
                        </div>
                    </div>

                    <!-- Fields in 2 & 3 Columns -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label-clean">
                                <span>Legal Full Name <span class="text-rose-500">*</span></span>
                            </label>
                            <input type="text" name="name" required value="<?= htmlspecialchars($user['name']) ?>"
                                   class="form-input-clean font-semibold">
                        </div>
                        <div>
                            <label class="form-label-clean">
                                <span>Official Designation</span>
                            </label>
                            <input type="text" name="designation" value="<?= htmlspecialchars($founderProfile['designation'] ?? 'Founder & CEO') ?>"
                                   class="form-input-clean font-semibold">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label-clean">
                                <span>Direct Phone</span>
                            </label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+91 98765 43210"
                                   class="form-input-clean font-medium">
                        </div>
                        <div>
                            <label class="form-label-clean">
                                <span>Operating City</span>
                            </label>
                            <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? 'Bengaluru') ?>" placeholder="e.g. Bengaluru, Mumbai"
                                   class="form-input-clean font-medium">
                        </div>
                        <div>
                            <label class="form-label-clean">
                                <span>Tax PAN Number</span>
                                <span class="text-[11px] text-slate-400 font-normal">Confidential</span>
                            </label>
                            <input type="text" name="pan_number" value="<?= htmlspecialchars($founderProfile['pan_number'] ?? '') ?>" placeholder="ABCDE1234F"
                                   class="form-input-clean font-mono uppercase">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label-clean">
                                <span>LinkedIn Profile URL</span>
                            </label>
                            <div class="relative">
                                <input type="url" name="linkedin_url" value="<?= htmlspecialchars($founderProfile['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/username"
                                       class="form-input-clean pl-10 font-medium">
                                <i data-lucide="linkedin" class="w-4 h-4 text-blue-600 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            </div>
                        </div>
                        <div>
                            <label class="form-label-clean">
                                <span>Personal Portfolio / Blog URL</span>
                            </label>
                            <div class="relative">
                                <input type="url" name="website_url" value="<?= htmlspecialchars($founderProfile['website_url'] ?? '') ?>" placeholder="https://founder.io"
                                       class="form-input-clean pl-10 font-medium">
                                <i data-lucide="globe" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="form-label-clean">
                            <span>Founder Narrative &amp; Entrepreneurial Bio</span>
                        </label>
                        <textarea name="bio" rows="4" placeholder="Detail your background, prior startups founded, engineering patents, or key domain authority..."
                                  class="form-input-clean font-normal leading-relaxed"><?= htmlspecialchars($founderProfile['bio'] ?? '') ?></textarea>
                    </div>

                    <!-- Founder Persona: Domain Expertise, Passions & Hobbies -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                                <i data-lucide="sparkles" class="w-4 h-4 text-amber-500"></i>
                                <span>Domain Expertise, Passions &amp; Personal Interests</span>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Help investors, co-founders, and partners understand your domain superpowers and personal background.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label-clean">
                                    <span>Core Domain &amp; Skills (Comma-separated)</span>
                                    <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold">e.g. Marketing, Chemical</span>
                                </label>
                                <input type="text" name="skills" value="<?= htmlspecialchars($founderProfile['skills'] ?? '') ?>"
                                       placeholder="e.g. Performance Marketing, Chemical Engineering, AI/ML, B2B Growth"
                                       class="form-input-clean font-medium">
                                <p class="form-hint">Key functional skills and technical knowledge domains.</p>
                            </div>
                            <div>
                                <label class="form-label-clean">
                                    <span>Passions &amp; Industry Focus</span>
                                    <span class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold">What drives you</span>
                                </label>
                                <input type="text" name="passions" value="<?= htmlspecialchars($founderProfile['passions'] ?? '') ?>"
                                       placeholder="e.g. Specialty Chemicals, Industrial DeepTech, Clean Energy, Web3"
                                       class="form-input-clean font-medium">
                                <p class="form-hint">Industries, missions, or technological frontiers you care about.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="form-label-clean">
                                    <span>Hobbies &amp; Personal Interests</span>
                                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">Human side</span>
                                </label>
                                <input type="text" name="hobbies" value="<?= htmlspecialchars($founderProfile['hobbies'] ?? '') ?>"
                                       placeholder="e.g. Marathon Running, Chess, Technical Blogging, Trekking"
                                       class="form-input-clean font-medium">
                                <p class="form-hint">How you recharge outside building your venture.</p>
                            </div>
                            <div>
                                <label class="form-label-clean">
                                    <span>Founder Superpower</span>
                                    <span class="text-[11px] text-violet-600 dark:text-violet-400 font-semibold">Unfair advantage</span>
                                </label>
                                <input type="text" name="superpower" value="<?= htmlspecialchars($founderProfile['superpower'] ?? '') ?>"
                                       placeholder="e.g. 0-to-1 Product Execution & High-Velocity GTM"
                                       class="form-input-clean font-medium">
                                <p class="form-hint">Your biggest superpower in execution or leadership.</p>
                            </div>
                            <div>
                                <label class="form-label-clean">
                                    <span>Education &amp; Alma Mater</span>
                                    <span class="text-[11px] text-slate-500 font-semibold">Background</span>
                                </label>
                                <input type="text" name="education" value="<?= htmlspecialchars($founderProfile['education'] ?? '') ?>"
                                       placeholder="e.g. IIT Bombay • Chemical Engineering"
                                       class="form-input-clean font-medium">
                                <p class="form-hint">Degree, university, or research background.</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <a href="<?= url('founder/profile.php') ?>" class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition">
                            Cancel &amp; Return
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 transition flex items-center space-x-2">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Change Security Card -->
            <div class="section-card p-6 sm:p-7 space-y-4">
                <div class="flex items-center space-x-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <i data-lucide="lock" class="w-4 h-4 text-slate-600 dark:text-slate-400"></i>
                    <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Security &amp; Account Password</h2>
                </div>

                <form action="<?= url('founder/edit_profile.php') ?>" method="POST" class="space-y-4 text-xs">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="change_password">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                        <!-- Current Password -->
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 text-[11px] mb-1 uppercase tracking-wider">Current Password</label>
                            <div class="relative">
                                <input type="password" id="current_password" name="current_password" required class="form-input-clean text-xs pr-10">
                                <button type="button" onclick="togglePass('current_password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 text-[11px] mb-1 uppercase tracking-wider">New Password</label>
                            <div class="relative">
                                <input type="password" id="new_password" name="new_password" required minlength="6" class="form-input-clean text-xs pr-10">
                                <button type="button" onclick="togglePass('new_password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                            <span class="text-[10.5px] text-slate-400 mt-1 block">Min. 6 characters</span>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 text-[11px] mb-1 uppercase tracking-wider">Confirm Password</label>
                            <div class="relative">
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6" class="form-input-clean text-xs pr-10">
                                <button type="button" onclick="togglePass('confirm_password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs transition shadow-xs flex items-center gap-1.5">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            <span>Update Security Password</span>
                        </button>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <script>
        function refreshIcons() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
        refreshIcons();
        document.addEventListener('DOMContentLoaded', refreshIcons);
        window.addEventListener('load', refreshIcons);

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
                    const formPl = document.getElementById('avatar-form-placeholder');

                    if (formImg) {
                        formImg.src = e.target.result;
                        formImg.classList.remove('hidden');
                    }
                    if (formPl) formPl.classList.add('hidden');
                }
                reader.readAsDataURL(file);
            }
        }

        function previewUrlAvatar(url) {
            if (url && url.length > 5) {
                const formImg = document.getElementById('avatar-form-img');
                const formPl = document.getElementById('avatar-form-placeholder');

                if (formImg) {
                    formImg.src = url;
                    formImg.classList.remove('hidden');
                }
                if (formPl) formPl.classList.add('hidden');
            }
        }

        function previewCover(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const label = document.getElementById('cover-chosen-name');
                if (label) {
                    label.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                    label.className = 'text-xs text-emerald-600 font-bold';
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const coverImg = document.getElementById('cover-form-img');
                    const coverPl = document.getElementById('cover-form-placeholder');
                    if (coverImg) {
                        coverImg.src = e.target.result;
                        coverImg.classList.remove('hidden');
                    }
                    if (coverPl) coverPl.classList.add('hidden');
                }
                reader.readAsDataURL(file);
            }
        }

        function previewUrlCover(url) {
            const coverImg = document.getElementById('cover-form-img');
            const coverPl = document.getElementById('cover-form-placeholder');
            if (url && url.length > 5) {
                if (coverImg) {
                    coverImg.src = url;
                    coverImg.classList.remove('hidden');
                }
                if (coverPl) coverPl.classList.add('hidden');
            } else {
                if (coverImg) coverImg.classList.add('hidden');
                if (coverPl) coverPl.classList.remove('hidden');
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
