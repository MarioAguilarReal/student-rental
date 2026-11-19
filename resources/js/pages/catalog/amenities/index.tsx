import { Form, Head } from '@inertiajs/react';
import Page from '@/components/page';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { useTranslation } from '@/lib/i18n';
import { index, store, update } from '@/routes/catalog/amenities';
import type { Option } from '@/types';

type Amenity = {
    id: number;
    name: string;
    category: string;
    scope: string;
    is_active: boolean;
};
type Props = { amenities: Amenity[]; categories: Option[]; scopes: Option[] };

export default function AmenitiesIndex({
    amenities,
    categories,
    scopes,
}: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Amenities')} />
            <Page
                title={t('Amenities')}
                description={t(
                    'Catalog that owners pick from. Deactivating one hides it for new selections; properties that already have it keep it.',
                )}
            >
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Add amenity')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <AmenityRow
                                categories={categories}
                                scopes={scopes}
                            />
                        </CardContent>
                    </Card>
                    <ul className="space-y-2">
                        {amenities.map((amenity) => (
                            <li
                                key={amenity.id}
                                className="rounded-lg border p-3"
                            >
                                <AmenityRow
                                    amenity={amenity}
                                    categories={categories}
                                    scopes={scopes}
                                />
                            </li>
                        ))}
                    </ul>
                </div>
            </Page>
        </>
    );
}

function AmenityRow({
    amenity,
    categories,
    scopes,
}: {
    amenity?: Amenity;
    categories: Option[];
    scopes: Option[];
}) {
    const { t } = useTranslation();

    return (
        <Form
            {...(amenity ? update.form(amenity.id) : store.form())}
            options={{ preserveScroll: true }}
            resetOnSuccess={!amenity}
            transform={(data) => ({
                ...data,
                is_active: data.is_active === 'on',
            })}
            className="grid items-start gap-2 sm:grid-cols-[1fr_10rem_10rem_auto_auto]"
        >
            {({ processing, errors }) => (
                <>
                    <div>
                        <Input
                            name="name"
                            required
                            defaultValue={amenity?.name}
                            aria-label={t('Name')}
                            placeholder={t('Name')}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <NativeSelect
                        name="category"
                        defaultValue={amenity?.category}
                        aria-label={t('Category')}
                    >
                        {categories.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <NativeSelect
                        name="scope"
                        defaultValue={amenity?.scope ?? 'both'}
                        aria-label={t('Where it applies')}
                    >
                        {scopes.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <label className="flex h-9 items-center gap-2 text-sm">
                        <Checkbox
                            name="is_active"
                            defaultChecked={amenity?.is_active ?? true}
                        />
                        {t('Active')}
                    </label>
                    <Button
                        variant={amenity ? 'outline' : 'default'}
                        disabled={processing}
                    >
                        {amenity ? t('Save') : t('Add')}
                    </Button>
                </>
            )}
        </Form>
    );
}

AmenitiesIndex.layout = {
    breadcrumbs: [{ title: 'Amenities', href: index() }],
};
