/**
 * Nexora - LinkedIn Style Connection & Contact Request Controller
 * Handles Connect, Accept, Ignore, Withdraw, and Modals with smooth AJAX feedback
 */
(function () {
    'use strict';

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.getAttribute('content');
        const input = document.querySelector('input[name="csrf_token"]');
        if (input) return input.value;
        return '';
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-5 right-5 z-[99999] px-4 py-3 rounded-2xl shadow-xl text-xs sm:text-sm font-bold flex items-center gap-2.5 transition-all duration-300 transform translate-y-4 opacity-0 ${
            type === 'success' 
            ? 'bg-emerald-600 text-white' 
            : (type === 'error' ? 'bg-rose-600 text-white' : 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900')
        }`;
        
        const iconName = type === 'success' ? 'check-circle' : (type === 'error' ? 'alert-triangle' : 'info');
        toast.innerHTML = `<i data-lucide="${iconName}" class="w-4 h-4 flex-shrink-0"></i><span>${message}</span>`;
        document.body.appendChild(toast);

        if (window.lucide) window.lucide.createIcons();

        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-4', 'opacity-0');
        });

        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-4');
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // Modal creation and management
    let modalEl = null;

    function initConnectModal() {
        if (document.getElementById('nexora-connect-modal')) return;

        const modalHtml = `
        <div id="nexora-connect-modal" class="fixed inset-0 z-[9999] bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl p-6 sm:p-7 space-y-4 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="user-plus" class="w-5 h-5"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white truncate" id="connect-modal-title">Connect with Founder</h3>
                            <p class="text-xs text-slate-500 truncate" id="connect-modal-subtitle">Grow your verified startup network</p>
                        </div>
                    </div>
                    <button type="button" onclick="window.NexoraConnect.closeModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                    <img id="connect-modal-avatar" src="" alt="Avatar" class="w-12 h-12 rounded-full object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-extrabold text-slate-900 dark:text-white truncate" id="connect-modal-name">Name</div>
                        <div class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold truncate" id="connect-modal-headline">Role & Company</div>
                    </div>
                </div>

                <form id="nexora-connect-form" class="space-y-3.5">
                    <input type="hidden" id="connect-modal-target-id" value="">
                    <input type="hidden" id="connect-modal-company-id" value="">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Add a note (Optional)</label>
                            <span class="text-[11px] text-slate-400" id="connect-char-count">0/300</span>
                        </div>
                        <textarea id="connect-modal-message" rows="3" maxlength="300"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15 transition resize-none"
                            placeholder="Introduce yourself or share why you'd like to connect..."></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" onclick="window.NexoraConnect.closeModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" id="connect-modal-submit-btn" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            <span>Send Invitation</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>`;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = modalHtml;
        document.body.appendChild(wrapper.firstElementChild);
        modalEl = document.getElementById('nexora-connect-modal');

        const textarea = document.getElementById('connect-modal-message');
        const charCount = document.getElementById('connect-char-count');
        if (textarea && charCount) {
            textarea.addEventListener('input', () => {
                charCount.textContent = `${textarea.value.length}/300`;
            });
        }

        const form = document.getElementById('nexora-connect-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const targetId = document.getElementById('connect-modal-target-id').value;
                const companyId = document.getElementById('connect-modal-company-id').value;
                const message = textarea.value.trim();
                const submitBtn = document.getElementById('connect-modal-submit-btn');

                if (!targetId && !companyId) return;

                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-70');

                try {
                    const res = await window.NexoraConnect.sendRequest(targetId, message, companyId);
                    if (res.success) {
                        window.NexoraConnect.closeModal();
                        showToast(res.message || 'Connection invitation sent!', 'success');
                    } else {
                        showToast(res.error || 'Failed to send invitation', 'error');
                    }
                } catch (err) {
                    showToast('Connection error. Please try again.', 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-70');
                }
            });
        }

        if (window.lucide) window.lucide.createIcons();
    }

    window.NexoraConnect = {
        openModal: function (targetId, name, headline, avatarUrl, companyId = '') {
            initConnectModal();
            const modal = document.getElementById('nexora-connect-modal');
            if (!modal) return;

            document.getElementById('connect-modal-target-id').value = targetId || '';
            document.getElementById('connect-modal-company-id').value = companyId || '';
            document.getElementById('connect-modal-title').textContent = companyId ? `Connect with ${name}` : `Connect with ${name}`;
            document.getElementById('connect-modal-subtitle').textContent = companyId 
                ? `Invitation will be received by the founder managing ${name}`
                : `Grow your verified startup network`;
            document.getElementById('connect-modal-name').textContent = name;
            document.getElementById('connect-modal-headline').textContent = headline || (companyId ? 'Verified Startup Company' : 'Nexora Member');
            
            const avatar = document.getElementById('connect-modal-avatar');
            if (avatar) {
                avatar.src = avatarUrl || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100';
            }

            const textarea = document.getElementById('connect-modal-message');
            if (textarea) textarea.value = '';
            const charCount = document.getElementById('connect-char-count');
            if (charCount) charCount.textContent = '0/300';

            modal.classList.remove('hidden');
        },

        closeModal: function () {
            const modal = document.getElementById('nexora-connect-modal');
            if (modal) modal.classList.add('hidden');
        },

        sendRequest: async function (targetId, message = '', companyId = '') {
            const formData = new FormData();
            formData.append('action', 'send_request');
            if (targetId) formData.append('target_id', targetId);
            if (companyId) formData.append('target_company_id', companyId);
            formData.append('message', message);
            formData.append('csrf_token', getCsrfToken());

            const rootUrl = window.APP_URL || (window.location.origin + window.location.pathname.replace(/\/[\w\-]+\.php.*$/, ''));
            const apiUrl = (window.APP_URL ? window.APP_URL + '/' : '') + 'api/connect.php';

            const resp = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await resp.json();
            if (data.success) {
                const finalTargetId = data.target_id || targetId;
                const finalCompanyId = data.company_id || companyId;
                window.NexoraConnect.updateButtons(finalTargetId, 'pending_sent', finalCompanyId);
            }
            return data;
        },

        respond: async function (connectionId, action, targetUserId = null) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('connection_id', connectionId);
            if (targetUserId) formData.append('target_id', targetUserId);
            formData.append('csrf_token', getCsrfToken());

            const apiUrl = (window.APP_URL ? window.APP_URL + '/' : '') + 'api/connect.php';

            try {
                const resp = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await resp.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    if (targetUserId) {
                        window.NexoraConnect.updateButtons(targetUserId, data.status || (action === 'accept' ? 'connected' : 'none'));
                    }
                    // If on network.php, optionally fade out card
                    const card = document.getElementById(`invitation-card-${connectionId}`);
                    if (card) {
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => card.remove(), 300);
                    }
                } else {
                    showToast(data.error || 'Action failed', 'error');
                }
                return data;
            } catch (err) {
                showToast('Network error. Please try again.', 'error');
                return { success: false };
            }
        },

        withdraw: async function (targetId, connectionId = null) {
            if (!confirm('Are you sure you want to withdraw this connection invitation?')) return;
            const formData = new FormData();
            formData.append('action', 'withdraw');
            if (connectionId) formData.append('connection_id', connectionId);
            formData.append('target_id', targetId);
            formData.append('csrf_token', getCsrfToken());

            const apiUrl = (window.APP_URL ? window.APP_URL + '/' : '') + 'api/connect.php';
            try {
                const resp = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await resp.json();
                if (data.success) {
                    showToast(data.message, 'info');
                    window.NexoraConnect.updateButtons(targetId, 'none');
                } else {
                    showToast(data.error || 'Withdraw failed', 'error');
                }
            } catch (e) {
                showToast('Failed to withdraw request', 'error');
            }
        },

        updateButtons: function (targetId, newStatus, companyId = '') {
            const userSelector = targetId ? `[data-connect-user="${targetId}"]` : null;
            const companySelector = companyId ? `[data-connect-company="${companyId}"]` : null;
            const selector = [userSelector, companySelector].filter(Boolean).join(',');
            if (!selector) return;

            const btns = document.querySelectorAll(selector);
            btns.forEach(btn => {
                btn.setAttribute('data-connect-status', newStatus);
                const isCompany = btn.hasAttribute('data-connect-company');
                const compId = btn.getAttribute('data-connect-company');
                const connId = btn.getAttribute('data-connection-id') || '';

                if (newStatus === 'connected') {
                    btn.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold text-xs border border-emerald-200 dark:border-emerald-800 transition shadow-2xs';
                    btn.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i><span>Connected</span>`;
                    btn.onclick = () => {
                        showToast('You are connected! Open messages to chat.', 'info');
                    };
                } else if (newStatus === 'pending_sent') {
                    btn.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition shadow-2xs cursor-pointer';
                    btn.innerHTML = `<i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i><span>Pending</span>`;
                    btn.onclick = () => window.NexoraConnect.withdraw(targetId, connId);
                    btn.title = 'Click to withdraw invitation';
                } else {
                    btn.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer';
                    const iconName = isCompany ? 'building-2' : 'user-plus';
                    const defaultText = isCompany ? 'Connect' : 'Connect';
                    btn.innerHTML = `<i data-lucide="${iconName}" class="w-3.5 h-3.5"></i><span>${defaultText}</span>`;
                    const name = btn.getAttribute('data-connect-name') || (isCompany ? 'Company' : 'Member');
                    const headline = btn.getAttribute('data-connect-headline') || '';
                    const avatar = btn.getAttribute('data-connect-avatar') || '';
                    btn.onclick = () => window.NexoraConnect.openModal(targetId, name, headline, avatar, compId || '');
                }
            });

            if (window.lucide) window.lucide.createIcons();
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        initConnectModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            window.NexoraConnect.closeModal();
        }
    });
})();
