import { Form } from '@inertiajs/react';
import { useState } from 'react';
import FormActions from '@/components/form-actions';
import InputError from '@/components/input-error';
import MapPicker from '@/components/properties/map-picker';
import ReinspectionNotice from '@/components/properties/reinspection-notice';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import { update } from '@/routes/properties/location';
import type { PropertyDetail } from '@/types';

const FIELDS = [
    ['street', 'Street', true],
    ['exterior_number', 'Exterior number', true],
    ['interior_number', 'Interior number', false],
    ['neighborhood', 'Neighborhood', true],
    ['city', 'City', true],
    ['state', 'State', true],
    ['postal_code', 'Postal code', true],
] as const;

export default function LocationForm({
    property,
    disabled,
}: {
    property: PropertyDetail;
    disabled: boolean;
}) {
    const { t } = useTranslation();
    const [position, setPosition] = useState({
        latitude: property.latitude,
        longitude: property.longitude,
    });

    return (
        <section className="space-y-6">
            <header className="space-y-1">
                <h2 className="text-lg font-semibold">{t('Location')}</h2>
                <p className="text-sm text-muted-foreground">
                    {t(
                        'The exact address is only shown to tenants once their rental is confirmed. The catalog shows the neighborhood.',
                    )}
                </p>
            </header>
            <div>
                <Form
                    {...update.form(property.id)}
                    options={{ preserveScroll: true }}
                    transform={(data) => ({ ...data, ...position })}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <fieldset disabled={disabled} className="space-y-4">
                            <ReinspectionNotice
                                show={property.passed_inspection}
                            />
                            <div className="grid gap-4 sm:grid-cols-2">
                                {FIELDS.map(([name, label, required]) => (
                                    <div key={name} className="grid gap-2">
                                        <Label htmlFor={name}>{t(label)}</Label>
                                        <Input
                                            id={name}
                                            name={name}
                                            required={required}
                                            defaultValue={property[name] ?? ''}
                                            inputMode={
                                                name === 'postal_code'
                                                    ? 'numeric'
                                                    : undefined
                                            }
                                        />
                                        <InputError message={errors[name]} />
                                    </div>
                                ))}
                            </div>
                            <MapPicker
                                latitude={position.latitude}
                                longitude={position.longitude}
                                onChange={(latitude, longitude) =>
                                    setPosition({ latitude, longitude })
                                }
                                disabled={disabled}
                            />
                            <InputError
                                message={errors.latitude ?? errors.longitude}
                            />
                            <FormActions>
                                <Button disabled={processing}>
                                    {t('Save location')}
                                </Button>
                            </FormActions>
                        </fieldset>
                    )}
                </Form>
            </div>
        </section>
    );
}
