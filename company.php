<?php
/**
 * Public Company Dossier & Zero-Login Data Room
 * Accessible to team members, employees, candidates, and investors without requiring login/signup.
 */
require_once __DIR__ . '/config.php';

$db = get_db();
$currentUser = current_user();

// 1. Resolve Company ID from hash or numeric ID
$rawId = trim($_GET['id'] ?? '');
$companyId = 0;

if (!empty($rawId)) {
    $decoded = hash_id_decode($rawId);
    if ($decoded > 0) {
        $companyId = $decoded;
    } elseif (is_numeric($rawId)) {
        $companyId = (int) $rawId;
    }
}

// Fallback: If no ID or invalid, redirect to welcome or show error
if ($companyId <= 0) {
    // If user is a founder, redirect to their company
    if ($currentUser && $currentUser['role'] === 'founder') {
        $myComp = $db->query("SELECT c.id FROM companies c JOIN company_founders cf ON c.id = cf.company_id WHERE cf.user_id = {$currentUser['id']} LIMIT 1")->fetch();
        if ($myComp) {
            header('Location: ' . url('company.php?id=' . hash_id_encode($myComp['id'])));
            exit;
        }
    }
    // Default fallback to first active company
    $firstComp = $db->query("SELECT id FROM companies ORDER BY id ASC LIMIT 1")->fetch();
    if ($firstComp) {
        $companyId = (int)$firstComp['id'];
    } else {
        die("No company profile found.");
    }
}

// 2. Fetch Company Details
$company = null;
$founders = [];
$documents = [];
$activeRound = null;

if ($db) {
    $cStmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
    $cStmt->execute([$companyId]);
    $company = $cStmt->fetch();

    if (!$company) {
        http_response_code(404);
        die("Company profile not found.");
    }

    // Founders
    $fStmt = $db->prepare("
        SELECT u.id as user_id, u.name, u.email, u.avatar_url, u.city as user_city,
               cf.designation, cf.equity_percent, cf.is_signatory,
               fp.bio, fp.linkedin_url, fp.website_url
        FROM company_founders cf
        JOIN users u ON cf.user_id = u.id
        LEFT JOIN founder_profiles fp ON u.id = fp.user_id
        WHERE cf.company_id = ?
        ORDER BY cf.is_signatory DESC, cf.equity_percent DESC
    ");
    $fStmt->execute([$companyId]);
    $founders = $fStmt->fetchAll();

    // Documents
    $dStmt = $db->prepare("SELECT * FROM company_documents WHERE company_id = ? ORDER BY uploaded_at DESC");
    $dStmt->execute([$companyId]);
    $documents = $dStmt->fetchAll();

    // Active Round
    $rStmt = $db->prepare("SELECT * FROM funding_rounds WHERE company_id = ? AND status IN ('LIVE', 'PARTIALLY_FUNDED', 'APPROVED') ORDER BY created_at DESC LIMIT 1");
    $rStmt->execute([$companyId]);
    $activeRound = $rStmt->fetch();

    // Check if logged in investor already expressed interest
    $userExistingInterest = null;
    if ($currentUser && $currentUser['role'] === 'investor') {
        $eiStmt = $db->prepare("SELECT * FROM investor_interests WHERE investor_id = ? AND company_id = ? LIMIT 1");
        $eiStmt->execute([$currentUser['id'], $companyId]);
        $userExistingInterest = $eiStmt->fetch();
    }

    // 1.1 Track Founder Share & Invite Link attribution
    $inviteRef = trim($_GET['invite'] ?? ($_GET['ref'] ?? ($_SESSION['active_founder_invite'] ?? '')));
    $currentInviteData = null;
    if (!empty($inviteRef)) {
        try {
            $invStmt = $db->prepare("SELECT fi.*, u.name as founder_name FROM founder_invites fi JOIN users u ON fi.founder_id = u.id WHERE fi.invite_code = ? LIMIT 1");
            $invStmt->execute([$inviteRef]);
            $currentInviteData = $invStmt->fetch();

            if ($currentInviteData && isset($_GET['invite']) || isset($_GET['ref'])) {
                // Increment views
                $db->prepare("
                    UPDATE founder_invites 
                    SET views_count = views_count + 1, 
                        status = IF(status = 'pending', 'viewed', status), 
                        updated_at = NOW() 
                    WHERE id = ?
                ")->execute([$currentInviteData['id']]);
                $_SESSION['active_founder_invite'] = $inviteRef;
            }
        } catch (\Throwable $e) {}
    }
}

$compHash = hash_id_encode($company['id']);
$publicShareUrl = url('company.php?id=' . $compHash);
if (!empty($inviteRef)) {
    $publicShareUrl .= '&invite=' . urlencode($inviteRef);
}

// Company Initials
$companyInitials = 'SU';
if (!empty($company['name'])) {
    $words = preg_split('/\s+/', trim($company['name']));
    if (count($words) >= 2) {
        $companyInitials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        $companyInitials = strtoupper(substr($words[0], 0, 2));
    }
}

// Find primary pitch deck doc if any
$pitchDeckDoc = null;
foreach ($documents as $doc) {
    if (stripos($doc['document_type'], 'pitch') !== false || stripos($doc['title'], 'pitch') !== false) {
        $pitchDeckDoc = $doc;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($company['name']) ?> • Public Company Dossier • <?= APP_NAME ?></title>
    <meta name="description" content="<?= htmlspecialchars($company['pitch'] ?? 'Company overview and data room on NEXORA.') ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'nx-bg': '#F8FAFC',
                        'nx-dark': '#0B0F19',
                        'nx-blue': '#2563EB',
                        'nx-indigo': '#4F46E5',
                        'nx-emerald': '#059669',
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                        heading: ['Plus Jakarta Sans', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #F8FAFC;
        }
        .glass-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .card-dossier {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1.5rem;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            transition: all 0.2s ease;
        }
        .card-dossier:hover {
            border-color: #CBD5E1;
            box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.08);
        }
    </style>
</head>

<body class="bg-[#F8FAFC] text-slate-900 min-h-screen flex flex-col selection:bg-blue-600 selection:text-white antialiased">

    <!-- Top Guest / Verified Share Bar -->
    <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-800 text-white text-xs sm:text-sm py-2.5 px-4 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-white/20 text-white text-[11px] font-bold uppercase tracking-wider backdrop-blur-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Direct Share Link
                </span>
                <span class="text-xs sm:text-sm font-medium text-blue-100">
                    Viewing official company dossier for <strong class="text-white"><?= htmlspecialchars($company['name']) ?></strong> (No login required)
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="openShareModal('<?= htmlspecialchars($publicShareUrl) ?>', '<?= htmlspecialchars(addslashes($company['name'])) ?>')" class="px-3 py-1 rounded-lg bg-white/15 hover:bg-white/25 text-white text-xs font-bold transition flex items-center gap-1.5 border border-white/20">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                    <span>Share Link</span>
                </button>

                <?php if ($currentUser): ?>
                    <a href="<?= url($currentUser['role'] . '/dashboard.php') ?>" class="px-3 py-1 rounded-lg bg-white text-blue-700 hover:bg-blue-50 text-xs font-bold transition flex items-center gap-1 shadow-xs">
                        <span>Go to Dashboard</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                <?php else: ?>
                    <a href="<?= url('auth/login.php') ?>" class="px-3 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-blue-100 text-xs font-semibold transition">
                        Portal Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Navigation Bar -->
    <header class="glass-header border-b border-slate-200/80 sticky top-[41px] z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="<?= url('index.php') ?>" class="flex items-center gap-2 group">
                <span class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-black text-sm shadow-md shadow-blue-500/25 group-hover:scale-105 transition-transform">
                    N
                </span>
                <span class="font-extrabold text-lg text-slate-900 tracking-tight">NEXORA</span>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200">Public Dossier</span>
            </a>

            <div class="flex items-center gap-3">
                <a href="#company-data-room" class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-blue-600 hover:bg-slate-100 transition">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    <span>Data Room (<?= count($documents) ?>)</span>
                </a>

                <a href="#company-team" class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-blue-600 hover:bg-slate-100 transition">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>Founders & Team</span>
                </a>

                <button type="button" onclick="openShareModal('<?= htmlspecialchars($publicShareUrl) ?>', '<?= htmlspecialchars(addslashes($company['name'])) ?>')" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                    <span>Share Company</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Executive Hero Header Card -->
        <div class="card-dossier p-6 sm:p-8 lg:p-10 relative overflow-hidden bg-gradient-to-b from-white to-slate-50/60">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                
                <!-- Left: Identity & Badges -->
                <div class="flex items-start sm:items-center gap-5 min-w-0 flex-1">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-violet-600 text-white flex items-center justify-center font-black text-2xl sm:text-3xl shadow-xl shadow-blue-500/20 flex-shrink-0">
                        <?= htmlspecialchars($companyInitials) ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 tracking-tight">
                                <?= htmlspecialchars($company['name']) ?>
                            </h1>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Verified Entity</span>
                            </span>
                            <?php if (!empty($company['legal_name'])): ?>
                                <span class="text-xs px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 font-semibold border border-slate-200">
                                    <?= htmlspecialchars($company['legal_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="text-slate-600 text-sm sm:text-base leading-relaxed max-w-3xl font-medium">
                            <?= htmlspecialchars($company['pitch'] ?: 'Innovative technology startup building market-leading solutions.') ?>
                        </p>

                        <!-- Attribute Chips -->
                        <div class="flex flex-wrap items-center gap-2 mt-4 text-xs font-semibold text-slate-700">
                            <span class="px-3 py-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200/80 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                <?= htmlspecialchars($company['industry'] ?? 'Technology') ?>
                            </span>
                            <span class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-800 border border-indigo-200/80">
                                <?= htmlspecialchars($company['stage'] ?? 'Seed') ?> Stage
                            </span>
                            <span class="px-3 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200">
                                <?= htmlspecialchars($company['business_model'] ?? 'B2B SaaS') ?>
                            </span>
                            <span class="px-3 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <?= htmlspecialchars($company['city'] ?? 'Bengaluru') ?>, <?= htmlspecialchars($company['country'] ?? 'India') ?>
                            </span>
                            <?php if (!empty($company['employee_count'])): ?>
                                <span class="px-3 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200">
                                    <?= htmlspecialchars($company['employee_count']) ?> Employees
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($company['website'])): ?>
                                <a href="<?= htmlspecialchars(str_starts_with($company['website'], 'http') ? $company['website'] : 'https://' . $company['website']) ?>" target="_blank" rel="noopener noreferrer" class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-blue-700 border border-slate-200 flex items-center gap-1 transition">
                                    <span><?= htmlspecialchars(parse_url($company['website'], PHP_URL_HOST) ?: $company['website']) ?></span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: High-Impact CTAs -->
                <div class="flex flex-col sm:flex-row lg:flex-col gap-3 w-full lg:w-auto flex-shrink-0">
                    <?php if ($currentUser && $currentUser['role'] === 'investor'): ?>
                        <?php if ($userExistingInterest): ?>
                            <div class="w-full p-3 rounded-2xl bg-indigo-50 border border-indigo-200/80 text-indigo-900 text-xs space-y-1 text-center">
                                <div class="font-bold flex items-center justify-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full <?= $userExistingInterest['status'] === 'accepted' ? 'bg-emerald-500' : ($userExistingInterest['status'] === 'declined' ? 'bg-slate-400' : 'bg-amber-500') ?>"></span>
                                    <span>Interest <?= strtoupper($userExistingInterest['status']) ?></span>
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    <?= $userExistingInterest['status'] === 'accepted' ? 'Communication unlocked!' : 'Submitted to founders' ?>
                                </div>
                                <a href="<?= url('investor/startup_detail.php?id=' . encode_id($company['id'])) ?>" class="block font-bold text-indigo-600 hover:underline pt-1 text-[11px]">
                                    Open in Investor Portal &rarr;
                                </a>
                            </div>
                        <?php else: ?>
                            <button type="button" onclick="openExpressInterestModal()" class="w-full justify-center px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-sm shadow-md shadow-emerald-500/25 flex items-center gap-2 transition">
                                <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>Express Interest &rarr;</span>
                            </button>
                        <?php endif; ?>
                    <?php elseif (!$currentUser): ?>
                        <a href="<?= url('login.php?redirect=' . urlencode('company.php?id=' . $compHash)) ?>" class="w-full justify-center px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-sm shadow-md shadow-emerald-500/25 flex items-center gap-2 transition">
                            <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span>Express Interest (Login)</span>
                        </a>
                    <?php endif; ?>

                    <button type="button" onclick="openShareModal('<?= htmlspecialchars($publicShareUrl) ?>', '<?= htmlspecialchars(addslashes($company['name'])) ?>')" class="w-full justify-center px-5 py-2.5 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-blue-500/25 flex items-center gap-2 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                        </svg>
                        <span>Share Profile Link</span>
                    </button>

                    <?php if ($pitchDeckDoc): ?>
                        <a href="<?= url('download.php?type=company&id=' . $pitchDeckDoc['id'] . '&comp_hash=' . $compHash) ?>" class="w-full justify-center px-5 py-2.5 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-bold text-xs sm:text-sm shadow-xs flex items-center gap-2 transition">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Download Pitch Deck</span>
                        </a>
                    <?php else: ?>
                        <a href="#company-data-room" class="w-full justify-center px-5 py-2.5 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-bold text-xs sm:text-sm shadow-xs flex items-center gap-2 transition">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                            <span>View Data Room (<?= count($documents) ?>)</span>
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Metric KPI Strip -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="card-dossier p-5">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Company Stage</span>
                <div class="text-xl sm:text-2xl font-black text-slate-900"><?= htmlspecialchars($company['stage'] ?? 'Seed') ?></div>
                <span class="text-xs text-blue-600 font-semibold mt-1 inline-block"><?= htmlspecialchars($company['business_model'] ?? 'B2B SaaS') ?></span>
            </div>

            <div class="card-dossier p-5">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Headquarters</span>
                <div class="text-xl sm:text-2xl font-black text-slate-900"><?= htmlspecialchars($company['city'] ?? 'Bengaluru') ?></div>
                <span class="text-xs text-slate-500 font-medium mt-1 inline-block"><?= htmlspecialchars($company['state'] ?? 'Karnataka') ?>, <?= htmlspecialchars($company['country'] ?? 'India') ?></span>
            </div>

            <div class="card-dossier p-5">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Data Room</span>
                <div class="text-xl sm:text-2xl font-black text-slate-900"><?= count($documents) ?> Documents</div>
                <span class="text-xs text-emerald-600 font-semibold mt-1 inline-flex items-center gap-1"><svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg><span>Direct Guest Download</span></span>
            </div>

            <div class="card-dossier p-5">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Funding Round</span>
                <?php if ($activeRound): ?>
                    <div class="text-xl sm:text-2xl font-black text-indigo-600">₹<?= number_format($activeRound['target_amount'] / 10000000, 2) ?> Cr</div>
                    <span class="text-xs text-indigo-600 font-semibold mt-1 inline-block"><?= htmlspecialchars($activeRound['round_name'] ?? 'Active Round') ?></span>
                <?php else: ?>
                    <div class="text-xl sm:text-2xl font-black text-slate-900">Privately Held</div>
                    <span class="text-xs text-slate-500 font-medium mt-1 inline-block">Active Operations</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2-Column Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left 2 Cols: Overview, Story, Market, and Documents -->
            <div class="lg:col-span-2 space-y-8">

                <!-- About & Description -->
                <div class="card-dossier p-6 sm:p-8 space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-blue-50 text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                </svg>
                            </span>
                            <h2 class="text-lg sm:text-xl font-bold text-slate-900">About <?= htmlspecialchars($company['name']) ?></h2>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">Overview</span>
                    </div>

                    <?php if (!empty($company['pitch'])): ?>
                        <div class="p-4 sm:p-5 rounded-2xl bg-blue-50/70 border-l-4 border-blue-600 text-slate-800 text-sm sm:text-base font-medium leading-relaxed">
                            &ldquo;<?= htmlspecialchars($company['pitch']) ?>&rdquo;
                        </div>
                    <?php endif; ?>

                    <div class="prose max-w-none text-slate-600 text-sm sm:text-base leading-relaxed">
                        <?= nl2br(htmlspecialchars($company['description'] ?: 'No long description provided.')) ?>
                    </div>

                    <?php if (!empty($company['target_market'])): ?>
                        <div class="pt-4 border-t border-slate-100">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Target Market &amp; ICP</h3>
                            <p class="text-slate-700 text-sm sm:text-base leading-relaxed font-medium">
                                <?= htmlspecialchars($company['target_market']) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Confidential Data Room & Documents (Direct Public Access) -->
                <div class="card-dossier p-6 sm:p-8 space-y-6" id="company-data-room">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-slate-900">Company Data Room &amp; Files</h2>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                                Verified documents uploaded by the founding team. Available for direct download via this link.
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold self-start sm:self-auto">
                            <?= count($documents) ?> Files Available
                        </span>
                    </div>

                    <?php if (empty($documents)): ?>
                        <div class="text-center py-12 px-4 rounded-2xl bg-slate-50 border border-dashed border-slate-200">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-base font-bold text-slate-800">No public documents uploaded yet</h3>
                            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-md mx-auto">
                                The founder has not attached files to the data room yet. Contact the founder directly to request diligence materials.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-slate-100">
                            <?php foreach ($documents as $doc): 
                                $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                                $downloadUrl = url('download.php?type=company&id=' . $doc['id'] . '&comp_hash=' . $compHash);
                            ?>
                                <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/80 -mx-4 px-4 rounded-xl transition">
                                    <div class="flex items-start gap-3.5 min-w-0">
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs uppercase flex-shrink-0 mt-0.5 border border-slate-200">
                                            <?= $ext ?: 'DOC' ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-sm font-bold text-slate-900 truncate"><?= htmlspecialchars($doc['title'] ?? 'Company Document') ?></h4>
                                                <?php if ($doc['is_verified']): ?>
                                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">Verified</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1">
                                                <span class="font-semibold text-indigo-600"><?= htmlspecialchars($doc['document_type']) ?></span>
                                                <span>&bull;</span>
                                                <span><?= htmlspecialchars($doc['file_size'] ?? '2.4 MB') ?></span>
                                                <span>&bull;</span>
                                                <span><?= date('M d, Y', strtotime($doc['uploaded_at'])) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0 self-end sm:self-auto">
                                        <a href="<?= $downloadUrl ?>" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            <span>Download</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Active Funding Round & Deal Terms (If Active) -->
                <?php if ($activeRound): ?>
                    <div class="card-dossier p-6 sm:p-8 space-y-5 bg-gradient-to-br from-indigo-50/50 via-white to-blue-50/40">
                        <div class="flex items-center justify-between pb-3 border-b border-indigo-100">
                            <div class="flex items-center gap-2">
                                <span class="p-2 rounded-xl bg-indigo-600 text-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                                <div>
                                    <h2 class="text-lg sm:text-xl font-bold text-slate-900"><?= htmlspecialchars($activeRound['round_name'] ?? 'Active Round') ?> Details</h2>
                                    <span class="text-xs font-semibold text-emerald-700">● LIVE Syndicate &amp; Capital Raise</span>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-indigo-100 text-indigo-800 text-xs font-bold">
                                <?= $activeRound['target_amount'] > 0 ? round(($activeRound['amount_raised'] / $activeRound['target_amount']) * 100) : 0 ?>% Funded
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <?php 
                            $pct = $activeRound['target_amount'] > 0 ? min(100, round(($activeRound['amount_raised'] / $activeRound['target_amount']) * 100)) : 0;
                        ?>
                        <div>
                            <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                                <span>Raised: ₹<?= number_format($activeRound['amount_raised']) ?></span>
                                <span>Target: ₹<?= number_format($activeRound['target_amount']) ?></span>
                            </div>
                            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-full rounded-full transition-all duration-700" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>

                        <!-- Key Terms Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2">
                            <div class="p-3.5 rounded-xl bg-white border border-slate-200">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Pre-Money Valuation</span>
                                <span class="text-base sm:text-lg font-bold text-slate-900">₹<?= number_format($activeRound['valuation']) ?></span>
                            </div>
                            <div class="p-3.5 rounded-xl bg-white border border-slate-200">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Min Ticket</span>
                                <span class="text-base sm:text-lg font-bold text-slate-900">₹<?= number_format($activeRound['min_investment']) ?></span>
                            </div>
                            <div class="p-3.5 rounded-xl bg-white border border-slate-200">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Equity Dilution</span>
                                <span class="text-base sm:text-lg font-bold text-indigo-600"><?= htmlspecialchars($activeRound['equity_offered']) ?>%</span>
                            </div>
                        </div>

                        <?php if (!empty($activeRound['purpose'])): ?>
                            <div class="pt-2 text-xs sm:text-sm text-slate-600">
                                <strong class="text-slate-800">Use of Funds:</strong> <?= htmlspecialchars($activeRound['purpose']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Direct Syndicate & Express Interest Action Bar -->
                        <div class="mt-4 pt-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                            <div class="text-xs text-slate-500 font-medium">
                                Backed by verified angel syndicates and statutory MCA compliance.
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($currentUser && $currentUser['role'] === 'investor'): ?>
                                    <?php if (!$userExistingInterest): ?>
                                        <button type="button" onclick="openExpressInterestModal()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                                            <span>Express Interest</span>
                                        </button>
                                    <?php endif; ?>
                                    <a href="<?= url('investor/invest.php?round=' . hash_id_encode($activeRound['id'])) ?>" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                                        <span>Participate / Invest &rarr;</span>
                                    </a>
                                <?php elseif (!$currentUser): ?>
                                    <a href="<?= url('login.php?redirect=' . urlencode('company.php?id=' . $compHash)) ?>" class="px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                                        <span>Log In to Invest / Express Interest</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Right Col: Founders & Team + Corporate Details -->
            <div class="space-y-8">

                <!-- Founders & Leadership Team -->
                <div class="card-dossier p-6 space-y-5" id="company-team">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-violet-50 text-violet-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </span>
                            <h2 class="text-lg font-bold text-slate-900">Leadership Team</h2>
                        </div>
                        <span class="text-xs font-bold text-slate-500"><?= count($founders) ?> Key Execs</span>
                    </div>

                    <div class="space-y-4">
                        <?php foreach ($founders as $f): 
                            $fInitials = 'F';
                            if (!empty($f['name'])) {
                                $parts = explode(' ', trim($f['name']));
                                $fInitials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                            }
                        ?>
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                                <div class="flex items-center gap-3">
                                    <?php if (!empty($f['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars(get_avatar_url($f['avatar_url'])) ?>" alt="<?= htmlspecialchars($f['name']) ?>" class="w-12 h-12 rounded-xl object-cover border border-slate-200 shadow-xs flex-shrink-0">
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-slate-800 to-slate-700 text-white flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                                            <?= htmlspecialchars($fInitials) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <h3 class="text-sm font-bold text-slate-900 truncate"><?= htmlspecialchars($f['name']) ?></h3>
                                            <?php if ($f['is_signatory']): ?>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-bold">Signatory</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs font-semibold text-indigo-600"><?= htmlspecialchars($f['designation'] ?? 'Co-Founder & Executive') ?></div>
                                    </div>
                                </div>

                                <?php if (!empty($f['bio'])): ?>
                                    <p class="text-xs text-slate-600 leading-relaxed font-normal">
                                        <?= htmlspecialchars($f['bio']) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($f['linkedin_url'])): ?>
                                    <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between">
                                        <span class="text-[11px] text-slate-500 font-medium">Professional Profile</span>
                                        <a href="<?= htmlspecialchars($f['linkedin_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition">
                                            <span>LinkedIn</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Corporate Governance & Regulatory Identifiers -->
                <div class="card-dossier p-6 space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <span class="p-2 rounded-xl bg-slate-100 text-slate-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </span>
                        <h2 class="text-base font-bold text-slate-900">Corporate Master Data</h2>
                    </div>

                    <div class="space-y-3 text-xs">
                        <?php if (!empty($company['cin_number'])): ?>
                            <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500 font-semibold">CIN Number</span>
                                <span class="font-mono font-bold text-slate-800"><?= htmlspecialchars($company['cin_number']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($company['incorporation_date'])): ?>
                            <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500 font-semibold">Incorporated</span>
                                <span class="font-bold text-slate-800"><?= date('F d, Y', strtotime($company['incorporation_date'])) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-500 font-semibold">Authorized Capital</span>
                            <span class="font-bold text-slate-800">₹<?= number_format($company['authorized_capital'] ?? 10000000) ?></span>
                        </div>

                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-500 font-semibold">Face Value</span>
                            <span class="font-bold text-slate-800">₹<?= number_format($company['face_value_per_share'] ?? 10, 2) ?></span>
                        </div>

                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-500 font-semibold">ESOP Pool</span>
                            <span class="font-bold text-slate-800"><?= number_format($company['esop_pool_percent'] ?? 10, 1) ?>%</span>
                        </div>

                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-slate-500 font-semibold">Diligence Status</span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[11px]">Verified Registry</span>
                        </div>
                    </div>
                </div>

                <!-- Fast Contact & Connect -->
                <div class="card-dossier p-6 text-center space-y-4 bg-gradient-to-tr from-slate-900 to-slate-800 text-white">
                    <div class="w-12 h-12 rounded-2xl bg-white/10 text-white flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Contact <?= htmlspecialchars($company['name']) ?></h3>
                        <p class="text-xs text-slate-300 mt-1 max-w-xs mx-auto">
                            Connect with the founders regarding employment, syndication, or strategic partnerships.
                        </p>
                    </div>
                    <?php 
                        $primaryEmail = !empty($founders[0]['email']) ? $founders[0]['email'] : 'contact@' . ($company['website'] ? parse_url($company['website'], PHP_URL_HOST) : 'example.com');
                    ?>
                    <a href="mailto:<?= htmlspecialchars($primaryEmail) ?>?subject=<?= urlencode('Inquiry regarding ' . $company['name']) ?>" class="inline-flex items-center justify-center w-full px-4 py-2.5 rounded-xl bg-white text-slate-900 hover:bg-slate-100 text-xs font-bold transition shadow-md">
                        Email Founding Team
                    </a>
                </div>

            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="mt-16 border-t border-slate-200 bg-white py-8 text-xs text-slate-500 text-center">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="font-extrabold text-slate-900">NEXORA</span>
                <span>&bull;</span>
                <span>Verified Venture Ecosystem</span>
            </div>
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($company['name']) ?> &amp; <?= APP_NAME ?>. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <a href="<?= url('legal/privacy.php') ?>" class="hover:text-slate-800 transition">Privacy</a>
                <a href="<?= url('legal/terms.php') ?>" class="hover:text-slate-800 transition">Terms</a>
            </div>
        </div>
    </footer>

    <?php if ($currentUser && $currentUser['role'] === 'investor' && !$userExistingInterest): ?>
    <!-- Express Interest Modal for Verified Investors -->
    <div id="express-interest-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl relative border border-slate-200">
            <button type="button" onclick="closeExpressInterestModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">Express Interest</h3>
                    <p class="text-xs text-slate-500">Discover and connect directly with the founders</p>
                </div>
            </div>

            <form id="express-interest-form" onsubmit="submitExpressInterest(event)" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="company_id" value="<?= htmlspecialchars($company['id']) ?>">

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-600 text-white font-bold flex items-center justify-center flex-shrink-0 text-sm">
                        <?= htmlspecialchars($companyInitials) ?>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Startup</div>
                        <div class="text-sm font-bold text-slate-900 truncate"><?= htmlspecialchars($company['name']) ?></div>
                        <div class="text-xs text-slate-500"><?= htmlspecialchars($company['industry'] ?? '') ?> &bull; <?= htmlspecialchars($company['stage'] ?? 'Seed') ?></div>
                    </div>
                </div>

                <div>
                    <label for="interest-message" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Why are you interested? <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="interest-message" name="message" rows="4" required minlength="10" maxlength="2000"
                        placeholder="Briefly describe what caught your attention (e.g. market thesis, traction, technology synergies, or potential check size)..."
                        class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 bg-white text-slate-900 p-3 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition placeholder-slate-400"></textarea>
                    <div class="flex justify-between items-center mt-1 text-[11px] text-slate-400">
                        <span>Keep the message short and useful.</span>
                        <span id="char-count">0 / 2000</span>
                    </div>
                </div>

                <div id="interest-error" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold"></div>
                <div id="interest-success" class="hidden p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold"></div>

                <div class="flex items-center justify-end space-x-3 pt-2">
                    <button type="button" onclick="closeExpressInterestModal()"
                        class="px-4 py-2.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
                        Cancel
                    </button>
                    <button type="submit" id="btn-submit-interest"
                        class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center space-x-2 shadow-sm disabled:opacity-50">
                        <span>Send Expression of Interest &rarr;</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const expressModal = document.getElementById('express-interest-modal');
        const expressMsg = document.getElementById('interest-message');
        const expressCharCount = document.getElementById('char-count');
        const expressErr = document.getElementById('interest-error');
        const expressSucc = document.getElementById('interest-success');
        const expressBtn = document.getElementById('btn-submit-interest');

        function openExpressInterestModal() {
            if (expressModal) {
                expressModal.classList.remove('hidden');
                if (expressErr) expressErr.classList.add('hidden');
                if (expressSucc) expressSucc.classList.add('hidden');
                if (expressMsg) expressMsg.focus();
            }
        }

        function closeExpressInterestModal() {
            if (expressModal) expressModal.classList.add('hidden');
        }

        if (expressMsg && expressCharCount) {
            expressMsg.addEventListener('input', () => {
                expressCharCount.textContent = `${expressMsg.value.length} / 2000`;
            });
        }

        async function submitExpressInterest(e) {
            e.preventDefault();
            if (!expressMsg || expressMsg.value.trim().length < 10) {
                if (expressErr) {
                    expressErr.textContent = 'Please write at least 10 characters explaining your interest.';
                    expressErr.classList.remove('hidden');
                }
                return;
            }

            const form = document.getElementById('express-interest-form');
            const formData = new FormData(form);

            if (expressBtn) {
                expressBtn.disabled = true;
                expressBtn.textContent = 'Submitting...';
            }
            if (expressErr) expressErr.classList.add('hidden');
            if (expressSucc) expressSucc.classList.add('hidden');

            try {
                const response = await fetch('<?= url("api/investor/express-interest.php") ?>', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    if (expressSucc) {
                        expressSucc.textContent = result.message || 'Interest sent successfully! The founder has been notified.';
                        expressSucc.classList.remove('hidden');
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    if (expressErr) {
                        expressErr.textContent = result.error || 'Failed to submit interest.';
                        expressErr.classList.remove('hidden');
                    }
                    if (expressBtn) {
                        expressBtn.disabled = false;
                        expressBtn.textContent = 'Send Expression of Interest →';
                    }
                }
            } catch (err) {
                if (expressErr) {
                    expressErr.textContent = 'Network error. Please try again.';
                    expressErr.classList.remove('hidden');
                }
                if (expressBtn) {
                    expressBtn.disabled = false;
                    expressBtn.textContent = 'Send Expression of Interest →';
                }
            }
        }
    </script>
    <?php endif; ?>

    <!-- Include Reusable Share Modal -->
    <?php include __DIR__ . '/includes/share_company_modal.php'; ?>

</body>
</html>
