<?php
/**
 * Investor Module: Demo Days & Live Pitches
 * Founder Pitch Sessions, 1-Click RSVPs, and Syndicate Co-Investments
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Demo Days & Live Pitches';

$flash = get_flash();

// Handle Demo Day RSVP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $eventTitle = trim($_POST['event_title'] ?? 'Founder Pitch Session');
        log_audit($user['id'], 'RSVP_COMMUNITY_EVENT', 'users', $user['id'], "Investor RSVP'd to: {$eventTitle}");
        send_notification($user['id'], 'Demo Day Seat Confirmed', "You're registered for '{$eventTitle}'. Private Google Meet link dispatched to {$user['email']}.", 'success', 'investor/community.php');
        set_flash('success', "Seat reserved for '{$eventTitle}'! The calendar invite and video link were sent to your email ({$user['email']}).");
        header('Location: ' . url('investor/community.php'));
        exit;
    }
}

// Fetch live syndicate companies for deal showcase
$syndicateRounds = [];
if ($db) {
    $stmt = $db->query("
        SELECT fr.*, c.name as company_name, c.industry, c.logo_url, c.city
        FROM funding_rounds fr
        JOIN companies c ON fr.company_id = c.id
        WHERE fr.status IN ('LIVE', 'PARTIALLY_FUNDED')
        ORDER BY fr.amount_raised DESC
        LIMIT 4
    ");
    $syndicateRounds = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/investor/head.php'; ?>
    <style>
        body { background-color: #F4F2EE; color: #111827; }
    </style>
</head>
<body class="bg-[#F4F2EE] text-[#111827] flex min-h-screen dark:bg-[#0B0F19] dark:text-slate-100">

    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 md:px-8 py-6 space-y-6 max-w-7xl mx-auto">
            
            <!-- Flash Message -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-semibold bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center space-x-3 shadow-xs">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Banner: Clear & Direct -->
            <div class="p-6 sm:p-7 rounded-3xl bg-gradient-to-r from-[#0B1F3A] to-[#123B7A] text-white shadow-sm relative overflow-hidden">
                <div class="relative z-10 max-w-3xl">
                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-white/10 text-blue-200 text-xs font-bold uppercase tracking-wider mb-2.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Live Pitch Sessions & Demo Days</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight">Watch Founders Pitch Live</h1>
                    <p class="text-xs sm:text-sm text-slate-200 mt-1.5 leading-relaxed">
                        RSVP in one click to watch curated founders pitch live, ask questions in real-time, and co-invest with top angel syndicates.
                    </p>

                    <!-- Quick Highlights Strip -->
                    <div class="mt-4 pt-4 border-t border-white/10 flex flex-wrap items-center gap-4 text-xs font-semibold text-blue-100">
                        <div class="flex items-center gap-1.5">
                            <i data-lucide="video" class="w-4 h-4 text-emerald-400"></i>
                            <span>2 Live Sessions Scheduled</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-blue-300"></i>
                            <span>Free Instant RSVP</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <i data-lucide="users" class="w-4 h-4 text-purple-300"></i>
                            <span>Co-Invest from ₹50,000</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3-Step Simple Guide (User-Friendly Strip) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex items-center space-x-3.5 shadow-2xs">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#123B7A] dark:text-blue-400 flex items-center justify-center font-black text-sm flex-shrink-0">
                        1
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Choose a Session</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">Pick founder pitches in your focus</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex items-center space-x-3.5 shadow-2xs">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-black text-sm flex-shrink-0">
                        2
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-900 dark:text-white">RSVP in 1-Click</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">Video room link sent to your email</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex items-center space-x-3.5 shadow-2xs">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center font-black text-sm flex-shrink-0">
                        3
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Co-Invest with Angels</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">Pool funds into vetted rounds</div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout: Upcoming Pitch Sessions + Sector Circles -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Upcoming Demo Days (2 Cols) -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                            <i data-lucide="radio" class="w-4.5 h-4.5 text-[#123B7A] dark:text-blue-400"></i>
                            <span>Upcoming Founder Pitch Sessions</span>
                        </h2>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">15m Pitch + 15m Q&amp;A</span>
                    </div>

                    <!-- Event Card 1 -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs hover:border-blue-300 dark:hover:border-blue-700 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start space-x-4">
                            <div class="w-13 h-13 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex flex-col items-center justify-center font-bold flex-shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                                <span class="text-[10px] uppercase tracking-wider font-extrabold text-indigo-400">OCT</span>
                                <span class="text-lg font-black leading-none mt-0.5">28</span>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">AI Infra &amp; Agents</span>
                                    <span class="text-[11px] text-slate-400 font-medium">5:00 PM IST · Zoom</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white mt-1">TechPulse AI — Autonomous Enterprise Workflows</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                    Presented by Aarav Sharma (ex-Google AI). Raising ₹2.5 Cr Seed round.
                                </p>
                            </div>
                        </div>
                        <form action="<?= url('investor/community.php') ?>" method="POST" class="sm:flex-shrink-0">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="event_title" value="TechPulse AI Pitch Session">
                            <button type="submit" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-xs cursor-pointer">
                                <i data-lucide="ticket" class="w-3.5 h-3.5"></i>
                                <span>Reserve Seat (Free)</span>
                            </button>
                        </form>
                    </div>

                    <!-- Event Card 2 -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs hover:border-emerald-300 dark:hover:border-emerald-700 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start space-x-4">
                            <div class="w-13 h-13 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex flex-col items-center justify-center font-bold flex-shrink-0 border border-emerald-100 dark:border-emerald-900/40">
                                <span class="text-[10px] uppercase tracking-wider font-extrabold text-emerald-400">NOV</span>
                                <span class="text-lg font-black leading-none mt-0.5">04</span>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">CleanTech Mobility</span>
                                    <span class="text-[11px] text-slate-400 font-medium">6:30 PM IST · Google Meet</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white mt-1">GreenGrid Battery — Rapid EV Charging Grid</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                    Presented by Neha Verma (IIT Bombay). Co-investment round lead by Tier-1 Angels.
                                </p>
                            </div>
                        </div>
                        <form action="<?= url('investor/community.php') ?>" method="POST" class="sm:flex-shrink-0">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="event_title" value="GreenGrid Battery Pitch Session">
                            <button type="submit" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-xs cursor-pointer">
                                <i data-lucide="ticket" class="w-3.5 h-3.5"></i>
                                <span>Reserve Seat (Free)</span>
                            </button>
                        </form>
                    </div>

                    <!-- Live Syndicate Deals -->
                    <div class="pt-3">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                                <i data-lucide="flame" class="w-4.5 h-4.5 text-amber-500"></i>
                                <span>Open Co-Investment Deals</span>
                            </h2>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Invest with top angel leads</span>
                        </div>

                        <?php if (!empty($syndicateRounds)): ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <?php foreach ($syndicateRounds as $sr): ?>
                                    <?php 
                                    $pct = ($sr['target_amount'] > 0) ? round(($sr['amount_raised'] / $sr['target_amount']) * 100) : 0;
                                    $pctClamped = min(100, max(0, $pct));
                                    ?>
                                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex flex-col justify-between">
                                        <div>
                                            <div class="flex items-center space-x-3 mb-2.5">
                                                <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-black text-xs text-slate-700 dark:text-slate-300">
                                                    <?= strtoupper(substr($sr['company_name'], 0, 2)) ?>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($sr['company_name']) ?></div>
                                                    <div class="text-[10px] text-slate-400 truncate"><?= htmlspecialchars($sr['industry'] ?? 'Startup') ?></div>
                                                </div>
                                            </div>

                                            <div class="space-y-1 mt-2">
                                                <div class="flex items-center justify-between text-xs">
                                                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Raised</span>
                                                    <span class="font-bold text-slate-900 dark:text-white"><?= format_inr($sr['amount_raised']) ?> <span class="text-slate-400 font-normal">/ <?= format_inr($sr['target_amount']) ?></span></span>
                                                </div>
                                                <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-300" style="width: <?= $pctClamped ?>%"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <a href="<?= url('investor/startup_detail.php?id=' . encode_id($sr['company_id'])) ?>" class="mt-3.5 block text-center py-2 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950/50 text-[#123B7A] dark:text-blue-400 text-xs font-bold transition">
                                            Review Deal Pitch &rarr;
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 text-center text-slate-400 text-xs">
                                No active syndicate rounds open today. Check back after next Demo Day!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Rail: Sector Circles & Network -->
                <div class="space-y-4">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <i data-lucide="layers" class="w-4.5 h-4.5 text-[#123B7A] dark:text-blue-400"></i>
                        <span>Investor Sector Groups</span>
                    </h2>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs space-y-3">
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Connect with angels who co-invest in specific startup categories:
                        </p>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">AI &amp; Autonomous Agents</div>
                                <div class="text-[10px] text-slate-400">142 Active Investors</div>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" title="Active deals right now"></span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">FinTech &amp; Wealth Infra</div>
                                <div class="text-[10px] text-slate-400">98 Active Investors</div>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">CleanTech &amp; Green Energy</div>
                                <div class="text-[10px] text-slate-400">76 Active Investors</div>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">Enterprise SaaS &amp; Cloud</div>
                                <div class="text-[10px] text-slate-400">114 Active Investors</div>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                        </div>

                        <div class="pt-2 text-center border-t border-slate-100 dark:border-slate-800">
                            <span class="text-[11px] text-slate-400 flex items-center justify-center gap-1">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-blue-500"></i>
                                All participants are verified angels
                            </span>
                        </div>
                    </div>

                    <!-- Host a demo day CTA -->
                    <div class="p-5 rounded-3xl bg-blue-50/70 dark:bg-slate-800/60 border border-blue-100 dark:border-slate-700/60 text-center space-y-2">
                        <div class="w-9 h-9 rounded-xl bg-white dark:bg-slate-700 text-[#123B7A] dark:text-blue-400 flex items-center justify-center mx-auto shadow-2xs">
                            <i data-lucide="mic" class="w-4 h-4"></i>
                        </div>
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white">Want to pitch a deal?</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">If you are leading a round or syndicate, submit it to our demo day committee.</p>
                        <a href="<?= url('investor/support.php') ?>" class="inline-block px-3 py-1.5 rounded-xl bg-white dark:bg-slate-700 text-[#123B7A] dark:text-blue-300 text-xs font-bold hover:shadow-2xs transition">
                            Submit a Deal &rarr;
                        </a>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
