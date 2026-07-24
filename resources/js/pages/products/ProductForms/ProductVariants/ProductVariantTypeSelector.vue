<script setup lang="ts">
import { Plus, PlusCircle, MinusCircle } from '@primeicons/vue';
import { computed, nextTick, ref, watch } from 'vue';
import type { VariantType } from '@/types/product-settings';
import CreateVariantTypeDialog from '@/pages/productSettings/VariantCreate.vue';
import EditVariantTypeDialog from '@/pages/productSettings/VariantEdit.vue';

const props = defineProps<{ variantTypes: VariantType[] }>();
const variantSelections = defineModel<Record<number, number[]>>('variantSelections', { required: true });
const attributeValueIds = defineModel<number[]>('attributeValueIds', { default: () => [] });

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

// Hint showing what the generated SKU pattern will look like, based on
// whichever combination-type rows currently have a type picked, in row order.
const skuPattern = computed(() => {
    const names = rows.value
        .map((row) => typeById(row.typeId))
        .filter((type): type is VariantType => !!type && type.generates_variants)
        .map((type) => type.name);

    return names.length ? names.join(' - ') : null;
});

// Dialogs — create type
const createTypeDialogVisible = ref(false);
const pendingTypeRow = ref<Row | null>(null);
const pendingTypeName = ref<string | null>(null);

// Dialogs — create value
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

function onTypeCreated(name: string) {
    pendingTypeName.value = name;
}

watch(
    () => props.variantTypes,
    (types) => {
        // Handle newly created type
        if (pendingTypeName.value && pendingTypeRow.value) {
            const type = types.find(
                t => t.name.toLowerCase() === pendingTypeName.value!.toLowerCase()
            );

            if (type) {
                pendingTypeRow.value.typeId = Number(type.id);
                onTypeChange(pendingTypeRow.value);
            }

            pendingTypeName.value = null;
            pendingTypeRow.value = null;
        }

        // Handle newly created value
        if (pendingNewValue.value && pendingValueRow.value) {
            const type = types.find(
                t => Number(t.id) === pendingValueRow.value!.typeId
            );

            const value = type?.values?.find(
                v => v.value.toLowerCase() === pendingNewValue.value!.toLowerCase()
            );

            if (value) {
                const id = Number(value.id);

                if (!pendingValueRow.value.valueIds.includes(id)) {
                    pendingValueRow.value.valueIds.push(id);
                }
            }

            pendingNewValue.value = null;
            pendingValueRow.value = null;
        }
    },
    { deep: true }
);

function makeRow(): Row {
    return { key: crypto.randomUUID(), typeId: null, valueIds: [] };
}

function buildInitialRows(): Row[] {
    const fromSelections = Object.entries(variantSelections.value)
        .filter(([, ids]) => ids.length > 0)
        .map(([typeId, ids]) => ({ key: crypto.randomUUID(), typeId: Number(typeId), valueIds: [...ids] }));

    if (attributeValueIds.value.length) {
        const byType = new Map<number, number[]>();
        props.variantTypes
            .filter((t) => !t.generates_variants)
            .forEach((type) => {
                const matched = (type.values ?? [])
                    .filter((v) => attributeValueIds.value.includes(Number(v.id)))
                    .map((v) => Number(v.id));
                if (matched.length) byType.set(Number(type.id), matched);
            });

        byType.forEach((valueIds, typeId) => {
            fromSelections.push({ key: crypto.randomUUID(), typeId, valueIds });
        });
    }
    return fromSelections.length ? fromSelections : [makeRow()];
}

const rows = ref<Row[]>(buildInitialRows());


// Split rows into `selections` (generating types → cartesian generation on the backend)
// and `attributeValueIds` (descriptive types → flat list of ids) on every change.
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

        variantSelections.value = nextSelections;
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

    if (invalidRowKeys.value.size === 0) {
        return true;
    }

    const firstInvalidKey = rows.value.find((r) => invalidRowKeys.value.has(r.key))?.key;
    if (firstInvalidKey) {
        nextTick(() => {
            document.querySelector(`[data-variant-type-row="${firstInvalidKey}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    return false;
}

defineExpose({ validate });

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
                            <FloatLabel variant="on">
                                <Select
                                    v-model="row.typeId"
                                    :options="availableTypesFor(row)"
                                    optionLabel="name"
                                    optionValue="id"
                                    :invalid="attemptedSubmit && !!rowErrors(row)"
                                    class="w-full"
                                    @update:modelValue="onTypeChange(row)"
                                >
                                    <template #option="{ option }">
                                        <div class="flex w-full items-center justify-between">
                                            <span>{{ option.name }}</span>
                                            <Tag v-if="!option.generates_variants" severity="secondary" class="text-[10px]">tag only</Tag>
                                            <Tag v-else severity="primary" class="text-[10px]">sku</Tag>
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
                                <Label :for="row.typeId">Variant Type</Label>
                            </FloatLabel>
                        </td>
                        <td class="p-3 align-top">
                            <FloatLabel variant="on">
                                <MultiSelect
                                    v-model="row.valueIds"
                                    :options="valuesFor(row)"
                                    optionLabel="value"
                                    optionValue="id"
                                    :disabled="row.typeId === null"
                                    :invalid="attemptedSubmit && !!rowErrors(row)"
                                    filter
                                    class="w-full"
                                    @filter="onFilterChange(row, $event)"
                                >
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
                                <Label :for="row.valueIds">Values</Label>
                            </FloatLabel>
                            <p v-if="attemptedSubmit && rowErrors(row)" class="mt-1 text-xs text-red-600 dark:text-red-400">
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
        <div class="flex justify-between">
            <div class="flex-1">
                <Button label="Add Variant Type" severity="secondary" variant="text" size="small" :disabled="!canAddRow" @click="addRow">
                    <template #icon><Plus /></template>
                </Button>
            </div>

            <div class="text-right! justify-end!">
                <Message severity="error" v-if="invalidRowKeys.size > 0" size="small" variant="simple">
                    Please fix invalid variant rows before submitting.
                </Message>
                <Message severity="error" v-if="attemptedSubmit && invalidRowKeys.size > 0" size="small" variant="simple">
                    Please select at least one variant type (sku) and value.
                </Message>
            </div>
        </div>
    </div>
    <CreateVariantTypeDialog v-model:visible="createTypeDialogVisible" @created="onTypeCreated" />
    <EditVariantTypeDialog
        v-model:visible="editTypeDialogVisible"
        :variant-type="editTypeTarget"
        :prefill-value="pendingNewValue"
    />
</template>