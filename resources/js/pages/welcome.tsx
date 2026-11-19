import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { dashboard, login, register } from '@/routes';

/**
 * Temporary home page. Iteration 5 (E07) replaces it with the public catalog.
 */
export default function Welcome() {
    const { auth, name } = usePage().props;
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Welcome')} />

            <div className="flex min-h-svh flex-col bg-background text-foreground">
                <header className="flex items-center justify-between px-4 py-4 sm:px-6">
                    <div className="flex items-center gap-2 font-medium">
                        <AppLogoIcon className="size-7 fill-current" />
                        <span>{name}</span>
                    </div>

                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild variant="outline">
                                <Link href={dashboard()}>{t('Dashboard')}</Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild variant="ghost">
                                    <Link href={login()}>{t('Log in')}</Link>
                                </Button>
                                <Button asChild>
                                    <Link href={register()}>
                                        {t('Register')}
                                    </Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </header>

                <main className="flex flex-1 items-center justify-center px-4 py-16">
                    <div className="max-w-xl space-y-4 text-center">
                        <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                            {t(
                                'Safe, inspected housing for your school community',
                            )}
                        </h1>
                        <p className="text-muted-foreground">
                            {t(
                                'Whole homes, private rooms and shared spaces for faculty, students and exchange students. Every property is inspected before it is published.',
                            )}
                        </p>
                    </div>
                </main>
            </div>
        </>
    );
}
