import { Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { InertiaLinkProps } from '@inertiajs/react';

type Width = 'narrow' | 'default' | 'wide';

const widths: Record<Width, string> = {
    narrow: 'max-w-3xl',
    default: 'max-w-5xl',
    wide: 'max-w-7xl',
};

/**
 * Every app screen: one title row (h1 22px, one sentence, the screen's main action on the
 * right), then the content. See docs/spec/09-sistema-diseno.md §Página.
 */
export default function Page({
    title,
    description,
    actions,
    back,
    meta,
    width = 'default',
    children,
}: {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
    back?: { href: NonNullable<InertiaLinkProps['href']>; label: string };
    meta?: ReactNode;
    width?: Width;
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'mx-auto w-full px-4 py-6 md:px-8 md:py-8',
                widths[width],
            )}
        >
            {back && (
                <Link
                    href={back.href}
                    className="mb-3 inline-flex items-center gap-1 rounded-md text-sm text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <ChevronLeft className="size-4" aria-hidden />
                    {back.label}
                </Link>
            )}
            <header className="mb-6 flex flex-wrap items-start justify-between gap-4 md:mb-8">
                <div className="min-w-0 space-y-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-[22px] leading-tight font-semibold tracking-tight">
                            {title}
                        </h1>
                        {meta}
                    </div>
                    {description && (
                        <p className="max-w-2xl text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                {actions && (
                    <div className="flex flex-wrap items-center gap-2">
                        {actions}
                    </div>
                )}
            </header>
            {children}
        </div>
    );
}

/**
 * A titled block inside a page or editor section (16px title, one line of help).
 */
export function PageSection({
    title,
    description,
    actions,
    children,
    className,
}: {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section className={cn('space-y-4', className)}>
            <div className="flex flex-wrap items-end justify-between gap-2">
                <div className="space-y-1">
                    <h2 className="text-base font-semibold">{title}</h2>
                    {description && (
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                {actions}
            </div>
            {children}
        </section>
    );
}
