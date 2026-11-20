import { Badge } from '@/components/ui/badge';
import type { RentalStatus } from '@/types';

const tones: Record<
    RentalStatus,
    'neutral' | 'brand' | 'warning' | 'positive' | 'negative'
> = {
    requested: 'warning',
    approved: 'brand',
    confirmed: 'positive',
    active: 'positive',
    completed: 'neutral',
    rejected: 'negative',
    expired: 'neutral',
    cancelled: 'negative',
};

export default function RentalStatusBadge({
    status,
    label,
}: {
    status: RentalStatus;
    label: string;
}) {
    return <Badge variant={tones[status]}>{label}</Badge>;
}
