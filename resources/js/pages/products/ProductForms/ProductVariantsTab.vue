<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, reactive, ref } from 'vue';
import ProductVariantController from '@/actions/App/Http/Controllers/Product/ProductVariantController';
import type { EditableProductVariant, Product } from '@/types/product';
import type { VariantType } from '@/types/product-settings';
import { Check, Trash } from '@primeicons/vue';

const props = defineProps<{ product?: Product | null; variantTypes: VariantType[] }>();
const selections = defineModel<Record<number, number[]>>('selections', { required: true });
const toast = useToast();
const generating = ref(false);
const localVariants = ref<EditableProductVariant[]>(
    (props.product?.variants ?? []).map((variant) => ({ ...variant, additional_price: Number(variant.additional_price) })),
);
const savingVariantIds = reactive<Record<number, boolean>>({});
const hasSelections = computed(() => Object.keys(selections.value).length > 0);

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

function variantIsDirty(variant: EditableProductVariant) {
    const original = props.product?.variants?.find((item) => item.id === variant.id);
    return !!original && (variant.sku.trim() !== original.sku || Number(variant.additional_price) !== Number(original.additional_price) || Number(variant.stock) !== Number(original.stock));
}

function saveVariant(variant: EditableProductVariant) {
    if (!props.product || !variantIsDirty(variant)) return;
    savingVariantIds[variant.id] = true;
    router.patch(ProductVariantController.update([props.product.id, variant.id]).url, { sku: variant.sku.trim(), additional_price: variant.additional_price, stock: variant.stock }, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Variant updated', life: 3000 }),
        onError: (errors) => toast.add({ severity: 'error', summary: 'Update failed', detail: Object.values(errors)[0] as string, life: 5000 }),
        onFinish: () => { savingVariantIds[variant.id] = false; },
    });
}

function deleteVariant(variant: EditableProductVariant) {
    if (!props.product) return;
    router.delete(ProductVariantController.destroy([props.product.id, variant.id]).url, {
        preserveScroll: true,
        onSuccess: () => { toast.add({ severity: 'success', summary: 'Variant deleted', life: 3000 }); localVariants.value = localVariants.value.filter((item) => item.id !== variant.id); },
        onError: () => toast.add({ severity: 'error', summary: 'Delete failed', life: 3000 }),
    });
}

function variantLabel(variant: EditableProductVariant) { return variant.values.map((value) => value.value).join(' / '); }
</script>

<template>
    <div class="space-y-6 py-4">
        <div v-for="type in variantTypes" :key="type.id" class="space-y-2">
            <label class="text-sm font-medium">{{ type.name }}</label>
            <div class="flex flex-wrap gap-2">
                <Tag v-for="value in type.values" :key="value.id" :severity="isSelected(type.id, value.id) ? 'info' : 'secondary'" class="w-max cursor-pointer px-3" @click="toggleValue(type.id, value.id)">
                    {{ value.value }}
                </Tag>
            </div>
        </div>
        <div v-if="product" class="pt-2">
            <Button label="Generate Variants" :disabled="!hasSelections" :loading="generating" @click="generateVariants" />
            <p class="mt-1 text-xs text-muted-foreground">Only new combinations are created — existing variants are untouched.</p>
        </div>
        <p v-else class="text-xs text-muted-foreground">Selected combinations will be generated automatically once the product is saved.</p>
        <div v-if="product && localVariants.length" class="space-y-2 border-t pt-4">
            <label class="text-sm font-medium">Existing Variants</label>
            <div v-for="variant in localVariants" :key="variant.id" class="grid grid-cols-[1fr_1fr_120px_100px_auto_auto] items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ variantLabel(variant) }}</span>
                <InputText v-model="variant.sku" placeholder="SKU" fluid />
                <InputNumber v-model="variant.additional_price" mode="currency" currency="MYR" locale="en-US" placeholder="Adj. price" fluid />
                <InputNumber v-model="variant.stock" placeholder="Stock" fluid />
                <Button text severity="success" :disabled="!variantIsDirty(variant)" :loading="savingVariantIds[variant.id]" @click="saveVariant(variant)"><Check /></Button>
                <Button severity="danger" variant="text" rounded @click="deleteVariant(variant)"><Trash /></Button>
            </div>
        </div>
    </div>
</template>
