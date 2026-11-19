import { Head, Link, router } from '@inertiajs/react';
import { ChevronRight, ClipboardCheck } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import FilterChips from '@/components/filter-chips';
import Page from '@/components/page';
import Pagination from '@/components/pagination';
import PersonAvatar from '@/components/person-avatar';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/lib/i18n';
import { index, show } from '@/routes/verifications/reviews';
import type { Option, Paginated } from '@/types';

type Row = {
    id: number;
    user: { id: number; name: string; email: string };
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
    kindCounts: Record<string, number>;
    kindOptions: Option[];
};

export default function ReviewsIndex({
    profiles,
    filters,
    kindCounts,
    kindOptions,
}: Props) {
    const { t } = useTranslation();

    const chips = [
        { value: 'all', label: t('All'), count: kindCounts.all ?? 0 },
        ...kindOptions.map((option) => ({
            value: option.value,
            label: t(':kind profile', { kind: option.label }),
            count: kindCounts[option.value] ?? 0,
        })),
    ];

    return (
        <>
            <Head title={t('Verifications')} />
            <Page
                title={t('Verifications to review')}
                description={t(
                    'Oldest first. Check that the documents match the person and their institution.',
                )}
            >
                <div className="mb-6">
                    <FilterChips
                        label={t('Profile type')}
                        chips={chips}
                        value={filters.kind ?? 'all'}
                        onChange={(kind) =>
                            router.get(
                                index.url(),
                                kind === 'all' ? {} : { kind },
                                { preserveState: true, replace: true },
                            )
                        }
                    />
                </div>

                {profiles.data.length === 0 ? (
                    <EmptyState
                        icon={ClipboardCheck}
                        title={t('No verifications waiting for review.')}
                        description={t(
                            'New requests appear here as soon as someone sends their documents.',
                        )}
                    />
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {profiles.data.map((row) => (
                            <li key={row.id}>
                                <Link
                                    href={show(row.id)}
                                    className="flex items-center gap-4 px-4 py-4 transition-colors hover:bg-muted focus-visible:bg-muted focus-visible:outline-none"
                                >
                                    <PersonAvatar
                                        id={row.user.id}
                                        name={row.user.name}
                                    />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">
                                            {row.user.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {[
                                                row.community_type ?? row.kind,
                                                row.institution,
                                                row.user.email,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                    </div>
                                    <div className="hidden shrink-0 flex-col items-end gap-1 text-right sm:flex">
                                        <Badge variant="neutral">
                                            {t(':count documents', {
                                                count: row.documents_count,
                                            })}
                                        </Badge>
                                        <span className="text-xs text-muted-foreground">
                                            {t('Sent :when', {
                                                when: row.waiting ?? '',
                                            })}
                                        </span>
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

                <Pagination page={profiles} />
            </Page>
        </>
    );
}

ReviewsIndex.layout = {
    breadcrumbs: [{ title: 'Verifications', href: index() }],
};
