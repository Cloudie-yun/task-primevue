export interface ProductImage {
    id: number;
    url: string;
}

export interface StaticAttribute {
    id: number;
    value: string;
    type: { id: number; name: string };
}

export interface VariantOptionValue {
    id: number;
    value: string;
    sort_order: number;
}

export interface VariantOptionType {
    id: number;
    name: string;
    values: VariantOptionValue[];
}

export interface PreviewVariant {
    id: number;
    sku: string;
    additional_price: string;
    stock: number;
    value_ids: number[];
}

export interface PreviewProduct {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    status: string;
    category: { id: number; name: string } | null;
    images: ProductImage[];
    static_attributes: StaticAttribute[];
    variant_types: VariantOptionType[];
    variants: PreviewVariant[];
}