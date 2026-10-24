<?php
/**
 * Founder Module: Full Public / Detail Founder Profile
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/../config.php';
$currentUser = require_auth(); // Accessible by founder, investor, admin
$db = get_db();

// 1. Resolve Target Founder ID
$targetUserId = 0;
if (isset($_GET['id'])) {
    $targetUserId = decode_id($_GET['id']);
}

// Fallback to current user if they are a founder and no ID passed
if ($targetUserId <= 0 && $currentUser['role'] === 'founder') {
    $targetUserId = $currentUser['id'];
}

if ($targetUserId <= 0) {
    set_flash('error', 'Founder profile not specified or invalid.');
    header('Location: ' . ($currentUser['role'] === 'founder' ? url('founder/dashboard.php') : url('investor/discover.php')));
    exit;
}

// 2. Fetch User & Founder Profile
$founder = null;
$profile = null;
$companies = [];
$fundingRounds = [];

if ($db) {
    $uStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $uStmt->execute([$targetUserId]);
    $founder = $uStmt->fetch();

    if (!$founder) {
        set_flash('error', 'Founder account not found.');
        header('Location: ' . url('index.php'));
        exit;
    }

    $fpStmt = $db->prepare("SELECT * FROM founder_profiles WHERE user_id = ?");
    $fpStmt->execute([$targetUserId]);
    $profile = $fpStmt->fetch();

    // 3. Fetch Associated Companies
    $cStmt = $db->prepare("
        SELECT c.*, cf.equity_percent, cf.is_signatory
        FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $cStmt->execute([$targetUserId]);
    $companies = $cStmt->fetchAll();

    // 4. Fetch Funding Rounds and Company Awards for these companies
    $fundingRounds = [];
    $companyAwards = [];
    if (!empty($companies)) {
        $companyIds = array_column($companies, 'id');
        $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
        $frStmt = $db->prepare("
            SELECT fr.*, c.name as company_name, c.logo_url as company_logo
            FROM funding_rounds fr
            JOIN companies c ON fr.company_id = c.id
            WHERE fr.company_id IN ($placeholders)
            ORDER BY fr.created_at DESC
        ");
        $frStmt->execute($companyIds);
        $fundingRounds = $frStmt->fetchAll();

        // 5. Fetch Company Awards & Trust Badges
        $aStmt = $db->prepare("
            SELECT a.*, c.name as company_name 
            FROM company_awards a
            JOIN companies c ON a.company_id = c.id
            WHERE a.company_id IN ($placeholders)
            ORDER BY a.is_featured DESC, a.created_at DESC
        ");
        $aStmt->execute($companyIds);
        $companyAwards = $aStmt->fetchAll();
    }
}

$isSelf = ($currentUser['id'] == $targetUserId);
$pageTitle = htmlspecialchars($founder['name']) . ' • Founder Profile';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">        <title><?= $pageTitle ?? APP_NAME ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
    <style>
        .profile-cover-mesh {
            background-color: #050816;
            background-image: 
                radial-gradient(circle at 15% 25%, rgba(99, 102, 241, 0.35) 0%, transparent 45%),
                radial-gradient(circle at 85% 75%, rgba(168, 85, 247, 0.28) 0%, transparent 45%),
                radial-gradient(circle, rgba(255, 255, 255, 0.22) 1.2px, transparent 1.2px),
                linear-gradient(115deg, #050814 0%, #0c1a4e 45%, #081133 75%, #020409 100%);
            background-size: 100% 100%, 100% 100%, 22px 22px, 100% 100%;
        }
        .metric-card-elevated {
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .metric-card-elevated:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.08);
        }
        .award-zoom-overlay {
            background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.2) 60%, transparent 100%);
        }
    </style>
</head>
<body class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">
    
    <!-- Sidebar Navigation based on current user role -->
    <?php 
    if ($currentUser['role'] === 'founder') {
        include __DIR__ . '/../includes/founder/sidebar.php';
    } elseif ($currentUser['role'] === 'investor') {
        include __DIR__ . '/../includes/investor/sidebar.php';
    } else {
        include __DIR__ . '/../includes/admin/sidebar.php';
    }
    ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php 
        if ($currentUser['role'] === 'founder') {
            include __DIR__ . '/../includes/founder/navbar.php';
        } elseif ($currentUser['role'] === 'investor') {
            include __DIR__ . '/../includes/investor/navbar.php';
        } else {
            include __DIR__ . '/../includes/admin/navbar.php';
        }
        ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 space-y-6" id="founder-view-main">
            
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 border-rose-200 dark:border-rose-800/60' ?> flex items-center space-x-2.5 shadow-xs">
                    <i data-lucide="check-circle" class="w-4.5 h-4.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>            <!-- Navigation Strip -->
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2 text-slate-500 dark:text-slate-400 font-medium">
                    <?php if ($currentUser['role'] === 'founder'): ?>
                        <a href="<?= url('founder/feed.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center space-x-1.5 font-bold text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Network Feed</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= url('investor/discover.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center space-x-1.5">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Discover</span>
                        </a>
                    <?php endif; ?>
                    <span class="text-slate-300 dark:text-slate-700">/</span>
                    <span class="text-slate-900 dark:text-white font-bold">Founder Profile</span>
                </div>
            </div>

            <!-- Master Profile Header Card -->
            <div class="card-clean rounded-3xl overflow-hidden relative shadow-sm border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-[#111827]">
                
                <!-- 1. Cover Banner Area (Clean, no floating badges or extra edit buttons) -->
                <div class="relative h-48 sm:h-56 md:h-64 w-full overflow-hidden bg-[#050816]">
                    <?php if (!empty($profile['cover_url'])): ?>
                        <img src="<?= htmlspecialchars($profile['cover_url']) ?>" 
                             alt="Profile Cover Banner" 
                             class="w-full h-full object-cover object-center">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>
                    <?php else: ?>
                        <div class="w-full h-full profile-cover-mesh relative flex items-center justify-between">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent pointer-events-none"></div>

                            <svg class="absolute -left-10 sm:-left-4 -bottom-10 w-52 sm:w-68 h-52 sm:h-68 pointer-events-none opacity-45 sm:opacity-60" viewBox="0 0 300 300" fill="none" stroke="rgba(147, 197, 253, 0.75)" stroke-width="1.3">
                                <polygon points="50,220 110,140 180,180" />
                                <polygon points="110,140 160,70 180,180" />
                                <polygon points="160,70 240,110 180,180" />
                                <polygon points="180,180 260,220 220,280" />
                                <polygon points="50,220 180,180 120,290" />
                                <polygon points="120,290 180,180 220,280" />
                                <polygon points="160,70 190,20 240,110" />
                                <polygon points="110,140 190,20 160,70" />
                                <polygon points="40,130 110,140 50,220" />
                                <circle cx="110" cy="140" r="3" fill="#60A5FA" />
                                <circle cx="160" cy="70" r="3" fill="#60A5FA" />
                                <circle cx="180" cy="180" r="3" fill="#60A5FA" />
                                <circle cx="240" cy="110" r="3" fill="#60A5FA" />
                                <circle cx="50" cy="220" r="3" fill="#60A5FA" />
                                <circle cx="120" cy="290" r="3" fill="#60A5FA" />
                                <circle cx="220" cy="280" r="3" fill="#60A5FA" />
                            </svg>

                            <svg class="absolute -right-8 sm:-right-4 -top-8 w-48 sm:w-60 h-48 sm:h-60 pointer-events-none opacity-40 sm:opacity-55" viewBox="0 0 260 260" fill="none" stroke="rgba(255, 255, 255, 0.7)" stroke-width="1.2">
                                <polygon points="130,30 195,65 210,135" />
                                <polygon points="130,30 210,135 155,175" />
                                <polygon points="130,30 155,175 105,175" />
                                <polygon points="130,30 105,175 50,135" />
                                <polygon points="130,30 50,135 65,65" />
                                <polygon points="130,30 65,65 195,65" />
                                <polygon points="195,65 230,120 210,135" />
                                <circle cx="130" cy="30" r="2.5" fill="#FFFFFF" />
                                <circle cx="195" cy="65" r="2.5" fill="#FFFFFF" />
                                <circle cx="210" cy="135" r="2.5" fill="#FFFFFF" />
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. Profile Identity & Overlapping Circular Avatar -->
                <div class="px-6 sm:px-8 pb-7 pt-0 relative">
                    
                    <!-- Row 1: Circular Avatar (Left) & Only ONE Essential Action Button (Right) -->
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 -mt-14 sm:-mt-18 md:-mt-20 mb-3">
                        
                        <!-- Circular Avatar with Verified Badge -->
                        <div class="relative flex-shrink-0 self-start">
                            <img src="<?= $founder['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=180' ?>" 
                                 alt="<?= htmlspecialchars($founder['name']) ?>"
                                 class="w-28 h-28 sm:w-34 sm:h-34 md:w-38 md:h-38 rounded-full object-cover ring-4 ring-white dark:ring-slate-900 border-2 border-indigo-100 dark:border-indigo-900/60 shadow-xl bg-white dark:bg-slate-900">

                            <?php if ($founder['is_verified']): ?>
                                <div class="absolute bottom-1 right-1.5 w-8 h-8 rounded-full bg-emerald-500 border-2 border-white dark:border-slate-900 flex items-center justify-center text-white shadow-md" title="Verified Founder">
                                    <i data-lucide="check" class="w-4 h-4 stroke-[3]"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Single Essential Action Button on Right -->
                        <div class="flex items-center gap-2 pt-2 sm:pt-0 self-start sm:self-end">
                            <?php if (!$isSelf): ?>
                                <a href="<?= url('investor/messages.php') ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5">
                                    <i data-lucide="message-square" class="w-4 h-4"></i>
                                    <span>Connect</span>
                                </a>
                                <?php if (!empty($companies)): ?>
                                    <a href="<?= url('investor/startup_detail.php?id=' . encode_id($companies[0]['id'])) ?>" class="px-4 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-800 font-bold text-xs rounded-xl transition flex items-center space-x-1.5 shadow-2xs">
                                        <i data-lucide="building-2" class="w-4 h-4 text-indigo-500"></i>
                                        <span>View Startup</span>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?= url('founder/edit_profile.php') ?>" class="px-4.5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-indigo-500/25 transition flex items-center space-x-1.5 hover:scale-105 active:scale-95">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    <span>Edit Profile</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Row 2: Identity Block with Crisp Text -->
                    <div class="space-y-1.5 pt-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                <?= htmlspecialchars($founder['name']) ?>
                            </h1>
                            <?php if ($founder['is_verified']): ?>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80 inline-flex items-center gap-1">
                                    <i data-lucide="shield-check" class="w-3 h-3 text-emerald-600 dark:text-emerald-400"></i>
                                    <span>Verified</span>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex flex-wrap items-center gap-1.5">
                            <span><?= htmlspecialchars($profile['designation'] ?? 'Founder & CEO') ?></span>
                            <?php if (!empty($companies)): ?>
                                <span class="text-slate-400 dark:text-slate-600 font-normal">at</span> 
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold"><?= htmlspecialchars($companies[0]['name']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400 pt-0.5">
                            <span class="inline-flex items-center gap-1">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span><?= htmlspecialchars($founder['city'] ?? 'Mumbai') ?>, <?= htmlspecialchars($founder['country'] ?? 'India') ?></span>
                            </span>
                            <span class="text-slate-300 dark:text-slate-700">•</span>
                            <span class="inline-flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>Member since <?= date('M Y', strtotime($founder['created_at'])) ?></span>
                            </span>
                        </div>
                    </div>

                    <!-- Clean Quick Metrics (No wordy captions) -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-4">
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                            <div class="text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">Startups</div>
                            <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-0.5"><?= count($companies) ?></div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                            <div class="text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">Funding Rounds</div>
                            <div class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-0.5"><?= count($fundingRounds) ?></div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                            <div class="text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">Compliance</div>
                            <div class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">100% Passed</div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                            <div class="text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">Equity</div>
                            <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-0.5"><?= !empty($companies[0]['equity_percent']) ? $companies[0]['equity_percent'] . '%' : 'Founder' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Column (Bio, Persona, Credentials, Links) -->
                <div class="space-y-6">
                    
                    <!-- Bio & Persona Card -->
                    <div class="card-clean rounded-2xl p-6 space-y-4 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-[#111827] shadow-xs">
                        <div>
                            <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-2.5 flex items-center space-x-1.5">
                                <i data-lucide="user" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                                <span>About</span>
                            </h2>
                            <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                                <?= nl2br(htmlspecialchars($profile['bio'] ?: 'High-conviction startup founder building disruptive solutions.')) ?>
                            </p>
                        </div>

                        <?php if (!empty($profile['superpower'])): ?>
                            <div class="p-3 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50 flex items-start gap-2.5">
                                <i data-lucide="zap" class="w-4 h-4 text-indigo-600 dark:text-indigo-400 flex-shrink-0 mt-0.5"></i>
                                <div class="min-w-0">
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-indigo-700 dark:text-indigo-300 block">Superpower</span>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white leading-snug"><?= htmlspecialchars($profile['superpower']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($profile['education'])): ?>
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 flex items-center gap-2.5 text-xs">
                                <i data-lucide="graduation-cap" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                                <div class="min-w-0">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 block tracking-wider">Education</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 truncate block"><?= htmlspecialchars($profile['education']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Skills -->
                        <?php if (!empty($profile['skills'])): 
                            $skillList = array_map('trim', explode(',', $profile['skills']));
                        ?>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80">
                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-2">Skills</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($skillList as $sk): if ($sk === '') continue; ?>
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-900/50">
                                            <?= htmlspecialchars($sk) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Passions -->
                        <?php if (!empty($profile['passions'])): 
                            $passionList = array_map('trim', explode(',', $profile['passions']));
                        ?>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80">
                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-2">Passions</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($passionList as $ps): if ($ps === '') continue; ?>
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200/70 dark:border-amber-800/60">
                                            <?= htmlspecialchars($ps) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Hobbies -->
                        <?php if (!empty($profile['hobbies'])): 
                            $hobbyList = array_map('trim', explode(',', $profile['hobbies']));
                        ?>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80">
                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-2">Hobbies</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($hobbyList as $hb): if ($hb === '') continue; ?>
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-800/60">
                                            <?= htmlspecialchars($hb) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Verified Credentials -->
                    <div class="card-clean rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-[#111827] shadow-xs">
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                            <span>Verified Credentials</span>
                        </h2>
                        <div class="space-y-2.5 text-sm">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50">
                                <span class="text-slate-800 dark:text-slate-200 text-xs font-bold flex items-center space-x-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                    <span>DigiLocker KYC</span>
                                </span>
                                <span class="text-emerald-700 dark:text-emerald-300 font-bold text-[11px]">Verified</span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50">
                                <span class="text-slate-800 dark:text-slate-200 text-xs font-bold flex items-center space-x-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                    <span>MCA Registry Match</span>
                                </span>
                                <span class="text-emerald-700 dark:text-emerald-300 font-bold text-[11px]">Verified</span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                                <span class="text-slate-700 dark:text-slate-300 text-xs font-bold flex items-center space-x-2">
                                    <i data-lucide="credit-card" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>PAN Verification</span>
                                </span>
                                <span class="font-mono text-xs font-bold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($profile['pan_number'] ?? 'VERIFIED') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Links -->
                    <div class="card-clean rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-[#111827] shadow-xs">
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                            <i data-lucide="globe" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                            <span>Contact &amp; Links</span>
                        </h2>
                        <div class="space-y-2.5 text-sm">
                            <?php if (!empty($profile['linkedin_url'])): ?>
                                <a href="<?= htmlspecialchars($profile['linkedin_url']) ?>" target="_blank" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200/80 dark:border-slate-800 text-slate-800 dark:text-slate-200 transition font-bold text-xs">
                                    <span class="flex items-center space-x-2">
                                        <i data-lucide="linkedin" class="w-4 h-4 text-blue-600"></i>
                                        <span>LinkedIn</span>
                                    </span>
                                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($profile['website_url'])): ?>
                                <a href="<?= htmlspecialchars($profile['website_url']) ?>" target="_blank" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200/80 dark:border-slate-800 text-slate-800 dark:text-slate-200 transition font-bold text-xs">
                                    <span class="flex items-center space-x-2">
                                        <i data-lucide="link" class="w-4 h-4 text-indigo-500"></i>
                                        <span>Website</span>
                                    </span>
                                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                                </a>
                            <?php endif; ?>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block uppercase tracking-wider">Email</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white mt-0.5 block truncate"><?= htmlspecialchars($founder['email']) ?></span>
                            </div>

                            <?php if (!empty($founder['phone'])): ?>
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block uppercase tracking-wider">Phone</span>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white mt-0.5 block"><?= htmlspecialchars($founder['phone']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Right Column (Startups, Awards, Funding Rounds) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Startups -->
                    <div class="card-clean rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-[#111827] shadow-xs">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-1.5">
                                <i data-lucide="building-2" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                                <span>Startups (<?= count($companies) ?>)</span>
                            </h2>
                        </div>

                        <?php if (empty($companies)): ?>
                            <div class="py-10 text-center text-slate-400 text-xs font-medium">
                                No startups linked yet.
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($companies as $comp): ?>
                                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-3.5 shadow-2xs">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-start space-x-3">
                                                <img src="<?= $comp['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100' ?>" 
                                                     alt="<?= htmlspecialchars($comp['name']) ?>" 
                                                     class="w-12 h-12 rounded-xl object-cover border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex-shrink-0">
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <h3 class="font-extrabold text-slate-900 dark:text-white text-base"><?= htmlspecialchars($comp['name']) ?></h3>
                                                        <?= render_status_badge($comp['verified_status']) ?>
                                                    </div>
                                                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                                                        <?= htmlspecialchars($comp['industry']) ?> • <?= htmlspecialchars($comp['stage']) ?> Stage
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="text-right flex-shrink-0">
                                                <span class="text-[10px] text-slate-400 uppercase font-bold block">Equity</span>
                                                <span class="font-black text-indigo-600 dark:text-indigo-400 text-sm">
                                                    <?= !empty($comp['equity_percent']) ? $comp['equity_percent'] . '%' : 'Founder' ?>
                                                </span>
                                            </div>
                                        </div>

                                        <?php if (!empty($comp['description'])): ?>
                                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                                                <?= htmlspecialchars($comp['description']) ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="pt-3 border-t border-slate-200/70 dark:border-slate-800 flex items-center justify-between text-xs">
                                            <div class="font-mono text-xs text-slate-500 dark:text-slate-400">
                                                CIN: <span class="font-bold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($comp['cin_number'] ?? 'PENDING') ?></span>
                                            </div>

                                            <?php if ($currentUser['role'] === 'investor'): ?>
                                                <a href="<?= url('investor/startup_detail.php?id=' . encode_id($comp['id'])) ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-bold flex items-center space-x-1">
                                                    <span>View Startup</span>
                                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php elseif ($isSelf): ?>
                                                <a href="<?= url('founder/company.php') ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-bold flex items-center space-x-1">
                                                    <span>Manage Startup</span>
                                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Awards & Recognition (Framed Photos) -->
                    <?php if (!empty($companyAwards)): ?>
                        <div class="card-clean rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-[#111827] shadow-xs">
                            <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100 dark:border-slate-800">
                                <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-1.5">
                                    <i data-lucide="award" class="w-3.5 h-3.5 text-amber-500"></i>
                                    <span>Awards &amp; Recognition (<?= count($companyAwards) ?>)</span>
                                </h2>
                                <span class="text-[11px] text-slate-400 font-medium">Click frame to enlarge</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <?php foreach ($companyAwards as $aw): 
                                    $isPdf = str_ends_with(strtolower($aw['image_url']), '.pdf');
                                ?>
                                    <!-- Certificate / Award Wall Frame -->
                                    <div class="group relative cursor-pointer"
                                         onclick="<?= $isPdf ? "window.open('" . htmlspecialchars($aw['image_url']) . "', '_blank')" : "openFounderAwardLightbox('" . htmlspecialchars($aw['image_url']) . "', '" . htmlspecialchars(addslashes($aw['title'] ?? '')) . "', '" . htmlspecialchars(addslashes($aw['issuer'] ?? '')) . "')" ?>"
                                         title="Click to view full certificate">
                                        <!-- Outer Frame Molding (Border on all 4 sides) -->
                                        <div class="p-3 sm:p-3.5 rounded-xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 dark:from-slate-800 dark:via-slate-900 dark:to-black shadow-[0_10px_25px_-5px_rgba(0,0,0,0.25)] border-2 border-amber-600/30 group-hover:border-amber-500/70 transition-all duration-300 group-hover:scale-[1.02] group-hover:shadow-[0_16px_35px_-5px_rgba(0,0,0,0.35)]">
                                            <!-- Inner Matting (passe-partout on all sides) -->
                                            <div class="p-2 sm:p-2.5 rounded-lg bg-[#faf8f5] dark:bg-[#141820] border border-amber-200/40 dark:border-amber-900/40 shadow-inner">
                                                <!-- Certificate Photo Mount -->
                                                <div class="relative w-full h-52 sm:h-60 rounded bg-white dark:bg-slate-950 overflow-hidden flex items-center justify-center border border-slate-300/80 dark:border-slate-800 shadow-xs">
                                                    <?php if ($isPdf): ?>
                                                        <div class="text-center p-4">
                                                            <i data-lucide="file-text" class="w-10 h-10 text-rose-500 mx-auto mb-2"></i>
                                                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">Certificate (PDF)</span>
                                                            <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold block mt-1">Click to view &rarr;</span>
                                                        </div>
                                                    <?php else: ?>
                                                        <img src="<?= htmlspecialchars($aw['image_url']) ?>" 
                                                             alt="Award Certificate" 
                                                             class="w-full h-full object-contain p-1 group-hover:brightness-105 transition duration-300"
                                                             loading="lazy">
                                                        <div class="absolute inset-0 bg-slate-950/20 opacity-0 group-hover:opacity-100 transition duration-200 flex items-center justify-center">
                                                            <span class="px-3 py-1.5 rounded-full bg-slate-900/90 text-white text-[11px] font-bold shadow-lg backdrop-blur-xs flex items-center gap-1.5">
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
                        </div>
                    <?php endif; ?>

                    <!-- Funding Rounds -->
                    <div class="card-clean rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-[#111827] shadow-xs">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-1.5">
                                <i data-lucide="circle-dollar-sign" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                                <span>Funding Rounds</span>
                            </h2>
                        </div>

                        <?php if (empty($fundingRounds)): ?>
                            <div class="py-10 text-center text-slate-400 text-xs font-medium">
                                No active funding rounds.
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($fundingRounds as $fr): ?>
                                    <?php 
                                    $pct = $fr['target_amount'] > 0 ? round(($fr['amount_raised'] / $fr['target_amount']) * 100) : 0;
                                    ?>
                                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-3.5 shadow-2xs">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="font-extrabold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($fr['round_name']) ?></div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400 font-medium"><?= htmlspecialchars($fr['company_name']) ?></div>
                                            </div>
                                            <?= render_status_badge($fr['status']) ?>
                                        </div>

                                        <div>
                                            <div class="flex justify-between text-xs mb-1 font-medium">
                                                <span class="text-slate-600 dark:text-slate-400">Raised: <strong class="text-slate-900 dark:text-white font-bold"><?= format_inr($fr['amount_raised']) ?></strong></span>
                                                <span class="text-indigo-600 dark:text-indigo-400 font-black"><?= $pct ?>%</span>
                                            </div>
                                            <div class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                                <div class="h-full bg-indigo-600 rounded-full" style="width: <?= min(100, $pct) ?>%"></div>
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-1 flex justify-between">
                                                <span>Target: <?= format_inr($fr['target_amount']) ?></span>
                                            </div>
                                        </div>

                                        <div class="pt-3 border-t border-slate-200/70 dark:border-slate-800 flex items-center justify-between text-xs">
                                            <div class="flex items-center space-x-2 text-slate-600 dark:text-slate-400">
                                                <span>Val: <strong class="text-slate-900 dark:text-white"><?= format_inr($fr['valuation']) ?></strong></span>
                                                <span>•</span>
                                                <span>Equity: <strong class="text-emerald-600 dark:text-emerald-400"><?= $fr['equity_offered'] ?>%</strong></span>
                                            </div>

                                            <?php if ($currentUser['role'] === 'investor'): ?>
                                                <a href="<?= url('investor/invest.php?round_id=' . encode_id($fr['id'])) ?>" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                                                    Invest
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>        </div>

        </main>
    </div>

    <!-- Award Lightbox Modal -->
    <div id="founder-award-lightbox"
        class="hidden fixed inset-0 bg-slate-950/85 backdrop-blur-md z-50 flex items-center justify-center p-4 transition-all duration-300"
        onclick="if (event.target === this) closeFounderAwardLightbox();">
        <div class="relative max-w-4xl w-full bg-slate-900 rounded-3xl overflow-hidden shadow-2xl border border-slate-800">
            <div class="flex items-center justify-between p-4 sm:p-5 border-b border-slate-800 bg-slate-900/95 text-white">
                <div>
                    <h4 id="f-lightbox-title" class="font-extrabold text-base sm:text-lg text-white"></h4>
                    <p id="f-lightbox-issuer" class="text-xs text-amber-400 font-medium mt-0.5"></p>
                </div>
                <button type="button" onclick="closeFounderAwardLightbox()"
                    class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition" aria-label="Close modal">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-3 bg-black flex items-center justify-center min-h-[320px] max-h-[75vh] overflow-hidden">
                <img id="f-lightbox-img" src="" alt="Award Photo" class="max-h-[70vh] max-w-full object-contain rounded-xl shadow-lg">
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#founder-view-main", { duration: 0.45, y: 12, opacity: 0, ease: "power2.out" });

        function openFounderAwardLightbox(src, title, issuer) {
            const modal = document.getElementById('founder-award-lightbox');
            const img = document.getElementById('f-lightbox-img');
            const titleEl = document.getElementById('f-lightbox-title');
            const issuerEl = document.getElementById('f-lightbox-issuer');
            if (modal && img) {
                img.src = src;
                if (titleEl) titleEl.textContent = title || 'Verified Award';
                if (issuerEl) issuerEl.textContent = issuer ? ('Issued by: ' + issuer) : '';
                modal.classList.remove('hidden');
                lucide.createIcons();
            }
        }
        function closeFounderAwardLightbox() {
            const modal = document.getElementById('founder-award-lightbox');
            if (modal) modal.classList.add('hidden');
        }
        document.addEventListener('keydown', (e) => { 
            if (e.key === 'Escape') closeFounderAwardLightbox();
        });
    </script>
</body>
</html>
