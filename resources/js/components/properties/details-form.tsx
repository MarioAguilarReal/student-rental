import { Form } from '@inertiajs/react';
import FormActions from '@/components/form-actions';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { update } from '@/routes/properties';
import type { Option, PropertyDetail } from '@/types';

type Props = {
    property: PropertyDetail;
    propertyTypes: Option[];
    managementModes: Option[];
    disabled: boolean;
};

export default function DetailsForm({
    property,
    propertyTypes,
    managementModes,
    disabled,
}: Props) {
    const { t } = useTranslation();

    return (
        <section className="space-y-6">
            <header className="space-y-1">
                <h2 className="text-lg font-semibold">
                    {t('General information')}
                </h2>
            </header>
            <div>
                <Form
                    {...update.form(property.id)}
                    options={{ preserveScroll: true }}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <fieldset disabled={disabled} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="title">{t('Title')}</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    defaultValue={property.title}
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    {t('Description')}
                                </Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    rows={5}
                                    defaultValue={property.description ?? ''}
                                    placeholder={t(
                                        'What makes it a good place to live while studying or teaching?',
                                    )}
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="property_type">
                                        {t('Property type')}
                                    </Label>
                                    <NativeSelect
                                        id="property_type"
                                        name="property_type"
                                        defaultValue={property.property_type}
                                    >
                                        {propertyTypes.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="management_mode">
                                        {t('Management')}
                                    </Label>
                                    <NativeSelect
                                        id="management_mode"
                                        name="management_mode"
                                        defaultValue={property.management_mode}
                                    >
                                        {managementModes.map((option) => (
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
                                            'Self-managed: you approve rentals (10% commission). Managed: our team operates it (20%). A change applies to new rentals only.',
                                        )}
                                    </p>
                                </div>
                            </div>
                            <FormActions>
                                <Button disabled={processing}>
                                    {t('Save')}
                                </Button>
                            </FormActions>
                        </fieldset>
                    )}
                </Form>
            </div>
        </section>
    );
}
