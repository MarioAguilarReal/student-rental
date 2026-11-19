import type { ReactNode } from 'react';

/**
 * The save bar of an editor section: stays in reach at the bottom of the sheet while the
 * form scrolls (one Save per section, docs/spec/09 §Formularios).
 */
export default function FormActions({
    children,
    hint,
}: {
    children: ReactNode;
    hint?: ReactNode;
}) {
    return (
        <div className="sticky bottom-0 z-10 mt-8 flex items-center justify-end gap-3 border-t bg-card py-3">
            {hint && (
                <p className="mr-auto text-sm text-muted-foreground">{hint}</p>
            )}
            {children}
        </div>
    );
}
