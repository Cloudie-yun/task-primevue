<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Product/ProductController';
import type { Product } from '@/types/product';
import { ExclamationTriangle } from '@primeicons/vue';

const visible = defineModel<boolean>('visible', { default: false });
const props = defineProps<{ product: Product | null }>();
const emit = defineEmits<{ deleted: [] }>();

const toast = useToast();
const processing = ref(false);

function handleDelete() {
    if (!props.product) return;
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
    <Dialog v-model:visible="visible" modal header="Confirm Deletion" :style="{ width: '26rem' }">
        <div class="flex items-start gap-3">
            <ExclamationTriangle :size="32" class="text-red-500" />
            <p class="text-sm text-muted-foreground">
                Delete <strong class="text-foreground">{{ product?.name }}</strong>?
                This product will be moved to trash and can be restored later if needed.
            </p>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" variant="text" :disabled="processing" @click="visible = false" />
            <Button label="Delete" severity="danger" :loading="processing" @click="handleDelete" />
        </template>
    </Dialog>
</template>