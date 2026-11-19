import { Badge } from '@/components/ui/badge';
import type { InspectionResult, InspectionStatus } from '@/types';

const statusTones: Record<
    InspectionStatus,
    'neutral' | 'brand' | 'warning' | 'positive' | 'negative'
> = {
    pending_assignment: 'warning',
    assigned: 'brand',
    in_progress: 'brand',
    completed: 'neutral',
    cancelled: 'neutral',
};

const resultTones: Record<
    InspectionResult,
    'positive' | 'warning' | 'negative'
> = {
    approved: 'positive',
    approved_with_observations: 'warning',
    rejected: 'negative',
};

/**
 * A completed inspection shows its verdict; an open one shows where it is.
 */
export default function InspectionStatusBadge({
    status,
    statusLabel,
    result,
    resultLabel,
}: {
    status: InspectionStatus;
    statusLabel: string;
    result: InspectionResult | null;
    resultLabel: string | null;
}) {
    if (status === 'completed' && result) {
        return <Badge variant={resultTones[result]}>{resultLabel}</Badge>;
    }

    return <Badge variant={statusTones[status]}>{statusLabel}</Badge>;
}
