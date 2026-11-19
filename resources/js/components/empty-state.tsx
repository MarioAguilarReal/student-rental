import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * Empty lists keep their frame and say what to do next. One plain grey icon, no tinted circle.
 */
export default function EmptyState({
    icon: Icon,
    title,
    description,
    action,
}: {
    icon?: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-6 py-12 text-center">
            {Icon && (
                <Icon
                    className="mb-1 size-6 text-muted-foreground"
                    aria-hidden
                />
            )}
            <p className="font-medium">{title}</p>
            {description && (
                <p className="max-w-sm text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-3">{action}</div>}
        </div>
    );
}
