<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import { FilterService } from '@primevue/core/api';
import ProductVariantController from '@/actions/App/Http/Controllers/Product/ProductVariantController';
import type { EditableProductVariant, Product } from '@/types/product';
import type { VariantType } from '@/types/product-settings';
import { Trash } from '@primeicons/vue';

const props = defineProps<{ product?: Product | null; variantTypes: VariantType[] }>();

// Lookup so the filter function below can tell which type each selected value belongs to.
// IDs are coerced to Number here since Laravel/Inertia can serialize some numeric fields as
// strings, and a strict "5" !== 5 comparison would silently fail every match below.
const valueTypeMap = computed(() => {
    const map = new Map<number, number>();
    props.variantTypes.forEach((type) => (type.values ?? []).forEach((value) => map.set(Number(value.id), Number(type.id))));
    return map;
});

// A variant has several values (Red, XS, XL, ...). Selecting values from the SAME type is OR
// (XS or XL — either satisfies that type), while selecting values from DIFFERENT types is AND
// (Red AND (XS or XL) — every type that has a selection must be satisfied). There's no built-in
// FilterMatchMode for this, so we register one.
FilterService.register('valueIn', (value: (number | string)[] | null, filterValue: (number | string)[] | null) => {
    if (!filterValue || !filterValue.length) return true;
    if (!value) return false;

    const rowValueIds = value.map(Number);

    const groupsByType = new Map<number, number[]>();
    filterValue.forEach((rawValueId) => {
        const valueId = Number(rawValueId);
        const typeId = valueTypeMap.value.get(valueId);
        if (typeId === undefined) return;
        if (!groupsByType.has(typeId)) groupsByType.set(typeId, []);
        groupsByType.get(typeId)!.push(valueId);
    });

    // every type-group must have at least one of its selected values present on the variant
    return [...groupsByType.values()].every((idsInGroup) => idsInGroup.some((valueId) => rowValueIds.includes(valueId)));
});

const selections = defineModel<Record<number, number[]>>('selections', { required: true });
const toast = useToast();
const generating = ref(false);

// Builds a sortable string like "Blue|L" from a variant's values, always ordered by the
// type order in `variantTypes` (not the order values happen to attach in) — so sorting by
// this key naturally groups rows by the first type (e.g. all Colors together), then by the
// second type within each group (e.g. Sizes within each color), and so on.
function buildSortKey(variant: { values: { variant_type_id: number; value: string }[] }): string {
    return props.variantTypes
        .map((type) => variant.values.find((value) => value.variant_type_id === type.id)?.value ?? '')
        .join('|');
}

const localVariants = ref<(EditableProductVariant & { valueIds: number[]; sortKey: string })[]>(
    (props.product?.variants ?? []).map((variant) => ({
        ...variant,
        additional_price: Number(variant.additional_price),
        // derived once here so the DataTable can filter on a plain field ('valueIds')
        valueIds: variant.values.map((value) => Number(value.id)),
        sortKey: buildSortKey(variant),
    })),
);
const editingRows = ref<EditableProductVariant[]>([]);
const savingVariantIds = ref<Record<number, boolean>>({});
const hasSelections = computed(() => Object.keys(selections.value).length > 0);
const variantFilters = ref({
    valueIds: { value: null as number[] | null, matchMode: 'valueIn' },
});
// Flattened list of every value across all types, labeled with its type so
// e.g. two different types sharing a value name ("Small" size vs a color) aren't ambiguous.
const valueFilterOptions = computed(() =>
    props.variantTypes.flatMap((type) => (type.values ?? []).map((value) => ({ id: Number(value.id), label: `${type.name}: ${value.value}` }))),
);

function toggleValue(typeId: number, valueId: number) {
    const current = selections.value[typeId] ?? [];
    selections.value[typeId] = current.includes(valueId)
        ? current.filter((id) => id !== valueId)
        : [...current, valueId];

    if (selections.value[typeId].length === 0) {
        delete selections.value[typeId];
    }
}

function isSelected(typeId: number, valueId: number) {
    return selections.value[typeId]?.includes(valueId) ?? false;
}

function generateVariants() {
    if (!props.product || !hasSelections.value) {
        return;
    }

    generating.value = true;
    router.post(ProductVariantController.generate(props.product.id).url, { selections: selections.value }, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Variants generated', life: 3000 }),
        onError: (errors) => toast.add({ severity: 'error', summary: 'Could not generate variants', detail: Object.values(errors)[0] as string, life: 5000 }),
        onFinish: () => { generating.value = false; },
    });
}

function variantLabel(variant: EditableProductVariant) {
    return variant.values.map((value) => value.value).join(' / ');
}

function onRowEditSave(event: { newData: EditableProductVariant; index: number }) {
    const { newData } = event;

    if (!props.product) return;

    const original = props.product.variants?.find((item) => item.id === newData.id);
    const isDirty = !!original && (
        newData.sku.trim() !== original.sku
        || Number(newData.additional_price) !== Number(original.additional_price)
        || Number(newData.stock) !== Number(original.stock)
    );

    // reflect the edited row in the table immediately, since PrimeVue expects
    // the row to visually update to newData as soon as row-edit-save fires
    const index = localVariants.value.findIndex((item) => item.id === newData.id);
    if (index !== -1) {
        localVariants.value[index] = { 
            ...newData,
            valueIds: newData.values.map((value) => value.id),
            sortKey: buildSortKey(newData),
        };
    }

    if (!isDirty) {
        return;
    }

    savingVariantIds.value[newData.id] = true;
    router.patch(
        ProductVariantController.update([props.product.id, newData.id]).url,
        { sku: newData.sku.trim(), additional_price: newData.additional_price, stock: newData.stock },
        {
            preserveScroll: true,
            onSuccess: () => toast.add({ severity: 'success', summary: 'Variant updated', life: 3000 }),
            onError: (errors) => {
                toast.add({ severity: 'error', summary: 'Update failed', detail: Object.values(errors)[0] as string, life: 5000 });
                // revert the row to the last known-good server value on failure
                if (original && index !== -1) {
                    localVariants.value[index] = {
                        ...original,
                        additional_price: Number(original.additional_price),
                        valueIds: original.values.map((value) => Number(value.id)),
                        sortKey: buildSortKey(original),
                    };
                }
            },
            onFinish: () => { savingVariantIds.value[newData.id] = false; },
        },
    );
}

function deleteVariant(variant: EditableProductVariant) {
    if (!props.product) return;
    router.delete(ProductVariantController.destroy([props.product.id, variant.id]).url, {
        preserveScroll: true,
        onSuccess: () => { toast.add({ severity: 'success', summary: 'Variant deleted', life: 3000 }); localVariants.value = localVariants.value.filter((item) => item.id !== variant.id); },
        onError: () => toast.add({ severity: 'error', summary: 'Delete failed', life: 3000 }),
    });
}
</script>

<template>
    <div class="space-y-8 py-4">
        <!-- Step 1: choose which combinations to generate -->
        <div class="space-y-3">
            <label class="text-sm font-medium">Select Variant Types & Values</label>
            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-surface-50 dark:bg-surface-800">
                        <tr>
                            <th class="w-48 border-b p-3 text-left font-medium">Variant Type</th>
                            <th class="border-b p-3 text-left font-medium">Values</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="type in variantTypes" :key="type.id" class="border-b last:border-b-0">
                            <td class="p-3 align-top font-medium text-muted-foreground">{{ type.name }}</td>
                            <td class="p-3">
                                <div class="flex flex-wrap gap-2">
                                    <Tag
                                        v-for="value in type.values"
                                        :key="value.id"
                                        :severity="isSelected(type.id, value.id) ? 'info' : 'secondary'"
                                        class="w-max cursor-pointer px-3"
                                        @click="toggleValue(type.id, value.id)"
                                    >
                                        {{ value.value }}
                                    </Tag>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="product">
                <Button label="Generate Variants" :disabled="!hasSelections" :loading="generating" @click="generateVariants" />
                <p class="mt-1 text-xs text-muted-foreground">
                    Only new combinations are created — existing variants are untouched. Not every value needs to be
                    combined; unwanted variants can simply be deleted below after generation.
                </p>
            </div>
            <p v-else class="text-xs text-muted-foreground">Selected combinations will be generated automatically once the product is saved.</p>
        </div>

        <!-- Step 2: generated variants, editable via DataTable row-edit -->
        <div v-if="product && localVariants.length" class="space-y-3 border-t pt-6">
            <label class="text-sm font-medium">Existing Variants</label>

            <DataTable
                v-model:editingRows="editingRows"
                v-model:filters="variantFilters"
                :value="localVariants"
                editMode="row"
                filterDisplay="menu"
                dataKey="id"
                stripedRows
                scrollable
                scrollHeight="400px"
                class="w-full"
                @row-edit-save="onRowEditSave"
            >
                <template #empty> No variants match the selected filters. </template>
                <Column
                    field="valueIds"
                    filterField="valueIds"
                    sortField="sortKey"
                    header="Variant"
                    sortable
                    :showFilterMatchModes="false"
                    :showFilterOperator="false"
                    :showAddButton="false"
                    :filterMenuStyle="{ minWidth: '18rem' }"
                    style="width: 25%"
                    bodyStyle="text-align:left"
                >
                    <template #body="{ data }">
                        <span class="text-muted-foreground">{{ variantLabel(data) }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <MultiSelect
                            v-model="filterModel.value"
                            :options="valueFilterOptions"
                            optionLabel="label"
                            optionValue="id"
                            placeholder="Any value"
                            filter
                            showClear
                            class="w-full"
                        />
                    </template>
                </Column>
                <Column field="sku" header="SKU" style="width: 25%" bodyStyle="text-align:left">
                    <template #body="{ data }">
                        <span class="text-muted-foreground">{{ data.sku }}</span>
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
                    <template #body="{ data }">
                        {{ data.stock }}
                    </template>
                    <template #editor="{ data, field }">
                        <InputNumber v-model="data[field]" fluid />
                    </template>
                </Column>
                <Column :rowEditor="true" style="width: 10%" bodyStyle="text-align:center" />
                <Column field="actions" header="" style="width: 1%" bodyStyle="text-align:center">
                    <template #body="{ data }">
                        <Button
                            severity="danger"
                            variant="text"
                            :loading="savingVariantIds[data.id]"
                            rounded
                            @click="deleteVariant(data)"
                        >
                            <Trash />
                        </Button>
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>