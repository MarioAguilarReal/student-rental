import { Check } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export type Step = { key: string; label: string; met: boolean };

/**
 * "3 of 5 done" with a bar and the list. The bar uses the brand hue: it is the data.
 */
export default function ProgressSteps({
    steps,
    title,
}: {
    steps: Step[];
    title: string;
}) {
    const { t } = useTranslation();
    const done = steps.filter((step) => step.met).length;

    return (
        <div className="space-y-3">
            <div className="flex items-baseline justify-between gap-2">
                <h3 className="text-sm font-semibold">{title}</h3>
                <span className="tabular text-sm text-muted-foreground">
                    {t(':done of :total', { done, total: steps.length })}
                </span>
            </div>
            <div
                className="h-1.5 overflow-hidden rounded-full bg-muted"
                role="progressbar"
                aria-valuemin={0}
                aria-valuemax={steps.length}
                aria-valuenow={done}
            >
                <div
                    className="h-full rounded-full bg-brand transition-[width] duration-500"
                    style={{
                        width: `${(done / Math.max(steps.length, 1)) * 100}%`,
                    }}
                />
            </div>
            <ul className="space-y-2">
                {steps.map((step) => (
                    <li
                        key={step.key}
                        className="flex items-start gap-2.5 text-sm"
                    >
                        <span
                            className={cn(
                                'mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border',
                                step.met
                                    ? 'border-foreground bg-foreground text-background'
                                    : 'border-input',
                            )}
                            aria-hidden
                        >
                            {step.met && (
                                <Check className="size-3" strokeWidth={3} />
                            )}
                        </span>
                        <span
                            className={step.met ? '' : 'text-muted-foreground'}
                        >
                            {step.label}
                            <span className="sr-only">
                                {step.met ? t('(done)') : t('(pending)')}
                            </span>
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
