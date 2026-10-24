<?php
/**
 * Founder Module: Profile Overview & Persona Hub
 * Full-width Tech Mesh Wireframe Cover Banner with Non-Clipped Overlapping Circular Avatar
 * Direct Instant Banner & Avatar Upload Support
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Founder Profile Overview';

$founderProfile = null;
$company = null;
$flash = get_flash();

if ($db) {
    // Handle Direct One-Click Banner and Avatar Uploads
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'Security token invalid. Please refresh the page.');
        } else {
            $action = $_POST['form_action'] ?? '';

            if ($action === 'update_banner') {
                if (!empty($_FILES['cover_file']['name'])) {
                    $coverRes = handle_cover_upload($_FILES['cover_file'], $user['id']);
                    if (!empty($coverRes['success'])) {
                        $upd = $db->prepare("
                            INSERT INTO founder_profiles (user_id, cover_url)
                            VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE cover_url = VALUES(cover_url)
                        ");
                        $upd->execute([$user['id'], $coverRes['url']]);
                        log_audit($user['id'], 'UPDATE_BANNER', 'founder_profiles', $user['id'], 'Founder updated cover banner');
                        set_flash('success', 'Profile cover banner updated successfully!');
                    } else {
                        set_flash('error', $coverRes['error'] ?? 'Failed to upload cover banner.');
                    }
                }
                header('Location: ' . url('founder/profile.php'));
                exit;

            } elseif ($action === 'update_avatar') {
                if (!empty($_FILES['avatar_file']['name'])) {
                    $avatarRes = handle_avatar_upload($_FILES['avatar_file'], $user['id']);
                    if (!empty($avatarRes['success'])) {
                        $db->prepare("UPDATE users SET avatar_url = ? WHERE id = ?")->execute([$avatarRes['url'], $user['id']]);
                        log_audit($user['id'], 'UPDATE_AVATAR', 'users', $user['id'], 'Founder updated avatar photo');
                        set_flash('success', 'Profile photo updated successfully!');
                    } else {
                        set_flash('error', $avatarRes['error'] ?? 'Failed to upload avatar photo.');
                    }
                }
                header('Location: ' . url('founder/profile.php'));
                exit;
            }
        }
    }

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
        .profile-cover-mesh {
            background-color: #060919;
            background-image: 
                radial-gradient(circle, rgba(255, 255, 255, 0.22) 1.2px, transparent 1.2px),
                linear-gradient(110deg, #050814 0%, #0d1b54 45%, #081133 75%, #020409 100%);
            background-size: 22px 22px, 100% 100%;
        }

        .section-card {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1.5rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }
        .dark .section-card, html.dark .section-card {
            background-color: #111827 !important;
            border-color: #1E293B !important;
            color: #F8FAFC !important;
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

            <!-- Master Profile Header Card -->
            <div class="section-card rounded-3xl overflow-hidden bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm relative">
                
                <!-- 1. Cover Banner Area (Top rounded, overflow-hidden for banner graphic) -->
                <div class="relative h-48 sm:h-60 md:h-68 w-full overflow-hidden bg-[#060919] group">
                    
                    <?php if (!empty($founderProfile['cover_url'])): ?>
                        <!-- User's Uploaded Custom Banner: Shows full graphic cleanly without SVG obstruction -->
                        <img src="<?= htmlspecialchars($founderProfile['cover_url']) ?>" 
                             alt="Profile Cover Banner" 
                             class="w-full h-full object-cover object-center transition duration-500">
                    <?php else: ?>
                        <!-- Default 3D Geometric Mesh Wireframe (when no banner is uploaded) -->
                        <div class="w-full h-full profile-cover-mesh relative flex items-center justify-between">
                            <!-- SVG 3D Wireframe Geometric Polyhedron Mesh - Left -->
                            <svg class="absolute -left-10 sm:-left-4 -bottom-10 w-52 sm:w-68 md:w-80 h-52 sm:h-68 md:h-80 pointer-events-none opacity-45 sm:opacity-60" viewBox="0 0 300 300" fill="none" stroke="rgba(147, 197, 253, 0.75)" stroke-width="1.3">
                                <polygon points="50,220 110,140 180,180" />
                                <polygon points="110,140 160,70 180,180" />
                                <polygon points="160,70 240,110 180,180" />
                                <polygon points="180,180 260,220 220,280" />
                                <polygon points="50,220 180,180 120,290" />
                                <polygon points="120,290 180,180 220,280" />
                                <polygon points="160,70 190,20 240,110" />
                                <polygon points="110,140 190,20 160,70" />
                                <polygon points="40,130 110,140 50,220" />
                                <circle cx="110" cy="140" r="2.5" fill="#60A5FA" />
                                <circle cx="160" cy="70" r="2.5" fill="#60A5FA" />
                                <circle cx="180" cy="180" r="2.5" fill="#60A5FA" />
                                <circle cx="240" cy="110" r="2.5" fill="#60A5FA" />
                                <circle cx="50" cy="220" r="2.5" fill="#60A5FA" />
                                <circle cx="120" cy="290" r="2.5" fill="#60A5FA" />
                                <circle cx="220" cy="280" r="2.5" fill="#60A5FA" />
                            </svg>

                            <!-- SVG 3D Wireframe Geodesic Sphere / Icosahedron - Right -->
                            <svg class="absolute -right-8 sm:-right-4 -top-8 w-48 sm:w-64 md:w-76 h-48 sm:h-64 md:h-76 pointer-events-none opacity-45 sm:opacity-60" viewBox="0 0 260 260" fill="none" stroke="rgba(255, 255, 255, 0.75)" stroke-width="1.2">
                                <polygon points="130,30 195,65 210,135" />
                                <polygon points="130,30 210,135 155,175" />
                                <polygon points="130,30 155,175 105,175" />
                                <polygon points="130,30 105,175 50,135" />
                                <polygon points="130,30 50,135 65,65" />
                                <polygon points="130,30 65,65 195,65" />
                                <polygon points="195,65 230,120 210,135" />
                                <circle cx="130" cy="30" r="2" fill="#FFFFFF" />
                                <circle cx="195" cy="65" r="2" fill="#FFFFFF" />
                                <circle cx="210" cy="135" r="2" fill="#FFFFFF" />
                                <circle cx="155" cy="175" r="2" fill="#FFFFFF" />
                                <circle cx="105" cy="175" r="2" fill="#FFFFFF" />
                            </svg>
                        </div>
                    <?php endif; ?>

                    <!-- Direct Banner Upload Action Button (Top Right) -->
                    <form id="banner-quick-form" action="<?= url('founder/profile.php') ?>" method="POST" enctype="multipart/form-data" class="absolute top-4 right-4 z-20">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="form_action" value="update_banner">
                        <input type="file" name="cover_file" id="quick_cover_file" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="uploadBannerDirectly(this)">
                        
                        <label for="quick_cover_file" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-black/60 hover:bg-black/80 backdrop-blur-md text-white text-xs font-bold border border-white/20 transition shadow-lg hover:scale-105 active:scale-95" title="Click to upload/change cover banner">
                            <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                            <span class="hidden sm:inline">Change Banner</span>
                        </label>
                    </form>
                </div>

                <!-- 2. Profile Identity & Overlapping Circular Avatar -->
                <div class="px-6 sm:px-8 pb-7 pt-0 relative">
                    
                    <!-- Row 1: Circular Avatar (Left) & Action Buttons (Right) -->
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 -mt-16 sm:-mt-20 md:-mt-24 mb-4">
                        
                        <!-- Circular Avatar with Camera Upload Icon -->
                        <div class="relative flex-shrink-0 self-start group">
                            <form id="avatar-quick-form" action="<?= url('founder/profile.php') ?>" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="form_action" value="update_avatar">
                                <input type="file" name="avatar_file" id="quick_avatar_file" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="uploadAvatarDirectly(this)">
                            </form>

                            <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" 
                                     alt="<?= htmlspecialchars($user['name']) ?>"
                                     class="w-32 h-32 sm:w-36 sm:h-36 md:w-40 md:h-40 rounded-full object-cover border-4 border-white dark:border-slate-900 shadow-xl bg-white dark:bg-slate-900" id="profile-avatar-display">
                            <?php else: ?>
                                <div class="w-32 h-32 sm:w-36 sm:h-36 md:w-40 md:h-40 rounded-full bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 text-white flex items-center justify-center font-black text-3xl sm:text-4xl md:text-5xl border-4 border-white dark:border-slate-900 shadow-xl" id="profile-avatar-placeholder">
                                    <?= htmlspecialchars($userInitials) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Quick Avatar Photo Change Badge -->
                            <label for="quick_avatar_file" class="absolute bottom-1 right-1 w-9 h-9 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white border-2 border-white dark:border-slate-900 shadow-md flex items-center justify-center cursor-pointer transition hover:scale-110 active:scale-95" title="Click to upload headshot">
                                <i data-lucide="camera" class="w-4 h-4"></i>
                            </label>
                        </div>

                        <!-- Action Buttons on Top Right (Balanced opposite Avatar) -->
                        <div class="flex flex-wrap items-center gap-2.5 pt-2 sm:pt-0 self-start sm:self-end">
                            <a href="<?= url('founder/edit_profile.php') ?>" class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white text-xs font-bold rounded-xl shadow-sm shadow-indigo-500/25 transition flex items-center space-x-1.5 hover:scale-105 active:scale-95">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                <span>Edit Profile</span>
                            </a>

                            <?php if ($company): ?>
                                <button type="button" onclick="openShareModal('<?= htmlspecialchars($publicCompanyUrl) ?>', '<?= htmlspecialchars(addslashes($company['name'])) ?>')"
                                    class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm shadow-blue-500/25 transition flex items-center space-x-1.5 hover:scale-105 active:scale-95">
                                    <i data-lucide="share-2" class="w-4 h-4"></i>
                                    <span>Share Company Link</span>
                                </button>
                            <?php endif; ?>
                            
                            <a href="<?= url('founder/view.php') ?>" class="px-4 py-2.5 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl shadow-xs transition flex items-center space-x-1.5 hover:scale-105 active:scale-95">
                                <i data-lucide="external-link" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                <span>View Public Profile</span>
                            </a>
                        </div>
                    </div>

                    <!-- Row 2: Full-Width Identity Block with Refined Typography & Clean Alignment -->
                    <div class="space-y-2 pt-1">
                        
                        <!-- Name & Verification Badge -->
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                <?= htmlspecialchars($user['name']) ?>
                            </h1>
                            <?php if ($user['is_verified']): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 inline-flex items-center gap-1.5 shadow-xs">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                    <span>KYC VERIFIED FOUNDER</span>
                                </span>
                            <?php else: ?>
                                <a href="<?= url('founder/verification.php') ?>" class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 inline-flex items-center gap-1.5 transition shadow-xs" title="Complete KYC verification">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i>
                                    <span>KYC PENDING &rarr;</span>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Designation & Company Affiliation -->
                        <div class="text-base font-semibold text-slate-700 dark:text-slate-300 flex flex-wrap items-center gap-2">
                            <span><?= htmlspecialchars($founderProfile['designation'] ?? 'Founder & CEO') ?></span>
                            <?php if ($company): ?>
                                <span class="text-slate-400 dark:text-slate-600 font-normal">at</span>
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold"><?= htmlspecialchars($company['name']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Contact Metadata Row -->
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs sm:text-sm text-slate-600 dark:text-slate-400 pt-0.5">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="mail" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                                <span><?= htmlspecialchars($user['email']) ?></span>
                            </span>
                            <span class="hidden sm:inline text-slate-300 dark:text-slate-700">•</span>
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="map-pin" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                                <span><?= htmlspecialchars($user['city'] ?? 'India') ?></span>
                            </span>
                            <?php if (!empty($user['phone'])): ?>
                                <span class="hidden sm:inline text-slate-300 dark:text-slate-700">•</span>
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="phone" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                                    <span><?= htmlspecialchars($user['phone']) ?></span>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Social & Website Pills (Seamlessly nested, no disjointed dividers!) -->
                        <?php if (!empty($founderProfile['linkedin_url']) || !empty($founderProfile['website_url'])): ?>
                            <div class="flex flex-wrap items-center gap-2.5 pt-2">
                                <?php if (!empty($founderProfile['linkedin_url'])): ?>
                                    <a href="<?= htmlspecialchars($founderProfile['linkedin_url']) ?>" target="_blank" rel="noopener noreferrer" class="px-3.5 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 text-xs font-bold border border-blue-200 dark:border-blue-800 transition inline-flex items-center gap-1.5 shadow-xs hover:scale-105 active:scale-95">
                                        <i data-lucide="linkedin" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400"></i>
                                        <span>LinkedIn Profile</span>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($founderProfile['website_url'])): ?>
                                    <a href="<?= htmlspecialchars($founderProfile['website_url']) ?>" target="_blank" rel="noopener noreferrer" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5 shadow-xs hover:scale-105 active:scale-95">
                                        <i data-lucide="globe" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                                        <span>Personal Website</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- Founder Persona, Passions & Domain DNA Card -->
            <div class="section-card p-6 sm:p-7 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="sparkles" class="w-4 h-4 text-amber-500"></i>
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Persona &amp; Passions</h2>
                    </div>
                </div>

                <!-- Bio Text -->
                <?php if (!empty($founderProfile['bio'])): ?>
                    <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-normal">
                        <?= nl2br(htmlspecialchars($founderProfile['bio'])) ?>
                    </p>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                    <!-- Superpower -->
                    <?php if (!empty($founderProfile['superpower'])): ?>
                        <div class="p-3.5 rounded-xl bg-gradient-to-r from-violet-50 to-indigo-50 dark:from-indigo-950/40 dark:to-violet-950/30 border border-indigo-100 dark:border-indigo-900/50 flex items-start gap-2.5">
                            <div class="p-1.5 rounded-lg bg-indigo-600 text-white flex-shrink-0 mt-0.5">
                                <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-extrabold tracking-wider text-indigo-700 dark:text-indigo-300 block">Founder Superpower</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white leading-snug"><?= htmlspecialchars($founderProfile['superpower']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Education -->
                    <?php if (!empty($founderProfile['education'])): ?>
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800 flex items-center gap-2.5">
                            <i data-lucide="graduation-cap" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                            <div class="min-w-0 text-xs">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Education &amp; Background</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200 truncate block"><?= htmlspecialchars($founderProfile['education']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tags Grid: Skills, Passions, Hobbies -->
                <div class="space-y-3.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <?php if (!empty($founderProfile['skills'])): 
                        $sList = array_map('trim', explode(',', $founderProfile['skills']));
                    ?>
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1.5 flex items-center gap-1.5">
                                <i data-lucide="briefcase" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                                <span>Domain Expertise &amp; Skills</span>
                            </span>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($sList as $sk): if ($sk === '') continue; ?>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800 inline-flex items-center gap-1">
                                        <?= htmlspecialchars($sk) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($founderProfile['passions'])): 
                        $pList = array_map('trim', explode(',', $founderProfile['passions']));
                    ?>
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1.5 flex items-center gap-1.5">
                                <i data-lucide="flame" class="w-3.5 h-3.5 text-amber-500"></i>
                                <span>Passions &amp; Focus Areas</span>
                            </span>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($pList as $ps): if ($ps === '') continue; ?>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 inline-flex items-center gap-1">
                                        <i data-lucide="sparkles" class="w-3 h-3 text-amber-500"></i>
                                        <?= htmlspecialchars($ps) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($founderProfile['hobbies'])): 
                        $hList = array_map('trim', explode(',', $founderProfile['hobbies']));
                    ?>
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1.5 flex items-center gap-1.5">
                                <i data-lucide="compass" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                <span>Hobbies &amp; Personal Interests</span>
                            </span>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($hList as $hb): if ($hb === '') continue; ?>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 inline-flex items-center gap-1">
                                        <?= htmlspecialchars($hb) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Associated Startup Entity Card -->
            <?php if ($company): 
                $compInitials = 'SU';
                if (!empty($company['name'])) {
                    $cParts = preg_split('/\s+/', trim($company['name']));
                    $compInitials = strtoupper(substr($cParts[0], 0, 1) . (isset($cParts[1]) ? substr($cParts[1], 0, 1) : ''));
                }
            ?>
                <div class="section-card p-6 sm:p-7 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                            <span>Corporate Entity Affiliation</span>
                        </h2>
                        <a href="<?= url('founder/company.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-bold flex items-center gap-1">
                            <span>Manage Startup Profile</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50/80 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center space-x-3.5 min-w-0">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-base shadow-xs flex-shrink-0">
                                <?= htmlspecialchars($compInitials) ?>
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 dark:text-white text-sm truncate"><?= htmlspecialchars($company['name']) ?></div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    <?= htmlspecialchars($company['industry'] ?? 'Technology') ?> &bull; <?= htmlspecialchars($company['stage'] ?? 'Seed') ?> Stage
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 sm:text-right self-end sm:self-auto">
                            <div>
                                <span class="text-[10px] text-slate-400 font-bold uppercase block tracking-wider">Equity Allocation</span>
                                <span class="font-extrabold text-indigo-600 dark:text-indigo-400 text-sm">
                                    <?= !empty($company['equity_percent']) ? $company['equity_percent'] . '%' : 'Founder Stake' ?>
                                </span>
                            </div>
                            <button type="button" onclick="openShareModal('<?= htmlspecialchars($publicCompanyUrl) ?>', '<?= htmlspecialchars(addslashes($company['name'])) ?>')" class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-blue-600 transition shadow-2xs" title="Share public company link">
                                <i data-lucide="share-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="section-card p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center">
                            <i data-lucide="building" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white text-sm">No Startup Entity Attached</div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Initialize your startup record to start adding documents and launching funding rounds.</p>
                        </div>
                    </div>
                    <a href="<?= url('founder/company.php') ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Register Startup</span>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Security & Account Protection Card -->
            <div class="section-card p-6 sm:p-7 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-600 dark:text-slate-400"></i>
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Security &amp; Account Protection</h2>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">Password &amp; Authentication Active</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Keep your account secure with an encrypted master passphrase.</div>
                        </div>
                    </div>
                    <a href="<?= url('founder/edit_profile.php') ?>" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs transition shadow-xs flex items-center gap-1.5 self-start sm:self-auto">
                        <i data-lucide="key" class="w-3.5 h-3.5"></i>
                        <span>Update Password</span>
                    </a>
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

        function uploadBannerDirectly(input) {
            if (input.files && input.files[0]) {
                document.getElementById('banner-quick-form').submit();
            }
        }

        function uploadAvatarDirectly(input) {
            if (input.files && input.files[0]) {
                document.getElementById('avatar-quick-form').submit();
            }
        }
    </script>
</body>
</html>
