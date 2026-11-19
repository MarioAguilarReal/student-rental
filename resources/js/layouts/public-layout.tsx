import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { dashboard, home, login, register } from '@/routes';

/**
 * Public pages (catalog, listing): white page, brand on the left, account on the right.
 */
export default function PublicLayout({ children }: { children: ReactNode }) {
    const { auth, name } = usePage().props;
    const { t } = useTranslation();

    return (
        <div className="flex min-h-svh flex-col bg-card text-foreground">
            <header className="sticky top-0 z-30 border-b bg-card">
                <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 md:px-8">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 rounded-lg font-semibold focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <span className="flex size-8 items-center justify-center rounded-lg bg-brand text-brand-foreground">
                            <AppLogoIcon className="size-5" />
                        </span>
                        <span className="hidden sm:inline">{name}</span>
                    </Link>
                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild variant="outline">
                                <Link href={dashboard()}>
                                    {t('My account')}
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild variant="ghost">
                                    <Link href={login()}>{t('Log in')}</Link>
                                </Button>
                                <Button asChild>
                                    <Link href={register()}>
                                        {t('Sign up')}
                                    </Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </div>
            </header>
            <main className="flex-1">{children}</main>
            <footer className="border-t">
                <div className="mx-auto flex w-full max-w-7xl flex-wrap justify-between gap-2 px-4 py-6 text-sm text-muted-foreground md:px-8">
                    <span>{name}</span>
                    <span>
                        {t(
                            'Every property is inspected before it is published.',
                        )}
                    </span>
                </div>
            </footer>
        </div>
    );
}
