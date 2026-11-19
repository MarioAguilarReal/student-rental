export type InspectionStatus =
    | 'pending_assignment'
    | 'assigned'
    | 'in_progress'
    | 'completed'
    | 'cancelled';
export type InspectionResult =
    | 'approved'
    | 'approved_with_observations'
    | 'rejected';
export type AnswerResult = 'compliant' | 'non_compliant' | 'not_applicable';

export type EvidencePhoto = { id: number; url: string };

export type InspectionSummary = {
    id: number;
    type: string;
    type_label: string;
    status: InspectionStatus;
    status_label: string;
    result: InspectionResult | null;
    result_label: string | null;
    observations: string | null;
    scheduled_for: string | null;
    completed_at: string | null;
    valid_until: string | null;
    inspector: { id: number; name: string } | null;
    property: {
        id: number;
        title: string;
        location: string;
        owner: { id: number; name: string; email: string };
    };
};

export type ChecklistAnswer = {
    id: number;
    result: AnswerResult;
    comment: string | null;
    photos: EvidencePhoto[];
};

export type ChecklistSectionItem = {
    id: number;
    text: string;
    category: string;
    is_critical: boolean;
    requires_photo: boolean;
    answer: ChecklistAnswer | null;
};

export type InspectionDetail = InspectionSummary & {
    address: string;
    map_url: string | null;
    ownership_proof: { name: string; url: string } | null;
    template_version: number;
    sections: {
        key: string;
        unit_id: number | null;
        title: string | null;
        items: ChecklistSectionItem[];
    }[];
    progress: { answered: number; total: number };
    has_critical_failure: boolean;
};

export type OwnerInspection = InspectionSummary & {
    failed_items: {
        id: number;
        text: string;
        unit: string | null;
        is_critical: boolean;
        comment: string | null;
        photos: EvidencePhoto[];
    }[];
};
