<!-- ProductVariantTypeSelector.vue -->
<script setup lang="ts">
import { Trash, Plus, PlusCircle, MinusCircle } from '@primeicons/vue';
import { computed, ref, watch } from 'vue';
import type { VariantType } from '@/types/product-settings';
import type { WorkingVariant } from './ProductVariantTable.vue'; 
import CreateVariantTypeDialog from '@/pages/productSettings/VariantCreate.vue';
import EditVariantTypeDialog from '@/pages/productSettings/VariantEdit.vue';

const props = defineProps<{ variantTypes: VariantType[]; variants: WorkingVariant[] }>();
const emit = defineEmits<{ typesUpdated: [] }>();
const selections = defineModel<Record<number, number[]>>('selections', { required: true });

const VALUE_LABEL_CUTOFF = 5;

// Dialogs Related Declarations
// Create Type
const createTypeDialogVisible = ref(false);
const pendingTypeRow = ref<Row | null>(null);
const pendingTypeName = ref<string | null>(null);

// Edit Value
const editTypeDialogVisible = ref(false);
const editTypeTarget = ref<VariantType | null>(null);
const pendingNewValue = ref<string | null>(null);
const pendingValueRow = ref<Row | null>(null);

interface Row {
    key: string;
    typeId: number | null;
    valueIds: number[];
}

function openCreateTypeDialog(row: Row) {
    pendingTypeRow.value = row;
    createTypeDialogVisible.value = true;
}

//Edit
function promptCreateValue(row: Row, searchTerm: string) {
    const trimmed = searchTerm.trim();
    if (!trimmed) return;

    const type = props.variantTypes.find((t) => Number(t.id) === row.typeId);
    if (!type) return;

    editTypeTarget.value = type;
    pendingNewValue.value = trimmed;
    pendingValueRow.value = row;
    editTypeDialogVisible.value = true;
}

function onTypeUpdated() {
    emit('typesUpdated'); // parent reloads variantTypes prop
}

function onTypeCreated(name: string) {
    pendingTypeName.value = name;
    emit('typesUpdated'); // parent reloads variantTypes prop
}

watch(
    () => props.variantTypes,
    (types) => {
        if (!pendingNewValue.value || !pendingValueRow.value) return;

        const type = types.find((t) => Number(t.id) === pendingValueRow.value!.typeId);
        const match = type?.values?.find((v) => v.value.toLowerCase() === pendingNewValue.value!.toLowerCase());

        if (match && !pendingValueRow.value.valueIds.includes(Number(match.id))) {
            pendingValueRow.value.valueIds.push(Number(match.id));
        }

        pendingNewValue.value = null;
        pendingValueRow.value = null;
    },
);
watch(
    () => props.variantTypes,
    (types) => {
        if (!pendingTypeName.value || !pendingTypeRow.value) return;

        const match = types.find((t) => t.name.toLowerCase() === pendingTypeName.value!.toLowerCase());
        if (match) {
            pendingTypeRow.value.typeId = Number(match.id);
            onTypeChange(pendingTypeRow.value);
        }

        pendingTypeName.value = null;
        pendingTypeRow.value = null;
    },
);

let hasSeeded = false;

watch(
    () => props.variants,
    (currentVariants) => {
        if (hasSeeded || currentVariants.length === 0) return;
        hasSeeded = true;

        const grouped = new Map<number, Set<number>>();
        currentVariants.forEach((variant) => {
            if (variant.markedForDelete) return;
            variant.values.forEach((val) => {
                if (!grouped.has(val.variant_type_id)) grouped.set(val.variant_type_id, new Set());
                grouped.get(val.variant_type_id)!.add(val.id);
            });
        });

        if (grouped.size === 0) return;

        rows.value = Array.from(grouped.entries()).map(([typeId, valueIds]) => ({
            key: crypto.randomUUID(),
            typeId,
            valueIds: Array.from(valueIds),
        }));
    },
    { immediate: true },
);

//Show used value IDs
const usedValueIds = computed(() => {
    const ids = new Set<number>();
    props.variants.forEach((variant) => {
        if (variant.markedForDelete) return;
        variant.values.forEach((v) => ids.add(v.id));
    });
    return ids;
});

const existingCombinationKeys = computed(() => {
    const keys = new Set<string>();
    props.variants.forEach((variant) => {
        if (variant.markedForDelete) return;
        const key = variant.values.map((v) => v.id).sort((a, b) => a - b).join(',');
        keys.add(key);
    });
    return keys;
});

function cartesianForPreview(): number[][] {
    const groups = Object.values(selections.value).filter((ids) => ids.length);
    if (!groups.length) return [];
    return groups.reduce<number[][]>((combos, ids) => combos.flatMap((combo) => ids.map((id) => [...combo, id])), [[]]);
}

const generationPreview = computed(() => {
    const combos = cartesianForPreview();
    if (!combos.length) return null;

    const newCount = combos.filter((c) => !existingCombinationKeys.value.has([...c].sort((a, b) => a - b).join(','))).length;

    return { total: combos.length, new: newCount, existing: combos.length - newCount };
});

defineExpose({ generationPreview });

//Rows
function makeRow(): Row {
    return { key: crypto.randomUUID(), typeId: null, valueIds: [] };
}

const rows = ref<Row[]>([makeRow()]);

// Keep the exposed `selections` map in sync with the rows whenever either changes.
watch(
    rows,
    () => {
        const next: Record<number, number[]> = {};
        rows.value.forEach((row) => {
            if (row.typeId !== null && row.valueIds.length) {
                next[row.typeId] = [...row.valueIds];
            }
        });
        selections.value = next;
    },
    { deep: true, immediate: true },
);

function usedTypeIds(excludingKey: string) {
    return rows.value.filter((row) => row.key !== excludingKey && row.typeId !== null).map((row) => row.typeId as number);
}

// Each row can only pick a type not already claimed by another row —
// but still shows its own current selection even though that type is "in use".
function availableTypesFor(row: Row) {
    const used = new Set(usedTypeIds(row.key));
    return props.variantTypes.filter((type) => !used.has(Number(type.id)));
}

function valuesFor(row: Row) {
    return props.variantTypes.find((type) => Number(type.id) === row.typeId)?.values ?? [];
}

const filterTextByRow = ref<Record<string, string>>({});

function onFilterChange(row: Row, event: { value: string }) {
    filterTextByRow.value[row.key] = event.value;
}

function onTypeChange(row: Row) {
    row.valueIds = []; // stale values from a previous type no longer make sense
}

function selectedValueLabel(row: Row) {
    const values = valuesFor(row).filter((v) => row.valueIds.includes(Number(v.id)));
    if (!values.length) return '';

    const shown = values.slice(0, VALUE_LABEL_CUTOFF).map((v) => v.value);
    const remaining = values.length - shown.length;

    return remaining > 0 ? `${shown.join(', ')} + ${remaining} more` : shown.join(', ');
}

// Add and Remove Rows
function addRow() {
    rows.value.push(makeRow());
}

function removeRow(row: Row) {
    if (rows.value.length === 1) {
        rows.value[0] = makeRow(); // always keep at least one row visible
        return;
    }
    rows.value = rows.value.filter((r) => r.key !== row.key);
}

const canAddRow = computed(() => rows.value.length < props.variantTypes.length);
</script>

<template>
    <div class="space-y-3">
        <div class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-surface-50 dark:bg-surface-800">
                    <tr>
                        <th class="w-56 border-b p-3 text-left font-medium">Variant Type</th>
                        <th class="border-b p-3 text-left font-medium">Values</th>
                        <th class="w-12 border-b p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.key" class="border-b last:border-b-0">
                        <td class="p-3 align-top">
                            <Select
                                v-model="row.typeId"
                                :options="availableTypesFor(row)"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select type"
                                class="w-full"
                                @update:modelValue="onTypeChange(row)"
                            >
                                <template #footer>
                                    <div class="border-t p-2">
                                        <Button text size="small" class="w-full justify-start!" @click="openCreateTypeDialog(row)" ><PlusCircle />Add New Type</Button>
                                    </div>
                                </template>
                            </Select>
                        </td>
                        <td class="p-3 align-top">
                            <MultiSelect
                                v-model="row.valueIds"
                                :options="valuesFor(row)"
                                optionLabel="value"
                                optionValue="id"
                                :disabled="row.typeId === null"
                                placeholder="Select values"
                                filter
                                class="w-full"
                                @filter="onFilterChange(row, $event)"
                            >
                                <template #option="{ option }">
                                    <div class="flex w-full items-center justify-between">
                                        <span>{{ option.value }}</span>
                                        <span
                                            v-if="usedValueIds.has(Number(option.id))"
                                            class="ml-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"
                                            v-tooltip="'Used in an existing variant'"
                                        />
                                    </div>
                                </template>
                                <template #value>
                                    <span v-if="!row.valueIds.length" class="text-muted-foreground">Select values</span>
                                    <span v-else class="truncate">{{ selectedValueLabel(row) }}</span>
                                </template>
                                <template #emptyfilter>
                                    <div class="p-2">
                                        <Button
                                            :label="`Create \u201c${filterTextByRow[row.key] ?? ''}\u201d`"
                                            text
                                            size="small"
                                            class="w-full justify-start"
                                            @click="promptCreateValue(row, filterTextByRow[row.key] ?? '')"
                                        />
                                    </div>
                                </template>
                            </MultiSelect>
                        </td>
                        <td class="p-3 text-right align-top">
                            <Button severity="danger" variant="text" rounded @click="removeRow(row)">
                                <MinusCircle />
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Button label="Add Variant Type" severity="secondary" variant="text" size="small" :disabled="!canAddRow" @click="addRow">
            <template #icon><Plus /></template>
        </Button>
    </div>
    <CreateVariantTypeDialog v-model:visible="createTypeDialogVisible" @created="onTypeCreated" />
    <EditVariantTypeDialog
        v-model:visible="editTypeDialogVisible"
        :variant-type="editTypeTarget"
        :prefill-value="pendingNewValue"
        @updated="onTypeUpdated"
    />
</template>