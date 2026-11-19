import { Form, Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

const ALL = 'all';

export default function UsersIndex({ users, filters, roleOptions }: Props) {
    const { t } = useTranslation();

    const filterByRole = (role: string) =>
        router.get(
            index.url({
                query: {
                    ...(filters.search ? { search: filters.search } : {}),
                    ...(role === ALL ? {} : { role }),
                },
            }),
            {},
            { preserveState: true, replace: true },
        );

    return (
        <>
            <Head title={t('Users')} />

            <div className="space-y-4 p-4">
                <Heading
                    title={t('Users')}
                    description={t('Manage internal roles and suspensions.')}
                />

                <div className="flex flex-wrap gap-2">
                    <Form
                        {...index.form()}
                        transform={(data) => ({
                            ...data,
                            ...(filters.role ? { role: filters.role } : {}),
                        })}
                        options={{ preserveState: true, replace: true }}
                        className="relative min-w-60 flex-1"
                    >
                        <Search className="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground" />
                        <Input
                            name="search"
                            defaultValue={filters.search}
                            placeholder={t('Search by name or email')}
                            className="pl-9"
                            aria-label={t('Search by name or email')}
                        />
                    </Form>
                    <Select
                        value={filters.role ?? ALL}
                        onValueChange={filterByRole}
                    >
                        <SelectTrigger className="w-48" aria-label={t('Role')}>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>
                                {t('All roles')}
                            </SelectItem>
                            {roleOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {users.data.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                        {t('No users match the search.')}
                    </p>
                ) : (
                    <ul className="divide-y rounded-lg border">
                        {users.data.map((user) => (
                            <li key={user.id}>
                                <Link
                                    href={show(user.id)}
                                    className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 hover:bg-accent"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
                                            {user.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {user.email}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-1">
                                        {user.suspended && (
                                            <Badge variant="destructive">
                                                {t('Suspended')}
                                            </Badge>
                                        )}
                                        {user.roles.map((role) => (
                                            <Badge key={role} variant="outline">
                                                {role}
                                            </Badge>
                                        ))}
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination page={users} />
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [{ title: 'Users', href: index() }],
};
