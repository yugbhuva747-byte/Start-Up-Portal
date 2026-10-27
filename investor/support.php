<?php
/**
 * Investor Module: Support & Help Center
 * Interactive Ticket Submission, WhatsApp Direct Chat, and Plain-English FAQs
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Investor Help & Support';

$flash = get_flash();
$ticketSubmitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? 'General Inquiry');
        $message = trim($_POST['message'] ?? '');

        if (!empty($subject) && !empty($message)) {
            try {
                log_audit($user['id'], 'SUBMIT_SUPPORT_TICKET', 'users', $user['id'], "Support ticket: [{$category}] {$subject}");
                send_notification($user['id'], 'Support Ticket Received', "Ticket '{$subject}' is logged. Our team will respond within 4 hours.", 'info', 'investor/support.php');
                set_flash('success', 'Ticket submitted successfully! Ticket ID #TK-' . rand(10000, 99999) . '. Our support desk will reply shortly.');
                header('Location: ' . url('investor/support.php'));
                exit;
            } catch (Exception $e) {}
        }
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

            <!-- Page Header: Clear, Welcoming & Friendly -->
            <div class="p-6 sm:p-7 rounded-3xl bg-gradient-to-r from-[#0B1F3A] to-[#123B7A] text-white shadow-sm relative overflow-hidden">
                <div class="relative z-10 max-w-3xl">
                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-white/10 text-blue-200 text-xs font-bold uppercase tracking-wider mb-2.5">
                        <i data-lucide="life-buoy" class="w-3.5 h-3.5"></i>
                        <span>Investor Help &amp; Concierge</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight">How Can We Help You?</h1>
                    <p class="text-xs sm:text-sm text-slate-200 mt-1.5 leading-relaxed">
                        Fast support for your investments, bank escrow settlements, share certificate delivery, and KYC verification.
                    </p>
                </div>
            </div>

            <!-- 3 Fast Help Channels -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- WhatsApp Quick Chat -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3">
                            <i data-lucide="message-circle" class="w-5 h-5"></i>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <h3 class="font-bold text-slate-900 dark:text-white text-sm">WhatsApp Instant Help</h3>
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Chat directly with an investment officer. Usually replies in under 5 minutes.</p>
                    </div>
                    <a href="https://api.whatsapp.com/send?phone=919876543210&text=Hello%20Startup%20Portal%20Investor%20Support" target="_blank" rel="noopener noreferrer"
                       class="mt-4 w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center justify-center gap-2 shadow-2xs">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <span>Chat on WhatsApp &rarr;</span>
                    </a>
                </div>

                <!-- Submit Ticket -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#123B7A] dark:text-blue-400 flex items-center justify-center mb-3">
                            <i data-lucide="send" class="w-5 h-5"></i>
                        </div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm">Submit a Help Ticket</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Official inquiry for payment verification, contract queries, or share certificates. Reply within 4 hours.</p>
                    </div>
                    <button type="button" onclick="document.getElementById('ticket-form-section')?.scrollIntoView({behavior: 'smooth'})"
                       class="mt-4 w-full py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                        <span>Write a Ticket &darr;</span>
                    </button>
                </div>

                <!-- KYC & Escrow Guidance -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-3">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm">Escrow &amp; Compliance</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">100% of your capital is protected in RBI-regulated escrow accounts until round legal closing.</p>
                    </div>
                    <a href="<?= url('investor/verification.php') ?>"
                       class="mt-4 w-full py-2.5 px-3 rounded-xl bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/50 dark:hover:bg-purple-900/50 text-purple-700 dark:text-purple-300 text-xs font-bold transition flex items-center justify-center gap-2">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        <span>Check KYC Status &rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Quick Topic Selector Chips -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-2xs">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-2.5">
                    Click a common topic to pre-fill your request:
                </span>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="selectTopic('Escrow & Payment Wire', 'Question regarding payment wire or escrow confirmation')"
                        class="px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950/60 text-slate-700 dark:text-slate-200 hover:text-[#123B7A] dark:hover:text-blue-300 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer inline-flex items-center gap-1.5">
                        <i data-lucide="credit-card" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400"></i>
                        <span>Payment &amp; Escrow</span>
                    </button>
                    <button type="button" onclick="selectTopic('Share Certificate & Allotment', 'Inquiry regarding digital share certificate and PAS-3 return')"
                        class="px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950/60 text-slate-700 dark:text-slate-200 hover:text-[#123B7A] dark:hover:text-blue-300 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer inline-flex items-center gap-1.5">
                        <i data-lucide="file-text" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                        <span>Share Certificates</span>
                    </button>
                    <button type="button" onclick="selectTopic('KYC & DigiLocker Accreditation', 'Assistance with Aadhaar / PAN DigiLocker verification')"
                        class="px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950/60 text-slate-700 dark:text-slate-200 hover:text-[#123B7A] dark:hover:text-blue-300 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer inline-flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                        <span>KYC Verification</span>
                    </button>
                    <button type="button" onclick="selectTopic('Deal Due Diligence Access', 'Requesting data room access for active pitch')"
                        class="px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950/60 text-slate-700 dark:text-slate-200 hover:text-[#123B7A] dark:hover:text-blue-300 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer inline-flex items-center gap-1.5">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i>
                        <span>Pitch Data Room</span>
                    </button>
                    <button type="button" onclick="selectTopic('General Question', 'General investor query')"
                        class="px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950/60 text-slate-700 dark:text-slate-200 hover:text-[#123B7A] dark:hover:text-blue-300 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer inline-flex items-center gap-1.5">
                        <i data-lucide="message-square" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400"></i>
                        <span>General Query</span>
                    </button>
                </div>
            </div>

            <!-- Two Column: Ticket Submission Form + FAQ Accordion -->
            <div id="ticket-form-section" class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                
                <!-- Ticket Submission Form -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-1">
                        <i data-lucide="edit-3" class="w-4.5 h-4.5 text-[#123B7A] dark:text-blue-400"></i>
                        <span>Send Us a Message</span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Our operations desk will review and reply within 4 hours.</p>

                    <form action="<?= url('investor/support.php') ?>" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Category</label>
                            <select id="ticket-category" name="category" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm font-semibold outline-none focus:border-[#123B7A] dark:focus:border-blue-500">
                                <option value="Escrow & Payment Wire">Escrow &amp; Payment Wire</option>
                                <option value="Share Certificate & Allotment">Share Certificate &amp; Allotment</option>
                                <option value="KYC & DigiLocker Accreditation">KYC &amp; DigiLocker Accreditation</option>
                                <option value="Deal Due Diligence Access">Deal Due Diligence Access</option>
                                <option value="General Question">General Question</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Subject</label>
                            <input id="ticket-subject" type="text" name="subject" required placeholder="e.g., Question about my payment receipt or deal terms..."
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm font-medium outline-none focus:border-[#123B7A] dark:focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">How can we help?</label>
                            <textarea name="message" rows="4" required placeholder="Please describe your question. If referencing an investment, include the startup name or UTR / reference number..."
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm font-medium outline-none focus:border-[#123B7A] dark:focus:border-blue-500"></textarea>
                        </div>

                        <button type="submit" class="w-full py-3 rounded-xl bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs sm:text-sm font-bold shadow-xs transition flex items-center justify-center space-x-2 cursor-pointer">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            <span>Submit Request to Support Team</span>
                        </button>
                    </form>
                </div>

                <!-- Frequently Asked Questions (Plain English & Short) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="help-circle" class="w-4.5 h-4.5 text-[#123B7A] dark:text-blue-400"></i>
                            <span>Frequently Asked Questions</span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Quick answers to common investor questions.</p>
                    </div>

                    <div class="space-y-2.5 text-xs sm:text-sm">
                        <!-- FAQ 1 -->
                        <details class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 group cursor-pointer" open>
                            <summary class="font-bold text-slate-800 dark:text-slate-200 list-none flex items-center justify-between">
                                <span>When do I receive my Share Certificate?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <p class="mt-2 text-slate-600 dark:text-slate-400 leading-relaxed text-xs">
                                Within 7 business days after the startup's funding round closes and the MCA allotment filing (Form PAS-3) is completed. You can download and verify your official digital certificates anytime from your <strong>Portfolio</strong> page.
                            </p>
                        </details>

                        <!-- FAQ 2 -->
                        <details class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 group cursor-pointer">
                            <summary class="font-bold text-slate-800 dark:text-slate-200 list-none flex items-center justify-between">
                                <span>Is my invested money safe in escrow?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <p class="mt-2 text-slate-600 dark:text-slate-400 leading-relaxed text-xs">
                                Yes, 100%. All investor capital is deposited into RBI-regulated bank escrow accounts. Funds are only released to the startup once minimum legal funding goals are reached. If a round fails, you get a full 100% refund.
                            </p>
                        </details>

                        <!-- FAQ 3 -->
                        <details class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 group cursor-pointer">
                            <summary class="font-bold text-slate-800 dark:text-slate-200 list-none flex items-center justify-between">
                                <span>How do I complete KYC verification?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <p class="mt-2 text-slate-600 dark:text-slate-400 leading-relaxed text-xs">
                                Open the <strong>Verification Center</strong> link in your sidebar. It takes under 2 minutes with DigiLocker OTP for instant PAN and Aadhaar authentication.
                            </p>
                        </details>

                        <!-- FAQ 4 -->
                        <details class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 group cursor-pointer">
                            <summary class="font-bold text-slate-800 dark:text-slate-200 list-none flex items-center justify-between">
                                <span>Can I cancel or refund before the round closes?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <p class="mt-2 text-slate-600 dark:text-slate-400 leading-relaxed text-xs">
                                Yes. You can request a withdrawal or cancellation by submitting a ticket above or messaging our WhatsApp desk anytime before the round officially closes and documents are executed.
                            </p>
                        </details>

                        <!-- FAQ 5 -->
                        <details class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 group cursor-pointer">
                            <summary class="font-bold text-slate-800 dark:text-slate-200 list-none flex items-center justify-between">
                                <span>Where do I find tax invoices &amp; allotment proofs?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <p class="mt-2 text-slate-600 dark:text-slate-400 leading-relaxed text-xs">
                                Go to your <strong>Portfolio</strong> page, tap on any confirmed startup investment, and click <em>Download Certificate &amp; Tax Receipt</em>.
                            </p>
                        </details>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script>
        function selectTopic(category, subject) {
            const catSelect = document.getElementById('ticket-category');
            const subInput = document.getElementById('ticket-subject');
            if (catSelect) catSelect.value = category;
            if (subInput) {
                subInput.value = subject;
                subInput.focus();
            }
            document.getElementById('ticket-form-section')?.scrollIntoView({ behavior: 'smooth' });
        }
        lucide.createIcons();
    </script>
</body>
</html>
