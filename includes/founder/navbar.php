<?php
/**
 * Founder Top Navigation Bar Component
 * Clean, High-Legibility Theme with "Vay Portal", Sans-serif
 * Enhanced layout with large avatar profile card and quick search
 */
$currentUser = current_user();
$db = get_db();

$unreadCount = 0;
$notifs = [];
if ($db) {
    $nStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $nStmt->execute([$currentUser['id']]);
    $notifs = $nStmt->fetchAll();

    $cStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $cStmt->execute([$currentUser['id']]);
    $unreadCount = (int)$cStmt->fetchColumn();
}

// Ensure Founder Dark & Light Theme Controller is loaded
require_once __DIR__ . '/theme.php';
?>
<style>
    .founder-navbar, .founder-navbar * {
        font-family: "Vay Portal", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    .founder-navbar {
        position: sticky !important;
        top: 0 !important;
        z-index: 40 !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
    }
</style>
<header class="founder-navbar h-20 border-b border-slate-200 bg-white/95 backdrop-blur-md px-4 sm:px-6 md:px-8 flex items-center justify-between sticky top-0 z-40">
    <!-- Left Section: Mobile Menu + Quick Search -->
    <div class="flex items-center space-x-3 sm:space-x-4 min-w-0">
        <!-- Hamburger Menu Button (Mobile & Tablet) -->
        <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-2.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex-shrink-0 border border-slate-200" aria-label="Open sidebar menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <!-- Quick Platform Search -->
        <form action="<?= url('founder/funding_rounds.php') ?>" method="GET" class="relative hidden sm:block w-72 md:w-84 m-0">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
            <input type="text" name="q" placeholder="Search rounds, investors, documents..." 
                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 rounded-xl text-sm text-slate-800 placeholder-slate-400 outline-none transition" />
        </form>
    </div>

    <!-- Right Section: Verification Badge + Actions + Theme Toggle + Notifs + Profile Card -->
    <div class="flex items-center space-x-3 sm:space-x-4 flex-shrink-0">
        <!-- Verification Status Indicator -->
        <div class="hidden sm:flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-slate-50 border border-slate-200 text-xs">
            <span class="w-2.5 h-2.5 rounded-full <?= $currentUser['is_verified'] ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' ?>"></span>
            <span class="text-slate-700 font-semibold text-xs"><?= $currentUser['is_verified'] ? 'MCA & DigiLocker Verified' : 'KYC Under Review' ?></span>
        </div>

        <!-- Launch Round Quick Action Button -->
        <a href="<?= url('founder/funding_rounds.php?action=new') ?>" class="hidden md:inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-xs transition">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>New Round</span>
        </a>

        <!-- Dark / Light Theme Toggle Switcher -->
        <button id="founder-theme-toggle-btn" 
                onclick="toggleFounderTheme()" 
                type="button" 
                class="relative p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 transition flex items-center justify-center cursor-pointer shadow-xs group" 
                title="Toggle Dark / Light Theme" 
                aria-label="Toggle Dark / Light Theme">
            <!-- Sun Icon Wrapper (Active in Dark Mode) -->
            <span id="theme-sun-wrap" class="hidden flex items-center justify-center">
                <i data-lucide="sun" class="w-4 h-4 text-amber-400 group-hover:rotate-45 transition-transform duration-300"></i>
            </span>
            <!-- Moon Icon Wrapper (Active in Light Mode) -->
            <span id="theme-moon-wrap" class="flex items-center justify-center">
                <i data-lucide="moon" class="w-4 h-4 text-slate-600 group-hover:-rotate-12 transition-transform duration-300"></i>
            </span>
        </button>

        <!-- Notifications Bell -->
        <div class="relative" id="notif-dropdown-wrapper">
            <button onclick="toggleNotifs()" class="relative p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 transition" aria-label="Notifications">
                <i data-lucide="bell" class="w-4 h-4"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center">
                        <?= $unreadCount ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Dropdown Menu -->
            <div id="notif-menu" class="hidden absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-2xl shadow-xl p-4 z-50">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 text-sm font-bold text-slate-900">
                    <span>Notifications</span>
                    <span class="text-indigo-600 font-medium text-xs"><?= count($notifs) ?> recent</span>
                </div>
                <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
                    <?php if (empty($notifs)): ?>
                        <div class="py-6 text-center text-sm text-slate-400">No notifications yet</div>
                    <?php else: ?>
                        <?php foreach ($notifs as $n): ?>
                            <div class="py-3 text-sm <?= $n['is_read'] ? 'opacity-70' : 'font-semibold' ?>">
                                <div class="text-slate-800 text-sm leading-snug"><?= htmlspecialchars($n['title']) ?></div>
                                <div class="text-slate-500 text-xs mt-1 leading-relaxed"><?= htmlspecialchars($n['message']) ?></div>
                                <div class="text-[11px] text-slate-400 mt-1"><?= date('M d, H:i', strtotime($n['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="pt-3 mt-2 border-t border-slate-100 text-center">
                    <a href="<?= url('notifications.php') ?>" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">View all notifications →</a>
                </div>
            </div>
        </div>

        <!-- Founder User Profile Card with Large Avatar -->
        <a href="<?= url('founder/view.php') ?>" title="View My Profile" class="flex items-center space-x-3 p-1.5 pr-3.5 rounded-2xl border border-slate-200 hover:border-indigo-300 hover:bg-slate-50 transition group">
            <div class="relative flex-shrink-0">
                <img src="<?= $currentUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120' ?>" 
                     alt="<?= htmlspecialchars($currentUser['name']) ?>" 
                     class="w-11 h-11 sm:w-12 sm:h-12 rounded-full object-cover border-2 border-white ring-2 ring-indigo-500/20 group-hover:ring-indigo-500/50 transition shadow-xs">
                <span class="absolute bottom-0 right-0 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white"></span>
            </div>
            <div class="hidden md:block text-left">
                <div class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition leading-snug truncate max-w-[130px]">
                    <?= htmlspecialchars($currentUser['name']) ?>
                </div>
                <div class="text-[11px] font-semibold text-slate-400 leading-none mt-0.5">
                    Founder
                </div>
            </div>
        </a>
    </div>
</header>
<script>
    function toggleNotifs() {
        const menu = document.getElementById('notif-menu');
        if (menu) menu.classList.toggle('hidden');
    }

    function toggleFounderTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        const themeName = isDark ? 'dark' : 'light';
        try {
            localStorage.setItem('startup_portal_theme', themeName);
            document.cookie = "startup_portal_theme=" + themeName + ";path=/;max-age=31536000";
        } catch (e) {}
        syncFounderThemeIcons(isDark);
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: themeName } }));
    }

    function syncFounderThemeIcons(isDark) {
        const sun = document.getElementById('theme-sun-wrap');
        const moon = document.getElementById('theme-moon-wrap');
        const btn = document.getElementById('founder-theme-toggle-btn');
        if (sun && moon) {
            if (isDark) {
                sun.classList.remove('hidden');
                moon.classList.add('hidden');
                if (btn) {
                    btn.setAttribute('title', 'Switch to Light Theme');
                    btn.setAttribute('aria-label', 'Switch to Light Theme');
                }
            } else {
                sun.classList.add('hidden');
                moon.classList.remove('hidden');
                if (btn) {
                    btn.setAttribute('title', 'Switch to Dark Theme');
                    btn.setAttribute('aria-label', 'Switch to Dark Theme');
                }
            }
        }
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    // Sync icon state on page load
    (function () {
        const isDark = document.documentElement.classList.contains('dark');
        syncFounderThemeIcons(isDark);
    })();
    document.addEventListener('DOMContentLoaded', function () {
        const isDark = document.documentElement.classList.contains('dark');
        syncFounderThemeIcons(isDark);
    });
</script>
<?php include_once __DIR__ . '/../smooth_scroll.php'; ?>
