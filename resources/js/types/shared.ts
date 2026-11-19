export type Option<T extends string | number = string> = {
    value: T;
    label: string;
};

/** Laravel paginator as serialized by Inertia. */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type VerificationStatus =
    | 'unverified'
    | 'email_verified'
    | 'in_review'
    | 'verified'
    | 'rejected'
    | 'expired';
