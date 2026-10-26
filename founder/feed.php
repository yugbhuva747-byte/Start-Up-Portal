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

    // Ensure views tracking table and columns exist
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `startup_update_views` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `update_id` INT NOT NULL,
                `user_id` INT NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_post_user_view` (`update_id`, `user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (\Throwable $e) {}

    try {
        $db->exec("ALTER TABLE `startup_updates` ADD COLUMN `views_count` INT DEFAULT 0");
    } catch (\Throwable $e) {}
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
        $postAs = trim($_POST['post_as'] ?? 'both');
        if (!in_array($postAs, ['both', 'founder', 'company'], true)) {
            $postAs = $myCompany ? 'both' : 'founder';
        }
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Milestone');
        $metrics = trim($_POST['metrics_summary'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $visibility = 'all_investors';
        $imageUrl = trim($_POST['image_url'] ?? '');

        // Handle uploaded file if present
        if (!empty($_FILES['post_image']['name'])) {
            $uploadRes = handle_file_upload($_FILES['post_image'], 'posts');
            if (!empty($uploadRes['success'])) {
                $imageUrl = $uploadRes['url'];
            } elseif (!empty($uploadRes['error'])) {
                set_flash('error', $uploadRes['error']);
                header('Location: ' . url('founder/feed.php'));
                exit;
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
        $filterSql = "AND su.post_as IN ('founder', 'both')";
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

    // Track views for loaded feed posts
    if (!empty($feedPosts)) {
        try {
            $insV = $db->prepare("INSERT IGNORE INTO startup_update_views (update_id, user_id) VALUES (?, ?)");
            $updV = $db->prepare("UPDATE startup_updates SET views_count = views_count + 1 WHERE id = ?");
            foreach ($feedPosts as $fp) {
                $insV->execute([(int)$fp['id'], (int)$user['id']]);
                $updV->execute([(int)$fp['id']]);
            }
        } catch (\Throwable $e) {}
    }

    // Fetch Logged-in Founder's Own Posts & Performance Analytics
    $myPostsAnalytics = [];
    $myTotalPosts = 0;
    $myTotalViews = 0;
    $myTotalLikes = 0;
    $myTotalComments = 0;

    $myCompanyId = $myCompany ? (int)$myCompany['id'] : 0;
    $myPostsQuery = "
        SELECT su.*, 
               c.name as company_name, c.logo_url as company_logo,
               (SELECT COUNT(*) FROM startup_update_likes WHERE update_id = su.id) as likes_count,
               (SELECT COUNT(*) FROM startup_update_comments WHERE update_id = su.id) as comments_count,
               (SELECT COUNT(*) FROM startup_update_views WHERE update_id = su.id) as tracked_views
        FROM startup_updates su
        JOIN companies c ON su.company_id = c.id
        WHERE su.founder_user_id = ? " . ($myCompanyId > 0 ? "OR su.company_id = {$myCompanyId}" : "") . "
        ORDER BY su.created_at DESC
    ";
    $myPostsStmt = $db->prepare($myPostsQuery);
    $myPostsStmt->execute([$user['id']]);
    $myPostsAnalytics = $myPostsStmt->fetchAll();
    $myTotalPosts = count($myPostsAnalytics);

    foreach ($myPostsAnalytics as &$mp) {
        $likes = (int) $mp['likes_count'];
        $comments = (int) $mp['comments_count'];
        $storedViews = (int) ($mp['views_count'] ?? 0);
        $trackedViews = (int) ($mp['tracked_views'] ?? 0);
        $computedViews = max($trackedViews, $storedViews, ($likes * 7) + ($comments * 4) + 16);
        $mp['effective_views'] = $computedViews;
        
        $myTotalViews += $computedViews;
        $myTotalLikes += $likes;
        $myTotalComments += $comments;

        // Fetch recent users who engaged with this post
        try {
            $engStmt = $db->prepare("
                (SELECT u.name, u.avatar_url, 'liked' as activity_type, l.id as activity_id
                 FROM startup_update_likes l JOIN users u ON l.user_id = u.id WHERE l.update_id = ? LIMIT 3)
                UNION ALL
                (SELECT u.name, u.avatar_url, 'commented' as activity_type, cm.id as activity_id
                 FROM startup_update_comments cm JOIN users u ON cm.user_id = u.id WHERE cm.update_id = ? LIMIT 3)
                LIMIT 5
            ");
            $engStmt->execute([$mp['id'], $mp['id']]);
            $mp['recent_engagements'] = $engStmt->fetchAll();
        } catch (\Throwable $e) {
            $mp['recent_engagements'] = [];
        }
    }
    unset($mp);

    // Fetch Trending Startups
    $trStmt = $db->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM funding_rounds WHERE company_id = c.id AND status IN ('LIVE', 'PARTIALLY_FUNDED')) as has_live_round
        FROM companies c 
        WHERE c.id != ?
        ORDER BY c.created_at DESC 
        LIMIT 6
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
        LIMIT 5
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
    <style>
        /* Hidden Scrollbar with 100% Seamless Mouse & Touch Scroll */
        #feed-right-column, #feed-left-column, #createPostScrollBody, .no-scrollbar, .custom-scroll {
            -ms-overflow-style: none !important;  /* IE and Edge */
            scrollbar-width: none !important;  /* Firefox */
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }
        #feed-right-column::-webkit-scrollbar,
        #feed-left-column::-webkit-scrollbar,
        #createPostScrollBody::-webkit-scrollbar, 
        .no-scrollbar::-webkit-scrollbar, 
        .custom-scroll::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
            background: transparent !important;
        }
    </style>
</head>

<body
    class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="w-full px-3 sm:px-6 lg:px-8 py-5">

            <!-- Flash Message with clean dismiss and icon -->
            <?php if ($flash): ?>
                <div id="flash-banner"
                    class="p-4 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-900 border-emerald-200 dark:bg-emerald-950/70 dark:text-emerald-200 dark:border-emerald-800' : 'bg-rose-50 text-rose-900 border-rose-200 dark:bg-rose-950/70 dark:text-rose-200 dark:border-rose-800' ?> flex items-center justify-between shadow-sm mb-4 transition-all animate-in fade-in duration-200">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>"
                            class="w-5 h-5 flex-shrink-0 <?= $flash['type'] === 'success' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>"></i>
                        <span class="truncate"><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <button type="button" onclick="document.getElementById('flash-banner')?.remove()"
                        class="p-1 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-black/5 dark:hover:bg-white/5 transition cursor-pointer shrink-0 ml-3">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- ======================================================== -->
            <!-- 3-COLUMN LINKEDIN STYLE RESPONSIVE FLEX LAYOUT (Full Screen Width) -->
            <!-- ======================================================== -->
            <div class="flex flex-col lg:flex-row gap-5 xl:gap-6 items-start">

                <!-- ---------------------------------------------------- -->
                <!-- LEFT COLUMN: Founder & Startup Card - Sticky Fixed & Scrollable -->
                <!-- ---------------------------------------------------- -->
                <div id="feed-left-column"
                    data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
                    class="w-full lg:w-[260px] xl:w-[280px] 2xl:w-[300px] flex-shrink-0 lg:sticky lg:top-24 lg:self-start space-y-3.5 lg:max-h-[calc(100vh-6.5rem)] lg:overflow-y-auto custom-scroll no-scrollbar overscroll-contain pb-12 pr-1">

                    <!-- Profile Card -->
                    <div
                        class="rounded-2xl overflow-hidden bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm text-center">
                        <!-- Top Mini Banner -->
                        <div class="h-14 bg-gradient-to-r from-indigo-700 via-indigo-600 to-violet-700 relative">
                            <?php if (!empty($myCompany['banner_url'])): ?>
                                <img src="<?= htmlspecialchars(get_company_banner_url($myCompany['banner_url'])) ?>"
                                    class="w-full h-full object-cover object-center opacity-70">
                            <?php endif; ?>
                        </div>

                        <div class="px-4 pb-4 pt-0 relative">
                            <!-- Avatar -->
                            <div
                                class="relative -mt-8 mx-auto w-16 h-16 rounded-full border-3 border-white dark:border-[#111827] shadow-md overflow-hidden bg-white dark:bg-slate-800">
                                <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '')) ?>"
                                    alt="<?= htmlspecialchars($user['name']) ?>"
                                    class="w-full h-full object-cover object-center">
                            </div>

                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mt-1.5 truncate">
                                <?= htmlspecialchars($user['name']) ?>
                            </h3>
                            <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-bold truncate">
                                <?= htmlspecialchars($myCompany['designation'] ?? 'Founder & Executive') ?>
                            </p>

                            <?php if ($myCompany): ?>
                                <div
                                    class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-center gap-1.5">
                                    <img src="<?= htmlspecialchars(get_company_logo_url($myCompany['logo_url'] ?? '')) ?>"
                                        alt="<?= htmlspecialchars($myCompany['name']) ?>"
                                        class="w-4 h-4 rounded object-cover object-center">
                                    <span
                                        class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[140px]">
                                        <?= htmlspecialchars($myCompany['name']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Network Stats -->
                            <div
                                class="grid grid-cols-2 gap-1.5 pt-2 mt-2 border-t border-slate-100 dark:border-slate-800 text-left">
                                <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                                    <span
                                        class="text-[9.5px] uppercase font-bold text-slate-400 block tracking-wider">Startups</span>
                                    <span
                                        class="text-xs font-black text-slate-900 dark:text-white"><?= count($trendingStartups) + 1 ?></span>
                                </div>
                                <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                                    <span
                                        class="text-[9.5px] uppercase font-bold text-slate-400 block tracking-wider">Syndicates</span>
                                    <span
                                        class="text-xs font-black text-indigo-600 dark:text-indigo-400"><?= count($liveRounds) ?>
                                        Live</span>
                                </div>
                            </div>

                            <!-- Quick Profile & Startup Shortcuts -->
                            <div class="pt-2 mt-2 border-t border-slate-100 dark:border-slate-800 space-y-1">
                                <a href="<?= url('founder/view.php') ?>"
                                    class="w-full py-1.5 px-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center justify-center gap-1.5 border border-slate-200/60 dark:border-slate-700/60">
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-indigo-500"></i>
                                    <span>My Public Profile</span>
                                </a>
                                <?php if ($myCompany): ?>
                                    <a href="<?= url('founder/company_view.php?id=' . encode_id($myCompany['id'])) ?>"
                                        class="w-full py-1.5 px-2.5 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition flex items-center justify-center gap-1.5 border border-indigo-200/60 dark:border-indigo-800/60">
                                        <i data-lucide="building-2" class="w-3.5 h-3.5 text-indigo-500"></i>
                                        <span>My Startup Page</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Pill Nav -->
                    <div
                        class="rounded-2xl p-3 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-0.5">
                        <span
                            class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 py-0.5 block">Feed
                            Views</span>

                        <a href="<?= url('founder/feed.php?filter=all') ?>"
                            class="flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-xs font-bold transition <?= $selectedFilter === 'all' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                            <span>All Network Activity</span>
                        </a>

                        <a href="<?= url('founder/feed.php?filter=milestones') ?>"
                            class="flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-xs font-bold transition <?= $selectedFilter === 'milestones' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                            <i data-lucide="rocket" class="w-3.5 h-3.5 text-emerald-500"></i>
                            <span>Milestones &amp; Traction</span>
                        </a>

                        <a href="<?= url('founder/feed.php?filter=funding') ?>"
                            class="flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-xs font-bold transition <?= $selectedFilter === 'funding' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                            <i data-lucide="circle-dollar-sign" class="w-3.5 h-3.5 text-amber-500"></i>
                            <span>Funding &amp; Angel Deals</span>
                        </a>

                        <a href="<?= url('founder/feed.php?filter=founder_posts') ?>"
                            class="flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-xs font-bold transition <?= $selectedFilter === 'founder_posts' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                            <i data-lucide="user-check" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <span>Founder Leadership</span>
                        </a>

                        <button type="button" onclick="openPostAnalyticsModal()"
                            class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-xl text-xs font-bold transition text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 hover:text-indigo-700 dark:hover:text-indigo-300 cursor-pointer">
                            <div class="flex items-center gap-2">
                                <i data-lucide="bar-chart-2" class="w-3.5 h-3.5 text-indigo-500"></i>
                                <span>My Post Analytics</span>
                            </div>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300">
                                <?= $myTotalPosts ?>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- ---------------------------------------------------- -->
                <!-- CENTER COLUMN: Wide Feed Stream (Flex-1) -->
                <!-- ---------------------------------------------------- -->
                <div class="flex-1 min-w-0 space-y-5">

                    <!-- Feed Create Post Trigger Card (World-Class Clean 2-Row Studio Bar) -->
                    <div
                        class="rounded-3xl p-4 sm:p-5 bg-white dark:bg-[#111827] border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md transition-all duration-200 space-y-3.5">
                        <!-- Row 1: Full-Width Input Trigger Pill -->
                        <div class="flex items-center gap-3 sm:gap-3.5">
                            <!-- Founder Avatar with status dot -->
                            <div class="relative shrink-0 cursor-pointer" onclick="openPostModal()">
                                <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '')) ?>"
                                    alt="<?= htmlspecialchars($user['name']) ?>"
                                    class="w-11 h-11 sm:w-12 sm:h-12 rounded-full object-cover object-center ring-2 ring-indigo-500/20 dark:ring-indigo-500/30 shadow-2xs hover:scale-105 transition-transform">
                                <span class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-emerald-500 border-2 border-white dark:border-[#111827] rounded-full shadow-xs" title="Online"></span>
                            </div>

                            <!-- Full Width Trigger Pill -->
                            <button type="button" onclick="openPostModal()"
                                class="h-11 sm:h-12 flex-1 px-4 sm:px-5 rounded-full bg-slate-100/90 hover:bg-slate-200/70 dark:bg-slate-800/80 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/80 hover:border-indigo-400 dark:hover:border-indigo-500 transition-all duration-200 cursor-pointer flex items-center justify-between text-left group min-w-0 shadow-2xs">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <i data-lucide="edit-3" class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0"></i>
                                    <span class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 group-hover:text-slate-800 dark:group-hover:text-slate-200 transition-colors truncate">
                                        Start a post, share traction, or announce a milestone...
                                    </span>
                                </div>
                                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-700/80 text-[11px] font-bold text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white group-hover:border-indigo-600 transition shadow-2xs shrink-0 ml-2">
                                    <i data-lucide="sparkles" class="w-3 h-3 text-amber-500 group-hover:text-amber-300"></i>
                                    <span>Create Post</span>
                                </span>
                            </button>
                        </div>

                        <!-- Row 2: Media, Milestone, Analytics & Submit Actions -->
                        <div class="flex items-center justify-between gap-2 flex-wrap pt-2.5 border-t border-slate-100 dark:border-slate-800/80">
                            <div class="flex items-center gap-1 sm:gap-2 flex-wrap">
                                <!-- Photo Attachment Trigger -->
                                <button type="button" onclick="openPostModal(); setTimeout(() => document.getElementById('post-img-file')?.click(), 250);"
                                    class="px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 text-xs font-bold transition flex items-center gap-2 cursor-pointer group">
                                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i data-lucide="image" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                    </div>
                                    <span>Photo</span>
                                </button>

                                <!-- Milestone Preset Trigger -->
                                <button type="button" onclick="openPostModal();"
                                    class="px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 text-xs font-bold transition flex items-center gap-2 cursor-pointer group">
                                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i data-lucide="trending-up" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                    </div>
                                    <span>Milestone</span>
                                </button>

                                <!-- Live Traction Analytics Trigger -->
                                <button type="button" onclick="openPostAnalyticsModal()"
                                    class="px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-purple-600 dark:hover:text-purple-400 text-xs font-bold transition flex items-center gap-2 cursor-pointer group"
                                    title="View post impressions, views, and likes">
                                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-purple-50 dark:bg-purple-950/80 text-purple-600 dark:text-purple-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i data-lucide="bar-chart-2" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span>Analytics</span>
                                        <?php if ($myTotalPosts > 0): ?>
                                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-purple-100 dark:bg-purple-900/80 text-purple-700 dark:text-purple-300">
                                                <?= $myTotalPosts ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </button>
                            </div>

                            <!-- Right Post Button -->
                            <button type="button" onclick="openPostModal()"
                                class="h-9 sm:h-10 px-4 sm:px-5 rounded-full bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-extrabold text-xs sm:text-sm shadow-md shadow-indigo-600/25 hover:shadow-lg hover:shadow-indigo-600/35 transition-all duration-200 flex items-center gap-1.5 shrink-0 cursor-pointer ml-auto">
                                <i data-lucide="plus" class="w-4 h-4 stroke-[2.5]"></i>
                                <span>Create Post</span>
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
                            $postAs = $p['post_as'] ?? 'company';
                            $isPostBoth = ($postAs === 'both');
                            $isPostCompany = ($postAs === 'company');
                            $isPostFounder = ($postAs === 'founder');

                            $founderName = htmlspecialchars($p['founder_name'] ?? 'Founder');
                            $companyName = htmlspecialchars($p['company_name'] ?? 'Company');
                            $founderUrl = url('founder/view.php?id=' . encode_id($p['founder_id']));
                            $companyUrl = url('founder/company_view.php?id=' . encode_id($p['company_id']));

                            $founderAvatar = get_avatar_url($p['founder_avatar'] ?? '');
                            $companyLogo = get_company_logo_url($p['company_logo'] ?? '');

                            if ($isPostBoth) {
                                $authorTitle = $founderName . ' & ' . $companyName;
                                $authorSubtitle = htmlspecialchars($p['founder_designation'] ?? 'Founder') . ' • Dual Co-Broadcast • ' . time_ago($p['created_at'] ?? 'now');
                                $primaryUrl = $founderUrl;
                                $avatarSrc = $founderAvatar;
                            } elseif ($isPostCompany) {
                                $authorTitle = $companyName;
                                $authorSubtitle = "Posted by " . $founderName . " • " . time_ago($p['created_at'] ?? 'now');
                                $primaryUrl = $companyUrl;
                                $avatarSrc = $companyLogo;
                            } else {
                                $authorTitle = $founderName;
                                $authorSubtitle = htmlspecialchars($p['founder_designation'] ?? 'Founder') . " at " . $companyName . " • " . time_ago($p['created_at'] ?? 'now');
                                $primaryUrl = $founderUrl;
                                $avatarSrc = $founderAvatar;
                            }
                            $isMyPost = (($p['founder_id'] ?? 0) == $user['id']);
                            ?>
                            <!-- Single LinkedIn Style Post Card -->
                            <article id="post-<?= $p['id'] ?>"
                                class="rounded-3xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden space-y-4 p-5 sm:p-6 transition hover:border-slate-300 dark:hover:border-slate-700">

                                <!-- Post Author Header -->
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <?php if ($isPostBoth): ?>
                                            <!-- Dual Overlapping Co-Author Avatars -->
                                            <div class="relative flex-shrink-0" style="width: 50px; height: 48px;">
                                                <a href="<?= $founderUrl ?>" title="Founder: <?= $founderName ?>" class="block">
                                                    <img src="<?= htmlspecialchars($founderAvatar) ?>"
                                                        alt="<?= $founderName ?>"
                                                        class="w-10 h-10 rounded-full border-2 border-white dark:border-slate-800 object-cover shadow-xs hover:scale-105 transition"
                                                        style="width: 40px; height: 40px; min-width: 40px;">
                                                </a>
                                                <a href="<?= $companyUrl ?>" title="Company: <?= $companyName ?>" class="block absolute -bottom-1 -right-1">
                                                    <img src="<?= htmlspecialchars($companyLogo) ?>"
                                                        alt="<?= $companyName ?>"
                                                        class="w-6 h-6 rounded-lg border-2 border-white dark:border-slate-800 object-cover shadow-sm bg-white hover:scale-110 transition"
                                                        style="width: 24px; height: 24px; min-width: 24px;">
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <!-- Single Author Avatar or Logo -->
                                            <a href="<?= $primaryUrl ?>"
                                                class="flex-shrink-0">
                                                <img src="<?= htmlspecialchars($avatarSrc) ?>"
                                                    alt="<?= htmlspecialchars($authorTitle) ?>"
                                                    class="w-12 h-12 <?= $isPostCompany ? 'rounded-2xl border' : 'rounded-full border-2' ?> border-slate-200 dark:border-slate-700 object-cover object-center shadow-xs hover:scale-105 transition"
                                                    style="width: 48px; height: 48px; min-width: 48px;">
                                            </a>
                                        <?php endif; ?>

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <?php if ($isPostBoth): ?>
                                                    <a href="<?= $founderUrl ?>"
                                                        class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition truncate">
                                                        <?= $founderName ?>
                                                    </a>
                                                    <span class="text-xs text-slate-400 font-normal">&amp;</span>
                                                    <a href="<?= $companyUrl ?>"
                                                        class="font-extrabold text-sm sm:text-base text-indigo-600 dark:text-indigo-400 hover:underline transition truncate">
                                                        <?= $companyName ?>
                                                    </a>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-pink-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200/80 dark:border-indigo-800/80 shadow-2xs">
                                                        <i data-lucide="sparkles" class="w-3 h-3 text-indigo-500"></i> Dual Co-Publish
                                                    </span>
                                                <?php else: ?>
                                                    <a href="<?= $primaryUrl ?>"
                                                        class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition truncate">
                                                        <?= htmlspecialchars($authorTitle) ?>
                                                    </a>
                                                    <i data-lucide="badge-check" class="w-4 h-4 text-indigo-600 flex-shrink-0"></i>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-[11.5px] text-slate-500 dark:text-slate-400 truncate">
                                                <?= $authorSubtitle ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <?php if ($p['founder_id'] != $user['id']): ?>
                                            <?= render_connect_button((int)$p['founder_id'], [
                                                'name' => $p['founder_name'],
                                                'headline' => $authorSubtitle,
                                                'avatar' => $avatarSrc,
                                                'class' => 'text-[11px] py-1 px-2.5 hidden sm:inline-flex'
                                            ]) ?>
                                        <?php endif; ?>

                                        <!-- Category Badge -->
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/80 flex-shrink-0">
                                            <?= htmlspecialchars($p['category']) ?>
                                        </span>
                                    </div>
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
                                            class="w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center shadow-xs">
                                            <i data-lucide="heart" class="w-2.5 h-2.5 fill-white text-white"></i>
                                        </span>
                                        <span id="likes-count-<?= $p['id'] ?>"><?= (int) $p['likes_count'] ?> likes</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                                            <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span><?= number_format(max((int)($p['views_count'] ?? 0), ((int)$p['likes_count'] * 7) + ((int)$p['comments_count'] * 4) + 16)) ?> views</span>
                                        </span>
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
                <!-- RIGHT COLUMN: Trending Startups & Network - Sticky Fixed & Scrollable -->
                <!-- ---------------------------------------------------- -->
                <div id="feed-right-column"
                    data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
                    class="w-full lg:w-[300px] xl:w-[330px] 2xl:w-[360px] flex-shrink-0 lg:sticky lg:top-24 lg:self-start space-y-3.5 lg:max-h-[calc(100vh-6.5rem)] lg:overflow-y-auto custom-scroll no-scrollbar overscroll-contain pb-12 pr-1">

                    <!-- Live Angel Syndicate Rounds (Placed at Top) -->
                    <?php if (!empty($liveRounds)): ?>
                        <div
                            class="rounded-2xl p-4 bg-gradient-to-br from-amber-500/10 via-indigo-500/5 to-transparent border border-amber-500/30 dark:border-amber-500/20 shadow-sm space-y-2.5">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                <h3 class="text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                    Live Angel Syndicates</h3>
                            </div>

                            <div class="space-y-2.5">
                                <?php foreach ($liveRounds as $lIdx => $lr):
                                    $pct = $lr['target_amount'] > 0 ? min(100, round(($lr['amount_raised'] / $lr['target_amount']) * 100)) : 0;
                                    ?>
                                    <div
                                        class="<?= $lIdx >= 2 ? 'live-syndicate-extra hidden' : '' ?> p-2.5 rounded-xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-700/80 space-y-1.5">
                                        <div class="flex items-center justify-between text-xs">
                                            <span
                                                class="font-extrabold text-slate-900 dark:text-white truncate max-w-[170px]"><?= htmlspecialchars($lr['company_name']) ?></span>
                                            <span class="font-black text-emerald-600 dark:text-emerald-400"><?= $pct ?>%</span>
                                        </div>
                                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-gradient-to-r from-amber-500 to-emerald-500 h-full rounded-full"
                                                style="width: <?= $pct ?>%"></div>
                                        </div>
                                        <div class="flex items-center justify-between text-[10.5px] text-slate-500">
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

                            <?php if (count($liveRounds) > 2): ?>
                                <button type="button" id="btn-toggle-live-syndicates"
                                    onclick="toggleLiveSyndicates()"
                                    class="w-full py-1.5 px-3 rounded-xl bg-white/90 dark:bg-slate-800/80 hover:bg-amber-50 dark:hover:bg-amber-950/60 text-amber-700 dark:text-amber-400 text-xs font-bold transition flex items-center justify-center gap-1.5 border border-amber-200/60 dark:border-amber-800/60 cursor-pointer shadow-2xs">
                                    <i data-lucide="chevrons-down" class="w-3.5 h-3.5 text-amber-500"></i>
                                    <span>Read More (+<?= count($liveRounds) - 2 ?>)</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Trending Startups to Explore -->
                    <div
                        class="rounded-2xl p-4 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                        <div
                            class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h3 class="text-xs font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <i data-lucide="compass" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Discover Startups</span>
                            </h3>
                            <span class="text-[10.5px] text-indigo-600 font-bold"><?= count($trendingStartups) ?>
                                Live</span>
                        </div>

                        <div class="space-y-2.5">
                            <?php foreach ($trendingStartups as $idx => $ts): ?>
                                <div
                                    class="<?= $idx >= 2 ? 'discover-startup-extra hidden' : '' ?> p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 space-y-1.5">
                                    <div class="flex items-center gap-2.5">
                                        <img src="<?= htmlspecialchars(get_company_logo_url($ts['logo_url'] ?? '')) ?>"
                                            alt="<?= htmlspecialchars($ts['name']) ?>"
                                            class="w-8 h-8 rounded-lg object-cover object-center border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                        <div class="min-w-0 flex-1">
                                            <a href="<?= url('founder/company_view.php?id=' . encode_id($ts['id'])) ?>"
                                                class="text-xs font-extrabold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 block truncate">
                                                <?= htmlspecialchars($ts['name']) ?>
                                            </a>
                                            <div class="text-[10.5px] text-slate-500 truncate">
                                                <?= htmlspecialchars($ts['industry'] ?? 'Tech') ?> •
                                                <?= htmlspecialchars($ts['city'] ?? 'India') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between pt-1 gap-1 text-xs">
                                        <a href="<?= url('founder/company_view.php?id=' . encode_id($ts['id'])) ?>"
                                            class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline flex items-center gap-1 text-[11px]">
                                            <span>Deal Room</span>
                                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                        </a>

                                        <div class="flex items-center gap-1">
                                            <?= render_company_connect_button((int)$ts['id'], [
                                                'name' => $ts['name'],
                                                'logo' => get_company_logo_url($ts['logo_url'] ?? ''),
                                                'class' => 'text-[10px] py-0.5 px-2'
                                            ]) ?>
                                            <button type="button"
                                                onclick="triggerFeedAngelModal(<?= $ts['id'] ?>, '<?= htmlspecialchars(addslashes($ts['name'])) ?>')"
                                                class="px-2 py-0.5 rounded-md bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-[10px] uppercase tracking-wider cursor-pointer">
                                                Invest
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($trendingStartups) > 2): ?>
                            <button type="button" id="btn-toggle-discover-startups"
                                onclick="toggleDiscoverStartups()"
                                class="w-full py-1.5 px-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-slate-600 hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-400 text-xs font-bold transition flex items-center justify-center gap-1.5 border border-slate-200/60 dark:border-slate-700/60 cursor-pointer shadow-2xs">
                                <i data-lucide="chevrons-down" class="w-3.5 h-3.5 text-indigo-500"></i>
                                <span>Read More (+<?= count($trendingStartups) - 2 ?>)</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Top Founders to Network With -->
                    <div
                        class="rounded-2xl p-4 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                        <div
                            class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h3 class="text-xs font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <i data-lucide="users" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Peer Founders</span>
                            </h3>
                            <span class="text-[10.5px] text-slate-400">Network</span>
                        </div>

                        <div class="space-y-2.5">
                            <?php foreach ($networkFounders as $nfIdx => $nf): ?>
                                <div class="<?= $nfIdx >= 2 ? 'peer-founder-extra hidden' : '' ?> flex items-center justify-between gap-2.5">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <img src="<?= htmlspecialchars(get_avatar_url($nf['avatar_url'] ?? '')) ?>"
                                            alt="<?= htmlspecialchars($nf['name']) ?>"
                                            class="w-8 h-8 rounded-full object-cover object-center border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                        <div class="min-w-0">
                                            <a href="<?= url('founder/view.php?id=' . encode_id($nf['id'])) ?>"
                                                class="text-xs font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 truncate block">
                                                <?= htmlspecialchars($nf['name']) ?>
                                            </a>
                                            <div class="text-[10px] text-slate-500 truncate">
                                                <?= htmlspecialchars($nf['company_name'] ?? 'Startup Founder') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <?= render_connect_button((int)$nf['id'], [
                                            'name' => $nf['name'],
                                            'headline' => $nf['company_name'] ?? 'Startup Founder',
                                            'avatar' => get_avatar_url($nf['avatar_url'] ?? ''),
                                            'class' => 'text-[11px] py-1 px-2.5'
                                        ]) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($networkFounders) > 2): ?>
                            <button type="button" id="btn-toggle-peer-founders"
                                onclick="togglePeerFounders()"
                                class="w-full py-1.5 px-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-slate-600 hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-400 text-xs font-bold transition flex items-center justify-center gap-1.5 border border-slate-200/60 dark:border-slate-700/60 cursor-pointer shadow-2xs">
                                <i data-lucide="chevrons-down" class="w-3.5 h-3.5 text-indigo-500"></i>
                                <span>Read More (+<?= count($networkFounders) - 2 ?>)</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ======================================================== -->
    <!-- POST PERFORMANCE & LIVE TRACTION ANALYTICS MODAL -->
    <!-- ======================================================== -->
    <div id="post-analytics-modal"
        data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
        class="fixed inset-0 z-50 bg-black/75 backdrop-blur-xs flex items-center justify-center p-2 sm:p-4 hidden overscroll-contain">
        <div
            data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
            class="bg-white dark:bg-slate-900 rounded-3xl w-[98vw] sm:w-[94vw] lg:w-[85vw] max-w-5xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col h-[90vh] max-h-[880px] animate-in fade-in zoom-in-95 duration-200">

            <!-- STICKY TOP HEADER -->
            <div class="flex items-center justify-between px-5 sm:px-7 py-4 border-b border-slate-100 dark:border-slate-800 shrink-0 bg-white dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-xs">
                        <i data-lucide="bar-chart-2" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base sm:text-xl font-black text-slate-900 dark:text-white">
                                Post Performance &amp; Live Traction
                            </h3>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-extrabold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Live Insights</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Track views, likes, comments, and real-time investor engagement on your updates</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="location.reload()" title="Refresh Analytics"
                        class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                    <button type="button" onclick="closePostAnalyticsModal()"
                        class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- SCROLLABLE BODY -->
            <div id="analyticsScrollBody" data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true" tabindex="0" class="p-5 sm:p-7 space-y-6 overflow-y-auto custom-scroll no-scrollbar flex-1 overscroll-contain outline-none">
                
                <!-- 4 HERO KPI CARDS -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                    <!-- Total Posts -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Updates</span>
                            <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <i data-lucide="newspaper" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white"><?= number_format($myTotalPosts) ?></div>
                        <p class="text-[11px] text-slate-400 mt-1">Live in Network Feed</p>
                    </div>

                    <!-- Total Views -->
                    <div class="p-4 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/70 dark:border-emerald-800/70">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">Total Impressions</span>
                            <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-900 dark:text-emerald-100"><?= number_format($myTotalViews) ?></div>
                        <p class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-1">Investor &amp; Founder Reach</p>
                    </div>

                    <!-- Total Likes -->
                    <div class="p-4 rounded-2xl bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200/70 dark:border-rose-800/70">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-rose-700 dark:text-rose-300">Total Likes</span>
                            <div class="w-7 h-7 rounded-lg bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                                <i data-lucide="heart" class="w-3.5 h-3.5 fill-rose-500"></i>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-rose-900 dark:text-rose-100"><?= number_format($myTotalLikes) ?></div>
                        <p class="text-[11px] text-rose-600/80 dark:text-rose-400/80 mt-1">Direct Endorsements</p>
                    </div>

                    <!-- Total Comments -->
                    <div class="p-4 rounded-2xl bg-purple-50/60 dark:bg-purple-950/30 border border-purple-200/70 dark:border-purple-800/70">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-purple-700 dark:text-purple-300">Discussions</span>
                            <div class="w-7 h-7 rounded-lg bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-purple-900 dark:text-purple-100"><?= number_format($myTotalComments) ?></div>
                        <p class="text-[11px] text-purple-600/80 dark:text-purple-400/80 mt-1">Comments &amp; Inquiries</p>
                    </div>
                </div>

                <!-- LIVE POST-BY-POST TRACTION BREAKDOWN -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="activity" class="w-4 h-4 text-indigo-600"></i>
                                <span>Recent Updates &amp; Engagement Stream</span>
                            </h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Live breakdown of views and interactions for each update you published</p>
                        </div>
                        <button type="button" onclick="closePostAnalyticsModal(); openPostModal();"
                            class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>New Post</span>
                        </button>
                    </div>

                    <?php if (empty($myPostsAnalytics)): ?>
                        <div class="p-8 text-center rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 space-y-3 bg-slate-50/50 dark:bg-slate-800/20">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto">
                                <i data-lucide="newspaper" class="w-6 h-6"></i>
                            </div>
                            <h5 class="text-sm font-bold text-slate-800 dark:text-slate-200">No updates published yet</h5>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto">Post your first traction milestone or company announcement to start tracking live views and investor interactions!</p>
                            <button type="button" onclick="closePostAnalyticsModal(); openPostModal();"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md">
                                Create My First Post
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3.5">
                            <?php foreach ($myPostsAnalytics as $item): 
                                $views = (int)$item['effective_views'];
                                $likes = (int)$item['likes_count'];
                                $comms = (int)$item['comments_count'];
                                $engRate = $views > 0 ? round((($likes + $comms) / $views) * 100, 1) : 0;
                            ?>
                                <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-800/40 border border-slate-200/90 dark:border-slate-700/80 shadow-xs space-y-3 hover:border-indigo-300 dark:hover:border-indigo-700 transition">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                                <?= htmlspecialchars($item['category']) ?>
                                            </span>
                                            <span class="text-xs text-slate-400">• <?= time_ago($item['created_at']) ?></span>
                                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700/60">
                                                Author: <?= ucfirst($item['post_as']) ?>
                                            </span>
                                        </div>
                                        <button type="button" onclick="closePostAnalyticsModal(); scrollToFeedPost(<?= $item['id'] ?>)"
                                            class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 cursor-pointer">
                                            <span>View in Feed</span>
                                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>

                                    <div>
                                        <h5 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </h5>
                                        <p class="text-xs text-slate-600 dark:text-slate-300 line-clamp-2 mt-1">
                                            <?= htmlspecialchars($item['content']) ?>
                                        </p>
                                    </div>

                                    <!-- METRICS CHIPS -->
                                    <div class="flex flex-wrap items-center gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-700/60">
                                        <!-- Views -->
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 text-xs font-bold">
                                            <i data-lucide="eye" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            <span><?= number_format($views) ?> Views</span>
                                        </div>

                                        <!-- Likes -->
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/60 text-xs font-bold">
                                            <i data-lucide="heart" class="w-3.5 h-3.5 text-rose-600 fill-rose-500/20"></i>
                                            <span><?= number_format($likes) ?> Likes</span>
                                        </div>

                                        <!-- Comments -->
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-800 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60 text-xs font-bold">
                                            <i data-lucide="message-square" class="w-3.5 h-3.5 text-purple-600"></i>
                                            <span><?= number_format($comms) ?> Comments</span>
                                        </div>

                                        <!-- Engagement Rate -->
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300 text-xs font-bold ml-auto">
                                            <i data-lucide="trending-up" class="w-3.5 h-3.5 text-indigo-500"></i>
                                            <span><?= $engRate ?>% Engagement</span>
                                        </div>
                                    </div>

                                    <!-- Recent Engagements (If any) -->
                                    <?php if (!empty($item['recent_engagements'])): ?>
                                        <div class="pt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                                            <span class="font-bold text-[11px] uppercase tracking-wider text-slate-400">Recent:</span>
                                            <div class="flex items-center -space-x-1.5 overflow-hidden">
                                                <?php foreach ($item['recent_engagements'] as $eng): ?>
                                                    <img src="<?= htmlspecialchars(get_avatar_url($eng['avatar_url'] ?? '')) ?>"
                                                        alt="<?= htmlspecialchars($eng['name']) ?>"
                                                        title="<?= htmlspecialchars($eng['name']) ?> <?= $eng['activity_type'] ?>"
                                                        class="w-5 h-5 rounded-full object-cover ring-2 ring-white dark:ring-slate-800">
                                                <?php endforeach; ?>
                                            </div>
                                            <span class="text-[11px] truncate">
                                                <?= htmlspecialchars($item['recent_engagements'][0]['name']) ?>
                                                <?= count($item['recent_engagements']) > 1 ? 'and ' . (count($item['recent_engagements']) - 1) . ' others interacted' : 'interacted' ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- STICKY FOOTER -->
            <div class="flex items-center justify-between px-5 sm:px-7 py-3.5 border-t border-slate-100 dark:border-slate-800 shrink-0 bg-slate-50/70 dark:bg-slate-900/90 text-xs">
                <span class="text-slate-500 dark:text-slate-400">
                    Showing real-time interaction metrics for your updates
                </span>
                <button type="button" onclick="closePostAnalyticsModal()"
                    class="px-5 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- LINKEDIN STYLE CREATE POST MODAL (90% Screen Cover, Responsive Studio Layout, Sticky Header & Footer) -->
    <!-- ======================================================== -->
    <div id="create-post-modal"
        data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
        class="fixed inset-0 z-50 bg-black/75 backdrop-blur-xs flex items-center justify-center p-1.5 sm:p-3 lg:p-4 hidden overscroll-contain">
        <div
            data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
            class="bg-white dark:bg-slate-900 rounded-3xl w-[98vw] sm:w-[95vw] lg:w-[90vw] max-w-7xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col h-[92vh] max-h-[94vh] animate-in fade-in zoom-in-95 duration-200">

            <!-- STICKY TOP HEADER -->
            <div class="flex items-center justify-between px-5 sm:px-8 py-4 border-b border-slate-100 dark:border-slate-800 shrink-0 bg-white dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-xs">
                        <i data-lucide="edit-3" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base sm:text-xl font-black text-slate-900 dark:text-white">
                                Create Network Post
                            </h3>
                            <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] font-extrabold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">
                                <i data-lucide="radio" class="w-3.5 h-3.5 text-emerald-500"></i>
                                <span>Broadcast Mode</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Share traction milestones, funding updates, or announcements with 450+ verified investors</p>
                    </div>
                </div>
                <button type="button" onclick="closePostModal()"
                    class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- FORM WRAPPER (Flex-1 with Scrollable Body & Sticky Footer) -->
            <form method="POST" action="<?= url('founder/feed.php') ?>" enctype="multipart/form-data" id="createPostForm" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="form_action" value="create_post">

                <!-- SCROLLABLE FORM BODY -->
                <div id="createPostScrollBody"
                    data-lenis-prevent="true" data-lenis-prevent-wheel="true" data-lenis-prevent-touch="true"
                    tabindex="0"
                    class="p-4 sm:p-6 space-y-4 overflow-y-auto custom-scroll no-scrollbar flex-1 overscroll-contain outline-none">

                    <!-- SECTION 1: POST AS IDENTITY SELECTOR (Medium & Clean UX) -->
                    <div class="bg-slate-50/80 dark:bg-slate-800/40 p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="user-check" class="w-4 h-4 text-indigo-600"></i>
                                    <span>Publish As</span>
                                </label>
                                <span class="text-xs text-slate-400">• Choose who authors this update</span>
                            </div>
                            <?php if ($myCompany): ?>
                                <span class="hidden sm:inline-flex items-center gap-1 text-[11px] font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 px-2.5 py-0.5 rounded-full border border-indigo-200/70 dark:border-indigo-800/70">
                                    <i data-lucide="sparkles" class="w-3 h-3 text-indigo-600"></i> Co-Publishing Active
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($myCompany): ?>
                            <!-- 3 Responsive Medium Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                
                                <!-- Card 1: BOTH PROFILES (CO-PUBLISH) -->
                                <div id="identity-card-both" onclick="setPostIdentity('both')"
                                    class="p-3 sm:p-3.5 rounded-2xl border-2 border-indigo-600 bg-white dark:bg-slate-900 ring-4 ring-indigo-500/15 cursor-pointer transition-all select-none relative group shadow-sm hover:shadow-md">
                                    <input type="radio" name="post_as" id="post-as-both" value="both" checked class="sr-only">
                                    
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <!-- Overlapping Dual Avatars -->
                                        <div class="relative flex items-center shrink-0" style="width: 52px; height: 34px;">
                                            <div class="w-8 h-8 rounded-full overflow-hidden border-2 border-white dark:border-slate-800 shadow-sm z-10 shrink-0" style="width: 32px; height: 32px;">
                                                <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '')) ?>" 
                                                     alt="<?= htmlspecialchars($user['name']) ?>" 
                                                     class="w-full h-full object-cover">
                                            </div>
                                            <div class="w-8 h-8 rounded-lg overflow-hidden border-2 border-white dark:border-slate-800 shadow-sm -ml-2.5 bg-white shrink-0" style="width: 32px; height: 32px;">
                                                <img src="<?= htmlspecialchars(get_company_logo_url($myCompany['logo_url'] ?? '')) ?>" 
                                                     alt="<?= htmlspecialchars($myCompany['name']) ?>" 
                                                     class="w-full h-full object-cover">
                                            </div>
                                        </div>
                                        
                                        <!-- Checkmark & Badge -->
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 bg-indigo-600 text-white rounded shadow-2xs">
                                                <i data-lucide="zap" class="w-2.5 h-2.5 text-amber-300"></i> 2x Reach
                                            </span>
                                            <div id="check-both" class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                                <i data-lucide="check" class="w-3 h-3 stroke-[3]"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white leading-tight flex items-center gap-1.5">
                                            <i data-lucide="users" class="w-3.5 h-3.5 text-indigo-600"></i>
                                            <span>Both Profiles</span>
                                        </h4>
                                        <p class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 truncate mt-0.5">
                                            <?= htmlspecialchars($user['name']) ?> &amp; <?= htmlspecialchars($myCompany['name']) ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Card 2: PERSONAL FOUNDER ONLY -->
                                <div id="identity-card-founder" onclick="setPostIdentity('founder')"
                                    class="p-3 sm:p-3.5 rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-600 cursor-pointer transition-all select-none relative group shadow-xs hover:shadow-sm">
                                    <input type="radio" name="post_as" id="post-as-founder" value="founder" class="sr-only">
                                    
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700 shadow-xs" style="width: 32px; height: 32px;">
                                            <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '')) ?>" 
                                                 alt="<?= htmlspecialchars($user['name']) ?>" 
                                                 class="w-full h-full object-cover">
                                        </div>
                                        
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded">
                                                <i data-lucide="user" class="w-2.5 h-2.5 text-slate-500"></i> Personal
                                            </span>
                                            <div id="check-founder" class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs hidden">
                                                <i data-lucide="check" class="w-3 h-3 stroke-[3]"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white leading-tight truncate flex items-center gap-1.5">
                                            <i data-lucide="user" class="w-3.5 h-3.5 text-slate-600 dark:text-slate-300"></i>
                                            <span><?= htmlspecialchars($user['name']) ?></span>
                                        </h4>
                                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                            Founder Leadership Timeline
                                        </p>
                                    </div>
                                </div>

                                <!-- Card 3: OFFICIAL COMPANY ONLY -->
                                <div id="identity-card-company" onclick="setPostIdentity('company')"
                                    class="p-3 sm:p-3.5 rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-600 cursor-pointer transition-all select-none relative group shadow-xs hover:shadow-sm">
                                    <input type="radio" name="post_as" id="post-as-company" value="company" class="sr-only">
                                    
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <div class="w-8 h-8 rounded-lg overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700 shadow-xs bg-white" style="width: 32px; height: 32px;">
                                            <img src="<?= htmlspecialchars(get_company_logo_url($myCompany['logo_url'] ?? '')) ?>" 
                                                 alt="<?= htmlspecialchars($myCompany['name']) ?>" 
                                                 class="w-full h-full object-cover">
                                        </div>
                                        
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded">
                                                <i data-lucide="building" class="w-2.5 h-2.5 text-slate-500"></i> Company
                                            </span>
                                            <div id="check-company" class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs hidden">
                                                <i data-lucide="check" class="w-3 h-3 stroke-[3]"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white leading-tight truncate flex items-center gap-1.5">
                                            <i data-lucide="building-2" class="w-3.5 h-3.5 text-indigo-600"></i>
                                            <span><?= htmlspecialchars($myCompany['name']) ?></span>
                                        </h4>
                                        <p class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 truncate mt-0.5">
                                            Official Startup Brand Page
                                        </p>
                                    </div>
                                </div>

                            </div>
                        <?php else: ?>
                            <!-- Single Personal Founder Card when no company registered -->
                            <div id="identity-card-founder"
                                class="p-3 sm:p-3.5 rounded-2xl border-2 border-indigo-600 bg-white dark:bg-slate-900 ring-4 ring-indigo-500/15 flex items-center gap-3 shadow-sm">
                                <input type="radio" name="post_as" id="post-as-founder" value="founder" checked class="sr-only">
                                <div class="w-10 h-10 rounded-full overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700 shadow-xs" style="width: 40px; height: 40px;">
                                    <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '')) ?>" 
                                         alt="<?= htmlspecialchars($user['name']) ?>" 
                                         class="w-full h-full object-cover">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-600 dark:text-slate-300"></i>
                                        <span><?= htmlspecialchars($user['name']) ?></span>
                                    </span>
                                    <span class="text-[11px] text-slate-500 font-medium block">Personal Founder Profile</span>
                                </div>
                                <div class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <i data-lucide="check" class="w-3 h-3 stroke-[3]"></i>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- DYNAMIC SLEEK EXPLANATION PILL (Medium, Clean & Direct) -->
                        <div id="identity-explain-box" class="px-3.5 py-2.5 rounded-xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/70 dark:border-indigo-800/70 flex items-center justify-between gap-3 text-xs transition">
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-indigo-600 shrink-0"></i>
                                <span class="font-black text-slate-900 dark:text-slate-100 shrink-0" id="identity-explain-title">
                                    Co-Publishing Active:
                                </span>
                                <span class="text-slate-600 dark:text-slate-300 truncate" id="identity-explain-desc">
                                    Broadcasts to both <strong><?= htmlspecialchars($user['name']) ?></strong> &amp; <strong><?= htmlspecialchars($myCompany['name'] ?? 'Company') ?></strong> timelines simultaneously.
                                </span>
                            </div>
                            <span class="shrink-0 text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-indigo-600 text-white flex items-center gap-1" id="identity-explain-tag">
                                <i data-lucide="trending-up" class="w-3 h-3"></i> 2x Reach
                            </span>
                        </div>
                    </div>

                    <!-- SECTION 2: UPDATE TYPE & POST HEADLINE (Responsive 2-Column Row) -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- Update Type (1 col) -->
                        <div>
                            <label class="block text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <i data-lucide="tag" class="w-3.5 h-3.5 text-slate-500"></i>
                                <span>Update Category</span>
                            </label>
                            <select name="category" id="post-category-select"
                                class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-bold bg-white dark:bg-slate-800 text-slate-900 dark:text-white outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 transition">
                                <option value="Milestone">Traction Milestone</option>
                                <option value="Product Launch">Product Launch &amp; Feature</option>
                                <option value="Funding Round">Funding Round &amp; Angel Pitch</option>
                                <option value="Partnership">Strategic Partnership / Customer Win</option>
                                <option value="Hiring">Executive / Tech Hiring</option>
                                <option value="News Update">General Company Announcement</option>
                            </select>
                        </div>

                        <!-- Post Headline (2 cols) -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <i data-lucide="type" class="w-3.5 h-3.5 text-slate-500"></i>
                                <span>Post Headline <span class="text-rose-500">*</span></span>
                            </label>
                            <input type="text" name="title" required
                                placeholder="e.g. SolarisPulse completes 50,000 battery swaps across Delhi-NCR!"
                                class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-bold bg-white dark:bg-slate-800 text-slate-900 dark:text-white outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 transition">
                        </div>
                    </div>

                    <!-- SECTION 3: KEY METRICS BADGE & QUICK SUGGESTIONS -->
                    <div class="bg-slate-50/60 dark:bg-slate-800/30 p-4 sm:p-5 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                                <i data-lucide="bar-chart-2" class="w-3.5 h-3.5 text-slate-500"></i>
                                <span>Key Metrics Badge (Optional)</span>
                            </label>
                            <span class="text-xs font-extrabold text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                                <i data-lucide="trending-up" class="w-3.5 h-3.5 text-indigo-600"></i> Boosts Investor Views
                            </span>
                        </div>
                        <input type="text" name="metrics_summary" id="post-metrics-input" placeholder="e.g. ARR: ₹2.4 Cr • MoM: +35% • 18k Fleets"
                            class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-semibold bg-white dark:bg-slate-800 text-slate-900 dark:text-white outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 transition">
                        
                        <!-- Quick Metric Insertion Chips -->
                        <div class="flex items-center gap-2 flex-wrap pt-1">
                            <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-500"></i> Quick Chips:
                            </span>
                            <button type="button" onclick="setQuickMetric('ARR: ₹1.2 Cr • +40% MoM')" 
                                class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-300 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer shadow-2xs flex items-center gap-1.5">
                                <i data-lucide="trending-up" class="w-3 h-3 text-emerald-500"></i>
                                <span>+ ARR Milestone</span>
                            </button>
                            <button type="button" onclick="setQuickMetric('Raising ₹2 Cr Seed • 50% Committed')" 
                                class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-300 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer shadow-2xs flex items-center gap-1.5">
                                <i data-lucide="dollar-sign" class="w-3 h-3 text-amber-500"></i>
                                <span>+ Seed Round</span>
                            </button>
                            <button type="button" onclick="setQuickMetric('50,000+ Active Users • 4.8/5 Rating')" 
                                class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-300 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer shadow-2xs flex items-center gap-1.5">
                                <i data-lucide="users" class="w-3 h-3 text-blue-500"></i>
                                <span>+ 50k Users</span>
                            </button>
                            <button type="button" onclick="setQuickMetric('Monthly GMV: ₹85 Lakhs • Unit Profitable')" 
                                class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-300 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer shadow-2xs flex items-center gap-1.5">
                                <i data-lucide="activity" class="w-3 h-3 text-indigo-500"></i>
                                <span>+ GMV Milestone</span>
                            </button>
                        </div>
                    </div>

                    <!-- SECTION 4: STORY TEXTAREA & IMAGE ATTACHMENT (Responsive 2-Column Row) -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- Story / Content (2 cols) with Formatting Toolbar -->
                        <div class="lg:col-span-2 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-500"></i>
                                    <span>Story / Announcement Details <span class="text-rose-500">*</span></span>
                                </label>
                                <span class="text-[11px] text-slate-400 font-medium">Supports formatting &amp; bullet points</span>
                            </div>

                            <!-- Formatting Quick Toolbar (Icons) -->
                            <div class="flex items-center gap-2 p-2 rounded-2xl bg-slate-100/90 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 flex-wrap">
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1 px-1.5 shrink-0">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-indigo-500"></i>
                                    <span>Insert Template:</span>
                                </span>
                                <button type="button" onclick="insertPostSnippet('\n• ')"
                                    class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1 border border-slate-200 dark:border-slate-600 cursor-pointer shadow-2xs">
                                    <i data-lucide="list" class="w-3 h-3 text-indigo-500"></i>
                                    <span>Bullet</span>
                                </button>
                                <button type="button" onclick="insertPostSnippet('\nKey Traction: ')"
                                    class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1 border border-slate-200 dark:border-slate-600 cursor-pointer shadow-2xs">
                                    <i data-lucide="trending-up" class="w-3 h-3 text-emerald-500"></i>
                                    <span>Traction</span>
                                </button>
                                <button type="button" onclick="insertPostSnippet('\nMilestone Achieved: ')"
                                    class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1 border border-slate-200 dark:border-slate-600 cursor-pointer shadow-2xs">
                                    <i data-lucide="award" class="w-3 h-3 text-amber-500"></i>
                                    <span>Milestone</span>
                                </button>
                                <button type="button" onclick="insertPostSnippet('\nNext Target: ')"
                                    class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1 border border-slate-200 dark:border-slate-600 cursor-pointer shadow-2xs">
                                    <i data-lucide="target" class="w-3 h-3 text-blue-500"></i>
                                    <span>Next Goal</span>
                                </button>
                            </div>

                            <textarea name="content" id="post-content-textarea" rows="6" required
                                placeholder="Share the context, what your team accomplished, milestones achieved, lessons learned, and what's next for your startup..."
                                class="w-full p-4 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 leading-relaxed outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 transition"></textarea>
                        </div>

                        <!-- Image Attachment (1 col) -->
                        <div>
                            <label class="block text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <i data-lucide="image" class="w-3.5 h-3.5 text-slate-500"></i>
                                <span>Attach Photo / Graphic (Optional)</span>
                            </label>
                            <div class="p-4 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 flex flex-col items-center justify-center text-center space-y-2.5 min-h-[170px]">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-xs">
                                    <i data-lucide="image-plus" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <label for="post-img-file"
                                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold cursor-pointer inline-flex items-center gap-1.5 transition shadow-xs active:scale-95">
                                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                        <span>Choose Image</span>
                                    </label>
                                    <input type="file" id="post-img-file" name="post_image" accept="image/*" class="hidden"
                                        onchange="previewPostFile(this)">
                                </div>
                                <span id="post-img-filename" class="text-[11px] text-slate-400 truncate max-w-[220px] block">PNG, JPG or WebP (max 10MB)</span>
                                <div id="post-img-preview-box" class="hidden w-full pt-1">
                                    <img id="post-img-preview" src="#" alt="Preview" class="w-full h-24 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shadow-xs">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STICKY BOTTOM FOOTER (ALWAYS VISIBLE & ACCESSIBLE) -->
                <div class="px-5 sm:px-8 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-900/95 shrink-0 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 font-medium">
                        <i data-lucide="globe" class="w-4 h-4 text-indigo-500 shrink-0"></i>
                        <span>Public network broadcast • Visible to all verified investors &amp; founders</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" onclick="closePostModal()"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-200/50 dark:hover:bg-slate-800 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" id="publishPostBtn"
                            class="px-7 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-extrabold text-xs sm:text-sm shadow-md shadow-indigo-600/30 transition flex items-center gap-2 cursor-pointer font-satoshi">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            <span id="publishBtnText">Publish to Network</span>
                        </button>
                    </div>
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
        // Clean Background Scroll Lock & Lenis Engine Pause
        function lockBodyScroll() {
            if (window.__lenis) {
                try { window.__lenis.stop(); } catch(e) {}
            }
            if (window.lenis) {
                try { window.lenis.stop(); } catch(e) {}
            }
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            document.body.classList.add('overflow-hidden');
            document.documentElement.classList.add('overflow-hidden');
        }

        function unlockBodyScroll() {
            if (window.__lenis) {
                try { window.__lenis.start(); } catch(e) {}
            }
            if (window.lenis) {
                try { window.lenis.start(); } catch(e) {}
            }
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
            document.body.classList.remove('overflow-hidden');
            document.documentElement.classList.remove('overflow-hidden');
        }

        function openPostModal(category = '') {
            const modal = document.getElementById('create-post-modal');
            if (modal) {
                modal.classList.remove('hidden');
                lockBodyScroll();
            }
            if (category) {
                const sel = document.getElementById('post-category-select');
                if (sel) sel.value = category;
            }
            <?php if ($myCompany): ?>
            setPostIdentity('both');
            <?php else: ?>
            setPostIdentity('founder');
            <?php endif; ?>
            if (window.lucide) lucide.createIcons();

            setTimeout(() => {
                const scrollBody = document.getElementById('createPostScrollBody');
                if (scrollBody) {
                    scrollBody.scrollTop = 0;
                    scrollBody.focus({ preventScroll: true });
                }
            }, 30);
        }

        function closePostModal() {
            document.getElementById('create-post-modal')?.classList.add('hidden');
            unlockBodyScroll();
        }

        // Post Analytics Modal Handlers
        function openPostAnalyticsModal() {
            const modal = document.getElementById('post-analytics-modal');
            if (modal) {
                modal.classList.remove('hidden');
                lockBodyScroll();
            }
            if (window.lucide) lucide.createIcons();
            setTimeout(() => {
                const scrollBody = document.getElementById('analyticsScrollBody');
                if (scrollBody) {
                    scrollBody.scrollTop = 0;
                    scrollBody.focus({ preventScroll: true });
                }
            }, 30);
        }

        function closePostAnalyticsModal() {
            document.getElementById('post-analytics-modal')?.classList.add('hidden');
            unlockBodyScroll();
        }

        function scrollToFeedPost(postId) {
            const el = document.getElementById('post-' + postId);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.add('ring-4', 'ring-indigo-500/40', 'transition-all', 'duration-500');
                setTimeout(() => {
                    el.classList.remove('ring-4', 'ring-indigo-500/40');
                }, 2500);
            }
        }

        // Close on backdrop click & Escape key
        document.getElementById('create-post-modal')?.addEventListener('click', function(e) {
            if (e.target === this) closePostModal();
        });
        document.getElementById('post-analytics-modal')?.addEventListener('click', function(e) {
            if (e.target === this) closePostAnalyticsModal();
        });

        // Keyboard navigation trap for modal
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('create-post-modal');
            const analyticsModal = document.getElementById('post-analytics-modal');
            const isPostOpen = modal && !modal.classList.contains('hidden');
            const isAnalyticsOpen = analyticsModal && !analyticsModal.classList.contains('hidden');

            if (isPostOpen) {
                if (e.key === 'Escape') {
                    closePostModal();
                } else if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End'].includes(e.key)) {
                    if (e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'INPUT') {
                        const scrollBody = document.getElementById('createPostScrollBody');
                        if (scrollBody) {
                            if (e.key === 'ArrowDown') scrollBody.scrollTop += 60;
                            else if (e.key === 'ArrowUp') scrollBody.scrollTop -= 60;
                            else if (e.key === 'PageDown') scrollBody.scrollTop += 320;
                            else if (e.key === 'PageUp') scrollBody.scrollTop -= 320;
                            else if (e.key === 'Home') scrollBody.scrollTop = 0;
                            else if (e.key === 'End') scrollBody.scrollTop = scrollBody.scrollHeight;
                        }
                        e.preventDefault();
                    }
                }
            } else if (isAnalyticsOpen) {
                if (e.key === 'Escape') {
                    closePostAnalyticsModal();
                } else if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End'].includes(e.key)) {
                    const scrollBody = document.getElementById('analyticsScrollBody');
                    if (scrollBody) {
                        if (e.key === 'ArrowDown') scrollBody.scrollTop += 60;
                        else if (e.key === 'ArrowUp') scrollBody.scrollTop -= 60;
                        else if (e.key === 'PageDown') scrollBody.scrollTop += 320;
                        else if (e.key === 'PageUp') scrollBody.scrollTop -= 320;
                        else if (e.key === 'Home') scrollBody.scrollTop = 0;
                        else if (e.key === 'End') scrollBody.scrollTop = scrollBody.scrollHeight;
                    }
                    e.preventDefault();
                }
            }
        });

        // Global Wheel Trap: Background CANNOT move while modal is open!
        window.addEventListener('wheel', function(e) {
            const modal = document.getElementById('create-post-modal');
            const analyticsModal = document.getElementById('post-analytics-modal');
            const angelModal = document.getElementById('feed-angel-modal');
            const isPostModalOpen = modal && !modal.classList.contains('hidden');
            const isAnalyticsModalOpen = analyticsModal && !analyticsModal.classList.contains('hidden');
            const isAngelModalOpen = angelModal && !angelModal.classList.contains('hidden');

            if (isPostModalOpen) {
                const scrollBody = document.getElementById('createPostScrollBody');
                if (scrollBody) {
                    // If user is inside a textarea that can scroll, let it scroll
                    if (e.target && e.target.tagName === 'TEXTAREA') {
                        const ta = e.target;
                        const canScrollUp = ta.scrollTop > 0 && e.deltaY < 0;
                        const canScrollDown = (ta.scrollTop + ta.clientHeight < ta.scrollHeight) && e.deltaY > 0;
                        if (canScrollUp || canScrollDown) return;
                    }
                    // Route wheel scroll directly to modal content
                    scrollBody.scrollTop += e.deltaY;
                }
                // Stop background from ever scrolling!
                e.preventDefault();
            } else if (isAnalyticsModalOpen) {
                const scrollBody = document.getElementById('analyticsScrollBody');
                if (scrollBody) {
                    scrollBody.scrollTop += e.deltaY;
                }
                e.preventDefault();
            } else if (isAngelModalOpen) {
                e.preventDefault();
            }
        }, { passive: false });

        // Touch scroll trap for background
        window.addEventListener('touchmove', function(e) {
            const modal = document.getElementById('create-post-modal');
            const analyticsModal = document.getElementById('post-analytics-modal');
            if (modal && !modal.classList.contains('hidden')) {
                const scrollBody = document.getElementById('createPostScrollBody');
                if (!scrollBody || !scrollBody.contains(e.target)) {
                    e.preventDefault();
                }
            } else if (analyticsModal && !analyticsModal.classList.contains('hidden')) {
                const scrollBody = document.getElementById('analyticsScrollBody');
                if (!scrollBody || !scrollBody.contains(e.target)) {
                    e.preventDefault();
                }
            }
        }, { passive: false });


        // Quick Snippet Insertion into Post Content Story Textarea
        function insertPostSnippet(snippet) {
            const textarea = document.getElementById('post-content-textarea') || document.querySelector('textarea[name="content"]');
            if (!textarea) return;
            const start = textarea.selectionStart ?? textarea.value.length;
            const end = textarea.selectionEnd ?? textarea.value.length;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + snippet + text.substring(end);
            textarea.focus();
            const newPos = start + snippet.length;
            textarea.setSelectionRange(newPos, newPos);
        }

        function setPostIdentity(identity) {
            const bothRadio = document.getElementById('post-as-both');
            const founderRadio = document.getElementById('post-as-founder');
            const companyRadio = document.getElementById('post-as-company');

            const bothCard = document.getElementById('identity-card-both');
            const founderCard = document.getElementById('identity-card-founder');
            const companyCard = document.getElementById('identity-card-company');

            const checkBoth = document.getElementById('check-both');
            const checkFounder = document.getElementById('check-founder');
            const checkCompany = document.getElementById('check-company');

            const explainBox = document.getElementById('identity-explain-box');
            const explainTitle = document.getElementById('identity-explain-title');
            const explainTag = document.getElementById('identity-explain-tag');
            const explainDesc = document.getElementById('identity-explain-desc');

            const activeCardClasses = ['border-indigo-600', 'ring-4', 'ring-indigo-500/15', 'shadow-md'];
            const inactiveCardClasses = ['border-slate-200', 'dark:border-slate-700', 'shadow-xs'];

            // Reset all 3 cards
            [bothCard, founderCard, companyCard].forEach(c => {
                if (c) {
                    activeCardClasses.forEach(cls => c.classList.remove(cls));
                    inactiveCardClasses.forEach(cls => c.classList.add(cls));
                }
            });
            [checkBoth, checkFounder, checkCompany].forEach(chk => {
                if (chk) chk.classList.add('hidden');
            });

            if (identity === 'both') {
                if (bothRadio) bothRadio.checked = true;
                if (bothCard) {
                    inactiveCardClasses.forEach(cls => bothCard.classList.remove(cls));
                    activeCardClasses.forEach(cls => bothCard.classList.add(cls));
                }
                if (checkBoth) checkBoth.classList.remove('hidden');

                if (explainBox) {
                    explainBox.className = 'px-3.5 py-2.5 rounded-xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/70 dark:border-indigo-800/70 flex items-center justify-between gap-3 text-xs transition';
                }
                if (explainTitle) explainTitle.textContent = 'Co-Publishing Active:';
                if (explainTag) {
                    explainTag.innerHTML = '<i data-lucide="trending-up" class="w-3 h-3"></i> 2x Reach';
                    explainTag.className = 'shrink-0 text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-indigo-600 text-white flex items-center gap-1';
                }
                if (explainDesc) {
                    explainDesc.innerHTML = 'Broadcasts to both <strong><?= addslashes(htmlspecialchars($user['name'])) ?></strong> &amp; <strong><?= addslashes(htmlspecialchars($myCompany['name'] ?? 'Company')) ?></strong> timelines simultaneously.';
                }
            } else if (identity === 'founder') {
                if (founderRadio) founderRadio.checked = true;
                if (founderCard) {
                    inactiveCardClasses.forEach(cls => founderCard.classList.remove(cls));
                    activeCardClasses.forEach(cls => founderCard.classList.add(cls));
                }
                if (checkFounder) checkFounder.classList.remove('hidden');

                if (explainBox) {
                    explainBox.className = 'px-3.5 py-2.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200/70 dark:border-amber-800/70 flex items-center justify-between gap-3 text-xs transition';
                }
                if (explainTitle) explainTitle.textContent = 'Personal Timeline:';
                if (explainTag) {
                    explainTag.innerHTML = '<i data-lucide="user" class="w-3 h-3"></i> Founder Only';
                    explainTag.className = 'shrink-0 text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-amber-600 text-white flex items-center gap-1';
                }
                if (explainDesc) {
                    explainDesc.innerHTML = 'Broadcasts strictly to <strong><?= addslashes(htmlspecialchars($user['name'])) ?></strong> personal leadership timeline.';
                }
            } else if (identity === 'company') {
                if (companyRadio) companyRadio.checked = true;
                if (companyCard) {
                    inactiveCardClasses.forEach(cls => companyCard.classList.remove(cls));
                    activeCardClasses.forEach(cls => companyCard.classList.add(cls));
                }
                if (checkCompany) checkCompany.classList.remove('hidden');

                if (explainBox) {
                    explainBox.className = 'px-3.5 py-2.5 rounded-xl bg-purple-50/80 dark:bg-purple-950/40 border border-purple-200/70 dark:border-purple-800/70 flex items-center justify-between gap-3 text-xs transition';
                }
                if (explainTitle) explainTitle.textContent = 'Company Page:';
                if (explainTag) {
                    explainTag.innerHTML = '<i data-lucide="building" class="w-3 h-3"></i> Brand Only';
                    explainTag.className = 'shrink-0 text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-purple-600 text-white flex items-center gap-1';
                }
                if (explainDesc) {
                    explainDesc.innerHTML = 'Broadcasts strictly to <strong><?= addslashes(htmlspecialchars($myCompany['name'] ?? 'Company')) ?></strong> official venture profile.';
                }
            }
            if (window.lucide) lucide.createIcons();
        }

        function setQuickMetric(val) {
            const input = document.getElementById('post-metrics-input');
            if (input) {
                input.value = val;
                input.focus();
            }
        }

        function previewPostFile(input) {
            const label = document.getElementById('post-img-filename');
            const previewBox = document.getElementById('post-img-preview-box');
            const previewImg = document.getElementById('post-img-preview');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                label.textContent = file.name;
                label.className = 'text-[11px] text-emerald-600 font-bold truncate max-w-xs';
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (previewImg) previewImg.src = e.target.result;
                    if (previewBox) previewBox.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                if (previewBox) previewBox.classList.add('hidden');
            }
        }

        document.getElementById('createPostForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('publishPostBtn');
            const txt = document.getElementById('publishBtnText');
            if (btn && txt) {
                btn.classList.add('opacity-80', 'cursor-wait');
                txt.textContent = 'Publishing to Network...';
            }
        });

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
            lockBodyScroll();
        }
        function closeFeedAngelModal() {
            document.getElementById('feed-angel-modal')?.classList.add('hidden');
            unlockBodyScroll();
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

        function toggleLiveSyndicates() {
            const items = document.querySelectorAll('.live-syndicate-extra');
            const btn = document.getElementById('btn-toggle-live-syndicates');
            if (!items.length || !btn) return;
            const isExpanded = !items[0].classList.contains('hidden');
            const extraCount = items.length;
            items.forEach(el => {
                if (isExpanded) {
                    el.classList.add('hidden');
                } else {
                    el.classList.remove('hidden');
                }
            });
            btn.innerHTML = isExpanded
                ? `<i data-lucide="chevrons-down" class="w-3.5 h-3.5 text-amber-500"></i><span>Read More (+${extraCount})</span>`
                : `<i data-lucide="chevrons-up" class="w-3.5 h-3.5 text-amber-500"></i><span>Show Less</span>`;
            if (window.lucide) lucide.createIcons();
        }

        function toggleDiscoverStartups() {
            const items = document.querySelectorAll('.discover-startup-extra');
            const btn = document.getElementById('btn-toggle-discover-startups');
            if (!items.length || !btn) return;
            const isExpanded = !items[0].classList.contains('hidden');
            const extraCount = items.length;
            items.forEach(el => {
                if (isExpanded) {
                    el.classList.add('hidden');
                } else {
                    el.classList.remove('hidden');
                }
            });
            btn.innerHTML = isExpanded
                ? `<i data-lucide="chevrons-down" class="w-3.5 h-3.5 text-indigo-500"></i><span>Read More (+${extraCount})</span>`
                : `<i data-lucide="chevrons-up" class="w-3.5 h-3.5 text-indigo-500"></i><span>Show Less</span>`;
            if (window.lucide) lucide.createIcons();
        }

        function togglePeerFounders() {
            const items = document.querySelectorAll('.peer-founder-extra');
            const btn = document.getElementById('btn-toggle-peer-founders');
            if (!items.length || !btn) return;
            const isExpanded = !items[0].classList.contains('hidden');
            const extraCount = items.length;
            items.forEach(el => {
                if (isExpanded) {
                    el.classList.add('hidden');
                } else {
                    el.classList.remove('hidden');
                }
            });
            btn.innerHTML = isExpanded
                ? `<i data-lucide="chevrons-down" class="w-3.5 h-3.5 text-indigo-500"></i><span>Read More (+${extraCount})</span>`
                : `<i data-lucide="chevrons-up" class="w-3.5 h-3.5 text-indigo-500"></i><span>Show Less</span>`;
            if (window.lucide) lucide.createIcons();
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closePostModal();
                closePostAnalyticsModal();
                closeFeedAngelModal();
            }
        });

        // Auto-open Analytics Modal if requested via URL param (e.g. from navbar on other pages)
        if (new URLSearchParams(window.location.search).get('open_analytics') === '1' || new URLSearchParams(window.location.search).get('analytics') === '1') {
            setTimeout(openPostAnalyticsModal, 150);
        }

        // Auto-fade flash message
        const flashEl = document.getElementById('flash-banner');
        if (flashEl) {
            setTimeout(() => {
                flashEl.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                flashEl.style.opacity = '0';
                flashEl.style.transform = 'translateY(-6px)';
                setTimeout(() => flashEl.remove(), 400);
            }, 4500);
        }

        // Enable smooth independent mouse-wheel scrolling for sidebars
        ['feed-right-column', 'feed-left-column'].forEach(id => {
            const col = document.getElementById(id);
            if (col) {
                col.addEventListener('wheel', (e) => {
                    const atTop = col.scrollTop <= 0 && e.deltaY < 0;
                    const atBottom = col.scrollTop + col.clientHeight >= col.scrollHeight - 1 && e.deltaY > 0;
                    if (!atTop && !atBottom) {
                        col.scrollTop += e.deltaY;
                        e.preventDefault();
                    }
                }, { passive: false });
            }
        });
    </script>
</body>

</html>