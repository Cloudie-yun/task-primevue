<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import CategoryCreate from '@/pages/productSettings/CategoryCreate.vue';
import type { Category } from '@/types/product-settings';

const props = defineProps<{
    categories: Category[];
    errors: Record<string, string | undefined>;
}>();

const emit = defineEmits<{ categoryCreated: [] }>();
const name = defineModel<string>('name', { required: true });
const categoryId = defineModel<number | null>('categoryId', { required: true });
const description = defineModel<string>('description', { required: true });
const price = defineModel<number | null>('price', { required: true });

const selectedCategory = ref<Category | null>(
    props.categories.find((category) => category.id === categoryId.value) ?? null,
);
const categorySuggestions = ref<Category[]>([]);
const showCreateCategoryDialog = ref(false);
const categorySearchQuery = ref('');
const pendingCategoryName = ref('');

watch(selectedCategory, (category) => {
    categoryId.value = category?.id ?? null;
});

watch(categoryId, (id) => {
    if (selectedCategory.value?.id !== id) {
        selectedCategory.value = props.categories.find((category) => category.id === id) ?? null;
    }
});

watch(
    () => props.categories,
    (categories) => {
        if (!pendingCategoryName.value) {
            return;
        }

        const created = categories.find(
            (category) => category.name.toLowerCase() === pendingCategoryName.value.toLowerCase(),
        );

        if (created) {
            selectedCategory.value = created;
            pendingCategoryName.value = '';
        }
    },
);

function searchCategories(event: { query: string }) {
    categorySearchQuery.value = event.query;
    const query = event.query.toLowerCase().trim();

    categorySuggestions.value = query
        ? props.categories.filter((category) => category.name.toLowerCase().includes(query))
        : [...props.categories];
}

function openCreateCategoryDialog() {
    pendingCategoryName.value = categorySearchQuery.value;
    showCreateCategoryDialog.value = true;
}

function handleCategoryCreated() {
    router.reload({ only: ['categories'] });
    emit('categoryCreated');
}
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="grid gap-1.5">
            <label for="name" class="flex gap-2 text-sm font-medium">
                <span>Product name</span><span aria-hidden="true" class="text-red-500">*</span>
            </label>
            <InputText id="name" v-model="name" placeholder="Enter product name" fluid :invalid="!!errors.name" />
            <Message v-if="errors.name" severity="error" size="small" variant="simple">{{ errors.name }}</Message>
        </div>

        <div class="grid gap-1.5">
            <label class="flex gap-2 text-sm font-medium">
                <span>Category</span><span aria-hidden="true" class="text-red-500">*</span>
            </label>
            <AutoComplete
                v-model="selectedCategory"
                :suggestions="categorySuggestions"
                option-label="name"
                placeholder="Search or create a category"
                fluid
                dropdown
                :invalid="!!errors.category_id"
                @complete="searchCategories"
            >
                <template #empty>
                    <div class="flex items-center gap-2 p-2 text-sm">
                        <p class="text-muted-foreground">No categories found.</p>
                        <Button text class="underline" @click="openCreateCategoryDialog">Create new category here</Button>
                    </div>
                </template>
            </AutoComplete>
            <Message v-if="errors.category_id" severity="error" size="small" variant="simple">
                {{ errors.category_id }}
            </Message>
        </div>

        <CategoryCreate
            v-model:visible="showCreateCategoryDialog"
            :initial-name="pendingCategoryName"
            @created="handleCategoryCreated"
        />

        <div class="grid gap-1.5">
            <label for="description" class="flex gap-2 text-sm font-medium">
                <span>Description</span><span aria-hidden="true" class="text-red-500">*</span>
            </label>
            <Textarea
                id="description"
                v-model="description"
                placeholder="Enter product description"
                fluid
                rows="4"
                auto-resize
                :invalid="!!errors.description"
            />
            <Message v-if="errors.description" severity="error" size="small" variant="simple">
                {{ errors.description }}
            </Message>
        </div>

        <div class="grid gap-1.5">
            <label for="price" class="flex gap-2 text-sm font-medium">
                <span>Price</span><span aria-hidden="true" class="text-red-500">*</span>
            </label>
            <InputNumber
                id="price"
                v-model="price"
                mode="currency"
                currency="MYR"
                locale="en-US"
                placeholder="Enter product price"
                fluid
                :invalid="!!errors.price"
            />
            <Message v-if="errors.price" severity="error" size="small" variant="simple">{{ errors.price }}</Message>
        </div>
    </div>
</template>
