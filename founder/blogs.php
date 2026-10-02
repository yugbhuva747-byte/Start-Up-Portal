<?php
/**
 * Founder Module: Company Blog & Trust Stories Management
 * Allows founders to publish dynamic stories, upload proof/cover images, build trust, and share them.
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Company Blog & Trust Stories';

$company = null;
$blogs = [];
$error = '';
$flash = get_flash();

$categories = [
    'Company Story' => 'Company Story',
    'Product Launch' => 'Product Launch & Demo',
    'Customer Story' => 'Customer Case Study',
    'Milestone' => 'Growth Milestone',
    'Award & Press' => 'Award & Press Mention',
    'Culture & Team' => 'Culture & Team Proof',
];

// Load founder's company
if ($db) {
    $cStmt = $db->prepare("
        SELECT c.* FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ? LIMIT 1
    ");
    $cStmt->execute([$user['id']]);
    $company = $cStmt->fetch();
}

// Handle Blog Submission or Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. If you uploaded a large image, it may exceed the server upload limit.';
    } elseif (!$company) {
        $error = 'Please register or complete your company profile before publishing blog stories.';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'create_blog') {
            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? 'Company Story');
            if (!isset($categories[$category]))
                $category = 'Company Story';
            $summary = trim($_POST['summary'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $isPublished = isset($_POST['is_published']) ? 1 : 0;

            if (empty($title) || empty($content)) {
                $error = 'Please provide both a story title and story content.';
            } else {
                // Approximate read time (200 wpm)
                $wordCount = str_word_count(strip_tags($content));
                $readTime = max(1, (int) ceil($wordCount / 200));

                // Handle cover image upload
                $coverImagePath = '';
                if (!empty($_FILES['cover_image']['name'])) {
                    $file = $_FILES['cover_image'];
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                        if (in_array($ext, $allowedExts) && $file['size'] <= 12 * 1024 * 1024) {
                            $targetDir = ROOT_PATH . '/uploads/blogs';
                            if (!is_dir($targetDir))
                                @mkdir($targetDir, 0755, true);

                            $filename = 'blog_' . $company['id'] . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
                            if (move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
                                $coverImagePath = 'uploads/blogs/' . $filename;
                            } else {
                                $error = 'Failed to save cover image. Check folder permissions on uploads/blogs.';
                            }
                        } else {
                            $error = 'Image must be JPG, PNG, WEBP or GIF under 12MB.';
                        }
                    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                        $error = 'Image upload failed (file may exceed the server limit). Try a smaller image.';
                    }
                }

                // Default curated cover if none uploaded
                if (empty($coverImagePath) && empty($error)) {
                    $defaultImages = [
                        'Product Launch' => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=1200&q=80',
                        'Customer Story' => 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=1200&q=80',
                        'Award & Press' => 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?w=1200&q=80',
                        'Milestone' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=1200&q=80',
                        'Culture & Team' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&q=80',
                        'Company Story' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1200&q=80'
                    ];
                    $coverImagePath = $defaultImages[$category] ?? $defaultImages['Company Story'];
                }

                if (empty($error)) {
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-')) . '-' . rand(1000, 9999);

                    $ins = $db->prepare("
                        INSERT INTO company_blogs (company_id, author_user_id, title, slug, category, summary, content, cover_image, read_time_minutes, is_published, published_at, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $ins->execute([
                        $company['id'],
                        $user['id'],
                        $title,
                        $slug,
                        $category,
                        $summary ?: mb_substr(strip_tags($content), 0, 200) . '...',
                        $content,
                        $coverImagePath,
                        $readTime,
                        $isPublished
                    ]);

                    $blogId = $db->lastInsertId();
                    log_audit($user['id'], 'CREATE_COMPANY_BLOG', 'company_blogs', $blogId, "Founder published blog: {$title}");
                    set_flash('success', "Your company story '{$title}' has been published successfully!");
                    header('Location: ' . url('founder/blogs.php'));
                    exit;
                }
            }

        } elseif ($action === 'delete_blog') {
            $blogId = (int) ($_POST['blog_id'] ?? 0);
            if ($blogId > 0) {
                $targetBlog = $db->prepare("SELECT cover_image FROM company_blogs WHERE id = ? AND company_id = ?");
                $targetBlog->execute([$blogId, $company['id']]);
                $bRow = $targetBlog->fetch();

                if ($bRow && !empty($bRow['cover_image']) && str_starts_with($bRow['cover_image'], 'uploads/blogs/')) {
                    $fullPath = ROOT_PATH . '/' . $bRow['cover_image'];
                    if (file_exists($fullPath))
                        @unlink($fullPath);
                }

                $del = $db->prepare("DELETE FROM company_blogs WHERE id = ? AND company_id = ?");
                $del->execute([$blogId, $company['id']]);

                log_audit($user['id'], 'DELETE_COMPANY_BLOG', 'company_blogs', $blogId, "Founder deleted blog post #{$blogId}");
                set_flash('success', 'Blog story removed.');
                header('Location: ' . url('founder/blogs.php'));
                exit;
            }
        }
    }
}

// Load blogs AFTER handling POST so the list is always current
if ($db && $company) {
    $bStmt = $db->prepare("SELECT * FROM company_blogs WHERE company_id = ? ORDER BY created_at DESC");
    $bStmt->execute([$company['id']]);
    $blogs = $bStmt->fetchAll();
}

// Re-open the modal (with values kept) when the create form failed
$openModal = ($_SERVER['REQUEST_METHOD'] === 'POST'
    && $error !== ''
    && ($_POST['form_action'] ?? '') === 'create_blog');
$old = $openModal ? $_POST : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
</head>

<body
    class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 md:px-8 py-6 space-y-6" id="blogs-main">

            <?php if ($flash): ?>
                <div
                    class="p-4 rounded-xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2.5">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error && !$openModal): ?>
                <div
                    class="p-4 rounded-xl text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2.5">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!$company): ?>
                <div
                    class="p-4 rounded-xl text-sm font-semibold bg-amber-50 text-amber-800 border border-amber-200 flex items-center space-x-2.5">
                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
                    <span>Complete your company profile first to publish stories.</span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Company Blog
                        & Trust Stories</h1>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-medium mt-0.5">Share company
                        stories, milestone proof, customer case studies, and team photos to build investor trust.</p>
                </div>
                <div>
                    <button type="button" onclick="openCreateModal()"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-sm transition flex items-center space-x-2">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Write New Story</span>
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-4 sm:p-5">
                    <div class="text-xs font-bold text-slate-600 uppercase tracking-wider">Published Stories</div>
                    <div
                        class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-1 flex items-center justify-between">
                        <span><?= count($blogs) ?></span>
                        <i data-lucide="book-open" class="w-4 h-4 text-indigo-500"></i>
                    </div>
                </div>
                <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-4 sm:p-5">
                    <div class="text-xs font-bold text-slate-600 uppercase tracking-wider">Total Reader Views</div>
                    <div
                        class="text-xl sm:text-2xl font-extrabold text-emerald-600 mt-1 flex items-center justify-between">
                        <span><?= (int) array_sum(array_map(fn($b) => (int) ($b['views_count'] ?? 0), $blogs)) ?></span>
                        <i data-lucide="eye" class="w-4 h-4 text-emerald-500"></i>
                    </div>
                </div>
                <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-4 sm:p-5">
                    <div class="text-xs font-bold text-slate-600 uppercase tracking-wider">Verified Credibility</div>
                    <div
                        class="text-xl sm:text-2xl font-extrabold text-indigo-600 mt-1 flex items-center justify-between">
                        <span>100% Genuineness</span>
                        <i data-lucide="shield-check" class="w-5 h-5 text-indigo-500"></i>
                    </div>
                </div>
            </div>

            <!-- Published Stories List -->
            <div class="card-clean rounded-2xl p-6 sm:p-7">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5">
                    <h2
                        class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                        <i data-lucide="newspaper" class="w-4 h-4 text-indigo-600"></i>
                        <span>Company Stories Feed (<?= count($blogs) ?>)</span>
                    </h2>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Publicly visible on your
                        startup profile & shareable via URL</span>
                </div>

                <?php if (empty($blogs)): ?>
                    <div class="py-14 text-center text-slate-400">
                        <div
                            class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="feather" class="w-7 h-7"></i>
                        </div>
                        <div class="font-extrabold text-slate-800 dark:text-slate-100 text-base sm:text-lg">No stories
                            published yet</div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1.5 font-medium">Founders
                            who post authentic milestones and customer case studies receive 3.5x higher investor response
                            rates.</p>
                        <button type="button" onclick="openCreateModal()"
                            class="mt-5 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-sm transition">
                            Publish First Story
                        </button>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <?php foreach ($blogs as $b):
                            $cover = $b['cover_image'] ?? '';
                            $coverSrc = (str_starts_with($cover, 'http')) ? $cover : url($cover);
                            $publicBlogUrl = url('blog.php?id=' . $b['id']);
                            $dateRaw = $b['published_at'] ?: $b['created_at'];
                            ?>
                            <div
                                class="border border-slate-200 rounded-2xl overflow-hidden bg-white hover:border-indigo-300 transition flex flex-col group shadow-sm">
                                <div class="relative h-48 overflow-hidden bg-slate-100">
                                    <img src="<?= htmlspecialchars($coverSrc) ?>" alt="<?= htmlspecialchars($b['title']) ?>"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1200&q=80';"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                    <div class="absolute top-3 left-3">
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-bold bg-white/95 text-indigo-700 shadow-sm backdrop-blur-sm border border-white">
                                            <?= htmlspecialchars($b['category']) ?>
                                        </span>
                                    </div>
                                    <div class="absolute top-3 right-3 flex items-center space-x-1">
                                        <span
                                            class="px-2.5 py-1 rounded text-xs font-bold <?= $b['is_published'] ? 'bg-emerald-600 text-white' : 'bg-amber-600 text-white' ?> shadow-sm">
                                            <?= $b['is_published'] ? 'LIVE' : 'DRAFT' ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="p-5 flex-1 flex flex-col justify-between">
                                    <div>
                                        <div class="text-xs text-slate-500 font-medium mb-1.5 flex items-center space-x-2">
                                            <span><?= date('M d, Y', strtotime($dateRaw)) ?></span>
                                            <span>•</span>
                                            <span><?= (int) $b['read_time_minutes'] ?> min read</span>
                                            <span>•</span>
                                            <span class="flex items-center space-x-1">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                <span><?= (int) ($b['views_count'] ?? 0) ?></span>
                                            </span>
                                        </div>
                                        <h3 class="font-extrabold text-slate-900 text-base leading-snug line-clamp-2">
                                            <?= htmlspecialchars($b['title']) ?></h3>
                                        <p class="text-slate-600 text-sm mt-2 line-clamp-2 leading-relaxed">
                                            <?= htmlspecialchars($b['summary']) ?></p>
                                    </div>

                                    <div class="pt-4 mt-5 border-t border-slate-100 flex items-center justify-between">
                                        <a href="<?= htmlspecialchars($publicBlogUrl) ?>" target="_blank" rel="noopener"
                                            class="text-indigo-600 hover:text-indigo-700 text-sm font-bold flex items-center space-x-1 transition">
                                            <span>Read Article</span>
                                            <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                                        </a>

                                        <div class="flex items-center space-x-1.5">
                                            <!-- Share Button -->
                                            <button type="button" data-title="<?= htmlspecialchars($b['title'], ENT_QUOTES) ?>"
                                                data-url="<?= htmlspecialchars($publicBlogUrl, ENT_QUOTES) ?>"
                                                onclick="openShareModal(this.dataset.title, this.dataset.url)"
                                                class="p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 transition"
                                                title="Share Article">
                                                <i data-lucide="share-2" class="w-4 h-4"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <form action="<?= url('founder/blogs.php') ?>" method="POST"
                                                onsubmit="return confirm('Delete this blog story?');" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="form_action" value="delete_blog">
                                                <input type="hidden" name="blog_id" value="<?= (int) $b['id'] ?>">
                                                <button type="submit"
                                                    class="p-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 transition"
                                                    title="Delete Story">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Create Blog Modal -->
            <div id="create-blog-modal"
                class="<?= $openModal ? '' : 'hidden' ?> fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                <div
                    class="bg-white border border-slate-200 max-w-2xl w-full rounded-2xl flex flex-col max-h-[92vh] overflow-hidden shadow-2xl relative my-auto">
                    <!-- Fixed Header -->
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between flex-shrink-0 bg-white">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                <i data-lucide="feather" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">Publish Company Story & Validation
                                </h3>
                                <p class="text-xs text-slate-500 font-medium">Share office milestones, client wins, or
                                    product launches</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeCreateModal()"
                            class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <?php if ($openModal): ?>
                        <div
                            class="mx-5 mt-4 p-3 rounded-xl text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2 flex-shrink-0">
                            <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Scrollable Form Body -->
                    <form action="<?= url('founder/blogs.php') ?>" method="POST" enctype="multipart/form-data"
                        class="flex flex-col flex-1 overflow-hidden">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="form_action" value="create_blog">

                        <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-4 text-sm">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block font-bold text-slate-800 text-sm mb-1.5">
                                        <span>Story Title</span>
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" name="title" required
                                        value="<?= htmlspecialchars($old['title'] ?? '') ?>"
                                        placeholder="e.g., How we scaled to 100K active users & ₹2.5 Cr ARR"
                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10 rounded-xl text-slate-900 text-sm outline-none font-medium">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 text-sm mb-1.5">Category *</label>
                                    <div class="relative border border-slate-200 rounded-xl bg-slate-50 transition">
                                        <select name="category" required
                                            class="w-full px-4 py-3 bg-transparent rounded-xl text-sm text-slate-900 font-medium outline-none cursor-pointer">
                                            <?php foreach ($categories as $val => $label): ?>
                                                <option value="<?= htmlspecialchars($val) ?>" <?= (($old['category'] ?? 'Company Story') === $val) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Cover Image Uploader -->
                            <div>
                                <label class="block font-bold text-slate-800 text-sm mb-1.5">Cover Image (Team, Product,
                                    or Award Proof)</label>
                                <div class="border-2 border-dashed border-slate-200 hover:border-indigo-500 rounded-2xl p-5 text-center bg-slate-50 hover:bg-indigo-50 transition cursor-pointer relative"
                                    onclick="document.getElementById('blog-cover-input').click()">
                                    <input type="file" name="cover_image" id="blog-cover-input"
                                        accept=".jpg,.jpeg,.png,.webp,.gif" class="hidden"
                                        onchange="handleBlogImageSelect(this)">
                                    <div class="flex items-center justify-center space-x-2 text-indigo-600">
                                        <i data-lucide="image-plus" class="w-5 h-5"></i>
                                        <span class="text-sm font-bold" id="blog-img-label">Upload genuine photo (JPG,
                                            PNG, WEBP, GIF)</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1 font-medium">If empty, a high-quality curated
                                        category visual will be assigned automatically.</p>
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-800 text-sm mb-1.5">Executive Summary / Teaser
                                    Hook</label>
                                <input type="text" name="summary" value="<?= htmlspecialchars($old['summary'] ?? '') ?>"
                                    placeholder="Brief 1-2 sentence preview to engage investors..."
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10 rounded-xl text-slate-900 text-sm outline-none font-medium">
                            </div>

                            <div>
                                <label
                                    class="block font-bold text-slate-800 text-sm mb-1.5 flex items-center justify-between">
                                    <span class="flex items-center space-x-1">
                                        <span>Full Story Narrative</span>
                                        <span class="text-rose-500">*</span>
                                    </span>
                                    <span class="text-xs text-slate-500 font-medium">Markdown / Paragraphs
                                        supported</span>
                                </label>
                                <div
                                    class="border border-slate-200 focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-500/10 rounded-xl bg-slate-50 transition p-1">
                                    <textarea name="content" rows="6" required
                                        placeholder="Describe key accomplishments, customer testimonials, hard metrics, architecture details, and lessons learned..."
                                        class="w-full p-3 bg-transparent rounded-lg text-sm text-slate-900 outline-none leading-relaxed font-normal placeholder:text-slate-400 resize-y"><?= htmlspecialchars($old['content'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="pt-1">
                                <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                    <?php $checked = $openModal ? isset($old['is_published']) : true; ?>
                                    <input type="checkbox" name="is_published" value="1" <?= $checked ? 'checked' : '' ?>
                                        class="w-5 h-5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                    <span class="text-slate-800 font-bold text-sm">Publish immediately to company
                                        profile</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pinned Footer -->
                        <div
                            class="p-4 sm:p-5 border-t border-slate-100 flex items-center justify-end space-x-2.5 bg-slate-50 flex-shrink-0">
                            <button type="button" onclick="closeCreateModal()"
                                class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 text-sm font-bold transition">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm transition shadow-md flex items-center space-x-2">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>Publish Story</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Share Modal -->
            <div id="share-modal"
                class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div
                    class="bg-white border border-slate-200 max-w-md w-full rounded-2xl p-6 sm:p-7 shadow-2xl relative">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                <i data-lucide="share-2" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">Share Company Story</h3>
                                <p class="text-xs text-slate-500 font-medium">Broadcast your milestone to build network
                                    credibility</p>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('share-modal').classList.add('hidden')"
                            class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div>
                            <label class="block font-bold text-slate-800 text-sm mb-1.5">Direct Article Link</label>
                            <div class="flex items-center space-x-2">
                                <input type="text" id="share-url-input" readonly
                                    class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm font-mono outline-none font-semibold">
                                <button type="button" onclick="copyShareUrl()" id="copy-btn"
                                    class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm transition flex items-center space-x-1.5 shadow-sm">
                                    <i data-lucide="copy" class="w-4 h-4"></i>
                                    <span id="copy-btn-text">Copy</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 text-sm mb-2">Share To Social Channels</label>
                            <div class="grid grid-cols-3 gap-3">
                                <a id="share-wa" href="#" target="_blank" rel="noopener"
                                    class="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs sm:text-sm flex flex-col items-center justify-center space-y-1.5 transition">
                                    <i data-lucide="message-circle" class="w-5 h-5 text-emerald-600"></i>
                                    <span>WhatsApp</span>
                                </a>
                                <a id="share-li" href="#" target="_blank" rel="noopener"
                                    class="p-3.5 rounded-xl border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold text-xs sm:text-sm flex flex-col items-center justify-center space-y-1.5 transition">
                                    <i data-lucide="linkedin" class="w-5 h-5 text-blue-600"></i>
                                    <span>LinkedIn</span>
                                </a>
                                <a id="share-tw" href="#" target="_blank" rel="noopener"
                                    class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-800 font-bold text-xs sm:text-sm flex flex-col items-center justify-center space-y-1.5 transition">
                                    <i data-lucide="twitter" class="w-5 h-5 text-slate-800"></i>
                                    <span>Twitter / X</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        function refreshIcons() {
            if (window.lucide && typeof lucide.createIcons === 'function') lucide.createIcons();
        }
        refreshIcons();
        if (window.gsap) {
            // fromTo avoids the page staying invisible if the animation is interrupted
            gsap.fromTo("#blogs-main", { y: 10, opacity: 0 }, { duration: 0.35, y: 0, opacity: 1, ease: "power2.out", clearProps: "all" });
        }

        function openCreateModal() {
            document.getElementById('create-blog-modal').classList.remove('hidden');
        }
        function closeCreateModal() {
            document.getElementById('create-blog-modal').classList.add('hidden');
        }

        function openShareModal(title, url) {
            // make the URL absolute in case url() returns a relative path
            const absUrl = new URL(url, window.location.origin).href;
            document.getElementById('share-url-input').value = absUrl;
            document.getElementById('copy-btn-text').innerText = 'Copy';

            const encodedUrl = encodeURIComponent(absUrl);
            const encodedTitle = encodeURIComponent(title + " | " + <?= json_encode($company['name'] ?? 'Startup') ?>);

            document.getElementById('share-wa').href = `https://api.whatsapp.com/send?text=${encodedTitle}%20${encodedUrl}`;
            document.getElementById('share-li').href = `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
            document.getElementById('share-tw').href = `https://twitter.com/intent/tweet?text=${encodedTitle}&url=${encodedUrl}`;

            document.getElementById('share-modal').classList.remove('hidden');
            refreshIcons();
        }

        function copyShareUrl() {
            const input = document.getElementById('share-url-input');
            input.select();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).catch(() => document.execCommand('copy'));
            } else {
                document.execCommand('copy');
            }
            document.getElementById('copy-btn-text').innerText = 'Copied!';
            setTimeout(() => {
                document.getElementById('copy-btn-text').innerText = 'Copy';
            }, 2500);
        }

        function handleBlogImageSelect(input) {
            const label = document.getElementById('blog-img-label');
            if (input.files && input.files[0]) {
                const f = input.files[0];
                const sizeMB = (f.size / (1024 * 1024)).toFixed(2);

                label.textContent = '';
                const name = document.createElement('span');
                name.className = 'text-indigo-600 font-bold';
                name.textContent = f.name;
                const size = document.createElement('span');
                size.className = 'text-slate-400 font-normal ml-1';
                size.textContent = `(${sizeMB} MB)`;
                label.appendChild(name);
                label.appendChild(size);

                let prev = document.getElementById('blog-img-preview');
                if (!prev) {
                    prev = document.createElement('img');
                    prev.id = 'blog-img-preview';
                    prev.alt = 'Selected cover preview';
                    prev.className = 'mt-3 mx-auto max-h-40 rounded-xl object-cover';
                    input.parentElement.appendChild(prev);
                }
                prev.src = URL.createObjectURL(f);
            }
        }

        // Close modals with Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeCreateModal();
                document.getElementById('share-modal').classList.add('hidden');
            }
        });
    </script>
</body>

</html>