import { enhanceSlidebookThemePicker } from './slidebook/theme-picker';
import { enhanceSlidebookAuthoring } from './slidebook/authoring';

function initializeSlidebookEnhancements() {
    enhanceSlidebookThemePicker();
    enhanceSlidebookAuthoring();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSlidebookEnhancements, { once: true });
} else {
    initializeSlidebookEnhancements();
}
