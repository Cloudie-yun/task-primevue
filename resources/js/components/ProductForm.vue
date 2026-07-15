<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Product/ProductController';
import { index as productsIndex } from '@/routes/products';
import type { Category } from '@/types/product-settings';
import type { Product } from '@/types/product';

const props = defineProps<{
    product?: Product | null;
    categories: Category[];
}>();

const toast = useToast();
const isEditing = computed(() => !!props.product);

const form = useForm({
    name: props.product?.name ?? '',
    category_id: props.product?.category_id ?? null,
    description: props.product?.description ?? '',
    price: props.product ? Number(props.product.price) : null,
    status: props.product?.status ?? 'draft',
    image: null as File | null,
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
</script>

<template>
    <form class="space-y-6" @submit.prevent>
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

        <div class="grid gap-1.5">
            <label class="text-sm font-medium">Product image</label>

            <img
                v-if="product?.media?.[0]?.original_url && !form.image"
                :src="product.media[0].original_url"
                alt="Current product image"
                class="mb-2 h-24 w-24 rounded-md object-cover"
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

        <div class="flex justify-end gap-2 pt-2">
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
            <Button
                type="button"
                label="Publish"
                :loading="form.processing"
                @click="submit('published')"
            />
        </div>
    </form>
</template>