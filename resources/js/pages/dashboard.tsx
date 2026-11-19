import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    CalendarDays,
    ClipboardList,
    ClipboardCheck,
    Home,
    Plus,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import ListingCard from '@/components/listing-card';
import Page, { PageSection } from '@/components/page';
import PropertyStatusBadge from '@/components/properties/property-status-badge';
import { Button } from '@/components/ui/button';
import VerificationStatusBadge from '@/components/verification-status-badge';
import { useTranslation } from '@/lib/i18n';
import { dashboard } from '@/routes';
import {
    create as createProperty,
    edit as editProperty,
    index as myProperties,
} from '@/routes/properties';
import { agenda as inspectionAgenda } from '@/routes/inspections';
import { index as inspectionQueue } from '@/routes/inspections/queue';
import { show as verification } from '@/routes/verifications';
import { index as reviews } from '@/routes/verifications/reviews';
import type { PropertySummary, VerificationStatus } from '@/types';
import type { InertiaLinkProps } from '@inertiajs/react';

type Props = {
    firstName: string;
    verifications: {
        id: number;
        kind_label: string;
        status: VerificationStatus;
        status_label: string;
        verified_until: string | null;
        needs_action: boolean;
    }[];
    properties: PropertySummary[] | null;
    pendingReviews: number | null;
    inspectionsToAssign: number | null;
    upcomingInspections: number | null;
};

type Task = {
    key: string;
    icon: LucideIcon;
    title: string;
    detail: string;
    href: NonNullable<InertiaLinkProps['href']>;
    action: string;
};

export default function Dashboard({
    firstName,
    verifications,
    properties,
    pendingReviews,
    inspectionsToAssign,
    upcomingInspections,
}: Props) {
    const { t } = useTranslation();

    const tasks: Task[] = [
        ...verifications
            .filter((profile) => profile.needs_action)
            .map((profile) => ({
                key: `verification-${profile.id}`,
                icon: BadgeCheck,
                title: t('Verify your :kind profile', {
                    kind: profile.kind_label,
                }),
                detail:
                    profile.status === 'rejected'
                        ? t(
                              'Your last request was not approved. Upload your documents again.',
                          )
                        : t('Upload your documents so we can review them.'),
                href: verification(),
                action: t('Verify'),
            })),
        ...(properties ?? [])
            .filter(
                (property) =>
                    property.status === 'draft' ||
                    property.status === 'rejected',
            )
            .map((property) => ({
                key: `property-${property.id}`,
                icon: Home,
                title: property.title,
                detail:
                    property.status === 'rejected'
                        ? t(
                              'Fix what the inspection found and request it again.',
                          )
                        : t('Complete it and request the inspection.'),
                href: editProperty(property.id),
                action: t('Continue'),
            })),
        ...(upcomingInspections
            ? [
                  {
                      key: 'agenda',
                      icon: CalendarDays,
                      title: t(':count inspections scheduled', {
                          count: upcomingInspections,
                      }),
                      detail: t('The next one first.'),
                      href: inspectionAgenda(),
                      action: t('Open agenda'),
                  },
              ]
            : []),
        ...(inspectionsToAssign
            ? [
                  {
                      key: 'assign',
                      icon: ClipboardList,
                      title: t(':count inspections to assign', {
                          count: inspectionsToAssign,
                      }),
                      detail: t('Choose an inspector and a date.'),
                      href: inspectionQueue(),
                      action: t('Assign'),
                  },
              ]
            : []),
        ...(pendingReviews
            ? [
                  {
                      key: 'reviews',
                      icon: ClipboardCheck,
                      title: t(':count verifications to review', {
                          count: pendingReviews,
                      }),
                      detail: t('Oldest first.'),
                      href: reviews(),
                      action: t('Review'),
                  },
              ]
            : []),
    ];

    return (
        <>
            <Head title={t('Dashboard')} />
            <Page
                width="wide"
                title={t('Hi, :name', { name: firstName })}
                description={
                    tasks.length
                        ? t('You have :count pending tasks.', {
                              count: tasks.length,
                          })
                        : t('You are up to date.')
                }
            >
                <div className="space-y-12">
                    {tasks.length > 0 && (
                        <PageSection title={t('What is next')}>
                            <ul className="divide-y rounded-xl border">
                                {tasks.map((task) => (
                                    <li
                                        key={task.key}
                                        className="flex items-center gap-4 px-4 py-4"
                                    >
                                        <task.icon
                                            className="size-5 shrink-0 text-muted-foreground"
                                            aria-hidden
                                        />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-medium">
                                                {task.title}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {task.detail}
                                            </p>
                                        </div>
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                        >
                                            <Link href={task.href}>
                                                {task.action}
                                            </Link>
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        </PageSection>
                    )}

                    {properties !== null && (
                        <PageSection
                            title={t('Your properties')}
                            actions={
                                properties.length > 0 ? (
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href={myProperties()}>
                                            {t('View all')} <ArrowRight />
                                        </Link>
                                    </Button>
                                ) : undefined
                            }
                        >
                            {properties.length === 0 ? (
                                <div className="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-muted px-6 py-6">
                                    <div>
                                        <p className="font-medium">
                                            {t('List your first property')}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'Every property is inspected before it is published, so students know it is safe.',
                                            )}
                                        </p>
                                    </div>
                                    <Button asChild>
                                        <Link href={createProperty()}>
                                            <Plus /> {t('New property')}
                                        </Link>
                                    </Button>
                                </div>
                            ) : (
                                <ul className="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-2 lg:grid-cols-4">
                                    {properties.map((property) => (
                                        <li key={property.id}>
                                            <ListingCard
                                                href={editProperty(property.id)}
                                                photo={property.cover}
                                                title={property.title}
                                                badge={
                                                    <PropertyStatusBadge
                                                        status={property.status}
                                                        label={
                                                            property.status_label
                                                        }
                                                    />
                                                }
                                                lines={[
                                                    property.location ||
                                                        t('No address yet'),
                                                ]}
                                            />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </PageSection>
                    )}

                    {verifications.length > 0 && (
                        <PageSection title={t('Your verification')}>
                            <ul className="divide-y rounded-xl border">
                                {verifications.map((profile) => (
                                    <li
                                        key={profile.id}
                                        className="flex flex-wrap items-center gap-3 px-4 py-4"
                                    >
                                        <p className="min-w-0 flex-1 font-medium">
                                            {t(':kind profile', {
                                                kind: profile.kind_label,
                                            })}
                                        </p>
                                        {profile.verified_until && (
                                            <span className="text-sm text-muted-foreground">
                                                {t('until :date', {
                                                    date: profile.verified_until,
                                                })}
                                            </span>
                                        )}
                                        <VerificationStatusBadge
                                            status={profile.status}
                                            label={profile.status_label}
                                        />
                                    </li>
                                ))}
                            </ul>
                        </PageSection>
                    )}
                </div>
            </Page>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
