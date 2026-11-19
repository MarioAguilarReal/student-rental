import { usePage } from '@inertiajs/react';

/**
 * UI-only check to hide what the user cannot do. The backend authorizes every request.
 * Permission names come from App\Modules\Identity\Enums\Permission.
 */
export function useCan() {
    const { auth } = usePage().props;
    const permissions = auth.permissions ?? [];

    return (permission: string) => permissions.includes(permission);
}
