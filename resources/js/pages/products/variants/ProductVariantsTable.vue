<script setup lang="ts">
import { computed, ref, watch } from 'vue';

export interface VariantValueLite { id: number; value: string; variant_type_id: number; sort_order: number; type?: { id: number; name: string } }
export interface VariantRow {
    id: number;
    product_id: number;
    product?: { id: number; name: string };
    sku: string;
    additional_price: string;
    stock: number;
    values: VariantValueLite[];
}

const props = defineProps<{
    variants: VariantRow[];
    isScoped: boolean;
    workingLookup: (id: number) => { additional_price: number; stock: number; markedForDelete: boolean };
    selectedId: number | null;
    attemptedSubmit: boolean;
    invalidIds: Set<number>;
    dirtyIds: Set<number>;
}>();

const emit = defineEmits<{ select: [id: number] }>();

// --- Search ---
const search = ref('');
const filteredVariants = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return props.variants;
    return props.variants.filter((v) =>
        v.sku.toLowerCase().includes(term) ||
        v.product?.name.toLowerCase().includes(term) ||
        v.values.some((val) => val.value.toLowerCase().includes(term))
    );
});

// --- Grouping: by variant type when scoped to one product, by product otherwise ---
const groupableTypes = computed<{ id: number; name: string }[]>(() => {
    if (!props.isScoped) return [];
    const map = new Map<number, string>();
    props.variants.forEach((v) =>
        v.values.forEach((val) => {
            if (val.type && !map.has(val.variant_type_id)) map.set(val.variant_type_id, val.type.name);
        })
    );
    return Array.from(map, ([id, name]) => ({ id, name }));
});

const groupByTypeId = ref<number | null>(null);

watch(
    groupableTypes,
    (types) => { if (groupByTypeId.value === null && types.length) groupByTypeId.value = types[0].id; },
    { immediate: true }
);

function valueForType(variant: VariantRow, typeId: number | null): { label: string; order: number } {
    if (typeId === null) return { label: '', order: 0 };
    const match = variant.values.find((v) => v.variant_type_id === typeId);
    return match ? { label: match.value, order: match.sort_order } : { label: '—', order: Number.MAX_SAFE_INTEGER };
}

const groupedVariants = computed(() => {
    if (props.isScoped) {
        return [...filteredVariants.value]
            .map((v) => {
                const { label, order } = valueForType(v, groupByTypeId.value);
                return { ...v, _groupValue: label, _groupOrder: order };
            })
            .sort((a, b) => a._groupOrder - b._groupOrder);
    }

    // Unscoped: group by product, sorted by product name
    return [...filteredVariants.value]
        .map((v) => ({ ...v, _groupValue: v.product?.name ?? '—', _groupOrder: 0 }))
        .sort((a, b) => a._groupValue.localeCompare(b._groupValue));
});

const groupSortField = computed(() => (props.isScoped ? '_groupOrder' : '_groupValue'))

const groupHeaderLabel = computed(() =>
    props.isScoped
        ? groupableTypes.value.find((t) => t.id === groupByTypeId.value)?.name ?? ''
        : 'Product'
);

function variantLabel(variant: VariantRow) {
    return variant.values.map((v) => v.value).join(' / ');
}

function rowClass(data: VariantRow) {
    const working = props.workingLookup(data.id);
    if (working.markedForDelete) return 'opacity-50';
    if (props.attemptedSubmit && props.invalidIds.has(data.id)) return 'bg-red-50! dark:bg-red-950/30!';
    if (props.selectedId === data.id) return 'bg-primary-50! dark:bg-primary-950/30!';
    if (props.dirtyIds.has(data.id)) return 'bg-amber-50! dark:bg-amber-950/30!';
    return '';
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <InputText v-model="search" placeholder="Search SKU, product, or value..." class="w-72" />
        <Select
            v-if="isScoped && groupableTypes.length"
            v-model="groupByTypeId"
            :options="groupableTypes"
            optionLabel="name"
            optionValue="id"
            :disabled="groupableTypes.length === 1"
            class="w-48"
        >
            <template #value="{ value }">
                Group by: {{ groupableTypes.find((t) => t.id === value)?.name ?? '—' }}
            </template>
        </Select>
    </div>

    <DataTable
        :value="groupedVariants"
        rowGroupMode="subheader"
        groupRowsBy="_groupValue"
        sortMode="single"
        :sortField="groupSortField"
        :sortOrder="1"
        dataKey="id"
        scrollable
        scrollHeight="500px"
        :rowClass="rowClass"
        @row-click="(e) => emit('select', e.data.id)"
        :pt="{ bodyRow: { class: 'align-middle h-16 cursor-pointer' } }"
    >
        <template #groupheader="{ data }">
            <span class="font-semibold">{{ groupHeaderLabel }}: {{ data._groupValue }}</span>
        </template>

        <!-- <Column v-if="!isScoped" header="Product">
            <template #body="{ data }">{{ data.product?.name }}</template>
        </Column> -->
        <Column header="Variant">
            <template #body="{ data }">
                <span :class="workingLookup(data.id).markedForDelete ? 'line-through text-muted-foreground' : ''">
                    {{ variantLabel(data) }}
                </span>
            </template>
        </Column>
        <Column field="sku" header="SKU" sortable>
            <template #body="{ data }"><span class="text-muted-foreground">{{ data.sku }}</span></template>
        </Column>
        <Column header="Additional Price">
            <template #body="{ data }">RM {{ Number(workingLookup(data.id).additional_price).toFixed(2) }}</template>
        </Column>
        <Column field="stock" header="Stock" sortable>
            <template #body="{ data }">{{ workingLookup(data.id).stock }}</template>
        </Column>

    </DataTable>
</template>