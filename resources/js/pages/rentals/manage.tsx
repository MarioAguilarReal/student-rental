import { Head, router } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import FilterChips from '@/components/filter-chips';
import Page from '@/components/page';
import Pagination from '@/components/pagination';
import RentalList from '@/components/rentals/rental-list';
import { useDeadlines } from '@/hooks/use-deadlines';
import { useTranslation } from '@/lib/i18n';
import { manage } from '@/routes/rentals';
import type { Option, Paginated, RentalSummary } from '@/types';

type Props = {
    rentals: Paginated<RentalSummary>;
    status: string;
    statusCounts: Record<string, number>;
    statusOptions: Option[];
};

/**
 * HU-08.02 / HU-08.05: requests to answer first, then the rentals of each status.
 */
export default function ManageRentals({
    rentals,
    status,
    statusCounts,
    statusOptions,
}: Props) {
    const { t } = useTranslation();
    const { requestHours, paymentHours } = useDeadlines();

    return (
        <>
            <Head title={t('Rentals')} />
            <Page
                title={t('Rentals')}
                description={t(
                    'Answer each request within :request hours. Approving gives the client :payment hours to pay.',
                    { request: requestHours, payment: paymentHours },
                )}
            >
                <div className="mb-6">
                    <FilterChips
                        label={t('Status')}
                        chips={statusOptions.map((option) => ({
                            ...option,
                            count: statusCounts[option.value] ?? 0,
                        }))}
                        value={status}
                        onChange={(value) =>
                            router.get(
                                manage.url(),
                                { status: value },
                                { preserveState: true, replace: true },
                            )
                        }
                    />
                </div>

                {rentals.data.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title={t('Nothing here')}
                        description={t(
                            'Requests appear here as soon as a client sends one.',
                        )}
                    />
                ) : (
                    <RentalList
                        rentals={rentals.data}
                        subtitle={(rental) =>
                            [rental.folio, rental.client]
                                .filter(Boolean)
                                .join(' · ')
                        }
                    />
                )}
                <Pagination page={rentals} />
            </Page>
        </>
    );
}

ManageRentals.layout = {
    breadcrumbs: [{ title: 'Rentals', href: manage() }],
};
