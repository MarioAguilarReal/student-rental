import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/**
 * The 48px top bar lives on the frame (no rule under it); the sheet starts below.
 */
export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="flex h-12 shrink-0 items-center gap-2 px-4 md:pl-2">
            <SidebarTrigger className="-ml-1 text-muted-foreground" />
            <Breadcrumbs breadcrumbs={breadcrumbs} />
        </header>
    );
}
