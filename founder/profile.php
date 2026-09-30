<?php
/**
 * Founder Module: Profile & Account Settings
 * Ultra-Modern SaaS Form UI
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Founder Profile & Account Settings';

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

                log_audit($user['id'], 'UPDATE_PROFILE', 'users', $user['id'], 'Founder updated personal profile');
                set_flash('success', 'Profile credentials successfully saved.');
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
                    set_flash('success', 'Password updated successfully.');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Founder Profile Settings • <?= APP_NAME ?></title>
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

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-4xl w-full mx-auto" id="profile-main">
            
            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-2.5">
                        <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-4 h-4 <?= $flash['type'] === 'success' ? 'text-emerald-600' : 'text-rose-600' ?> flex-shrink-0"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium">Saved</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2.5 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Founder Identity Hero Card -->
            <div class="clean-card rounded-2xl p-5 sm:p-6 bg-gradient-to-r from-white via-slate-50/50 to-indigo-50/30">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="relative group">
                            <img src="<?= $user['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=160' ?>" 
                                 class="w-16 h-16 sm:w-18 sm:h-18 rounded-2xl object-cover border-2 border-white shadow-md bg-white">
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full <?= $user['is_verified'] ? 'bg-emerald-500' : 'bg-amber-500' ?> border-2 border-white flex items-center justify-center text-white" title="<?= $user['is_verified'] ? 'Verified Founder' : 'Pending Verification' ?>">
                                <i data-lucide="<?= $user['is_verified'] ? 'check' : 'clock' ?>" class="w-3 h-3"></i>
                            </div>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-base sm:text-lg font-black text-slate-900 tracking-tight"><?= htmlspecialchars($user['name']) ?></h1>
                                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border <?= $user['is_verified'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' ?>">
                                    <?= $user['is_verified'] ? 'DigiLocker Verified' : 'KYC Review Pending' ?>
                                </span>
                            </div>
                            <div class="text-xs text-slate-600 font-semibold mt-0.5">
                                <?= htmlspecialchars($founderProfile['designation'] ?? 'Founder & CEO') ?>
                                <?php if ($company): ?>
                                    <span class="text-slate-400 font-normal">•</span> <span class="text-indigo-600 font-bold"><?= htmlspecialchars($company['name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5 flex items-center space-x-2">
                                <span><?= htmlspecialchars($user['email']) ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars($user['city'] ?? 'India') ?></span>
                            </div>
                        </div>
                    </div>
                    <a href="<?= url('founder/view.php') ?>" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold flex items-center justify-center space-x-1.5 shadow-2xs transition">
                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Public Preview</span>
                    </a>
                </div>
            </div>

            <!-- Profile Info Edit Form -->
            <div class="clean-card rounded-2xl p-6 sm:p-7">
                <form action="<?= url('founder/profile.php') ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="update_profile">

                    <div class="flex items-center space-x-2 border-b border-slate-100 pb-3">
                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                            <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                        </div>
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                            1. Personal Information & Identity
                        </h2>
                    </div>

                    <!-- Profile Photo Uploader Dropzone -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/80 border border-slate-200/80 flex flex-col sm:flex-row items-center gap-5">
                        <div class="relative flex-shrink-0 group cursor-pointer" onclick="document.getElementById('avatar_file').click()">
                            <img src="<?= $user['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=160' ?>" 
                                 class="w-20 h-20 rounded-2xl object-cover border-2 border-white shadow-md bg-white transition group-hover:brightness-90" id="avatar-form-img">
                            <div class="absolute inset-0 bg-slate-900/50 rounded-2xl flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition">
                                <i data-lucide="camera" class="w-5 h-5 mb-0.5"></i>
                                <span class="text-[9px] font-bold">Change</span>
                            </div>
                        </div>

                        <div class="flex-1 text-center sm:text-left space-y-2 w-full">
                            <div>
                                <div class="font-bold text-slate-900 text-xs flex items-center justify-center sm:justify-start space-x-2">
                                    <span>Founder Display Portrait</span>
                                    <span class="text-[10px] px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold">JPG, PNG, WEBP</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">High-resolution authentic photo increases founder trustworthiness with institutional angels.</p>
                            </div>

                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 pt-0.5">
                                <label for="avatar_file" class="cursor-pointer inline-flex items-center space-x-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs shadow-sm transition">
                                    <i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i>
                                    <span>Upload New Photo</span>
                                </label>
                                <input type="file" name="avatar_file" id="avatar_file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="previewAvatar(this)">
                                <span id="file-chosen-name" class="text-[11px] text-slate-500 font-medium">No new file selected</span>
                            </div>

                            <details class="text-[11px] text-slate-500 pt-1">
                                <summary class="hover:text-indigo-600 font-semibold cursor-pointer select-none">Or paste an online image web link</summary>
                                <div class="mt-2 flex items-center gap-2">
                                    <input type="url" name="avatar_url" id="avatar_url_input" value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>" placeholder="https://example.com/photo.jpg"
                                           class="w-full px-3 py-1.5 bg-white border border-slate-200 focus:border-indigo-600 rounded-xl text-xs text-slate-800 outline-none"
                                           oninput="previewUrlAvatar(this.value)">
                                </div>
                            </details>
                        </div>
                    </div>

                    <!-- Personal Information Inputs -->
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    <span>Full Legal Name</span>
                                    <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="user" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="name" required value="<?= htmlspecialchars($user['name']) ?>"
                                           class="w-full pl-2.5 pr-3.5 py-2.5 bg-transparent rounded-xl text-xs text-slate-900 outline-none font-medium">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Official Designation</label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="briefcase" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="designation" value="<?= htmlspecialchars($founderProfile['designation'] ?? 'Founder & CEO') ?>"
                                           class="w-full pl-2.5 pr-3.5 py-2.5 bg-transparent rounded-xl text-xs text-slate-900 outline-none font-medium">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Direct Phone Number</label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="phone" class="w-4 h-4"></i>
                                    </span>
                                    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+91 98765 43210"
                                           class="w-full pl-2.5 pr-3.5 py-2.5 bg-transparent rounded-xl text-xs text-slate-900 outline-none font-medium">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Operating City</label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? 'Bengaluru') ?>"
                                           class="w-full pl-2.5 pr-3.5 py-2.5 bg-transparent rounded-xl text-xs text-slate-900 outline-none font-medium">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                                    <span>Tax PAN Number</span>
                                    <span class="text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded font-bold">Confidential</span>
                                </label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="pan_number" value="<?= htmlspecialchars($founderProfile['pan_number'] ?? '') ?>" placeholder="ABCDE1234F" maxlength="10"
                                           class="w-full pl-2.5 pr-3.5 py-2.5 bg-transparent rounded-xl text-xs text-slate-900 uppercase font-mono font-bold outline-none">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">LinkedIn Profile URL</label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="linkedin" class="w-4 h-4"></i>
                                    </span>
                                    <input type="url" name="linkedin_url" value="<?= htmlspecialchars($founderProfile['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/username"
                                           class="w-full pl-2.5 pr-3.5 py-2.5 bg-transparent rounded-xl text-xs text-slate-900 outline-none font-medium">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Personal Portfolio / Link</label>
                                <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                    <span class="pl-3.5 text-slate-400">
                                        <i data-lucide="globe" class="w-4 h-4"></i>
                                    </span>
                                    <input type="url" name="website_url" value="<?= htmlspecialchars($founderProfile['website_url'] ?? '') ?>" placeholder="https://founder.xyz"
                                           class="w-full pl-2.5 pr-3.5 py-2.5 bg-transparent rounded-xl text-xs text-slate-900 outline-none font-medium">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                                <span>Founder Bio & Entrepreneurial Background</span>
                                <span class="text-[10.5px] text-slate-400" id="bio-counter">Domain Authority</span>
                            </label>
                            <div class="form-input-group border border-slate-200 rounded-xl bg-slate-50 transition p-1">
                                <textarea name="bio" rows="4" placeholder="Tell investors about your core domain expertise, previous startup exits, engineering patents, or key milestone achievements..."
                                          class="w-full p-2.5 bg-transparent rounded-lg text-xs text-slate-900 outline-none leading-relaxed font-normal placeholder:text-slate-400 resize-y"><?= htmlspecialchars($founderProfile['bio'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-100 transition flex items-center space-x-1.5">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Associated Startup Entity Card -->
            <?php if ($company): ?>
                <div class="clean-card rounded-2xl p-6 sm:p-7">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
                            </div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Associated Venture Entity</h3>
                        </div>
                        <a href="<?= url('founder/company.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center space-x-1">
                            <span>Manage Company Profile</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-base shadow-xs">
                                <?= strtoupper(substr($company['name'], 0, 2)) ?>
                            </div>
                            <div>
                                <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($company['name']) ?></div>
                                <div class="text-[11px] text-slate-500 mt-0.5"><?= htmlspecialchars($company['industry']) ?> • <?= htmlspecialchars($company['stage']) ?></div>
                            </div>
                        </div>
                        <div class="sm:text-right">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block tracking-wider">Cap Table Stake</span>
                            <span class="font-extrabold text-indigo-600 text-sm"><?= !empty($company['equity_percent']) ? $company['equity_percent'] . '%' : 'Founder' ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Password & Security Card -->
            <div class="clean-card rounded-2xl p-6 sm:p-7">
                <div class="flex items-center space-x-2 border-b border-slate-100 pb-3 mb-5">
                    <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">
                        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Security & Account Password</h3>
                </div>

                <form action="<?= url('founder/profile.php') ?>" method="POST" class="space-y-4 text-xs">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="change_password">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 text-xs mb-1.5">Current Password</label>
                            <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                <input type="password" id="curr-pass" name="current_password" required
                                       class="w-full pl-3.5 pr-10 py-2.5 bg-transparent rounded-xl text-xs text-slate-900 outline-none font-medium">
                                <button type="button" onclick="togglePass('curr-pass', this)" class="absolute right-3 text-slate-400 hover:text-slate-600">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 text-xs mb-1.5">New Password</label>
                            <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                <input type="password" id="new-pass" name="new_password" required minlength="6"
                                       class="w-full pl-3.5 pr-10 py-2.5 bg-transparent rounded-xl text-xs text-slate-900 outline-none font-medium">
                                <button type="button" onclick="togglePass('new-pass', this)" class="absolute right-3 text-slate-400 hover:text-slate-600">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 text-xs mb-1.5">Confirm New Password</label>
                            <div class="relative flex items-center form-input-group border border-slate-200 rounded-xl bg-slate-50 transition">
                                <input type="password" id="conf-pass" name="confirm_password" required minlength="6"
                                       class="w-full pl-3.5 pr-10 py-2.5 bg-transparent rounded-xl text-xs text-slate-900 outline-none font-medium">
                                <button type="button" onclick="togglePass('conf-pass', this)" class="absolute right-3 text-slate-400 hover:text-slate-600">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs transition shadow-sm">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#profile-main", { duration: 0.35, y: 8, opacity: 0, ease: "power2.out" });

        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar-form-img').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
                document.getElementById('file-chosen-name').textContent = input.files[0].name;
            }
        }

        function previewUrlAvatar(url) {
            if (url && url.length > 5) {
                document.getElementById('avatar-form-img').src = url;
            }
        }

        function togglePass(inputId, btn) {
            const el = document.getElementById(inputId);
            if (el.type === 'password') {
                el.type = 'text';
                btn.innerHTML = '<i data-lucide="eye-off" class="w-4 h-4"></i>';
            } else {
                el.type = 'password';
                btn.innerHTML = '<i data-lucide="eye" class="w-4 h-4"></i>';
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>
