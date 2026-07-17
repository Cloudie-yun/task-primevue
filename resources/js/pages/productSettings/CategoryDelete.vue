<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/Product/CategoryController';
import DeleteDialog from '@/components/DeleteDialog.vue';
import type { Category } from '@/types/product-settings';

const visible = defineModel<boolean>('visible', { default: false });
const props = defineProps<{ category: Category | null }>();
const emit = defineEmits<{ deleted: [] }>();

const toast = useToast();
const processing = ref(false);

function handleDelete() {
    if (!props.category) {
        return;
    }

    processing.value = true;

    router.delete(CategoryController.destroy(props.category.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Category deleted', life: 3000 });
            visible.value = false;
            emit('deleted');
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Delete failed', life: 3000 });
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <DeleteDialog v-model:visible="visible" :processing="processing" @confirm="handleDelete">
        <p>
            Are you sure you want to delete the category
            <strong class="text-foreground">{{ category?.name }}</strong>?
            This action cannot be undone.
        </p>
    </DeleteDialog>
</template>
