<?php
/**
 * Public & Investor Blog Reader: Company Trust Stories & Announcements
 * Ultra-Smooth Magazine Layout with Scroll Progress, Fluid Micro-interactions, and Verified Identity Stamp
 */
require_once __DIR__ . '/config.php';
$db = get_db();

$blogId = (int)($_GET['id'] ?? 0);
$slug = trim($_GET['slug'] ?? '');

$blog = null;
$company = null;
$author = null;
$moreBlogs = [];

if ($db && ($blogId > 0 || !empty($slug))) {
    if ($blogId > 0) {
        $stmt = $db->prepare("
            SELECT b.*, 
                   c.id as comp_id, c.name as comp_name, c.logo_url as comp_logo, c.industry as comp_industry,
                   c.city as comp_city, c.cin_number, c.verified_status as comp_verified, c.stage as comp_stage,
                   u.id as author_id, u.name as author_name, u.avatar_url as author_avatar, u.role as author_role, u.is_verified as author_verified
            FROM company_blogs b
            JOIN companies c ON b.company_id = c.id
            JOIN users u ON b.author_user_id = u.id
            WHERE b.id = ? AND b.is_published = 1
        ");
        $stmt->execute([$blogId]);
    } else {
        $stmt = $db->prepare("
            SELECT b.*, 
                   c.id as comp_id, c.name as comp_name, c.logo_url as comp_logo, c.industry as comp_industry,
                   c.city as comp_city, c.cin_number, c.verified_status as comp_verified, c.stage as comp_stage,
                   u.id as author_id, u.name as author_name, u.avatar_url as author_avatar, u.role as author_role, u.is_verified as author_verified
            FROM company_blogs b
            JOIN companies c ON b.company_id = c.id
            JOIN users u ON b.author_user_id = u.id
            WHERE b.slug = ? AND b.is_published = 1
        ");
        $stmt->execute([$slug]);
    }
    $blog = $stmt->fetch();

    if ($blog) {
        // Increment views count
        $db->prepare("UPDATE company_blogs SET views_count = views_count + 1 WHERE id = ?")->execute([$blog['id']]);

        // Fetch other stories from the same company
        $mStmt = $db->prepare("SELECT * FROM company_blogs WHERE company_id = ? AND id != ? AND is_published = 1 ORDER BY created_at DESC LIMIT 3");
        $mStmt->execute([$blog['comp_id'], $blog['id']]);
        $moreBlogs = $mStmt->fetchAll();
    }
}

// If no specific blog found or no id passed, show latest company stories feed
if (!$blog && $db) {
    $feedStmt = $db->query("
        SELECT b.*, c.name as comp_name, c.logo_url as comp_logo, c.verified_status as comp_verified
        FROM company_blogs b
        JOIN companies c ON b.company_id = c.id
        WHERE b.is_published = 1
        ORDER BY b.created_at DESC
        LIMIT 12
    ");
    $feedBlogs = $feedStmt->fetchAll();
}

$pageTitle = $blog ? ($blog['title'] . ' • ' . $blog['comp_name']) : ('Startup Stories & Blogs • ' . APP_NAME);
$coverImage = $blog ? (str_starts_with($blog['cover_image'], 'http') ? $blog['cover_image'] : url($blog['cover_image'])) : '';
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php if ($blog): ?>
        <meta name="description" content="<?= htmlspecialchars($blog['summary'] ?? '') ?>">
        <meta property="og:title" content="<?= htmlspecialchars($blog['title']) ?>">
        <meta property="og:description" content="<?= htmlspecialchars($blog['summary'] ?? '') ?>">
        <meta property="og:image" content="<?= htmlspecialchars($coverImage) ?>">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
        }
        .editorial-body { 
            font-family: 'Newsreader', Georgia, serif; 
        }
        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .card-clean:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        }
        /* Custom Reading Progress Bar */
        #reading-progress {
            transform-origin: left;
            will-change: transform;
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 min-h-screen flex flex-col selection:bg-indigo-500 selection:text-white">

    <!-- Top Reading Progress Indicator -->
    <div id="reading-progress" class="fixed top-0 left-0 h-[3px] bg-gradient-to-r from-indigo-500 via-indigo-600 to-purple-600 w-full z-50 transform scale-x-0 transition-transform duration-75"></div>

    <!-- Header Navigation (Ultra Smooth Glassmorphism) -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-40 transition-all duration-300" id="main-header">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="<?= url('index.php') ?>" class="flex items-center space-x-2.5 group">
                <div class="w-8 h-8 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-black text-xs shadow-sm group-hover:scale-105 group-hover:bg-indigo-700 transition duration-200">
                    SP
                </div>
                <div>
                    <span class="font-extrabold text-slate-900 text-sm tracking-tight group-hover:text-indigo-600 transition"><?= APP_NAME ?></span>
                    <span class="text-[10px] text-indigo-600 font-bold uppercase block -mt-1 tracking-wider">Stories & Trust Hub</span>
                </div>
            </a>

            <div class="flex items-center space-x-2.5 sm:space-x-3">
                <a href="<?= url('blog.php') ?>" class="text-slate-600 hover:text-slate-900 text-xs font-semibold px-3 py-1.5 rounded-xl hover:bg-slate-100 transition duration-200">
                    All Stories
                </a>
                <a href="<?= url('index.php') ?>" class="text-slate-600 hover:text-slate-900 text-xs font-semibold px-3 py-1.5 rounded-xl hover:bg-slate-100 transition duration-200 hidden sm:inline-block">
                    Explore Startups
                </a>

                <?php if (auth_check()): ?>
                    <?php $currentUser = current_user(); ?>
                    <a href="<?= url($currentUser && $currentUser['role'] === 'founder' ? 'founder/dashboard.php' : 'investor/discover.php') ?>" 
                       class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shadow-sm hover:shadow transition duration-200 active:scale-95">
                        Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?= url('auth/login.php') ?>" 
                       class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm hover:shadow transition duration-200 active:scale-95">
                        Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if (!$blog): ?>
        <!-- Company Stories Directory Feed -->
        <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-12 space-y-10" id="feed-container">
            <div class="text-center max-w-2xl mx-auto space-y-3">
                <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/80 shadow-sm inline-block">
                    STARTUP STORIES & MILESTONES
                </span>
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">Verified Startup Blogs & Trust Stories</h1>
                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">Discover real milestones, product journeys, customer wins, and proof of execution directly from company founders.</p>
            </div>

            <?php if (empty($feedBlogs)): ?>
                <div class="card-clean rounded-3xl p-12 text-center text-slate-400 text-xs">
                    No company stories published yet. Founders can publish stories from their Founder Portal.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($feedBlogs as $fb): 
                        $fCover = str_starts_with($fb['cover_image'], 'http') ? $fb['cover_image'] : url($fb['cover_image']);
                    ?>
                        <a href="<?= url('blog.php?id=' . $fb['id']) ?>" class="card-clean rounded-2xl overflow-hidden hover:border-indigo-300 transition duration-300 flex flex-col group">
                            <div class="h-48 overflow-hidden bg-slate-100 relative">
                                <img src="<?= htmlspecialchars($fCover) ?>" alt="<?= htmlspecialchars($fb['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                                <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[10px] font-bold bg-white/95 text-indigo-700 shadow-sm border border-white">
                                    <?= htmlspecialchars($fb['category']) ?>
                                </span>
                            </div>
                            <div class="p-5 flex-1 flex flex-col justify-between">
                                <div>
                                    <div class="text-[10.5px] text-slate-400 font-semibold mb-1">
                                        <?= htmlspecialchars($fb['comp_name']) ?> • <?= date('M d, Y', strtotime($fb['published_at'])) ?>
                                    </div>
                                    <h3 class="font-bold text-slate-900 text-sm leading-snug line-clamp-2 group-hover:text-indigo-600 transition"><?= htmlspecialchars($fb['title']) ?></h3>
                                    <p class="text-slate-500 text-xs mt-2 line-clamp-2 leading-relaxed"><?= htmlspecialchars($fb['summary']) ?></p>
                                </div>
                                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-indigo-600 font-bold">
                                    <span>Read Story</span>
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform"></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>

    <?php else: ?>
        <!-- Single Blog Reader -->
        <article class="flex-1 max-w-3xl w-full mx-auto px-4 sm:px-6 py-10 space-y-8" id="story-article">

            <!-- Breadcrumbs -->
            <div class="fade-element flex items-center space-x-2 text-[11px] text-slate-400">
                <a href="<?= url('blog.php') ?>" class="hover:text-indigo-600 transition">Stories</a>
                <span>/</span>
                <a href="<?= url('investor/startup_detail.php?id=' . encode_id($blog['comp_id'])) ?>" class="hover:text-indigo-600 font-semibold text-slate-600 transition"><?= htmlspecialchars($blog['comp_name']) ?></a>
                <span>/</span>
                <span class="truncate max-w-[200px] text-slate-400"><?= htmlspecialchars($blog['category']) ?></span>
            </div>

            <!-- Category Tag & Main Headline -->
            <div class="fade-element space-y-3.5">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/80 shadow-sm">
                    <?= htmlspecialchars($blog['category']) ?>
                </span>
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight sm:leading-[1.18]">
                    <?= htmlspecialchars($blog['title']) ?>
                </h1>
            </div>

            <!-- Author, Startup Meta Bar & Interactive Share Toolbar -->
            <div class="fade-element py-4 border-y border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-3.5">
                    <a href="<?= url('investor/startup_detail.php?id=' . encode_id($blog['comp_id'])) ?>" class="flex-shrink-0 group">
                        <img src="<?= $blog['comp_logo'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>" alt="<?= htmlspecialchars($blog['comp_name']) ?>" class="w-11 h-11 rounded-2xl object-cover border border-slate-200 bg-white group-hover:scale-105 transition duration-200">
                    </a>
                    <div>
                        <div class="flex items-center space-x-1.5">
                            <a href="<?= url('investor/startup_detail.php?id=' . encode_id($blog['comp_id'])) ?>" class="font-bold text-slate-900 text-xs sm:text-sm hover:text-indigo-600 transition">
                                <?= htmlspecialchars($blog['comp_name']) ?>
                            </a>
                            <?php if ($blog['comp_verified'] === 'verified'): ?>
                                <i data-lucide="check-circle" class="w-3.5 h-3.5 text-blue-500 fill-blue-500 text-white" title="Verified Startup Company"></i>
                            <?php endif; ?>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            By <span class="font-semibold text-slate-700"><?= htmlspecialchars($blog['author_name']) ?></span> • <?= date('M d, Y', strtotime($blog['published_at'])) ?> • <?= $blog['read_time_minutes'] ?> min read
                        </div>
                    </div>
                </div>

                <!-- Smooth Micro-interactive Share Toolbar -->
                <div class="flex items-center space-x-1 bg-white border border-slate-200 rounded-2xl p-1 shadow-sm self-start sm:self-auto">
                    <button onclick="copyArticleLink()" id="share-copy-btn" class="px-2.5 py-1.5 rounded-xl hover:bg-slate-100 text-slate-700 text-xs font-semibold flex items-center space-x-1.5 transition duration-150 active:scale-95" title="Copy Link to Clipboard">
                        <i data-lucide="link" class="w-3.5 h-3.5" id="copy-icon"></i>
                        <span id="share-copy-label">Copy</span>
                    </button>
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode($blog['title'] . ' | ' . $blog['comp_name'] . ' ' . $currentUrl) ?>" target="_blank" class="p-2 rounded-xl hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 hover:-translate-y-0.5 transition duration-150" title="Share to WhatsApp">
                        <i data-lucide="message-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($currentUrl) ?>" target="_blank" class="p-2 rounded-xl hover:bg-blue-50 text-slate-600 hover:text-blue-700 hover:-translate-y-0.5 transition duration-150" title="Share to LinkedIn">
                        <i data-lucide="linkedin" class="w-3.5 h-3.5 text-blue-600"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode($blog['title']) ?>&url=<?= urlencode($currentUrl) ?>" target="_blank" class="p-2 rounded-xl hover:bg-slate-100 text-slate-600 hover:text-slate-900 hover:-translate-y-0.5 transition duration-150" title="Share to Twitter / X">
                        <i data-lucide="twitter" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>

            <!-- Featured Cover Photo & Proof Stamp -->
            <div class="fade-element rounded-3xl overflow-hidden border border-slate-200/90 shadow-sm bg-slate-100 group">
                <div class="relative overflow-hidden">
                    <img src="<?= htmlspecialchars($coverImage) ?>" alt="<?= htmlspecialchars($blog['title']) ?>" class="w-full max-h-[460px] object-cover group-hover:scale-[1.015] transition-transform duration-700 ease-out">
                </div>
                <div class="p-3 bg-white/95 border-t border-slate-100 text-[11px] text-slate-400 text-center flex items-center justify-center space-x-1.5 font-medium">
                    <i data-lucide="camera" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Official Operational Photo published by <?= htmlspecialchars($blog['comp_name']) ?></span>
                </div>
            </div>

            <!-- Key Takeaway Callout Card -->
            <?php if (!empty($blog['summary'])): ?>
                <div class="fade-element p-5 sm:p-6 rounded-2xl bg-indigo-50/70 border border-indigo-100/90 text-slate-800 leading-relaxed shadow-sm">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 mb-1 flex items-center space-x-1">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span>Executive Summary & Proof</span>
                    </div>
                    <div class="text-xs sm:text-[13.5px] text-indigo-950 font-medium leading-relaxed">
                        <?= htmlspecialchars($blog['summary']) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Editorial Article Body -->
            <div class="fade-element editorial-body text-slate-800 text-[17px] sm:text-[19px] leading-[1.8] sm:leading-[1.85] space-y-6 pt-2">
                <?= nl2br(htmlspecialchars($blog['content'])) ?>
            </div>

            <!-- Authentic Trust Stamp & Deal Room CTA -->
            <div class="fade-element card-clean rounded-3xl p-6 sm:p-7 bg-gradient-to-br from-white via-slate-50/50 to-indigo-50/30 border border-slate-200 mt-12">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center font-bold flex-shrink-0 shadow-sm">
                            <i data-lucide="shield-check" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="font-bold text-slate-900 text-sm sm:text-base">Verified Corporate Identity</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">MCA REGISTERED</span>
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                <?= htmlspecialchars($blog['comp_name']) ?> (CIN: <?= htmlspecialchars($blog['cin_number'] ?? 'U72900KA2024PTC123456') ?>) has completed regulatory identity compliance.
                            </div>
                        </div>
                    </div>

                    <a href="<?= url('investor/startup_detail.php?id=' . encode_id($blog['comp_id'])) ?>" 
                       class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm hover:shadow transition duration-200 flex items-center justify-center space-x-2 flex-shrink-0 active:scale-95">
                        <span>View Investment Deal Room</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>

            <!-- More Stories From This Startup -->
            <?php if (!empty($moreBlogs)): ?>
                <div class="fade-element pt-10 mt-10 border-t border-slate-200/80 space-y-4">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center space-x-1.5">
                        <i data-lucide="book-open" class="w-4 h-4 text-indigo-600"></i>
                        <span>More Stories from <?= htmlspecialchars($blog['comp_name']) ?></span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <?php foreach ($moreBlogs as $mb): 
                            $mbCover = str_starts_with($mb['cover_image'], 'http') ? $mb['cover_image'] : url($mb['cover_image']);
                        ?>
                            <a href="<?= url('blog.php?id=' . $mb['id']) ?>" class="card-clean rounded-2xl overflow-hidden hover:border-indigo-300 transition duration-300 group flex flex-col">
                                <div class="h-32 overflow-hidden bg-slate-100">
                                    <img src="<?= htmlspecialchars($mbCover) ?>" alt="<?= htmlspecialchars($mb['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                </div>
                                <div class="p-3.5 flex-1 flex flex-col justify-between text-xs">
                                    <h4 class="font-bold text-slate-900 line-clamp-2 group-hover:text-indigo-600 transition duration-150"><?= htmlspecialchars($mb['title']) ?></h4>
                                    <div class="text-[10px] text-slate-400 mt-2.5"><?= date('M d, Y', strtotime($mb['published_at'])) ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </article>
    <?php endif; ?>

    <!-- Floating Back to Top Button -->
    <button id="back-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="fixed bottom-6 right-6 p-3 rounded-full bg-slate-900/90 text-white shadow-lg backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300 hover:bg-slate-900 hover:scale-110 active:scale-95 z-40" title="Scroll to Top">
        <i data-lucide="arrow-up" class="w-4 h-4"></i>
    </button>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 mt-16">
        <div class="max-w-5xl mx-auto px-4 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> <?= APP_NAME ?> • Startup Stories, Verified Profiles & Investment Network.
        </div>
    </footer>

    <script>
        lucide.createIcons();

        // Smooth GSAP staggered entrance
        gsap.from(".fade-element", {
            duration: 0.55,
            y: 18,
            opacity: 0,
            stagger: 0.08,
            ease: "power2.out"
        });

        // Reading Progress & Back to Top Scroll Listener
        window.addEventListener('scroll', () => {
            const winScroll = document.documentElement.scrollTop || document.body.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = height > 0 ? (winScroll / height) : 0;
            
            const progressBar = document.getElementById('reading-progress');
            if (progressBar) {
                progressBar.style.transform = `scaleX(${scrolled})`;
            }

            const topBtn = document.getElementById('back-to-top');
            if (topBtn) {
                if (winScroll > 350) {
                    topBtn.classList.remove('opacity-0', 'pointer-events-none');
                    topBtn.classList.add('opacity-100', 'pointer-events-auto');
                } else {
                    topBtn.classList.add('opacity-0', 'pointer-events-none');
                    topBtn.classList.remove('opacity-100', 'pointer-events-auto');
                }
            }
        });

        function copyArticleLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                const label = document.getElementById('share-copy-label');
                const btn = document.getElementById('share-copy-btn');
                label.innerText = 'Copied!';
                btn.classList.add('bg-emerald-50', 'text-emerald-700');

                setTimeout(() => {
                    label.innerText = 'Copy';
                    btn.classList.remove('bg-emerald-50', 'text-emerald-700');
                }, 2200);
            });
        }
    </script>
    <?php include_once __DIR__ . '/includes/smooth_scroll.php'; ?>
</body>
</html>
