/**
 * La Caracola — Main TypeScript entry point
 * Initialises all interactive UI modules.
 */

import { initHeroSlider } from '@/components/hero-slider';
import { initMobileNav } from '@/components/mobile-nav';
import { initLangSwitcher } from '@/components/lang-switcher';
import { initGallery } from '@/components/gallery';
import { initBookingForm } from '@/components/booking-form';
import { initGuestPicker } from '@/components/guest-picker';
import { initPricingSimulator } from '@/components/pricing-simulator';
import { initMap } from '@/components/map';
import { initPhonePrefixSelects } from '@/components/phone-prefix-select';

// Run after DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initLangSwitcher();
    initHeroSlider();
    initGallery();
    initBookingForm();
    initGuestPicker();
    initPricingSimulator();
    initMap();
    initPhonePrefixSelects();
});
