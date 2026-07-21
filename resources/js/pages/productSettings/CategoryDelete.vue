<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/Product/CategoryController';
import DeleteDialog from '@/components/DeleteDialog.vue';
import ForceDeleteCategoryDialog from './ForceDeleteCategory.vue';
import type { Category } from '@/types/product-settings';
import TextLink from '@/components/TextLink.vue';

interface AffectedProduct {
    id: number;
    name: string;
}

const visible = defineModel<boolean>('visible', { default: false });
const props = defineProps<{ category: Category | null }>();
const emit = defineEmits<{ deleted: [] }>();

const toast = useToast();
const processing = ref(false);
const deleteError = ref('');
const forceDialogVisible = ref(false);
const affectedProducts = ref<AffectedProduct[]>([]);

watch(visible, (isVisible) => {
    if (isVisible) {
        deleteError.value = '';
        affectedProducts.value = [];
    }
});

function handleDelete( force = false ) {
    if (!props.category) {
        return;
    }
    deleteError.value = '';
    processing.value = true;

    router.delete(CategoryController.destroy(props.category.id).url, {
        data: { force },
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Category deleted', life: 3000 });
            affectedProducts.value = [];
            forceDialogVisible.value = false;
            visible.value = false;
            emit('deleted');
        },
        onError: (errors) => {
            if (errors.affected_products) {
                try {
                    affectedProducts.value = JSON.parse(errors.affected_products);
                } catch {
                    affectedProducts.value = [];
                }
            }
            deleteError.value = errors.delete ?? 'Delete failed. Please try again.';
            toast.add({ severity: 'error', summary: 'Delete failed', life: 3000 });
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <DeleteDialog v-model:visible="visible" :processing="processing" @confirm="handleDelete()" :error="deleteError">
        <p>
            Are you sure you want to delete the category
            <strong class="text-foreground">{{ category?.name }}</strong>?
            This action cannot be undone.
        </p>
        <p v-if="deleteError.length" class="flex mt-3 justify-end ml-auto">
            <Button label="View affected products" text size="small" class="self-start p-0 underline" severity="danger" @click="forceDialogVisible = true" />
        </p>
    </DeleteDialog>

    <ForceDeleteCategoryDialog
        v-model:visible="forceDialogVisible"
        :affected-products="affectedProducts"
        :processing="processing"
        @confirm="handleDelete(true)"
    />
</template>
