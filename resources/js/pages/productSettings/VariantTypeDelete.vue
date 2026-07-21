<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';
import VariantTypeController from '@/actions/App/Http/Controllers/Product/VariantTypeController';
import DeleteDialog from '@/components/DeleteDialog.vue';
import type { VariantType } from '@/types/product-settings';

const visible = defineModel<boolean>('visible', { default: false });
const props = defineProps<{ type: VariantType | null }>();
const emit = defineEmits<{ deleted: [] }>();

const toast = useToast();
const processing = ref(false);
const deleteError = ref('');

watch(visible, (isVisible) => {
    if (isVisible) {
        deleteError.value = '';
    }
});

function handleDelete() {
    if (!props.type) return;

    deleteError.value = '';
    processing.value = true;

    router.delete(VariantTypeController.destroy(props.type.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Variant type deleted', life: 3000 });
            visible.value = false;
            emit('deleted');
        },
        onError: (errors) => {
            deleteError.value = errors.delete ?? 'Delete failed. Please try again.';
            toast.add({ severity: 'error', summary: 'Delete failed', detail: deleteError.value, life: 3000 });
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <DeleteDialog v-model:visible="visible" :processing="processing" @confirm="handleDelete" :error="deleteError">
        <p>
            Delete <strong class="text-foreground">{{ type?.name }}</strong> and all
            {{ type?.values?.length ?? 0 }} of its values? This cannot be undone.
        </p>
    </DeleteDialog>
</template>
