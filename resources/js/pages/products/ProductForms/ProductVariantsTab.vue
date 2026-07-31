<script setup lang="ts">
import { computed, ref } from 'vue';
import type { VariantType } from '@/types/product-settings';
import { show as variantsShow } from '@/routes/products/variants';
import ProductVariantTypeSelector from './ProductVariants/ProductVariantTypeSelector.vue';

const props = defineProps<{ variantTypes: VariantType[]; productName: string; product?: { slug: string } | null }>();

const variantSelections = defineModel<Record<number, number[]>>('variantSelections', { required: true });
const attributeValueIds = defineModel<number[]>('attributeValueIds', { default: () => [] });

const variantSelector = ref<{ validate: () => boolean }>();

const hasNoSelections = computed(() =>
    Object.values(variantSelections.value).every((ids) => ids.length === 0)
);

// Delegated to by ProductForm before it submits.
function validate(): boolean {
    const selectorValid = variantSelector.value?.validate() ?? true;

    if (!selectorValid) return false;
    if (hasNoSelections.value) return false;
    return true;
}

defineExpose({ validate, hasNoSelections });
</script>

<template>
    <div class="py-4">
        <ProductVariantTypeSelector
            ref="variantSelector"
            v-model:variant-selections="variantSelections"
            v-model:attributeValueIds="attributeValueIds"
            :variant-types="variantTypes"
        />
        <div v-if="product" class="flex justify-end">
            <Button label="View Variants List" @click="$inertia.visit(variantsShow(product!.slug).url)" />
        </div>
    </div>
</template>