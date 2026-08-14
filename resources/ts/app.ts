/**
 * La Caracola — Main TypeScript entry point
 * Initialises all interactive UI modules.
 */

import { initBookingForm } from '@/components/booking-form';
import { initGallery } from '@/components/gallery';
import { initLangSwitcher } from '@/components/lang-switcher';
import { initMap } from '@/components/map';
import { initMobileNav } from '@/components/mobile-nav';
import { initPhonePrefixSelects } from '@/components/phone-prefix-select';
import { initPricingSimulator } from '@/components/pricing-simulator';

// Run after DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initLangSwitcher();
    initGallery();
    initBookingForm();
    initPricingSimulator();
    initMap();
    initPhonePrefixSelects();
});
