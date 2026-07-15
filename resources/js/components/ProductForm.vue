<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, reactive, ref } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Product/ProductController';
import ProductVariantController from '@/actions/App/Http/Controllers/Product/ProductVariantController';
import { index as productsIndex } from '@/routes/products';
import { Trash, Check } from '@primeicons/vue';
import type { Category, VariantType } from '@/types/product-settings';
import type { Product, ProductVariant } from '@/types/product';

const props = defineProps<{
    product?: Product | null;
    categories: Category[];
    variantTypes: VariantType[];
}>();

const toast = useToast();
const isEditing = computed(() => !!props.product);
const activeTab = ref('0');

// ---------- Basic details + image ----------

const form = useForm({
    name: props.product?.name ?? '',
    category_id: props.product?.category_id ?? null,
    description: props.product?.description ?? '',
    price: props.product ? Number(props.product.price) : null,
    status: props.product?.status ?? 'draft',
    image: null as File | null,
    selections: {} as Record<number, number[]>,
});

function handleImageSelect(event: { files: File[] }) {
    form.image = event.files[0] ?? null;
}

function submit(status: 'draft' | 'published') {
    form.status = status;

    const options = {
        forceFormData: true,
        onError: () => {
            toast.add({
                severity: 'error',
                summary: isEditing.value ? 'Could not update product' : 'Could not create product',
                detail: 'Please check the form for errors.',
                life: 5000,
            });
        },
    };

    if (isEditing.value && props.product) {
        form.patch(ProductController.update(props.product.id).url, options);
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

const localVariants = ref<ProductVariant[]>(
    props.product?.variants ? props.product.variants.map((v) => ({ 
        ...v,
    })) : [],
);
const savingVariantIds = reactive<Record<number, boolean>>({});

function variantIsDirty(variant: ProductVariant): boolean {
    const original = props.product?.variants?.find((v) => v.id === variant.id);
    if (!original) return false;

    return (
        variant.sku.trim() !== original.sku ||
        Number(variant.additional_price) !== Number(original.additional_price) ||
        Number(variant.stock) !== Number(original.stock)
    );
}

function saveVariant(variant: ProductVariant) {
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

function deleteVariant(variant: ProductVariant) {
    router.delete(ProductVariantController.destroy([props.product!.id, variant.id]).url, {
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

function variantLabel(variant: ProductVariant): string {
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
                        <InputText id="name" v-model="form.name" placeholder="Enter product name" fluid />
                        <Message v-if="form.errors.name" severity="error" size="small" variant="simple">
                            {{ form.errors.name }}
                        </Message>
                    </div>

                    <div class="grid gap-1.5">
                        <label class="flex gap-2 text-sm font-medium">
                            <span>Category</span>
                            <span aria-hidden="true" class="text-red-500">*</span>
                        </label>
                        <Select
                            v-model="form.category_id"
                            :options="categories"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select a category"
                            fluid
                            showClear
                        />
                        <Message v-if="form.errors.category_id" severity="error" size="small" variant="simple">
                            {{ form.errors.category_id }}
                        </Message>
                    </div>

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
                        />
                        <Message v-if="form.errors.price" severity="error" size="small" variant="simple">
                            {{ form.errors.price }}
                        </Message>
                    </div>

                    <div v-if="isEditing" class="flex items-center justify-between border-t pt-4">
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
                    </div>
                </div>
            </TabPanel>

            <!-- ---------- Tab 2: Image ---------- -->
            <TabPanel value="1">
                <div class="space-y-4 py-4">
                    <img
                        v-if="product?.media?.[0]?.original_url && !form.image"
                        :src="product.media[0].original_url"
                        alt="Current product image"
                        class="h-40 w-40 rounded-md object-cover"
                    />

                    <FileUpload
                        mode="basic"
                        accept="image/*"
                        :maxFileSize="5 * 1024 * 1024"
                        chooseLabel="Choose image"
                        @select="handleImageSelect"
                    />
                    <Message v-if="form.errors.image" severity="error" size="small" variant="simple">
                        {{ form.errors.image }}
                    </Message>
                </div>
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
                                :v-model="Number(variant.additional_price)"
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
            type="button"
            label="Save as Draft"
            severity="secondary"
            :loading="form.processing"
            @click="submit('draft')"
        />
        <Button type="button" label="Publish" :loading="form.processing" @click="submit('published')" />
    </div>
</template>