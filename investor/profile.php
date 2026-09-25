<?php
/**
 * Investor Module: Profile, Thesis & KYC Status
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Investor Profile & Thesis';

$investorProfile = null;
$preferences = null;
$error = '';
$flash = get_flash();

if ($db) {
    $ipStmt = $db->prepare("SELECT * FROM investor_profiles WHERE user_id = ?");
    $ipStmt->execute([$user['id']]);
    $investorProfile = $ipStmt->fetch();

    $prefStmt = $db->prepare("SELECT * FROM investor_preferences WHERE user_id = ?");
    $prefStmt->execute([$user['id']]);
    $preferences = $prefStmt->fetch();
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
            $investorType = trim($_POST['investor_type'] ?? 'Angel Investor');
            $experience = (int)($_POST['experience_years'] ?? 3);
            $pan = strtoupper(trim($_POST['pan_number'] ?? ''));
            $industries = trim($_POST['preferred_industries'] ?? '');
            $stages = trim($_POST['preferred_stages'] ?? '');
            $minTicket = (float)($_POST['min_ticket'] ?? 100000);
            $maxTicket = (float)($_POST['max_ticket'] ?? 5000000);
            $thesis = trim($_POST['investment_thesis'] ?? '');

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
                // Update user
                $db->prepare("UPDATE users SET name = ?, phone = ?, city = ?, avatar_url = ? WHERE id = ?")
                   ->execute([$name, $phone, $city, $avatarUrl, $user['id']]);

            // Update profile
            $updIP = $db->prepare("
                INSERT INTO investor_profiles (user_id, investor_type, experience_years, pan_number, risk_disclosure_accepted)
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE investor_type = VALUES(investor_type), experience_years = VALUES(experience_years), pan_number = VALUES(pan_number)
            ");
            $updIP->execute([$user['id'], $investorType, $experience, $pan]);

            // Update preferences
            $updPref = $db->prepare("
                INSERT INTO investor_preferences (user_id, preferred_industries, preferred_stages, min_ticket, max_ticket, investment_thesis)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE preferred_industries = VALUES(preferred_industries), preferred_stages = VALUES(preferred_stages), min_ticket = VALUES(min_ticket), max_ticket = VALUES(max_ticket), investment_thesis = VALUES(investment_thesis)
            ");
            $updPref->execute([$user['id'], $industries, $stages, $minTicket, $maxTicket, $thesis]);

            log_audit($user['id'], 'UPDATE_INVESTOR_PROFILE', 'investor_profiles', $user['id'], 'Investor updated thesis and preferences');
            set_flash('success', 'Investor profile and thesis successfully saved.');
            header('Location: ' . url('investor/profile.php'));
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
                    log_audit($user['id'], 'PASSWORD_CHANGED', 'users', $user['id'], 'Investor changed password');
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
    <title>Investor Profile & Thesis • <?= APP_NAME ?></title>
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
    
    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

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
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Investor Thesis & Identity Settings</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Configure your ticket allocation limits, sectors, and accredited credentials.</p>
                </div>
                <a href="<?= url('investor/view.php') ?>" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    <span>Preview Public Profile</span>
                </a>
            </div>

            <!-- Profile Summary Preview Card -->
            <div class="card-clean rounded-2xl p-5">
                <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-3 sm:space-y-0 sm:space-x-4">
                    <img src="<?= $user['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=160' ?>" 
                         class="w-16 h-16 rounded-2xl object-cover border-2 border-slate-200 shadow-sm" id="avatar-preview-img">
                    <div class="flex-1 text-center sm:text-left">
                        <div class="flex flex-col sm:flex-row sm:items-center space-y-1 sm:space-y-0 sm:space-x-2">
                            <h2 class="text-base font-bold text-slate-900"><?= htmlspecialchars($user['name']) ?></h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                <?= strtoupper($investorProfile['investor_type'] ?? 'ANGEL INVESTOR') ?>
                            </span>
                        </div>
                        <div class="text-xs text-emerald-600 font-semibold mt-0.5 flex items-center justify-center sm:justify-start space-x-1">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            <span>SEBI Accredited Investor</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">
                            Check: <?= format_inr($preferences['min_ticket'] ?? 250000) ?> - <?= format_inr($preferences['max_ticket'] ?? 5000000) ?> • <?= htmlspecialchars($user['city'] ?? 'India') ?>
                        </div>
                    </div>
                    <div>
                        <a href="<?= url('investor/view.php') ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1 transition">
                            <i data-lucide="external-link" class="w-3 h-3 text-slate-400"></i>
                            <span>View Full Profile</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Profile Info Edit Form -->
            <div class="card-clean rounded-2xl p-6">
                <form action="<?= url('investor/profile.php') ?>" method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="update_profile">

                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 flex items-center space-x-1.5">
                        <i data-lucide="user-check" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Investor Identity & Personal Details</span>
                    </h3>

                    <!-- Profile Photo Picker -->
                    <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 flex flex-col sm:flex-row items-center gap-4">
                        <div class="relative flex-shrink-0 group">
                            <img src="<?= $user['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=160' ?>" 
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
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Full Legal Name</label>
                                <input type="text" name="name" required value="<?= htmlspecialchars($user['name']) ?>"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Investor Classification</label>
                                <select name="investor_type" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                                    <option value="Angel Investor" <?= ($investorProfile['investor_type'] ?? '') === 'Angel Investor' ? 'selected' : '' ?>>Angel Investor</option>
                                    <option value="Venture Capital Fund" <?= ($investorProfile['investor_type'] ?? '') === 'Venture Capital Fund' ? 'selected' : '' ?>>Venture Capital Fund</option>
                                    <option value="Family Office" <?= ($investorProfile['investor_type'] ?? '') === 'Family Office' ? 'selected' : '' ?>>Family Office</option>
                                    <option value="Syndicate Lead" <?= ($investorProfile['investor_type'] ?? '') === 'Syndicate Lead' ? 'selected' : '' ?>>Angel Syndicate Lead</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Years in Venture Investing</label>
                                <input type="number" name="experience_years" value="<?= htmlspecialchars($investorProfile['experience_years'] ?? '5') ?>" min="1" max="50"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Email (Read Only)</label>
                                <input type="email" readonly value="<?= htmlspecialchars($user['email']) ?>"
                                       class="w-full px-3.5 py-2 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-500 outline-none cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Phone Number</label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Operating City</label>
                                <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? 'Mumbai') ?>"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Tax PAN Number</label>
                                <input type="text" name="pan_number" value="<?= htmlspecialchars($investorProfile['pan_number'] ?? '') ?>"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 uppercase font-mono outline-none transition">
                            </div>
                        </div>

                        <h2 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center space-x-1.5 pt-3 border-b border-slate-100 pb-2">
                            <i data-lucide="target" class="w-3.5 h-3.5"></i>
                            <span>Investment Thesis & Deployment Limits</span>
                        </h2>

                        <div>
                            <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Preferred Sectors (Comma Separated)</label>
                            <input type="text" name="preferred_industries" value="<?= htmlspecialchars($preferences['preferred_industries'] ?? 'AI/SaaS, FinTech, HealthTech, CleanTech') ?>"
                                   class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Preferred Stages (Comma Separated)</label>
                            <input type="text" name="preferred_stages" value="<?= htmlspecialchars($preferences['preferred_stages'] ?? 'Seed, Pre-Series A, Series A') ?>"
                                   class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Minimum Check Size (₹)</label>
                                <input type="number" name="min_ticket" value="<?= htmlspecialchars($preferences['min_ticket'] ?? '250000') ?>" step="50000"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Maximum Check Size (₹)</label>
                                <input type="number" name="max_ticket" value="<?= htmlspecialchars($preferences['max_ticket'] ?? '5000000') ?>" step="100000"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Investment Thesis Statement</label>
                            <textarea name="investment_thesis" rows="3" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition"><?= htmlspecialchars($preferences['investment_thesis'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                            Save Investor Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Change Card -->
            <div class="card-clean rounded-2xl p-6">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                    <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-600"></i>
                    <span>Security & Account Password</span>
                </h3>
                <form action="<?= url('investor/profile.php') ?>" method="POST" class="space-y-4 text-xs">
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
