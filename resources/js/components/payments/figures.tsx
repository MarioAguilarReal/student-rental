import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';

export type Figure = {
    label: string;
    minor: number;
    hint?: string;
    primary?: boolean;
};

/**
 * A row of money totals (HU-09.03, HU-09.04). The main figure uses the brand colour.
 */
export default function Figures({ figures }: { figures: Figure[] }) {
    return (
        <dl className="grid grid-cols-2 gap-px overflow-hidden rounded-xl border bg-border md:grid-cols-[repeat(auto-fit,minmax(10rem,1fr))]">
            {figures.map((figure) => (
                <div key={figure.label} className="space-y-1 bg-background p-4">
                    <dt className="text-xs text-muted-foreground">
                        {figure.label}
                    </dt>
                    <dd
                        className={cn(
                            'tabular text-lg font-semibold',
                            figure.primary && 'text-brand',
                        )}
                    >
                        {formatMoney(figure.minor)}
                    </dd>
                    {figure.hint && (
                        <p className="text-xs text-muted-foreground">
                            {figure.hint}
                        </p>
                    )}
                </div>
            ))}
        </dl>
    );
}
