import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { useEffect, useRef } from 'react';
import { useTranslation } from '@/lib/i18n';

type Props = {
    latitude: number | null;
    longitude: number | null;
    onChange: (latitude: number, longitude: number) => void;
    disabled?: boolean;
};

// Center of Mexico when there is no pin yet.
const DEFAULT_CENTER: L.LatLngTuple = [23.6345, -102.5528];

const pinIcon = L.divIcon({
    className: '',
    html: '<span class="map-pin"></span>',
    iconSize: [22, 22],
    iconAnchor: [11, 11],
});

const round = (value: number) => Math.round(value * 1e6) / 1e6;

/**
 * Click the map or drag the pin to set the exact location (OpenStreetMap tiles, no API key).
 */
export default function MapPicker({
    latitude,
    longitude,
    onChange,
    disabled,
}: Props) {
    const { t } = useTranslation();
    const container = useRef<HTMLDivElement>(null);
    const map = useRef<L.Map | null>(null);
    const marker = useRef<L.Marker | null>(null);
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    useEffect(() => {
        if (!container.current || map.current) {
            return;
        }

        const hasPin = latitude !== null && longitude !== null;
        const instance = L.map(container.current).setView(
            hasPin ? [latitude, longitude] : DEFAULT_CENTER,
            hasPin ? 16 : 5,
        );

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(instance);

        const place = (position: L.LatLng) => {
            if (!marker.current) {
                marker.current = L.marker(position, {
                    icon: pinIcon,
                    draggable: !disabled,
                }).addTo(instance);
                marker.current.on('dragend', () => {
                    const moved = marker.current!.getLatLng();
                    onChangeRef.current(round(moved.lat), round(moved.lng));
                });
            } else {
                marker.current.setLatLng(position);
            }
        };

        if (hasPin) {
            place(L.latLng(latitude, longitude));
        }

        if (!disabled) {
            instance.on('click', (event: L.LeafletMouseEvent) => {
                place(event.latlng);
                onChangeRef.current(
                    round(event.latlng.lat),
                    round(event.latlng.lng),
                );
            });
        }

        map.current = instance;

        return () => {
            instance.remove();
            map.current = null;
            marker.current = null;
        };
        // The map is created once; later prop changes move the pin below.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (
            map.current &&
            marker.current &&
            latitude !== null &&
            longitude !== null
        ) {
            marker.current.setLatLng([latitude, longitude]);
        }
    }, [latitude, longitude]);

    return (
        <div className="space-y-1">
            <div
                ref={container}
                className="isolate z-0 h-72 w-full overflow-hidden rounded-xl border"
                role="application"
                aria-label={t('Map to place the property location')}
            />
            <p className="text-xs text-muted-foreground">
                {latitude !== null && longitude !== null
                    ? t('Pin at :lat, :lng. Drag it to adjust.', {
                          lat: latitude,
                          lng: longitude,
                      })
                    : t(
                          'Click on the map to place the pin at the exact entrance.',
                      )}
            </p>
        </div>
    );
}
