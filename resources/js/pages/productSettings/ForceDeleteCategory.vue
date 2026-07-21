<script setup lang="ts">
import { ExclamationTriangle } from '@primeicons/vue';

interface AffectedProduct {
    id: number;
    name: string;
}

const visible = defineModel<boolean>('visible', { default: false });
defineProps<{
    affectedProducts: AffectedProduct[];
    processing? : boolean;
}>();
const emit = defineEmits<{ confirm: [] }>();
</script>

<template>
    <Dialog v-model:visible="visible" modal header="Confirm force delete" :style="{ width: '32rem' }">
        <div class="space-y-4 py-4">
            <Message severity="warn" variant="outlined">
                <template #icon>
                    <ExclamationTriangle />
                </template>
                This category is currently assigned to
                <strong>{{ affectedProducts.length }}</strong>
                product{{ affectedProducts.length === 1 ? '' : 's' }}.
            </Message>
            <div class="text-sm text-muted-color space-y-1">
                <p>Deleting this category will:</p>
                <ul class="ml-5 list-disc">
                    <li>Delete the category.</li>
                    <li>Remove the category assignment from affected products.</li>
                    <li>The products themselves will <strong>not</strong> be deleted.</li>
                </ul>
            </div>
            <div class="max-h-56 overflow-y-auto rounded-md border border-surface-200 dark:border-surface-700">
                <ul class="divide-y divide-surface-200 dark:divide-surface-700">
                    <li v-for="product in affectedProducts" :key="product.id" class="px-3 py-2">
                        {{ product.name }}
                    </li>
                </ul>
            </div>
        </div>
        <template #footer>
            <Button label="Cancel" severity="secondary" variant="outlined" @click="visible = false" />
            <Button label="Delete Category" severity="danger" :loading="processing" @click="emit('confirm')" />
        </template>
    </Dialog>
</template>