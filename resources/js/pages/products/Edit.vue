<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import ProductForm from '@/components/ProductForm.vue';
import { index as productsIndex } from '@/routes/products';
import type { Category } from '@/types/product-settings';
import type { Product } from '@/types/product';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Products List', href: productsIndex() },
            { title: 'Edit', href: '' },
        ],
    },
});

defineProps<{
    product: Product;
    categories: Category[];
}>();
</script>

<template>
    <Head title="Edit Product" />

    <div class="mx-auto w-full max-w-3xl space-y-6 px-6 py-10">
        <Heading
            variant="small"
            :title="`Edit ${product.name}`"
            description="Update product details"
        />

        <ProductForm :product="product" :categories="categories" />
    </div>
</template>