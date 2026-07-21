<script setup lang="ts">
import { useToast } from 'primevue/usetoast';
import { computed, watch } from 'vue';
import type { Product } from '@/types/product';
import type { VariantType } from '@/types/product-settings';
import ProductVariantTable, { type WorkingVariant } from './ProductVariantTable.vue';

const props = defineProps<{ product?: Product | null; variantTypes: VariantType[] }>();

const selections = defineModel<Record<number, number[]>>('selections', { required: true });
const variants = defineModel<WorkingVariant[]>('variants', { required: true });
const deletedVariantIds = defineModel<number[]>('deletedVariantIds', { default: () => [] });

const toast = useToast();
const hasSelections = computed(() => Object.keys(selections.value).length > 0);

// Seed the working list from persisted variants once per product (e.g. when the form first loads).
watch(
    () => props.product?.id,
    () => {
        variants.value = (props.product?.variants ?? []).map((variant) => ({
            ...variant,
            additional_price: Number(variant.additional_price),
            _key: `v-${variant.id}`,
        }));
        deletedVariantIds.value = [];
    },
    { immediate: true },
);

function isSelected(typeId: number, valueId: number) {
    return selections.value[typeId]?.includes(valueId) ?? false;
}

function toggleValue(typeId: number, valueId: number) {
    const current = selections.value[typeId] ?? [];
    selections.value[typeId] = current.includes(valueId) ? current.filter((id) => id !== valueId) : [...current, valueId];
    if (selections.value[typeId].length === 0) delete selections.value[typeId];
}

function cartesianValueIds(): number[][] {
    const groups = Object.values(selections.value).filter((ids) => ids.length);
    if (!groups.length) return [];
    return groups.reduce<number[][]>((combos, ids) => combos.flatMap((combo) => ids.map((id) => [...combo, id])), [[]]);
}

function resolveValue(valueId: number) {
    for (const type of props.variantTypes) {
        const match = type.values?.find((value) => Number(value.id) === valueId);
        if (match) return { id: Number(match.id), value: match.value, variant_type_id: Number(type.id) };
    }
    return null;
}

// Adds new rows to the working list for value combinations not already present.
// Purely local — nothing is persisted until the product form itself is saved.
function generateVariants() {
    if (!hasSelections.value) return;

    const existingKeys = new Set(
        variants.value.map((variant) => variant.values.map((v) => v.id).sort((a, b) => a - b).join(',')),
    );

    let added = 0;
    cartesianValueIds().forEach((valueIds) => {
        const key = [...valueIds].sort((a, b) => a - b).join(',');
        if (existingKeys.has(key)) return;

        const values = valueIds.map(resolveValue).filter((v): v is NonNullable<typeof v> => v !== null);
        if (values.length !== valueIds.length) return;

        variants.value.push({ _key: `new-${crypto.randomUUID()}`, sku: '', additional_price: 0, stock: 0, values });
        existingKeys.add(key);
        added += 1;
    });

    toast.add({
        severity: added ? 'success' : 'info',
        summary: added ? `${added} variant${added > 1 ? 's' : ''} added` : 'No new variants to add',
        life: 3000,
    });
}
</script>

<template>
    <div class="space-y-8 py-4">
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

            <div>
                <Button label="Add Variants" :disabled="!hasSelections" @click="generateVariants" />
                <p class="mt-1 text-xs text-muted-foreground">Adds rows below for editing. Nothing is saved until you save the product.</p>
            </div>
        </div>

        <div v-if="variants.length" class="space-y-3 border-t pt-6">
            <label class="text-sm font-medium">Variants</label>
            <ProductVariantTable
                v-model:variants="variants"
                v-model:deleted-ids="deletedVariantIds"
                :variant-types="variantTypes"
                :scroll-height="300"
            />
        </div>
    </div>
</template>