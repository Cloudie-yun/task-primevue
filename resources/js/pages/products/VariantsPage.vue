<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Product } from '@/types/product';
import type { VariantType } from '@/types/product-settings';
import ProductVariantTable from './ProductForms/ProductVariants/ProductVariantTable.vue';
import { index as productsIndex, edit as productsEdit } from '@/routes/products';

const props = defineProps<{ product: Product; variantTypes: VariantType[] }>();

defineOptions({
    layout: (pageProps: { product: Product }) => ({
        breadcrumbs: [
            { title: 'Products List', href: productsIndex().url },
            { title: pageProps.product.name, href: productsEdit(pageProps.product.id).url },
            { title: 'Variants', href: '' },
        ],
    }),
});

const pageTitle = computed(() => `Variants - ${props.product.name}`);
</script>

<template>
    <Head :title="pageTitle" />

    <div class="bg-surface-0 p-6 dark:bg-surface-900">
        <div class="mx-auto flex max-w-7xl flex-col gap-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ product.name }} variants</h1>
                    <p class="text-sm text-muted-foreground">Manage SKU, pricing, and stock in a dedicated full-page view.</p>
                </div>
            </div>

            <div class="rounded-xl border bg-white p-4 shadow-sm dark:bg-surface-800">
                <!-- <ProductVariantTable :product="product" :variant-types="variantTypes" :scroll-height="600" /> -->
            </div>
        </div>
    </div>
</template>
