<?php
/**
 * Founder Module: Profile & Account Settings
 * Clean White / Light Theme, Small Crisp Typography
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
        $error = 'Security token invalid.';
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
                } else {
                    $error = 'New passwords must match and be at least 6 characters.';
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-4xl w-full mx-auto" id="profile-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header & Public View Shortcut -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Founder Profile & Account Settings</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Manage your persona, verification credentials, and venture affiliations.</p>
                </div>
                <a href="<?= url('founder/view.php') ?>" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    <span>Preview Public Profile</span>
                </a>
            </div>

            <!-- Profile Summary Preview Card -->
            <div class="card-clean rounded-2xl p-5">
                <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-3 sm:space-y-0 sm:space-x-4">
                    <img src="<?= $user['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=160' ?>" 
                         class="w-16 h-16 rounded-2xl object-cover border-2 border-slate-200 shadow-sm" id="avatar-preview-img">
                    <div class="flex-1 text-center sm:text-left">
                        <div class="flex flex-col sm:flex-row sm:items-center space-y-1 sm:space-y-0 sm:space-x-2">
                            <h2 class="text-base font-bold text-slate-900"><?= htmlspecialchars($user['name']) ?></h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $user['is_verified'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                                <?= $user['is_verified'] ? 'KYC VERIFIED' : 'KYC UNDER REVIEW' ?>
                            </span>
                        </div>
                        <div class="text-xs text-indigo-600 font-semibold mt-0.5">
                            <?= htmlspecialchars($founderProfile['designation'] ?? 'Founder & CEO') ?>
                            <?php if ($company): ?>
                                • <span class="text-slate-700 font-bold"><?= htmlspecialchars($company['name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">
                            <?= htmlspecialchars($user['email']) ?> • <?= htmlspecialchars($user['city'] ?? 'India') ?>
                        </div>
                    </div>
                    <div>
                        <a href="<?= url('founder/view.php') ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1 transition">
                            <i data-lucide="external-link" class="w-3 h-3 text-slate-400"></i>
                            <span>View Full Profile</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Profile Info Edit Form -->
            <div class="card-clean rounded-2xl p-6">
                <form action="<?= url('founder/profile.php') ?>" method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="update_profile">

                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 flex items-center space-x-1.5">
                        <i data-lucide="user-check" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Personal Information & Identity</span>
                    </h3>

                    <!-- Profile Photo Picker -->
                    <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 flex flex-col sm:flex-row items-center gap-4">
                        <div class="relative flex-shrink-0 group">
                            <img src="<?= $user['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=160' ?>" 
                                 class="w-20 h-20 rounded-2xl object-cover border-2 border-white shadow-md bg-white transition group-hover:brightness-95" id="avatar-form-img">
                            <label for="avatar_file" class="absolute inset-0 bg-black/40 rounded-2xl flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition cursor-pointer" title="Click to choose photo">
                                <i data-lucide="camera" class="w-5 h-5 mb-0.5"></i>
                                <span class="text-[9px] font-bold">Change</span>
                            </label>
                        </div>
                        <div class="flex-1 text-center sm:text-left space-y-1.5 w-full">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <div>
                                    <div class="font-bold text-slate-800 text-xs flex items-center justify-center sm:justify-start space-x-1.5">
                                        <span>Your Profile Photo</span>
                                        <span class="text-[10px] px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold">JPG, PNG, WEBP</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Choose your own photo from your computer or phone. Maximum 8MB.</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 pt-1">
                                <label for="avatar_file" class="cursor-pointer inline-flex items-center space-x-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold text-xs shadow-sm transition">
                                    <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                    <span>Choose My Photo</span>
                                </label>
                                <input type="file" name="avatar_file" id="avatar_file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="previewAvatar(this)">
                                <span id="file-chosen-name" class="text-[11px] text-slate-500 font-medium italic">No new file selected</span>
                            </div>
                            <!-- Collapsible URL fallback -->
                            <div class="pt-1">
                                <details class="text-[11px] text-slate-500 cursor-pointer">
                                    <summary class="hover:text-indigo-600 font-medium select-none">Or paste an image web link instead</summary>
                                    <div class="mt-1.5 flex items-center gap-2">
                                        <input type="url" name="avatar_url" id="avatar_url_input" value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>" placeholder="https://example.com/photo.jpg"
                                               class="w-full px-3 py-1.5 bg-white border border-slate-200 focus:border-indigo-600 rounded-lg text-xs text-slate-800 outline-none"
                                               oninput="previewUrlAvatar(this.value)">
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Full Legal Name</label>
                                <input type="text" name="name" required value="<?= htmlspecialchars($user['name']) ?>"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Official Designation</label>
                                <input type="text" name="designation" value="<?= htmlspecialchars($founderProfile['designation'] ?? 'Founder & CEO') ?>"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Direct Phone Number</label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Operating City</label>
                                <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? 'Mumbai') ?>"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Tax PAN Number (Confidential)</label>
                                <input type="text" name="pan_number" value="<?= htmlspecialchars($founderProfile['pan_number'] ?? '') ?>" placeholder="ABCDE1234F"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 uppercase font-mono outline-none transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">LinkedIn Profile URL</label>
                                <input type="url" name="linkedin_url" value="<?= htmlspecialchars($founderProfile['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/username"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Personal Portfolio / Website</label>
                                <input type="url" name="website_url" value="<?= htmlspecialchars($founderProfile['website_url'] ?? '') ?>" placeholder="https://founder.io"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Founder Bio & Background Story</label>
                            <textarea name="bio" rows="4" placeholder="Tell your entrepreneurial journey, past venture exits, technological patents, or domains of expertise..."
                                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition"><?= htmlspecialchars($founderProfile['bio'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Linked Startup Affiliation Card -->
            <?php if ($company): ?>
                <div class="card-clean rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="building-2" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Associated Startup Entity</span>
                        </h3>
                        <a href="<?= url('founder/company.php') ?>" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold">
                            Edit Company Profile →
                        </a>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <img src="<?= $company['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100' ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200">
                            <div>
                                <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($company['name']) ?></div>
                                <div class="text-[11px] text-slate-500"><?= htmlspecialchars($company['industry']) ?> • <?= htmlspecialchars($company['stage']) ?></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 font-semibold uppercase block">Equity Holding</span>
                            <span class="font-bold text-indigo-600 text-xs"><?= !empty($company['equity_percent']) ? $company['equity_percent'] . '%' : 'Founder' ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Password Change Card -->
            <div class="card-clean rounded-2xl p-6">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                    <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-600"></i>
                    <span>Security & Account Password</span>
                </h3>
                <form action="<?= url('founder/profile.php') ?>" method="POST" class="space-y-4 text-xs">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="change_password">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Current Password</label>
                            <input type="password" name="current_password" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 outline-none focus:bg-white focus:border-indigo-600">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">New Password</label>
                            <input type="password" name="new_password" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 outline-none focus:bg-white focus:border-indigo-600">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Confirm Password</label>
                            <input type="password" name="confirm_password" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 outline-none focus:bg-white focus:border-indigo-600">
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded-lg text-xs transition">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#profile-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });

        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const label = document.getElementById('file-chosen-name');
                if (label) {
                    label.textContent = 'Selected: ' + file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                    label.className = 'text-[11px] text-emerald-600 font-bold';
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const formImg = document.getElementById('avatar-form-img');
                    if (formImg) formImg.src = e.target.result;
                    const topPreviewImg = document.getElementById('avatar-preview-img');
                    if (topPreviewImg) topPreviewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        function previewUrlAvatar(url) {
            if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
                const formImg = document.getElementById('avatar-form-img');
                if (formImg) formImg.src = url;
                const topPreviewImg = document.getElementById('avatar-preview-img');
                if (topPreviewImg) topPreviewImg.src = url;
            }
        }
    </script>
</body>
</html>
