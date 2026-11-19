import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import VerificationStatusBadge from '@/components/verification-status-badge';
import { useTranslation } from '@/lib/i18n';
import { dashboard } from '@/routes';
import { show as verification } from '@/routes/verifications';
import { index as reviews } from '@/routes/verifications/reviews';
import type { VerificationStatus } from '@/types';

type Props = {
    verifications: {
        id: number;
        kind_label: string;
        status: VerificationStatus;
        status_label: string;
        verified_until: string | null;
        needs_action: boolean;
    }[];
    pendingReviews: number | null;
};

export default function Dashboard({ verifications, pendingReviews }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Dashboard')} />

            <div className="grid auto-rows-min gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
                {verifications.map((profile) => (
                    <Card key={profile.id}>
                        <CardHeader>
                            <div className="flex items-center justify-between gap-2">
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
                                {profile.needs_action
                                    ? t('Complete your verification to start.')
                                    : profile.verified_until
                                      ? t('Verified until :date.', {
                                            date: profile.verified_until,
                                        })
                                      : profile.status_label}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button
                                asChild
                                variant={
                                    profile.needs_action ? 'default' : 'outline'
                                }
                                size="sm"
                            >
                                <Link href={verification()}>
                                    {t('Go to my verification')}
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ))}

                {pendingReviews !== null && (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {t('Verifications to review')}
                            </CardTitle>
                            <CardDescription>
                                {t(':count waiting for review', {
                                    count: pendingReviews,
                                })}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild variant="outline" size="sm">
                                <Link href={reviews()}>
                                    {t('Open review queue')}
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {verifications.length === 0 && pendingReviews === null && (
                    <p className="rounded-lg border border-dashed p-8 text-sm text-muted-foreground md:col-span-2">
                        {t('There is nothing for you here yet.')}
                    </p>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
