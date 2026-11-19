import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * Sign-in and sign-up: one white card on the brand frame, the product named at the top.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="flex min-h-svh flex-col items-center bg-background px-4 py-8 md:justify-center md:py-12">
            <Link
                href={home()}
                className="mb-6 flex items-center gap-2 rounded-lg font-semibold focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <span className="flex size-9 items-center justify-center rounded-lg bg-brand text-brand-foreground">
                    <AppLogoIcon className="size-5" />
                </span>
                {name}
            </Link>
            <div className="w-full max-w-md rounded-2xl border bg-card px-6 py-8 md:px-10">
                <div className="mb-8 space-y-1">
                    <h1 className="text-[22px] leading-tight font-semibold tracking-tight">
                        {title}
                    </h1>
                    {description && (
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                {children}
            </div>
        </div>
    );
}
