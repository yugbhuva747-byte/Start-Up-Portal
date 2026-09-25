<?php
/**
 * Founder Module: Investor Deal Chat & Direct Messaging
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Investor Deal Communications';

$conversations = [];
$activeConv = null;
$messages = [];
$selectedConvId = 0;

if ($db) {
    // 1. Fetch all conversations involving this founder
    $cStmt = $db->prepare("
        SELECT c.*, 
               u.name as other_user_name, u.avatar_url as other_user_avatar, u.role as other_user_role,
               ip.investor_type, comp.name as company_name,
               (SELECT message_text FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_msg,
               (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_msg_time,
               (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_user_id != ? AND is_read = 0) as unread_count
        FROM conversations c
        JOIN users u ON (u.id = IF(c.user_one_id = ?, c.user_two_id, c.user_one_id))
        LEFT JOIN investor_profiles ip ON u.id = ip.user_id
        LEFT JOIN companies comp ON c.company_id = comp.id
        WHERE c.user_one_id = ? OR c.user_two_id = ?
        ORDER BY c.last_message_at DESC
    ");
    $cStmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
    $conversations = $cStmt->fetchAll();

    // Determine active conversation
    $reqConvHash = $_GET['conv'] ?? '';
    if (!empty($reqConvHash)) {
        $selectedConvId = hash_id_decode($reqConvHash);
    } elseif (!empty($conversations)) {
        $selectedConvId = (int)$conversations[0]['id'];
    }

    if ($selectedConvId > 0) {
        // Fetch active conversation details
        $acStmt = $db->prepare("
            SELECT c.*, u.id as other_user_id, u.name as other_user_name, u.avatar_url as other_user_avatar, ip.investor_type, comp.name as company_name
            FROM conversations c
            JOIN users u ON (u.id = IF(c.user_one_id = ?, c.user_two_id, c.user_one_id))
            LEFT JOIN investor_profiles ip ON u.id = ip.user_id
            LEFT JOIN companies comp ON c.company_id = comp.id
            WHERE c.id = ? AND (c.user_one_id = ? OR c.user_two_id = ?)
        ");
        $acStmt->execute([$user['id'], $selectedConvId, $user['id'], $user['id']]);
        $activeConv = $acStmt->fetch();

        if ($activeConv) {
            // Mark messages as read
            $db->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_user_id != ?")->execute([$selectedConvId, $user['id']]);

            // Fetch messages
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

            // Notify other user
            send_notification($activeConv['other_user_id'], 'New message from ' . $user['name'], substr($msgText, 0, 80), 'chat', 'investor/messages.php?conv=' . hash_id_encode($selectedConvId));

            header('Location: ' . url('founder/messages.php?conv=' . hash_id_encode($selectedConvId)));
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
    <title>Deal Chat • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <div class="flex-1 flex overflow-hidden">
            
            <!-- Conversation Threads List -->
            <div class="<?= $activeConv ? 'hidden md:flex' : 'flex' ?> w-full md:w-80 lg:w-96 border-r border-slate-200 bg-white flex-col">
                <div class="p-4 border-b border-slate-100">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">Deal Conversations</h2>
                    <div class="relative">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" placeholder="Search threads..." class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 outline-none focus:bg-white focus:border-indigo-600 transition">
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                    <?php if (empty($conversations)): ?>
                        <div class="p-8 text-center text-xs text-slate-400">
                            No active discussions. When investors discover your company and initiate chat, it will show here.
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $c): 
                            $isActive = $c['id'] == $selectedConvId;
                            $hash = hash_id_encode($c['id']);
                        ?>
                            <a href="<?= url('founder/messages.php?conv=' . $hash) ?>" class="p-3.5 block transition <?= $isActive ? 'bg-indigo-50/70 border-l-4 border-indigo-600' : 'hover:bg-slate-50' ?>">
                                <div class="flex items-start space-x-3">
                                    <img src="<?= $c['other_user_avatar'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80' ?>" class="w-9 h-9 rounded-full object-cover border border-slate-200">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($c['other_user_name']) ?></span>
                                            <span class="text-[10px] text-slate-400"><?= $c['last_msg_time'] ? date('H:i', strtotime($c['last_msg_time'])) : '' ?></span>
                                        </div>
                                        <div class="text-[10px] text-indigo-600 font-semibold uppercase tracking-wider"><?= htmlspecialchars($c['investor_type'] ?? 'Investor') ?></div>
                                        <div class="text-xs text-slate-500 truncate mt-0.5"><?= htmlspecialchars($c['last_msg'] ?? 'No messages yet') ?></div>
                                    </div>
                                    <?php if ($c['unread_count'] > 0): ?>
                                        <span class="w-4 h-4 rounded-full bg-indigo-600 text-white text-[9px] font-bold flex items-center justify-center">
                                            <?= $c['unread_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Chat Box -->
            <div class="<?= !$activeConv ? 'hidden md:flex' : 'flex' ?> flex-1 flex-col bg-[#FAFAFB]">
                <?php if ($activeConv): ?>
                    <!-- Active Header -->
                    <div class="h-14 px-3 sm:px-6 border-b border-slate-200 bg-white flex items-center justify-between">
                        <div class="flex items-center space-x-2 sm:space-x-3">
                            <a href="<?= url('founder/messages.php') ?>" class="md:hidden p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 transition" title="Back to discussions">
                                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            </a>
                            <img src="<?= $activeConv['other_user_avatar'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80' ?>" class="w-8 h-8 rounded-full object-cover border border-slate-200">
                            <div>
                                <div class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($activeConv['other_user_name']) ?></div>
                                <div class="text-[10px] text-emerald-600 flex items-center space-x-1 font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span><?= htmlspecialchars($activeConv['investor_type'] ?? 'Verified Investor') ?></span>
                                </div>
                            </div>
                        </div>
                        <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-full flex items-center space-x-1">
                            <i data-lucide="shield" class="w-3 h-3 text-slate-400"></i>
                            <span>Encrypted Deal Room</span>
                        </span>
                    </div>

                    <!-- Messages Log -->
                    <div class="flex-1 p-6 overflow-y-auto space-y-3" id="chat-messages-container">
                        <?php foreach ($messages as $msg): 
                            $isMe = $msg['sender_user_id'] == $user['id'];
                        ?>
                            <div class="flex items-end space-x-2 <?= $isMe ? 'justify-end' : 'justify-start' ?>">
                                <?php if (!$isMe): ?>
                                    <img src="<?= $msg['sender_avatar'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80' ?>" class="w-6 h-6 rounded-full object-cover mb-0.5">
                                <?php endif; ?>

                                <div class="max-w-md rounded-xl p-3 text-xs leading-relaxed <?= $isMe ? 'bg-indigo-600 text-white rounded-br-none shadow-sm' : 'bg-white border border-slate-200 text-slate-800 rounded-bl-none shadow-sm' ?>">
                                    <div class="break-words"><?= nl2br(htmlspecialchars($msg['message_text'])) ?></div>
                                    <div class="text-[9px] mt-1 text-right <?= $isMe ? 'text-indigo-200' : 'text-slate-400' ?>">
                                        <?= date('h:i A', strtotime($msg['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Input Form -->
                    <form action="<?= url('founder/messages.php?conv=' . hash_id_encode($selectedConvId)) ?>" method="POST" class="p-3.5 bg-white border-t border-slate-200 flex items-center space-x-2.5">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="text" name="message_text" required autocomplete="off" placeholder="Write a message or answer diligence queries..."
                               class="flex-1 px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition">
                        <button type="submit" class="p-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-sm transition">
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="flex-1 flex items-center justify-center p-6 text-center text-slate-400 text-xs">
                        Select a conversation to start chatting.
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
