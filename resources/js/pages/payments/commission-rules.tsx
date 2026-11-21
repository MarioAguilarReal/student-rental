import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import Page from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import { destroy, index, store } from '@/routes/commission-rules';
import type { CommissionRuleRow } from '@/types';

type Mode = { value: string; label: string; rules: CommissionRuleRow[] };

const stateTones: Record<
    CommissionRuleRow['state'],
    'positive' | 'warning' | 'neutral'
> = {
    current: 'positive',
    scheduled: 'warning',
    past: 'neutral',
};

/**
 * HU-03.04 · RN-50: commission by management mode, with the dates each rate applies.
 */
export default function CommissionRules({
    modes,
    today,
}: {
    modes: Mode[];
    today: string;
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Commissions')} />
            <Page
                title={t('Commissions')}
                description={t(
                    'The rate is taken from the rent only, never from the deposit. Each rental keeps the rate it had when it was requested.',
                )}
            >
                <div className="grid gap-6 md:grid-cols-2">
                    {modes.map((mode) => (
                        <ModeCard key={mode.value} mode={mode} today={today} />
                    ))}
                </div>
            </Page>
        </>
    );
}

function ModeCard({ mode, today }: { mode: Mode; today: string }) {
    const { t } = useTranslation();
    const stateLabels: Record<CommissionRuleRow['state'], string> = {
        current: t('In force'),
        scheduled: t('Scheduled'),
        past: t('Ended'),
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{mode.label}</CardTitle>
                <CardDescription>
                    {t('A new rate closes the previous one the day before.')}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <ul className="divide-y rounded-lg border text-sm">
                    {mode.rules.map((rule) => (
                        <li
                            key={rule.id}
                            className="flex flex-wrap items-center gap-3 px-3 py-2"
                        >
                            <span className="tabular w-16 font-medium">
                                {rule.percentage_bp / 100} %
                            </span>
                            <span className="tabular flex-1 text-muted-foreground">
                                {rule.valid_until
                                    ? `${rule.valid_from} → ${rule.valid_until}`
                                    : t('From :date', {
                                          date: rule.valid_from,
                                      })}
                            </span>
                            <Badge variant={stateTones[rule.state]}>
                                {stateLabels[rule.state]}
                            </Badge>
                            {rule.state === 'scheduled' && (
                                <Form
                                    {...destroy.form(rule.id)}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            disabled={processing}
                                        >
                                            {t('Remove')}
                                        </Button>
                                    )}
                                </Form>
                            )}
                        </li>
                    ))}
                </ul>

                <Form
                    {...store.form()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="grid items-end gap-3 sm:grid-cols-[8rem_1fr_auto]"
                >
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="hidden"
                                name="management_mode"
                                value={mode.value}
                            />
                            <div className="grid gap-2">
                                <Label htmlFor={`percentage-${mode.value}`}>
                                    {t('New rate (%)')}
                                </Label>
                                <Input
                                    id={`percentage-${mode.value}`}
                                    name="percentage"
                                    type="number"
                                    min={0}
                                    max={100}
                                    step="0.01"
                                    required
                                    className="tabular"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`valid-from-${mode.value}`}>
                                    {t('Starts on')}
                                </Label>
                                <Input
                                    id={`valid-from-${mode.value}`}
                                    name="valid_from"
                                    type="date"
                                    min={today}
                                    required
                                />
                            </div>
                            <Button disabled={processing}>
                                {t('Schedule')}
                            </Button>
                            <div className="sm:col-span-3">
                                <InputError
                                    message={
                                        errors.percentage ??
                                        errors.valid_from ??
                                        errors.domain
                                    }
                                />
                            </div>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}

CommissionRules.layout = {
    breadcrumbs: [{ title: 'Commissions', href: index() }],
};
