<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, reactive, ref, watch, onMounted } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Product/ProductController';
import ProductVariantController from '@/actions/App/Http/Controllers/Product/ProductVariantController';
import ProductImageController from '@/actions/App/Http/Controllers/Product/ProductImageController';
import { index as productsIndex } from '@/routes/products';

import CategoryCreate from '@/components/CategoryCreate.vue';
import ProductImageManager from '@/components/ProductImageManager.vue';
import { Trash, Check, Plus } from '@primeicons/vue';

import type { Category, VariantType } from '@/types/product-settings';
import type { Product, EditableProductVariant } from '@/types/product';
import { productSchema } from '@/validation/product';

const props = defineProps<{
    product?: Product | null;
    categories: Category[];
    variantTypes: VariantType[];
}>();

const toast = useToast();
const isEditing = computed(() => !!props.product);
const imageManagerRef = ref();
const loading = ref(false);


const activeTab = ref('0');

const activeTabKey = computed(() => {
    return `productFormActiveTab-${props.product?.id ?? 'new'}`;
});

watch(
    () => props.product?.id,
    () => {
        const storedTab = localStorage.getItem(activeTabKey.value);

        // Reset to first tab if creating new product
        activeTab.value = props.product ? (storedTab ?? '0') : '0';
    },
    { immediate: true }
);

watch(activeTab, (newTab) => {
    localStorage.setItem(activeTabKey.value, newTab);
});

// ---------- Basic details + image ----------
const newImages = ref<File[]>([]);
const newImagePreviews = ref<string[]>([]);

const form = useForm({
    name: props.product?.name ?? '',
    category_id: props.product?.category_id ?? null,
    description: props.product?.description ?? '',
    price: props.product ? Number(props.product.price) : null,
    status: props.product?.status ?? 'draft',
    images: [] as File[],
    deleted_image_ids: [] as number[],
    selections: {} as Record<number, number[]>,
});

function submit(status: 'draft' | 'published') {
    form.status = status;
    form.clearErrors();

    const result = productSchema.safeParse({
        name: form.name,
        category_id: form.category_id,
        description: form.description,
        price: form.price,
    });

    if (!result.success) {
        for (const issue of result.error.issues) {
            const field = issue.path[0] as string;
            form.setError(field as keyof typeof form.data, issue.message);
        }
        activeTab.value = '0';
        toast.add({
            severity: 'warn',
            summary: 'Please Check',
            detail: 'Please fix the highlighted fields',
            life: 4000,
        });
        return; // stop — don't hit the server at all
    }

    if (imageManagerRef.value?.hasNoImages) {
        activeTab.value = '1'; // jump to the Image tab so the user sees why
        toast.add({
            severity: 'error',
            summary: 'No Image Added',
            detail: 'At least one product image is required',
            life: 4000,
        });
        return;
    }

    const options = {
        forceFormData: true,
        onError: (errors: Record<string, string>) => {
            const messages = Object.values(errors);
            const fieldCount = messages.length;

            toast.add({
                severity: 'error',
                summary: isEditing.value ? 'Could not update product' : 'Could not create product',
                detail:
                    fieldCount === 1
                        ? messages[0]
                        : `${messages[0]} (+${fieldCount - 1} more issue${fieldCount - 1 > 1 ? 's' : ''})`,
                life: 6000,
            });
        },
    };

    if (isEditing.value && props.product) {
        form.patch(ProductController.update(props.product.id).url, {
            ...options,
            onSuccess: () => {
                imageManagerRef.value?.reset();
            },
        });
    } else {
        form.post(ProductController.store().url, options);
    }
}

function archive() {
    if (!props.product) return;     

    form.status = 'archived';
    form.patch(ProductController.update(props.product.id).url, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Product archived', life: 3000 });
        },
    });
}

// ---------- Category autocomplete ----------
const selectedCategory = ref<Category | null>(
    props.categories.find((c) => c.id === props.product?.category_id) ?? null,
);
const categorySuggestions = ref<Category[]>([]);
const showCreateCategoryDialog = ref(false);
const categorySearchQuery = ref('');
const pendingCategoryName = ref('');

watch(selectedCategory, (val) => {
    form.category_id = val?.id ?? null;
});

function searchCategories(event: { query: string }) {
    categorySearchQuery.value = event.query;
    const query = event.query.toLowerCase().trim();

    categorySuggestions.value = query
        ? props.categories.filter((c) => c.name.toLowerCase().includes(query))
        : [...props.categories];
}

function openCreateCategoryDialog() {
    pendingCategoryName.value = categorySearchQuery.value;
    showCreateCategoryDialog.value = true;
}

function handleCategoryCreated() {
    router.reload({
        only: ['categories'],
        onSuccess: () => {
            const created = props.categories.find(
                (c) => c.name.toLowerCase() === pendingCategoryName.value.toLowerCase(),
            );
            if (created) {
                selectedCategory.value = created;
            }
        },
    });
}

// ---------- Variant selection (used in both create + edit) ----------
const selections = reactive<Record<number, number[]>>({});

function toggleValue(typeId: number, valueId: number) {
    const current = selections[typeId] ?? [];
    selections[typeId] = current.includes(valueId)
        ? current.filter((id) => id !== valueId)
        : [...current, valueId];

    if (selections[typeId].length === 0) delete selections[typeId];
}

function isSelected(typeId: number, valueId: number) {
    return selections[typeId]?.includes(valueId) ?? false;
}

const hasSelections = computed(() => Object.keys(selections).length > 0);
const generating = ref(false);

function generateVariants() {
    if (!props.product || !hasSelections.value) return;

    generating.value = true;

    router.post(
        ProductVariantController.generate(props.product.id).url,
        { selections },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Variants generated', life: 3000 });
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Could not generate variants',
                    detail: Object.values(errors)[0] as string,
                    life: 5000,
                });
            },
            onFinish: () => {
                generating.value = false;
            },
        },
    );
}

// ---------- Existing variant rows (edit mode only) ----------
const localVariants = ref<EditableProductVariant[]>(
    (props.product?.variants ?? []).map((v) => ({
        ...v,
        additional_price: Number(v.additional_price),
    })),
);

const savingVariantIds = reactive<Record<number, boolean>>({});

function variantIsDirty(variant: EditableProductVariant): boolean {
    const original = props.product?.variants?.find((v) => v.id === variant.id);
    if (!original) return false;

    return (
        variant.sku.trim() !== original.sku ||
        Number(variant.additional_price) !== Number(original.additional_price) ||
        Number(variant.stock) !== Number(original.stock)
    );
}

function saveVariant(variant: EditableProductVariant) {
    if (!props.product || !variantIsDirty(variant)) return;

    savingVariantIds[variant.id] = true;

    router.patch(
        ProductVariantController.update([props.product.id, variant.id]).url,
        {
            sku: variant.sku.trim(),
            additional_price: variant.additional_price,
            stock: variant.stock,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Variant updated', life: 3000 });
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Update failed',
                    detail: Object.values(errors)[0] as string,
                    life: 5000,
                });
            },
            onFinish: () => {
                savingVariantIds[variant.id] = false;
            },
        },
    );
}

function deleteVariant(variant: EditableProductVariant) {
    if (!props.product) return;

    router.delete(ProductVariantController.destroy([props.product.id, variant.id]).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Variant deleted', life: 3000 });
            localVariants.value = localVariants.value.filter((v) => v.id !== variant.id);
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Delete failed', life: 3000 });
        },
    });
}

function variantLabel(variant: EditableProductVariant): string {
    return variant.values.map((v) => v.value).join(' / ');
}
</script>

<template>
    <Tabs v-model:value="activeTab">
        <TabList>
            <Tab value="0">Basic Details</Tab>
            <Tab value="1">Image</Tab>
            <Tab value="2">Variants</Tab>
        </TabList>

        <TabPanels>
            <!-- ---------- Tab 1: Basic Details ---------- -->
            <TabPanel value="0">
                <div class="space-y-6 py-4">
                    <div class="grid gap-1.5">
                        <label for="name" class="flex gap-2 text-sm font-medium">
                            <span>Product name</span>
                            <span aria-hidden="true" class="text-red-500">*</span>
                        </label>
                        <InputText id="name" v-model="form.name" placeholder="Enter product name" fluid :invalid="!!form.errors.name"/>
                        <Message v-if="form.errors.name" severity="error" size="small" variant="simple">
                            {{ form.errors.name }}
                        </Message>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="flex gap-2 text-sm font-medium">
                            <span>Category</span>
                            <span aria-hidden="true" class="text-red-500">*</span>
                        </label>

                        <AutoComplete
                            v-model="selectedCategory"
                            :suggestions="categorySuggestions"
                            optionLabel="name"
                            placeholder="Search or create a category"
                            fluid
                            dropdown
                            :invalid="!!form.errors.category_id"
                            @complete="searchCategories"
                        >
                            <template #empty>
                                <div class="p-2 text-sm flex gap-2 items-center">
                                    <p class="text-muted-foreground">No categories found.</p>
                                    <Button
                                        text
                                        @click="openCreateCategoryDialog"
                                        class=" underline"
                                    >
                                        Create new category here
                                    </Button>
                                </div>
                            </template>
                        </AutoComplete>

                        <Message v-if="form.errors.category_id" severity="error" size="small" variant="simple">
                            {{ form.errors.category_id }}
                        </Message>
                    </div>

                    <CategoryCreate
                        v-model:visible="showCreateCategoryDialog"
                        :initial-name="pendingCategoryName"
                        @created="handleCategoryCreated"
                    />
                    <div class="grid gap-1.5">
                        <label for="description" class="flex gap-2 text-sm font-medium">
                            <span>Description</span>
                            <span aria-hidden="true" class="text-red-500">*</span>
                        </label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Enter product description"
                            fluid
                            rows="4"
                            autoResize
                            :invalid="!!form.errors.description"
                        />
                        <Message v-if="form.errors.description" severity="error" size="small" variant="simple">
                            {{ form.errors.description }}
                        </Message>
                    </div>

                    <div class="grid gap-1.5">
                        <label for="price" class="flex gap-2 text-sm font-medium">
                            <span>Price</span>
                            <span aria-hidden="true" class="text-red-500">*</span>
                        </label>
                        <InputNumber
                            id="price"
                            v-model="form.price"
                            mode="currency"
                            currency="MYR"
                            locale="en-US"
                            placeholder="Enter product price"
                            fluid
                            :invalid="!!form.errors.price"
                        />
                        <Message v-if="form.errors.price" severity="error" size="small" variant="simple">
                            {{ form.errors.price }}
                        </Message>
                    </div>

                    <!-- <div v-if="isEditing" class="flex items-center justify-between border-t pt-4">
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <span>Current status:</span>
                            <Tag :value="product?.status" severity="secondary" />
                        </div>
                        <Button
                            v-if="product?.status !== 'archived'"
                            label="Archive product"
                            severity="danger"
                            variant="text"
                            :loading="form.processing"
                            @click="archive"
                        />
                    </div> -->
                </div>
            </TabPanel>

            <!-- ---------- Tab 2: Image ---------- -->
            <TabPanel value="1">
                <!-- <ProductImageManager
                    ref="imageManagerRef"
                    v-model="form.images"
                    :product="product"
                    :errors="form.errors.images"
                /> -->
                <ProductImageManager
                    ref="imageManagerRef"
                    v-model="form.images"
                    v-model:deleted-ids="form.deleted_image_ids"
                    :product="product"
                    :errors="form.errors.images"
                />
            </TabPanel>

            <!-- ---------- Tab 3: Variants ---------- -->
            <TabPanel value="2">
                <div class="space-y-6 py-4">
                    <div v-for="type in variantTypes" :key="type.id" class="space-y-2">
                        <label class="text-sm font-medium">{{ type.name }}</label>
                        <div class="flex flex-wrap gap-2">
                            <Tag
                                v-for="val in type.values"
                                :key="val.id"
                                :severity="isSelected(type.id, val.id) ? 'info' : 'secondary'"
                                class="w-max cursor-pointer px-3"
                                @click="toggleValue(type.id, val.id)"
                            >
                                {{ val.value }}
                            </Tag>
                        </div>
                    </div>

                    <div v-if="isEditing" class="pt-2">
                        <Button
                            label="Generate Variants"
                            :disabled="!hasSelections"
                            :loading="generating"
                            @click="generateVariants"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Only new combinations are created — existing variants are untouched.
                        </p>
                    </div>
                    <p v-else class="text-xs text-muted-foreground">
                        Selected combinations will be generated automatically once the product is saved.
                    </p>

                    <div v-if="isEditing && localVariants.length" class="space-y-2 border-t pt-4">
                        <label class="text-sm font-medium">Existing Variants</label>

                        <div
                            v-for="variant in localVariants"
                            :key="variant.id"
                            class="grid grid-cols-[1fr_1fr_120px_100px_auto_auto] items-center gap-2"
                        >
                            <span class="text-sm text-muted-foreground">{{ variantLabel(variant) }}</span>
                            <InputText v-model="variant.sku" placeholder="SKU" fluid />
                            <InputNumber
                                v-model="variant.additional_price"
                                mode="currency"
                                currency="MYR"
                                locale="en-US"
                                placeholder="Adj. price"
                                fluid
                            />
                            <InputNumber v-model="variant.stock" placeholder="Stock" fluid />
                            <Button
                                text
                                severity="success"
                                :disabled="!variantIsDirty(variant)"
                                :loading="savingVariantIds[variant.id]"
                                @click="saveVariant(variant)"
                            >
                                <Check />
                            </Button>
                            <Button severity="danger" variant="text" rounded @click="deleteVariant(variant)">
                                <Trash />
                            </Button>
                        </div>
                    </div>
                </div>
            </TabPanel>
        </TabPanels>
    </Tabs>

    <div class="flex justify-end gap-2 border-t pt-6">
        <Button
            type="button"
            label="Cancel"
            severity="secondary"
            variant="text"
            @click="$inertia.visit(productsIndex().url)"
        />
        <Button
            v-if="product?.status !== 'archived' && isEditing"
            label="Archive product"
            severity="danger"
            variant="text"
            :loading="form.processing"
            @click="archive"
        />
        <Button
            v-if="product?.status !== 'published'"
            type="button"
            :label="isEditing ? 'Save Changes' : 'Save as Draft'"
            severity="secondary"
            :loading="form.processing"
            @click="submit('draft')"
        />
        <Button
            v-else
            type="button"
            :label="isEditing ? 'Save Changes' : 'Save as Draft'"
            severity="secondary"
            :loading="form.processing"
            @click="submit('published')"
        />
        <Button 
            v-if="product?.status !== 'published'" 
            type="button" 
            label="Publish"
            :loading="form.processing"
            @click="submit('published')" 
        />
    </div>
</template>