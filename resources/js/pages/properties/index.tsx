import { Head, Link, router } from '@inertiajs/react';
import { Home, Plus, Search } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import FilterChips from '@/components/filter-chips';
import ListingCard from '@/components/listing-card';
import Page from '@/components/page';
import Pagination from '@/components/pagination';
import PropertyStatusBadge from '@/components/properties/property-status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { useTranslation } from '@/lib/i18n';
import { create, edit, index } from '@/routes/properties';
import { index as adminIndex } from '@/routes/properties/admin';
import type { Option, Paginated, PropertySummary } from '@/types';

type Props = {
    scope: 'mine' | 'all';
    properties: Paginated<PropertySummary>;
    filters: { status?: string; mode?: string; search?: string };
    statusCounts: Record<string, number>;
    statusOptions: Option[];
    modeOptions: Option[];
    canCreate: boolean;
};

export default function PropertiesIndex({
    scope,
    properties,
    filters,
    statusCounts,
    statusOptions,
    modeOptions,
    canCreate,
}: Props) {
    const { t } = useTranslation();
    const mine = scope === 'mine';
    const baseUrl = mine ? index.url() : adminIndex.url();

    const filter = (changes: Record<string, string>) =>
        router.get(
            baseUrl,
            Object.fromEntries(
                Object.entries({ ...filters, ...changes }).filter(
                    ([, value]) => value && value !== 'all',
                ),
            ),
            { preserveState: true, replace: true, preserveScroll: true },
        );

    const chips = [
        { value: 'all', label: t('All'), count: statusCounts.all ?? 0 },
        ...statusOptions
            .filter(
                (option) =>
                    option.value !== 'archived' ||
                    (statusCounts.archived ?? 0) > 0,
            )
            .map((option) => ({
                value: option.value,
                label: option.label,
                count: statusCounts[option.value] ?? 0,
            })),
    ];

    const newProperty = canCreate && (
        <Button asChild>
            <Link href={create()}>
                <Plus /> {t('New property')}
            </Link>
        </Button>
    );

    return (
        <>
            <Head title={mine ? t('My properties') : t('Properties')} />
            <Page
                width="wide"
                title={mine ? t('My properties') : t('Properties')}
                description={
                    mine
                        ? t(
                              'Register your properties, get them inspected and publish them.',
                          )
                        : t(
                              'Every property on the platform. You operate the managed ones.',
                          )
                }
                actions={newProperty}
            >
                <div className="mb-8 space-y-4">
                    {!mine && (
                        <div className="flex flex-wrap gap-2">
                            <div className="relative min-w-60 flex-1">
                                <Search
                                    className="pointer-events-none absolute top-3 left-3 size-4 text-muted-foreground"
                                    aria-hidden
                                />
                                <Input
                                    className="pl-9"
                                    defaultValue={filters.search ?? ''}
                                    placeholder={t(
                                        'Search by title or owner email',
                                    )}
                                    aria-label={t(
                                        'Search by title or owner email',
                                    )}
                                    onKeyDown={(event) =>
                                        event.key === 'Enter' &&
                                        filter({
                                            search: event.currentTarget.value,
                                        })
                                    }
                                />
                            </div>
                            <NativeSelect
                                className="w-52"
                                aria-label={t('Management')}
                                value={filters.mode ?? ''}
                                onChange={(event) =>
                                    filter({ mode: event.target.value })
                                }
                            >
                                <option value="">{t('Any management')}</option>
                                {modeOptions.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                    )}
                    {(statusCounts.all ?? 0) > 0 && (
                        <FilterChips
                            label={t('Status')}
                            chips={chips}
                            value={filters.status ?? 'all'}
                            onChange={(status) => filter({ status })}
                        />
                    )}
                </div>

                {properties.data.length === 0 ? (
                    <EmptyState
                        icon={Home}
                        title={
                            mine && !filters.status
                                ? t('You have not registered any property yet.')
                                : t('No properties match the filters.')
                        }
                        description={
                            mine && !filters.status
                                ? t(
                                      'Start with the basics: a title, the type and who will operate it. You can finish the rest at your own pace.',
                                  )
                                : undefined
                        }
                        action={
                            mine && !filters.status ? newProperty : undefined
                        }
                    />
                ) : (
                    <ul className="grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {properties.data.map((property) => (
                            <li key={property.id}>
                                <ListingCard
                                    href={edit(property.id)}
                                    photo={property.cover}
                                    title={property.title}
                                    badge={
                                        <PropertyStatusBadge
                                            status={property.status}
                                            label={property.status_label}
                                        />
                                    }
                                    lines={[
                                        property.location ||
                                            t('No address yet'),
                                        mine
                                            ? `${t(':count spaces', { count: property.units_count })} · ${property.management_mode_label}`
                                            : `${property.owner} · ${property.management_mode_label}`,
                                    ]}
                                />
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination page={properties} />
            </Page>
        </>
    );
}

PropertiesIndex.layout = {
    breadcrumbs: [{ title: 'Properties', href: index() }],
};
