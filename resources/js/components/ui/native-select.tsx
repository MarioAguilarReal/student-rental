import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * Styled native <select>: accessible, works in forms and with controlled state.
 */
function NativeSelect({ className, ...props }: React.ComponentProps<'select'>) {
    return (
        <select
            data-slot="native-select"
            className={cn(
                'flex h-10 w-full rounded-lg border border-input bg-card px-3 py-1 text-base outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive md:text-sm dark:bg-input/30',
                className,
            )}
            {...props}
        />
    );
}

export { NativeSelect };
