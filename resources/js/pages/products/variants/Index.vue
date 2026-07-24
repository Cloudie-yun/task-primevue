<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import { variantRowSchema } from '@/validation/product';
import { index as productsIndex, edit as productsEdit } from '@/routes/products';
import { index as variantsIndex, show as variantsShow, update as variantsUpdate } from '@/routes/products/variants';
import ProductVariantsTable, { type VariantRow } from './ProductVariantsTable.vue';
import ProductVariantEditCard from './ProductVariantEditCard.vue';

interface PaginatedIds {
    data: number[]; current_page: number; last_page: number; per_page: number; total: number;
}

const props = defineProps<{
    product?: { id: number; name: string; category?: { name: string } | null; price?: string } | null;
    variants: VariantRow[];
    productsPage?: PaginatedIds;
    products: { id: number; name: string; slug: string;}[];
}>();

const isScoped = computed(() => !!props.product);

defineOptions({
    layout: (pageProps: { product?: { id: number; name: string } }) => ({
        breadcrumbs: pageProps.product
            ? [
                  { title: 'Products List', href: productsIndex().url },
                  { title: pageProps.product.name, href: productsEdit(pageProps.product.slug).url },
                  { title: 'Variants', href: '' },
              ]
            : [{ title: 'All Variants', href: '' }],
    }),
});

function goToProduct(productId: number | null) {
    if (!productId) { router.get(variantsIndex().url); return; }
    const slug = props.products.find((p) => p.id === productId)?.slug;
    if (slug) router.get(variantsShow(slug).url);
}

// --- Working copy of price/stock, per variant id ---
type WorkingRow = { id: number; additional_price: number; stock: number; markedForDelete: boolean };

const originals = new Map<number, { additional_price: number; stock: number }>(
    props.variants.map((v) => [v.id, { additional_price: Number(v.additional_price), stock: v.stock }])
);

// Track edited rows
const dirtyIds = computed(() =>
    new Set(form.variants.filter((v) => !v.markedForDelete && isRowDirty(v.id)).map((v) => v.id))
);

const form = useForm({
    variants: props.variants.map((v) => ({
        id: v.id,
        additional_price: Number(v.additional_price),
        stock: v.stock,
        markedForDelete: false,
    })) as WorkingRow[],
});

function workingRow(id: number): WorkingRow {
    return form.variants.find((v) => v.id === id)!;
}

const selectedVariantId = ref<number | null>(null);
const selectedVariant = computed(() => props.variants.find((v) => v.id === selectedVariantId.value) ?? null);
const selectedWorking = computed(() => (selectedVariantId.value !== null ? workingRow(selectedVariantId.value) : null));

function selectVariant(id: number) {
    selectedVariantId.value = id;
}

function isRowDirty(id: number): boolean {
    const original = originals.get(id)!;
    const row = workingRow(id);
    return row.additional_price !== original.additional_price || row.stock !== original.stock;
}

function cancelRowEdit(id: number) {
    const original = originals.get(id)!;
    const row = workingRow(id);
    row.additional_price = original.additional_price;
    row.stock = original.stock;
}

function toggleDelete(id: number) {
    const row = workingRow(id);
    row.markedForDelete = !row.markedForDelete;
    if (row.markedForDelete) cancelRowEdit(id);
}

// --- Validation ---
const attemptedSubmit = ref(false);

function fieldErrors(id: number) {
    const row = workingRow(id);
    if (row.markedForDelete) return {};
    const result = variantRowSchema.safeParse({ additional_price: row.additional_price, stock: row.stock });
    if (result.success) return {};
    const errors: Record<string, string> = {};
    for (const issue of result.error.issues) errors[issue.path[0] as string] = issue.message;
    return errors;
}

const invalidIds = computed(() =>
    new Set(form.variants.filter((v) => !v.markedForDelete && Object.keys(fieldErrors(v.id)).length > 0).map((v) => v.id))
);

const dirtyOrDeletedCount = computed(() =>
    form.variants.filter((v) => v.markedForDelete || isRowDirty(v.id)).length
);

const toast = useToast();

function submit() {
    attemptedSubmit.value = true;

    if (invalidIds.value.size > 0) {
        toast.add({ severity: 'warn', summary: 'Please fix the highlighted rows', life: 4000 });
        selectedVariantId.value = [...invalidIds.value][0];
        return;
    }

    form.transform((data) => ({
        variants: data.variants
            .filter((v) => !v.markedForDelete)
            .map(({ id, additional_price, stock }) => ({ id, additional_price, stock })),
        deleted_variant_ids: data.variants.filter((v) => v.markedForDelete).map((v) => v.id),
    })).put(variantsUpdate(props.product!.slug).url, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Variants updated', life: 3000 });
            selectedVariantId.value = null;
        },
        onError: () => toast.add({ severity: 'error', summary: 'Failed to update variants', life: 4000 }),
    });
}
</script>

<template>
    <Head title="Product Variants" />

    <div class="p-6">
        <div class="mx-auto flex max-w-7xl flex-col gap-6">
            <div>
                <h1 class="text-2xl font-semibold">{{ isScoped ? `${product!.name} variants` : 'All Variants' }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ isScoped ? 'Click a row to edit its price and stock.' : 'Browse variants across all products.' }}
                </p>
            </div>

            <Select
                v-if="!isScoped"
                :model-value="null"
                :options="products"
                option-label="name"
                option-value="id"
                placeholder="Filter by product"
                show-clear
                class="w-64"
                @update:model-value="goToProduct"
            />

            <ProductVariantEditCard
                v-if="isScoped && selectedVariant && selectedWorking"
                :variant="selectedVariant"
                :working="selectedWorking"
                :errors="fieldErrors(selectedVariant.id)"
                :attempted-submit="attemptedSubmit"
                :is-dirty="isRowDirty(selectedVariant.id)"
                @cancel="cancelRowEdit(selectedVariant.id)"
                @toggle-delete="toggleDelete(selectedVariant.id)"
                @close="selectedVariantId = null"
            />
            
            <ProductVariantsTable
                :variants="variants"
                :is-scoped="isScoped"
                :working-lookup="(id: number) => workingRow(id)"
                :selected-id="selectedVariantId"
                :attempted-submit="attemptedSubmit"
                :invalid-ids="invalidIds"
                :dirty-ids="dirtyIds"
                @select="selectVariant"
            />

            <Paginator
                v-if="!isScoped && productsPage"
                :rows="productsPage.per_page"
                :totalRecords="productsPage.total"
                :first="(productsPage.current_page - 1) * productsPage.per_page"
                @page="(e) => router.get(variantsIndex().url, { page: e.page + 1 }, { preserveScroll: true })"
            />

            <div v-if="isScoped" class="flex items-start justify-between gap-3">
                <div class="flex gap-4 text-xs text-muted-foreground">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>Unsaved change</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>Needs attention</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full opacity-40 bg-surface-400"></span>Marked for deletion</span>
                </div>
                <div>
                    <span v-if="dirtyOrDeletedCount" class="text-sm text-muted-foreground">
                        {{ dirtyOrDeletedCount }} change(s) pending
                    </span>
                    <Button label="Save Changes" :disabled="!dirtyOrDeletedCount" :loading="form.processing" @click="submit" />
                </div>
                
            </div>
        </div>
    </div>
</template>