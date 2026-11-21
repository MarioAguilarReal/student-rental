import { usePage } from '@inertiajs/react';

/**
 * HU-03.05: the rental deadlines in force (RN-61, RN-62), to quote them in the interface.
 */
export function useDeadlines(): { requestHours: number; paymentHours: number } {
    const { deadlines } = usePage().props;

    return {
        requestHours: deadlines.request_hours,
        paymentHours: deadlines.payment_hours,
    };
}
