<script setup lang="ts">
import { computed, ref } from 'vue';
import type { VariantType } from '@/types/product-settings';
import ProductVariantTypeSelector from './ProductVariants/ProductVariantTypeSelector.vue';

const props = defineProps<{ variantTypes: VariantType[]; productName: string }>();

const selections = defineModel<Record<number, number[]>>('selections', { required: true });
const attributeValueIds = defineModel<number[]>('attributeValueIds', { default: () => [] });

const variantSelector = ref<{ validate: () => boolean }>();

const hasNoSelections = computed(() =>
    Object.values(selections.value).every((ids) => ids.length === 0)
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
    <div class="space-y-8 py-4">
        <ProductVariantTypeSelector
            ref="variantSelector"
            v-model:selections="selections"
            v-model:attributeValueIds="attributeValueIds"
            :variant-types="variantTypes"
        />
    </div>
</template>