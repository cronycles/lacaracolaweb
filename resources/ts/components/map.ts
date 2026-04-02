/**
 * Map — initialise OpenStreetMap embed via Leaflet.
 * A native "open in maps" link is provided for navigation.
 */

const ZOOM = 15;

declare global {
    interface Window {
        L?: typeof import('leaflet');
    }
}

export function initMap(): void {
    const container = document.getElementById('map');
    if (!container || !window.L) return;

    const lat = Number.parseFloat(container.dataset.lat ?? '');
    const lng = Number.parseFloat(container.dataset.lng ?? '');

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

    const placeName = container.dataset.name ?? 'La Caracola';
    const placeAddress = container.dataset.address ?? '';

    const L = window.L;

    const map = L.map(container, {
        center: [lat, lng],
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

    L.marker([lat, lng], { icon })
        .addTo(map)
        .bindPopup(`<strong>${placeName}</strong><br>${placeAddress}`)
        .openPopup();

    // "Navigate here" opens the user's preferred maps app
    const navigateBtn = document.querySelector<HTMLAnchorElement>('#map-navigate');
    if (navigateBtn) {
        // Universal deep-link: works with Google Maps, Apple Maps, and others
        navigateBtn.href = `https://maps.google.com/?q=${lat},${lng}`;
        navigateBtn.target = '_blank';
        navigateBtn.rel = 'noopener noreferrer';
    }
}
