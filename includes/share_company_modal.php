<?php
/**
 * Reusable Share Company Modal
 * Allows Founders and Users to copy public link, scan QR, or share via WhatsApp, LinkedIn, Email.
 */
?>
<!-- Share Company Modal -->
<div id="shareCompanyModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-300" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all p-6 sm:p-8">
        
        <!-- Close Button -->
        <button type="button" onclick="closeShareModal()" class="absolute top-5 right-5 p-2 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- Header -->
        <div class="flex items-center space-x-3.5 mb-5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                </svg>
            </div>
            <div>
                <span class="text-[11px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400">Direct Shareable Link</span>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="shareModalTitle">Share Company Profile</h3>
            </div>
        </div>

        <!-- Info Card: Zero Login Required -->
        <div class="p-3.5 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-900/60 mb-5 flex items-start gap-3">
            <div class="p-1.5 rounded-lg bg-blue-600 text-white mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="text-xs text-blue-900 dark:text-blue-200 leading-relaxed font-medium">
                <strong>No Login Required:</strong> Anyone with this link (employees, advisors, co-founders, or investors) can view full company overview, team details, and data room documents without creating an account.
            </p>
        </div>

        <!-- Copy Link Input Group -->
        <div class="mb-5">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Public Share Link</label>
            <div class="flex items-center gap-2">
                <input type="text" id="shareModalInput" readonly class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-mono bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none select-all" value="">
                <button type="button" onclick="copyShareModalLink()" id="shareModalCopyBtn" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold flex items-center gap-1.5 transition flex-shrink-0 shadow-sm shadow-blue-500/25">
                    <svg class="w-4 h-4" id="copyIconDefault" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                    </svg>
                    <span id="copyBtnText">Copy</span>
                </button>
            </div>
        </div>

        <!-- Social & Instant Sharing Channels -->
        <div class="mb-6">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2.5">Instant Share Via</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                <!-- WhatsApp -->
                <a id="shareWhatsAppBtn" href="#" target="_blank" rel="noopener noreferrer" class="p-2.5 rounded-xl border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 flex items-center justify-center gap-2 text-xs font-bold transition">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>WhatsApp</span>
                </a>
                <!-- LinkedIn -->
                <a id="shareLinkedInBtn" href="#" target="_blank" rel="noopener noreferrer" class="p-2.5 rounded-xl border border-sky-200 bg-sky-50 hover:bg-sky-100 text-sky-800 dark:bg-sky-950/40 dark:border-sky-800 dark:text-sky-300 flex items-center justify-center gap-1.5 text-xs font-bold transition">
                    <span>LinkedIn</span>
                </a>
                <!-- Twitter/X -->
                <a id="shareTwitterBtn" href="#" target="_blank" rel="noopener noreferrer" class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-800 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 flex items-center justify-center gap-1.5 text-xs font-bold transition">
                    <span>X / Twitter</span>
                </a>
                <!-- Email -->
                <a id="shareEmailBtn" href="#" class="p-2.5 rounded-xl border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:border-indigo-800 dark:text-indigo-300 flex items-center justify-center gap-1.5 text-xs font-bold transition">
                    <span>Email</span>
                </a>
            </div>
        </div>

        <!-- QR Code & Open Preview Action -->
        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img id="shareModalQR" src="" alt="QR Code" class="w-12 h-12 rounded-lg border border-slate-200 bg-white p-1">
                <span class="text-[11px] text-slate-500 font-medium">Scan to open on mobile instantly</span>
            </div>
            <a id="shareModalPreviewLink" href="#" target="_blank" class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold flex items-center gap-1.5 transition">
                <span>Open Preview</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </a>
        </div>

    </div>
</div>

<script>
function openShareModal(shareUrl, companyName) {
    const modal = document.getElementById('shareCompanyModal');
    const input = document.getElementById('shareModalInput');
    const title = document.getElementById('shareModalTitle');
    const qrImg = document.getElementById('shareModalQR');
    const previewLink = document.getElementById('shareModalPreviewLink');

    if (companyName) {
        title.textContent = 'Share ' + companyName;
    }
    input.value = shareUrl;
    previewLink.href = shareUrl;

    // Generate dynamic QR image using public QR service
    qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(shareUrl);

    // Social URLs
    const encodedUrl = encodeURIComponent(shareUrl);
    const encodedText = encodeURIComponent('View ' + (companyName || 'our startup') + ' company dossier, pitch deck, and data room on NEXORA: ');
    
    document.getElementById('shareWhatsAppBtn').href = 'https://api.whatsapp.com/send?text=' + encodedText + encodedUrl;
    document.getElementById('shareLinkedInBtn').href = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodedUrl;
    document.getElementById('shareTwitterBtn').href = 'https://twitter.com/intent/tweet?text=' + encodedText + '&url=' + encodedUrl;
    document.getElementById('shareEmailBtn').href = 'mailto:?subject=' + encodeURIComponent((companyName || 'Company') + ' - Public Dossier & Data Room') + '&body=' + encodeURIComponent('Hi,\n\nPlease find the company profile, metrics, and documents here:\n\n' + shareUrl);

    modal.classList.remove('hidden');
}

function closeShareModal() {
    const modal = document.getElementById('shareCompanyModal');
    modal.classList.add('hidden');
    // Reset copy button text
    const btnText = document.getElementById('copyBtnText');
    if (btnText) btnText.textContent = 'Copy';
}

function copyShareModalLink() {
    const input = document.getElementById('shareModalInput');
    const btnText = document.getElementById('copyBtnText');
    
    input.select();
    input.setSelectionRange(0, 99999); // Mobile
    
    navigator.clipboard.writeText(input.value).then(() => {
        btnText.textContent = 'Copied!';
        setTimeout(() => {
            btnText.textContent = 'Copy';
        }, 3000);
    }).catch(() => {
        document.execCommand('copy');
        btnText.textContent = 'Copied!';
        setTimeout(() => {
            btnText.textContent = 'Copy';
        }, 3000);
    });
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeShareModal();
    }
});

// Close when clicking outside modal box
document.getElementById('shareCompanyModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeShareModal();
    }
});
</script>
