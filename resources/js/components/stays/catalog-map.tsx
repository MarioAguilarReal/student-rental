import 'leaflet/dist/leaflet.css';
import { router } from '@inertiajs/react';
import L from 'leaflet';
import { useEffect, useRef } from 'react';
import { useTranslation } from '@/lib/i18n';
import { formatMoney } from '@/lib/money';
import { show } from '@/routes/stays';
import type { ListingCardData } from '@/types';

type Props = {
    listings: ListingCardData[];
    campus: { name: string; lat: number; lng: number } | null;
    radiusKm: number;
    highlighted?: number | null;
};

const escape = (text: string) =>
    text.replace(/[&<>"']/g, (char) => `&#${char.charCodeAt(0)};`);

/**
 * Price pins at approximate locations (never the exact address) and the campus with its radius.
 */
export default function CatalogMap({
    listings,
    campus,
    radiusKm,
    highlighted,
}: Props) {
    const { t } = useTranslation();
    const container = useRef<HTMLDivElement>(null);
    const map = useRef<L.Map | null>(null);
    const layer = useRef<L.LayerGroup | null>(null);

    useEffect(() => {
        if (!container.current || map.current) {
            return;
        }

        map.current = L.map(container.current, {
            scrollWheelZoom: false,
        }).setView([23.6345, -102.5528], 5);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map.current);
        layer.current = L.layerGroup().addTo(map.current);

        return () => {
            map.current?.remove();
            map.current = null;
        };
    }, []);

    useEffect(() => {
        if (!map.current || !layer.current) {
            return;
        }

        layer.current.clearLayers();
        const points: L.LatLngExpression[] = [];

        if (campus) {
            L.circle([campus.lat, campus.lng], {
                radius: radiusKm * 1000,
                color: '#1b6c80',
                weight: 1,
                fillOpacity: 0.06,
            }).addTo(layer.current);
            L.marker([campus.lat, campus.lng], {
                icon: L.divIcon({
                    className: '',
                    html: `<span style="display:block;padding:2px 8px;border-radius:9999px;background:#1b6c80;color:#fff;font:600 12px/1.6 'IBM Plex Sans',sans-serif;white-space:nowrap">${escape(campus.name)}</span>`,
                    iconAnchor: [20, 10],
                }),
                keyboard: false,
            }).addTo(layer.current);
            points.push([campus.lat, campus.lng]);
        }

        for (const listing of listings) {
            const active = listing.id === highlighted;
            const marker = L.marker([listing.lat, listing.lng], {
                title: listing.title,
                icon: L.divIcon({
                    className: '',
                    html: `<span style="display:block;padding:2px 8px;border-radius:9999px;border:1px solid #cdd0d2;background:${active ? '#131416' : '#fff'};color:${active ? '#fff' : '#131416'};font:600 13px/1.6 'IBM Plex Sans',sans-serif;white-space:nowrap">${escape(formatMoney(listing.min_price_minor))}</span>`,
                    iconAnchor: [28, 12],
                }),
            });
            marker.on('click', () => router.visit(show.url(listing.id)));
            marker.addTo(layer.current);
            points.push([listing.lat, listing.lng]);
        }

        if (points.length > 0) {
            map.current.fitBounds(L.latLngBounds(points), {
                padding: [40, 40],
                maxZoom: 15,
            });
        }
    }, [listings, campus, radiusKm, highlighted]);

    return (
        <div
            ref={container}
            className="isolate z-0 size-full overflow-hidden rounded-xl"
            role="region"
            aria-label={t('Map of results')}
        />
    );
}
