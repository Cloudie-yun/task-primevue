<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import ProductForm from './ProductForms/ProductForm.vue';
import { index as productsIndex } from '@/routes/products';
import type { Category, VariantType } from '@/types/product-settings';
import type { Product } from '@/types/product';
import { ProductStatus } from '@/types/product';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Products List', href: productsIndex() },
            { title: 'Edit', href: '' },
        ],
    },
});

defineProps<{
    product?: Product; 
    categories: Category[];
    variantTypes: VariantType[];
}>();

function getStatusLabel(status?: string) {
    switch (status) {
        case ProductStatus.Draft:
            return 'Draft';

        case ProductStatus.Published:
            return 'Published';

        case ProductStatus.Archived:
            return 'Archived';

        default:
            return '';
    }
}

function getStatusSeverity(status?: string) {
    switch (status) {
        case ProductStatus.Draft:
            return 'secondary';

        case ProductStatus.Published:
            return 'success';

        case ProductStatus.Archived:
            return 'danger';

        default:
            return undefined;
    }
}
</script>

<template>
    <Head title="Edit Product" />

    <div class="mx-auto w-full max-w-4xl space-y-6 px-6 py-10">
        <div class="flex justify-between items-center">
            <Heading
                variant="small"
                :title="`Edit ${product?.name}`"
                description="Update product details"
            />
            <div class="flex items-center gap-2 text-muted-foreground">
                <!-- <Tag :value="product?.status" class="text-lg! px-5!" severity="secondary" /> -->
                <Tag
                    :value="getStatusLabel(product?.status)"
                    :severity="getStatusSeverity(product?.status)"
                    class="text-base! px-5!"
                />
            </div>
        </div>
        

        <ProductForm :categories="categories" :variant-types="variantTypes" :product="product" />
        <!-- <ProductForm :product="product" :categories="categories" /> -->
    </div>
</template>
