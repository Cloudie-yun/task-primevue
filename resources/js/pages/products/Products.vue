<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import TextLink from '@/components/TextLink.vue';
import ProductDelete from './ProductDelete.vue';
import { index as productsIndex, create, edit as productEdit, preview as productPreview } from '@/routes/products';
import { Pencil, Trash } from '@primeicons/vue';
import type { Product } from '@/types/product';
import type { PaginatedResponse } from '@/types/pagination';
import { Plus } from '@primeicons/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Products List',
                href: productsIndex(),
            },
        ],
    },
});

const props = defineProps<{
    products: PaginatedResponse<Product>;
}>();

const showDeleteDialog = ref(false);
const productToDelete = ref<Product | null>(null);

function openDeleteDialog(product: Product) {
    productToDelete.value = product;
    showDeleteDialog.value = true;
}

function handleDeleted() {
    router.reload({ only: ['products'] });
}

function onPageChange(event: { page: number }) {
    router.get(
        productsIndex().url,
        { page: event.page + 1 }, // PrimeVue is 0-indexed, Laravel paginator is 1-indexed
        { preserveState: true, preserveScroll: true, only: ['products'] },
    );
}

function statusSeverity(status: string) {
    switch (status) {
        case 'published':
            return 'success';
        case 'archived':
            return 'danger';
        default:
            return 'secondary';
    }
}

const onProductClick = (event: { data: Product }) => {
    router.get(productPreview(event.data.slug).url);
};
</script>

<template>
    <Head title="Products List" />

    <div class="flex h-full w-full flex-1 flex-col space-y-6 p-5 max-w-7xl mx-auto">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Products"
                description="Manage your product catalog"
            />
            <Button as="a" :href="create().url" label="Add Product">
                <Plus/>
                Add Product
            </Button>
        </div>

        <!-- <div v-if="products.data.length"> -->
        <div>
            <DataTable
                :value="products.data"
                dataKey="id"
                stripedRows
                :lazy="true"
                :paginator="true"
                :rows="products.per_page"
                :totalRecords="products.total"
                :first="(products.current_page - 1) * products.per_page"
                @page="onPageChange"
                tableStyle="min-width: 50rem"
                @row-click="onProductClick"
            >
            
                <Column header="Image" headerStyle="width: 10%">
                    <template #body="{ data }">
                        <img
                            v-if="data.media?.[0]?.original_url"
                            :src="data.media[0].original_url"
                            :alt="data.name"
                            class="h-12 w-12 rounded-md object-cover"
                        />
                        <div v-else class="h-12 w-12 rounded-md bg-muted" />
                    </template>
                </Column>

                <Column field="name" header="Name" headerStyle="width: 25%">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.name }}</span>
                    </template>
                </Column>

                <Column header="Category" headerStyle="width: 15%">
                    <template #body="{ data }">
                        <span class="text-muted-foreground">{{ data.category?.name ?? '-' }}</span>
                    </template>
                </Column>

                <Column field="price" header="Price" headerStyle="width: 15%">
                    <template #body="{ data }">
                        {{ Number(data.price).toLocaleString('en-MY', { style: 'currency', currency: 'MYR' }) }}
                    </template>
                </Column>

                <Column header="Status" headerStyle="width: 15%">
                    <template #body="{ data }">
                        <Tag :value="data.status" :severity="statusSeverity(data.status)" />
                    </template>
                </Column>

                <Column header="" headerStyle="width: 15%" bodyStyle="text-align:center">
                    <template #body="{ data }">
                        <Button as="a" :href="productEdit(data.slug).url" severity="secondary" variant="text" rounded>
                            <Pencil />
                        </Button>
                        <Button severity="danger" variant="text" rounded @click="openDeleteDialog(data)">
                            <Trash />
                        </Button>
                    </template>
                </Column>
                <template #empty>
                    <p class="text-sm text-muted-foreground text-center p-5">
                        No products yet.
                        <TextLink :href="create().url">
                            Create one now
                        </TextLink>
                    </p>
                </template>
            </DataTable>
            
        </div>
        <ProductDelete v-model:visible="showDeleteDialog" :product="productToDelete" @deleted="handleDeleted" />
    </div>
</template>
