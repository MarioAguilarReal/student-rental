import { Head, Link, router, usePage } from '@inertiajs/react';
import { BadgeCheck, Check, Images, Pencil, Users, X } from 'lucide-react';
import { useState } from 'react';
import RequestRentalDialog from '@/components/rentals/request-rental-dialog';
import ApproximateMap from '@/components/stays/approximate-map';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTranslation } from '@/lib/i18n';
import { formatMoney } from '@/lib/money';
import { useCan } from '@/lib/permissions';
import { home, login } from '@/routes';
import { edit as editProperty } from '@/routes/properties';
import { show } from '@/routes/stays';
import { show as verification } from '@/routes/verifications';
import type { Option, StayQuote, StayUnit } from '@/types';

type Props = {
    property: {
        id: number;
        title: string;
        description: string | null;
        property_type: string;
        location: string;
        lat: number | null;
        lng: number | null;
        inspected_until: string | null;
        host: string | null;
        managed: boolean;
        gender_policy: string;
        communities: string[];
        listed: boolean;
    };
    photos: { id: number; thumb: string; url: string; alt: string | null }[];
    amenities: { name: string; category: string }[];
    houseRules: { name: string; allowed: boolean; note: string | null }[];
    units: StayUnit[];
    quote: StayQuote | null;
    preview: boolean;
    canEdit: boolean;
    needsGender: boolean;
    genderOptions: Option[];
};

/**
 * HU-07.02: everything needed to decide, and what the stay would cost.
 */
export default function StayShow({
    property,
    photos,
    amenities,
    houseRules,
    units,
    quote,
    preview,
    canEdit,
    needsGender,
    genderOptions,
}: Props) {
    const { t } = useTranslation();
    const [gallery, setGallery] = useState(false);

    return (
        <>
            <Head title={property.title} />
            <div className="mx-auto w-full max-w-6xl px-4 py-6 md:px-8">
                {preview && (
                    <div className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-warning-soft px-4 py-3 text-sm text-warning-ink">
                        {t(
                            'Preview: this property does not appear in the catalog yet.',
                        )}
                        {canEdit && (
                            <Button asChild size="sm" variant="outline">
                                <Link href={editProperty(property.id)}>
                                    <Pencil /> {t('Back to editor')}
                                </Link>
                            </Button>
                        )}
                    </div>
                )}

                <header className="mb-6 space-y-2">
                    <h1 className="text-[22px] leading-tight font-semibold tracking-tight md:text-[28px]">
                        {property.title}
                    </h1>
                    <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        <span>{property.location}</span>
                        {property.inspected_until && (
                            <Badge variant="brand">
                                <BadgeCheck />{' '}
                                {t('Inspected · valid until :date', {
                                    date: property.inspected_until,
                                })}
                            </Badge>
                        )}
                    </div>
                </header>

                <Gallery photos={photos} onOpen={() => setGallery(true)} />

                <div className="mt-10 grid grid-cols-1 gap-12 lg:grid-cols-[minmax(0,1fr)_22rem]">
                    <div className="space-y-10">
                        <section className="space-y-2 border-b pb-8">
                            <h2 className="text-lg font-semibold">
                                {property.property_type} ·{' '}
                                {property.managed
                                    ? t('Managed by our team')
                                    : t('Hosted by :name', {
                                          name: property.host ?? '',
                                      })}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {t('Coexistence: :policy', {
                                    policy: property.gender_policy,
                                })}
                                {property.communities.length > 0 &&
                                    ` · ${t('Only :list', { list: property.communities.join(', ') })}`}
                            </p>
                            {property.description && (
                                <p className="pt-2 whitespace-pre-line">
                                    {property.description}
                                </p>
                            )}
                        </section>

                        <section className="space-y-4">
                            <h2 className="text-lg font-semibold">
                                {t('Spaces you can rent')}
                            </h2>
                            <ul className="space-y-3">
                                {units.map((unit) => (
                                    <li
                                        key={unit.id}
                                        className="space-y-3 rounded-xl border p-4"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <p className="font-semibold">
                                                    {unit.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {[
                                                        unit.type,
                                                        unit.parent &&
                                                            t('in :name', {
                                                                name: unit.parent,
                                                            }),
                                                        unit.gender_policy,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </p>
                                            </div>
                                            <span className="inline-flex items-center gap-1 text-sm text-muted-foreground">
                                                <Users
                                                    className="size-4"
                                                    aria-hidden
                                                />{' '}
                                                {t(':count people', {
                                                    count: unit.capacity,
                                                })}
                                            </span>
                                        </div>
                                        {unit.description && (
                                            <p className="text-sm">
                                                {unit.description}
                                            </p>
                                        )}
                                        <ul className="flex flex-wrap gap-x-6 gap-y-1 text-sm">
                                            {unit.plans.map((plan) => (
                                                <li key={plan.period}>
                                                    <span className="tabular font-semibold">
                                                        {formatMoney(
                                                            plan.price_minor,
                                                        )}
                                                    </span>{' '}
                                                    <span className="text-muted-foreground">
                                                        {plan.label.toLowerCase()}
                                                    </span>
                                                </li>
                                            ))}
                                            <li className="text-muted-foreground">
                                                {t('Deposit :amount', {
                                                    amount: formatMoney(
                                                        unit.deposit_minor,
                                                    ),
                                                })}
                                            </li>
                                        </ul>
                                        {(unit.amenities.length > 0 ||
                                            unit.communities.length > 0) && (
                                            <div className="flex flex-wrap gap-1">
                                                {unit.amenities.map(
                                                    (amenity) => (
                                                        <Badge
                                                            key={amenity}
                                                            variant="neutral"
                                                        >
                                                            {amenity}
                                                        </Badge>
                                                    ),
                                                )}
                                                {unit.communities.length >
                                                    0 && (
                                                    <Badge variant="warning">
                                                        {t('Only :list', {
                                                            list: unit.communities.join(
                                                                ', ',
                                                            ),
                                                        })}
                                                    </Badge>
                                                )}
                                            </div>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </section>

                        {amenities.length > 0 && (
                            <section className="space-y-4">
                                <h2 className="text-lg font-semibold">
                                    {t('What the place offers')}
                                </h2>
                                <ul className="grid gap-x-6 gap-y-2 sm:grid-cols-2">
                                    {amenities.map((amenity) => (
                                        <li
                                            key={amenity.name}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <Check
                                                className="size-4 text-muted-foreground"
                                                aria-hidden
                                            />{' '}
                                            {amenity.name}
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}

                        {houseRules.length > 0 && (
                            <section className="space-y-4">
                                <h2 className="text-lg font-semibold">
                                    {t('House rules')}
                                </h2>
                                <ul className="space-y-2">
                                    {houseRules.map((rule) => (
                                        <li
                                            key={rule.name}
                                            className="flex items-start gap-2 text-sm"
                                        >
                                            {rule.allowed ? (
                                                <Check
                                                    className="mt-0.5 size-4 text-positive"
                                                    aria-label={t('Allowed')}
                                                />
                                            ) : (
                                                <X
                                                    className="mt-0.5 size-4 text-negative"
                                                    aria-label={t(
                                                        'Not allowed',
                                                    )}
                                                />
                                            )}
                                            <span>
                                                {rule.name}
                                                {rule.note && (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        · {rule.note}
                                                    </span>
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}

                        {property.lat !== null && property.lng !== null && (
                            <section className="space-y-4">
                                <h2 className="text-lg font-semibold">
                                    {t('Where it is')}
                                </h2>
                                <ApproximateMap
                                    lat={property.lat}
                                    lng={property.lng}
                                />
                                <p className="text-sm text-muted-foreground">
                                    {t(
                                        'The exact address is shared once your rental is confirmed.',
                                    )}
                                </p>
                            </section>
                        )}
                    </div>

                    <aside>
                        <div className="lg:sticky lg:top-24">
                            <QuoteCard
                                propertyId={property.id}
                                units={units}
                                quote={quote}
                                houseRules={houseRules}
                                needsGender={needsGender}
                                genderOptions={genderOptions}
                                canRequest={!preview}
                            />
                        </div>
                    </aside>
                </div>
            </div>

            <Dialog open={gallery} onOpenChange={setGallery}>
                <DialogContent className="max-h-[90svh] overflow-y-auto sm:max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>
                            {t('Photos of :title', { title: property.title })}
                        </DialogTitle>
                    </DialogHeader>
                    <ul className="grid gap-3 sm:grid-cols-2">
                        {photos.map((photo) => (
                            <li key={photo.id}>
                                <img
                                    src={photo.url}
                                    alt={photo.alt ?? ''}
                                    loading="lazy"
                                    className="w-full rounded-xl object-cover"
                                />
                            </li>
                        ))}
                    </ul>
                </DialogContent>
            </Dialog>
        </>
    );
}

function Gallery({
    photos,
    onOpen,
}: {
    photos: Props['photos'];
    onOpen: () => void;
}) {
    const { t } = useTranslation();

    if (photos.length === 0) {
        return <div className="aspect-[16/7] rounded-2xl bg-muted" />;
    }

    const [cover, ...rest] = photos;
    const thumbs = rest.slice(0, 4);
    // Fill the 2×2 area next to the cover whatever the number of photos.
    const span = (index: number) =>
        thumbs.length === 1
            ? 'md:col-span-2 md:row-span-2'
            : thumbs.length === 2 || (thumbs.length === 3 && index === 2)
              ? 'md:col-span-2'
              : '';

    return (
        <div className="relative grid gap-2 overflow-hidden rounded-2xl md:aspect-[16/6] md:grid-cols-4 md:grid-rows-2">
            <button
                type="button"
                onClick={onOpen}
                className={
                    thumbs.length === 0
                        ? 'md:col-span-4 md:row-span-2'
                        : 'md:col-span-2 md:row-span-2'
                }
            >
                <img
                    src={cover.url}
                    alt={cover.alt ?? ''}
                    className="aspect-[4/3] size-full object-cover transition-opacity hover:opacity-90 md:aspect-auto"
                />
            </button>
            {thumbs.map((photo, index) => (
                <button
                    key={photo.id}
                    type="button"
                    onClick={onOpen}
                    className={`hidden md:block ${span(index)}`}
                >
                    <img
                        src={photo.thumb}
                        alt={photo.alt ?? ''}
                        loading="lazy"
                        className="size-full object-cover transition-opacity hover:opacity-90"
                    />
                </button>
            ))}
            <Button
                variant="outline"
                size="sm"
                className="absolute right-3 bottom-3"
                onClick={onOpen}
            >
                <Images />{' '}
                {t('Show all :count photos', { count: photos.length })}
            </Button>
        </div>
    );
}

function QuoteCard({
    propertyId,
    units,
    quote,
    houseRules,
    needsGender,
    genderOptions,
    canRequest,
}: {
    propertyId: number;
    units: StayUnit[];
    quote: StayQuote | null;
    houseRules: Props['houseRules'];
    needsGender: boolean;
    genderOptions: Option[];
    canRequest: boolean;
}) {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const can = useCan();
    const tomorrow = new Date(Date.now() + 86_400_000)
        .toISOString()
        .slice(0, 10);

    const [unitId, setUnitId] = useState(quote?.unit_id ?? units[0]?.id ?? 0);
    const unit = units.find((item) => item.id === unitId) ?? units[0];
    const [period, setPeriod] = useState(
        quote?.period ??
            unit?.plans.find((plan) => plan.period === 'monthly')?.period ??
            unit?.plans[0]?.period ??
            'monthly',
    );
    const plan =
        unit?.plans.find((item) => item.period === period) ?? unit?.plans[0];
    const [start, setStart] = useState(quote?.start ?? '');
    const [periods, setPeriods] = useState(
        quote?.periods ?? plan?.min_periods ?? 1,
    );

    if (!unit || !plan) {
        return null;
    }

    const calculate = (
        changes: Partial<{
            unit: number;
            period: string;
            start: string;
            periods: number;
        }>,
    ) => {
        const next = {
            unit: unitId,
            period: plan.period,
            start,
            periods,
            ...changes,
        };

        if (next.start) {
            router.get(show.url(propertyId), next, {
                only: ['quote'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }
    };
    const current =
        quote &&
        quote.unit_id === unit.id &&
        quote.period === plan.period &&
        quote.start === start &&
        quote.periods === periods
            ? quote
            : null;
    const perUnit: Record<string, string> = {
        weekly: t('per week'),
        monthly: t('per month'),
        yearly: t('per year'),
    };
    const periodWord: Record<string, string> = {
        weekly: t('weeks'),
        monthly: t('months'),
        yearly: t('years'),
    };

    return (
        <div className="space-y-4 rounded-2xl border p-5">
            <p>
                <span className="tabular text-2xl font-semibold text-brand-ink">
                    {formatMoney(plan.price_minor)}
                </span>{' '}
                <span className="text-muted-foreground">
                    {perUnit[plan.period] ?? plan.label.toLowerCase()}
                </span>
            </p>

            <div className="grid gap-3">
                {units.length > 1 && (
                    <div className="grid gap-1">
                        <Label htmlFor="quote_unit">{t('Space')}</Label>
                        <NativeSelect
                            id="quote_unit"
                            value={unit.id}
                            onChange={(event) => {
                                const next =
                                    units.find(
                                        (item) =>
                                            item.id ===
                                            Number(event.target.value),
                                    ) ?? unit;
                                const nextPlan =
                                    next.plans.find(
                                        (item) => item.period === period,
                                    ) ?? next.plans[0];
                                setUnitId(next.id);
                                setPeriod(nextPlan.period);
                                setPeriods(nextPlan.min_periods);
                                calculate({
                                    unit: next.id,
                                    period: nextPlan.period,
                                    periods: nextPlan.min_periods,
                                });
                            }}
                        >
                            {units.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                )}
                <div className="grid grid-cols-2 gap-3">
                    <div className="grid gap-1">
                        <Label htmlFor="quote_period">{t('Stay')}</Label>
                        <NativeSelect
                            id="quote_period"
                            value={plan.period}
                            onChange={(event) => {
                                const nextPlan =
                                    unit.plans.find(
                                        (item) =>
                                            item.period === event.target.value,
                                    ) ?? plan;
                                setPeriod(nextPlan.period);
                                setPeriods(nextPlan.min_periods);
                                calculate({
                                    period: nextPlan.period,
                                    periods: nextPlan.min_periods,
                                });
                            }}
                        >
                            {unit.plans.map((item) => (
                                <option key={item.period} value={item.period}>
                                    {item.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                    <div className="grid gap-1">
                        <Label htmlFor="quote_periods">
                            {periodWord[plan.period] ?? ''}
                        </Label>
                        <NativeSelect
                            id="quote_periods"
                            value={periods}
                            onChange={(event) => {
                                setPeriods(Number(event.target.value));
                                calculate({
                                    periods: Number(event.target.value),
                                });
                            }}
                        >
                            {Array.from(
                                {
                                    length:
                                        plan.max_periods - plan.min_periods + 1,
                                },
                                (_, index) => plan.min_periods + index,
                            ).map((count) => (
                                <option key={count} value={count}>
                                    {count}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                </div>
                <div className="grid gap-1">
                    <Label htmlFor="quote_start">{t('Move in')}</Label>
                    <Input
                        id="quote_start"
                        type="date"
                        min={tomorrow}
                        value={start}
                        onChange={(event) => {
                            setStart(event.target.value);
                            calculate({ start: event.target.value });
                        }}
                    />
                </div>
                {unit.unavailable.length > 0 && (
                    <p className="text-xs text-muted-foreground">
                        {t('Not available: :ranges', {
                            ranges: unit.unavailable
                                .map(
                                    (range) =>
                                        `${range.starts_on} → ${range.ends_on}`,
                                )
                                .join(', '),
                        })}
                    </p>
                )}
            </div>

            {current && (
                <div
                    className="space-y-2 border-t pt-4 text-sm"
                    aria-live="polite"
                >
                    {current.errors.length > 0 ? (
                        current.errors.map((error) => (
                            <p key={error} className="text-negative">
                                {error}
                            </p>
                        ))
                    ) : (
                        <>
                            <Row
                                label={t(':price × :count :unit', {
                                    price: formatMoney(
                                        current.price_minor ?? 0,
                                    ),
                                    count: current.periods,
                                    unit: periodWord[current.period] ?? '',
                                })}
                                value={formatMoney(current.total_minor ?? 0)}
                            />
                            <Row
                                label={t('Deposit (refundable)')}
                                value={formatMoney(current.deposit_minor)}
                            />
                            <Row label={t('Move out')} value={current.end} />
                            <p className="text-xs text-muted-foreground">
                                {t(
                                    'No service fee: you pay exactly the listed price.',
                                )}
                            </p>
                        </>
                    )}
                </div>
            )}

            {!auth.user ? (
                <Button asChild size="lg" className="w-full">
                    <Link href={login()}>{t('Log in to request it')}</Link>
                </Button>
            ) : can('rentals.request') ? (
                canRequest && current && current.errors.length === 0 ? (
                    <RequestRentalDialog
                        key={`${current.unit_id}-${current.period}-${current.start}-${current.periods}`}
                        propertyId={propertyId}
                        unit={unit}
                        quote={current}
                        houseRules={houseRules}
                        askGender={unit.gender_restricted && needsGender}
                        genderOptions={genderOptions}
                    />
                ) : (
                    <>
                        <Button size="lg" className="w-full" disabled>
                            {t('Request rental')}
                        </Button>
                        <p className="text-center text-xs text-muted-foreground">
                            {t('Choose your dates to request this space.')}
                        </p>
                    </>
                )
            ) : (
                <Button asChild size="lg" variant="outline" className="w-full">
                    <Link href={verification()}>
                        {t('Add a client profile to rent')}
                    </Link>
                </Button>
            )}
        </div>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-4">
            <span className="text-muted-foreground">{label}</span>
            <span className="tabular font-medium">{value}</span>
        </div>
    );
}

StayShow.layout = {
    breadcrumbs: [{ title: 'Search', href: home() }],
};
