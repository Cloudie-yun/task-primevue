<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch, onBeforeUnmount, onMounted } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Product/ProductController';
import { index as productsIndex } from '@/routes/products';
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
const activeTab = ref('0');
const activeTabKey = computed(() => `productFormActiveTab-${props.product?.id ?? 'new'}`);

const form = useForm({
    name: props.product?.name ?? '',
    slug: props.product?.slug ?? '',
    category_id: props.product?.category_id ?? null,
    description: props.product?.description ?? '',
    price: props.product ? Number(props.product.price) : null,
    status: props.product?.status ?? 'draft',
    images: [] as File[],
    deleted_image_ids: [] as number[],
    selections: {} as Record<number, number[]>,
});
// Active Tab Watching
watch(() => props.product?.id, () => {
    activeTab.value = props.product ? (localStorage.getItem(activeTabKey.value) ?? '0') : '0';
}, { immediate: true });

watch(activeTab, (tab) => localStorage.setItem(activeTabKey.value, tab));

const FIELD_TAB_MAP: Record<string, string> = {
    name: '0',
    slug: '0', // slug errors surface on the Basic Details tab, alongside name
    category_id: '0',
    description: '0',
    price: '0',
    images: '1',
    deleted_image_ids: '1',
    selections: '2',
};

function jumpToFirstErrorTab(errors: Record<string, string>) {
    const firstField = Object.keys(errors)[0];
    const tab = FIELD_TAB_MAP[firstField];
    if (tab) activeTab.value = tab;
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
        // only guard actual page navigations — form.patch()/form.post() are visits too,
        // but they shouldn't trigger the "leaving page" warning on themselves
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

// Form Submit Function
function submit(status: 'draft' | 'published') {
    form.status = status;
    form.clearErrors();
    const result = productSchema.safeParse({ name: form.name, category_id: form.category_id, description: form.description, price: form.price });

    if (!result.success) {
        for (const issue of result.error.issues) form.setError(issue.path[0] as keyof typeof form.data, issue.message);
        activeTab.value = '0';
        toast.add({ severity: 'warn', summary: 'Please Check', detail: 'Please fix the highlighted fields', life: 4000 });
        return;
    }

    if (imageTabRef.value?.hasNoImages) {
        activeTab.value = '1';
        toast.add({ severity: 'error', summary: 'No Image Added', detail: 'At least one product image is required', life: 4000 });
        return;
    }

    const options = {
        forceFormData: true,
        onError: (errors: Record<string, string>) => {
            const messages = Object.values(errors);
            jumpToFirstErrorTab(errors);
            toast.add({ severity: 'error', summary: isEditing.value ? 'Could not update product' : 'Could not create product', detail: messages.length === 1 ? messages[0] : `${messages[0]} (+${messages.length - 1} more issue${messages.length - 1 > 1 ? 's' : ''})`, life: 6000 });
        },
    };

    if (props.product) {
        form.patch(ProductController.update(props.product.id).url, { ...options, onSuccess: () => imageTabRef.value?.reset() });
    } else {
        form.post(ProductController.store().url, options);
    }
}

function archive() {
    if (!props.product) return;
    form.status = 'archived';
    form.patch(ProductController.update(props.product.id).url, { onSuccess: () => toast.add({ severity: 'success', summary: 'Product archived', life: 3000 }) });
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
            <TabPanel value="0">
                <BasicDetailsTab
                    v-model:name="form.name"
                    v-model:category-id="form.category_id"
                    v-model:description="form.description"
                    v-model:price="form.price"
                    :categories="categories"
                    :errors="{ ...form.errors, name: form.errors.slug ?? form.errors.name }"
                />
            </TabPanel>
            <TabPanel value="1">
                <ProductImagesTab
                    ref="imageTabRef"
                    v-model="form.images"
                    v-model:deleted-ids="form.deleted_image_ids"
                    :product="product"
                    :errors="form.errors.images"
                />
            </TabPanel>
            <TabPanel value="2">
                <ProductVariantsTab v-model:selections="form.selections" :product="product" :variant-types="variantTypes" />
            </TabPanel>
        </TabPanels>
    </Tabs>

    <div class="flex justify-end gap-2 border-t pt-6">
        <Button type="button" label="Cancel" severity="secondary" variant="text" @click="$inertia.visit(productsIndex().url)" />
        <Button v-if="product?.status !== 'archived' && isEditing" label="Archive product" severity="danger" variant="text" :loading="form.processing" @click="archive" />
        <Button type="submit" :label="isEditing ? 'Save Changes' : 'Save as Draft'" severity="secondary" :loading="form.processing" @click="submit(product?.status === 'published' ? 'published' : 'draft')" />
        <Button v-if="product?.status !== 'published'" type="button" label="Publish" :loading="form.processing" @click="submit('published')" />
    </div>
</template>
