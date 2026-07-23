<!-- ProductVariantTypeSelector.vue -->
<script setup lang="ts">
import { Trash, Plus, PlusCircle, MinusCircle } from '@primeicons/vue';
import { computed, nextTick, ref, watch } from 'vue';
import type { VariantType } from '@/types/product-settings';
import type { WorkingVariant } from './ProductVariantTable.vue';
import CreateVariantTypeDialog from '@/pages/productSettings/VariantCreate.vue';
import EditVariantTypeDialog from '@/pages/productSettings/VariantEdit.vue';

const props = defineProps<{ variantTypes: VariantType[]; variants: WorkingVariant[] }>();
const selections = defineModel<Record<number, number[]>>('selections', { required: true });
const attributeValueIds = defineModel<number[]>('attributeValueIds', { default: () => [] });
const variantsTabRef = ref<{noProductName: boolean}>();
const emit = defineEmits<{ typesUpdated: [] }>();

const VALUE_LABEL_CUTOFF = 5;

const attemptedSubmit = ref(false);

interface Row {
    key: string;
    typeId: number | null;
    valueIds: number[];
}

function typeById(typeId: number | null) {
    return props.variantTypes.find((t) => Number(t.id) === typeId) ?? null;
}

// Live preview of what the generated SKU pattern will look like, based on
// whichever combination-type rows currently have a type picked, in row order.
const skuPattern = computed(() => {
    const names = rows.value
        .map((row) => typeById(row.typeId))
        .filter((type): type is VariantType => !!type && type.generates_variants)
        .map((type) => type.name);

    return names.length ? names.join(' - ') : null;
});

// Dialogs
const createTypeDialogVisible = ref(false);
const pendingTypeRow = ref<Row | null>(null);
const pendingTypeName = ref<string | null>(null);

const editTypeDialogVisible = ref(false);
const editTypeTarget = ref<VariantType | null>(null);
const pendingNewValue = ref<string | null>(null);
const pendingValueRow = ref<Row | null>(null);

function openCreateTypeDialog(row: Row) {
    pendingTypeRow.value = row;
    createTypeDialogVisible.value = true;
}

function promptCreateValue(row: Row, searchTerm: string) {
    const trimmed = searchTerm.trim();
    if (!trimmed) return;

    const type = typeById(row.typeId);
    if (!type) return;

    editTypeTarget.value = type;
    pendingNewValue.value = trimmed;
    pendingValueRow.value = row;
    editTypeDialogVisible.value = true;
}

function onTypeUpdated() {
    emit('typesUpdated');
}

function onTypeCreated(name: string) {
    pendingTypeName.value = name;
    emit('typesUpdated');
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

// Seed rows from existing variants once (edit mode) — combination-type values only,
// since attribute values never live on `variants`.
let hasSeeded = false;

watch(
    () => props.variants,
    (currentVariants) => {
        const active = currentVariants.filter((v) => !v.markedForDelete);
        const usedByType = new Map<number, Set<number>>();
        active.forEach((v) => v.values.forEach((val) => {
            if (!usedByType.has(val.variant_type_id)) usedByType.set(val.variant_type_id, new Set());
            usedByType.get(val.variant_type_id)!.add(val.id);
        }));

        rows.value.forEach((row) => {
            if (row.typeId === null) return;
            const type = typeById(row.typeId);
            if (!type?.generates_variants) return; // don't touch descriptive/attribute rows
            const stillUsed = usedByType.get(row.typeId) ?? new Set();
            row.valueIds = row.valueIds.filter((id) => stillUsed.has(id));
        });
    },
    { deep: true }, // NOT immediate — the existing hasSeeded watcher owns initial seeding
);

// Seed a row for each descriptive type that already has attribute values selected (edit mode).
let hasSeededAttributes = false;

watch(
    () => attributeValueIds.value,
    (ids) => {
        if (hasSeededAttributes || ids.length === 0) return;
        hasSeededAttributes = true;

        const byType = new Map<number, number[]>();
        props.variantTypes
            .filter((t) => !t.generates_variants)
            .forEach((type) => {
                const matched = (type.values ?? []).filter((v) => ids.includes(Number(v.id))).map((v) => Number(v.id));
                if (matched.length) byType.set(Number(type.id), matched);
            });

        if (byType.size === 0) return;

        const newRows = Array.from(byType.entries()).map(([typeId, valueIds]) => ({
            key: crypto.randomUUID(),
            typeId,
            valueIds,
        }));

        // if the only row so far is the default empty one, replace it; otherwise append
        if (rows.value.length === 1 && rows.value[0].typeId === null) {
            rows.value = newRows;
        } else {
            rows.value = [...rows.value, ...newRows];
        }
    },
    { immediate: true },
);

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

function makeRow(): Row {
    return { key: crypto.randomUUID(), typeId: null, valueIds: [] };
}

const rows = ref<Row[]>([makeRow()]);

// Split rows into `selections` (combination types → feeds cartesian generation)
// and `attributeValueIds` (descriptive types → flat list of tag ids) on every change.
watch(
    rows,
    () => {
        const nextSelections: Record<number, number[]> = {};
        const nextAttributeIds: number[] = [];

        rows.value.forEach((row) => {
            if (row.typeId === null || !row.valueIds.length) return;

            const type = typeById(row.typeId);
            if (!type) return;

            if (type.generates_variants) {
                nextSelections[row.typeId] = [...row.valueIds];
            } else {
                nextAttributeIds.push(...row.valueIds);
            }
        });

        selections.value = nextSelections;
        attributeValueIds.value = nextAttributeIds;
    },
    { deep: true, immediate: true },
);

function usedTypeIds(excludingKey: string) {
    return rows.value.filter((row) => row.key !== excludingKey && row.typeId !== null).map((row) => row.typeId as number);
}

function availableTypesFor(row: Row) {
    const used = new Set(usedTypeIds(row.key));
    return props.variantTypes.filter((type) => !used.has(Number(type.id)));
}

function valuesFor(row: Row) {
    return typeById(row.typeId)?.values ?? [];
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

function addRow() {
    rows.value.push(makeRow());
}

function removeRow(row: Row) {
    if (rows.value.length === 1) {
        rows.value[0] = makeRow();
        return;
    }
    rows.value = rows.value.filter((r) => r.key !== row.key);
}

function rowErrors(row: Row): string | null {
    if (row.typeId === null) return null; // an empty, unused row is fine — just ignore it
    if (row.valueIds.length === 0) return 'Select at least one value';
    return null;
}

const invalidRowKeys = computed(() => {
    const keys = new Set<string>();
    rows.value.forEach((row) => {
        if (rowErrors(row)) keys.add(row.key);
    });
    return keys;
});

function validate(): boolean {
    attemptedSubmit.value = true;

    if (invalidRowKeys.value.size === 0) return true;

    const firstInvalidKey = rows.value.find((r) => invalidRowKeys.value.has(r.key))?.key;
    if (firstInvalidKey) {
        nextTick(() => {
            document.querySelector(`[data-variant-type-row="${firstInvalidKey}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    return false;
}

defineExpose({ generationPreview, validate });

const canAddRow = computed(() => rows.value.length < props.variantTypes.length);
</script>

<template>
    <div class="space-y-3">
        <div v-if="skuPattern" class="rounded-md bg-surface-50 px-3 py-2 text-xs text-muted-foreground dark:bg-surface-800">
            SKU naming pattern: <span class="font-medium text-foreground">{{ skuPattern }}</span>
        </div>

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
                    <tr v-for="row in rows" :key="row.key" class="border-b last:border-b-0" :data-variant-type-row="row.key">
                        <td class="p-3 align-top">
                            <Select
                                v-model="row.typeId"
                                :options="availableTypesFor(row)"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select type"
                                :invalid="attemptedSubmit || !!rowErrors(row) || variantsTabRef?.noProductName"
                                class="w-full"
                                @update:modelValue="onTypeChange(row)"
                            >
                                <template #option="{ option }">
                                    <div class="flex w-full items-center justify-between">
                                        <span>{{ option.name }}</span>
                                        <Tag v-if="!option.generates_variants" severity="secondary" class="text-[10px]">tag only</Tag>
                                    </div>
                                </template>
                                <template #footer>
                                    <div class="border-t p-2">
                                        <Button text size="small" class="w-full justify-start!" @click="openCreateTypeDialog(row)">
                                            <PlusCircle />Add New Type
                                        </Button>
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
                                :invalid="attemptedSubmit || !!rowErrors(row) || variantsTabRef?.noProductName"
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
                            <p v-if="rowErrors(row)" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                {{ rowErrors(row) }}
                            </p>
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
    <!-- <p v-if="attemptedSubmit" class="mt-1 text-xs text-red-600 dark:text-red-400">
        Please select at least one variant type and value.
    </p> -->
    <p v-if="attemptedSubmit && invalidRowKeys.size > 0" class="mt-1 text-xs text-red-600 dark:text-red-400">
        Please select at least one variant type and value.
    </p>
    <CreateVariantTypeDialog v-model:visible="createTypeDialogVisible" @created="onTypeCreated" />
    <EditVariantTypeDialog
        v-model:visible="editTypeDialogVisible"
        :variant-type="editTypeTarget"
        :prefill-value="pendingNewValue"
        @updated="onTypeUpdated"
    />
</template>