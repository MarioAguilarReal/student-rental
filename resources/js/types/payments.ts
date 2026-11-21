export type OwnerBalanceRow = {
    id: number;
    folio: string | null;
    property: string;
    unit: string;
    client: string;
    status: string;
    status_label: string;
    starts_on: string;
    ends_on: string;
    commission_bp: number;
    gross_minor: number;
    commission_minor: number;
    net_minor: number;
    upcoming_minor: number;
};

export type OwnerBalance = {
    totals: {
        gross_minor: number;
        commission_minor: number;
        net_minor: number;
        deposits_minor: number;
        upcoming_minor: number;
    };
    rentals: OwnerBalanceRow[];
};

export type FinancePanel = {
    totals: {
        commission_minor: number;
        commission_month_minor: number;
        owner_payable_minor: number;
        deposits_minor: number;
        overdue_minor: number;
    };
    owners: { id: number; name: string; amount_minor: number }[];
    overdue: {
        id: number;
        number: number;
        rental_id: number;
        folio: string | null;
        client: string;
        property: string;
        due_on: string;
        amount_minor: number;
    }[];
    payments: {
        id: number;
        rental_id: number;
        folio: string | null;
        number: number;
        client: string;
        amount_minor: number;
        status: 'succeeded' | 'failed';
        status_label: string;
        processed_at: string | null;
    }[];
};

export type CommissionRuleRow = {
    id: number;
    percentage_bp: number;
    valid_from: string;
    valid_until: string | null;
    state: 'current' | 'scheduled' | 'past';
};
