import { Form, Head } from '@inertiajs/react';
import FormActions from '@/components/form-actions';
import InputError from '@/components/input-error';
import Page from '@/components/page';
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
import { edit, update } from '@/routes/parameters';

type Parameter = {
    key: string;
    label: string;
    area: string;
    unit: 'hours' | 'days' | 'months' | 'factor';
    value: number;
    default: number;
    min: number;
    max: number;
    decimal: boolean;
};

/** "rentals.request_hours" → "values[rentals][request_hours]" (keys contain dots). */
function fieldName(key: string): string {
    return `values[${key.split('.').join('][')}]`;
}

/**
 * HU-03.05: deadlines, validities and price factors, without a deploy.
 */
export default function Parameters({
    parameters,
}: {
    parameters: Parameter[];
}) {
    const { t } = useTranslation();
    const areas: { key: string; title: string; description: string }[] = [
        {
            key: 'rentals',
            title: t('Rentals'),
            description: t(
                'How long operators have to answer and clients to pay.',
            ),
        },
        {
            key: 'verification',
            title: t('Verification'),
            description: t(
                'How long a verification lasts and when we remind people.',
            ),
        },
        {
            key: 'inspections',
            title: t('Inspections'),
            description: t(
                'How long an approved inspection lasts and when the renewal opens.',
            ),
        },
        {
            key: 'payments',
            title: t('Payments'),
            description: t('When clients are reminded of a charge.'),
        },
        {
            key: 'pricing',
            title: t('Suggested prices'),
            description: t(
                'Weekly: monthly ÷ 4 × factor. Yearly: monthly × 12 × factor.',
            ),
        },
    ];
    const units: Record<Parameter['unit'], string> = {
        hours: t('hours'),
        days: t('days'),
        months: t('months'),
        factor: '×',
    };

    return (
        <>
            <Head title={t('System parameters')} />
            <Page
                title={t('System parameters')}
                description={t(
                    'Changes apply from now on. Rentals, verifications and inspections already dated keep their dates.',
                )}
                width="narrow"
            >
                <Form {...update.form()} options={{ preserveScroll: true }}>
                    {({ processing, errors }) => (
                        <div className="space-y-6">
                            {areas.map((area) => (
                                <Card key={area.key}>
                                    <CardHeader>
                                        <CardTitle>{area.title}</CardTitle>
                                        <CardDescription>
                                            {area.description}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {parameters
                                            .filter(
                                                (parameter) =>
                                                    parameter.area === area.key,
                                            )
                                            .map((parameter) => (
                                                <div
                                                    key={parameter.key}
                                                    className="grid items-center gap-2 sm:grid-cols-[1fr_9rem]"
                                                >
                                                    <div>
                                                        <Label
                                                            htmlFor={
                                                                parameter.key
                                                            }
                                                        >
                                                            {parameter.label}
                                                        </Label>
                                                        <p className="text-xs text-muted-foreground">
                                                            {t(
                                                                'Default :value · between :min and :max',
                                                                {
                                                                    value: parameter.default,
                                                                    min: parameter.min,
                                                                    max: parameter.max,
                                                                },
                                                            )}
                                                        </p>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Input
                                                            id={parameter.key}
                                                            name={fieldName(
                                                                parameter.key,
                                                            )}
                                                            type="number"
                                                            min={parameter.min}
                                                            max={parameter.max}
                                                            step={
                                                                parameter.decimal
                                                                    ? '0.01'
                                                                    : '1'
                                                            }
                                                            defaultValue={
                                                                parameter.value
                                                            }
                                                            required
                                                            className="tabular"
                                                        />
                                                        <span className="w-12 text-sm text-muted-foreground">
                                                            {
                                                                units[
                                                                    parameter
                                                                        .unit
                                                                ]
                                                            }
                                                        </span>
                                                    </div>
                                                    <InputError
                                                        className="sm:col-span-2"
                                                        message={
                                                            errors[
                                                                `values.${parameter.key}`
                                                            ]
                                                        }
                                                    />
                                                </div>
                                            ))}
                                    </CardContent>
                                </Card>
                            ))}
                            <FormActions>
                                <Button disabled={processing}>
                                    {t('Save')}
                                </Button>
                            </FormActions>
                        </div>
                    )}
                </Form>
            </Page>
        </>
    );
}

Parameters.layout = {
    breadcrumbs: [{ title: 'System parameters', href: edit() }],
};
