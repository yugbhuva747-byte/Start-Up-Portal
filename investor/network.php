<?php
/**
 * Investor Module: My Network & Connections (LinkedIn-Style Professional Networking)
 * Connect with venture-backed founders, co-investors, and syndicate leads.
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'My Network & Syndicate Connections';

$flash = get_flash();
$activeTab = $_GET['tab'] ?? 'connections';

$pendingRequests = get_pending_connection_requests((int) $user['id']);
$sentRequests = get_sent_connection_requests((int) $user['id']);
$myConnections = get_user_connections((int) $user['id']);
$pendingCount = count($pendingRequests);
$connectionsCount = count($myConnections);

if ($pendingCount > 0 && !isset($_GET['tab'])) {
    $activeTab = 'invitations';
}

// Recommended Founders & Investors to connect
$suggestedPeople = [];
if ($db) {
    try {
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.avatar_url, u.role, u.city, u.country, u.is_verified,
                   fp.designation as founder_designation, fp.bio as founder_bio,
                   c.name as company_name, c.logo_url as company_logo,
                   ip.investor_type
            FROM users u
            LEFT JOIN founder_profiles fp ON u.id = fp.user_id AND u.role = 'founder'
            LEFT JOIN company_founders cf ON u.id = cf.user_id
            LEFT JOIN companies c ON cf.company_id = c.id
            LEFT JOIN investor_profiles ip ON u.id = ip.user_id AND u.role = 'investor'
            WHERE u.id != ? AND u.status = 'active'
              AND u.id NOT IN (
                  SELECT receiver_id FROM user_connections WHERE requester_id = ?
                  UNION
                  SELECT requester_id FROM user_connections WHERE receiver_id = ?
              )
            GROUP BY u.id
            ORDER BY (u.role = 'founder') DESC, u.is_verified DESC, u.created_at DESC
            LIMIT 12
        ");
        $stmt->execute([$user['id'], $user['id'], $user['id']]);
        $suggestedPeople = $stmt->fetchAll();
    } catch (\Throwable $e) {
        $suggestedPeople = [];
    }
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
        body {
            background-color: #F4F2EE;
            color: #111827;
        }
    </style>
</head>

<body class="bg-[#F4F2EE] text-[#111827] flex min-h-screen dark:bg-[#0B0F19] dark:text-slate-100">

    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <!-- Investor Top Nav -->
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 space-y-6">

            <!-- Flash Alert -->
            <?php if ($flash): ?>
                <div
                    class="p-4 rounded-2xl text-xs sm:text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800' ?> flex items-center space-x-2.5 shadow-sm">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>"
                        class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Header Card -->
            <div
                class="rounded-3xl p-5 sm:p-7 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-[#EAF2FF] dark:bg-blue-950/60 text-[#123B7A] dark:text-blue-300 border border-[#123B7A]/20 dark:border-blue-800/80">
                            <i data-lucide="user-check" class="w-3.5 h-3.5 text-blue-600"></i>
                            Syndicate Co-Investment Network
                        </span>
                        <?php if ($pendingCount > 0): ?>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-500 text-white animate-pulse">
                                <?= $pendingCount ?> Invitation<?= $pendingCount > 1 ? 's' : '' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        My Network &amp; Syndicate Connections
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                        Direct 1st-degree connections with verified startup founders and fellow angels. Exchange
                        proprietary deal flow and collaborate on lead investments.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="p-3 sm:p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/80 dark:border-slate-800 text-center min-w-[100px]">
                        <span
                            class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400 block">Connections</span>
                        <span
                            class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white"><?= $connectionsCount ?></span>
                    </div>
                    <div
                        class="p-3 sm:p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/80 dark:border-slate-800 text-center min-w-[100px]">
                        <span
                            class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400 block">Pending</span>
                        <span
                            class="text-xl sm:text-2xl font-black text-blue-600 dark:text-blue-400"><?= $pendingCount ?></span>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto">
                <a href="<?= url('investor/network.php?tab=connections') ?>"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition flex items-center gap-2 whitespace-nowrap <?= $activeTab === 'connections' ? 'bg-[#123B7A] text-white shadow-xs dark:bg-blue-600' : 'bg-white dark:bg-[#111827] text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' ?>">
                    <i data-lucide="users" class="w-4 h-4"></i>
                    <span>My Connections</span>
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] <?= $activeTab === 'connections' ? 'bg-blue-500/50 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' ?>"><?= $connectionsCount ?></span>
                </a>

                <a href="<?= url('investor/network.php?tab=invitations') ?>"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition flex items-center gap-2 whitespace-nowrap <?= $activeTab === 'invitations' ? 'bg-[#123B7A] text-white shadow-xs dark:bg-blue-600' : 'bg-white dark:bg-[#111827] text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' ?>">
                    <i data-lucide="inbox" class="w-4 h-4"></i>
                    <span>Invitations</span>
                    <?php if ($pendingCount > 0): ?>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500 text-white font-black"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= url('investor/network.php?tab=sent') ?>"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition flex items-center gap-2 whitespace-nowrap <?= $activeTab === 'sent' ? 'bg-[#123B7A] text-white shadow-xs dark:bg-blue-600' : 'bg-white dark:bg-[#111827] text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' ?>">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Sent Invitations</span>
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] <?= $activeTab === 'sent' ? 'bg-blue-500/50 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' ?>"><?= count($sentRequests) ?></span>
                </a>

                <a href="<?= url('investor/network.php?tab=discover') ?>"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition flex items-center gap-2 whitespace-nowrap <?= $activeTab === 'discover' ? 'bg-[#123B7A] text-white shadow-xs dark:bg-blue-600' : 'bg-white dark:bg-[#111827] text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' ?>">
                    <i data-lucide="compass" class="w-4 h-4"></i>
                    <span>Discover Founders &amp; Angels</span>
                </a>
            </div>

            <!-- TAB 1: INVITATIONS RECEIVED -->
            <?php if ($activeTab === 'invitations'): ?>
                <div class="space-y-4">
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="mail-open" class="w-4 h-4 text-blue-600"></i>
                        <span>Pending Connection Requests (<?= $pendingCount ?>)</span>
                    </h2>

                    <?php if (empty($pendingRequests)): ?>
                        <div
                            class="p-12 rounded-3xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 text-center space-y-3 shadow-sm">
                            <div
                                class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center">
                                <i data-lucide="inbox" class="w-7 h-7"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">No pending invitations</h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto">When founders or investors request to connect,
                                they'll appear here.</p>
                            <a href="<?= url('investor/network.php?tab=discover') ?>"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 transition shadow-sm">
                                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                <span>Discover Founders &amp; Angels</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($pendingRequests as $req):
                                $reqUserHash = encode_id($req['user_id']);
                                $connHash = encode_id($req['connection_id']);
                                $headline = $req['role'] === 'founder'
                                    ? ($req['founder_designation'] ?? 'Founder') . (!empty($req['company_name']) ? ' at ' . $req['company_name'] : '')
                                    : ($req['investor_type'] ?? 'Angel Investor');
                                $profileUrl = $req['role'] === 'founder' ? url('founder/view.php?id=' . $reqUserHash) : url('investor/view.php?id=' . $reqUserHash);
                                ?>
                                <div id="invitation-card-<?= $req['connection_id'] ?>"
                                    class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 transition hover:border-slate-300 dark:hover:border-slate-700">
                                    <div class="flex items-start gap-3.5 min-w-0">
                                        <a href="<?= $profileUrl ?>" class="flex-shrink-0">
                                            <img src="<?= htmlspecialchars(get_avatar_url($req['avatar_url'] ?? '')) ?>"
                                                alt="<?= htmlspecialchars($req['name']) ?>"
                                                class="w-13 h-13 rounded-full object-cover object-center border border-slate-200 dark:border-slate-700 shadow-2xs">
                                        </a>

                                        <div class="space-y-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <a href="<?= $profileUrl ?>"
                                                    class="text-sm font-extrabold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition truncate">
                                                    <?= htmlspecialchars($req['name']) ?>
                                                </a>
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase <?= $req['role'] === 'founder' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' ?>">
                                                    <?= ucfirst($req['role']) ?>
                                                </span>
                                                <?php if ($req['is_verified']): ?>
                                                    <i data-lucide="badge-check" class="w-4 h-4 text-blue-600 flex-shrink-0"></i>
                                                <?php endif; ?>
                                            </div>

                                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                <?= htmlspecialchars($headline) ?> •
                                                <?= htmlspecialchars($req['city'] ?? 'India') ?>
                                            </p>

                                            <?php if (!empty($req['invite_note'])): ?>
                                                <div
                                                    class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 text-xs text-slate-700 dark:text-slate-300 italic max-w-xl">
                                                    "<?= htmlspecialchars($req['invite_note']) ?>"
                                                </div>
                                            <?php endif; ?>

                                            <span class="text-[10.5px] text-slate-400 block">
                                                Requested on <?= date('M d, Y • h:i A', strtotime($req['requested_at'])) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2.5 self-end md:self-center flex-shrink-0">
                                        <button type="button"
                                            onclick="window.NexoraConnect.respond('<?= $connHash ?>', 'decline', '<?= $reqUserHash ?>')"
                                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition cursor-pointer">
                                            Ignore
                                        </button>
                                        <button type="button"
                                            onclick="window.NexoraConnect.respond('<?= $connHash ?>', 'accept', '<?= $reqUserHash ?>')"
                                            class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span>Accept</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB 2: MY CONNECTIONS (1ST DEGREE) -->
            <?php elseif ($activeTab === 'connections'): ?>
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4 text-blue-600"></i>
                            <span><?= $connectionsCount ?> Connection<?= $connectionsCount !== 1 ? 's' : '' ?></span>
                        </h2>

                        <div class="relative w-full sm:w-64">
                            <i data-lucide="search"
                                class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input type="text" id="filter-connections-input" placeholder="Search connections..."
                                class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <?php if (empty($myConnections)): ?>
                        <div
                            class="p-12 rounded-3xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 text-center space-y-3 shadow-sm">
                            <div
                                class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center">
                                <i data-lucide="user-plus" class="w-7 h-7"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">You don't have any connections yet
                            </h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto">Connect with founders raising rounds and fellow
                                syndicate investors to exchange verified deal flow.</p>
                            <a href="<?= url('investor/network.php?tab=discover') ?>"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 transition shadow-sm">
                                <i data-lucide="compass" class="w-3.5 h-3.5"></i>
                                <span>Discover Founders &amp; Angels</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="connections-grid">
                            <?php foreach ($myConnections as $conn):
                                $connUserHash = encode_id($conn['user_id']);
                                $profileUrl = $conn['role'] === 'founder' ? url('founder/view.php?id=' . $connUserHash) : url('investor/view.php?id=' . $connUserHash);
                                $msgUrl = url('investor/messages.php');
                                $headline = $conn['role'] === 'founder'
                                    ? ($conn['founder_designation'] ?? 'Founder') . (!empty($conn['company_name']) ? ' • ' . $conn['company_name'] : '')
                                    : ($conn['investor_type'] ?? 'Angel Investor');
                                ?>
                                <div class="connection-card p-5 rounded-2xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-4 hover:border-slate-300 dark:hover:border-slate-700 transition"
                                    data-name="<?= strtolower(htmlspecialchars($conn['name'])) ?>"
                                    data-role="<?= strtolower(htmlspecialchars($headline)) ?>">
                                    <div class="flex items-start gap-3.5 min-w-0">
                                        <a href="<?= $profileUrl ?>" class="flex-shrink-0">
                                            <img src="<?= htmlspecialchars(get_avatar_url($conn['avatar_url'] ?? '')) ?>"
                                                alt="<?= htmlspecialchars($conn['name']) ?>"
                                                class="w-13 h-13 rounded-full object-cover object-center border border-slate-200 dark:border-slate-700 shadow-2xs">
                                        </a>

                                        <div class="min-w-0 flex-1 space-y-1">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <a href="<?= $profileUrl ?>"
                                                    class="text-sm font-extrabold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition truncate">
                                                    <?= htmlspecialchars($conn['name']) ?>
                                                </a>
                                                <?php if ($conn['is_verified']): ?>
                                                    <i data-lucide="badge-check" class="w-3.5 h-3.5 text-blue-600 flex-shrink-0"></i>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold truncate">
                                                <?= htmlspecialchars($headline) ?>
                                            </p>
                                            <p class="text-[11px] text-slate-400 truncate">
                                                <?= htmlspecialchars($conn['city'] ?? 'India') ?> • Connected
                                                <?= date('M Y', strtotime($conn['connected_at'])) ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Direct Contact Drawer -->
                                    <div
                                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 space-y-1.5 text-xs">
                                        <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300 truncate">
                                            <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"></i>
                                            <a href="mailto:<?= htmlspecialchars($conn['email']) ?>"
                                                class="hover:underline truncate"><?= htmlspecialchars($conn['email']) ?></a>
                                        </div>
                                        <?php if (!empty($conn['phone'])): ?>
                                            <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300 truncate">
                                                <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"></i>
                                                <span><?= htmlspecialchars($conn['phone']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div
                                        class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                        <a href="<?= $msgUrl ?>"
                                            class="flex-1 py-2 px-3 rounded-xl bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 text-blue-700 dark:text-blue-300 font-bold text-xs flex items-center justify-center gap-1.5 transition">
                                            <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                            <span>Message</span>
                                        </a>

                                        <button type="button"
                                            onclick="if (confirm('Disconnect from <?= addslashes($conn['name']) ?>?')) { window.NexoraConnect.withdraw('<?= $connUserHash ?>'); }"
                                            class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                                            title="Remove Connection">
                                            <i data-lucide="user-x" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB 3: SENT REQUESTS -->
            <?php elseif ($activeTab === 'sent'): ?>
                <div class="space-y-4">
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-blue-600"></i>
                        <span>Sent Invitations Pending Approval (<?= count($sentRequests) ?>)</span>
                    </h2>

                    <?php if (empty($sentRequests)): ?>
                        <div
                            class="p-12 rounded-3xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 text-center space-y-3 shadow-sm">
                            <div
                                class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center">
                                <i data-lucide="send" class="w-7 h-7"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">No outgoing invitations pending</h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto">When you invite other members, they will appear
                                here until accepted.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($sentRequests as $sReq):
                                $sUserHash = encode_id($sReq['user_id']);
                                $sConnHash = encode_id($sReq['connection_id']);
                                $headline = $sReq['role'] === 'founder'
                                    ? ($sReq['founder_designation'] ?? 'Founder') . (!empty($sReq['company_name']) ? ' at ' . $sReq['company_name'] : '')
                                    : ($sReq['investor_type'] ?? 'Angel Investor');
                                ?>
                                <div
                                    class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <img src="<?= htmlspecialchars(get_avatar_url($sReq['avatar_url'] ?? '')) ?>"
                                            alt="<?= htmlspecialchars($sReq['name']) ?>"
                                            class="w-12 h-12 rounded-full object-cover object-center border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                        <div class="space-y-0.5 min-w-0">
                                            <div class="text-sm font-extrabold text-slate-900 dark:text-white truncate">
                                                <?= htmlspecialchars($sReq['name']) ?>
                                            </div>
                                            <div class="text-xs text-blue-600 dark:text-blue-400 font-semibold truncate">
                                                <?= htmlspecialchars($headline) ?>
                                            </div>
                                            <div class="text-[11px] text-slate-400">
                                                Sent on <?= date('M d, Y', strtotime($sReq['requested_at'])) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button"
                                        onclick="window.NexoraConnect.withdraw('<?= $sUserHash ?>', '<?= $sConnHash ?>')"
                                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-slate-200 dark:border-slate-700 transition cursor-pointer self-end md:self-center">
                                        Withdraw Invitation
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB 4: DISCOVER PEOPLE -->
            <?php elseif ($activeTab === 'discover'): ?>
                <div class="space-y-4">
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="compass" class="w-4 h-4 text-blue-600"></i>
                        <span>Discover High-Growth Founders &amp; Co-Investors</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <?php foreach ($suggestedPeople as $p):
                            $pHash = encode_id($p['id']);
                            $headline = $p['role'] === 'founder'
                                ? ($p['founder_designation'] ?? 'Founder') . (!empty($p['company_name']) ? ' at ' . $p['company_name'] : '')
                                : ($p['investor_type'] ?? 'Angel Investor');
                            $profileUrl = $p['role'] === 'founder' ? url('founder/view.php?id=' . $pHash) : url('investor/view.php?id=' . $pHash);
                            $avatar = get_avatar_url($p['avatar_url'] ?? '');
                            ?>
                            <div
                                class="p-5 rounded-2xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between text-center space-y-3.5 hover:border-slate-300 dark:hover:border-slate-700 transition">
                                <div class="space-y-2.5">
                                    <a href="<?= $profileUrl ?>" class="inline-block relative">
                                        <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                                            class="w-16 h-16 rounded-full object-cover object-center mx-auto border-2 border-blue-100 dark:border-blue-900 shadow-2xs">
                                        <?php if ($p['is_verified']): ?>
                                            <div
                                                class="absolute bottom-0 right-0 w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center border-2 border-white dark:border-slate-900 shadow-xs">
                                                <i data-lucide="check" class="w-3 h-3"></i>
                                            </div>
                                        <?php endif; ?>
                                    </a>

                                    <div>
                                        <a href="<?= $profileUrl ?>"
                                            class="text-sm font-extrabold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 block truncate">
                                            <?= htmlspecialchars($p['name']) ?>
                                        </a>
                                        <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold truncate mt-0.5">
                                            <?= htmlspecialchars($headline) ?>
                                        </p>
                                        <p class="text-[11px] text-slate-400 truncate">
                                            <?= htmlspecialchars($p['city'] ?? 'India') ?>
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <?= render_connect_button((int) $p['id'], [
                                        'name' => $p['name'],
                                        'headline' => $headline,
                                        'avatar' => $avatar,
                                        'class' => 'w-full justify-center'
                                    ]) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        const filterInput = document.getElementById('filter-connections-input');
        if (filterInput) {
            filterInput.addEventListener('input', (e) => {
                const q = e.target.value.toLowerCase().trim();
                const cards = document.querySelectorAll('.connection-card');
                cards.forEach(card => {
                    const name = card.getAttribute('data-name') || '';
                    const role = card.getAttribute('data-role') || '';
                    if (name.includes(q) || role.includes(q)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>

</html>