import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Home } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * Photo-first card: the picture carries the card, the words sit under it with no box around.
 * Used for properties now and for the public catalog later.
 */
export default function ListingCard({
    href,
    photo,
    title,
    lines,
    badge,
}: {
    href: NonNullable<InertiaLinkProps['href']>;
    photo: string | null;
    title: string;
    lines: ReactNode[];
    badge?: ReactNode;
}) {
    return (
        <Link
            href={href}
            className="group block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:outline-none"
        >
            <div className="relative aspect-[4/3] overflow-hidden rounded-xl bg-muted">
                {photo ? (
                    <img
                        src={photo}
                        alt=""
                        loading="lazy"
                        className="size-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                    />
                ) : (
                    <div className="flex size-full items-center justify-center">
                        <Home
                            className="size-8 text-muted-foreground"
                            aria-hidden
                        />
                    </div>
                )}
                {badge && <div className="absolute top-3 left-3">{badge}</div>}
            </div>
            <div className="mt-3 space-y-0.5">
                <p className="line-clamp-2 leading-snug font-semibold">
                    {title}
                </p>
                {lines.map((line, index) => (
                    <p
                        key={index}
                        className="truncate text-sm text-muted-foreground"
                    >
                        {line}
                    </p>
                ))}
            </div>
        </Link>
    );
}
