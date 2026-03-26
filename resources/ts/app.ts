/**
 * La Caracola — Main TypeScript entry point
 * Initialises all interactive UI modules.
 */

import { initHeroSlider } from '@/components/hero-slider';
import { initMobileNav } from '@/components/mobile-nav';
import { initLangSwitcher } from '@/components/lang-switcher';
import { initGallery } from '@/components/gallery';
import { initBookingForm } from '@/components/booking-form';
import { initMap } from '@/components/map';

// Run after DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initLangSwitcher();
    initHeroSlider();
    initGallery();
    initBookingForm();
    initMap();
});
