<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Product/ProductController';
import DeleteDialog from '@/components/DeleteDialog.vue';
import type { Product } from '@/types/product';

const visible = defineModel<boolean>('visible', { default: false });
const props = defineProps<{ product: Product | null }>();
const emit = defineEmits<{ deleted: [] }>();

const toast = useToast();
const processing = ref(false);

function handleDelete() {
    if (!props.product) {
        return;
    }

    processing.value = true;

    router.delete(ProductController.destroy(props.product.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Product deleted', life: 3000 });
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
            Delete <strong class="text-foreground">{{ product?.name }}</strong>?
            This product will be moved to trash and can be restored later if needed.
        </p>
    </DeleteDialog>
</template>
