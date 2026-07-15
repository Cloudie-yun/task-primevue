<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import Heading from '@/components/Heading.vue';
import TextLink from '@/components/TextLink.vue';
import { index as products } from '@/routes/products';
import { create } from '@/routes/products';
import {Check} from '@primeicons/vue';
import type { Product } from '@/types/product';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Products List',
                href: products(),
            },
        ],
    },
});

// defineProps<{
//     products: Product[];
// }>();

</script>

<template>
    <Head title="Products List" />

    <div class="flex flex-col h-full flex-1 p-5 space-y-6">
        <Heading
            variant="small"
            title="Create Product"
            description="Add a new product to your catalog"
        />
        <!-- <div v-if="products.length"> -->
            <!-- TODO Display DataTable of Products here -->
             <!-- TODO: Add Switch to Card View Function after Datatable done -->
        <!-- </div> -->
        <p class="text-sm text-muted-foreground">
            No products yet. <TextLink :href="create()" class="text-sm">Create one Now</TextLink>
        </p>
    </div>
</template>