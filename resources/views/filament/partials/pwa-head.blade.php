{{-- PWA Meta Tags and Manifest --}}
<link rel="manifest" href="/manifest.json" crossorigin="use-credentials">
<meta name="theme-color" content="#10b981">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Shillings">
<link rel="apple-touch-icon" href="/icons/icon-192x192.png">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">

{{-- Mobile-optimized Filament styles --}}
<style>
    /* Touch-friendly tap targets (min 44x44px per WCAG) */
    @media (max-width: 768px) {
        .fi-btn { min-height: 44px; min-width: 44px; }
        .fi-ta-row td { padding-top: 0.75rem !important; padding-bottom: 0.75rem !important; }
        .fi-sidebar { transition: transform 0.3s ease-in-out; }
        .fi-topbar { position: sticky; top: 0; z-index: 40; }

        /* Collapsible sidebar on mobile */
        .fi-sidebar-nav { padding-bottom: env(safe-area-inset-bottom, 0); }

        /* Table responsiveness: scroll horizontally */
        .fi-ta { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* Larger form inputs for touch */
        .fi-input, .fi-select, .fi-textarea {
            font-size: 16px !important;  /* Prevents zoom on iOS */
            min-height: 44px;
        }

        /* Stack form columns on mobile */
        .fi-fo-component-ctn { grid-template-columns: 1fr !important; }

        /* Bottom safe area padding for notched devices */
        .fi-page { padding-bottom: env(safe-area-inset-bottom, 1rem); }
    }

    /* PWA standalone adjustments */
    @media (display-mode: standalone) {
        body { padding-top: env(safe-area-inset-top, 0); }
    }

    /* Offline indicator styles */
    .shillings-offline-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #ef4444;
        color: white;
        text-align: center;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        z-index: 9999;
        transform: translateY(100%);
        transition: transform 0.3s ease-in-out;
        padding-bottom: calc(0.5rem + env(safe-area-inset-bottom, 0));
    }
    .shillings-offline-bar.visible { transform: translateY(0); }
    .shillings-sync-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        background: rgba(0,0,0,0.2);
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        margin-left: 0.5rem;
    }
</style>
