import type { Category } from './product-settings';

export enum ProductStatus {
    Draft = 'draft',
    Published = 'published',
    Archived = 'archived',
}

export const productStatusOptions = [
    { label: 'Draft', value: ProductStatus.Draft },
    { label: 'Published', value: ProductStatus.Published },
    { label: 'Archived', value: ProductStatus.Archived },
];

export interface Media {
    id: number;
    name: string;
    file_name: string;
    mime_type: string | null;
    size: number;
    original_url: string;
}

export interface Product {
    id: number;
    name: string;
    category_id: number | null;
    category?: Category | null; // present when eager-loaded via with('category')
    slug: string;
    description: string | null;
    price: string; // decimal:2 casts serialize as string in JSON, not number
    status: ProductStatus;
    media?: Media[]; // present when eager-loaded via with('media')
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}