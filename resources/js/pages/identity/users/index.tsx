import { Head, Link, router } from '@inertiajs/react';
import { ChevronRight, Search, Users } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import Page from '@/components/page';
import Pagination from '@/components/pagination';
import PersonAvatar from '@/components/person-avatar';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { useTranslation } from '@/lib/i18n';
import { index, show } from '@/routes/users';
import type { Option, Paginated } from '@/types';

type Row = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    suspended: boolean;
};

type Props = {
    users: Paginated<Row>;
    filters: { search: string; role: string | null };
    roleOptions: Option[];
};

export default function UsersIndex({ users, filters, roleOptions }: Props) {
    const { t } = useTranslation();
    const filter = (changes: Record<string, string>) =>
        router.get(
            index.url(),
            Object.fromEntries(
                Object.entries({
                    search: filters.search,
                    role: filters.role ?? '',
                    ...changes,
                }).filter(([, value]) => value),
            ),
            { preserveState: true, replace: true },
        );

    return (
        <>
            <Head title={t('Users')} />
            <Page
                title={t('Users')}
                description={t('Manage internal roles and suspensions.')}
            >
                <div className="mb-6 flex flex-wrap gap-2">
                    <div className="relative min-w-60 flex-1">
                        <Search
                            className="pointer-events-none absolute top-3 left-3 size-4 text-muted-foreground"
                            aria-hidden
                        />
                        <Input
                            className="pl-9"
                            defaultValue={filters.search}
                            placeholder={t('Search by name or email')}
                            aria-label={t('Search by name or email')}
                            onKeyDown={(event) =>
                                event.key === 'Enter' &&
                                filter({ search: event.currentTarget.value })
                            }
                        />
                    </div>
                    <NativeSelect
                        className="w-48"
                        aria-label={t('Role')}
                        value={filters.role ?? ''}
                        onChange={(event) =>
                            filter({ role: event.target.value })
                        }
                    >
                        <option value="">{t('All roles')}</option>
                        {roleOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </NativeSelect>
                </div>

                {users.data.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        title={t('No users match the search.')}
                    />
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {users.data.map((user) => (
                            <li key={user.id}>
                                <Link
                                    href={show(user.id)}
                                    className="flex items-center gap-4 px-4 py-3 transition-colors hover:bg-muted focus-visible:bg-muted focus-visible:outline-none"
                                >
                                    <PersonAvatar
                                        id={user.id}
                                        name={user.name}
                                    />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">
                                            {user.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {user.email}
                                        </p>
                                    </div>
                                    <div className="hidden flex-wrap justify-end gap-1 sm:flex">
                                        {user.suspended && (
                                            <Badge variant="negative">
                                                {t('Suspended')}
                                            </Badge>
                                        )}
                                        {user.roles.map((role) => (
                                            <Badge key={role} variant="neutral">
                                                {role}
                                            </Badge>
                                        ))}
                                    </div>
                                    <ChevronRight
                                        className="size-4 shrink-0 text-muted-foreground"
                                        aria-hidden
                                    />
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination page={users} />
            </Page>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [{ title: 'Users', href: index() }],
};
