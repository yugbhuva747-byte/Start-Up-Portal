<?php
/**
 * Investor Module: Syndicate Community & Venture Forum
 * Angel Network Discussions, Demo Day Schedules, and Peer Co-Investment
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Investor Syndicate Community';

$flash = get_flash();
$rsvpSuccess = false;

// Handle Demo Day RSVP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $eventTitle = trim($_POST['event_title'] ?? 'Venture Demo Day');
        log_audit($user['id'], 'RSVP_COMMUNITY_EVENT', 'users', $user['id'], "Investor RSVP'd to: {$eventTitle}");
        send_notification($user['id'], 'Demo Day Calendar Confirmation', "You are registered for '{$eventTitle}'. Calendar invite dispatched to {$user['email']}.", 'success', 'investor/community.php');
        set_flash('success', "Seat reserved for '{$eventTitle}'! The private webinar link was added to your schedule.");
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
                <div class="p-4 rounded-2xl text-sm font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center space-x-3 shadow-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 text-emerald-600"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Banner -->
            <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-[#0B1F3A] to-[#123B7A] text-white shadow-sm relative overflow-hidden">
                <div class="relative z-10 max-w-3xl">
                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-white/10 text-blue-300 text-xs font-bold uppercase tracking-wider mb-3">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        <span>Institutional Syndicate Network</span>
                    </div>
                    <h1 class="text-lg sm:text-xl font-bold tracking-tight">Investor Community & Demo Days</h1>
                    <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed">
                        Connect with verified family offices, angel networks, and venture funds. Co-invest in curated rounds and participate in private pitch sessions.
                    </p>
                </div>
            </div>

            <!-- Two Column: Upcoming Demo Days + Syndicate Discussion Streams -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Upcoming Demo Days (2 Cols) -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                            <i data-lucide="calendar" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                            <span>Upcoming Founder Pitch Sessions</span>
                        </h2>
                        <span class="text-xs text-slate-400 font-semibold">Live Q&A with CEOs</span>
                    </div>

                    <!-- Event Card 1 -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 flex flex-col items-center justify-center font-bold flex-shrink-0">
                                <span class="text-xs uppercase">OCT</span>
                                <span class="text-base leading-none">15</span>
                            </div>
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">DeepTech / AI Infra</span>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white mt-1">TechPulse AI & Autonomous Agents Pitch</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Featuring Founder Aarav Sharma • Evaluating Seed Extension round terms.</p>
                            </div>
                        </div>
                        <form action="<?= url('investor/community.php') ?>" method="POST" class="sm:flex-shrink-0">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="event_title" value="TechPulse AI Pitch Session">
                            <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-xl bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-2xs">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Reserve Seat (RSVP)</span>
                            </button>
                        </form>
                    </div>

                    <!-- Event Card 2 -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex flex-col items-center justify-center font-bold flex-shrink-0">
                                <span class="text-xs uppercase">OCT</span>
                                <span class="text-base leading-none">22</span>
                            </div>
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">CleanTech & Mobility</span>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white mt-1">NextGen Battery & Grid Storage Syndicate</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Co-investment syndicate lead by Tier-1 Cleantech Angels.</p>
                            </div>
                        </div>
                        <form action="<?= url('investor/community.php') ?>" method="POST" class="sm:flex-shrink-0">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="event_title" value="CleanTech Battery Syndicate Pitch">
                            <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-xl bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-2xs">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Reserve Seat (RSVP)</span>
                            </button>
                        </form>
                    </div>

                    <!-- Live Syndicate Deals -->
                    <div class="pt-4">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2 mb-3">
                            <i data-lucide="flame" class="w-4 h-4 text-amber-500"></i>
                            <span>Trending Syndicate Co-Investments</span>
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($syndicateRounds as $sr): ?>
                                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-xs text-slate-700 dark:text-slate-300">
                                            <?= strtoupper(substr($sr['company_name'], 0, 2)) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($sr['company_name']) ?></div>
                                            <div class="text-[10px] text-slate-400 truncate"><?= htmlspecialchars($sr['industry'] ?? 'Startup') ?></div>
                                        </div>
                                    </div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400">
                                        Raised: <strong class="text-slate-900 dark:text-white"><?= format_inr($sr['amount_raised']) ?></strong> of <?= format_inr($sr['target_amount']) ?>
                                    </div>
                                    <a href="<?= url('investor/startup_detail.php?id=' . encode_id($sr['company_id'])) ?>" class="mt-3 block text-center py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 text-[#123B7A] dark:text-blue-400 text-xs font-bold transition">
                                        Review Deal Pitch →
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Rail: Syndicate Sector Circles -->
                <div class="space-y-4">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <i data-lucide="layers" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                        <span>Sector Syndicates</span>
                    </h2>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-3.5">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">AI & Autonomous Systems</div>
                                <div class="text-[10px] text-slate-400">142 Accredited Investors</div>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">FinTech & Wealth Infra</div>
                                <div class="text-[10px] text-slate-400">98 Accredited Investors</div>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">Healthcare & BioDevices</div>
                                <div class="text-[10px] text-slate-400">76 Accredited Investors</div>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">SaaS & Enterprise Tools</div>
                                <div class="text-[10px] text-slate-400">114 Accredited Investors</div>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        </div>

                        <div class="pt-2 text-center">
                            <span class="text-[11px] text-slate-400">All syndicate members are SEBI/MCA verified</span>
                        </div>
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
