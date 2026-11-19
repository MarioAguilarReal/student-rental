import { Form, Head } from '@inertiajs/react';
import Page from '@/components/page';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/lib/i18n';
import { index, store, update } from '@/routes/catalog/house-rules';

type HouseRule = {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
};

export default function HouseRulesIndex({
    houseRules,
}: {
    houseRules: HouseRule[];
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('House rules')} />
            <Page
                title={t('House rules')}
                description={t(
                    'Each property states whether every rule is allowed. Tenants accept them when they request a rental.',
                )}
            >
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Add rule')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <RuleRow />
                        </CardContent>
                    </Card>
                    <ul className="space-y-2">
                        {houseRules.map((rule) => (
                            <li key={rule.id} className="rounded-lg border p-3">
                                <RuleRow rule={rule} />
                            </li>
                        ))}
                    </ul>
                </div>
            </Page>
        </>
    );
}

function RuleRow({ rule }: { rule?: HouseRule }) {
    const { t } = useTranslation();

    return (
        <Form
            {...(rule ? update.form(rule.id) : store.form())}
            options={{ preserveScroll: true }}
            resetOnSuccess={!rule}
            transform={(data) => ({
                ...data,
                is_active: data.is_active === 'on',
            })}
            className="grid items-start gap-2 sm:grid-cols-[12rem_1fr_auto_auto]"
        >
            {({ processing, errors }) => (
                <>
                    <div>
                        <Input
                            name="name"
                            required
                            defaultValue={rule?.name}
                            aria-label={t('Name')}
                            placeholder={t('Name')}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <Input
                        name="description"
                        defaultValue={rule?.description ?? ''}
                        aria-label={t('Description')}
                        placeholder={t('Description')}
                    />
                    <label className="flex h-9 items-center gap-2 text-sm">
                        <Checkbox
                            name="is_active"
                            defaultChecked={rule?.is_active ?? true}
                        />
                        {t('Active')}
                    </label>
                    <Button
                        variant={rule ? 'outline' : 'default'}
                        disabled={processing}
                    >
                        {rule ? t('Save') : t('Add')}
                    </Button>
                </>
            )}
        </Form>
    );
}

HouseRulesIndex.layout = {
    breadcrumbs: [{ title: 'House rules', href: index() }],
};
