<script setup lang="ts">
import { Trash, Undo } from '@primeicons/vue';
import { computed, nextTick, ref } from 'vue';
import type { VariantType } from '@/types/product-settings';
import { variantRowSchema } from '@/validation/product';

export type WorkingVariant = {
    id?: number;
    _key: string;
    sku: string;
    additional_price: number;
    stock: number;
    values: { id: number; value: string; variant_type_id: number }[];
    markedForDelete: boolean;
};

defineProps<{ variantTypes: VariantType[]; scrollHeight: number | '300' }>();

const variants = defineModel<WorkingVariant[]>('variants', { required: true });
const deletedIds = defineModel<number[]>('deletedIds', { default: () => [] });

const selectedRows = ref<WorkingVariant[]>([]);
const attemptedSubmit = ref(false);

// --- Validation -----------------------------------------------------------

function fieldErrors(variant: WorkingVariant): { additional_price?: string; stock?: string } {
    const result = variantRowSchema.safeParse({
        additional_price: variant.additional_price,
        stock: variant.stock,
    });

    if (result.success) return {};

    const errors: { additional_price?: string; stock?: string } = {};
    for (const issue of result.error.issues) {
        const field = issue.path[0] as 'additional_price' | 'stock';
        if (!errors[field]) errors[field] = issue.message;
    }
    return errors;
}

const invalidKeys = computed(() => {
    const keys = new Set<string>();
    variants.value.forEach((variant) => {
        if (variant.markedForDelete) return;
        if (Object.keys(fieldErrors(variant)).length) keys.add(variant._key);
    });
    return keys;
});

// New rows (never persisted, not marked for delete) get a green highlight;
// error rows get red; deleted rows get faded. Order matters — delete/error win over "new".
function rowClass(data: WorkingVariant) {
    if (data.markedForDelete) return 'opacity-50';
    if (attemptedSubmit.value && invalidKeys.value.has(data._key)) return 'bg-red-50! dark:bg-red-950/30!';
    if (!data.id) return 'bg-green-50! dark:bg-green-950/30!';
    return '';
}

function validate(): boolean {
    attemptedSubmit.value = true;

    if (invalidKeys.value.size === 0) return true;

    const firstInvalidKey = variants.value.find((v) => invalidKeys.value.has(v._key))?._key;
    if (firstInvalidKey) {
        nextTick(() => {
            const el = document.querySelector(`[data-variant-row="${firstInvalidKey}"] input`);
            (el as HTMLElement | null)?.focus();
            el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    return false;
}

defineExpose({ validate });

// --- Delete / undo ----------------------------------------------------------

function setDeleted(rows: WorkingVariant[], deleted: boolean) {
    rows.forEach((row) => {
        row.markedForDelete = deleted;

        if (!row.id) return;

        const already = deletedIds.value.includes(row.id);
        if (deleted && !already) {
            deletedIds.value = [...deletedIds.value, row.id];
        } else if (!deleted && already) {
            deletedIds.value = deletedIds.value.filter((id) => id !== row.id);
        }
    });

    selectedRows.value = [];
}

function deleteVariant(variant: WorkingVariant) {
    setDeleted([variant], true);
}

function undoDelete(variant: WorkingVariant) {
    setDeleted([variant], false);
}

// Selection can now contain a mix of active and deleted rows —
// act only on the relevant subset for each button.
const selectedActiveRows = computed(() => selectedRows.value.filter((r) => !r.markedForDelete));
const selectedDeletedRows = computed(() => selectedRows.value.filter((r) => r.markedForDelete));

function deleteSelected() {
    if (selectedActiveRows.value.length) setDeleted(selectedActiveRows.value, true);
}

function undoSelected() {
    if (selectedDeletedRows.value.length) setDeleted(selectedDeletedRows.value, false);
}

function variantLabel(variant: WorkingVariant) {
    return variant.values.map((value) => value.value).join(' / ');
}
</script>

<template>
    <div class="w-full space-y-2">
        <div v-if="selectedRows.length" class="flex items-center justify-between rounded-md border bg-surface-50 px-3 py-2 dark:bg-surface-800">
            <span class="text-sm text-muted-foreground">{{ selectedRows.length }} selected</span>
            <div class="flex gap-2">
                <Button v-if="selectedActiveRows.length" label="Delete selected" severity="danger" variant="text" size="small" @click="deleteSelected" />
                <Button v-if="selectedDeletedRows.length" label="Undo delete for selected" severity="secondary" variant="text" size="small" @click="undoSelected" />
            </div>
        </div>

        <DataTable
            v-model:selection="selectedRows"
            :value="variants"
            dataKey="_key"
            stripedRows
            scrollable
            :scrollHeight="scrollHeight + 'px'"
            class="w-full"
            :rowClass="rowClass"
        >
            <template #empty> No variants yet. </template>

            <Column selectionMode="multiple" style="width: 3rem" />

            <Column header="Variant" style="width: 22%" bodyStyle="text-align:left">
                <template #body="{ data }">
                    <span :class="data.markedForDelete ? 'text-muted-foreground line-through' : 'text-muted-foreground'">
                        {{ variantLabel(data) }}
                    </span>
                </template>
            </Column>

            <Column field="sku" header="SKU" style="width: 22%" bodyStyle="text-align:left">
                <template #body="{ data }">
                    <InputText 
                        v-model="data.sku" 
                        :disabled="data.markedForDelete" 
                        size="small" 
                        fluid 
                    />
                </template>
            </Column>

            <Column field="additional_price" header="Additional Price" style="width: 20%" bodyStyle="text-align:left">
                <template #body="{ data }">
                    <div :data-variant-row="data._key">
                        <Inplace :disabled="data.markedForDelete">
                            <template #display>
                                <span :class="{ 'text-red-600 dark:text-red-400': attemptedSubmit && fieldErrors(data).additional_price }">
                                    {{ Number(data.additional_price || 0).toLocaleString('en-US', { style: 'currency', currency: 'MYR' }) }}
                                </span>
                            </template>
                            <template #content>
                                <InputNumber
                                    v-model="data.additional_price"
                                    mode="currency"
                                    currency="MYR"
                                    locale="en-US"
                                    autofocus
                                    fluid
                                    :invalid="attemptedSubmit && !!fieldErrors(data).additional_price"
                                />
                            </template>
                        </Inplace>
                        <p v-if="attemptedSubmit && fieldErrors(data).additional_price" class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ fieldErrors(data).additional_price }}
                        </p>
                    </div>
                </template>
            </Column>

            <Column field="stock" header="Stock" style="width: 15%" bodyStyle="text-align:left">
                <template #body="{ data }">
                    <div>
                        <Inplace :disabled="data.markedForDelete">
                            <template #display>
                                <span :class="{ 'text-red-600 dark:text-red-400': attemptedSubmit && fieldErrors(data).stock }">
                                    {{ data.stock }}
                                </span>
                            </template>
                            <template #content>
                                <InputNumber
                                    v-model="data.stock"
                                    autofocus
                                    fluid
                                    :invalid="attemptedSubmit && !!fieldErrors(data).stock"
                                />
                            </template>
                        </Inplace>
                        <p v-if="attemptedSubmit && fieldErrors(data).stock" class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ fieldErrors(data).stock }}
                        </p>
                    </div>
                </template>
            </Column>

            <Column field="actions" header="" style="width: 1%" bodyStyle="text-align:center">
                <template #body="{ data }">
                    <Button v-if="data.markedForDelete" severity="secondary" variant="text" rounded @click="undoDelete(data)">
                        <Undo />
                    </Button>
                    <Button v-else severity="danger" variant="text" rounded @click="deleteVariant(data)">
                        <Trash />
                    </Button>
                </template>
            </Column>
        </DataTable>
    </div>
</template>