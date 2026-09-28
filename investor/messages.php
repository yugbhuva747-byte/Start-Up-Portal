<?php
/**
 * Investor Module: Founder Deal Messaging & Direct Communication
 * Professional Communication Interface, Vay Portal Typography, Clean Minimal 2-Pane Layout
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Founder Deal Messaging';

$conversations = [];
$activeConv = null;
$messages = [];
$selectedConvId = 0;

if ($db) {
    // 1. Handle auto-creation of conversation if founder & company query params passed
    if (isset($_GET['founder']) && isset($_GET['company'])) {
        $targetFounderId = hash_id_decode($_GET['founder']);
        $targetCompanyId = hash_id_decode($_GET['company']);

        if ($targetFounderId > 0 && $targetCompanyId > 0) {
            $chkC = $db->prepare("SELECT id FROM conversations WHERE ((user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?)) AND company_id = ?");
            $chkC->execute([$user['id'], $targetFounderId, $targetFounderId, $user['id'], $targetCompanyId]);
            $existingC = $chkC->fetch();

            if ($existingC) {
                $selectedConvId = (int) $existingC['id'];
            } else {
                $insC = $db->prepare("INSERT INTO conversations (user_one_id, user_two_id, company_id, last_message_at, created_at) VALUES (?, ?, ?, NOW(), NOW())");
                $insC->execute([$user['id'], $targetFounderId, $targetCompanyId]);
                $selectedConvId = (int) $db->lastInsertId();

                // Initial greeting message
                $insM = $db->prepare("INSERT INTO messages (conversation_id, sender_user_id, message_text, is_read, created_at) VALUES (?, ?, 'Hello, I reviewed your startup profile and would like to learn more about your traction and current round.', 0, NOW())");
                $insM->execute([$selectedConvId, $user['id']]);
            }
            header('Location: ' . url('investor/messages.php?conv=' . hash_id_encode($selectedConvId)));
            exit;
        }
    }

    // 2. Fetch all conversations involving this investor
    $cStmt = $db->prepare("
        SELECT c.*, 
               u.name as other_user_name, u.avatar_url as other_user_avatar, u.role as other_user_role,
               comp.name as company_name, comp.logo_url as company_logo,
               (SELECT message_text FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_msg,
               (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_msg_time,
               (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_user_id != ? AND is_read = 0) as unread_count
        FROM conversations c
        JOIN users u ON (u.id = IF(c.user_one_id = ?, c.user_two_id, c.user_one_id))
        LEFT JOIN companies comp ON c.company_id = comp.id
        WHERE c.user_one_id = ? OR c.user_two_id = ?
        ORDER BY c.last_message_at DESC
    ");
    $cStmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
    $conversations = $cStmt->fetchAll();

    $reqConvHash = $_GET['conv'] ?? '';
    if (!empty($reqConvHash)) {
        $selectedConvId = hash_id_decode($reqConvHash);
    } elseif (!empty($conversations)) {
        $selectedConvId = (int) $conversations[0]['id'];
    }

    if ($selectedConvId > 0) {
        $acStmt = $db->prepare("
            SELECT c.*, u.id as other_user_id, u.name as other_user_name, u.avatar_url as other_user_avatar, comp.name as company_name, comp.id as comp_id
            FROM conversations c
            JOIN users u ON (u.id = IF(c.user_one_id = ?, c.user_two_id, c.user_one_id))
            LEFT JOIN companies comp ON c.company_id = comp.id
            WHERE c.id = ? AND (c.user_one_id = ? OR c.user_two_id = ?)
        ");
        $acStmt->execute([$user['id'], $selectedConvId, $user['id'], $user['id']]);
        $activeConv = $acStmt->fetch();

        if ($activeConv) {
            $db->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_user_id != ?")->execute([$selectedConvId, $user['id']]);

            $mStmt = $db->prepare("
                SELECT m.*, u.name as sender_name, u.avatar_url as sender_avatar
                FROM messages m
                JOIN users u ON m.sender_user_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC
            ");
            $mStmt->execute([$selectedConvId]);
            $messages = $mStmt->fetchAll();
        }
    }
}

// Handle Send Message POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $activeConv) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $msgText = trim($_POST['message_text'] ?? '');
        if (!empty($msgText)) {
            $insM = $db->prepare("
                INSERT INTO messages (conversation_id, sender_user_id, message_text, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $insM->execute([$selectedConvId, $user['id'], $msgText]);

            $db->prepare("UPDATE conversations SET last_message_at = NOW() WHERE id = ?")->execute([$selectedConvId]);

            // Notify Founder
            send_notification($activeConv['other_user_id'], 'New message from ' . $user['name'], substr($msgText, 0, 80), 'chat', 'founder/messages.php?conv=' . hash_id_encode($selectedConvId));

            header('Location: ' . url('investor/messages.php?conv=' . hash_id_encode($selectedConvId)));
            exit;
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Vay Portal - Regular';
            src: local('Vay Portal - Regular'), local('Vay Portal'), local('Plus Jakarta Sans');
        }
        :root {
            --inv-primary: #123B7A;
            --inv-navy: #0B1F3A;
            --inv-secondary: #315F9F;
            --inv-light-blue: #EAF2FF;
            --inv-bg: #FAFBFD;
            --inv-text: #111827;
            --inv-text-sec: #667085;
            --inv-border: #E4E8EF;
        }
        body {
            font-family: 'Vay Portal - Regular', 'Vay Portal', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
            background-color: var(--inv-bg);
            color: var(--inv-text);
        }
    </style>
</head>

<body class="bg-[#FAFBFD] text-[#111827] flex min-h-screen antialiased">

    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <div class="flex-1 flex overflow-hidden">

            <!-- Conversation Threads List (Left Pane) -->
            <div class="<?= $activeConv ? 'hidden md:flex' : 'flex' ?> w-full md:w-80 lg:w-96 border-r border-[#E4E8EF] bg-white flex-col">
                <div class="p-4 border-b border-[#E4E8EF] space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xs font-bold text-[#0B1F3A] uppercase tracking-wider">Deal Conversations</h2>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#EAF2FF] text-[#123B7A]">
                            <?= count($conversations) ?>
                        </span>
                    </div>
                    <div class="relative">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-[#667085] absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" placeholder="Search discussions..."
                            class="w-full pl-9 pr-3 py-1.5 bg-[#FAFBFD] border border-[#E4E8EF] rounded-xl text-xs text-[#111827] placeholder-[#667085] outline-none focus:bg-white focus:border-[#123B7A] transition">
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-[#E4E8EF]">
                    <?php if (empty($conversations)): ?>
                        <div class="p-8 text-center text-xs text-[#667085] space-y-2">
                            <i data-lucide="message-square-off" class="w-8 h-8 text-[#667085]/30 mx-auto"></i>
                            <div class="font-bold text-[#0B1F3A]">No active discussions</div>
                            <p class="text-[11px] text-[#667085]">Explore the Discover marketplace to contact founders directly.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $c):
                            $isActive = $c['id'] == $selectedConvId;
                            $hash = hash_id_encode($c['id']);
                            ?>
                            <a href="<?= url('investor/messages.php?conv=' . $hash) ?>"
                                class="p-4 block transition <?= $isActive ? 'bg-[#EAF2FF]/60 border-l-4 border-[#123B7A]' : 'hover:bg-[#FAFBFD]' ?>">
                                <div class="flex items-start space-x-3">
                                    <img src="<?= $c['other_user_avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80' ?>"
                                        class="w-10 h-10 rounded-full object-cover border border-[#E4E8EF] bg-white flex-shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="text-xs font-bold text-[#0B1F3A] truncate"><?= htmlspecialchars($c['other_user_name']) ?></span>
                                            <span class="text-[10px] text-[#667085] flex-shrink-0"><?= $c['last_msg_time'] ? date('H:i', strtotime($c['last_msg_time'])) : '' ?></span>
                                        </div>
                                        <div class="text-[10px] text-[#123B7A] font-semibold uppercase tracking-wider mt-0.5 truncate">
                                            <?= htmlspecialchars($c['company_name'] ?? 'Startup') ?>
                                        </div>
                                        <div class="text-xs text-[#667085] truncate mt-1">
                                            <?= htmlspecialchars($c['last_msg'] ?? 'No messages yet') ?>
                                        </div>
                                    </div>
                                    <?php if ($c['unread_count'] > 0): ?>
                                        <span class="w-5 h-5 rounded-full bg-[#123B7A] text-white text-[10px] font-bold flex items-center justify-center flex-shrink-0">
                                            <?= $c['unread_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Chat Box (Right Pane) -->
            <div class="<?= !$activeConv ? 'hidden md:flex' : 'flex' ?> flex-1 flex-col bg-[#FAFBFD]">
                <?php if ($activeConv): ?>
                    <!-- Active Header -->
                    <div class="h-16 px-4 sm:px-6 border-b border-[#E4E8EF] bg-white flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <a href="<?= url('investor/messages.php') ?>"
                                class="md:hidden p-1.5 rounded-lg text-[#667085] hover:bg-[#FAFBFD] transition"
                                title="Back to discussions">
                                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            </a>
                            <img src="<?= $activeConv['other_user_avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80' ?>"
                                class="w-9 h-9 rounded-full object-cover border border-[#E4E8EF] bg-white">
                            <div>
                                <div class="text-xs font-bold text-[#0B1F3A] truncate">
                                    <?= htmlspecialchars($activeConv['other_user_name']) ?>
                                </div>
                                <div class="text-[10.5px] text-emerald-700 flex items-center space-x-1.5 font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Founder • <?= htmlspecialchars($activeConv['company_name'] ?? 'Startup') ?></span>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($activeConv['comp_id'])): ?>
                            <a href="<?= url('investor/startup_detail.php?id=' . hash_id_encode($activeConv['comp_id'])) ?>"
                                class="text-xs font-bold text-[#123B7A] hover:text-[#0B1F3A] flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-[#EAF2FF] transition">
                                <span>Deal Room</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Messages Stream -->
                    <div class="flex-1 p-6 overflow-y-auto space-y-4" id="chat-messages-container">
                        <div class="text-center py-2">
                            <span class="px-3 py-1 rounded-full bg-[#EAF2FF] text-[#123B7A] text-[10px] font-semibold border border-[#123B7A]/10">
                                End-to-End Diligence Encrypted • Direct Founder Channel
                            </span>
                        </div>

                        <?php foreach ($messages as $msg):
                            $isMe = $msg['sender_user_id'] == $user['id'];
                            ?>
                            <div class="flex items-end space-x-2.5 <?= $isMe ? 'justify-end' : 'justify-start' ?>">
                                <?php if (!$isMe): ?>
                                    <img src="<?= $msg['sender_avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80' ?>"
                                        class="w-7 h-7 rounded-full object-cover mb-0.5 border border-[#E4E8EF] flex-shrink-0">
                                <?php endif; ?>

                                <div class="max-w-md rounded-2xl p-3.5 text-xs leading-relaxed <?= $isMe ? 'bg-[#123B7A] text-white rounded-br-xs shadow-sm' : 'bg-white border border-[#E4E8EF] text-[#111827] rounded-bl-xs shadow-sm' ?>">
                                    <div class="break-words"><?= nl2br(htmlspecialchars($msg['message_text'])) ?></div>
                                    <div class="text-[9px] mt-1.5 text-right <?= $isMe ? 'text-[#EAF2FF]' : 'text-[#667085]' ?>">
                                        <?= date('h:i A', strtotime($msg['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Message Input Form -->
                    <form action="<?= url('investor/messages.php?conv=' . hash_id_encode($selectedConvId)) ?>" method="POST"
                        class="p-4 bg-white border-t border-[#E4E8EF] flex items-center space-x-3">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="text" name="message_text" required autocomplete="off"
                            placeholder="Type your diligence query or deal term inquiry..."
                            class="flex-1 px-4 py-2.5 bg-[#FAFBFD] border border-[#E4E8EF] focus:bg-white focus:border-[#123B7A] rounded-xl text-xs text-[#111827] placeholder-[#667085] outline-none transition">
                        <button type="submit"
                            class="px-4 py-2.5 bg-[#123B7A] hover:bg-[#0B1F3A] text-white rounded-xl shadow-sm transition flex items-center space-x-1.5 font-bold text-xs">
                            <span>Send</span>
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="flex-1 flex flex-col items-center justify-center p-8 text-center text-[#667085] space-y-3">
                        <i data-lucide="message-circle" class="w-12 h-12 text-[#667085]/30"></i>
                        <div class="text-sm font-bold text-[#0B1F3A]">Select a deal conversation</div>
                        <p class="text-xs text-[#667085] max-w-sm">Choose a conversation from the left to review deal correspondence with founders.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        lucide.createIcons();
        const container = document.getElementById('chat-messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    </script>
</body>

</html>