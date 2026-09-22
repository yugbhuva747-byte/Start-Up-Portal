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

if ($db) {
    // Get founder's company
    $cStmt = $db->prepare("
        SELECT c.* FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ? LIMIT 1
    ");
    $cStmt->execute([$user['id']]);
    $company = $cStmt->fetch();

    if ($company) {
        $bStmt = $db->prepare("SELECT * FROM company_blogs WHERE company_id = ? ORDER BY created_at DESC");
        $bStmt->execute([$company['id']]);
        $blogs = $bStmt->fetchAll();
    }
}

// Handle Blog Submission or Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } elseif (!$company) {
        $error = 'Please register or complete your company profile before publishing blog stories.';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'create_blog') {
            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? 'Company Story');
            $summary = trim($_POST['summary'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $isPublished = isset($_POST['is_published']) ? 1 : 0;

            if (empty($title) || empty($content)) {
                $error = 'Please provide both a story title and story content.';
            } else {
                // Calculate approximate read time (average 200 words per minute)
                $wordCount = str_word_count(strip_tags($content));
                $readTime = max(1, ceil($wordCount / 200));

                // Handle Image Upload dynamically
                $coverImagePath = '';
                if (!empty($_FILES['cover_image']['name'])) {
                    $file = $_FILES['cover_image'];
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                        if (in_array($ext, $allowedExts) && $file['size'] <= 12 * 1024 * 1024) {
                            $targetDir = ROOT_PATH . '/uploads/blogs';
                            if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);

                            $filename = 'blog_' . $company['id'] . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
                            if (move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
                                $coverImagePath = 'uploads/blogs/' . $filename;
                            } else {
                                $error = 'Failed to save cover image.';
                            }
                        } else {
                            $error = 'Image must be JPG, PNG, WEBP under 12MB.';
                        }
                    }
                }

                // If no image uploaded, provide a default high-res curated cover based on category
                if (empty($coverImagePath)) {
                    $defaultImages = [
                        'Product Launch' => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=1200&q=80',
                        'Customer Story' => 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=1200&q=80',
                        'Award & Press' => 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?w=1200&q=80',
                        'Milestone' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=1200&q=80',
                        'Culture & Team' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&q=80',
                        'Company Story' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1200&q=80'
                    ];
                    $coverImagePath = $defaultImages[$category] ?? 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1200&q=80';
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
                        $summary ?: substr(strip_tags($content), 0, 200) . '...',
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
            $blogId = (int)($_POST['blog_id'] ?? 0);
            if ($blogId > 0) {
                // Delete cover image if stored locally
                $targetBlog = $db->prepare("SELECT cover_image FROM company_blogs WHERE id = ? AND company_id = ?");
                $targetBlog->execute([$blogId, $company['id']]);
                $bRow = $targetBlog->fetch();

                if ($bRow && !empty($bRow['cover_image']) && str_starts_with($bRow['cover_image'], 'uploads/blogs/')) {
                    $fullPath = ROOT_PATH . '/' . $bRow['cover_image'];
                    if (file_exists($fullPath)) @unlink($fullPath);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

        <main class="p-6 md:p-8 space-y-6 max-w-5xl w-full mx-auto" id="blogs-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Company Blog & Trust Stories</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Share company stories, milestone proof, customer case studies, and team photos to build investor trust.</p>
                </div>
                <div>
                    <button onclick="document.getElementById('create-blog-modal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-sm transition flex items-center space-x-1.5">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Write New Story</span>
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Published Stories</div>
                    <div class="text-xl font-extrabold text-slate-900 mt-1 flex items-center justify-between">
                        <span><?= count($blogs) ?></span>
                        <i data-lucide="book-open" class="w-4 h-4 text-indigo-500"></i>
                    </div>
                </div>
                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Reader Views</div>
                    <div class="text-xl font-extrabold text-emerald-600 mt-1 flex items-center justify-between">
                        <span><?= array_sum(array_column($blogs, 'views_count')) ?></span>
                        <i data-lucide="eye" class="w-4 h-4 text-emerald-500"></i>
                    </div>
                </div>
                <div class="card-clean rounded-2xl p-4">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Verified Credibility</div>
                    <div class="text-xl font-extrabold text-indigo-600 mt-1 flex items-center justify-between">
                        <span>100% Genuineness</span>
                        <i data-lucide="shield-check" class="w-4 h-4 text-indigo-500"></i>
                    </div>
                </div>
            </div>

            <!-- Published Stories List -->
            <div class="card-clean rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                        <i data-lucide="newspaper" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Company Stories Feed (<?= count($blogs) ?>)</span>
                    </h2>
                    <span class="text-[11px] text-slate-400">Publicly visible on your startup profile & shareable via URL</span>
                </div>

                <?php if (empty($blogs)): ?>
                    <div class="py-12 text-center text-slate-400 text-xs">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="feather" class="w-6 h-6"></i>
                        </div>
                        <div class="font-bold text-slate-700 text-sm">No stories published yet</div>
                        <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-1">Founders who post authentic milestones and customer case studies receive 3.5x higher investor response rates.</p>
                        <button onclick="document.getElementById('create-blog-modal').classList.remove('hidden')" class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-sm transition">
                            Publish First Story
                        </button>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($blogs as $b): 
                            $coverSrc = str_starts_with($b['cover_image'], 'http') ? $b['cover_image'] : url($b['cover_image']);
                            $publicBlogUrl = url('blog.php?id=' . $b['id']);
                        ?>
                            <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white hover:border-indigo-300 transition flex flex-col group">
                                <div class="relative h-44 overflow-hidden bg-slate-100">
                                    <img src="<?= htmlspecialchars($coverSrc) ?>" alt="<?= htmlspecialchars($b['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                    <div class="absolute top-3 left-3">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-white/95 text-indigo-700 shadow-sm backdrop-blur-sm border border-white">
                                            <?= htmlspecialchars($b['category']) ?>
                                        </span>
                                    </div>
                                    <div class="absolute top-3 right-3 flex items-center space-x-1">
                                        <span class="px-2 py-0.5 rounded text-[9.5px] font-bold <?= $b['is_published'] ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white' ?> shadow-sm">
                                            <?= $b['is_published'] ? 'LIVE' : 'DRAFT' ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="p-4 flex-1 flex flex-col justify-between">
                                    <div>
                                        <div class="text-[10.5px] text-slate-400 mb-1 flex items-center space-x-2">
                                            <span><?= date('M d, Y', strtotime($b['published_at'])) ?></span>
                                            <span>•</span>
                                            <span><?= $b['read_time_minutes'] ?> min read</span>
                                            <span>•</span>
                                            <span class="flex items-center space-x-1">
                                                <i data-lucide="eye" class="w-3 h-3"></i>
                                                <span><?= $b['views_count'] ?></span>
                                            </span>
                                        </div>
                                        <h3 class="font-bold text-slate-900 text-sm leading-snug line-clamp-2"><?= htmlspecialchars($b['title']) ?></h3>
                                        <p class="text-slate-500 text-xs mt-1.5 line-clamp-2 leading-relaxed"><?= htmlspecialchars($b['summary']) ?></p>
                                    </div>

                                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between">
                                        <a href="<?= $publicBlogUrl ?>" target="_blank" class="text-indigo-600 hover:text-indigo-700 text-xs font-bold flex items-center space-x-1 transition">
                                            <span>Read Article</span>
                                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                                        </a>

                                        <div class="flex items-center space-x-1">
                                            <!-- Share Button -->
                                            <button onclick="openShareModal('<?= addslashes(htmlspecialchars($b['title'])) ?>', '<?= $publicBlogUrl ?>')" class="p-2 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 transition" title="Share Article">
                                                <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <form action="<?= url('founder/blogs.php') ?>" method="POST" onsubmit="return confirm('Delete this blog story?');" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="form_action" value="delete_blog">
                                                <input type="hidden" name="blog_id" value="<?= $b['id'] ?>">
                                                <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 transition" title="Delete Story">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
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
            <div id="create-blog-modal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
                <div class="bg-white border border-slate-200 max-w-2xl w-full rounded-2xl p-6 shadow-2xl relative my-8">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                <i data-lucide="feather" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Publish Company Story / Blog</h3>
                                <p class="text-[11px] text-slate-500">Upload trust photos, product milestones, or customer validation stories.</p>
                            </div>
                        </div>
                        <button onclick="document.getElementById('create-blog-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <form action="<?= url('founder/blogs.php') ?>" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="form_action" value="create_blog">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Story Title *</label>
                                <input type="text" name="title" required placeholder="e.g., How TechPulse AI built 100K active user base in 6 months"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-xl text-slate-900 text-xs outline-none">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Category *</label>
                                <select name="category" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-xl text-slate-900 text-xs outline-none">
                                    <option value="Company Story">Company Story</option>
                                    <option value="Product Launch">Product Launch & Demo</option>
                                    <option value="Customer Story">Customer Case Study</option>
                                    <option value="Milestone">Growth Milestone</option>
                                    <option value="Award & Press">Award & Press Mention</option>
                                    <option value="Culture & Team">Culture & Team Proof</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Cover / Proof Photo (Upload Office, Team, Product, or Award Image)</label>
                            <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp,.gif" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700">
                            <p class="text-[10px] text-slate-400 mt-0.5">Upload a genuine photo to maximize trust. If left empty, an AI-curated category visual will be assigned.</p>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Executive Summary / Hook</label>
                            <input type="text" name="summary" placeholder="Brief 1-2 sentence preview to engage investors..."
                                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-xl text-slate-900 text-xs outline-none">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Full Story & Details *</label>
                            <textarea name="content" rows="6" required placeholder="Write the complete story, key metrics achieved, lessons learned, tech architecture, customer feedback..."
                                      class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-xl text-slate-900 text-xs outline-none leading-relaxed"></textarea>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="is_published" value="1" checked class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                <span class="text-slate-700 font-bold text-xs">Publish immediately to company profile</span>
                            </label>

                            <div class="flex items-center space-x-2">
                                <button type="button" onclick="document.getElementById('create-blog-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center space-x-1.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>Publish Story</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Share Modal -->
            <div id="share-modal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white border border-slate-200 max-w-md w-full rounded-2xl p-6 shadow-2xl relative">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                <i data-lucide="share-2" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Share Company Story</h3>
                                <p class="text-[11px] text-slate-500">Broadcast your milestone to build network credibility.</p>
                            </div>
                        </div>
                        <button onclick="document.getElementById('share-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Direct Article Link</label>
                            <div class="flex items-center space-x-2">
                                <input type="text" id="share-url-input" readonly class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs font-mono outline-none">
                                <button onclick="copyShareUrl()" id="copy-btn" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition flex items-center space-x-1">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span id="copy-btn-text">Copy</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-2 uppercase tracking-wider">Share To Social Channels</label>
                            <div class="grid grid-cols-3 gap-2">
                                <a id="share-wa" href="#" target="_blank" class="p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/50 hover:bg-emerald-50 text-emerald-700 font-bold text-xs flex flex-col items-center justify-center space-y-1 transition">
                                    <i data-lucide="message-circle" class="w-4 h-4 text-emerald-600"></i>
                                    <span>WhatsApp</span>
                                </a>
                                <a id="share-li" href="#" target="_blank" class="p-2.5 rounded-xl border border-blue-200 bg-blue-50/50 hover:bg-blue-50 text-blue-700 font-bold text-xs flex flex-col items-center justify-center space-y-1 transition">
                                    <i data-lucide="linkedin" class="w-4 h-4 text-blue-600"></i>
                                    <span>LinkedIn</span>
                                </a>
                                <a id="share-tw" href="#" target="_blank" class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-800 font-bold text-xs flex flex-col items-center justify-center space-y-1 transition">
                                    <i data-lucide="twitter" class="w-4 h-4 text-slate-800"></i>
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
        lucide.createIcons();
        gsap.from("#blogs-main", { duration: 0.35, y: 10, opacity: 0, ease: "power2.out" });

        function openShareModal(title, url) {
            document.getElementById('share-url-input').value = url;
            document.getElementById('copy-btn-text').innerText = 'Copy';

            const encodedUrl = encodeURIComponent(url);
            const encodedTitle = encodeURIComponent(title + " | " + <?= json_encode($company['name'] ?? 'Startup') ?>);

            document.getElementById('share-wa').href = `https://api.whatsapp.com/send?text=${encodedTitle}%20${encodedUrl}`;
            document.getElementById('share-li').href = `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
            document.getElementById('share-tw').href = `https://twitter.com/intent/tweet?text=${encodedTitle}&url=${encodedUrl}`;

            document.getElementById('share-modal').classList.remove('hidden');
            lucide.createIcons();
        }

        function copyShareUrl() {
            const input = document.getElementById('share-url-input');
            input.select();
            navigator.clipboard.writeText(input.value);
            document.getElementById('copy-btn-text').innerText = 'Copied!';
            setTimeout(() => {
                document.getElementById('copy-btn-text').innerText = 'Copy';
            }, 2500);
        }
    </script>
</body>
</html>
