import { Badge } from '@/components/ui/badge';
import type { VerificationStatus } from '@/types';

const variants: Record<
    VerificationStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    unverified: 'outline',
    email_verified: 'outline',
    in_review: 'secondary',
    verified: 'default',
    rejected: 'destructive',
    expired: 'destructive',
};

export default function VerificationStatusBadge({
    status,
    label,
}: {
    status: VerificationStatus;
    label: string;
}) {
    return <Badge variant={variants[status]}>{label}</Badge>;
}
