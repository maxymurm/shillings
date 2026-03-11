import './bootstrap';
import { initPwa } from './pwa.js';

// Initialize PWA when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initPwa());
} else {
    initPwa();
}
