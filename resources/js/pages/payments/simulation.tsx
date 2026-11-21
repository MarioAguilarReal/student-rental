import { Form, Head } from '@inertiajs/react';
import FormActions from '@/components/form-actions';
import InputError from '@/components/input-error';
import Page from '@/components/page';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import { edit, update } from '@/routes/payment-simulation';
import type { Option } from '@/types';

/**
 * HU-09.05 · RN-72: what the test gateway answers to every payment.
 */
export default function PaymentSimulation({
    outcome,
    delaySeconds,
    outcomes,
}: {
    outcome: string;
    delaySeconds: number;
    outcomes: Option[];
}) {
    const { t } = useTranslation();
    const help: Record<string, string> = {
        approve: t('Every payment goes through. This is the normal setting.'),
        decline: t('Every payment is declined, so clients see the retry flow.'),
        delay: t(
            'Payments stay in process and are approved after the delay, like a slow bank.',
        ),
    };

    return (
        <>
            <Head title={t('Test payments')} />
            <Page
                title={t('Test payments')}
                description={t(
                    'No real money moves. Choose what the test gateway answers from now on.',
                )}
                width="narrow"
            >
                <Card>
                    <CardContent>
                        <Form
                            {...update.form()}
                            options={{ preserveScroll: true }}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <fieldset className="space-y-3">
                                        <legend className="mb-2 text-sm font-medium">
                                            {t('Result')}
                                        </legend>
                                        {outcomes.map((option) => (
                                            <label
                                                key={option.value}
                                                className="flex cursor-pointer items-start gap-3 rounded-lg border p-3 has-checked:border-primary"
                                            >
                                                <input
                                                    type="radio"
                                                    name="outcome"
                                                    value={option.value}
                                                    defaultChecked={
                                                        option.value === outcome
                                                    }
                                                    className="mt-1"
                                                />
                                                <span className="space-y-0.5">
                                                    <span className="block text-sm font-medium">
                                                        {option.label}
                                                    </span>
                                                    <span className="block text-sm text-muted-foreground">
                                                        {help[
                                                            String(option.value)
                                                        ] ?? ''}
                                                    </span>
                                                </span>
                                            </label>
                                        ))}
                                        <InputError message={errors.outcome} />
                                    </fieldset>

                                    <div className="grid max-w-48 gap-2">
                                        <Label htmlFor="delay_seconds">
                                            {t('Delay (seconds)')}
                                        </Label>
                                        <Input
                                            id="delay_seconds"
                                            name="delay_seconds"
                                            type="number"
                                            min={1}
                                            max={3600}
                                            defaultValue={delaySeconds}
                                            required
                                            className="tabular"
                                        />
                                        <InputError
                                            message={errors.delay_seconds}
                                        />
                                    </div>

                                    <FormActions>
                                        <Button disabled={processing}>
                                            {t('Save')}
                                        </Button>
                                    </FormActions>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </Page>
        </>
    );
}

PaymentSimulation.layout = {
    breadcrumbs: [{ title: 'Test payments', href: edit() }],
};
