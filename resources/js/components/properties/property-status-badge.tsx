import { Badge } from '@/components/ui/badge';
import type { PropertyStatus } from '@/types';

const tones: Record<
    PropertyStatus,
    'neutral' | 'positive' | 'negative' | 'warning' | 'brand'
> = {
    draft: 'neutral',
    pending_inspection: 'brand',
    approved: 'positive',
    rejected: 'negative',
    published: 'positive',
    paused: 'warning',
    archived: 'neutral',
};

export default function PropertyStatusBadge({
    status,
    label,
}: {
    status: PropertyStatus;
    label: string;
}) {
    return <Badge variant={tones[status]}>{label}</Badge>;
}
