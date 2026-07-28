<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import { variantRowSchema } from '@/validation/product';
import { index as productsIndex, edit as productsEdit } from '@/routes/products';
import { index as variantsIndex, show as variantsShow, update as variantsUpdate } from '@/routes/products/variants';
import ProductVariantsTable, { type VariantRow } from './ProductVariantsTable.vue';
import ProductVariantEditCard from './ProductVariantEditCard.vue';
import { PaginatedResponse } from '@/types/pagination.js';
import ConfirmDeleteDialog from '@/components/DeleteDialog.vue';

const props = defineProps<{
    product?: { id: number; slug: string; name: string; category?: { name: string } | null; price?: string } | null;
    variants: VariantRow[] | PaginatedResponse<VariantRow>;
    products: { label: string; items: { id: number; name: string; slug: string }[] }[];
    cameFromBrowse?: boolean;
}>();

const variantRows = computed<VariantRow[]>(() =>
    Array.isArray(props.variants) ? props.variants : props.variants.data
);

const isScoped = computed(() => !!props.product);

defineOptions({
    layout: (pageProps: { product?: { id: number; name: string; slug: string }; cameFromBrowse?: boolean }) => ({
        breadcrumbs: !pageProps.product
            ? [{ title: 'Products List', href: productsIndex().url }, { title: 'All Variants', href: '' }]
            : pageProps.cameFromBrowse
              ? [
                    { title: 'Products List', href: productsIndex().url },
                    { title: 'All Variants', href: variantsIndex().url },
                    { title: pageProps.product.name, href: '' },
                ]
              : [
                    { title: 'Products List', href: productsIndex().url },
                    { title: pageProps.product.name, href: productsEdit(pageProps.product.slug).url },
                    { title: 'Variants', href: '' },
                ],
    }),
});

function goToProduct(productId: number | null) {
    if (!productId) { router.get(variantsIndex().url); return; }
    const slug = props.products.flatMap((g) => g.items).find((p) => p.id === productId)?.slug;
    if (slug) router.get(variantsShow(slug).url, { from: 'browse' });
}

// --- Working copy of price/stock, per variant id ---
type WorkingRow = { id: number; additional_price: number; stock: number; markedForDelete: boolean };

const originals = new Map<number, { additional_price: number; stock: number }>(
    variantRows.value.map((v) => [v.id, { additional_price: Number(v.additional_price), stock: v.stock }])
);

// Track edited rows
const dirtyIds = computed(() =>
    new Set(form.variants.filter((v) => !v.markedForDelete && isRowDirty(v.id)).map((v) => v.id))
);

const form = useForm({
    variants: variantRows.value.map((v) => ({
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
const selectedVariant = computed(() => variantRows.value.find((v) => v.id === selectedVariantId.value) ?? null);
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

// --- Show Delete Product Variants Confirmation Message ---
const showDeleteConfirm = ref(false);

const pendingDeletions = computed(() =>
    form.variants.filter((v) => v.markedForDelete)
);

function variantLabelById(id: number) {
    const v = variantRows.value.find((row) => row.id === id);
    return v ? v.values.map((val) => val.value).join(' / ') : '';
}

// --- Resync Updated Variant Data ---
function resyncWorkingState() {
    originals.clear();
    variantRows.value.forEach((v) =>
        originals.set(v.id, { additional_price: Number(v.additional_price), stock: v.stock })
    );

    form.variants = variantRows.value.map((v) => ({
        id: v.id,
        additional_price: Number(v.additional_price),
        stock: v.stock,
        markedForDelete: false,
    }));
}
// --- Submit ---
const toast = useToast();

function submit() {
    attemptedSubmit.value = true;

    if (invalidIds.value.size > 0) {
        toast.add({ severity: 'warn', summary: 'Please fix the highlighted rows', life: 4000 });
        selectedVariantId.value = [...invalidIds.value][0];
        return;
    }
    //Show delete confirmation dialog
    if (pendingDeletions.value.length > 0) {
        showDeleteConfirm.value = true;
        return;
    }
    performSubmit();
}

function performSubmit() {
    form.transform((data) => ({
        variants: data.variants
            .filter((v) => !v.markedForDelete)
            .map(({ id, additional_price, stock }) => ({ id, additional_price, stock })),
        deleted_variant_ids: data.variants.filter((v) => v.markedForDelete).map((v) => v.id),
    })).put(variantsUpdate(props.product!.slug).url, {
        onSuccess: () => {
            resyncWorkingState();
            toast.add({ severity: 'success', summary: 'Variants updated', life: 3000 });
            selectedVariantId.value = null;
            showDeleteConfirm.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Failed to update variants', life: 4000 });
            showDeleteConfirm.value = false;
        },
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
                optionGroupLabel="label"
                optionGroupChildren="items"
                option-label="name"
                option-value="id"
                filter
                filter-placeholder="Search products..."
                placeholder="Select to edit product"
                show-clear
                fluid
                class="max-w-1/3"
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
            <div
                v-else-if="isScoped"
                class="flex items-center justify-center rounded-lg border border-dashed p-6 text-sm text-muted-foreground"
            >
                Select a row below to edit its price and stock.
        </div>
            <ProductVariantsTable
                :variants="variantRows"
                :is-scoped="isScoped"
                :product-slug="product?.slug"
                :working-lookup="(id: number) => workingRow(id)"
                :selected-id="selectedVariantId"
                :attempted-submit="attemptedSubmit"
                :invalid-ids="invalidIds"
                :dirty-ids="dirtyIds"
                @select="selectVariant"
            />

            <Paginator
                v-if="!isScoped && !Array.isArray(variants)"
                :rows="variants.per_page"
                :totalRecords="variants.total"
                :first="(variants.current_page - 1) * variants.per_page"
                @page="(e) => router.get(variantsIndex().url, { page: e.page + 1 }, { preserveScroll: true })"
            />

            <div v-if="isScoped" class="flex items-start justify-between gap-3">
                <div class="flex gap-4 text-xs text-muted-foreground">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>Unsaved change</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>Needs attention</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full opacity-40 bg-surface-400"></span>Marked for deletion</span>
                </div>
                <div class="flex items-center gap-5">
                    <span v-if="dirtyOrDeletedCount" class="text-sm text-muted-foreground">
                        {{ dirtyOrDeletedCount }} change(s) pending
                    </span>
                    <Button label="Save Changes" :disabled="!dirtyOrDeletedCount" :loading="form.processing" @click="submit" />
                </div>
            </div>
        </div>
    </div>
    <ConfirmDeleteDialog
        v-model:visible="showDeleteConfirm"
        header="Confirm Variant Deletion"
        delete-label="Save & Delete"
        :processing="form.processing"
        @confirm="performSubmit"
    >
        You're about to permanently delete {{ pendingDeletions.length }} variant(s):
        <ul class="mt-2 list-disc pl-5">
            <li v-for="v in pendingDeletions" :key="v.id">{{ variantLabelById(v.id) }}</li>
        </ul>
        This will also save any other pending price/stock changes. This action cannot be undone.
    </ConfirmDeleteDialog>
</template>