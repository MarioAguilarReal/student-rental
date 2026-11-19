import type { Option } from './shared';

export type PropertyStatus =
    | 'draft'
    | 'pending_inspection'
    | 'approved'
    | 'rejected'
    | 'published'
    | 'paused'
    | 'archived';

export type PropertySummary = {
    id: number;
    title: string;
    status: PropertyStatus;
    status_label: string;
    management_mode_label: string;
    property_type_label: string;
    location: string;
    owner: string;
    cover: string | null;
    units_count: number;
};

export type PropertyDetail = PropertySummary & {
    description: string | null;
    property_type: string;
    management_mode: string;
    pause_reason: string | null;
    owner_email: string;
    street: string | null;
    exterior_number: string | null;
    interior_number: string | null;
    neighborhood: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    latitude: number | null;
    longitude: number | null;
    gender_policy: string;
    allowed_communities: string[];
    amenity_ids: number[];
    house_rules: { id: number; is_allowed: boolean; note: string | null }[];
    inspection_valid_until: string | null;
    submitted_for_inspection_at: string | null;
    passed_inspection: boolean;
    editable: boolean;
};

export type Unit = {
    id: number;
    parent_id: number | null;
    type: string;
    type_label: string;
    name: string;
    description: string | null;
    capacity: number;
    is_rentable: boolean;
    gender_policy: string | null;
    allowed_communities: string[] | null;
    effective_gender_policy: string;
    amenity_ids: number[];
};

export type PropertyPhoto = {
    id: number;
    thumb: string;
    url: string;
    alt: string | null;
    unit_id: number | null;
};

export type UnitTypeOption = Option & {
    can_be_root: boolean;
    children: string[];
};

export type AmenityOption = Option<number> & { category: string };

export type PropertyEditorOptions = {
    propertyTypes: Option[];
    managementModes: Option[];
    unitTypes: UnitTypeOption[];
    genderPolicies: Option[];
    communities: Option[];
    templates: Option[];
    propertyAmenities: AmenityOption[];
    unitAmenities: AmenityOption[];
    houseRules: { id: number; name: string; description: string | null }[];
};

export type PublicationRequirement = {
    key: string;
    label: string;
    met: boolean;
};

export type PlanPeriod = 'weekly' | 'monthly' | 'yearly';

export type PlanData = {
    enabled: boolean;
    price_minor: number;
    min_periods: number;
    max_periods: number;
};

export type PricingData = {
    units: Record<
        string,
        {
            deposit_minor: number | null;
            plans: Record<PlanPeriod, PlanData | null>;
        }
    >;
    blocks: {
        id: number;
        unit_id: number;
        unit_name: string;
        starts_on: string;
        ends_on: string;
        reason: string;
        note: string | null;
    }[];
    commission_bp: number;
    suggestions: {
        weekly_factor: number;
        yearly_factor: number;
        round_to_minor: number;
    };
    periods: { value: PlanPeriod; label: string; max_limit: number }[];
    blockReasons: { value: string; label: string }[];
};
