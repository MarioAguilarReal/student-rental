import { Form, Head } from '@inertiajs/react';
import Page from '@/components/page';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTranslation } from '@/lib/i18n';
import { index, store } from '@/routes/properties';
import type { Option } from '@/types';

type Props = {
    propertyTypes: Option[];
    managementModes: Option[];
    canCreateOnBehalf: boolean;
};

export default function PropertyCreate({
    propertyTypes,
    managementModes,
    canCreateOnBehalf,
}: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('New property')} />
            <Page
                width="narrow"
                title={t('New property')}
                description={t(
                    'Start with the basics. You will add the address, spaces and photos next.',
                )}
                back={{ href: index(), label: t('My properties') }}
            >
                <div className="space-y-6">
                    <Card>
                        <CardContent className="pt-6">
                            <Form {...store.form()} className="space-y-4">
                                {({ processing, errors }) => (
                                    <>
                                        {canCreateOnBehalf && (
                                            <div className="grid gap-2">
                                                <Label htmlFor="owner_email">
                                                    {t('Owner email')}
                                                </Label>
                                                <Input
                                                    id="owner_email"
                                                    name="owner_email"
                                                    type="email"
                                                    placeholder={t(
                                                        'Leave empty to register it as yours',
                                                    )}
                                                />
                                                <InputError
                                                    message={errors.owner_email}
                                                />
                                            </div>
                                        )}
                                        <div className="grid gap-2">
                                            <Label htmlFor="title">
                                                {t('Title')}
                                            </Label>
                                            <Input
                                                id="title"
                                                name="title"
                                                required
                                                placeholder={t(
                                                    'e.g. Bright house near Campus Centro',
                                                )}
                                            />
                                            <InputError
                                                message={errors.title}
                                            />
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="property_type">
                                                    {t('Property type')}
                                                </Label>
                                                <NativeSelect
                                                    id="property_type"
                                                    name="property_type"
                                                >
                                                    {propertyTypes.map(
                                                        (option) => (
                                                            <option
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </NativeSelect>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="management_mode">
                                                    {t('Management')}
                                                </Label>
                                                <NativeSelect
                                                    id="management_mode"
                                                    name="management_mode"
                                                >
                                                    {managementModes.map(
                                                        (option) => (
                                                            <option
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </NativeSelect>
                                            </div>
                                        </div>
                                        <Button disabled={processing}>
                                            {t('Create property')}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>
            </Page>
        </>
    );
}

PropertyCreate.layout = {
    breadcrumbs: [{ title: 'Properties', href: index() }],
};
