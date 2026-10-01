<?php
/**

 * Founder Module: Profile & Account Settings Studio
 * Executive Persona, Credentials, Social Links & Security Management
 * Vay Portal Typography, Clean Visual Contrast, Full Width Layout

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

                set_flash('success', 'Profile details successfully updated.');

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
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?? APP_NAME ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
    <style>
        html:not(.dark) .hero-profile-banner {
            background: radial-gradient(130% 100% at 0% 0%, #EEF2FF 0%, #F8FAFC 50%, #F1F5F9 100%);
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        }
        .hero-profile-banner {
            border-radius: 1.5rem;
            position: relative;
        }
        html.dark .hero-profile-banner {
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
        .form-label-clean {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.875rem;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 0.4rem;
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
        <main class="w-full px-4 sm:px-6 md:px-8 py-6 space-y-6" id="profile-main">
            
            <!-- Flash Feedback -->
            <?php if ($flash): ?>

                <div class="p-4 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center justify-between shadow-xs">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0 <?= $flash['type'] === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <span class="text-xs font-bold uppercase opacity-75">Notice</span>

                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>

                <div class="p-4 rounded-2xl text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-3 shadow-xs">
                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 text-rose-600"></i>

                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>


            <!-- Executive Persona Hero Card -->
            <div class="hero-profile-banner p-6 sm:p-8 relative overflow-hidden bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800">
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute right-32 -bottom-16 w-56 h-56 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col md:flex-row items-center sm:items-start justify-between gap-6 relative z-10">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-5">
                        <div class="relative group">
                            <img src="<?= $user['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=160' ?>" 
                                 class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover border-4 border-white dark:border-slate-800 shadow-md" id="avatar-preview-img">
                            <span class="absolute bottom-1 right-1 w-4 h-4 rounded-full border-2 border-white dark:border-slate-900 <?= $user['is_verified'] ? 'bg-emerald-500' : 'bg-amber-400' ?>"></span>
                        </div>

                        <div class="text-center sm:text-left space-y-1.5">
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                <h1 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                    <?= htmlspecialchars($user['name']) ?>
                                </h1>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?= $user['is_verified'] ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' ?>">
                                    <?= $user['is_verified'] ? 'KYC VERIFIED FOUNDER' : 'KYC PENDING' ?>
                                </span>
                            </div>
                            <div class="text-xs sm:text-sm text-indigo-600 dark:text-indigo-400 font-bold">
                                <?= htmlspecialchars($founderProfile['designation'] ?? 'Founder & CEO') ?>
                                <?php if ($company): ?>
                                    • <span class="text-slate-800 dark:text-slate-200 font-semibold"><?= htmlspecialchars($company['name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 flex flex-wrap items-center justify-center sm:justify-start gap-3 pt-0.5">
                                <span><?= htmlspecialchars($user['email']) ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars($user['city'] ?? 'India') ?></span>
                                <?php if (!empty($user['phone'])): ?>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($user['phone']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Public Profile Link Button -->
                    <div class="flex items-center space-x-2.5">
                        <a href="<?= url('founder/view.php') ?>" class="px-4 py-2.5 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl shadow-xs transition flex items-center space-x-2">
                            <i data-lucide="external-link" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                            <span>View Public Founder Profile</span>
                        </a>
                    </div>

                </div>
            </div>

            <!-- Profile Info Edit Form -->

            <div class="section-card p-6 sm:p-7 space-y-6">

                <form action="<?= url('founder/profile.php') ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="update_profile">


                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                                <i data-lucide="user-check" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                <span>Personal Information & Bio</span>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Visible to angel syndicates and venture investors researching your leadership background.</p>
                        </div>
                    </div>

                    <!-- Photo Upload Dropzone -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/80 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row items-center gap-5">
                        <div class="relative flex-shrink-0 group">
                            <img src="<?= $user['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=160' ?>" 
                                 class="w-20 h-20 rounded-2xl object-cover border-2 border-white dark:border-slate-800 shadow-md bg-white dark:bg-slate-900 transition group-hover:brightness-95" id="avatar-form-img">
                            <label for="avatar_file" class="absolute inset-0 bg-black/40 rounded-2xl flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition cursor-pointer" title="Click to choose photo">
                                <i data-lucide="camera" class="w-5 h-5 mb-0.5"></i>
                                <span class="text-[9px] font-bold">Change</span>
                            </label>
                        </div>
                        <div class="flex-1 text-center sm:text-left space-y-1.5 w-full">
                            <div class="font-bold text-slate-800 dark:text-white text-xs flex items-center justify-center sm:justify-start space-x-2">
                                <span>Executive Headshot</span>
                                <span class="text-[10px] px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-full font-bold">JPG, PNG, WEBP</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Upload a crisp professional headshot. Max file size: 8MB.</p>
                            
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 pt-1">
                                <label for="avatar_file" class="cursor-pointer inline-flex items-center space-x-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold text-xs shadow-xs transition">
                                    <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                    <span>Choose New Photo</span>
                                </label>
                                <input type="file" name="avatar_file" id="avatar_file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="previewAvatar(this)">
                                <span id="file-chosen-name" class="text-xs text-slate-500 italic">No new photo chosen</span>
                            </div>

                            <div class="pt-1">
                                <details class="text-xs text-slate-500 cursor-pointer">
                                    <summary class="hover:text-indigo-600 font-medium select-none">Or paste an external photo URL</summary>
                                    <div class="mt-1.5">
                                        <input type="url" name="avatar_url" id="avatar_url_input" value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>" placeholder="https://example.com/photo.jpg"
                                               class="w-full px-3 py-1.5 bg-white border border-slate-200 focus:border-indigo-600 rounded-lg text-xs text-slate-800 outline-none"
                                               oninput="previewUrlAvatar(this.value)">
                                    </div>
                                </details>

                            </div>

                            <details class="text-xs text-slate-500 pt-1">
                                <summary class="hover:text-indigo-600 font-bold cursor-pointer select-none">Or paste an online image web link</summary>
                                <div class="mt-2 flex items-center gap-2">
                                    <input type="url" name="avatar_url" id="avatar_url_input" value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>" placeholder="https://example.com/photo.jpg"
                                           class="w-full px-4 py-2.5 bg-white border border-slate-200 focus:border-indigo-600 rounded-xl text-sm text-slate-900 outline-none font-medium"
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
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                   class="form-input-clean font-medium">
                        </div>
                        <div>
                            <label class="form-label-clean">
                                <span>Operating City</span>
                            </label>
                            <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? 'Mumbai') ?>"
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
                            <span>Founder Narrative & Entrepreneurial Bio</span>
                        </label>
                        <textarea name="bio" rows="4" placeholder="Detail your background, prior startups founded, engineering patents, or key domain authority..."
                                  class="form-input-clean font-normal leading-relaxed"><?= htmlspecialchars($founderProfile['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 transition flex items-center space-x-2">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>

                            <span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Associated Startup Entity Card -->
            <?php if ($company): ?>

                <div class="section-card p-6">
                    <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-indigo-600"></i>
                            <span>Corporate Entity Affiliation</span>
                        </h3>
                        <a href="<?= url('founder/company.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold">
                            Manage Startup Profile →
                        </a>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-100 flex items-center justify-between">
                        <div class="flex items-center space-x-3.5">
                            <img src="<?= $company['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100' ?>" class="w-12 h-12 rounded-xl object-cover border border-slate-200 shadow-xs">
                            <div>
                                <div class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($company['name']) ?></div>
                                <div class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($company['industry']) ?> • <?= htmlspecialchars($company['stage']) ?></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block tracking-wider">Equity Allocation</span>
                            <span class="font-extrabold text-indigo-600 text-sm"><?= !empty($company['equity_percent']) ? $company['equity_percent'] . '%' : 'Founder Stake' ?></span>

                        </div>
                    </div>
                </div>
            <?php endif; ?>


            <!-- Password Change Security Card -->
            <div class="section-card p-6 sm:p-7 space-y-4">
                <div class="flex items-center space-x-2 border-b border-slate-100 pb-3">
                    <i data-lucide="lock" class="w-4 h-4 text-slate-600"></i>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Security & Account Password</h3>
                </div>

                <form action="<?= url('founder/profile.php') ?>" method="POST" class="space-y-4 text-xs">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="change_password">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Current Password</label>
                            <input type="password" name="current_password" required class="form-input-clean text-xs">

                        </div>

                        <div>

                            <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">New Password</label>
                            <input type="password" name="new_password" required class="form-input-clean text-xs">

                        </div>

                        <div>

                            <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Confirm Password</label>
                            <input type="password" name="confirm_password" required class="form-input-clean text-xs">
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs transition shadow-xs">
                            Update Security Password

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

                const file = input.files[0];
                const label = document.getElementById('file-chosen-name');
                if (label) {
                    label.textContent = 'Selected: ' + file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                    label.className = 'text-xs text-emerald-600 font-bold';
                }

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
