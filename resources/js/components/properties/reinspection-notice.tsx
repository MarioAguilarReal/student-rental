import { TriangleAlert } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';

/**
 * RN-11: shown before critical edits on a property that already passed its inspection.
 */
export default function ReinspectionNotice({ show }: { show: boolean }) {
    const { t } = useTranslation();

    if (!show) {
        return null;
    }

    return (
        <p className="flex items-start gap-2 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
            <TriangleAlert className="mt-0.5 size-4 shrink-0" aria-hidden />
            {t(
                'Changing this sends the property to a new inspection and hides it from the catalog until it is approved. Confirmed rentals are not affected.',
            )}
        </p>
    );
}
