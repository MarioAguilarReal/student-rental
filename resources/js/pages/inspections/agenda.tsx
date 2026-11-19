import { Head, Link } from '@inertiajs/react';
import { CalendarDays, ChevronRight } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import InspectionStatusBadge from '@/components/inspections/inspection-status-badge';
import Page, { PageSection } from '@/components/page';
import { useTranslation } from '@/lib/i18n';
import { agenda, perform } from '@/routes/inspections';
import type { InspectionSummary } from '@/types';

/**
 * HU-06.02: the inspector's visits, next one first.
 */
export default function InspectionAgenda({
    upcoming,
    completed,
}: {
    upcoming: InspectionSummary[];
    completed: InspectionSummary[];
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('My inspections')} />
            <Page
                width="narrow"
                title={t('My inspections')}
                description={t(
                    'Your visits, the next one first. Open one to start its checklist.',
                )}
            >
                <div className="space-y-10">
                    <PageSection title={t('Upcoming')}>
                        {upcoming.length === 0 ? (
                            <EmptyState
                                icon={CalendarDays}
                                title={t('No visits scheduled')}
                                description={t(
                                    'Administrators will assign you inspections here.',
                                )}
                            />
                        ) : (
                            <InspectionList inspections={upcoming} />
                        )}
                    </PageSection>
                    {completed.length > 0 && (
                        <PageSection title={t('Recently completed')}>
                            <InspectionList inspections={completed} />
                        </PageSection>
                    )}
                </div>
            </Page>
        </>
    );
}

function InspectionList({ inspections }: { inspections: InspectionSummary[] }) {
    return (
        <ul className="divide-y rounded-xl border">
            {inspections.map((inspection) => (
                <li key={inspection.id}>
                    <Link
                        href={perform(inspection.id)}
                        className="flex items-center gap-4 px-4 py-4 transition-colors hover:bg-muted focus-visible:bg-muted focus-visible:outline-none"
                    >
                        <div className="w-14 shrink-0 text-center">
                            <p className="tabular text-lg leading-none font-semibold">
                                {inspection.scheduled_for?.slice(8, 10) ?? '—'}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {inspection.scheduled_for?.slice(0, 7) ?? ''}
                            </p>
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate font-medium">
                                {inspection.property.title}
                            </p>
                            <p className="truncate text-sm text-muted-foreground">
                                {inspection.property.location} ·{' '}
                                {inspection.type_label}
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
    );
}

InspectionAgenda.layout = {
    breadcrumbs: [{ title: 'My inspections', href: agenda() }],
};
