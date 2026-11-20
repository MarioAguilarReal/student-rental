import { Form, Head } from '@inertiajs/react';
import { BadgeCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import Page, { PageSection } from '@/components/page';
import RentalStatusBadge from '@/components/rentals/rental-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { formatMoney } from '@/lib/money';
import { approve, cancel, index, manage, reject } from '@/routes/rentals';
import type { RentalDetail } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    rental: RentalDetail;
    isClient: boolean;
    canDecide: boolean;
    canCancel: boolean;
};

const chargeTones: Record<
    string,
    'neutral' | 'warning' | 'positive' | 'negative'
> = {
    pending: 'warning',
    paid: 'positive',
    failed: 'negative',
    overdue: 'negative',
    cancelled: 'neutral',
};

function formatDateTime(iso: string): string {
    return new Intl.DateTimeFormat('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
}

/**
 * HU-08.02 (operator decides), HU-08.04 (client follows up), HU-08.07 (admin cancels).
 */
export default function RentalShow({
    rental,
    isClient,
    canDecide,
    canCancel,
}: Props) {
    const { t } = useTranslation();
    const periodWord: Record<string, string> = {
        weekly: t('weeks'),
        monthly: t('months'),
        yearly: t('years'),
    };

    return (
        <>
            <Head title={rental.folio ?? rental.property.title} />
            <Page
                title={`${rental.property.title} · ${rental.unit}`}
                back={
                    isClient
                        ? { href: index(), label: t('My rentals') }
                        : { href: manage(), label: t('Rentals') }
                }
                meta={
                    <RentalStatusBadge
                        status={rental.status}
                        label={rental.status_label}
                    />
                }
                description={[rental.folio, rental.property.location]
                    .filter(Boolean)
                    .join(' · ')}
            >
                <div className="space-y-10">
                    <StatusNote rental={rental} isClient={isClient} />

                    <div className="grid gap-6 md:grid-cols-2">
                        <dl className="grid grid-cols-[auto_1fr] content-start gap-x-6 gap-y-2 rounded-xl border p-4 text-sm">
                            <Term label={t('Dates')}>
                                <span className="tabular">
                                    {rental.starts_on} → {rental.ends_on}
                                </span>
                            </Term>
                            <Term label={t('Stay')}>
                                {rental.periods}{' '}
                                {periodWord[rental.period] ??
                                    rental.period_label}{' '}
                                ·{' '}
                                {formatMoney(
                                    rental.price_minor,
                                    rental.currency,
                                )}
                            </Term>
                            <Term label={t('Rent')}>
                                <span className="tabular">
                                    {formatMoney(
                                        rental.total_minor,
                                        rental.currency,
                                    )}
                                </span>
                            </Term>
                            <Term label={t('Deposit (refundable)')}>
                                <span className="tabular">
                                    {formatMoney(
                                        rental.deposit_minor,
                                        rental.currency,
                                    )}
                                </span>
                            </Term>
                            {rental.owner_net_minor !== null && (
                                <Term label={t('Owner receives')}>
                                    <span className="tabular">
                                        {formatMoney(
                                            rental.owner_net_minor,
                                            rental.currency,
                                        )}
                                    </span>{' '}
                                    <span className="text-muted-foreground">
                                        {t('after :percent % commission', {
                                            percent:
                                                (rental.commission_bp ?? 0) /
                                                100,
                                        })}
                                    </span>
                                </Term>
                            )}
                            {rental.address && (
                                <Term label={t('Address')}>
                                    {rental.address}
                                </Term>
                            )}
                            {rental.contact && (
                                <Term label={t('Contact')}>
                                    {rental.contact.name} ·{' '}
                                    <a
                                        href={`mailto:${rental.contact.email}`}
                                        className="underline underline-offset-4"
                                    >
                                        {rental.contact.email}
                                    </a>
                                </Term>
                            )}
                            {!isClient && rental.managed && (
                                <Term label={t('Operator')}>
                                    {t('Managed by the platform')}
                                </Term>
                            )}
                        </dl>

                        {rental.applicant && (
                            <div className="space-y-3 rounded-xl border p-4 text-sm">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="font-semibold">
                                        {rental.applicant.name}
                                    </h2>
                                    {rental.applicant.verified && (
                                        <Badge variant="positive">
                                            <BadgeCheck aria-hidden />
                                            {t('Verified')}
                                        </Badge>
                                    )}
                                </div>
                                <p className="text-muted-foreground">
                                    {[
                                        rental.applicant.community,
                                        rental.applicant.institution,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </p>
                                <p>
                                    {t(
                                        "Meets this space's coexistence rules and accepted the house rules.",
                                    )}
                                </p>
                                {rental.client_message && (
                                    <p className="rounded-lg bg-muted p-3 whitespace-pre-line">
                                        {rental.client_message}
                                    </p>
                                )}
                            </div>
                        )}
                    </div>

                    {canDecide && (
                        <PageSection
                            title={t('Your answer')}
                            description={t(
                                'Approving holds the dates for 48 hours while the client pays the first charge.',
                            )}
                        >
                            <div className="grid gap-4 md:grid-cols-2">
                                <Form
                                    {...approve.form(rental.id)}
                                    options={{ preserveScroll: true }}
                                    className="space-y-3 rounded-xl bg-muted p-4"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <p className="text-sm">
                                                {t(
                                                    'The charge schedule is created and the client is notified.',
                                                )}
                                            </p>
                                            <InputError
                                                message={
                                                    errors.domain ??
                                                    errors.status
                                                }
                                            />
                                            <Button disabled={processing}>
                                                {t('Approve')}
                                            </Button>
                                        </>
                                    )}
                                </Form>
                                <ReasonForm
                                    action={reject.form(rental.id)}
                                    label={t('Reason for rejecting')}
                                    help={t('The client will read it.')}
                                    submit={t('Reject')}
                                />
                            </div>
                        </PageSection>
                    )}

                    {rental.charges.length > 0 && (
                        <PageSection
                            title={t('Charges')}
                            description={t(
                                'The amounts were fixed when the rental was approved.',
                            )}
                        >
                            <ul className="divide-y rounded-xl border">
                                {rental.charges.map((charge) => (
                                    <li
                                        key={charge.number}
                                        className="flex flex-wrap items-center gap-4 px-4 py-3 text-sm"
                                    >
                                        <span className="tabular w-6 text-muted-foreground">
                                            {charge.number}
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            {charge.concept_label}
                                        </span>
                                        <span className="tabular text-muted-foreground">
                                            {charge.due_on}
                                        </span>
                                        <span className="tabular font-medium">
                                            {formatMoney(
                                                charge.amount_minor,
                                                rental.currency,
                                            )}
                                        </span>
                                        <Badge
                                            variant={
                                                chargeTones[charge.status] ??
                                                'neutral'
                                            }
                                        >
                                            {charge.status_label}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        </PageSection>
                    )}

                    {canCancel && (
                        <PageSection
                            title={t('Cancel rental')}
                            description={t(
                                'Unpaid charges are cancelled. There is no automatic refund.',
                            )}
                        >
                            <ReasonForm
                                action={cancel.form(rental.id)}
                                label={t('Reason for cancelling')}
                                help={t('Both parties will read it.')}
                                submit={t('Cancel rental')}
                                destructive
                            />
                        </PageSection>
                    )}
                </div>
            </Page>
        </>
    );
}

function StatusNote({
    rental,
    isClient,
}: {
    rental: RentalDetail;
    isClient: boolean;
}) {
    const { t } = useTranslation();
    const until = rental.expires_at ? formatDateTime(rental.expires_at) : '';
    let note: string | null = null;

    switch (rental.status) {
        case 'requested':
            note = isClient
                ? t('Waiting for the host. They can answer until :date.', {
                      date: until,
                  })
                : t('Answer before :date or the request expires.', {
                      date: until,
                  });
            break;
        case 'approved':
            note = isClient
                ? t(
                      'Approved. Pay the first charge before :date to confirm your rental. Online payment opens very soon.',
                      { date: until },
                  )
                : t(
                      'Approved. The client has until :date to pay the first charge.',
                      { date: until },
                  );
            break;
        case 'rejected':
            note = t('Not approved. Reason: :reason', {
                reason: rental.rejection_reason ?? '',
            });
            break;
        case 'expired':
            note = t('This request expired and the dates are free again.');
            break;
        case 'cancelled':
            note = t('Cancelled by an administrator. Reason: :reason', {
                reason: rental.cancellation_reason ?? '',
            });
            break;
    }

    return note ? (
        <p className="rounded-xl bg-muted p-4 text-sm">{note}</p>
    ) : null;
}

function Term({ label, children }: { label: string; children: ReactNode }) {
    return (
        <>
            <dt className="text-muted-foreground">{label}</dt>
            <dd>{children}</dd>
        </>
    );
}

function ReasonForm({
    action,
    label,
    help,
    submit,
    destructive = false,
}: {
    action: RouteFormDefinition<'post'>;
    label: string;
    help: string;
    submit: string;
    destructive?: boolean;
}) {
    return (
        <Form
            {...action}
            options={{ preserveScroll: true }}
            className="space-y-3 rounded-xl border p-4"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor={`reason-${submit}`}>{label}</Label>
                        <Textarea
                            id={`reason-${submit}`}
                            name="reason"
                            rows={3}
                            maxLength={1000}
                            required
                        />
                        <p className="text-xs text-muted-foreground">{help}</p>
                        <InputError message={errors.reason ?? errors.status} />
                    </div>
                    <Button
                        variant={destructive ? 'destructive' : 'outline'}
                        disabled={processing}
                    >
                        {submit}
                    </Button>
                </>
            )}
        </Form>
    );
}
