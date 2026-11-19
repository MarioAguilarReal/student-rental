import { cn } from '@/lib/utils';

export type FilterChip = { value: string; label: string; count: number };

/**
 * Peer filters with their counts (zero prints as 0). The selected chip is ink on white;
 * on a phone the row scrolls sideways.
 */
export default function FilterChips({
    chips,
    value,
    onChange,
    label,
}: {
    chips: FilterChip[];
    value: string;
    onChange: (value: string) => void;
    label: string;
}) {
    return (
        <div
            role="radiogroup"
            aria-label={label}
            className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 md:mx-0 md:flex-wrap md:px-0"
        >
            {chips.map((chip) => {
                const selected = chip.value === value;

                return (
                    <button
                        key={chip.value}
                        type="button"
                        role="radio"
                        aria-checked={selected}
                        onClick={() => onChange(chip.value)}
                        className={cn(
                            'flex shrink-0 items-center gap-2 rounded-full border px-4 py-1.5 text-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none active:scale-[0.98]',
                            selected
                                ? 'border-foreground bg-foreground text-background'
                                : 'border-input bg-card hover:border-foreground',
                        )}
                    >
                        {chip.label}
                        <span
                            className={cn(
                                'tabular text-xs',
                                selected
                                    ? 'text-background/80'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {chip.count}
                        </span>
                    </button>
                );
            })}
        </div>
    );
}
