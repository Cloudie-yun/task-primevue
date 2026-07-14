<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { variants as variantIndex } from '@/routes/product-settings/index';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: variantIndex(),
            }
        ]
    }
});
</script>


<template>
    <Head title="Categories" />
    <h1 class="sr-only">Product Variants</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Product Variants"
            description="Manage your product variant types"
        />
        <!-- DataTable here -->

    </div>

</template>