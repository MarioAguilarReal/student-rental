import { Form, Head } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import VerificationStatusBadge from '@/components/verification-status-badge';
import { useTranslation } from '@/lib/i18n';
import { store as addAccountProfile } from '@/routes/account-profiles';
import { show } from '@/routes/verifications';
import { store as storeClient } from '@/routes/verifications/client';
import { store as storeOwner } from '@/routes/verifications/owner';
import type { Option, VerificationStatus } from '@/types';

type VerificationDocument = {
    id: number;
    type: string;
    status: string;
    status_label: string;
    name: string;
    uploaded_at: string | null;
};

type Profile = {
    id: number;
    kind: 'client' | 'owner';
    kind_label: string;
    status: VerificationStatus;
    status_label: string;
    accepts_submissions: boolean;
    community_type: string | null;
    institution_id: number | null;
    institution: string | null;
    stay_ends_on: string | null;
    submitted_at: string | null;
    rejection_reason: string | null;
    verified_until: string | null;
    documents: VerificationDocument[];
};

type Props = {
    profiles: Profile[];
    institutions: Option<number>[];
    communityTypes: Option[];
    missingProfiles: Option[];
};

const ACCEPTED_FILES = '.jpg,.jpeg,.png,.pdf';

export default function VerificationShow({
    profiles,
    institutions,
    communityTypes,
    missingProfiles,
}: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('My verification')} />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <Heading
                    title={t('My verification')}
                    description={t(
                        'We verify every member of the community to keep it safe.',
                    )}
                />

                {profiles.map((profile) => (
                    <Card key={profile.id}>
                        <CardHeader>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <CardTitle>
                                    {t(':kind profile', {
                                        kind: profile.kind_label,
                                    })}
                                </CardTitle>
                                <VerificationStatusBadge
                                    status={profile.status}
                                    label={profile.status_label}
                                />
                            </div>
                            <CardDescription>
                                <StatusMessage profile={profile} />
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-6">
                            {profile.documents.length > 0 && (
                                <DocumentList documents={profile.documents} />
                            )}

                            {profile.accepts_submissions &&
                                (profile.kind === 'client' ? (
                                    <ClientForm
                                        profile={profile}
                                        institutions={institutions}
                                        communityTypes={communityTypes}
                                    />
                                ) : (
                                    <OwnerForm />
                                ))}
                        </CardContent>
                    </Card>
                ))}

                {missingProfiles.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Add another profile')}</CardTitle>
                            <CardDescription>
                                {t(
                                    'You can rent and list properties with the same account.',
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-wrap gap-2">
                            {missingProfiles.map((option) => (
                                <Form
                                    key={option.value}
                                    {...addAccountProfile.form()}
                                    transform={(data) => ({
                                        ...data,
                                        role: option.value,
                                    })}
                                >
                                    {({ processing }) => (
                                        <Button
                                            variant="outline"
                                            disabled={processing}
                                        >
                                            {t('Add :profile profile', {
                                                profile: option.label,
                                            })}
                                        </Button>
                                    )}
                                </Form>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

function StatusMessage({ profile }: { profile: Profile }) {
    const { t } = useTranslation();

    switch (profile.status) {
        case 'unverified':
            return t('Confirm your email address to continue.');
        case 'email_verified':
            return t('Upload your documents to get verified.');
        case 'in_review':
            return t('We are reviewing the documents you sent on :date.', {
                date: profile.submitted_at ?? '',
            });
        case 'verified':
            return profile.verified_until
                ? t('Verified until :date.', { date: profile.verified_until })
                : t('Your identity is verified.');
        case 'rejected':
            return t('Not approved: :reason Upload your documents again.', {
                reason: profile.rejection_reason ?? '',
            });
        case 'expired':
            return t(
                'Your verification expired. Upload your current documents to renew it.',
            );
    }
}

function DocumentList({ documents }: { documents: VerificationDocument[] }) {
    const { t } = useTranslation();

    return (
        <div className="space-y-2">
            <h3 className="text-sm font-medium">{t('Documents sent')}</h3>
            <ul className="divide-y rounded-md border text-sm">
                {documents.map((document) => (
                    <li
                        key={document.id}
                        className="flex flex-wrap items-center justify-between gap-2 px-3 py-2"
                    >
                        <span className="flex min-w-0 items-center gap-2">
                            <FileText className="size-4 shrink-0 text-muted-foreground" />
                            <span className="truncate">
                                {document.type} · {document.name}
                            </span>
                        </span>
                        <span className="text-muted-foreground">
                            {document.status_label}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function ClientForm({
    profile,
    institutions,
    communityTypes,
}: {
    profile: Profile;
    institutions: Option<number>[];
    communityTypes: Option[];
}) {
    const { t } = useTranslation();
    const [communityType, setCommunityType] = useState(
        profile.community_type ?? '',
    );
    const isExchange = communityType === 'exchange_student';

    return (
        <Form
            {...storeClient.form()}
            options={{ preserveScroll: true }}
            className="space-y-4"
        >
            {({ processing, errors }) => (
                <>
                    {errors.domain && <InputError message={errors.domain} />}

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="community_type">{t('I am')}</Label>
                            <Select
                                name="community_type"
                                value={communityType}
                                onValueChange={setCommunityType}
                            >
                                <SelectTrigger id="community_type">
                                    <SelectValue placeholder={t('Select')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {communityTypes.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.community_type} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="institution_id">
                                {isExchange
                                    ? t('Host institution')
                                    : t('Institution')}
                            </Label>
                            <Select
                                name="institution_id"
                                defaultValue={
                                    profile.institution_id
                                        ? String(profile.institution_id)
                                        : undefined
                                }
                            >
                                <SelectTrigger id="institution_id">
                                    <SelectValue placeholder={t('Select')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {institutions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={String(option.value)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.institution_id} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="credential">
                            {t('Institutional ID card')}
                            {isExchange && (
                                <span className="font-normal text-muted-foreground">
                                    {' '}
                                    ({t('or acceptance letter')})
                                </span>
                            )}
                        </Label>
                        <Input
                            id="credential"
                            name="credential"
                            type="file"
                            accept={ACCEPTED_FILES}
                        />
                        <p className="text-xs text-muted-foreground">
                            {t('JPG, PNG or PDF, up to 10 MB.')}
                        </p>
                        <InputError message={errors.credential} />
                    </div>

                    {isExchange && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="acceptance_letter">
                                    {t('Acceptance letter')}
                                </Label>
                                <Input
                                    id="acceptance_letter"
                                    name="acceptance_letter"
                                    type="file"
                                    accept={ACCEPTED_FILES}
                                />
                                <InputError
                                    message={errors.acceptance_letter}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="stay_ends_on">
                                    {t('End of stay')}
                                </Label>
                                <Input
                                    id="stay_ends_on"
                                    name="stay_ends_on"
                                    type="date"
                                    defaultValue={profile.stay_ends_on ?? ''}
                                />
                                <InputError message={errors.stay_ends_on} />
                            </div>
                        </div>
                    )}

                    <Button disabled={processing}>
                        {processing && <Spinner />}
                        {t('Send for review')}
                    </Button>
                </>
            )}
        </Form>
    );
}

function OwnerForm() {
    const { t } = useTranslation();

    return (
        <Form
            {...storeOwner.form()}
            options={{ preserveScroll: true }}
            className="space-y-4"
        >
            {({ processing, errors }) => (
                <>
                    {errors.domain && <InputError message={errors.domain} />}

                    <div className="grid gap-2">
                        <Label htmlFor="official_id">{t('Official ID')}</Label>
                        <Input
                            id="official_id"
                            name="official_id"
                            type="file"
                            accept={ACCEPTED_FILES}
                        />
                        <p className="text-xs text-muted-foreground">
                            {t(
                                'Passport, voter ID or another government ID. JPG, PNG or PDF, up to 10 MB.',
                            )}
                        </p>
                        <InputError message={errors.official_id} />
                    </div>

                    <p className="text-xs text-muted-foreground">
                        {t(
                            'You will attach the proof of ownership of each property when you request its inspection.',
                        )}
                    </p>

                    <Button disabled={processing}>
                        {processing && <Spinner />}
                        {t('Send for review')}
                    </Button>
                </>
            )}
        </Form>
    );
}

VerificationShow.layout = {
    breadcrumbs: [{ title: 'My verification', href: show() }],
};
