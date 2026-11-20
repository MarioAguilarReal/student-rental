import { useForm } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { formatMoney } from '@/lib/money';
import { store } from '@/routes/rentals';
import type { Option, StayQuote, StayUnit } from '@/types';

type HouseRule = { name: string; allowed: boolean; note: string | null };

/**
 * HU-08.01: the breakdown, the charge schedule (RN-70), the house rules to accept (RN-33),
 * the gender only when the space requires it (RN-32) and an optional message to the host.
 */
export default function RequestRentalDialog({
    propertyId,
    unit,
    quote,
    houseRules,
    askGender,
    genderOptions,
}: {
    propertyId: number;
    unit: StayUnit;
    quote: StayQuote;
    houseRules: HouseRule[];
    askGender: boolean;
    genderOptions: Option[];
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        unit_id: quote.unit_id,
        period: quote.period,
        starts_on: quote.start,
        periods: quote.periods,
        accept_rules: false,
        message: '',
        gender: '',
    });
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            unit_id: quote.unit_id,
            period: quote.period,
            starts_on: quote.start,
            periods: quote.periods,
        }));
        form.post(store.url(propertyId));
    };

    return (
        <>
            <Button size="lg" className="w-full" onClick={() => setOpen(true)}>
                {t('Request rental')}
            </Button>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90svh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {t('Request :space', { space: unit.name })}
                        </DialogTitle>
                        <DialogDescription>
                            {t(
                                'The host has 72 hours to answer. Nothing is charged until they approve.',
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submit} className="space-y-6">
                        <section className="space-y-2 text-sm">
                            <Row
                                label={t('Dates')}
                                value={`${quote.start} → ${quote.end}`}
                            />
                            <Row
                                label={t('Rent')}
                                value={formatMoney(quote.total_minor ?? 0)}
                            />
                            <Row
                                label={t('Deposit (refundable)')}
                                value={formatMoney(quote.deposit_minor)}
                            />
                        </section>

                        <section className="space-y-2">
                            <h3 className="text-sm font-semibold">
                                {t('Charges')}
                            </h3>
                            <ul className="divide-y rounded-lg border text-sm">
                                {quote.charges.map((charge) => (
                                    <li
                                        key={charge.number}
                                        className="flex justify-between gap-4 px-3 py-2"
                                    >
                                        <span className="text-muted-foreground">
                                            {charge.due_on ??
                                                t('On approval (48 h to pay)')}
                                            {charge.deposit_minor > 0 &&
                                                ` · ${t('includes deposit')}`}
                                        </span>
                                        <span className="tabular font-medium">
                                            {formatMoney(charge.amount_minor)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </section>

                        {houseRules.length > 0 && (
                            <section className="space-y-2">
                                <h3 className="text-sm font-semibold">
                                    {t('House rules')}
                                </h3>
                                <ul className="space-y-1 text-sm">
                                    {houseRules.map((rule) => (
                                        <li
                                            key={rule.name}
                                            className="flex items-start gap-2"
                                        >
                                            {rule.allowed ? (
                                                <Check
                                                    className="mt-0.5 size-4 shrink-0"
                                                    aria-label={t('Allowed')}
                                                />
                                            ) : (
                                                <X
                                                    className="mt-0.5 size-4 shrink-0"
                                                    aria-label={t(
                                                        'Not allowed',
                                                    )}
                                                />
                                            )}
                                            <span>
                                                {rule.name}
                                                {rule.note && (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        · {rule.note}
                                                    </span>
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}

                        {askGender && (
                            <div className="grid gap-2">
                                <Label htmlFor="rental_gender">
                                    {t('Gender for coexistence')}
                                </Label>
                                <NativeSelect
                                    id="rental_gender"
                                    value={form.data.gender}
                                    onChange={(event) =>
                                        form.setData(
                                            'gender',
                                            event.target.value,
                                        )
                                    }
                                    required
                                >
                                    <option value="" disabled>
                                        {t('Select')}
                                    </option>
                                    {genderOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <p className="text-xs text-muted-foreground">
                                    {t(
                                        'This space is :policy. We only use it to check the rule and never show it to anyone.',
                                        {
                                            policy: unit.gender_policy.toLowerCase(),
                                        },
                                    )}
                                </p>
                            </div>
                        )}
                        <InputError message={errors.gender} />

                        <div className="grid gap-2">
                            <Label htmlFor="rental_message">
                                {t('Message to the host (optional)')}
                            </Label>
                            <Textarea
                                id="rental_message"
                                rows={3}
                                maxLength={1000}
                                value={form.data.message}
                                onChange={(event) =>
                                    form.setData('message', event.target.value)
                                }
                                placeholder={t(
                                    'Introduce yourself: what you study or teach and when you arrive.',
                                )}
                            />
                            <InputError message={errors.message} />
                        </div>

                        <div className="space-y-1">
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="rental_accept_rules"
                                    checked={form.data.accept_rules}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'accept_rules',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label
                                    htmlFor="rental_accept_rules"
                                    className="leading-snug font-normal"
                                >
                                    {t(
                                        'I have read and accept the house rules.',
                                    )}
                                </Label>
                            </div>
                            <InputError message={errors.accept_rules} />
                        </div>

                        <InputError
                            message={
                                errors.domain ??
                                errors.unit_id ??
                                errors.starts_on ??
                                errors.period ??
                                errors.periods
                            }
                        />

                        <Button
                            type="submit"
                            size="lg"
                            className="w-full"
                            disabled={
                                form.processing || !form.data.accept_rules
                            }
                        >
                            {t('Send request')}
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-4">
            <span className="text-muted-foreground">{label}</span>
            <span className="tabular font-medium">{value}</span>
        </div>
    );
}
