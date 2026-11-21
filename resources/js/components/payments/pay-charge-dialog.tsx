import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslation } from '@/lib/i18n';
import { formatMoney } from '@/lib/money';
import { pay } from '@/routes/charges';
import type { RentalCharge } from '@/types';

/**
 * HU-09.01: pay one charge with the test gateway. The screen says clearly that it is a test
 * payment and that no real money moves (D-05).
 */
export default function PayChargeDialog({
    rentalId,
    charge,
    currency,
    folio,
}: {
    rentalId: number;
    charge: RentalCharge;
    currency: string;
    folio: string | null;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const amount = formatMoney(charge.amount_minor, currency);

    return (
        <>
            <Button size="sm" onClick={() => setOpen(true)}>
                {charge.status === 'failed' ? t('Try again') : t('Pay')}
            </Button>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <div className="flex items-center gap-2">
                            <DialogTitle>{t('Test payment')}</DialogTitle>
                            <Badge variant="warning">{t('Test')}</Badge>
                        </div>
                        <DialogDescription>
                            {t(
                                'This is a test payment: no card is charged and no real money moves.',
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <dl className="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 rounded-lg bg-muted p-4 text-sm">
                        <dt className="text-muted-foreground">{t('Rental')}</dt>
                        <dd className="text-right">{folio}</dd>
                        <dt className="text-muted-foreground">{t('Charge')}</dt>
                        <dd className="text-right">
                            {charge.number} · {charge.concept_label}
                        </dd>
                        {charge.deposit_minor > 0 && (
                            <>
                                <dt className="text-muted-foreground">
                                    {t('Includes deposit')}
                                </dt>
                                <dd className="tabular text-right">
                                    {formatMoney(
                                        charge.deposit_minor,
                                        currency,
                                    )}
                                </dd>
                            </>
                        )}
                        <dt className="font-medium">{t('Total')}</dt>
                        <dd className="tabular text-right text-base font-semibold">
                            {amount}
                        </dd>
                    </dl>

                    <Form
                        {...pay.form({ rental: rentalId, charge: charge.id })}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setOpen(false)}
                        className="space-y-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <InputError message={errors.domain} />
                                <Button
                                    size="lg"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {t('Pay :amount (test)', { amount })}
                                </Button>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
        </>
    );
}
