import { Head, Link } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import Page from '@/components/page';
import Pagination from '@/components/pagination';
import RentalList from '@/components/rentals/rental-list';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { home } from '@/routes';
import { index } from '@/routes/rentals';
import type { Paginated, RentalSummary } from '@/types';

/**
 * HU-08.04: the client's requests and rentals, newest first.
 */
export default function MyRentals({
    rentals,
}: {
    rentals: Paginated<RentalSummary>;
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('My rentals')} />
            <Page
                title={t('My rentals')}
                description={t(
                    'Your requests and rentals with their status and charges.',
                )}
                actions={
                    <Button asChild>
                        <Link href={home()}>{t('Find a place')}</Link>
                    </Button>
                }
            >
                {rentals.data.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title={t('No rentals yet')}
                        description={t(
                            'Find a place near your campus and request it for your dates.',
                        )}
                    />
                ) : (
                    <RentalList
                        rentals={rentals.data}
                        subtitle={(rental) =>
                            [rental.folio, rental.property.location]
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

MyRentals.layout = {
    breadcrumbs: [{ title: 'My rentals', href: index() }],
};
