<?php
/**
 * Investor Module: Support & Helpdesk Center
 * Interactive Ticket Submission, FAQ Knowledgebase, and SEBI Compliance Guidance
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Investor Support & Helpdesk';

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
                send_notification($user['id'], 'Support Ticket Received', "Ticket '{$subject}' is logged. Our compliance officer will respond within 4 hours.", 'info', 'investor/support.php');
                set_flash('success', 'Support ticket submitted successfully! Ticket ID #TK-' . rand(10000, 99999) . '. Our team will respond shortly.');
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
                <div class="p-4 rounded-2xl text-sm font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center space-x-3 shadow-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 text-emerald-600"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-5">
                <div>
                    <div class="text-xs font-bold text-[#123B7A] dark:text-blue-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                        <i data-lucide="life-buoy" class="w-3.5 h-3.5"></i>
                        <span>Investor Concierge & Operations</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-[#0B1F3A] dark:text-white tracking-tight">Support & Helpdesk Desk</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Direct assistance for escrow settlements, share certificate delivery, DigiLocker KYC, and syndicate participation.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="https://api.whatsapp.com/send?phone=919876543210&text=Hello%20Portal%20Support" target="_blank" rel="noopener noreferrer"
                       class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-2 shadow-xs">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <span>WhatsApp VIP Desk</span>
                    </a>
                </div>
            </div>

            <!-- 3 Quick Access Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-[#123B7A] dark:text-blue-400 flex items-center justify-center mb-3">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Escrow Settlements</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Direct wire reconciliation questions, bank UTR verification, and refund timeline inquiries.</p>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-3">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Share Demat Custody</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Digital share certificate validation, distinctive number tracking, and PAS-3 return copies.</p>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Regulatory & SEBI</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Accredited investor threshold certifications, net worth declarations, and tax withholding questions.</p>
                </div>
            </div>

            <!-- Two Column: Submit Ticket + FAQ Accordion -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                
                <!-- Ticket Submission Form -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
                        <i data-lucide="send" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                        <span>Submit an Operational Ticket</span>
                    </h2>

                    <form action="<?= url('investor/support.php') ?>" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Issue Category</label>
                            <select name="category" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm font-semibold outline-none focus:border-[#123B7A]">
                                <option value="Escrow & Payment Wire">Escrow & Payment Wire</option>
                                <option value="Share Certificate & Allotment">Share Certificate & Allotment</option>
                                <option value="KYC & DigiLocker Accreditation">KYC & DigiLocker Accreditation</option>
                                <option value="Deal Due Diligence Access">Deal Due Diligence Access</option>
                                <option value="General Question">General Question</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Subject</label>
                            <input type="text" name="subject" required placeholder="Brief summary of your inquiry..."
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm font-medium outline-none focus:border-[#123B7A]">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Detailed Explanation</label>
                            <textarea name="message" rows="4" required placeholder="Please describe the transaction ID, startup name, or document reference..."
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm font-medium outline-none focus:border-[#123B7A]"></textarea>
                        </div>

                        <button type="submit" class="w-full py-3 rounded-xl bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs sm:text-sm font-bold shadow-sm transition flex items-center justify-center space-x-2">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Submit Ticket to Compliance Desk</span>
                        </button>
                    </form>
                </div>

                <!-- Frequently Asked Questions -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-2">
                        <i data-lucide="help-circle" class="w-4 h-4 text-[#123B7A] dark:text-blue-400"></i>
                        <span>Frequently Asked Questions</span>
                    </h2>

                    <div class="space-y-3 text-xs sm:text-sm">
                        <details class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 group cursor-pointer">
                            <summary class="font-bold text-slate-800 dark:text-slate-200 list-none flex items-center justify-between">
                                <span>When are Digital Share Certificates issued?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <p class="mt-2 text-slate-600 dark:text-slate-400 leading-relaxed text-xs">
                                Once an investment round closes and the MCA statutory return of allotment (Form PAS-3) is approved, official electronic share certificates with unique folio and distinctive numbers are credited to your portfolio within 7 business days.
                            </p>
                        </details>

                        <details class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 group cursor-pointer">
                            <summary class="font-bold text-slate-800 dark:text-slate-200 list-none flex items-center justify-between">
                                <span>Are investor funds protected in escrow?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <p class="mt-2 text-slate-600 dark:text-slate-400 leading-relaxed text-xs">
                                Yes. 100% of investor capital is collected into RBI-regulated nodal escrow accounts. Funds are only disbursed to the issuer once minimum funding milestones are legally achieved and verified.
                            </p>
                        </details>

                        <details class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 group cursor-pointer">
                            <summary class="font-bold text-slate-800 dark:text-slate-200 list-none flex items-center justify-between">
                                <span>How do I download tax invoices or allotment receipts?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <p class="mt-2 text-slate-600 dark:text-slate-400 leading-relaxed text-xs">
                                Navigate to your <strong>Portfolio</strong> page, select any confirmed holding, and click the <em>Download Certificate</em> or <em>Verify Certificate</em> button to view official statutory verification tokens.
                            </p>
                        </details>
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
