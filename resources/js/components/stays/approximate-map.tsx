import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { useEffect, useRef } from 'react';
import { useTranslation } from '@/lib/i18n';
import { cssVar } from '@/lib/css-var';

/**
 * The area of a property (≈500 m circle). The exact address is shared once a rental is confirmed.
 */
export default function ApproximateMap({
    lat,
    lng,
}: {
    lat: number;
    lng: number;
}) {
    const { t } = useTranslation();
    const container = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!container.current) {
            return;
        }

        const map = L.map(container.current, {
            scrollWheelZoom: false,
        }).setView([lat, lng], 15);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);
        L.circle([lat, lng], {
            radius: 450,
            color: cssVar('--brand'),
            weight: 1,
            fillOpacity: 0.12,
        }).addTo(map);

        return () => {
            map.remove();
        };
    }, [lat, lng]);

    return (
        <div
            ref={container}
            className="isolate z-0 h-72 w-full overflow-hidden rounded-xl"
            role="img"
            aria-label={t('Approximate location map')}
        />
    );
}
