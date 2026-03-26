/**
 * Map — initialise OpenStreetMap embed via Leaflet.
 * A native "open in maps" link is provided for navigation.
 */

// Coordinates for Via Aurelia 64, 17051 Andora (SV)
const LAT = 43.9552;
const LNG = 8.2533;
const ZOOM = 15;

declare global {
    interface Window {
        L?: typeof import('leaflet');
    }
}

export function initMap(): void {
    const container = document.getElementById('map');
    if (!container || !window.L) return;

    const L = window.L;

    const map = L.map(container, {
        center: [LAT, LNG],
        zoom: ZOOM,
        scrollWheelZoom: false, // Better UX on page scroll
    });

    // OpenStreetMap tile layer (free, no API key)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    // Custom marker using brand color
    const icon = L.divIcon({
        className: '',
        html: '<div style="width:32px;height:32px;background:#30596C;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3)"></div>',
        iconSize: [32, 32] as [number, number],
        iconAnchor: [16, 32] as [number, number],
    });

    L.marker([LAT, LNG], { icon })
        .addTo(map)
        .bindPopup('<strong>La Caracola</strong><br>Via Aurelia 64, Andora')
        .openPopup();

    // "Navigate here" opens the user's preferred maps app
    const navigateBtn = document.querySelector<HTMLAnchorElement>('#map-navigate');
    if (navigateBtn) {
        // Universal deep-link: works with Google Maps, Apple Maps, and others
        navigateBtn.href = `https://maps.google.com/?q=${LAT},${LNG}`;
        navigateBtn.target = '_blank';
        navigateBtn.rel = 'noopener noreferrer';
    }
}
