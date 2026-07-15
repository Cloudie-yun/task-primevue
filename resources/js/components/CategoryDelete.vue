<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/Product/CategoryController';
import type { Category } from '@/types/product-settings';

// Icons
import { ExclamationTriangle } from '@primeicons/vue';

const visible = defineModel<boolean>('visible', { default: false });

const props = defineProps<{
    category: Category | null;
}>();

const emit = defineEmits<{
    deleted: [];
}>();

const toast = useToast();
const processing = ref(false);

function handleDelete() {
    if (!props.category) return;

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
    <Dialog
        v-model:visible="visible"
        modal
        header="Confirm Deletion"
        :style="{ width: '26rem' }"
    >
        <div class="flex items-center gap-3">
            <ExclamationTriangle class="text-red-500" :size="32" />
            <p class="text-sm text-muted-foreground">
                Are you sure you want to delete the category
                <strong class="text-foreground">{{ category?.name }}</strong>?
                This action cannot be undone.
            </p>
        </div>

        <template #footer>
            <Button
                label="Cancel"
                severity="secondary"
                variant="text"
                :disabled="processing"
                @click="visible = false"
            />
            <Button
                label="Delete"
                severity="danger"
                :loading="processing"
                @click="handleDelete"
            />
        </template>
    </Dialog>
</template>