<script setup lang="ts">
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';
import type { Product } from '@/types/product';
import type { VariantType } from '@/types/product-settings';
import ProductVariantTable, { type WorkingVariant } from './ProductVariants/ProductVariantTable.vue';
import ProductVariantTypeSelector from './ProductVariants/ProductVariantTypeSelector.vue';
import { buildSku } from '@/lib/sku';

const props = defineProps<{ product?: Product | null; variantTypes: VariantType[]; productName: string }>();

const selections = defineModel<Record<number, number[]>>('selections', { required: true });
const variants = defineModel<WorkingVariant[]>('variants', { required: true });
const deletedVariantIds = defineModel<number[]>('deletedVariantIds', { default: () => [] });
const variantSelector = ref<{ generationPreview: { total: number; new: number; existing: number } | null}>();
const toast = useToast();
const hasSelections = computed(() => Object.keys(selections.value).length > 0);
const hasNoVariants = computed(() => variants.value.filter((v) => !v.markedForDelete).length === 0);
const tableRef = ref<{ validate: () => boolean }>();

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

    const productName = props.product?.name ?? props.productName;
    const existingKeys = new Set(
        variants.value.map((variant) => variant.values.map((v) => v.id).sort((a, b) => a - b).join(',')),
    );

    let added = 0;
    cartesianValueIds().forEach((valueIds) => {
        const key = [...valueIds].sort((a, b) => a - b).join(',');
        if (existingKeys.has(key)) return;

        const values = valueIds.map(resolveValue).filter((v): v is NonNullable<typeof v> => v !== null);
        if (values.length !== valueIds.length) return;

        variants.value.push({
            _key: `new-${crypto.randomUUID()}`,
            sku: buildSku(productName, values),
            additional_price: 0,
            stock: 0,
            values,
            markedForDelete: false,
        });
        existingKeys.add(key);
        added += 1;
    });

    toast.add({
        severity: added ? 'success' : 'info',
        summary: added ? `${added} variant${added > 1 ? 's' : ''} added` : 'No new variants to add',
        life: 3000,
    });
}

// Delegated to by ProductForm before it submits.
function validate(): boolean {
    if (hasNoVariants.value) {
        return false;
    }
    return tableRef.value?.validate() ?? true;
}

defineExpose({ validate, hasNoVariants });
</script>

<template>
    <div class="space-y-8 py-4">
        <div class="space-y-3">
            <ProductVariantTypeSelector ref="variantSelector" v-model:selections="selections" :variant-types="variantTypes" :variants="variants" />
            <div class="grid justify-end text-right">
                <div>
                    <Button label="Generate Variants" :disabled="!hasSelections" @click="generateVariants" />
                    <p v-if="variantSelector?.generationPreview" class="mt-1 text-xs text-muted-foreground" >
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