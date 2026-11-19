import { Head, Link, router } from '@inertiajs/react';
import { ChevronRight, ClipboardList } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import FilterChips from '@/components/filter-chips';
import InspectionStatusBadge from '@/components/inspections/inspection-status-badge';
import Page from '@/components/page';
import Pagination from '@/components/pagination';
import { useTranslation } from '@/lib/i18n';
import { index, show } from '@/routes/inspections/queue';
import type { InspectionSummary, Option, Paginated } from '@/types';

type Props = {
    inspections: Paginated<InspectionSummary>;
    status: string;
    statusCounts: Record<string, number>;
    statusOptions: Option[];
};

/**
 * HU-06.01: inspections to assign first, then those on their way.
 */
export default function InspectionQueue({
    inspections,
    status,
    statusCounts,
    statusOptions,
}: Props) {
    const { t } = useTranslation();
    const chips = statusOptions
        .filter(
            (option) =>
                option.value !== 'cancelled' ||
                (statusCounts.cancelled ?? 0) > 0,
        )
        .map((option) => ({
            ...option,
            count: statusCounts[option.value] ?? 0,
        }));

    return (
        <>
            <Head title={t('Inspections')} />
            <Page
                title={t('Inspections')}
                description={t(
                    'Assign each request to an inspector with a date. The owner is notified.',
                )}
            >
                <div className="mb-6">
                    <FilterChips
                        label={t('Status')}
                        chips={chips}
                        value={status}
                        onChange={(value) =>
                            router.get(
                                index.url(),
                                { status: value },
                                { preserveState: true, replace: true },
                            )
                        }
                    />
                </div>

                {inspections.data.length === 0 ? (
                    <EmptyState
                        icon={ClipboardList}
                        title={t('Nothing here')}
                        description={t(
                            'Inspections appear here as soon as an owner requests one.',
                        )}
                    />
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {inspections.data.map((inspection) => (
                            <li key={inspection.id}>
                                <Link
                                    href={show(inspection.id)}
                                    className="flex items-center gap-4 px-4 py-4 transition-colors hover:bg-muted focus-visible:bg-muted focus-visible:outline-none"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">
                                            {inspection.property.title}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {[
                                                inspection.type_label,
                                                inspection.property.location,
                                                inspection.property.owner.name,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                    </div>
                                    <div className="hidden text-right text-sm sm:block">
                                        <p>
                                            {inspection.inspector?.name ??
                                                t('Unassigned')}
                                        </p>
                                        <p className="tabular text-muted-foreground">
                                            {inspection.scheduled_for ?? '—'}
                                        </p>
                                    </div>
                                    <InspectionStatusBadge
                                        status={inspection.status}
                                        statusLabel={inspection.status_label}
                                        result={inspection.result}
                                        resultLabel={inspection.result_label}
                                    />
                                    <ChevronRight
                                        className="size-4 shrink-0 text-muted-foreground"
                                        aria-hidden
                                    />
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination page={inspections} />
            </Page>
        </>
    );
}

InspectionQueue.layout = {
    breadcrumbs: [{ title: 'Inspections', href: index() }],
};
