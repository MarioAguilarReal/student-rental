import * as React from 'react';
import { SidebarInset } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { AppVariant } from '@/types';

type Props = React.ComponentProps<'main'> & {
    variant?: AppVariant;
};

/**
 * Framed sheet (docs/spec/09): the main column is transparent so the sidebar and the
 * top bar form one tinted L; pages render inside AppSheet, a white sheet inset 8px.
 */
export function AppContent({
    variant = 'sidebar',
    children,
    className,
    ...props
}: Props) {
    if (variant === 'sidebar') {
        return (
            <SidebarInset
                className={cn(
                    'bg-transparent md:peer-data-[variant=inset]:m-0 md:peer-data-[variant=inset]:rounded-none md:peer-data-[variant=inset]:shadow-none',
                    className,
                )}
                {...props}
            >
                {children}
            </SidebarInset>
        );
    }

    return (
        <main
            className={cn(
                'mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl',
                className,
            )}
            {...props}
        >
            {children}
        </main>
    );
}

export function AppSheet({ children }: { children: React.ReactNode }) {
    return (
        <div className="flex min-h-0 flex-1 flex-col bg-card md:mr-2 md:mb-2 md:rounded-2xl md:border md:border-border">
            {children}
        </div>
    );
}
