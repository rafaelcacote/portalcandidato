/**
 * Process title catalog exposed to candidates (matches Inertia JSON from Eloquent).
 */
export type ProcessTitleItemRow = {
    id: number;
    code: string;
    title: string;
    score_per_unit: string | number;
    score_unit: string;
    max_quantity: number | null;
    period_rule: string | null;
    requires_attachment: boolean;
    accepted_formats: string[] | null;
    max_file_size_mb: number;
    candidate_instructions: string | null;
    order: number;
};

export type ProcessTitleGroupRow = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    max_score: string | number;
    order: number;
    items: ProcessTitleItemRow[];
};
