import { Head, Link } from '@inertiajs/react';
import { Building2, Plus } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import Page from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { create, edit, index } from '@/routes/catalog/institutions';

type Props = {
    institutions: {
        id: number;
        name: string;
        acronym: string | null;
        email_domains: string[];
        is_active: boolean;
        campuses_count: number;
    }[];
};

export default function InstitutionsIndex({ institutions }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Institutions')} />

            <Page
                title={t('Institutions')}
                description={t(
                    'Email domains identify the members of each institution; campuses are used to search by distance.',
                )}
                actions={
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            {t('New institution')}
                        </Link>
                    </Button>
                }
            >
                <div className="space-y-6">
                    {institutions.length === 0 ? (
                        <EmptyState
                            icon={Building2}
                            title={t('There are no institutions yet.')}
                            description={t(
                                'Add the first one with its email domains so its members are recognized when they sign up.',
                            )}
                        />
                    ) : (
                        <ul className="divide-y rounded-lg border">
                            {institutions.map((institution) => (
                                <li key={institution.id}>
                                    <Link
                                        href={edit(institution.id)}
                                        className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 hover:bg-accent"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {institution.name}
                                                {institution.acronym &&
                                                    ` (${institution.acronym})`}
                                            </p>
                                            <p className="truncate text-sm text-muted-foreground">
                                                {institution.email_domains.join(
                                                    ', ',
                                                )}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2 text-sm">
                                            {!institution.is_active && (
                                                <Badge variant="secondary">
                                                    {t('Inactive')}
                                                </Badge>
                                            )}
                                            <span className="text-muted-foreground">
                                                {t(':count campuses', {
                                                    count: institution.campuses_count,
                                                })}
                                            </span>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </Page>
        </>
    );
}

InstitutionsIndex.layout = {
    breadcrumbs: [{ title: 'Institutions', href: index() }],
};
