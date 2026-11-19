import { Head, router } from '@inertiajs/react';
import { List, Map as MapIcon, Search, SlidersHorizontal } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import ListingCard from '@/components/listing-card';
import Pagination from '@/components/pagination';
import CatalogMap from '@/components/stays/catalog-map';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { show } from '@/routes/stays';
import type {
    CatalogFilterValues,
    CatalogOptions,
    ListingCardData,
    Paginated,
} from '@/types';

type Props = {
    listings: Paginated<ListingCardData>;
    filters: CatalogFilterValues;
    campus: { name: string; lat: number; lng: number } | null;
    options: CatalogOptions;
};

/**
 * HU-07.01: search inspected spaces near a campus. Filters live in the URL so a search can be shared.
 */
export default function StaysIndex({
    listings,
    filters,
    campus,
    options,
}: Props) {
    const { t } = useTranslation();
    const [moreOpen, setMoreOpen] = useState(false);
    const [showMap, setShowMap] = useState(false);
    const [highlighted, setHighlighted] = useState<number | null>(null);
    const [draft, setDraft] = useState(filters);
    const periodLabel =
        options.periods
            .find((period) => period.value === filters.period)
            ?.label.toLowerCase() ?? '';
    const perUnit: Record<string, string> = {
        weekly: t('per week'),
        monthly: t('per month'),
        yearly: t('per year'),
    };

    const apply = (changes: Partial<CatalogFilterValues>) => {
        const next = { ...filters, ...changes };
        const query = Object.fromEntries(
            Object.entries(next).filter(
                ([key, value]) =>
                    (Array.isArray(value)
                        ? value.length > 0
                        : value !== undefined &&
                          value !== '' &&
                          value !== null) &&
                    !(key === 'period' && value === 'monthly'),
            ),
        );
        router.get(home.url(), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const extraFilters =
        [
            filters.min_price,
            filters.max_price,
            filters.gender,
            filters.sort && filters.sort !== 'recommended'
                ? filters.sort
                : undefined,
        ].filter(Boolean).length + filters.amenities.length;

    return (
        <>
            <Head title={t('Find a place near your campus')} />

            <section className="border-b bg-card">
                <div className="mx-auto w-full max-w-7xl space-y-4 px-4 py-6 md:px-8">
                    <h1 className="text-[22px] font-semibold tracking-tight md:text-[28px]">
                        {t('Inspected homes near your campus')}
                    </h1>

                    <form
                        className="grid gap-2 rounded-2xl border p-2 md:grid-cols-[2fr_1fr_1fr_1fr_auto] md:rounded-full"
                        onSubmit={(event) => {
                            event.preventDefault();
                            apply({
                                campus: draft.campus,
                                radius: draft.radius,
                                period: draft.period,
                                move_in: draft.move_in,
                            });
                        }}
                    >
                        <SearchField label={t('Campus')}>
                            <NativeSelect
                                className="h-8 border-0 bg-transparent px-0 shadow-none focus-visible:ring-0"
                                value={draft.campus ?? ''}
                                onChange={(event) =>
                                    setDraft({
                                        ...draft,
                                        campus: event.target.value,
                                    })
                                }
                            >
                                <option value="">{t('Any campus')}</option>
                                {options.campuses.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </SearchField>
                        <SearchField label={t('Distance')}>
                            <NativeSelect
                                className="h-8 border-0 bg-transparent px-0 shadow-none focus-visible:ring-0"
                                value={draft.radius ?? '5'}
                                onChange={(event) =>
                                    setDraft({
                                        ...draft,
                                        radius: event.target.value,
                                    })
                                }
                                disabled={!draft.campus}
                            >
                                {options.radii.map((radius) => (
                                    <option key={radius} value={radius}>
                                        {t('Up to :km km', { km: radius })}
                                    </option>
                                ))}
                            </NativeSelect>
                        </SearchField>
                        <SearchField label={t('Stay')}>
                            <NativeSelect
                                className="h-8 border-0 bg-transparent px-0 shadow-none focus-visible:ring-0"
                                value={draft.period}
                                onChange={(event) =>
                                    setDraft({
                                        ...draft,
                                        period: event.target.value,
                                    })
                                }
                            >
                                {options.periods.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </SearchField>
                        <SearchField label={t('Move in')}>
                            <Input
                                type="date"
                                className="h-8 border-0 bg-transparent px-0 shadow-none focus-visible:ring-0"
                                value={draft.move_in ?? ''}
                                onChange={(event) =>
                                    setDraft({
                                        ...draft,
                                        move_in: event.target.value,
                                    })
                                }
                            />
                        </SearchField>
                        <Button
                            type="submit"
                            size="lg"
                            className="rounded-full"
                        >
                            <Search /> {t('Search')}
                        </Button>
                    </form>

                    <div className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 md:mx-0 md:px-0">
                        {[
                            { value: '', label: t('All spaces') },
                            ...options.unitTypes,
                        ].map((type) => (
                            <button
                                key={type.value}
                                type="button"
                                onClick={() =>
                                    apply({ type: type.value || undefined })
                                }
                                aria-pressed={
                                    (filters.type ?? '') === type.value
                                }
                                className={cn(
                                    'shrink-0 rounded-full border px-4 py-1.5 text-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none active:scale-[0.98]',
                                    (filters.type ?? '') === type.value
                                        ? 'border-foreground bg-foreground text-background'
                                        : 'border-input hover:border-foreground',
                                )}
                            >
                                {type.label}
                            </button>
                        ))}
                        <Button
                            variant="outline"
                            size="sm"
                            className="shrink-0 rounded-full"
                            onClick={() => setMoreOpen(true)}
                        >
                            <SlidersHorizontal /> {t('More filters')}
                            {extraFilters > 0 && (
                                <Badge variant="neutral">{extraFilters}</Badge>
                            )}
                        </Button>
                    </div>
                </div>
            </section>

            <div className="mx-auto w-full max-w-7xl px-4 py-6 md:px-8">
                <div className="mb-4 flex items-center justify-between gap-4">
                    <p className="text-sm text-muted-foreground">
                        {listings.total === 1
                            ? t('1 property')
                            : t(':count properties', { count: listings.total })}
                        {campus &&
                            ` · ${t('near :campus', { campus: campus.name })}`}
                    </p>
                    <Button
                        variant="outline"
                        size="sm"
                        className="rounded-full lg:hidden"
                        onClick={() => setShowMap(!showMap)}
                    >
                        {showMap ? <List /> : <MapIcon />}{' '}
                        {showMap ? t('Show list') : t('Show map')}
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_40%]">
                    <div className={cn(showMap && 'hidden lg:block')}>
                        {listings.data.length === 0 ? (
                            <EmptyState
                                icon={Search}
                                title={t(
                                    'No inspected places match your search',
                                )}
                                description={t(
                                    'Try another campus, a larger distance or fewer filters.',
                                )}
                                action={
                                    <Button
                                        variant="outline"
                                        onClick={() => router.get(home.url())}
                                    >
                                        {t('Clear filters')}
                                    </Button>
                                }
                            />
                        ) : (
                            <ul className="grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2">
                                {listings.data.map((listing) => (
                                    <li
                                        key={listing.id}
                                        onMouseEnter={() =>
                                            setHighlighted(listing.id)
                                        }
                                        onMouseLeave={() =>
                                            setHighlighted(null)
                                        }
                                    >
                                        <ListingCard
                                            href={show(listing.id)}
                                            photo={listing.cover}
                                            title={listing.title}
                                            badge={
                                                <Badge variant="brand">
                                                    {t('Inspected')}
                                                </Badge>
                                            }
                                            lines={[
                                                [
                                                    listing.location,
                                                    listing.distance_km !== null
                                                        ? t(':km km away', {
                                                              km: listing.distance_km,
                                                          })
                                                        : null,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · '),
                                                <span key="price">
                                                    <span className="tabular font-semibold text-foreground">
                                                        {t('From :price', {
                                                            price: formatMoney(
                                                                listing.min_price_minor,
                                                            ),
                                                        })}
                                                    </span>{' '}
                                                    {perUnit[filters.period] ??
                                                        periodLabel}{' '}
                                                    ·{' '}
                                                    {t(':count spaces', {
                                                        count: listing.spaces,
                                                    })}
                                                </span>,
                                            ]}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                        <Pagination page={listings} />
                    </div>

                    <div
                        className={cn(
                            'h-[70svh] lg:sticky lg:top-24 lg:block lg:h-[calc(100svh-8rem)]',
                            !showMap && 'hidden',
                        )}
                    >
                        <CatalogMap
                            listings={listings.data}
                            campus={campus}
                            radiusKm={Number(filters.radius ?? 5)}
                            highlighted={highlighted}
                        />
                    </div>
                </div>
            </div>

            <Dialog open={moreOpen} onOpenChange={setMoreOpen}>
                <DialogContent className="max-h-[90svh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{t('More filters')}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1">
                                <Label htmlFor="min_price">
                                    {t('Minimum price')}
                                </Label>
                                <Input
                                    id="min_price"
                                    inputMode="numeric"
                                    placeholder="$"
                                    value={draft.min_price ?? ''}
                                    onChange={(event) =>
                                        setDraft({
                                            ...draft,
                                            min_price: event.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="max_price">
                                    {t('Maximum price')}
                                </Label>
                                <Input
                                    id="max_price"
                                    inputMode="numeric"
                                    placeholder="$"
                                    value={draft.max_price ?? ''}
                                    onChange={(event) =>
                                        setDraft({
                                            ...draft,
                                            max_price: event.target.value,
                                        })
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor="gender">
                                {t('Who can live here')}
                            </Label>
                            <NativeSelect
                                id="gender"
                                value={draft.gender ?? ''}
                                onChange={(event) =>
                                    setDraft({
                                        ...draft,
                                        gender: event.target.value,
                                    })
                                }
                            >
                                <option value="">{t('Any')}</option>
                                {options.genderPolicies.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor="sort">{t('Sort by')}</Label>
                            <NativeSelect
                                id="sort"
                                value={draft.sort ?? 'recommended'}
                                onChange={(event) =>
                                    setDraft({
                                        ...draft,
                                        sort: event.target.value,
                                    })
                                }
                            >
                                <option value="recommended">
                                    {t('Newest')}
                                </option>
                                <option value="price">
                                    {t('Lowest price')}
                                </option>
                                <option
                                    value="distance"
                                    disabled={!draft.campus}
                                >
                                    {t('Closest to campus')}
                                </option>
                            </NativeSelect>
                        </div>
                        <fieldset className="space-y-2">
                            <legend className="mb-2 text-sm font-medium">
                                {t('Amenities')}
                            </legend>
                            <div className="grid gap-2 sm:grid-cols-2">
                                {options.amenities.map((amenity) => (
                                    <label
                                        key={amenity.value}
                                        className="flex items-center gap-2 text-sm"
                                    >
                                        <Checkbox
                                            checked={draft.amenities.includes(
                                                amenity.value,
                                            )}
                                            onCheckedChange={() =>
                                                setDraft({
                                                    ...draft,
                                                    amenities:
                                                        draft.amenities.includes(
                                                            amenity.value,
                                                        )
                                                            ? draft.amenities.filter(
                                                                  (id) =>
                                                                      id !==
                                                                      amenity.value,
                                                              )
                                                            : [
                                                                  ...draft.amenities,
                                                                  amenity.value,
                                                              ],
                                                })
                                            }
                                        />
                                        {amenity.label}
                                    </label>
                                ))}
                            </div>
                        </fieldset>
                        <div className="flex justify-between gap-2 border-t pt-4">
                            <Button
                                variant="ghost"
                                onClick={() => {
                                    setMoreOpen(false);
                                    apply({
                                        min_price: undefined,
                                        max_price: undefined,
                                        gender: undefined,
                                        sort: undefined,
                                        amenities: [],
                                    });
                                }}
                            >
                                {t('Clear')}
                            </Button>
                            <Button
                                onClick={() => {
                                    setMoreOpen(false);
                                    apply({
                                        min_price: draft.min_price,
                                        max_price: draft.max_price,
                                        gender: draft.gender,
                                        sort: draft.sort,
                                        amenities: draft.amenities,
                                    });
                                }}
                            >
                                {t('Show results')}
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}

function SearchField({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <label className="flex flex-col rounded-xl px-4 py-1.5 transition-colors focus-within:bg-muted hover:bg-muted md:rounded-full">
            <span className="text-xs font-semibold">{label}</span>
            {children}
        </label>
    );
}
