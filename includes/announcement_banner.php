<?php
/**
 * Global Announcement Banner Component - Executive Edition
 * Displays active broadcasts targeted to current user on their dashboard.
 * Designed with modern micro-gradients, animated status beacons, and sleek typography.
 * Behavioral rule: Dismissable for current view, but re-appears on page refresh until expired.
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

    // Fetch active banners for this user (strictly only non-expired, active banners)
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
<div class="space-y-3.5 mb-6" id="platform-announcement-container">
    <?php foreach ($activeBannersList as $banner): 
        $bId = (int)$banner['id'];
        $bPriority = strtolower($banner['priority'] ?? 'update');

        // Dynamic expiration indicator
        $expiryBadge = null;
        if (!empty($banner['expires_at'])) {
            $expTimestamp = strtotime($banner['expires_at']);
            $remainingSeconds = $expTimestamp - time();
            if ($remainingSeconds > 0) {
                $days = floor($remainingSeconds / 86400);
                $hours = floor(($remainingSeconds % 86400) / 3600);
                $mins = floor(($remainingSeconds % 3600) / 60);

                if ($days > 0) {
                    $expiryBadge = "Valid for " . $days . "d " . ($hours > 0 ? $hours . "h" : "");
                } elseif ($hours > 0) {
                    $expiryBadge = "Expires in " . $hours . "h " . ($mins > 0 ? $mins . "m" : "");
                } else {
                    $expiryBadge = "Expires in " . max(1, $mins) . "m";
                }
            }
        }

        // Theme palette configuration
        $theme = match($bPriority) {
            'urgent' => [
                'card_bg' => 'bg-gradient-to-r from-rose-50/90 via-white to-rose-50/40',
                'border' => 'border-rose-200/90',
                'ring' => 'ring-1 ring-rose-500/10',
                'shadow' => 'shadow-sm shadow-rose-950/5 hover:shadow-md hover:shadow-rose-950/10',
                'accent_bar' => 'bg-gradient-to-b from-rose-500 via-rose-600 to-rose-700',
                'badge_bg' => 'bg-rose-100/90 text-rose-800 border-rose-200/90',
                'badge_label' => 'URGENT NOTICE',
                'beacon_ping' => 'bg-rose-400',
                'beacon_dot' => 'bg-rose-600',
                'icon' => 'alert-triangle',
                'icon_bg' => 'bg-gradient-to-br from-rose-500 to-rose-700 text-white shadow-rose-500/25',
                'img_ring' => 'ring-rose-300',
                'btn' => 'bg-rose-600 hover:bg-rose-700 text-white shadow-rose-600/20',
                'btn_icon' => 'arrow-right',
                'dismiss_hover' => 'hover:bg-rose-100 text-slate-400 hover:text-rose-700'
            ],
            'compliance' => [
                'card_bg' => 'bg-gradient-to-r from-amber-50/90 via-white to-amber-50/40',
                'border' => 'border-amber-200/90',
                'ring' => 'ring-1 ring-amber-500/10',
                'shadow' => 'shadow-sm shadow-amber-950/5 hover:shadow-md hover:shadow-amber-950/10',
                'accent_bar' => 'bg-gradient-to-b from-amber-500 via-amber-600 to-amber-700',
                'badge_bg' => 'bg-amber-100/90 text-amber-900 border-amber-200/90',
                'badge_label' => 'COMPLIANCE BULLETIN',
                'beacon_ping' => 'bg-amber-400',
                'beacon_dot' => 'bg-amber-600',
                'icon' => 'shield-alert',
                'icon_bg' => 'bg-gradient-to-br from-amber-500 to-amber-700 text-white shadow-amber-500/25',
                'img_ring' => 'ring-amber-300',
                'btn' => 'bg-amber-600 hover:bg-amber-700 text-white shadow-amber-600/20',
                'btn_icon' => 'file-text',
                'dismiss_hover' => 'hover:bg-amber-100 text-slate-400 hover:text-amber-800'
            ],
            'opportunity' => [
                'card_bg' => 'bg-gradient-to-r from-emerald-50/90 via-white to-teal-50/40',
                'border' => 'border-emerald-200/90',
                'ring' => 'ring-1 ring-emerald-500/10',
                'shadow' => 'shadow-sm shadow-emerald-950/5 hover:shadow-md hover:shadow-emerald-950/10',
                'accent_bar' => 'bg-gradient-to-b from-emerald-500 via-teal-600 to-teal-700',
                'badge_bg' => 'bg-emerald-100/90 text-emerald-900 border-emerald-200/90',
                'badge_label' => 'NEW OPPORTUNITY',
                'beacon_ping' => 'bg-emerald-400',
                'beacon_dot' => 'bg-emerald-600',
                'icon' => 'sparkles',
                'icon_bg' => 'bg-gradient-to-br from-emerald-500 to-teal-700 text-white shadow-emerald-500/25',
                'img_ring' => 'ring-emerald-300',
                'btn' => 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/20',
                'btn_icon' => 'arrow-right',
                'dismiss_hover' => 'hover:bg-emerald-100 text-slate-400 hover:text-emerald-800'
            ],
            default => [
                'card_bg' => 'bg-gradient-to-r from-indigo-50/90 via-white to-blue-50/40',
                'border' => 'border-indigo-200/90',
                'ring' => 'ring-1 ring-indigo-500/10',
                'shadow' => 'shadow-sm shadow-indigo-950/5 hover:shadow-md hover:shadow-indigo-950/10',
                'accent_bar' => 'bg-gradient-to-b from-indigo-500 via-indigo-600 to-blue-600',
                'badge_bg' => 'bg-indigo-100/90 text-indigo-900 border-indigo-200/90',
                'badge_label' => 'PLATFORM ANNOUNCEMENT',
                'beacon_ping' => 'bg-indigo-400',
                'beacon_dot' => 'bg-indigo-600',
                'icon' => 'bell-ring',
                'icon_bg' => 'bg-gradient-to-br from-indigo-500 to-blue-600 text-white shadow-indigo-500/25',
                'img_ring' => 'ring-indigo-300',
                'btn' => 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-600/20',
                'btn_icon' => 'arrow-right',
                'dismiss_hover' => 'hover:bg-indigo-100 text-slate-400 hover:text-indigo-800'
            ]
        };
    ?>
    <aside id="banner-<?= $bId ?>" 
           class="group relative overflow-hidden rounded-2xl border <?= $theme['border'] ?> <?= $theme['ring'] ?> <?= $theme['card_bg'] ?> p-4 md:p-4.5 <?= $theme['shadow'] ?> transition-all duration-300">
        
        <!-- Left vertical accent spine -->
        <div class="absolute left-0 top-0 bottom-0 w-1.5 <?= $theme['accent_bar'] ?>"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pl-2.5">
            
            <!-- Left block: Visual Media + Content -->
            <div class="flex items-start sm:items-center space-x-3.5 sm:space-x-4 flex-1 min-w-0">
                
                <?php if (!empty($banner['image_url'])): ?>
                    <!-- Professional Studio Thumbnail with subtle ring & badge -->
                    <div class="relative flex-shrink-0">
                        <div class="w-13 h-13 sm:w-16 sm:h-16 rounded-xl overflow-hidden ring-2 <?= $theme['img_ring'] ?> shadow-sm bg-slate-100">
                            <img src="<?= htmlspecialchars($banner['image_url']) ?>" 
                                 alt="Notice visual" 
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                 onerror="this.onerror=null; this.parentElement.style.display='none';">
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-md <?= $theme['icon_bg'] ?> flex items-center justify-center ring-2 ring-white shadow-xs">
                            <i data-lucide="<?= $theme['icon'] ?>" class="w-3 h-3"></i>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Emblem Monogram Icon Box -->
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl <?= $theme['icon_bg'] ?> flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="<?= $theme['icon'] ?>" class="w-5 h-5"></i>
                    </div>
                <?php endif; ?>

                <!-- Text & Metadata Stack -->
                <div class="min-w-0 flex-1">
                    
                    <!-- Metadata Header Row -->
                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                        
                        <!-- Pulsing Beacon + Priority Pill -->
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wider border <?= $theme['badge_bg'] ?> shadow-2xs">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?= $theme['beacon_ping'] ?> opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 <?= $theme['beacon_dot'] ?>"></span>
                            </span>
                            <span><?= $theme['badge_label'] ?></span>
                        </span>

                        <!-- Target Audience Tag -->
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold text-slate-500 bg-white/70 border border-slate-200/70">
                            <i data-lucide="users" class="w-2.5 h-2.5 text-slate-400"></i>
                            <span><?= ucfirst(htmlspecialchars($banner['target_audience'])) ?> Channel</span>
                        </span>

                        <!-- Expiry Countdown Badge (if scheduled) -->
                        <?php if ($expiryBadge): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold text-slate-600 bg-white/70 border border-slate-200/70" title="Notice expiration window">
                                <i data-lucide="clock" class="w-2.5 h-2.5 text-slate-400"></i>
                                <span><?= $expiryBadge ?></span>
                            </span>
                        <?php endif; ?>

                    </div>

                    <!-- Title -->
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight leading-snug">
                        <?= htmlspecialchars($banner['title']) ?>
                    </h3>

                    <!-- Body Message -->
                    <p class="text-xs text-slate-600 leading-relaxed mt-0.5 line-clamp-2 sm:line-clamp-none max-w-3xl">
                        <?= htmlspecialchars($banner['message']) ?>
                    </p>
                </div>
            </div>

            <!-- Right block: Call-to-Action & Session Dismiss Button -->
            <div class="flex items-center space-x-2.5 flex-shrink-0 self-end md:self-center pt-2 md:pt-0 border-t md:border-t-0 border-slate-200/60 w-full md:w-auto justify-between md:justify-end">
                
                <?php if (!empty($banner['cta_label']) && !empty($banner['cta_url'])): ?>
                    <a href="<?= url($banner['cta_url']) ?>" 
                       class="px-4 py-2 rounded-xl <?= $theme['btn'] ?> text-xs font-bold transition-all duration-200 flex items-center space-x-1.5 shadow-sm hover:translate-x-0.5">
                        <span><?= htmlspecialchars($banner['cta_label']) ?></span>
                        <i data-lucide="<?= $theme['btn_icon'] ?>" class="w-3.5 h-3.5"></i>
                    </a>
                <?php endif; ?>

                <!-- Professional Dismiss Button with tooltip -->
                <button type="button" 
                        onclick="dismissBanner(<?= $bId ?>)" 
                        class="p-2 rounded-xl <?= $theme['dismiss_hover'] ?> transition-all duration-200 hover:rotate-90 flex items-center justify-center" 
                        title="Dismiss for now (will re-show on page refresh)">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

        </div>
    </aside>
    <?php endforeach; ?>
</div>

<script>
    // Ensure any previously saved permanent localStorage hide is cleared,
    // guaranteeing that active banners will re-appear whenever user refreshes the page
    try {
        localStorage.removeItem('dismissed_announcements');
    } catch(e) {}

    // Initialize Lucide icons for the announcement banner
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
    }
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            lucide.createIcons();
        }
    });

    /**
     * Smoothly dismiss banner for the current viewing session.
     * When user refreshes the page, the banner will automatically show again until expired.
     */
    function dismissBanner(id) {
        const el = document.getElementById('banner-' + id);
        if (!el) return;

        el.style.transition = 'all 0.35s cubic-bezier(0.4, 0, 0.2, 1)';
        el.style.opacity = '0';
        el.style.transform = 'scale(0.97) translateY(-8px)';
        el.style.maxHeight = el.offsetHeight + 'px';

        requestAnimationFrame(() => {
            el.style.maxHeight = '0px';
            el.style.marginTop = '0px';
            el.style.marginBottom = '0px';
            el.style.paddingTop = '0px';
            el.style.paddingBottom = '0px';
            el.style.borderWidth = '0px';
        });

        setTimeout(() => {
            el.remove();
            const container = document.getElementById('platform-announcement-container');
            if (container && container.querySelectorAll('aside').length === 0) {
                container.style.display = 'none';
            }
        }, 360);
    }
</script>
<?php 
    endif;
endif;
?>
