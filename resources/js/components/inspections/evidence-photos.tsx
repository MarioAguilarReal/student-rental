import { useTranslation } from '@/lib/i18n';
import type { EvidencePhoto } from '@/types';

/**
 * Evidence thumbnails; each opens full size in a new tab (signed, short-lived URLs).
 */
export default function EvidencePhotos({
    photos,
}: {
    photos: EvidencePhoto[];
}) {
    const { t } = useTranslation();

    if (photos.length === 0) {
        return null;
    }

    return (
        <ul className="flex flex-wrap gap-2">
            {photos.map((photo, index) => (
                <li key={photo.id}>
                    <a
                        href={photo.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="block rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <img
                            src={photo.url}
                            alt={t('Evidence photo :number', {
                                number: index + 1,
                            })}
                            className="size-16 rounded-lg object-cover"
                            loading="lazy"
                        />
                    </a>
                </li>
            ))}
        </ul>
    );
}
