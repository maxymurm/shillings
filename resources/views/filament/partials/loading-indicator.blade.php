{{-- Global Loading Indicator for SPA Navigation --}}
<div id="shillings-loading-overlay" style="display:none;">
    <div id="shillings-progress-bar"></div>
    <div id="shillings-spinner-container">
        <div id="shillings-spinner"></div>
        <span id="shillings-loading-text">Loading...</span>
    </div>
</div>

<style>
    #shillings-loading-overlay {
        position: fixed;
        inset: 0;
        z-index: 99999;
        pointer-events: none;
    }

    #shillings-progress-bar {
        position: fixed;
        top: 0;
        left: 0;
        height: 3px;
        width: 0%;
        background: linear-gradient(90deg, #10b981, #34d399, #6ee7b7);
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.8), 0 0 5px rgba(16, 185, 129, 0.4);
        z-index: 100000;
    }

    #shillings-progress-bar.loading {
        width: 85%;
        transition: width 15s cubic-bezier(0.1, 0.05, 0, 1);
    }

    #shillings-progress-bar.complete {
        width: 100%;
        transition: width 0.15s ease;
    }

    #shillings-progress-bar.fade-out {
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    #shillings-spinner-container {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: none;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        background: rgba(0, 0, 0, 0.7);
        padding: 24px 32px;
        border-radius: 12px;
        backdrop-filter: blur(4px);
    }

    #shillings-spinner-container.visible {
        display: flex;
    }

    #shillings-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(16, 185, 129, 0.2);
        border-top-color: #10b981;
        border-radius: 50%;
        animation: shillings-spin 0.7s linear infinite;
    }

    #shillings-loading-text {
        color: #d1d5db;
        font-size: 13px;
        font-weight: 500;
        letter-spacing: 0.025em;
    }

    @keyframes shillings-spin {
        to { transform: rotate(360deg); }
    }

    /* Also add cursor feedback on sidebar nav clicks */
    .fi-sidebar-nav a:active,
    .fi-topbar a:active {
        cursor: wait !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        initShillingsLoader();
    });

    document.addEventListener('livewire:navigated', function() {
        initShillingsLoader();
    });

    function initShillingsLoader() {
        if (window.__shillingsLoaderInit) return;
        window.__shillingsLoaderInit = true;

        const overlay = document.getElementById('shillings-loading-overlay');
        const bar = document.getElementById('shillings-progress-bar');
        const spinner = document.getElementById('shillings-spinner-container');
        if (!overlay || !bar || !spinner) return;

        let spinnerTimeout = null;
        let isLoading = false;

        function startLoading() {
            if (isLoading) return;
            isLoading = true;

            bar.className = '';
            bar.style.width = '0%';
            overlay.style.display = 'block';
            document.body.style.cursor = 'wait';

            void bar.offsetHeight;
            bar.classList.add('loading');

            spinnerTimeout = setTimeout(() => {
                spinner.classList.add('visible');
            }, 600);
        }

        function stopLoading() {
            if (!isLoading) return;
            isLoading = false;

            clearTimeout(spinnerTimeout);
            spinner.classList.remove('visible');
            document.body.style.cursor = '';

            bar.classList.remove('loading');
            bar.classList.add('complete');

            setTimeout(() => {
                bar.classList.add('fade-out');
                setTimeout(() => {
                    overlay.style.display = 'none';
                    bar.className = '';
                    bar.style.width = '0%';
                }, 400);
            }, 200);
        }

        // SPA navigation events
        document.addEventListener('livewire:navigate', startLoading);
        document.addEventListener('livewire:navigated', stopLoading);

        // Livewire request lifecycle (component updates, form submits)
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('request', ({ respond, fail }) => {
                startLoading();
                respond(() => stopLoading());
                fail(() => stopLoading());
            });
        } else {
            document.addEventListener('livewire:init', () => {
                Livewire.hook('request', ({ respond, fail }) => {
                    startLoading();
                    respond(() => stopLoading());
                    fail(() => stopLoading());
                });
            });
        }

        // Fallback: also catch regular link clicks on sidebar nav items
        document.querySelectorAll('.fi-sidebar-nav a[href], .fi-topbar a[href]').forEach(link => {
            link.addEventListener('click', () => {
                setTimeout(() => { if (!isLoading) startLoading(); }, 50);
            });
        });
    }
</script>
