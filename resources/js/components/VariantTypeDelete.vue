<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import VariantTypeController from '@/actions/App/Http/Controllers/Product/VariantTypeController';
import type { VariantType } from '@/types/product-settings';

const visible = defineModel<boolean>('visible', { default: false });
const props = defineProps<{ type: VariantType | null }>();
const emit = defineEmits<{ deleted: [] }>();

const toast = useToast();
const processing = ref(false);

function handleDelete() {
    if (!props.type) return;
    processing.value = true;

    router.delete(VariantTypeController.destroy(props.type.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Variant type deleted', life: 3000 });
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
            <i class="pi pi-exclamation-triangle text-2xl text-red-500" />
            <p class="text-sm text-muted-foreground">
                Delete <strong class="text-foreground">{{ type?.name }}</strong> and all
                {{ type?.values?.length ?? 0 }} of its values? This cannot be undone.
            </p>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" variant="text" :disabled="processing" @click="visible = false" />
            <Button label="Delete" severity="danger" :loading="processing" @click="handleDelete" />
        </template>
    </Dialog>
</template>