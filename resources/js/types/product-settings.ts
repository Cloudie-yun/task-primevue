export interface Category {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface VariantValue {
    id: number;
    variant_type_id: number;
    value: string;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

export interface VariantType {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
    values?: VariantValue[]; // present only when eager-loaded via with('values')
}