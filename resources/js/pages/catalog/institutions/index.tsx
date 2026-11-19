import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
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

            <div className="space-y-4 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title={t('Institutions')}
                        description={t(
                            'Email domains identify the members of each institution; campuses are used to search by distance.',
                        )}
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            {t('New institution')}
                        </Link>
                    </Button>
                </div>

                {institutions.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                        {t('There are no institutions yet.')}
                    </p>
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
        </>
    );
}

InstitutionsIndex.layout = {
    breadcrumbs: [{ title: 'Institutions', href: index() }],
};
