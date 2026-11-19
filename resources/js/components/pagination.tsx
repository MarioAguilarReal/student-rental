import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import type { Paginated } from '@/types';

export default function Pagination<T>({ page }: { page: Paginated<T> }) {
    const { t } = useTranslation();

    if (page.last_page <= 1) {
        return null;
    }

    return (
        <nav
            className="flex items-center justify-between gap-4 pt-4 text-sm"
            aria-label={t('Pagination')}
        >
            <span className="text-muted-foreground">
                {t(':from–:to of :total', {
                    from: page.from ?? 0,
                    to: page.to ?? 0,
                    total: page.total,
                })}
            </span>
            <div className="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    asChild
                    disabled={!page.prev_page_url}
                >
                    {page.prev_page_url ? (
                        <Link href={page.prev_page_url} preserveScroll>
                            {t('Previous')}
                        </Link>
                    ) : (
                        <span>{t('Previous')}</span>
                    )}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    asChild
                    disabled={!page.next_page_url}
                >
                    {page.next_page_url ? (
                        <Link href={page.next_page_url} preserveScroll>
                            {t('Next')}
                        </Link>
                    ) : (
                        <span>{t('Next')}</span>
                    )}
                </Button>
            </div>
        </nav>
    );
}
