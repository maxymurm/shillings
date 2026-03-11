{{-- Offline Status Indicator & Sync Badge --}}
<div id="shillings-offline-bar" class="shillings-offline-bar">
    <span>⚡ You are offline — changes will sync when reconnected</span>
    <span id="shillings-sync-badge" class="shillings-sync-badge" style="display: none;">
        <span id="sync-count">0</span> pending
    </span>
</div>

{{-- PWA Install Prompt --}}
<div id="shillings-install-prompt" style="display: none; position: fixed; bottom: 1rem; right: 1rem; background: #18181b; border: 1px solid #3f3f46; border-radius: 0.75rem; padding: 1rem; z-index: 9998; max-width: 320px; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
    <p style="color: #e4e4e7; margin: 0 0 0.75rem; font-size: 0.875rem;">Install Shillings for offline access and a native app experience.</p>
    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
        <button onclick="dismissInstallPrompt()" style="background: transparent; border: 1px solid #3f3f46; color: #a1a1aa; padding: 0.375rem 0.75rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.8125rem;">Later</button>
        <button onclick="acceptInstallPrompt()" style="background: #10b981; border: none; color: white; padding: 0.375rem 0.75rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.8125rem;">Install</button>
    </div>
</div>

<script>
    // Offline indicator
    const offlineBar = document.getElementById('shillings-offline-bar');
    const syncBadge = document.getElementById('shillings-sync-badge');
    const syncCount = document.getElementById('sync-count');

    document.addEventListener('pwa:connection-change', (e) => {
        if (e.detail.online) {
            offlineBar.classList.remove('visible');
        } else {
            offlineBar.classList.add('visible');
        }
    });

    document.addEventListener('pwa:sync-badge', (e) => {
        const { pending, conflicts, pendingTransactions } = e.detail;
        const total = pending + conflicts + pendingTransactions;
        if (total > 0) {
            syncCount.textContent = total;
            syncBadge.style.display = 'inline-flex';
        } else {
            syncBadge.style.display = 'none';
        }
    });

    // Install prompt
    const installPrompt = document.getElementById('shillings-install-prompt');

    document.addEventListener('pwa:install-available', () => {
        if (!localStorage.getItem('pwa_install_dismissed')) {
            installPrompt.style.display = 'block';
        }
    });

    function dismissInstallPrompt() {
        installPrompt.style.display = 'none';
        localStorage.setItem('pwa_install_dismissed', Date.now());
    }

    async function acceptInstallPrompt() {
        installPrompt.style.display = 'none';
        // Import and call promptInstall from pwa.js
        const { promptInstall } = await import('/resources/js/pwa.js');
        if (promptInstall) await promptInstall();
    }

    // Show offline bar immediately if already offline
    if (!navigator.onLine) {
        offlineBar.classList.add('visible');
    }
</script>
