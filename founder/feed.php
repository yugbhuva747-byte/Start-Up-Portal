<?php
/**
 * Founder Module: Founder Network & Ecosystem Feed
 * LinkedIn-style social feed, company news, founder updates, and angel co-investing
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Founder Network & Feed';
$flash = get_flash();

$myCompany = null;
$myCompanyFounders = [];

if ($db) {
    // Fetch logged in founder's company
    $mcStmt = $db->prepare("
        SELECT c.*, cf.designation 
        FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ? LIMIT 1
    ");
    $mcStmt->execute([$user['id']]);
    $myCompany = $mcStmt->fetch();
}

// -------------------------------------------------------------
// POST ACTIONS: Create Post, Toggle Like, Add Comment, Angel Interest
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid security token.');
        header('Location: ' . url('founder/feed.php'));
        exit;
    }

    $action = $_POST['form_action'] ?? '';

    // 1. CREATE NEW POST (Just like LinkedIn!)
    if ($action === 'create_post') {
        $postAs = trim($_POST['post_as'] ?? 'company');
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Milestone');
        $metrics = trim($_POST['metrics_summary'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $visibility = 'all_investors';
        $imageUrl = trim($_POST['image_url'] ?? '');

        // Handle uploaded file if present
        if (!empty($_FILES['post_image']['name'])) {
            $uploadRes = handle_file_upload($_FILES['post_image'], 'posts');
            if ($uploadRes['success']) {
                $imageUrl = $uploadRes['url'];
            }
        }

        if (empty($title) || empty($content)) {
            set_flash('error', 'Please provide a title and content for your update.');
        } else {
            $companyId = $myCompany ? (int) $myCompany['id'] : 0;

            // If founder doesn't have a company yet, fallback to dummy or generic company ID 1
            if ($companyId <= 0) {
                $compFallback = $db->query("SELECT id FROM companies LIMIT 1")->fetchColumn();
                $companyId = $compFallback ? (int) $compFallback : 1;
            }

            try {
                $ins = $db->prepare("
                    INSERT INTO startup_updates 
                    (company_id, founder_user_id, title, category, metrics_summary, image_url, content, visibility, post_as)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$companyId, $user['id'], $title, $category, $metrics, $imageUrl, $content, $visibility, $postAs]);

                log_audit($user['id'], 'CREATE_FEED_POST', 'startup_updates', (int) $db->lastInsertId(), "Created post as {$postAs}");
                set_flash('success', 'Your update has been published to the founder & investor network!');
            } catch (Exception $e) {
                set_flash('error', 'Could not post update: ' . $e->getMessage());
            }
        }
        header('Location: ' . url('founder/feed.php'));
        exit;
    }

    // 2. TOGGLE LIKE (AJAX / POST)
    if ($action === 'toggle_like') {
        $updateId = (int) ($_POST['update_id'] ?? 0);
        $isLiked = false;

        if ($updateId > 0) {
            $chk = $db->prepare("SELECT id FROM startup_update_likes WHERE update_id = ? AND user_id = ?");
            $chk->execute([$updateId, $user['id']]);
            $existing = $chk->fetch();

            if ($existing) {
                $del = $db->prepare("DELETE FROM startup_update_likes WHERE id = ?");
                $del->execute([$existing['id']]);
                $isLiked = false;
            } else {
                $ins = $db->prepare("INSERT INTO startup_update_likes (update_id, user_id) VALUES (?, ?)");
                $ins->execute([$updateId, $user['id']]);
                $isLiked = true;
            }

            $cntStmt = $db->prepare("SELECT COUNT(*) FROM startup_update_likes WHERE update_id = ?");
            $cntStmt->execute([$updateId]);
            $newCount = (int) $cntStmt->fetchColumn();

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'liked' => $isLiked, 'count' => $newCount]);
                exit;
            }
        }
        header('Location: ' . url('founder/feed.php#post-' . $updateId));
        exit;
    }

    // 3. ADD COMMENT
    if ($action === 'add_comment') {
        $updateId = (int) ($_POST['update_id'] ?? 0);
        $commentText = trim($_POST['comment'] ?? '');

        if ($updateId > 0 && !empty($commentText)) {
            $ins = $db->prepare("INSERT INTO startup_update_comments (update_id, user_id, comment) VALUES (?, ?, ?)");
            $ins->execute([$updateId, $user['id'], $commentText]);
            set_flash('success', 'Comment posted!');
        }
        header('Location: ' . url('founder/feed.php#post-' . $updateId));
        exit;
    }

    // 4. ANGEL CO-INVESTMENT FROM FEED
    if ($action === 'feed_angel_invest') {
        $targetCompanyId = (int) ($_POST['company_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 100000);
        $ticketType = trim($_POST['investment_type'] ?? 'Direct Angel');
        $note = trim($_POST['message'] ?? '');

        if ($targetCompanyId > 0 && (!$myCompany || $targetCompanyId != $myCompany['id'])) {
            $fullMessage = "Angel Syndicate Ticket: ₹" . number_format($amount) . " (" . htmlspecialchars($ticketType) . ")\nNote: " . $note;

            try {
                // Upsert into investor_interests
                $chk = $db->prepare("SELECT id FROM investor_interests WHERE investor_id = ? AND company_id = ?");
                $chk->execute([$user['id'], $targetCompanyId]);
                $ex = $chk->fetch();

                if ($ex) {
                    $upd = $db->prepare("UPDATE investor_interests SET message = ?, status = 'pending', updated_at = NOW() WHERE id = ?");
                    $upd->execute([$fullMessage, $ex['id']]);
                } else {
                    $ins = $db->prepare("INSERT INTO investor_interests (investor_id, company_id, message, status) VALUES (?, ?, ?, 'pending')");
                    $ins->execute([$user['id'], $targetCompanyId, $fullMessage]);
                }

                // Notify target company founders
                $tfStmt = $db->prepare("SELECT user_id FROM company_founders WHERE company_id = ?");
                $tfStmt->execute([$targetCompanyId]);
                $tfUsers = $tfStmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($tfUsers as $tfId) {
                    send_notification(
                        (int) $tfId,
                        "Founder Angel Interest from " . $user['name'],
                        "Founder {$user['name']} expressed angel investment interest of ₹" . number_format($amount) . " in your startup.",
                        "success",
                        "founder/investor_interests.php"
                    );
                }

                set_flash('success', 'Angel investment interest submitted successfully! The founders have been notified.');
            } catch (Exception $e) {
                set_flash('error', 'Failed to express interest: ' . $e->getMessage());
            }
        }
        header('Location: ' . url('founder/feed.php'));
        exit;
    }
}

// -------------------------------------------------------------
// FETCH FEED STREAM POSTS
// -------------------------------------------------------------
$feedPosts = [];
$selectedFilter = $_GET['filter'] ?? 'all';

if ($db) {
    $filterSql = "";
    $params = [$user['id'], $user['id']];

    if ($selectedFilter === 'milestones') {
        $filterSql = "AND su.category = 'Milestone'";
    } elseif ($selectedFilter === 'funding') {
        $filterSql = "AND su.category IN ('Funding Round', 'Angel Pitch')";
    } elseif ($selectedFilter === 'founder_posts') {
        $filterSql = "AND su.post_as = 'founder'";
    }

    $feedQuery = "
        SELECT su.*, 
               c.name as company_name, c.logo_url as company_logo, c.industry as company_industry, c.stage as company_stage,
               u.id as founder_id, u.name as founder_name, u.avatar_url as founder_avatar,
               cf.designation as founder_designation,
               (SELECT COUNT(*) FROM startup_update_likes WHERE update_id = su.id) as likes_count,
               (SELECT COUNT(*) FROM startup_update_likes WHERE update_id = su.id AND user_id = ?) as user_has_liked,
               (SELECT COUNT(*) FROM startup_update_comments WHERE update_id = su.id) as comments_count
        FROM startup_updates su
        JOIN companies c ON su.company_id = c.id
        JOIN users u ON su.founder_user_id = u.id
        LEFT JOIN company_founders cf ON (cf.company_id = c.id AND cf.user_id = u.id)
        WHERE 1=1 {$filterSql}
        ORDER BY su.created_at DESC
        LIMIT 30
    ";
    $fStmt = $db->prepare($feedQuery);
    $fStmt->execute([$user['id']]);
    $feedPosts = $fStmt->fetchAll();

    // Fetch comments for each post
    foreach ($feedPosts as &$post) {
        $cStmt = $db->prepare("
            SELECT sc.*, u.name as user_name, u.avatar_url as user_avatar, u.role as user_role
            FROM startup_update_comments sc
            JOIN users u ON sc.user_id = u.id
            WHERE sc.update_id = ?
            ORDER BY sc.created_at ASC
            LIMIT 5
        ");
        $cStmt->execute([$post['id']]);
        $post['comments'] = $cStmt->fetchAll();
    }
    unset($post);

    // Fetch Trending Startups
    $trStmt = $db->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM funding_rounds WHERE company_id = c.id AND status IN ('LIVE', 'PARTIALLY_FUNDED')) as has_live_round
        FROM companies c 
        WHERE c.id != ?
        ORDER BY c.created_at DESC 
        LIMIT 4
    ");
    $trStmt->execute([$myCompany['id'] ?? 0]);
    $trendingStartups = $trStmt->fetchAll();

    // Fetch Other Founders in Network
    $ofStmt = $db->prepare("
        SELECT u.*, fp.designation, fp.bio, c.name as company_name, c.id as company_id
        FROM users u
        LEFT JOIN founder_profiles fp ON u.id = fp.user_id
        LEFT JOIN company_founders cf ON u.id = cf.user_id
        LEFT JOIN companies c ON cf.company_id = c.id
        WHERE u.role = 'founder' AND u.id != ?
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT 5
    ");
    $ofStmt->execute([$user['id']]);
    $networkFounders = $ofStmt->fetchAll();

    // Fetch Live Funding Rounds for Angel Co-Investing
    $lrStmt = $db->query("
        SELECT fr.*, c.name as company_name, c.logo_url as company_logo, c.industry, c.city
        FROM funding_rounds fr
        JOIN companies c ON fr.company_id = c.id
        WHERE fr.status IN ('LIVE', 'PARTIALLY_FUNDED')
        ORDER BY fr.amount_raised DESC
        LIMIT 3
    ");
    $liveRounds = $lrStmt ? $lrStmt->fetchAll() : [];
}

$founderInitials = strtoupper(substr($user['name'] ?? 'F', 0, 2));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
</head>

<body
    class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="w-full max-w-[1440px] mx-auto px-3 sm:px-6 md:px-8 py-5">

            <!-- Flash Message -->
            <?php if ($flash): ?>
                <div
                    class="p-4 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800' ?> flex items-center space-x-2.5 shadow-sm mb-4">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>"
                        class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- ======================================================== -->
            <!-- 3-COLUMN LINKEDIN STYLE RESPONSIVE FLEX LAYOUT -->
            <!-- ======================================================== -->
            <div class="flex flex-col lg:flex-row gap-6 items-start">

                <!-- ---------------------------------------------------- -->
                <!-- LEFT COLUMN: Founder & Startup Card - Sticky -->
                <!-- ---------------------------------------------------- -->
                <div class="w-full lg:w-[240px] xl:w-[250px] flex-shrink-0 lg:sticky lg:top-20 lg:self-start space-y-4">

                    <!-- Profile Card -->
                    <div
                        class="rounded-3xl overflow-hidden bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm text-center">
                        <!-- Top Mini Banner -->
                        <div class="h-20 bg-gradient-to-r from-indigo-700 via-indigo-600 to-violet-700 relative">
                            <?php if (!empty($myCompany['banner_url'])): ?>
                                <img src="<?= htmlspecialchars(get_company_banner_url($myCompany['banner_url'])) ?>"
                                    class="w-full h-full object-cover object-center opacity-70">
                            <?php endif; ?>
                        </div>

                        <div class="px-5 pb-5 pt-0 relative">
                            <!-- Avatar -->
                            <div
                                class="relative -mt-10 mx-auto w-20 h-20 rounded-full border-4 border-white dark:border-[#111827] shadow-md overflow-hidden bg-white dark:bg-slate-800">
                                <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '')) ?>"
                                    alt="<?= htmlspecialchars($user['name']) ?>"
                                    class="w-full h-full object-cover object-center">
                            </div>

                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white mt-2.5">
                                <?= htmlspecialchars($user['name']) ?>
                            </h3>
                            <p class="text-xs text-indigo-600 dark:text-indigo-400 font-bold truncate">
                                <?= htmlspecialchars($myCompany['designation'] ?? 'Founder & Executive') ?>
                            </p>

                            <?php if ($myCompany): ?>
                                <div
                                    class="mt-2.5 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-center gap-2">
                                    <img src="<?= htmlspecialchars(get_company_logo_url($myCompany['logo_url'] ?? '')) ?>"
                                        alt="<?= htmlspecialchars($myCompany['name']) ?>"
                                        class="w-5 h-5 rounded-md object-cover object-center">
                                    <span
                                        class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[150px]">
                                        <?= htmlspecialchars($myCompany['name']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Network Stats -->
                            <div
                                class="grid grid-cols-2 gap-2 pt-3.5 mt-3 border-t border-slate-100 dark:border-slate-800 text-left">
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                                    <span
                                        class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Startups</span>
                                    <span
                                        class="text-sm font-black text-slate-900 dark:text-white"><?= count($trendingStartups) + 1 ?></span>
                                </div>
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                                    <span
                                        class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Syndicates</span>
                                    <span
                                        class="text-sm font-black text-indigo-600 dark:text-indigo-400"><?= count($liveRounds) ?>
                                        Live</span>
                                </div>
                            </div>

                            <!-- Quick Profile & Startup Shortcuts -->
                            <div class="pt-3 mt-3 border-t border-slate-100 dark:border-slate-800 space-y-1.5">
                                <a href="<?= url('founder/view.php') ?>"
                                    class="w-full py-1.5 px-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center justify-center gap-1.5 border border-slate-200/60 dark:border-slate-700/60">
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-indigo-500"></i>
                                    <span>My Public Profile</span>
                                </a>
                                <?php if ($myCompany): ?>
                                    <a href="<?= url('founder/company_view.php?id=' . encode_id($myCompany['id'])) ?>"
                                        class="w-full py-1.5 px-3 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition flex items-center justify-center gap-1.5 border border-indigo-200/60 dark:border-indigo-800/60">
                                        <i data-lucide="building-2" class="w-3.5 h-3.5 text-indigo-500"></i>
                                        <span>My Startup Page</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Pill Nav -->
                    <div
                        class="rounded-3xl p-4 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                        <span
                            class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider px-3 py-1 block">Feed
                            Views</span>

                        <a href="<?= url('founder/feed.php?filter=all') ?>"
                            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold transition <?= $selectedFilter === 'all' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                            <span>All Network Activity</span>
                        </a>

                        <a href="<?= url('founder/feed.php?filter=milestones') ?>"
                            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold transition <?= $selectedFilter === 'milestones' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                            <i data-lucide="rocket" class="w-4 h-4 text-emerald-500"></i>
                            <span>Milestones &amp; Traction</span>
                        </a>

                        <a href="<?= url('founder/feed.php?filter=funding') ?>"
                            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold transition <?= $selectedFilter === 'funding' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                            <i data-lucide="circle-dollar-sign" class="w-4 h-4 text-amber-500"></i>
                            <span>Funding &amp; Angel Deals</span>
                        </a>

                        <a href="<?= url('founder/feed.php?filter=founder_posts') ?>"
                            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold transition <?= $selectedFilter === 'founder_posts' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                            <i data-lucide="user-check" class="w-4 h-4 text-indigo-500"></i>
                            <span>Founder Thought Leadership</span>
                        </a>
                    </div>
                </div>

                <!-- ---------------------------------------------------- -->
                <!-- CENTER COLUMN: Wide Feed Stream (Flex-1) -->
                <!-- ---------------------------------------------------- -->
                <div class="flex-1 min-w-0 space-y-5">

                    <!-- LinkedIn Style Create Post Box (Clean without Search Bar) -->
                    <div
                        class="rounded-3xl p-5 sm:p-6 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <div class="flex items-center gap-3.5">
                            <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '')) ?>"
                                alt="<?= htmlspecialchars($user['name']) ?>"
                                class="w-11 h-11 rounded-full object-cover object-center border border-slate-200 dark:border-slate-700 flex-shrink-0">

                            <button type="button" onclick="openPostModal()"
                                class="flex-1 py-3 px-4 rounded-2xl bg-slate-100 hover:bg-slate-200/80 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 text-xs sm:text-sm font-medium text-left transition cursor-pointer flex items-center justify-between">
                                <span>Start a post or share a company milestone...</span>
                                <i data-lucide="edit-2" class="w-4 h-4 text-indigo-500"></i>
                            </button>
                        </div>

                        <!-- Quick Post Action Triggers -->
                        <div
                            class="flex flex-wrap items-center justify-between gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300">
                            <button type="button" onclick="openPostModal('Milestone')"
                                class="flex items-center gap-1.5 py-1.5 px-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-emerald-600 dark:text-emerald-400">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                                <span>Milestone</span>
                            </button>
                            <button type="button" onclick="openPostModal('Funding Round')"
                                class="flex items-center gap-1.5 py-1.5 px-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-amber-600 dark:text-amber-400">
                                <i data-lucide="circle-dollar-sign" class="w-4 h-4"></i>
                                <span>Funding News</span>
                            </button>
                            <button type="button" onclick="openPostModal('Product Launch')"
                                class="flex items-center gap-1.5 py-1.5 px-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-indigo-600 dark:text-indigo-400">
                                <i data-lucide="rocket" class="w-4 h-4"></i>
                                <span>Product</span>
                            </button>
                            <button type="button" onclick="openPostModal('Hiring')"
                                class="flex items-center gap-1.5 py-1.5 px-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-blue-600 dark:text-blue-400">
                                <i data-lucide="briefcase" class="w-4 h-4"></i>
                                <span>Hiring</span>
                            </button>
                        </div>
                    </div>

                    <!-- ================================================ -->
                    <!-- FEED POSTS STREAM -->
                    <!-- ================================================ -->
                    <?php if (empty($feedPosts)): ?>
                        <div
                            class="rounded-3xl p-12 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 text-center space-y-3">
                            <i data-lucide="newspaper" class="w-12 h-12 text-slate-300 mx-auto"></i>
                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">No updates found in this view
                            </h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto">Be the first to share an update, milestone,
                                or angel co-investment pitch with the network!</p>
                            <button type="button" onclick="openPostModal()"
                                class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold text-xs shadow-md">
                                Create First Post
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($feedPosts as $p):
                            $isPostCompany = ($p['post_as'] ?? 'company') === 'company';
                            $authorTitle = $isPostCompany ? ($p['company_name'] ?? 'Company') : ($p['founder_name'] ?? 'Founder');
                            $authorSubtitle = $isPostCompany
                                ? "Posted by " . htmlspecialchars($p['founder_name'] ?? 'Founder') . " • " . time_ago($p['created_at'] ?? 'now')
                                : htmlspecialchars($p['founder_designation'] ?? 'Founder') . " at " . htmlspecialchars($p['company_name'] ?? 'Startup') . " • " . time_ago($p['created_at'] ?? 'now');
                            $avatarSrc = $isPostCompany
                                ? get_company_logo_url($p['company_logo'] ?? '')
                                : get_avatar_url($p['founder_avatar'] ?? '');
                            $isMyPost = (($p['founder_id'] ?? 0) == $user['id']);
                            ?>
                            <!-- Single LinkedIn Style Post Card -->
                            <article id="post-<?= $p['id'] ?>"
                                class="rounded-3xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden space-y-4 p-5 sm:p-6 transition hover:border-slate-300 dark:hover:border-slate-700">

                                <!-- Post Author Header -->
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <!-- Author Avatar or Logo -->
                                        <a href="<?= $isPostCompany ? url('founder/company_view.php?id=' . encode_id($p['company_id'])) : url('founder/view.php?id=' . encode_id($p['founder_id'])) ?>"
                                            class="flex-shrink-0">
                                            <img src="<?= htmlspecialchars($avatarSrc) ?>"
                                                alt="<?= htmlspecialchars($authorTitle) ?>"
                                                class="w-12 h-12 <?= $isPostCompany ? 'rounded-2xl border' : 'rounded-full border-2' ?> border-slate-200 dark:border-slate-700 object-cover object-center shadow-xs hover:scale-105 transition">
                                        </a>

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <a href="<?= $isPostCompany ? url('founder/company_view.php?id=' . encode_id($p['company_id'])) : url('founder/view.php?id=' . encode_id($p['founder_id'])) ?>"
                                                    class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition truncate">
                                                    <?= htmlspecialchars($authorTitle) ?>
                                                </a>
                                                <i data-lucide="badge-check" class="w-4 h-4 text-indigo-600 flex-shrink-0"></i>
                                            </div>
                                            <p class="text-[11.5px] text-slate-500 dark:text-slate-400 truncate">
                                                <?= $authorSubtitle ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Category Badge -->
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/80 flex-shrink-0">
                                        <?= htmlspecialchars($p['category']) ?>
                                    </span>
                                </div>

                                <!-- Post Headline & Content -->
                                <div class="space-y-2.5">
                                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white leading-snug">
                                        <?= htmlspecialchars($p['title']) ?>
                                    </h3>

                                    <p
                                        class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-normal whitespace-pre-line">
                                        <?= htmlspecialchars($p['content']) ?>
                                    </p>

                                    <!-- Traction Metrics Pill -->
                                    <?php if (!empty($p['metrics_summary'])): ?>
                                        <div
                                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-bold">
                                            <i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            <span><?= htmlspecialchars($p['metrics_summary']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Post Media Image (If Provided) -->
                                <?php if (!empty($p['image_url'])): ?>
                                    <div
                                        class="rounded-2xl overflow-hidden max-h-96 border border-slate-100 dark:border-slate-800 bg-slate-900">
                                        <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="Post image"
                                            class="w-full h-full max-h-96 object-cover object-center hover:scale-101 transition duration-500">
                                    </div>
                                <?php endif; ?>

                                <!-- Post Interaction Counter Bar -->
                                <div
                                    class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800/80 font-medium">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center text-[10px]">❤️</span>
                                        <span id="likes-count-<?= $p['id'] ?>"><?= (int) $p['likes_count'] ?> likes</span>
                                    </div>
                                    <div>
                                        <span><?= (int) $p['comments_count'] ?> comments</span>
                                    </div>
                                </div>

                                <!-- Interactive Action Buttons (Like, Comment, View Deal Room, Angel Invest) -->
                                <div
                                    class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                                    <!-- Like Button -->
                                    <form method="POST" action="<?= url('founder/feed.php') ?>" class="w-full"
                                        onsubmit="handleLikeAjax(event, <?= $p['id'] ?>)">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="form_action" value="toggle_like">
                                        <input type="hidden" name="update_id" value="<?= $p['id'] ?>">

                                        <button type="submit" id="like-btn-<?= $p['id'] ?>"
                                            class="w-full py-2 px-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer <?= $p['user_has_liked'] ? 'text-rose-600 bg-rose-50 dark:bg-rose-950/40' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                                            <i data-lucide="heart"
                                                class="w-4 h-4 <?= $p['user_has_liked'] ? 'fill-rose-500 text-rose-500' : '' ?>"></i>
                                            <span><?= $p['user_has_liked'] ? 'Liked' : 'Like' ?></span>
                                        </button>
                                    </form>

                                    <!-- Comment Toggle -->
                                    <button type="button" onclick="toggleCommentsSection(<?= $p['id'] ?>)"
                                        class="w-full py-2 px-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                                        <span>Comment</span>
                                    </button>

                                    <!-- View Company Deal Room -->
                                    <a href="<?= url('founder/company_view.php?id=' . encode_id($p['company_id'])) ?>"
                                        class="w-full py-2 px-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex items-center justify-center gap-1.5">
                                        <i data-lucide="building-2" class="w-4 h-4 text-indigo-500"></i>
                                        <span>Company</span>
                                    </a>

                                    <!-- Angel Invest CTA -->
                                    <?php if (!$isMyPost && (!$myCompany || $p['company_id'] != $myCompany['id'])): ?>
                                        <button type="button"
                                            onclick="triggerFeedAngelModal(<?= $p['company_id'] ?>, '<?= htmlspecialchars(addslashes($p['company_name'])) ?>')"
                                            class="w-full py-2 px-2 rounded-xl text-xs font-black bg-amber-50 dark:bg-amber-950/50 hover:bg-amber-100 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 transition flex items-center justify-center gap-1 cursor-pointer">
                                            <i data-lucide="coins" class="w-3.5 h-3.5 text-amber-600"></i>
                                            <span>Invest</span>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= url('founder/company.php') ?>"
                                            class="w-full py-2 px-2 rounded-xl text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition flex items-center justify-center gap-1">
                                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                            <span>Your Post</span>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Collapsible Comments Drawer -->
                                <div id="comments-drawer-<?= $p['id'] ?>"
                                    class="hidden pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                    <!-- Comment Form -->
                                    <form method="POST" action="<?= url('founder/feed.php') ?>" class="flex items-center gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="form_action" value="add_comment">
                                        <input type="hidden" name="update_id" value="<?= $p['id'] ?>">

                                        <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '')) ?>"
                                            class="w-8 h-8 rounded-full object-cover object-center flex-shrink-0">

                                        <input type="text" name="comment" required
                                            placeholder="Add a founder comment or congratulation..."
                                            class="flex-1 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm text-slate-800 dark:text-slate-200">

                                        <button type="submit"
                                            class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs">
                                            Post
                                        </button>
                                    </form>

                                    <!-- Comments List -->
                                    <?php if (!empty($p['comments'])): ?>
                                        <div class="space-y-2.5 pt-2">
                                            <?php foreach ($p['comments'] as $comm): ?>
                                                <div
                                                    class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 flex items-start gap-2.5">
                                                    <img src="<?= htmlspecialchars(get_avatar_url($comm['user_avatar'] ?? '')) ?>"
                                                        class="w-7 h-7 rounded-full object-cover object-center flex-shrink-0 mt-0.5">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center justify-between">
                                                            <span
                                                                class="text-xs font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($comm['user_name'] ?? 'User') ?></span>
                                                            <span
                                                                class="text-[10px] text-slate-400"><?= time_ago($comm['created_at'] ?? 'now') ?></span>
                                                        </div>
                                                        <p class="text-xs text-slate-700 dark:text-slate-300 mt-0.5">
                                                            <?= htmlspecialchars($comm['comment']) ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- ---------------------------------------------------- -->
                <!-- RIGHT COLUMN: Trending Startups & Network - Sticky -->
                <!-- ---------------------------------------------------- -->
                <div class="w-full lg:w-[280px] xl:w-[300px] flex-shrink-0 lg:sticky lg:top-20 lg:self-start space-y-5">

                    <!-- Live Angel Syndicate Rounds (Placed at Top) -->
                    <?php if (!empty($liveRounds)): ?>
                        <div
                            class="rounded-3xl p-5 bg-gradient-to-br from-amber-500/10 via-indigo-500/5 to-transparent border border-amber-500/30 dark:border-amber-500/20 shadow-sm space-y-3.5">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                <h3 class="text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                    Live Angel Syndicates</h3>
                            </div>

                            <div class="space-y-3">
                                <?php foreach ($liveRounds as $lr):
                                    $pct = $lr['target_amount'] > 0 ? min(100, round(($lr['amount_raised'] / $lr['target_amount']) * 100)) : 0;
                                    ?>
                                    <div
                                        class="p-3 rounded-2xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-700 space-y-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span
                                                class="font-extrabold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($lr['company_name']) ?></span>
                                            <span class="font-black text-emerald-600 dark:text-emerald-400"><?= $pct ?>%</span>
                                        </div>
                                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-gradient-to-r from-amber-500 to-emerald-500 h-full rounded-full"
                                                style="width: <?= $pct ?>%"></div>
                                        </div>
                                        <div class="flex items-center justify-between text-[11px] text-slate-500">
                                            <span>Target: ₹<?= number_format($lr['target_amount'] / 100000, 1) ?>L</span>
                                            <button type="button"
                                                onclick="triggerFeedAngelModal(<?= $lr['company_id'] ?>, '<?= htmlspecialchars(addslashes($lr['company_name'])) ?>')"
                                                class="text-indigo-600 font-bold hover:underline cursor-pointer">
                                                Co-Invest
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Trending Startups to Explore -->
                    <div
                        class="rounded-3xl p-5 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <div
                            class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <i data-lucide="compass" class="w-4 h-4 text-indigo-600"></i>
                                <span>Discover Startups</span>
                            </h3>
                            <span class="text-[11px] text-indigo-600 font-bold"><?= count($trendingStartups) ?>
                                Live</span>
                        </div>

                        <div class="space-y-3.5">
                            <?php foreach ($trendingStartups as $ts): ?>
                                <div
                                    class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 space-y-2">
                                    <div class="flex items-center gap-3">
                                        <img src="<?= htmlspecialchars(get_company_logo_url($ts['logo_url'] ?? '')) ?>"
                                            alt="<?= htmlspecialchars($ts['name']) ?>"
                                            class="w-10 h-10 rounded-xl object-cover object-center border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                        <div class="min-w-0 flex-1">
                                            <a href="<?= url('founder/company_view.php?id=' . encode_id($ts['id'])) ?>"
                                                class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 block truncate">
                                                <?= htmlspecialchars($ts['name']) ?>
                                            </a>
                                            <div class="text-[11px] text-slate-500 truncate">
                                                <?= htmlspecialchars($ts['industry'] ?? 'Tech') ?> •
                                                <?= htmlspecialchars($ts['city'] ?? 'India') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between pt-1 text-xs">
                                        <a href="<?= url('founder/company_view.php?id=' . encode_id($ts['id'])) ?>"
                                            class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline flex items-center gap-1">
                                            <span>Deal Room</span>
                                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                        </a>

                                        <button type="button"
                                            onclick="triggerFeedAngelModal(<?= $ts['id'] ?>, '<?= htmlspecialchars(addslashes($ts['name'])) ?>')"
                                            class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-[10.5px] uppercase tracking-wider cursor-pointer">
                                            Invest
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Top Founders to Network With -->
                    <div
                        class="rounded-3xl p-5 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <div
                            class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <i data-lucide="users" class="w-4 h-4 text-indigo-600"></i>
                                <span>Peer Founders</span>
                            </h3>
                            <span class="text-[11px] text-slate-400">Network</span>
                        </div>

                        <div class="space-y-3">
                            <?php foreach ($networkFounders as $nf): ?>
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <img src="<?= htmlspecialchars(get_avatar_url($nf['avatar_url'] ?? '')) ?>"
                                            alt="<?= htmlspecialchars($nf['name']) ?>"
                                            class="w-9 h-9 rounded-full object-cover object-center border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                        <div class="min-w-0">
                                            <a href="<?= url('founder/view.php?id=' . encode_id($nf['id'])) ?>"
                                                class="text-xs font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 truncate block">
                                                <?= htmlspecialchars($nf['name']) ?>
                                            </a>
                                            <div class="text-[10.5px] text-slate-500 truncate">
                                                <?= htmlspecialchars($nf['company_name'] ?? 'Startup Founder') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="<?= url('founder/view.php?id=' . encode_id($nf['id'])) ?>"
                                        class="px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-indigo-400 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex-shrink-0">
                                        View
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ======================================================== -->
    <!-- LINKEDIN STYLE CREATE POST MODAL -->
    <!-- ======================================================== -->
    <div id="create-post-modal"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div
            class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full border border-slate-200 dark:border-slate-800 shadow-2xl p-6 sm:p-7 space-y-5 animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="edit" class="w-5 h-5 text-indigo-600"></i>
                    <span>Create Network Post</span>
                </h3>
                <button type="button" onclick="closePostModal()"
                    class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="<?= url('founder/feed.php') ?>" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="form_action" value="create_post">

                <!-- POST AS TOGGLE (Choose Founder or Company) -->
                <div>
                    <label
                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Publish
                        As Identity</label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <label
                            class="p-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 has-checked:border-indigo-600 has-checked:bg-indigo-50/60 dark:has-checked:bg-indigo-950/40 flex items-center gap-3 cursor-pointer transition">
                            <input type="radio" name="post_as" value="founder" class="text-indigo-600">
                            <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '')) ?>"
                                class="w-8 h-8 rounded-full object-cover object-center flex-shrink-0">
                            <div class="min-w-0">
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-white block truncate"><?= htmlspecialchars($user['name']) ?></span>
                                <span class="text-[10px] text-slate-500">Personal Founder</span>
                            </div>
                        </label>

                        <?php if ($myCompany): ?>
                            <label
                                class="p-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 has-checked:border-indigo-600 has-checked:bg-indigo-50/60 dark:has-checked:bg-indigo-950/40 flex items-center gap-3 cursor-pointer transition">
                                <input type="radio" name="post_as" value="company" checked class="text-indigo-600">
                                <img src="<?= htmlspecialchars(get_company_logo_url($myCompany['logo_url'] ?? '')) ?>"
                                    class="w-8 h-8 rounded-lg object-cover object-center flex-shrink-0">
                                <div class="min-w-0">
                                    <span
                                        class="text-xs font-bold text-slate-900 dark:text-white block truncate"><?= htmlspecialchars($myCompany['name']) ?></span>
                                    <span class="text-[10px] text-slate-500">Official Company</span>
                                </div>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Post Category -->
                <div>
                    <label
                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Update
                        Type</label>
                    <select name="category" id="post-category-select"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-semibold bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                        <option value="Milestone">🚀 Major Traction Milestone</option>
                        <option value="Product Launch">✨ Product Launch &amp; Feature</option>
                        <option value="Funding Round">💰 Funding Round &amp; Angel Pitch</option>
                        <option value="Partnership">🤝 Strategic Partnership / Customer Win</option>
                        <option value="Hiring">💼 Executive / Tech Hiring</option>
                        <option value="News Update">📢 General Company Announcement</option>
                    </select>
                </div>

                <!-- Headline / Title -->
                <div>
                    <label
                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Post
                        Headline</label>
                    <input type="text" name="title" required
                        placeholder="e.g. SolarisPulse completes 50k battery swaps across Delhi-NCR!"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <!-- Metrics Summary (Optional) -->
                <div>
                    <label
                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Key
                        Metrics Badge (Optional)</label>
                    <input type="text" name="metrics_summary" placeholder="e.g. ARR: ₹2.4 Cr • MoM: +35% • 18k Fleets"
                        class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <!-- Content Textarea -->
                <div>
                    <label
                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Story
                        / Announcement</label>
                    <textarea name="content" rows="4" required
                        placeholder="Share the context, what your team accomplished, lessons learned, and what's next..."
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 leading-relaxed"></textarea>
                </div>

                <!-- Image Attachment -->
                <div>
                    <label
                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Attach
                        Photo / Graphic (Optional)</label>
                    <div class="flex items-center gap-3">
                        <label for="post-img-file"
                            class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-200 dark:border-slate-700 cursor-pointer flex items-center gap-1.5">
                            <i data-lucide="image" class="w-4 h-4 text-indigo-500"></i>
                            <span>Choose Image</span>
                        </label>
                        <input type="file" id="post-img-file" name="post_image" accept="image/*" class="hidden"
                            onchange="previewPostFile(this)">
                        <span id="post-img-filename" class="text-[11px] text-slate-400">No file selected</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closePostModal()"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition active:scale-95 cursor-pointer">
                        Publish to Network
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- ANGEL INVEST / EXPRESS INTEREST MODAL (Triggered from Feed) -->
    <!-- ======================================================== -->
    <div id="feed-angel-modal"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div
            class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full border border-slate-200 dark:border-slate-800 shadow-2xl p-6 sm:p-7 space-y-5 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 flex items-center justify-center font-bold">
                        <i data-lucide="coins" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Founder Angel Co-Investment
                        </h3>
                        <p class="text-xs text-slate-500">Back <span id="angel-modal-company-name"
                                class="font-bold text-indigo-600">Company</span> as an angel</p>
                    </div>
                </div>
                <button type="button" onclick="closeFeedAngelModal()"
                    class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="<?= url('founder/feed.php') ?>" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="form_action" value="feed_angel_invest">
                <input type="hidden" name="company_id" id="angel-modal-company-id" value="0">

                <!-- Ticket Size Options -->
                <div>
                    <label
                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Select
                        Proposed Ticket Size (₹)</label>
                    <div class="grid grid-cols-3 gap-2">
                        <?php foreach ([50000, 100000, 250000, 500000, 1000000, 2500000] as $amt): ?>
                            <button type="button" onclick="setFeedTicketAmount(<?= $amt ?>)"
                                class="ticket-btn py-2 px-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-500 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition text-center cursor-pointer">
                                ₹<?= $amt >= 100000 ? ($amt / 100000) . ' Lakh' . ($amt > 100000 ? 's' : '') : ($amt / 1000) . 'k' ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-2.5">
                        <input type="number" name="amount" id="feed-ticket-input" value="100000" min="10000"
                            step="10000"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-black bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            placeholder="Custom Amount in INR">
                    </div>
                </div>

                <!-- Investment Type -->
                <div>
                    <label
                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Syndicate
                        Role / Type</label>
                    <select name="investment_type"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-semibold bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                        <option value="Direct Angel Ticket">Direct Angel Ticket (Cash + Equity)</option>
                        <option value="Syndicate Co-Investment">Syndicate Co-Investment (Lead / Follower)</option>
                        <option value="Strategic Founder Advisory + Capital">Strategic Founder Advisory + Capital
                        </option>
                    </select>
                </div>

                <!-- Note to Founder -->
                <div>
                    <label
                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Note
                        to Founder Leadership</label>
                    <textarea name="message" rows="3"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200"
                        placeholder="e.g. Great progress! Would love to participate in this round and offer strategic founder support..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeFeedAngelModal()"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition active:scale-95 cursor-pointer">
                        Send Angel Interest
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function openPostModal(category = '') {
            const modal = document.getElementById('create-post-modal');
            if (modal) modal.classList.remove('hidden');
            if (category) {
                const sel = document.getElementById('post-category-select');
                if (sel) sel.value = category;
            }
        }
        function closePostModal() {
            document.getElementById('create-post-modal')?.classList.add('hidden');
        }
        function previewPostFile(input) {
            const label = document.getElementById('post-img-filename');
            if (input.files && input.files[0]) {
                label.textContent = input.files[0].name;
                label.className = 'text-[11px] text-emerald-600 font-bold';
            }
        }

        function toggleCommentsSection(postId) {
            const drawer = document.getElementById('comments-drawer-' + postId);
            if (drawer) {
                drawer.classList.toggle('hidden');
            }
        }

        function triggerFeedAngelModal(companyId, companyName) {
            document.getElementById('angel-modal-company-id').value = companyId;
            document.getElementById('angel-modal-company-name').textContent = companyName;
            document.getElementById('feed-angel-modal')?.classList.remove('hidden');
        }
        function closeFeedAngelModal() {
            document.getElementById('feed-angel-modal')?.classList.add('hidden');
        }
        function setFeedTicketAmount(amt) {
            const input = document.getElementById('feed-ticket-input');
            if (input) input.value = amt;
        }

        function handleLikeAjax(e, postId) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            fetch('<?= url("founder/feed.php") ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const btn = document.getElementById('like-btn-' + postId);
                        const countEl = document.getElementById('likes-count-' + postId);
                        if (countEl) countEl.textContent = data.count + ' likes';
                        if (btn) {
                            if (data.liked) {
                                btn.className = 'w-full py-2 px-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer text-rose-600 bg-rose-50 dark:bg-rose-950/40';
                                btn.innerHTML = '<i data-lucide="heart" class="w-4 h-4 fill-rose-500 text-rose-500"></i><span>Liked</span>';
                            } else {
                                btn.className = 'w-full py-2 px-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800';
                                btn.innerHTML = '<i data-lucide="heart" class="w-4 h-4"></i><span>Like</span>';
                            }
                            if (window.lucide) lucide.createIcons();
                        }
                    }
                })
                .catch(() => {
                    form.submit();
                });
        }

        function filterFeedPosts(query) {
            const q = query.trim().toLowerCase();
            const articles = document.querySelectorAll('article[id^="post-"]');
            const clearBtn = document.getElementById('clear-feed-search');
            const countBadge = document.getElementById('search-match-count');
            const noResults = document.getElementById('search-no-results-box');

            if (q === '') {
                articles.forEach(a => a.style.display = '');
                if (clearBtn) clearBtn.classList.add('hidden');
                if (countBadge) countBadge.classList.add('hidden');
                if (noResults) noResults.classList.add('hidden');
                return;
            }

            if (clearBtn) clearBtn.classList.remove('hidden');

            let matched = 0;
            articles.forEach(a => {
                const text = a.textContent.toLowerCase();
                if (text.includes(q)) {
                    a.style.display = '';
                    matched++;
                } else {
                    a.style.display = 'none';
                }
            });

            if (countBadge) {
                countBadge.textContent = matched + ' found';
                countBadge.classList.remove('hidden');
            }

            if (noResults) {
                if (matched === 0) {
                    noResults.classList.remove('hidden');
                    const termEl = document.getElementById('search-term-display');
                    if (termEl) termEl.textContent = query;
                } else {
                    noResults.classList.add('hidden');
                }
            }
        }

        function clearFeedSearch() {
            const input = document.getElementById('feed-search-input');
            if (input) {
                input.value = '';
                filterFeedPosts('');
                input.focus();
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closePostModal();
                closeFeedAngelModal();
            }
        });
    </script>
</body>

</html>