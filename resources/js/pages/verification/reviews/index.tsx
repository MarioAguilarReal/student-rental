import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/lib/i18n';
import { index, show } from '@/routes/verifications/reviews';
import type { Option, Paginated } from '@/types';

type Row = {
    id: number;
    user: { name: string; email: string };
    kind: string;
    community_type: string | null;
    institution: string | null;
    submitted_at: string | null;
    waiting: string | null;
    documents_count: number;
};

type Props = {
    profiles: Paginated<Row>;
    filters: { kind: string | null };
    kindOptions: Option[];
};

const ALL = 'all';

export default function ReviewsIndex({
    profiles,
    filters,
    kindOptions,
}: Props) {
    const { t } = useTranslation();

    const filterByKind = (kind: string) =>
        router.get(
            index.url({ query: kind === ALL ? {} : { kind } }),
            {},
            { preserveState: true, replace: true },
        );

    return (
        <>
            <Head title={t('Verifications')} />

            <div className="space-y-4 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title={t('Verifications to review')}
                        description={t(
                            'Oldest first. Check that the documents match the person and their institution.',
                        )}
                    />
                    <Select
                        value={filters.kind ?? ALL}
                        onValueChange={filterByKind}
                    >
                        <SelectTrigger
                            className="w-48"
                            aria-label={t('Profile type')}
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>
                                {t('All profiles')}
                            </SelectItem>
                            {kindOptions.map((option) => (
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

                {profiles.data.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                        {t('No verifications waiting for review.')}
                    </p>
                ) : (
                    <ul className="divide-y rounded-lg border">
                        {profiles.data.map((row) => (
                            <li key={row.id}>
                                <Link
                                    href={show(row.id)}
                                    className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 hover:bg-accent"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
                                            {row.user.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {row.user.email}
                                            {row.institution &&
                                                ` · ${row.institution}`}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2 text-sm">
                                        <Badge variant="outline">
                                            {row.kind}
                                        </Badge>
                                        {row.community_type && (
                                            <Badge variant="secondary">
                                                {row.community_type}
                                            </Badge>
                                        )}
                                        <span className="text-muted-foreground">
                                            {t(':count documents', {
                                                count: row.documents_count,
                                            })}{' '}
                                            · {row.waiting}
                                        </span>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination page={profiles} />
            </div>
        </>
    );
}

ReviewsIndex.layout = {
    breadcrumbs: [{ title: 'Verifications', href: index() }],
};
