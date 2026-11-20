export type RentalStatus =
    | 'requested'
    | 'approved'
    | 'rejected'
    | 'expired'
    | 'confirmed'
    | 'active'
    | 'completed'
    | 'cancelled';

export type RentalSummary = {
    id: number;
    folio: string | null;
    status: RentalStatus;
    status_label: string;
    property: { id: number; title: string; location: string };
    unit: string;
    client: string;
    period: string;
    period_label: string;
    periods: number;
    starts_on: string;
    ends_on: string;
    total_minor: number;
    deposit_minor: number;
    currency: string;
    expires_at: string | null;
    requested_at: string;
};

export type RentalCharge = {
    number: number;
    concept_label: string;
    amount_minor: number;
    deposit_minor: number;
    due_on: string;
    status: string;
    status_label: string;
};

export type RentalDetail = RentalSummary & {
    price_minor: number;
    client_message: string | null;
    rejection_reason: string | null;
    cancellation_reason: string | null;
    decided_at: string | null;
    cancelled_at: string | null;
    managed: boolean;
    address: string | null;
    contact: { name: string; email: string } | null;
    owner_net_minor: number | null;
    commission_bp: number | null;
    applicant: {
        name: string;
        community: string | null;
        institution: string | null;
        verification: string | null;
        verified: boolean;
    } | null;
    charges: RentalCharge[];
};
