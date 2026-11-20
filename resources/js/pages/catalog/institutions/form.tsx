import { Form, Head } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import Page from '@/components/page';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { index, store, update } from '@/routes/catalog/institutions';
import {
    destroy as destroyCampus,
    store as storeCampus,
    update as updateCampus,
} from '@/routes/catalog/institutions/campuses';

type Campus = {
    id: number;
    name: string;
    address: string | null;
    latitude: string;
    longitude: string;
};

type Props = {
    institution: {
        id: number;
        name: string;
        acronym: string | null;
        email_domains: string[];
        is_active: boolean;
        campuses: Campus[];
    } | null;
};

export default function InstitutionForm({ institution }: Props) {
    const { t } = useTranslation();
    const title = institution ? institution.name : t('New institution');

    return (
        <>
            <Head title={title} />

            <Page
                width="narrow"
                title={title}
                back={{ href: index(), label: t('Institutions') }}
            >
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Details')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...(institution
                                    ? update.form(institution.id)
                                    : store.form())}
                                options={{ preserveScroll: true }}
                                transform={(data) => ({
                                    ...data,
                                    is_active:
                                        data.is_active === 'on' ||
                                        data.is_active === true,
                                })}
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-4 sm:grid-cols-[1fr_10rem]">
                                            <div className="grid gap-2">
                                                <Label htmlFor="name">
                                                    {t('Name')}
                                                </Label>
                                                <Input
                                                    id="name"
                                                    name="name"
                                                    required
                                                    defaultValue={
                                                        institution?.name
                                                    }
                                                />
                                                <InputError
                                                    message={errors.name}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="acronym">
                                                    {t('Acronym')}
                                                </Label>
                                                <Input
                                                    id="acronym"
                                                    name="acronym"
                                                    defaultValue={
                                                        institution?.acronym ??
                                                        ''
                                                    }
                                                />
                                                <InputError
                                                    message={errors.acronym}
                                                />
                                            </div>
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="email_domains">
                                                {t('Email domains')}
                                            </Label>
                                            <Input
                                                id="email_domains"
                                                name="email_domains"
                                                required
                                                placeholder="uan.edu.mx, uan.mx"
                                                defaultValue={institution?.email_domains.join(
                                                    ', ',
                                                )}
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                {t(
                                                    'Separate them with commas. Subdomains are included (alumnos.uni.mx matches uni.mx).',
                                                )}
                                            </p>
                                            <InputError
                                                message={
                                                    errors.email_domains ??
                                                    errors['email_domains.0']
                                                }
                                            />
                                        </div>

                                        <div className="flex items-center gap-3">
                                            <Checkbox
                                                id="is_active"
                                                name="is_active"
                                                defaultChecked={
                                                    institution?.is_active ??
                                                    true
                                                }
                                            />
                                            <Label htmlFor="is_active">
                                                {t(
                                                    'Active (members can select it)',
                                                )}
                                            </Label>
                                        </div>

                                        <Button disabled={processing}>
                                            {processing && <Spinner />}
                                            {t('Save')}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>

                    {institution && (
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Campuses')}</CardTitle>
                                <CardDescription>
                                    {t(
                                        'Coordinates in decimal degrees, e.g. 21.4920, -104.8942.',
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {institution.campuses.map((campus) => (
                                    <CampusForm
                                        key={campus.id}
                                        institutionId={institution.id}
                                        campus={campus}
                                    />
                                ))}
                                <div className="space-y-2">
                                    <h3 className="text-sm font-medium">
                                        {t('Add campus')}
                                    </h3>
                                    <CampusForm
                                        institutionId={institution.id}
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </Page>
        </>
    );
}

function CampusForm({
    institutionId,
    campus,
}: {
    institutionId: number;
    campus?: Campus;
}) {
    const { t } = useTranslation();
    const args = campus
        ? { institution: institutionId, campus: campus.id }
        : null;

    return (
        <div className="rounded-lg border p-4">
            <Form
                {...(args
                    ? updateCampus.form(args)
                    : storeCampus.form(institutionId))}
                options={{ preserveScroll: true }}
                resetOnSuccess={!campus}
                className="grid gap-3 sm:grid-cols-2"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-1">
                            <Label htmlFor={`name-${campus?.id ?? 'new'}`}>
                                {t('Name')}
                            </Label>
                            <Input
                                id={`name-${campus?.id ?? 'new'}`}
                                name="name"
                                required
                                defaultValue={campus?.name}
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor={`address-${campus?.id ?? 'new'}`}>
                                {t('Address')}
                            </Label>
                            <Input
                                id={`address-${campus?.id ?? 'new'}`}
                                name="address"
                                defaultValue={campus?.address ?? ''}
                            />
                            <InputError message={errors.address} />
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor={`latitude-${campus?.id ?? 'new'}`}>
                                {t('Latitude')}
                            </Label>
                            <Input
                                id={`latitude-${campus?.id ?? 'new'}`}
                                name="latitude"
                                inputMode="decimal"
                                required
                                defaultValue={campus?.latitude}
                            />
                            <InputError message={errors.latitude} />
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor={`longitude-${campus?.id ?? 'new'}`}>
                                {t('Longitude')}
                            </Label>
                            <Input
                                id={`longitude-${campus?.id ?? 'new'}`}
                                name="longitude"
                                inputMode="decimal"
                                required
                                defaultValue={campus?.longitude}
                            />
                            <InputError message={errors.longitude} />
                        </div>
                        <div className="flex gap-2 sm:col-span-2">
                            <Button size="sm" disabled={processing}>
                                {campus ? t('Save') : t('Add campus')}
                            </Button>
                        </div>
                    </>
                )}
            </Form>
            {args && (
                <Form
                    {...destroyCampus.form(args)}
                    options={{ preserveScroll: true }}
                    className="mt-2"
                >
                    {({ processing }) => (
                        <Button
                            size="sm"
                            variant="ghost"
                            disabled={processing}
                            className="text-destructive"
                        >
                            <Trash2 />
                            {t('Delete campus')}
                        </Button>
                    )}
                </Form>
            )}
        </div>
    );
}

InstitutionForm.layout = {
    breadcrumbs: [{ title: 'Institutions', href: index() }],
};
