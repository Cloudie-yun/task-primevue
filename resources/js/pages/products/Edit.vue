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

</script>

<template>
    <Head title="Edit Product" />

    <div class="mx-auto h-full w-full max-w-6xl space-y-6 px-6 py-10 relative max-h-[80vh]">
        <Heading
            variant="small"
            :title="`Edit ${product?.name}`"
            description="Update product details"
        />

        <ProductForm :categories="categories" :variant-types="variantTypes" :product="product" />
    </div>
</template>
