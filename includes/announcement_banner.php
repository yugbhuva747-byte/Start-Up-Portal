<?php
/**
 * Global Announcement Banner Component
 * Displays active broadcasts targeted to current user on their dashboard
 * Dismissible per user session via localStorage
 */
if (!isset($db)) {
    $db = get_db();
}
if (!isset($user)) {
    $user = current_user();
}

if ($db && $user):
    $userRole = $user['role'] ?? 'founder';

    // Check if user has pending KYC
    $kycStmt = $db->prepare("SELECT status FROM verification_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $kycStmt->execute([$user['id']]);
    $kycStatus = $kycStmt->fetchColumn();
    $isPendingKyc = (empty($kycStatus) || $kycStatus === 'pending');

    // Fetch active banners for this user
    $bannerQuery = "
        SELECT * FROM broadcasts 
        WHERE is_active = 1 
          AND show_banner = 1 
          AND (expires_at IS NULL OR expires_at > NOW())
          AND (
              target_audience = 'all' 
              OR target_audience = ?
    ";
    $bannerParams = [$userRole];

    if ($isPendingKyc) {
        $bannerQuery .= " OR target_audience = 'pending_kyc'";
    }

    $bannerQuery .= " ) ORDER BY 
        CASE priority 
            WHEN 'urgent' THEN 1 
            WHEN 'compliance' THEN 2 
            WHEN 'opportunity' THEN 3 
            ELSE 4 
        END ASC, created_at DESC LIMIT 3";

    $bStmt = $db->prepare($bannerQuery);
    $bStmt->execute($bannerParams);
    $activeBannersList = $bStmt->fetchAll();

    if (!empty($activeBannersList)):
?>
<div class="space-y-3 mb-6" id="platform-announcement-container">
    <?php foreach ($activeBannersList as $banner): 
        $bId = $banner['id'];
        $bPriority = $banner['priority'];

        $theme = match($bPriority) {
            'urgent' => [
                'bg' => 'bg-gradient-to-r from-rose-500/10 via-rose-500/5 to-transparent',
                'border' => 'border-rose-300',
                'icon_bg' => 'bg-rose-100 text-rose-700',
                'badge' => 'bg-rose-100 text-rose-800 border-rose-200',
                'icon' => 'alert-triangle',
                'text' => 'text-rose-950',
                'btn' => 'bg-rose-600 hover:bg-rose-700 text-white'
            ],
            'compliance' => [
                'bg' => 'bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent',
                'border' => 'border-amber-300',
                'icon_bg' => 'bg-amber-100 text-amber-800',
                'badge' => 'bg-amber-100 text-amber-900 border-amber-200',
                'icon' => 'shield-alert',
                'text' => 'text-amber-950',
                'btn' => 'bg-amber-600 hover:bg-amber-700 text-white'
            ],
            'opportunity' => [
                'bg' => 'bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent',
                'border' => 'border-emerald-300',
                'icon_bg' => 'bg-emerald-100 text-emerald-700',
                'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'icon' => 'sparkles',
                'text' => 'text-emerald-950',
                'btn' => 'bg-emerald-600 hover:bg-emerald-700 text-white'
            ],
            default => [
                'bg' => 'bg-gradient-to-r from-indigo-500/10 via-indigo-500/5 to-transparent',
                'border' => 'border-indigo-200',
                'icon_bg' => 'bg-indigo-100 text-indigo-700',
                'badge' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                'icon' => 'bell',
                'text' => 'text-slate-900',
                'btn' => 'bg-indigo-600 hover:bg-indigo-700 text-white'
            ]
        };
    ?>
    <aside id="banner-<?= $bId ?>" 
           class="p-4 rounded-2xl border <?= $theme['border'] ?> <?= $theme['bg'] ?> bg-white flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm relative transition-all duration-300">
        
        <div class="flex items-start sm:items-center space-x-3.5 flex-1 min-w-0">
            <?php if (!empty($banner['image_url'])): ?>
                <div class="relative flex-shrink-0">
                    <img src="<?= htmlspecialchars($banner['image_url']) ?>" alt="Announcement visual" 
                         class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover border border-slate-200/80 shadow-sm">
                    <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-lg <?= $theme['icon_bg'] ?> border border-white flex items-center justify-center shadow-xs">
                        <i data-lucide="<?= $theme['icon'] ?>" class="w-3 h-3"></i>
                    </div>
                </div>
            <?php else: ?>
                <div class="w-10 h-10 rounded-xl <?= $theme['icon_bg'] ?> flex items-center justify-center flex-shrink-0 shadow-sm">
                    <i data-lucide="<?= $theme['icon'] ?>" class="w-4 h-4"></i>
                </div>
            <?php endif; ?>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-black uppercase tracking-wider border <?= $theme['badge'] ?>">
                        <?= strtoupper($bPriority) ?>
                    </span>
                    <h3 class="text-xs md:text-sm font-bold <?= $theme['text'] ?>">
                        <?= htmlspecialchars($banner['title']) ?>
                    </h3>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed max-w-3xl">
                    <?= htmlspecialchars($banner['message']) ?>
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-2.5 flex-shrink-0 self-end md:self-center">
            <?php if (!empty($banner['cta_label']) && !empty($banner['cta_url'])): ?>
                <a href="<?= url($banner['cta_url']) ?>" 
                   class="px-4 py-2 rounded-xl <?= $theme['btn'] ?> text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                    <span><?= htmlspecialchars($banner['cta_label']) ?></span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            <?php endif; ?>

            <button onclick="dismissBanner(<?= $bId ?>)" 
                    class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition" 
                    title="Dismiss Announcement">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </aside>
    <?php endforeach; ?>
</div>

<script>
    // Check localStorage for dismissed banners
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const dismissed = JSON.parse(localStorage.getItem('dismissed_announcements') || '[]');
            dismissed.forEach(id => {
                const el = document.getElementById('banner-' + id);
                if (el) el.style.display = 'none';
            });
        } catch(e) {}
    });

    function dismissBanner(id) {
        const el = document.getElementById('banner-' + id);
        if (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(() => {
                el.style.display = 'none';
            }, 300);
            try {
                const dismissed = JSON.parse(localStorage.getItem('dismissed_announcements') || '[]');
                if (!dismissed.includes(id)) {
                    dismissed.push(id);
                    localStorage.setItem('dismissed_announcements', JSON.stringify(dismissed));
                }
            } catch(e) {}
        }
    }
</script>
<?php 
    endif;
endif;
?>
