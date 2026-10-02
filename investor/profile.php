<?php
/**
 * Investor Module: Profile, Thesis & Identity Settings
 * Content-First Editorial Experience, Vay Portal Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Investor Profile & Thesis';

$investorProfile = null;
$preferences = null;
$error = '';
$flash = get_flash();

$allowedTypes = ['Angel Investor', 'Venture Capital Fund', 'Family Office', 'Syndicate Lead'];
$defaultAvatar = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=160';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please refresh and try again.';
    } else {
        $action = $_POST['form_action'] ?? 'update_profile';

        if ($action === 'update_profile') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $avatarUrl = trim($_POST['avatar_url'] ?? '');
            $investorType = trim($_POST['investor_type'] ?? 'Angel Investor');
            if (!in_array($investorType, $allowedTypes, true))
                $investorType = 'Angel Investor';
            $experience = max(1, min(50, (int) ($_POST['experience_years'] ?? 3)));
            $pan = strtoupper(trim($_POST['pan_number'] ?? ''));
            $industries = trim($_POST['preferred_industries'] ?? '');
            $stages = trim($_POST['preferred_stages'] ?? '');
            $minTicket = (float) ($_POST['min_ticket'] ?? 100000);
            $maxTicket = (float) ($_POST['max_ticket'] ?? 5000000);
            $thesis = trim($_POST['investment_thesis'] ?? '');

            if ($name === '') {
                $error = 'Full legal name is required.';
            } elseif ($pan !== '' && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
                $error = 'PAN must be in the format ABCDE1234F.';
            } elseif ($minTicket < 0 || $maxTicket <= 0 || $minTicket > $maxTicket) {
                $error = 'Minimum check size cannot be greater than the maximum check size.';
            } elseif ($avatarUrl !== '' && !preg_match('#^https?://#i', $avatarUrl)) {
                $error = 'Photo link must start with http:// or https://';
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

            if (empty($error)) {
                $db->prepare("UPDATE users SET name = ?, phone = ?, city = ?, avatar_url = ? WHERE id = ?")
                    ->execute([$name, $phone, $city, $avatarUrl, $user['id']]);

                $updIP = $db->prepare("
                    INSERT INTO investor_profiles (user_id, investor_type, experience_years, pan_number, risk_disclosure_accepted)
                    VALUES (?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE investor_type = VALUES(investor_type), experience_years = VALUES(experience_years), pan_number = VALUES(pan_number)
                ");
                $updIP->execute([$user['id'], $investorType, $experience, $pan]);

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

            if (!password_verify($currentPass, $user['password_hash'])) {
                $error = 'Current password entered is incorrect.';
            } elseif (strlen($newPass) < 8) {
                $error = 'New password must be at least 8 characters.';
            } elseif ($newPass !== $confirmPass) {
                $error = 'New password and confirmation do not match.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                log_audit($user['id'], 'PASSWORD_CHANGED', 'users', $user['id'], 'Investor changed password');
                set_flash('success', 'Password updated successfully.');
                header('Location: ' . url('investor/profile.php'));
                exit;
            }
        }
    }
}

// Load profile data AFTER POST handling
if ($db) {
    $ipStmt = $db->prepare("SELECT * FROM investor_profiles WHERE user_id = ?");
    $ipStmt->execute([$user['id']]);
    $investorProfile = $ipStmt->fetch();

    $prefStmt = $db->prepare("SELECT * FROM investor_preferences WHERE user_id = ?");
    $prefStmt->execute([$user['id']]);
    $preferences = $prefStmt->fetch();
}

$avatarSrc = !empty($user['avatar_url']) ? $user['avatar_url'] : $defaultAvatar;
$flashStyles = [
    'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
    'info' => 'bg-blue-50 text-blue-800 border-blue-200',
    'error' => 'bg-rose-50 text-rose-800 border-rose-200',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/investor/head.php'; ?>
    <style>
        @font-face {
            font-family: "Vay Portal", Sans-serif;
            src: local('Vay Portal - Regular'), local('Vay Portal'), local('Plus Jakarta Sans');
        }

        :root {
            --inv-primary: #123B7A;
            --inv-navy: #0B1F3A;
            --inv-secondary: #315F9F;
            --inv-light-blue: #EAF2FF;
            --inv-bg: #F4F2EE;
            --inv-text: #111827;
            --inv-text-sec: #667085;
            --inv-border: #E4E8EF;
        }

        body {
            font-family: 'Vay Portal - Regular', 'Vay Portal', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
            background-color: var(--inv-bg);
            color: var(--inv-text);
        }

        html.dark body {
            background-color: #0B0F19;
            color: #F1F5F9;
        }

        /* Always-white cards with smooth hover effect */
        .inv-card,
        html.dark .inv-card {
            background-color: #FFFFFF !important;
            color: #111827 !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border-color: #E4E8EF;
        }

        /* Card Hover Effect */
        .inv-card:hover {
            background-color: #FFFFFF !important;
            transform: translateY(-3px);
            box-shadow: 0 12px 28px -6px rgba(18, 59, 122, 0.1), 0 6px 14px -4px rgba(0, 0, 0, 0.05) !important;
            border-color: #CBD5E1 !important;
        }

        #profile-main {
            font-size: 1rem;
            line-height: 1.6;
        }

        .inv-label {
            display: block;
            font-weight: 700;
            color: #667085;
            font-size: 0.8125rem;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .inv-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: #FFFFFF;
            border: 1px solid #E4E8EF;
            border-radius: 0.75rem;
            font-size: 1rem;
            color: #111827;
            outline: none;
            transition: all 0.15s ease;
        }

        .inv-input:focus {
            background-color: #FFFFFF;
            border-color: #123B7A;
            box-shadow: 0 0 0 4px rgba(18, 59, 122, 0.08);
        }

        .inv-input[readonly] {
            color: #667085;
            cursor: not-allowed;
            background-color: #F8FAFC;
        }
    </style>
</head>

<body class="bg-[#F4F2EE] text-[#111827] flex min-h-screen antialiased dark:bg-[#0B0F19] dark:text-slate-100">

    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="profile-main">

            <?php if ($flash): ?>
                <div
                    class="p-4 rounded-xl text-sm font-semibold border <?= $flashStyles[$flash['type']] ?? $flashStyles['info'] ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div
                    class="p-4 rounded-xl text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div
                class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-4 border-b border-[#E4E8EF] dark:border-slate-700">
                <div class="space-y-1">
                    <span class="text-sm font-bold text-[#123B7A] dark:text-blue-300 uppercase tracking-wider">Identity
                        & Thesis</span>
                    <h1 class="text-lg sm:text-xl font-bold text-[#0B1F3A] dark:text-white tracking-tight">Investor
                        Profile & Thesis Settings</h1>
                    <p class="text-xs sm:text-sm text-[#667085] dark:text-slate-400">Define your syndicate investment criteria,
                        check allocation boundaries, and verified credentials.</p>
                </div>
                <a href="<?= url('investor/view.php') ?>"
                    class="inline-flex items-center space-x-1.5 px-4 py-2.5 bg-[#EAF2FF] hover:bg-[#123B7A] text-[#123B7A] hover:text-white rounded-xl font-bold text-sm transition duration-200 shadow-sm self-start sm:self-auto">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    <span>Preview Public Profile</span>
                </a>
            </div>

            <!-- Profile Summary Strip Card -->
            <div
                class="inv-card bg-white border border-[#E4E8EF] rounded-2xl p-5 flex flex-col sm:flex-row items-center sm:items-start justify-between gap-4 shadow-sm cursor-pointer">
                <div
                    class="flex flex-col sm:flex-row items-center sm:items-start space-y-3 sm:space-y-0 sm:space-x-4 text-center sm:text-left">
                    <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Profile photo"
                        class="w-20 h-20 rounded-2xl object-cover border border-[#E4E8EF] shadow-sm bg-white"
                        id="avatar-preview-img">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                            <h2 class="text-xl font-bold text-[#0B1F3A]"><?= htmlspecialchars($user['name']) ?></h2>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#EAF2FF] text-[#123B7A]">
                                <?= htmlspecialchars(strtoupper($investorProfile['investor_type'] ?? 'ANGEL INVESTOR')) ?>
                            </span>
                        </div>
                        <div
                            class="text-sm text-emerald-700 font-semibold flex items-center justify-center sm:justify-start space-x-1">
                            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                            <span>SEBI Compliant Accredited Investor</span>
                        </div>
                        <div class="text-sm text-[#667085]">
                            Check Range: <?= format_inr($preferences['min_ticket'] ?? 250000) ?> -
                            <?= format_inr($preferences['max_ticket'] ?? 5000000) ?> •
                            <?= htmlspecialchars($user['city'] ?? 'India') ?>
                        </div>
                    </div>
                </div>
                <a href="<?= url('investor/view.php') ?>"
                    class="text-sm text-[#123B7A] hover:text-[#0B1F3A] font-bold flex items-center space-x-1 self-center sm:self-start">
                    <span>View Public Bio</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>

            <!-- Profile Edit Form -->
            <form action="<?= url('investor/profile.php') ?>" method="POST" enctype="multipart/form-data"
                class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="form_action" value="update_profile">

                <!-- SECTION 1 -->
                <section class="inv-card bg-white border border-[#E4E8EF] rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
                    <div class="flex items-center space-x-2 pb-3 border-b border-[#E4E8EF]">
                        <i data-lucide="user-check" class="w-5 h-5 text-[#123B7A]"></i>
                        <h3 class="text-base font-bold text-[#0B1F3A] uppercase tracking-wider">Investor Identity &
                            Personal Details</h3>
                    </div>

                    <!-- Photo Picker -->
                    <div
                        class="p-4 rounded-xl bg-white border border-[#E4E8EF] flex flex-col sm:flex-row items-center gap-5 transition hover:border-[#123B7A]/30">
                        <div class="relative flex-shrink-0 group">
                            <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Profile photo"
                                class="w-24 h-24 rounded-2xl object-cover border-2 border-white shadow-md bg-white transition group-hover:brightness-95"
                                id="avatar-form-img">
                            <label for="avatar_file"
                                class="absolute inset-0 bg-[#0B1F3A]/60 rounded-2xl flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition cursor-pointer"
                                title="Click to choose photo">
                                <i data-lucide="camera" class="w-5 h-5 mb-0.5"></i>
                                <span class="text-sm font-bold">Change</span>
                            </label>
                        </div>
                        <div class="flex-1 text-center sm:text-left space-y-2 w-full">
                            <div>
                                <div
                                    class="font-bold text-[#0B1F3A] text-base flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                    <span>Your Profile Photo</span>
                                    <span
                                        class="text-xs px-2 py-0.5 bg-[#EAF2FF] text-[#123B7A] rounded-full font-bold">JPG,
                                        PNG, WEBP</span>
                                </div>
                                <p class="text-sm text-[#667085] mt-0.5">Select your photo from your device. Recommended
                                    400x400 square format. Max 8MB.</p>
                            </div>
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 pt-1">
                                <label for="avatar_file"
                                    class="cursor-pointer inline-flex items-center space-x-1.5 px-4 py-2 bg-[#123B7A] hover:bg-[#0B1F3A] text-white rounded-lg font-bold text-sm shadow-sm transition">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                    <span>Choose My Photo</span>
                                </label>
                                <input type="file" name="avatar_file" id="avatar_file"
                                    accept="image/jpeg,image/png,image/webp,image/gif" class="hidden"
                                    onchange="previewAvatar(this)">
                                <span id="file-chosen-name" class="text-sm text-[#667085] font-medium italic">No new
                                    file selected</span>
                            </div>
                            <div class="pt-1">
                                <details class="text-sm text-[#667085] cursor-pointer">
                                    <summary class="hover:text-[#123B7A] font-medium select-none">Or paste an image web
                                        link instead</summary>
                                    <div class="mt-2">
                                        <input type="url" name="avatar_url" id="avatar_url_input"
                                            value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>"
                                            placeholder="https://example.com/photo.jpg" class="inv-input"
                                            oninput="previewUrlAvatar(this.value)">
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>

                    <!-- Input Grid -->
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="inv-label">Full Legal Name</label>
                                <input type="text" name="name" required value="<?= htmlspecialchars($user['name']) ?>"
                                    class="inv-input">
                            </div>
                            <div>
                                <label class="inv-label">Investor Classification</label>
                                <select name="investor_type" class="inv-input cursor-pointer">
                                    <?php foreach (['Angel Investor' => 'Angel Investor', 'Venture Capital Fund' => 'Venture Capital Fund', 'Family Office' => 'Family Office', 'Syndicate Lead' => 'Angel Syndicate Lead'] as $val => $label): ?>
                                        <option value="<?= htmlspecialchars($val) ?>" <?= ($investorProfile['investor_type'] ?? '') === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="inv-label">Years in Venture</label>
                                <input type="number" name="experience_years"
                                    value="<?= htmlspecialchars($investorProfile['experience_years'] ?? '5') ?>" min="1"
                                    max="50" class="inv-input">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="inv-label">Email (Locked)</label>
                                <input type="email" readonly value="<?= htmlspecialchars($user['email']) ?>"
                                    class="inv-input">
                            </div>
                            <div>
                                <label class="inv-label">Phone Number</label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                    class="inv-input">
                            </div>
                            <div>
                                <label class="inv-label">Operating City</label>
                                <input type="text" name="city"
                                    value="<?= htmlspecialchars($user['city'] ?? 'Mumbai') ?>" class="inv-input">
                            </div>
                            <div>
                                <label class="inv-label">Income Tax PAN</label>
                                <input type="text" name="pan_number" maxlength="10" placeholder="ABCDE1234F"
                                    value="<?= htmlspecialchars($investorProfile['pan_number'] ?? '') ?>"
                                    class="inv-input uppercase font-mono">
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 2 -->
                <section class="inv-card bg-white border border-[#E4E8EF] rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
                    <div class="flex items-center space-x-2 pb-3 border-b border-[#E4E8EF]">
                        <i data-lucide="target" class="w-5 h-5 text-[#123B7A]"></i>
                        <h3 class="text-base font-bold text-[#0B1F3A] uppercase tracking-wider">Investment Thesis &
                            Criteria</h3>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="inv-label">Preferred Sectors (Comma Separated)</label>
                            <input type="text" name="preferred_industries"
                                value="<?= htmlspecialchars($preferences['preferred_industries'] ?? 'AI/SaaS, FinTech, HealthTech, CleanTech') ?>"
                                class="inv-input">
                        </div>
                        <div>
                            <label class="inv-label">Preferred Stages (Comma Separated)</label>
                            <input type="text" name="preferred_stages"
                                value="<?= htmlspecialchars($preferences['preferred_stages'] ?? 'Seed, Pre-Series A, Series A') ?>"
                                class="inv-input">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="inv-label">Minimum Check Size (₹)</label>
                                <input type="number" name="min_ticket" min="0"
                                    value="<?= htmlspecialchars($preferences['min_ticket'] ?? '250000') ?>" step="50000"
                                    class="inv-input">
                            </div>
                            <div>
                                <label class="inv-label">Maximum Check Size (₹)</label>
                                <input type="number" name="max_ticket" min="0"
                                    value="<?= htmlspecialchars($preferences['max_ticket'] ?? '5000000') ?>"
                                    step="100000" class="inv-input">
                            </div>
                        </div>

                        <div>
                            <label class="inv-label">Investment Thesis Statement</label>
                            <textarea name="investment_thesis" rows="5"
                                placeholder="Describe your evaluation criteria, moat requirements, founder-market fit beliefs, and value-add network..."
                                class="inv-input leading-relaxed"><?= htmlspecialchars($preferences['investment_thesis'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[#E4E8EF] flex justify-end">
                        <button type="submit"
                            class="px-6 py-3 bg-[#123B7A] hover:bg-[#0B1F3A] text-white font-bold text-base rounded-xl shadow-sm transition">
                            Save Investor Settings
                        </button>
                    </div>
                </section>
            </form>

            <!-- SECTION 3: SECURITY -->
            <section class="inv-card bg-white border border-[#E4E8EF] rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm">
                <div class="flex items-center space-x-2 pb-3 border-b border-[#E4E8EF]">
                    <i data-lucide="lock" class="w-5 h-5 text-[#123B7A]"></i>
                    <h3 class="text-base font-bold text-[#0B1F3A] uppercase tracking-wider">Security & Account Password
                    </h3>
                </div>

                <form action="<?= url('investor/profile.php') ?>" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="change_password">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="inv-label">Current Password</label>
                            <input type="password" name="current_password" required autocomplete="current-password"
                                class="inv-input">
                        </div>
                        <div>
                            <label class="inv-label">New Password</label>
                            <input type="password" name="new_password" required minlength="8"
                                autocomplete="new-password" class="inv-input">
                        </div>
                        <div>
                            <label class="inv-label">Confirm Password</label>
                            <input type="password" name="confirm_password" required minlength="8"
                                autocomplete="new-password" class="inv-input">
                        </div>
                    </div>
                    <p class="text-sm text-[#667085]">Use at least 8 characters.</p>

                    <div class="pt-3 border-t border-[#E4E8EF] flex justify-end">
                        <button type="submit"
                            class="px-5 py-2.5 bg-[#0B1F3A] hover:bg-[#123B7A] text-white font-bold rounded-xl text-base transition">
                            Update Password
                        </button>
                    </div>
                </form>
            </section>

        </main>
    </div>

    <script>
        if (window.lucide && typeof lucide.createIcons === 'function') lucide.createIcons();
        if (window.gsap) {
            gsap.fromTo("#profile-main > *", { y: 15, opacity: 0 }, { duration: 0.45, y: 0, opacity: 1, stagger: 0.08, ease: "power2.out", clearProps: "all" });
        }

        function setAvatarPreview(src) {
            ['avatar-form-img', 'avatar-preview-img'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.src = src;
            });
        }

        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const label = document.getElementById('file-chosen-name');
                if (label) {
                    label.textContent = 'Selected: ' + file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                    label.className = 'text-sm text-emerald-700 font-bold';
                }
                const reader = new FileReader();
                reader.onload = function (e) { setAvatarPreview(e.target.result); };
                reader.readAsDataURL(file);
            }
        }

        function previewUrlAvatar(url) {
            if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
                setAvatarPreview(url);
            }
        }
    </script>
</body>

</html>