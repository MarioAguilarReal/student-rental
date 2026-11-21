import { Form, Head, Link, router } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import FilterChips from '@/components/filter-chips';
import Page from '@/components/page';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { index, open, readAll } from '@/routes/notifications';
import type { Paginated } from '@/types';

type NotificationItem = {
    id: string;
    type: string;
    title: string;
    body: string;
    read: boolean;
    created_at: string | null;
};

function formatDateTime(iso: string | null): string {
    return iso
        ? new Intl.DateTimeFormat('es-MX', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(iso))
        : '';
}

/**
 * HU-10.02: the in-app copy of every email the platform sent this person.
 */
export default function Notifications({
    notifications,
    unreadOnly,
    unreadCount,
    totalCount,
}: {
    notifications: Paginated<NotificationItem>;
    unreadOnly: boolean;
    unreadCount: number;
    totalCount: number;
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Notifications')} />
            <Page
                title={t('Notifications')}
                description={t(
                    'Everything we sent you by email, in one place.',
                )}
                width="narrow"
                actions={
                    unreadCount > 0 && (
                        <Form
                            {...readAll.form()}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button variant="outline" disabled={processing}>
                                    {t('Mark all as read')}
                                </Button>
                            )}
                        </Form>
                    )
                }
            >
                <div className="space-y-4">
                    <FilterChips
                        label={t('Show')}
                        chips={[
                            {
                                value: 'all',
                                label: t('All'),
                                count: totalCount,
                            },
                            {
                                value: 'unread',
                                label: t('Unread'),
                                count: unreadCount,
                            },
                        ]}
                        value={unreadOnly ? 'unread' : 'all'}
                        onChange={(value) =>
                            router.get(
                                index.url(),
                                value === 'unread' ? { unread: 1 } : {},
                                { preserveState: true, replace: true },
                            )
                        }
                    />

                    {notifications.data.length === 0 ? (
                        <EmptyState
                            icon={Bell}
                            title={
                                unreadOnly
                                    ? t('You are up to date')
                                    : t('No notifications yet')
                            }
                            description={t(
                                'Requests, approvals, payments and inspections will show up here.',
                            )}
                        />
                    ) : (
                        <ul className="divide-y rounded-xl border">
                            {notifications.data.map((notification) => (
                                <li key={notification.id}>
                                    <Link
                                        href={open.url(notification.id)}
                                        method="post"
                                        as="button"
                                        className="flex w-full items-start gap-3 px-4 py-3 text-left text-sm hover:bg-muted/50 focus-visible:bg-muted/50 focus-visible:outline-none"
                                    >
                                        <span
                                            aria-hidden
                                            className={cn(
                                                'mt-1.5 size-2 shrink-0 rounded-full',
                                                notification.read
                                                    ? 'bg-transparent'
                                                    : 'bg-brand',
                                            )}
                                        />
                                        <span className="min-w-0 flex-1 space-y-0.5">
                                            <span
                                                className={cn(
                                                    'block',
                                                    !notification.read &&
                                                        'font-medium',
                                                )}
                                            >
                                                {notification.title}
                                                {!notification.read && (
                                                    <span className="sr-only">
                                                        {' '}
                                                        ({t('Unread')})
                                                    </span>
                                                )}
                                            </span>
                                            <span className="block text-muted-foreground">
                                                {notification.body}
                                            </span>
                                        </span>
                                        <span className="tabular shrink-0 text-xs text-muted-foreground">
                                            {formatDateTime(
                                                notification.created_at,
                                            )}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                    <Pagination page={notifications} />
                </div>
            </Page>
        </>
    );
}

Notifications.layout = {
    breadcrumbs: [{ title: 'Notifications', href: index() }],
};
