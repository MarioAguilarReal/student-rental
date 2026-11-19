import { Form, Head } from '@inertiajs/react';
import { ExternalLink, FileText } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import VerificationStatusBadge from '@/components/verification-status-badge';
import { useTranslation } from '@/lib/i18n';
import { approve, index, reject } from '@/routes/verifications/reviews';
import type { VerificationStatus } from '@/types';

type Props = {
    profile: {
        id: number;
        user: {
            id: number;
            name: string;
            email: string;
            birth_date: string | null;
        };
        kind: string;
        status: VerificationStatus;
        status_label: string;
        community_type: string | null;
        institution: string | null;
        stay_ends_on: string | null;
        submitted_at: string | null;
        reviewed_at: string | null;
        reviewer: string | null;
        rejection_reason: string | null;
        verified_until: string | null;
        documents: {
            id: number;
            type: string;
            status: string;
            status_label: string;
            name: string;
            mime_type: string;
            url: string;
            uploaded_at: string | null;
        }[];
    };
    canDecide: boolean;
};

export default function ReviewShow({ profile, canDecide }: Props) {
    const { t } = useTranslation();

    const details: [string, string | null][] = [
        ['Email', profile.user.email],
        ['Birth date', profile.user.birth_date],
        ['Profile type', profile.kind],
        ['Community', profile.community_type],
        ['Institution', profile.institution],
        ['End of stay', profile.stay_ends_on],
        ['Submitted', profile.submitted_at],
        ['Verified until', profile.verified_until],
        ['Reviewed by', profile.reviewer],
    ];

    return (
        <>
            <Head title={t('Review :name', { name: profile.user.name })} />

            <Page
                title={profile.user.name}
                description={t('Verification request')}
                back={{ href: index(), label: t('Verifications') }}
                meta={
                    <VerificationStatusBadge
                        status={profile.status}
                        label={profile.status_label}
                    />
                }
            >
                <div className="space-y-6">
                    <div className="grid gap-6 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Applicant')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                                    {details
                                        .filter(([, value]) => value)
                                        .map(([label, value]) => (
                                            <div
                                                key={label}
                                                className="contents"
                                            >
                                                <dt className="text-muted-foreground">
                                                    {t(label)}
                                                </dt>
                                                <dd className="break-words">
                                                    {value}
                                                </dd>
                                            </div>
                                        ))}
                                </dl>
                                {profile.rejection_reason && (
                                    <p className="mt-4 rounded-md bg-destructive/10 p-3 text-sm">
                                        {t('Rejection reason')}:{' '}
                                        {profile.rejection_reason}
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Documents')}</CardTitle>
                                <CardDescription>
                                    {t(
                                        'Links are private and expire in 5 minutes.',
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ul className="divide-y rounded-md border text-sm">
                                    {profile.documents.map((document) => (
                                        <li
                                            key={document.id}
                                            className="flex items-center justify-between gap-2 px-3 py-2"
                                        >
                                            <span className="flex min-w-0 items-center gap-2">
                                                <FileText className="size-4 shrink-0 text-muted-foreground" />
                                                <span className="min-w-0">
                                                    <span className="block truncate">
                                                        {document.type}
                                                    </span>
                                                    <span className="block truncate text-xs text-muted-foreground">
                                                        {document.name} ·{' '}
                                                        {document.status_label}
                                                    </span>
                                                </span>
                                            </span>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <a
                                                    href={document.url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    {t('Open')}
                                                    <ExternalLink className="size-3" />
                                                </a>
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    </div>

                    {canDecide && (
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Decision')}</CardTitle>
                                <CardDescription>
                                    {t(
                                        'Approve only if the documents are legible, current and match the applicant.',
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-6 md:grid-cols-2">
                                <Form {...approve.form(profile.id)}>
                                    {({ processing, errors }) => (
                                        <div className="space-y-2">
                                            <Button
                                                disabled={processing}
                                                className="w-full"
                                            >
                                                {processing && <Spinner />}
                                                {t('Approve verification')}
                                            </Button>
                                            <InputError
                                                message={errors.domain}
                                            />
                                        </div>
                                    )}
                                </Form>

                                <Form
                                    {...reject.form(profile.id)}
                                    className="space-y-2"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <Label htmlFor="reason">
                                                {t('Rejection reason')}
                                            </Label>
                                            <Textarea
                                                id="reason"
                                                name="reason"
                                                required
                                                placeholder={t(
                                                    'Explain what the applicant must fix.',
                                                )}
                                            />
                                            <InputError
                                                message={errors.reason}
                                            />
                                            <Button
                                                variant="destructive"
                                                disabled={processing}
                                                className="w-full"
                                            >
                                                {t('Reject')}
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </Page>
        </>
    );
}

ReviewShow.layout = {
    breadcrumbs: [{ title: 'Verifications', href: index() }],
};
