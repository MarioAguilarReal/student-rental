import { Head, Link } from '@inertiajs/react';
import { CircleCheck, Receipt } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import Page, { PageSection } from '@/components/page';
import Figures from '@/components/payments/figures';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/lib/i18n';
import { formatMoney } from '@/lib/money';
import { finance } from '@/routes/payments';
import { show } from '@/routes/rentals';
import type { FinancePanel } from '@/types';

function formatDateTime(iso: string | null): string {
    return iso
        ? new Intl.DateTimeFormat('es-MX', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(iso))
        : '';
}

/**
 * HU-09.04: commissions, what is owed to owners, deposits held and overdue charges.
 */
export default function Finance({
    totals,
    owners,
    overdue,
    payments,
}: FinancePanel) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Finance')} />
            <Page
                title={t('Finance')}
                description={t(
                    'Balances from the ledger. Payments are test payments: no real money moves.',
                )}
                width="wide"
            >
                <div className="space-y-10">
                    <Figures
                        figures={[
                            {
                                label: t('Commissions'),
                                minor: totals.commission_minor,
                                primary: true,
                                hint: t(':amount this month', {
                                    amount: formatMoney(
                                        totals.commission_month_minor,
                                    ),
                                }),
                            },
                            {
                                label: t('Owed to owners'),
                                minor: totals.owner_payable_minor,
                            },
                            {
                                label: t('Deposits in guarantee'),
                                minor: totals.deposits_minor,
                            },
                            {
                                label: t('Overdue'),
                                minor: totals.overdue_minor,
                            },
                        ]}
                    />

                    <PageSection
                        title={t('Overdue charges')}
                        description={t(
                            'Charges not paid by their due date. The client and the operator were notified.',
                        )}
                    >
                        {overdue.length === 0 ? (
                            <EmptyState
                                icon={CircleCheck}
                                title={t('Nothing overdue')}
                            />
                        ) : (
                            <ul className="divide-y rounded-xl border">
                                {overdue.map((charge) => (
                                    <li key={charge.id}>
                                        <Link
                                            href={show(charge.rental_id)}
                                            className="flex flex-wrap items-center gap-4 px-4 py-3 text-sm hover:bg-muted/50 focus-visible:bg-muted/50 focus-visible:outline-none"
                                        >
                                            <span className="min-w-0 flex-1">
                                                <span className="font-medium">
                                                    {charge.client}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {' · '}
                                                    {[
                                                        charge.folio,
                                                        charge.property,
                                                        t('charge :number', {
                                                            number: charge.number,
                                                        }),
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </span>
                                            </span>
                                            <span className="tabular text-muted-foreground">
                                                {t('Due :date', {
                                                    date: charge.due_on,
                                                })}
                                            </span>
                                            <span className="tabular font-medium">
                                                {formatMoney(
                                                    charge.amount_minor,
                                                )}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </PageSection>

                    <div className="grid gap-10 lg:grid-cols-2">
                        <PageSection
                            title={t('Owed to owners')}
                            description={t(
                                'Rent minus commission. Payouts to owners are not part of the MVP.',
                            )}
                        >
                            {owners.length === 0 ? (
                                <EmptyState
                                    icon={Receipt}
                                    title={t('No balances yet')}
                                />
                            ) : (
                                <ul className="divide-y rounded-xl border">
                                    {owners.map((owner) => (
                                        <li
                                            key={owner.id}
                                            className="flex items-center justify-between gap-4 px-4 py-3 text-sm"
                                        >
                                            <span>{owner.name}</span>
                                            <span className="tabular font-medium">
                                                {formatMoney(
                                                    owner.amount_minor,
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </PageSection>

                        <PageSection title={t('Latest payments')}>
                            {payments.length === 0 ? (
                                <EmptyState
                                    icon={Receipt}
                                    title={t('No payments yet')}
                                />
                            ) : (
                                <ul className="divide-y rounded-xl border">
                                    {payments.map((payment) => (
                                        <li key={payment.id}>
                                            <Link
                                                href={show(payment.rental_id)}
                                                className="flex flex-wrap items-center gap-3 px-4 py-3 text-sm hover:bg-muted/50 focus-visible:bg-muted/50 focus-visible:outline-none"
                                            >
                                                <span className="min-w-0 flex-1">
                                                    <span className="font-medium">
                                                        {payment.client}
                                                    </span>
                                                    <span className="block text-xs text-muted-foreground">
                                                        {[
                                                            payment.folio,
                                                            t(
                                                                'charge :number',
                                                                {
                                                                    number: payment.number,
                                                                },
                                                            ),
                                                            formatDateTime(
                                                                payment.processed_at,
                                                            ),
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' · ')}
                                                    </span>
                                                </span>
                                                <span className="tabular font-medium">
                                                    {formatMoney(
                                                        payment.amount_minor,
                                                    )}
                                                </span>
                                                <Badge
                                                    variant={
                                                        payment.status ===
                                                        'succeeded'
                                                            ? 'positive'
                                                            : 'negative'
                                                    }
                                                >
                                                    {payment.status_label}
                                                </Badge>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </PageSection>
                    </div>
                </div>
            </Page>
        </>
    );
}

Finance.layout = {
    breadcrumbs: [{ title: 'Finance', href: finance() }],
};
