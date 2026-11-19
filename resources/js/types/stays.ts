import type { Option } from './shared';

export type ListingCardData = {
    id: number;
    title: string;
    location: string;
    cover: string | null;
    min_price_minor: number;
    spaces: number;
    distance_km: number | null;
    lat: number;
    lng: number;
};

export type CatalogFilterValues = {
    campus?: string;
    radius?: string;
    period: string;
    type?: string;
    min_price?: string;
    max_price?: string;
    move_in?: string;
    gender?: string;
    sort?: string;
    amenities: number[];
};

export type CatalogOptions = {
    campuses: Option<number>[];
    radii: number[];
    periods: Option[];
    unitTypes: Option[];
    genderPolicies: Option[];
    amenities: Option<number>[];
};

export type StayPlan = {
    period: string;
    label: string;
    price_minor: number;
    min_periods: number;
    max_periods: number;
};

export type StayUnit = {
    id: number;
    name: string;
    type: string;
    parent: string | null;
    capacity: number;
    description: string | null;
    gender_policy: string;
    communities: string[];
    amenities: string[];
    deposit_minor: number;
    plans: StayPlan[];
    unavailable: { starts_on: string; ends_on: string }[];
};

export type StayQuote = {
    unit_id: number;
    period: string;
    start: string;
    periods: number;
    end: string;
    price_minor: number | null;
    total_minor: number | null;
    deposit_minor: number;
    available: boolean;
    errors: string[];
};
