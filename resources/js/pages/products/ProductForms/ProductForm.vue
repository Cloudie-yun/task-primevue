<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch, onBeforeUnmount, onMounted } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Product/ProductController';
import { index as productsIndex } from '@/routes/products';
import { show as variantsShow } from '@/routes/products/variants';
import type { Product } from '@/types/product';
import type { Category, VariantType } from '@/types/product-settings';
import { productSchema } from '@/validation/product';
import BasicDetailsTab from './BasicDetailsTab.vue';
import ProductImagesTab from './ProductImagesTab.vue';
import ProductVariantsTab from './ProductVariantsTab.vue';

const props = defineProps<{ product?: Product | null; categories: Category[]; variantTypes: VariantType[] }>();
const toast = useToast();
const isEditing = computed(() => !!props.product);
const imageTabRef = ref<{ hasNoImages: boolean; reset: () => void }>();
const variantsTabRef = ref<{ validate: () => boolean; hasNoSelections: boolean }>();
const showDeleteConfirm = ref(false);

const form = useForm({
    name: props.product?.name ?? '',
    slug: props.product?.slug ?? '',
    category_id: props.product?.category_id ?? null,
    description: props.product?.description ?? '',
    price: props.product ? Number(props.product.price) : null,
    status: props.product?.status ?? 'draft',
    images: [] as File[],
    deleted_image_ids: [] as number[],
    variant_selections: buildInitialSelections(props.product),
    attribute_value_ids: props.product?.attribute_values?.map((a) => a.id) ?? [],
    deleted_variant_ids: [] as number[],
});

function buildInitialSelections(product?: Product | null): Record<number, number[]> {
    const map: Record<number, number[]> = {};

    product?.variants?.forEach((variant) => {
            variant.values.forEach((val) => {
                const typeId = val.variant_type_id;
                if (typeId == null) return;
                if (!map[typeId]) map[typeId] = [];
                if (!map[typeId].includes(val.id)) map[typeId].push(val.id);
            });
        });
    return map;
}

function confirmDelete() {
    form.deleted_variant_ids = orphanedVariants.value.map((v) => v.id);
    showDeleteConfirm.value = false;
    submitForm();
}

// ---------- Unsaved changes warning ----------
function handleBeforeUnload(event: BeforeUnloadEvent) {
    if (form.isDirty) {
        event.preventDefault();
        event.returnValue = ''; // required for Chrome to show the native confirmation
    }
}

let removeInertiaListener: (() => void) | undefined;

onMounted(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);

    removeInertiaListener = router.on('before', (event) => {
        if (event.detail.visit.method !== 'get') {
            return;
        }

        if (form.isDirty) {
            const confirmed = window.confirm('You have unsaved changes. Leave this page anyway?');
            if (!confirmed) {
                event.preventDefault();
            }
        }
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    removeInertiaListener?.();
});

function onCategorySelected(id: number) {
    form.isDirty = false;
    form.defaults('category_id', id);
}

// ---------- Handle Form Error Validation ----------
watch(
    () => [form.name, form.category_id, form.description, form.price],
    ([name, category_id, description, price]) => {
        if (typeof name === 'string' && name.trim() && form.errors.name) {
            form.clearErrors('name');
        }
        if (category_id !== null && form.errors.category_id) {
            form.clearErrors('category_id');
        }
        if (typeof description === 'string' && description.trim() && form.errors.description) {
            form.clearErrors('description');
        }
        if (price !== null && price?.toString().length > 0 && form.errors.price) {
            form.clearErrors('price');
        }
    },
);

// Form Submit Function
const orphanedVariants = computed(() => {
    if (!props.product?.variants?.length) return [];

    const validSignatures = new Set(cartesianSignatures(
        Object.values(form.variant_selections).filter((ids) => ids.length)
    ));

    return props.product.variants.filter((variant) => {
        const signature = variant.values.map((v) => v.id).sort((a, b) => a - b).join('-');
        return !validSignatures.has(signature);
    });
});

function cartesianSignatures(groups: number[][]): string[] {
    if (!groups.length) return [];
    return groups.reduce<number[][]>(
        (acc, ids) => acc.flatMap((combo) => ids.map((id) => [...combo, id])),
        [[]],
    ).map((combo) => [...combo].sort((a, b) => a - b).join('-'));
}

function validateBasicFields(): boolean {
    const result = productSchema.safeParse({
        name: form.name,
        category_id: form.category_id,
        description: form.description,
        price: form.price,
    });

    if (result.success) return true;

    for (const issue of result.error.issues) {
        form.setError(issue.path[0] as keyof typeof form.data, issue.message);
    }
    return false;
}

function validateImages(): boolean {
    return !imageTabRef.value?.hasNoImages;
}


function validateVariants(): boolean {
const valid = variantsTabRef.value?.validate() ?? true;
    if (!valid) form.setError('variant_selections', 'Please add at least one variant');
    return valid;
}

function notifyValidationFailure(message: string) {
    toast.add({ severity: 'warn', summary: 'Please Check', detail: message, life: 4000 });
}

function submit(status: 'draft' | 'published') {
    form.clearErrors();

    if (!validateBasicFields()) {
        notifyValidationFailure('Please fix the highlighted fields');
        return;
    }
    if (!validateImages()) {
        notifyValidationFailure('At least one product image is required');
        return;
    }
    if (!validateVariants()) {
        notifyValidationFailure('Please fix the highlighted variant rows before saving');
        return;
    }

    form.status = status;

    if (orphanedVariants.value.length) {
        showDeleteConfirm.value = true; // wait for confirmDelete()
        return;
    }

    submitForm();
}

function submitForm() {
    const onSuccess = () => {
        imageTabRef.value?.reset();
        form.deleted_variant_ids = [];
        toast.add({
            severity: 'success',
            summary: isEditing.value ? 'Product updated' : 'Product created',
            detail: isEditing.value ? 'Changes saved successfully' : 'Product saved successfully',
            life: 3000,
        });
    };

    const onError = (errors: Record<string, string>) => {
        const messages = Object.values(errors);
        toast.add({
            severity: 'error',
            summary: isEditing.value ? 'Could not update product' : 'Could not create product',
            detail: messages.length === 1
                ? messages[0]
                : `${messages[0]} (+${messages.length - 1} more issue${messages.length - 1 > 1 ? 's' : ''})`,
            life: 6000,
        });
    };

    const options = { forceFormData: true, onSuccess, onError };

    props.product
        ? form.patch(ProductController.update(props.product.id).url, options)
        : form.post(ProductController.store().url, options);
}

function archive() {
    if (!props.product) return;
    form.status = 'archived';
    form.patch(ProductController.update(props.product.id).url, { onSuccess: () => toast.add({ severity: 'success', summary: 'Product archived', life: 3000 }) });
}
</script>

<template>
    <div class="space-y-6 pb-10">
        <!-- Main Grid -->
        <div class="grid grid-cols-12 gap-10">
            <div class="col-span-12 lg:col-span-8 space-y-6">
                <Card>
                    <template #title>Product Images</template>
                    <template #content>
                        <ProductImagesTab
                            ref="imageTabRef"
                            v-model="form.images"
                            v-model:deleted-ids="form.deleted_image_ids"
                            :product="product"
                            :errors="form.errors.images"
                        />
                    </template>
                </Card>
                <!-- Basic Information -->
                <Card>
                    <template #title> Basic Information</template>
                    <template #content>
                        <BasicDetailsTab
                            v-model:name="form.name"
                            v-model:category-id="form.category_id"
                            v-model:description="form.description"
                            v-model:price="form.price"
                            :categories="categories"
                            :errors="{
                                ...form.errors,
                                name: form.errors.slug ?? form.errors.name
                            }"
                            @category-selected="onCategorySelected"
                        />
                    </template>
                </Card>
                <!-- Variants -->
                <Card>
                    <template #title> Product Variants </template>
                    <template #content>
                        <ProductVariantsTab
                            ref="variantsTabRef"
                            v-model:variant-selections="form.variant_selections"
                            v-model:attributeValueIds="form.attribute_value_ids"
                            :variant-types="variantTypes"
                            :product="product"
                            :product-name="form.name"
                        />
                    </template>
                </Card>
            </div>

            <!-- RIGHT SIDEBAR -->
            <div class="col-span-12 lg:col-span-4">
                <div class="sticky! top-6 space-y-6">
                    <!-- Product Summary -->
                    <Card>
                        <template #title> Product Summary </template>
                        <template #content>
                            <div class="space-y-5">
                                <div>
                                    <div class="text-xs uppercase text-surface-500"> Name </div>
                                    <div class="font-medium mt-1 wrap-break-word"> {{ form.name || 'Untitled Product' }} </div>
                                </div>
                                <Divider />
                                <div class="flex justify-between">
                                    <span class="text-surface-500"> Category </span>
                                    <span>
                                        {{ categories.find(c => c.id === form.category_id)?.name ?? '-'}}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-surface-500"> Base Price </span>
                                    <span>
                                        {{ form.price != null ? `RM ${Number(form.price).toFixed(2)}` : '-' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-surface-500"> Images </span>
                                    <span> {{ (product?.media?.length ?? 0) + form.images.length - form.deleted_image_ids.length}}</span>
                                </div>
                                <div
                                    v-if="isEditing"
                                    class="flex cursor-pointer justify-between rounded-md transition-colors hover:bg-surface-100 dark:hover:bg-surface-700"
                                    @click="$inertia.visit(variantsShow(product!.slug).url)"
                                    v-tooltip.bottom="'Click here to view variants list'"
                                >
                                    <span class="text-surface-500 ">Variants</span>
                                    <span>{{ product?.variants?.length ?? 0 }}</span>
                                </div>
                                <Divider />
                                <div class="flex justify-between items-center">
                                    <span class="text-surface-500"> Status </span>
                                    <Tag
                                        :value="form.status.toUpperCase()"
                                        :severity=" form.status === 'published' ? 'success' : form.status === 'draft' ? 'warn' : 'danger'"
                                    />
                                </div>
                            </div>
                        </template>
                    </Card>
                    <!-- Actions -->
                    <Card>
                        <template #title> Actions </template>
                        <template #content>
                            <div class="flex flex-col gap-3">
                                <Button
                                    type="button"
                                    label="Cancel"
                                    severity="secondary"
                                    variant="outlined"
                                    fluid
                                    @click="$inertia.visit(productsIndex().url)"
                                />

                                <Button
                                    v-if="product?.status !== 'archived' && isEditing"
                                    label="Archive Product"
                                    severity="danger"
                                    variant="outlined"
                                    fluid
                                    :loading="form.processing"
                                    @click="archive"
                                />

                                <Button
                                    :label="
                                        isEditing
                                            ? 'Save Changes'
                                            : 'Save as Draft'
                                    "
                                    severity="secondary"
                                    fluid
                                    :loading="form.processing"
                                    @click="submit(product?.status === 'published'
                                        ? 'published'
                                        : 'draft')"
                                />

                                <Button
                                    v-if="product?.status !== 'published'"
                                    label="Publish Product"
                                    fluid
                                    :loading="form.processing"
                                    @click="submit('published')"
                                />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </div>
    <Dialog v-model:visible="showDeleteConfirm" modal header="Remove variants?" :style="{ width: '28rem' }">
        <p class="text-sm text-muted-foreground mb-3">
            These {{ orphanedVariants.length }} variant(s) no longer match your selected types/values and will be permanently removed:
        </p>
        <ul class="text-sm space-y-1 mb-4">
            <li v-for="v in orphanedVariants" :key="v.id" class="flex justify-between">
                <span>{{ v.values.map(val => val.value).join(' / ') }}</span>
                <span class="text-muted-foreground">{{ v.sku }}</span>
            </li>
        </ul>
        <div class="flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" variant="outlined" @click="showDeleteConfirm = false" />
            <Button label="Remove & Save" severity="danger" @click="confirmDelete" />
        </div>
    </Dialog>
</template>