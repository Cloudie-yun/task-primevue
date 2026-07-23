<script setup lang="ts">
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';
import type { Product } from '@/types/product';
import type { VariantType } from '@/types/product-settings';
import ProductVariantTable, { type WorkingVariant } from './ProductVariants/ProductVariantTable.vue';
import ProductVariantTypeSelector from './ProductVariants/ProductVariantTypeSelector.vue';
import { buildSku } from '@/lib/sku';

const props = defineProps<{ product?: Product | null; variantTypes: VariantType[]; productName: string }>();
const emit = defineEmits<{ nameRequired: [] }>();

const selections = defineModel<Record<number, number[]>>('selections', { required: true });
const variants = defineModel<WorkingVariant[]>('variants', { required: true });
const deletedVariantIds = defineModel<number[]>('deletedVariantIds', { default: () => [] });
const variantSelector = ref<{ generationPreview: { total: number; new: number; existing: number } | null; validate: () => boolean }>();
const toast = useToast();
const hasSelections = computed(() => Object.keys(selections.value).length > 0);
const hasNoVariants = computed(() => variants.value.filter((v) => !v.markedForDelete).length === 0);
const tableRef = ref<{ validate: () => boolean }>();
const noProductName = computed(() => !props.productName);

// Seed the working list from persisted variants once per product (e.g. when the form first loads).
watch(
    () => props.product?.id,
    () => {
        variants.value = (props.product?.variants ?? []).map((variant) => ({
            ...variant,
            additional_price: Number(variant.additional_price),
            _key: `v-${variant.id}`,
            markedForDelete: false,
        }));
        deletedVariantIds.value = [];
    },
    { immediate: true },
);

function signature(valueIds: number[]): string {
    return [...valueIds].sort((a, b) => a - b).join(',');
}

function signatureFromValues(values: { id: number }[]): string {
    return signature(values.map((v) => v.id));
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

// Adds/updates rows in the working list to match the current selections.
// - Existing variants whose type-coverage is a subset of the current selections get split across the newly added type's values, carrying forward their stock/price.
// - Any remaining full-dimension combinations not yet present get created fresh.
// Purely local — nothing is persisted until the product form itself is saved.
// function generateVariants() {
//     if (!hasSelections.value) return;
//     if (noProductName.value) {
//         emit('nameRequired');
//         return;
//     }

//     const productName = props.productName;
//     const selectedTypeIds = new Set(
//         Object.entries(selections.value)
//             .filter(([, ids]) => ids.length)
//             .map(([typeId]) => Number(typeId)),
//     );

//     const existingKeys = new Set(
//         variants.value
//             .filter((v) => !v.markedForDelete)
//             .map((variant) => signatureFromValues(variant.values)),
//     );

//     // Pass 1: expand existing variants that only cover *some* of the currently selected types.
//     const toExpand = variants.value.filter((variant) => {
//         if (variant.markedForDelete) return false;
//         const variantTypeIds = new Set(variant.values.map((v) => v.variant_type_id));
//         const isSubset = [...variantTypeIds].every((t) => selectedTypeIds.has(t));
//         return isSubset && variantTypeIds.size < selectedTypeIds.size;
//     });

//     let expanded = 0;
//     const keysToRemove = new Set<string>();
//     const idsToDelete: number[] = [];
//     const newlyAdded: WorkingVariant[] = [];

//     toExpand.forEach((oldVariant) => {
//         const coveredTypeIds = new Set(oldVariant.values.map((v) => v.variant_type_id));
//         const missingTypeIds = [...selectedTypeIds].filter((t) => !coveredTypeIds.has(t));

//         const missingCombos = missingTypeIds
//             .map((typeId) => selections.value[typeId])
//             .reduce<number[][]>((combos, ids) => combos.flatMap((combo) => ids.map((id) => [...combo, id])), [[]]);

//         let didExpand = false;

//         missingCombos.forEach((missingValueIds) => {
//             const missingValues = missingValueIds.map(resolveValue).filter((v): v is NonNullable<typeof v> => v !== null);
//             if (missingValues.length !== missingValueIds.length) return;

//             const combinedValues = [...oldVariant.values, ...missingValues];
//             const key = signatureFromValues(combinedValues);
//             if (existingKeys.has(key)) return;

//             newlyAdded.push({
//                 _key: `new-${crypto.randomUUID()}`,
//                 sku: buildSku(productName, combinedValues),
//                 additional_price: oldVariant.additional_price,
//                 stock: oldVariant.stock,
//                 values: combinedValues,
//                 markedForDelete: false,
//             });
//             existingKeys.add(key);
//             expanded += 1;
//             didExpand = true;
//         });

//         if (!didExpand) return; // nothing new for this row — leave it untouched

//         if (oldVariant.id) {
//             oldVariant.markedForDelete = true;
//             idsToDelete.push(oldVariant.id);
//         } else {
//             keysToRemove.add(oldVariant._key);
//         }
//     });
    
//     if (keysToRemove.size || newlyAdded.length) {
//         variants.value = [
//             ...variants.value.filter((v) => !keysToRemove.has(v._key)),
//             ...newlyAdded,
//         ];
//     }

//     if (idsToDelete.length) {
//         deletedVariantIds.value = [...new Set([...deletedVariantIds.value, ...idsToDelete])];
//     }

//     // Pass 2: generate any remaining full-dimension combinations not yet present.
//     let added = 0;

//     cartesianValueIds().forEach((valueIds) => {
//         const key = signature(valueIds);
//         if (existingKeys.has(key)) return;

//         const values = valueIds.map(resolveValue).filter((v): v is NonNullable<typeof v> => v !== null);
//         if (values.length !== valueIds.length) return;

//         variants.value.push({
//             _key: `new-${crypto.randomUUID()}`,
//             sku: buildSku(productName, values),
//             additional_price: 0,
//             stock: 0,
//             values,
//             markedForDelete: false,
//         });
//         existingKeys.add(key);
//         added += 1;
//     });

//     const totalChanges = expanded + added;
//     let summary = 'No new variants to add';
//     if (expanded && added) summary = `${expanded} variant${expanded > 1 ? 's' : ''} updated, ${added} new`;
//     else if (expanded) summary = `${expanded} variant${expanded > 1 ? 's' : ''} updated`;
//     else if (added) summary = `${added} variant${added > 1 ? 's' : ''} added`;

//     toast.add({ severity: totalChanges ? 'success' : 'info', summary, life: 3000 });
// }

function generateVariants() {
    if (!hasSelections.value) return;
    if (noProductName.value) { emit('nameRequired'); return; }

    const productName = props.productName;
    const selectedTypeIds = new Set(
        Object.entries(selections.value).filter(([, ids]) => ids.length).map(([id]) => Number(id)),
    );
    const targetCombos = cartesianValueIds();
    const targetKeys = new Set(targetCombos.map(signature));

    const active = variants.value.filter((v) => !v.markedForDelete);
    const activeKeys = new Set(active.map((v) => signatureFromValues(v.values)));

    const keysToRemove = new Set<string>();
    const idsToDelete: number[] = [];
    const newlyAdded: WorkingVariant[] = [];
    let expanded = 0;
    let removed = 0;

    active.forEach((variant) => {
        const variantTypeIds = new Set(variant.values.map((v) => v.variant_type_id));
        const key = signatureFromValues(variant.values);
        const isSubset = [...variantTypeIds].every((t) => selectedTypeIds.has(t));
        const coversAllSelected = variantTypeIds.size === selectedTypeIds.size;

        if (isSubset && !coversAllSelected) {
            // Case A: expand this row across the missing type(s), carrying stock/price forward
            const missingTypeIds = [...selectedTypeIds].filter((t) => !variantTypeIds.has(t));
            const missingCombos = missingTypeIds
                .map((t) => selections.value[t])
                .reduce<number[][]>((c, ids) => c.flatMap((combo) => ids.map((id) => [...combo, id])), [[]]);

            let didExpand = false;
            missingCombos.forEach((missingValueIds) => {
                const missingValues = missingValueIds.map(resolveValue).filter((v): v is NonNullable<typeof v> => v !== null);
                if (missingValues.length !== missingValueIds.length) return;
                const combinedValues = [...variant.values, ...missingValues];
                const combinedKey = signatureFromValues(combinedValues);
                if (activeKeys.has(combinedKey) || newlyAdded.some((v) => signatureFromValues(v.values) === combinedKey)) return;

                newlyAdded.push({
                    _key: `new-${crypto.randomUUID()}`,
                    sku: buildSku(productName, combinedValues),
                    additional_price: variant.additional_price,
                    stock: variant.stock,
                    values: combinedValues,
                    markedForDelete: false,
                });
                expanded += 1;
                didExpand = true;
            });

            if (didExpand) {
                if (variant.id) { keysToRemove.add(variant._key); idsToDelete.push(variant.id); }
                else keysToRemove.add(variant._key);
                removed += 1;
            }
        } else if (coversAllSelected && !targetKeys.has(key)) {
            // Case B: same dimensionality, but this exact combo is no longer wanted
            keysToRemove.add(variant._key);
            if (variant.id) idsToDelete.push(variant.id);
            removed += 1;
        } else if (!isSubset) {
            // Case C-orphan: covers a type that's no longer selected at all — drop it,
            // a fresh row gets created below if its remaining dims are still in target
            keysToRemove.add(variant._key);
            if (variant.id) idsToDelete.push(variant.id);
            removed += 1;
        }
    });

    // Anything in target that's still missing after the above -> add fresh
    let added = 0;
    targetCombos.forEach((valueIds) => {
        const key = signature(valueIds);
        const alreadyThere = activeKeys.has(key) && !keysToRemove.has([...active].find((v) => signatureFromValues(v.values) === key)?._key ?? '');
        const alreadyQueued = newlyAdded.some((v) => signatureFromValues(v.values) === key);
        if (alreadyThere || alreadyQueued) return;

        const values = valueIds.map(resolveValue).filter((v): v is NonNullable<typeof v> => v !== null);
        if (values.length !== valueIds.length) return;

        newlyAdded.push({
            _key: `new-${crypto.randomUUID()}`,
            sku: buildSku(productName, values),
            additional_price: 0,
            stock: 0,
            values,
            markedForDelete: false,
        });
        added += 1;
    });

    // Single batched write — avoids the "only last row survives" bug from before
    if (keysToRemove.size || newlyAdded.length) {
        variants.value = [
            ...variants.value.map((v) => (v.id && idsToDelete.includes(v.id) ? { ...v, markedForDelete: true } : v)),
        ].filter((v) => !(keysToRemove.has(v._key) && !v.id)).concat(newlyAdded);
    }
    if (idsToDelete.length) {
        deletedVariantIds.value = [...new Set([...deletedVariantIds.value, ...idsToDelete])];
    }

    const summaryParts = [];
    if (added) summaryParts.push(`${added} added`);
    if (expanded) summaryParts.push(`${expanded} split`);
    if (removed - expanded) summaryParts.push(`${removed - expanded} removed`);
    toast.add({ severity: summaryParts.length ? 'success' : 'info', summary: summaryParts.join(', ') || 'No changes', life: 3000 });
}

// Delegated to by ProductForm before it submits.
function validate(): boolean {
    const selectorValid = variantSelector.value?.validate() ?? true;
    const rowsValid = tableRef.value?.validate() ?? true;
    
    if (!selectorValid) return false; // stop here so focus lands on the type selector, not the table
    if (hasNoVariants.value) return false;
    return rowsValid;
}

defineExpose({ validate, hasNoVariants, noProductName });
</script>

<template>
    <div class="space-y-8 py-4">
        <div class="space-y-3">
            <ProductVariantTypeSelector ref="variantSelector" v-model:selections="selections" :variant-types="variantTypes" :variants="variants" />
            <div class="flex justify-end">
                <div class="flex flex-col items-end">
                    <Button label="Generate Variants" :disabled="!hasSelections" @click="generateVariants" class="w-max!"/>
                    <p v-if="variantSelector?.generationPreview" class="mt-1 text-xs text-muted-foreground text-right" >
                        {{ variantSelector.generationPreview.total }}
                        combination{{ variantSelector.generationPreview.total === 1 ? '' : 's' }}
                        selected —
                        {{ variantSelector.generationPreview.new }} new
                        <span v-if="variantSelector.generationPreview.existing">
                            · {{ variantSelector.generationPreview.existing }} already exist
                        </span>
                    </p>
                </div>
            </div>
        </div>
        
        <div v-if="variants.length" class="space-y-3 border-t pt-6">
            <label class="text-sm font-medium">Variants</label>
            <ProductVariantTable
                ref="tableRef"
                v-model:variants="variants"
                v-model:deleted-ids="deletedVariantIds"
                :variant-types="variantTypes"
                :scroll-height="300"
            />
        </div>
    </div>
</template>