import { Head } from '@inertiajs/react';
import { Check, ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import Page from '@/components/page';
import DetailsForm from '@/components/properties/details-form';
import FeaturesForm from '@/components/properties/features-form';
import LocationForm from '@/components/properties/location-form';
import PhotosSection from '@/components/properties/photos-section';
import PropertyStatusBadge from '@/components/properties/property-status-badge';
import StatusPanel, {
    NextStepAction,
} from '@/components/properties/status-panel';
import UnitsSection from '@/components/properties/units-section';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { index } from '@/routes/properties';
import type {
    PropertyDetail,
    PropertyEditorOptions,
    PropertyPhoto,
    PublicationRequirement,
    Unit,
} from '@/types';

type Props = {
    property: PropertyDetail;
    units: Unit[];
    photos: PropertyPhoto[];
    ownershipProof: {
        name: string;
        url: string;
        uploaded_at: string | null;
    } | null;
    publication: PublicationRequirement[];
    can: {
        update: boolean;
        publish: boolean;
        archive: boolean;
        requestInspection: boolean;
    };
    options: PropertyEditorOptions;
};

const SECTIONS = [
    'publication',
    'details',
    'location',
    'spaces',
    'photos',
    'features',
] as const;
type SectionKey = (typeof SECTIONS)[number];

const readHash = (): SectionKey | null => {
    const hash = window.location.hash.replace('#', '');

    return (SECTIONS as readonly string[]).includes(hash)
        ? (hash as SectionKey)
        : null;
};

/**
 * Property editor (docs/spec/09 §Editor): sections listed on the left with what they hold,
 * one section on the right, the next life-cycle step as the page's main action.
 */
export default function PropertyEdit({
    property,
    units,
    photos,
    ownershipProof,
    publication,
    can,
    options,
}: Props) {
    const { t } = useTranslation();
    const isMobile = useIsMobile();
    const readOnly = !can.update || !property.editable;
    const [current, setCurrent] = useState<SectionKey | null>(null);

    useEffect(() => {
        const sync = () => setCurrent(readHash());
        sync();
        window.addEventListener('hashchange', sync);

        return () => window.removeEventListener('hashchange', sync);
    }, []);

    const open = (key: SectionKey | null) => {
        window.history.replaceState(
            null,
            '',
            key ? `#${key}` : window.location.pathname + window.location.search,
        );
        setCurrent(key);
        window.scrollTo({ top: 0 });
    };

    const active: SectionKey | null =
        current ?? (isMobile ? null : 'publication');
    const met = publication.filter((requirement) => requirement.met).length;
    const rentable = units.filter((unit) => unit.is_rentable).length;
    const missingPhotos = Math.max(0, 3 - photos.length);
    const genderLabel = options.genderPolicies.find(
        (option) => option.value === property.gender_policy,
    )?.label;

    const sections: {
        key: SectionKey;
        title: string;
        summary: string;
        complete: boolean;
    }[] = [
        {
            key: 'publication',
            title: t('Publication'),
            summary: t(':done of :total', {
                done: met,
                total: publication.length,
            }),
            complete: met === publication.length,
        },
        {
            key: 'details',
            title: t('General information'),
            summary: `${property.property_type_label} · ${property.management_mode_label}`,
            complete: Boolean(property.description),
        },
        {
            key: 'location',
            title: t('Location'),
            summary:
                property.latitude !== null
                    ? property.location
                    : t('Address and map pin missing'),
            complete: property.latitude !== null,
        },
        {
            key: 'spaces',
            title: t('Spaces'),
            summary: units.length
                ? t(':count spaces · :rentable rentable', {
                      count: units.length,
                      rentable,
                  })
                : t('No spaces yet'),
            complete: rentable > 0,
        },
        {
            key: 'photos',
            title: t('Photos'),
            summary: missingPhotos
                ? t(':count photos · :missing more to publish', {
                      count: photos.length,
                      missing: missingPhotos,
                  })
                : t(':count photos', { count: photos.length }),
            complete: missingPhotos === 0,
        },
        {
            key: 'features',
            title: t('Amenities, rules and coexistence'),
            summary: t(':count amenities · :policy', {
                count: property.amenity_ids.length,
                policy: genderLabel ?? '',
            }),
            complete: property.amenity_ids.length > 0,
        },
    ];

    const sectionContent: Record<SectionKey, React.ReactNode> = {
        publication: (
            <StatusPanel
                property={property}
                publication={publication}
                ownershipProof={ownershipProof}
                can={can}
            />
        ),
        details: (
            <DetailsForm
                property={property}
                propertyTypes={options.propertyTypes}
                managementModes={options.managementModes}
                disabled={readOnly}
            />
        ),
        location: <LocationForm property={property} disabled={readOnly} />,
        spaces: (
            <UnitsSection
                property={property}
                units={units}
                options={options}
                disabled={readOnly}
            />
        ),
        photos: (
            <PhotosSection
                propertyId={property.id}
                photos={photos}
                units={units}
                disabled={readOnly}
            />
        ),
        features: (
            <FeaturesForm
                property={property}
                options={options}
                disabled={readOnly}
            />
        ),
    };

    return (
        <>
            <Head title={property.title} />
            <Page
                width="wide"
                title={property.title}
                back={{ href: index(), label: t('My properties') }}
                meta={
                    <PropertyStatusBadge
                        status={property.status}
                        label={property.status_label}
                    />
                }
                description={t('Owner: :name (:email)', {
                    name: property.owner,
                    email: property.owner_email,
                })}
                actions={<NextStepAction property={property} can={can} />}
            >
                {readOnly && property.editable && (
                    <p className="mb-6 rounded-xl bg-muted px-4 py-3 text-sm">
                        {t(
                            'You can see this property but not change it: it is operated by its owner.',
                        )}
                    </p>
                )}
                <div className="grid gap-8 md:grid-cols-[17rem_1fr] lg:gap-12">
                    <nav
                        aria-label={t('Property sections')}
                        className={cn(
                            'md:sticky md:top-4 md:self-start',
                            active && isMobile && 'hidden',
                        )}
                    >
                        <ul className="space-y-1">
                            {sections.map((section) => (
                                <li key={section.key}>
                                    <button
                                        type="button"
                                        onClick={() => open(section.key)}
                                        aria-current={
                                            active === section.key
                                                ? 'page'
                                                : undefined
                                        }
                                        className={cn(
                                            'flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                            active === section.key
                                                ? 'bg-accent'
                                                : 'hover:bg-muted',
                                        )}
                                    >
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-sm font-medium">
                                                {section.title}
                                            </span>
                                            <span className="block truncate text-sm text-muted-foreground">
                                                {section.summary}
                                            </span>
                                        </span>
                                        {section.complete ? (
                                            <Check
                                                className="size-4 shrink-0 text-positive"
                                                aria-label={t('(done)')}
                                            />
                                        ) : (
                                            <span
                                                className="size-2 shrink-0 rounded-full bg-warning-ink/70"
                                                aria-label={t('(pending)')}
                                            />
                                        )}
                                        <ChevronRight
                                            className="size-4 shrink-0 text-muted-foreground md:hidden"
                                            aria-hidden
                                        />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    {active && (
                        <div className="min-w-0">
                            {isMobile && (
                                <button
                                    type="button"
                                    onClick={() => open(null)}
                                    className="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground"
                                >
                                    <ChevronLeft
                                        className="size-4"
                                        aria-hidden
                                    />{' '}
                                    {t('All sections')}
                                </button>
                            )}
                            {sectionContent[active]}
                        </div>
                    )}
                </div>
            </Page>
        </>
    );
}

PropertyEdit.layout = {
    breadcrumbs: [{ title: 'Properties', href: index() }],
};
