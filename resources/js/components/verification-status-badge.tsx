import { Badge } from '@/components/ui/badge';
import type { VerificationStatus } from '@/types';

const tones: Record<
    VerificationStatus,
    'neutral' | 'positive' | 'negative' | 'warning' | 'brand'
> = {
    unverified: 'neutral',
    email_verified: 'warning',
    in_review: 'brand',
    verified: 'positive',
    rejected: 'negative',
    expired: 'negative',
};

export default function VerificationStatusBadge({
    status,
    label,
}: {
    status: VerificationStatus;
    label: string;
}) {
    return <Badge variant={tones[status]}>{label}</Badge>;
}
