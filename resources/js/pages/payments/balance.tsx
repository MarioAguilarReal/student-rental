import { Head, Link } from '@inertiajs/react';
import { ChevronRight, Wallet } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import Page, { PageSection } from '@/components/page';
import Figures from '@/components/payments/figures';
import RentalStatusBadge from '@/components/rentals/rental-status-badge';
import { useTranslation } from '@/lib/i18n';
import { formatMoney } from '@/lib/money';
import { balance } from '@/routes/payments';
import { show } from '@/routes/rentals';
import type { OwnerBalance, RentalStatus } from '@/types';

/**
 * HU-09.03: what the owner's rentals brought in, before and after the commission.
 */
export default function Balance({ totals, rentals }: OwnerBalance) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Income')} />
            <Page
                title={t('Income')}
                description={t(
                    'Rent your clients paid, the platform commission and what you receive. Deposits stay in guarantee and are not income.',
                )}
            >
                <div className="space-y-10">
                    <Figures
                        figures={[
                            {
                                label: t('You receive'),
                                minor: totals.net_minor,
                                primary: true,
                            },
                            {
                                label: t('Rent collected'),
                                minor: totals.gross_minor,
                            },
                            {
                                label: t('Commission'),
                                minor: totals.commission_minor,
                            },
                            {
                                label: t('Rent still to collect'),
                                minor: totals.upcoming_minor,
                                hint: t('Before commission'),
                            },
                            {
                                label: t('Deposits in guarantee'),
                                minor: totals.deposits_minor,
                            },
                        ]}
                    />

                    <PageSection
                        title={t('By rental')}
                        description={t(
                            'Each rental keeps the commission rate it had when it was requested.',
                        )}
                    >
                        {rentals.length === 0 ? (
                            <EmptyState
                                icon={Wallet}
                                title={t('No income yet')}
                                description={t(
                                    'When a client pays a charge it shows up here.',
                                )}
                            />
                        ) : (
                            <ul className="divide-y rounded-xl border">
                                {rentals.map((rental) => (
                                    <li key={rental.id}>
                                        <Link
                                            href={show(rental.id)}
                                            className="flex items-center gap-4 px-4 py-3 text-sm hover:bg-muted/50 focus-visible:bg-muted/50 focus-visible:outline-none"
                                        >
                                            <div className="min-w-0 flex-1 space-y-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="truncate font-medium">
                                                        {rental.property} ·{' '}
                                                        {rental.unit}
                                                    </span>
                                                    <RentalStatusBadge
                                                        status={
                                                            rental.status as RentalStatus
                                                        }
                                                        label={
                                                            rental.status_label
                                                        }
                                                    />
                                                </div>
                                                <p className="truncate text-muted-foreground">
                                                    {[
                                                        rental.folio,
                                                        rental.client,
                                                        `${rental.starts_on} → ${rental.ends_on}`,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="tabular font-medium">
                                                    {formatMoney(
                                                        rental.net_minor,
                                                    )}
                                                </p>
                                                <p className="tabular text-xs text-muted-foreground">
                                                    {t(
                                                        ':gross collected − :commission (:percent %)',
                                                        {
                                                            gross: formatMoney(
                                                                rental.gross_minor,
                                                            ),
                                                            commission:
                                                                formatMoney(
                                                                    rental.commission_minor,
                                                                ),
                                                            percent:
                                                                rental.commission_bp /
                                                                100,
                                                        },
                                                    )}
                                                </p>
                                            </div>
                                            <ChevronRight
                                                className="size-4 shrink-0 text-muted-foreground"
                                                aria-hidden
                                            />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </PageSection>
                </div>
            </Page>
        </>
    );
}

Balance.layout = {
    breadcrumbs: [{ title: 'Income', href: balance() }],
};
