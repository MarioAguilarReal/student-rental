import { Form, router, useForm } from '@inertiajs/react';
import { CalendarX, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/empty-state';
import FormActions from '@/components/form-actions';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTranslation } from '@/lib/i18n';
import {
    formatMoney,
    netAfterCommission,
    toInputAmount,
    toMinor,
} from '@/lib/money';
import {
    destroy as destroyBlock,
    store as storeBlock,
} from '@/routes/pricing/blocks';
import { update as updatePricing } from '@/routes/pricing/units';
import type { PlanPeriod, PricingData, Unit } from '@/types';

type Props = {
    propertyId: number;
    units: Unit[];
    pricing: PricingData;
    disabled: boolean;
};

/**
 * HU-05.01 to HU-05.03: plans per space with suggestions and the owner's net, deposit, blocks.
 */
export default function PricingSection({
    propertyId,
    units,
    pricing,
    disabled,
}: Props) {
    const { t } = useTranslation();
    const rentable = units.filter((unit) => unit.is_rentable);

    return (
        <section className="space-y-10">
            <header className="space-y-1">
                <h2 className="text-lg font-semibold">
                    {t('Prices and availability')}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {t(
                        'Set a price for each period you offer. Tenants pay exactly that price; the commission (:percent %) is deducted from what you receive.',
                        {
                            percent: pricing.commission_bp / 100,
                        },
                    )}
                </p>
            </header>

            {rentable.length === 0 ? (
                <EmptyState
                    title={t('No rentable spaces yet')}
                    description={t(
                        'Mark at least one space as rentable in Spaces to give it a price.',
                    )}
                />
            ) : (
                rentable.map((unit) => (
                    <UnitPricingForm
                        key={unit.id}
                        propertyId={propertyId}
                        unit={unit}
                        pricing={pricing}
                        disabled={disabled}
                    />
                ))
            )}

            <BlocksPanel
                propertyId={propertyId}
                units={units}
                pricing={pricing}
                disabled={disabled}
            />
        </section>
    );
}

function UnitPricingForm({
    propertyId,
    unit,
    pricing,
    disabled,
}: {
    propertyId: number;
    unit: Unit;
    pricing: PricingData;
    disabled: boolean;
}) {
    const { t } = useTranslation();
    const current = pricing.units[unit.id] ?? {
        deposit_minor: null,
        plans: { weekly: null, monthly: null, yearly: null },
    };
    const defaults: Record<PlanPeriod, { min: number; max: number }> = {
        weekly: { min: 1, max: 12 },
        monthly: { min: 1, max: 12 },
        yearly: { min: 1, max: 1 },
    };

    const form = useForm({
        plans: Object.fromEntries(
            pricing.periods.map((period) => {
                const plan = current.plans[period.value];

                return [
                    period.value,
                    {
                        enabled: plan?.enabled ?? period.value === 'monthly',
                        price: toInputAmount(plan?.price_minor),
                        min_periods: String(
                            plan?.min_periods ?? defaults[period.value].min,
                        ),
                        max_periods: String(
                            plan?.max_periods ?? defaults[period.value].max,
                        ),
                    },
                ];
            }),
        ) as Record<
            PlanPeriod,
            {
                enabled: boolean;
                price: string;
                min_periods: string;
                max_periods: string;
            }
        >,
        deposit: toInputAmount(current.deposit_minor),
    });

    const monthlyMinor = toMinor(form.data.plans.monthly.price);
    const roundTo = pricing.suggestions.round_to_minor;
    const round = (amount: number) => Math.round(amount / roundTo) * roundTo;
    const suggestion: Partial<Record<PlanPeriod, number>> = monthlyMinor
        ? {
              weekly: round(
                  (monthlyMinor / 4) * pricing.suggestions.weekly_factor,
              ),
              yearly: round(
                  monthlyMinor * 12 * pricing.suggestions.yearly_factor,
              ),
          }
        : {};

    const setPlan = (
        period: PlanPeriod,
        changes: Partial<{
            enabled: boolean;
            price: string;
            min_periods: string;
            max_periods: string;
        }>,
    ) =>
        form.setData('plans', {
            ...form.data.plans,
            [period]: { ...form.data.plans[period], ...changes },
        });

    const errors = form.errors as Record<string, string | undefined>;
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(updatePricing.url({ property: propertyId, unit: unit.id }), {
            preserveScroll: true,
        });
    };

    const rangeLabels: Record<PlanPeriod, [string, string]> = {
        weekly: [t('Minimum weeks'), t('Maximum weeks')],
        monthly: [t('Minimum months'), t('Maximum months')],
        yearly: [t('Minimum years'), t('Maximum years')],
    };
    const unitLabel: Record<PlanPeriod, string> = {
        weekly: t('per week'),
        monthly: t('per month'),
        yearly: t('per year'),
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <fieldset disabled={disabled} className="space-y-6">
                <div>
                    <h3 className="font-semibold">{unit.name}</h3>
                    <p className="text-sm text-muted-foreground">
                        {unit.type_label} ·{' '}
                        {t(':count people', { count: unit.capacity })}
                    </p>
                </div>
                <InputError message={errors.domain} />

                <ul className="divide-y rounded-xl border">
                    {pricing.periods.map((period) => {
                        const plan = form.data.plans[period.value];
                        const priceMinor = toMinor(plan.price);
                        const suggested = suggestion[period.value];

                        return (
                            <li key={period.value} className="space-y-4 p-4">
                                <label className="flex items-center gap-3">
                                    <Checkbox
                                        checked={plan.enabled}
                                        onCheckedChange={(checked) =>
                                            setPlan(period.value, {
                                                enabled: checked === true,
                                            })
                                        }
                                    />
                                    <span className="font-medium">
                                        {period.label}
                                    </span>
                                </label>

                                {plan.enabled && (
                                    <div className="grid grid-cols-2 gap-4 pl-7 md:grid-cols-[1fr_auto_auto] md:items-start">
                                        <div className="col-span-2 space-y-1 md:col-span-1">
                                            <Label
                                                htmlFor={`price-${unit.id}-${period.value}`}
                                                className="sr-only"
                                            >
                                                {t('Price :unit', {
                                                    unit: unitLabel[
                                                        period.value
                                                    ],
                                                })}
                                            </Label>
                                            <div className="flex items-baseline gap-2">
                                                <span className="text-2xl font-semibold">
                                                    $
                                                </span>
                                                <Input
                                                    id={`price-${unit.id}-${period.value}`}
                                                    inputMode="decimal"
                                                    className="tabular h-12 max-w-48 text-2xl font-semibold md:text-2xl"
                                                    value={plan.price}
                                                    placeholder="0"
                                                    onChange={(event) =>
                                                        setPlan(period.value, {
                                                            price: event.target
                                                                .value,
                                                        })
                                                    }
                                                />
                                                <span className="text-sm text-muted-foreground">
                                                    {unitLabel[period.value]}
                                                </span>
                                            </div>
                                            {priceMinor > 0 && (
                                                <p className="tabular text-sm text-muted-foreground">
                                                    {t(
                                                        'You receive :amount after the :percent % commission.',
                                                        {
                                                            amount: formatMoney(
                                                                netAfterCommission(
                                                                    priceMinor,
                                                                    pricing.commission_bp,
                                                                ),
                                                            ),
                                                            percent:
                                                                pricing.commission_bp /
                                                                100,
                                                        },
                                                    )}
                                                </p>
                                            )}
                                            {suggested !== undefined &&
                                                suggested !== priceMinor && (
                                                    <button
                                                        type="button"
                                                        className="text-sm underline underline-offset-4 hover:text-brand"
                                                        onClick={() =>
                                                            setPlan(
                                                                period.value,
                                                                {
                                                                    price: toInputAmount(
                                                                        suggested,
                                                                    ),
                                                                },
                                                            )
                                                        }
                                                    >
                                                        {t(
                                                            'Use the suggested :amount',
                                                            {
                                                                amount: formatMoney(
                                                                    suggested,
                                                                ),
                                                            },
                                                        )}
                                                    </button>
                                                )}
                                            <InputError
                                                message={
                                                    errors[
                                                        `plans.${period.value}.price`
                                                    ]
                                                }
                                            />
                                        </div>
                                        <PeriodField
                                            id={`min-${unit.id}-${period.value}`}
                                            label={rangeLabels[period.value][0]}
                                            value={plan.min_periods}
                                            max={period.max_limit}
                                            onChange={(value) =>
                                                setPlan(period.value, {
                                                    min_periods: value,
                                                })
                                            }
                                            error={
                                                errors[
                                                    `plans.${period.value}.min_periods`
                                                ]
                                            }
                                        />
                                        <PeriodField
                                            id={`max-${unit.id}-${period.value}`}
                                            label={rangeLabels[period.value][1]}
                                            value={plan.max_periods}
                                            max={period.max_limit}
                                            onChange={(value) =>
                                                setPlan(period.value, {
                                                    max_periods: value,
                                                })
                                            }
                                            error={
                                                errors[
                                                    `plans.${period.value}.max_periods`
                                                ]
                                            }
                                        />
                                    </div>
                                )}
                            </li>
                        );
                    })}
                </ul>

                <div className="grid gap-2 sm:max-w-sm">
                    <Label htmlFor={`deposit-${unit.id}`}>{t('Deposit')}</Label>
                    <div className="flex items-center gap-2">
                        <span className="text-muted-foreground">$</span>
                        <Input
                            id={`deposit-${unit.id}`}
                            inputMode="decimal"
                            className="tabular"
                            value={form.data.deposit}
                            placeholder="0"
                            onChange={(event) =>
                                form.setData('deposit', event.target.value)
                            }
                        />
                    </div>
                    {monthlyMinor > 0 &&
                        toMinor(form.data.deposit) !== monthlyMinor && (
                            <button
                                type="button"
                                className="w-fit text-sm underline underline-offset-4 hover:text-brand"
                                onClick={() =>
                                    form.setData(
                                        'deposit',
                                        toInputAmount(monthlyMinor),
                                    )
                                }
                            >
                                {t('Use one month (:amount)', {
                                    amount: formatMoney(monthlyMinor),
                                })}
                            </button>
                        )}
                    <p className="text-xs text-muted-foreground">
                        {t(
                            'Paid once with the first charge and held as a guarantee. It does not pay commission.',
                        )}
                    </p>
                    <InputError message={errors.deposit} />
                </div>

                <FormActions>
                    <Button disabled={form.processing}>
                        {t('Save prices for :unit', { unit: unit.name })}
                    </Button>
                </FormActions>
            </fieldset>
        </form>
    );
}

function PeriodField({
    id,
    label,
    value,
    max,
    onChange,
    error,
}: {
    id: string;
    label: string;
    value: string;
    max: number;
    onChange: (value: string) => void;
    error?: string;
}) {
    return (
        <div className="grid gap-1 md:w-32">
            <Label htmlFor={id} className="text-xs text-muted-foreground">
                {label}
            </Label>
            <Input
                id={id}
                type="number"
                min={1}
                max={max}
                className="tabular"
                value={value}
                onChange={(event) => onChange(event.target.value)}
            />
            {error && <InputError message={error} />}
        </div>
    );
}

function BlocksPanel({ propertyId, units, pricing, disabled }: Props) {
    const { t } = useTranslation();
    const today = new Date().toISOString().slice(0, 10);

    return (
        <div className="space-y-4 border-t pt-8">
            <div className="space-y-1">
                <h3 className="font-semibold">{t('Blocked dates')}</h3>
                <p className="text-sm text-muted-foreground">
                    {t(
                        'For maintenance or your own use. Blocking a space also blocks the space it belongs to and its subdivisions on those dates.',
                    )}
                </p>
            </div>

            {pricing.blocks.length === 0 ? (
                <EmptyState icon={CalendarX} title={t('No blocked dates')} />
            ) : (
                <ul className="divide-y rounded-xl border">
                    {pricing.blocks.map((block) => (
                        <li
                            key={block.id}
                            className="flex flex-wrap items-center gap-3 px-4 py-3"
                        >
                            <div className="min-w-0 flex-1">
                                <p className="tabular font-medium">
                                    {block.starts_on} → {block.ends_on}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {[block.unit_name, block.reason, block.note]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </p>
                            </div>
                            {!disabled && (
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    aria-label={t('Remove block')}
                                    onClick={() =>
                                        router.delete(
                                            destroyBlock.url({
                                                property: propertyId,
                                                block: block.id,
                                            }),
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <Trash2 />
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {!disabled && units.length > 0 && (
                <Form
                    {...storeBlock.form(propertyId)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="grid gap-3 rounded-xl bg-muted p-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-1">
                                <Label htmlFor="block_unit">{t('Space')}</Label>
                                <NativeSelect id="block_unit" name="unit_id">
                                    {units.map((unit) => (
                                        <option key={unit.id} value={unit.id}>
                                            {unit.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="block_starts">
                                    {t('From')}
                                </Label>
                                <Input
                                    id="block_starts"
                                    name="starts_on"
                                    type="date"
                                    min={today}
                                    required
                                />
                                <InputError message={errors.starts_on} />
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="block_ends">
                                    {t('Until (not included)')}
                                </Label>
                                <Input
                                    id="block_ends"
                                    name="ends_on"
                                    type="date"
                                    min={today}
                                    required
                                />
                                <InputError message={errors.ends_on} />
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="block_reason">
                                    {t('Reason')}
                                </Label>
                                <NativeSelect id="block_reason" name="reason">
                                    {pricing.blockReasons.map((reason) => (
                                        <option
                                            key={reason.value}
                                            value={reason.value}
                                        >
                                            {reason.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </div>
                            <div className="grid gap-1 sm:col-span-2 lg:col-span-3">
                                <Label htmlFor="block_note">
                                    {t('Note (optional)')}
                                </Label>
                                <Input id="block_note" name="note" />
                            </div>
                            <div className="flex items-end">
                                <Button
                                    variant="outline"
                                    disabled={processing}
                                    className="w-full"
                                >
                                    {t('Block dates')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            )}
        </div>
    );
}
