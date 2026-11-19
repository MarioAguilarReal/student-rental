import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { useTranslation } from '@/lib/i18n';

export default function AppLogo() {
    const { name } = usePage().props;
    const { t } = useTranslation();

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-brand text-brand-foreground">
                <AppLogoIcon className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left leading-tight">
                <span className="truncate text-sm font-semibold">{name}</span>
                <span className="truncate text-xs text-muted-foreground">
                    {t('Housing for your campus')}
                </span>
            </div>
        </>
    );
}
