import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import RentalStatusBadge from '@/components/rentals/rental-status-badge';
import { formatMoney } from '@/lib/money';
import { show } from '@/routes/rentals';
import type { RentalSummary } from '@/types';

/**
 * Rentals as rows: where, which dates, how much and where they stand.
 * `subtitle` picks what the second line shows (the client for operators, the place for clients).
 */
export default function RentalList({
    rentals,
    subtitle,
}: {
    rentals: RentalSummary[];
    subtitle: (rental: RentalSummary) => string;
}) {
    return (
        <ul className="divide-y rounded-xl border">
            {rentals.map((rental) => (
                <li key={rental.id}>
                    <Link
                        href={show(rental.id)}
                        className="flex items-center gap-4 px-4 py-4 transition-colors hover:bg-muted focus-visible:bg-muted focus-visible:outline-none"
                    >
                        <div className="min-w-0 flex-1">
                            <p className="truncate font-medium">
                                {rental.property.title} · {rental.unit}
                            </p>
                            <p className="truncate text-sm text-muted-foreground">
                                {subtitle(rental)}
                            </p>
                        </div>
                        <div className="hidden text-right text-sm sm:block">
                            <p className="tabular">
                                {rental.starts_on} → {rental.ends_on}
                            </p>
                            <p className="tabular text-muted-foreground">
                                {formatMoney(
                                    rental.total_minor,
                                    rental.currency,
                                )}
                            </p>
                        </div>
                        <RentalStatusBadge
                            status={rental.status}
                            label={rental.status_label}
                        />
                        <ChevronRight
                            className="size-4 shrink-0 text-muted-foreground"
                            aria-hidden
                        />
                    </Link>
                </li>
            ))}
        </ul>
    );
}
