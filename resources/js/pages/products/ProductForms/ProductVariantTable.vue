<script setup lang="ts">
import { Trash } from '@primeicons/vue';
import { ref } from 'vue';
import type { VariantType } from '@/types/product-settings';

export type WorkingVariant = {
    id?: number; // present only for variants that already exist in the DB
    _key: string; // stable row key: `v-{id}` for existing, `new-{uuid}` for freshly generated
    sku: string;
    additional_price: number;
    stock: number;
    values: { id: number; value: string; variant_type_id: number }[];
};

defineProps<{ variantTypes: VariantType[]; scrollHeight: number | '300' }>();

const variants = defineModel<WorkingVariant[]>('variants', { required: true });
const deletedIds = defineModel<number[]>('deletedIds', { default: () => [] });

const editingRows = ref<WorkingVariant[]>([]);
const selectedRows = ref<WorkingVariant[]>([]);

function variantLabel(variant: WorkingVariant) {
    return variant.values.map((value) => value.value).join(' / ');
}

function onRowEditSave(event: { newData: WorkingVariant }) {
    const index = variants.value.findIndex((item) => item._key === event.newData._key);
    if (index !== -1) {
        variants.value[index] = { ...event.newData };
    }
}

function removeVariants(rows: WorkingVariant[]) {
    // Only variants that already exist in the DB need to be flagged for deletion on save.
    const idsToDelete = rows.filter((row) => row.id).map((row) => row.id as number);
    if (idsToDelete.length) {
        deletedIds.value = [...deletedIds.value, ...idsToDelete];
    }

    const keysToRemove = new Set(rows.map((row) => row._key));
    variants.value = variants.value.filter((item) => !keysToRemove.has(item._key));
    selectedRows.value = selectedRows.value.filter((item) => !keysToRemove.has(item._key));
}

function deleteVariant(variant: WorkingVariant) {
    removeVariants([variant]);
}

function deleteSelected() {
    if (selectedRows.value.length) removeVariants(selectedRows.value);
}
</script>

<template>
    <div class="w-full space-y-2">
        <div v-if="selectedRows.length" class="flex items-center justify-between rounded-md border bg-surface-50 px-3 py-2 dark:bg-surface-800">
            <span class="text-sm text-muted-foreground">{{ selectedRows.length }} selected</span>
            <Button label="Delete selected" severity="danger" variant="text" size="small" @click="deleteSelected" />
        </div>

        <DataTable
            v-model:editingRows="editingRows"
            v-model:selection="selectedRows"
            :value="variants"
            dataKey="_key"
            editMode="row"
            stripedRows
            scrollable
            :scrollHeight="scrollHeight + 'px'"
            class="w-full"
            @row-edit-save="onRowEditSave"
        >
            <template #empty> No variants yet. </template>
            <Column selectionMode="multiple" style="width: 3rem" />
            <Column header="Variant" style="width: 25%" bodyStyle="text-align:left">
                <template #body="{ data }">
                    <span class="text-muted-foreground">{{ variantLabel(data) }}</span>
                </template>
            </Column>
            <Column field="sku" header="SKU" style="width: 22%" bodyStyle="text-align:left">
                <template #body="{ data }">
                    <span class="text-muted-foreground">{{ data.sku || '—' }}</span>
                </template>
                <template #editor="{ data, field }">
                    <InputText v-model="data[field]" fluid />
                </template>
            </Column>
            <Column field="additional_price" header="Additional Price" style="width: 20%" bodyStyle="text-align:left">
                <template #body="{ data }">
                    {{ Number(data.additional_price).toLocaleString('en-US', { style: 'currency', currency: 'MYR' }) }}
                </template>
                <template #editor="{ data, field }">
                    <InputNumber v-model="data[field]" mode="currency" currency="MYR" locale="en-US" fluid />
                </template>
            </Column>
            <Column field="stock" header="Stock" style="width: 15%" bodyStyle="text-align:left">
                <template #body="{ data }">{{ data.stock }}</template>
                <template #editor="{ data, field }">
                    <InputNumber v-model="data[field]" fluid />
                </template>
            </Column>
            <Column :rowEditor="true" style="width: 10%" bodyStyle="text-align:center" />
            <Column field="actions" header="" style="width: 1%" bodyStyle="text-align:center">
                <template #body="{ data }">
                    <Button severity="danger" variant="text" rounded @click="deleteVariant(data)">
                        <Trash />
                    </Button>
                </template>
            </Column>
        </DataTable>
    </div>
</template>