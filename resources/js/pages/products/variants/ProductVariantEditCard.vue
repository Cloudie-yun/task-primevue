<script setup lang="ts">
import { Trash, Undo, Times, ExclamationTriangle } from '@primeicons/vue';
import { computed } from 'vue';
import type { VariantRow } from './ProductVariantsTable.vue';

const props = defineProps<{
    variant: VariantRow;
    working: { additional_price: number; stock: number; markedForDelete: boolean };
    errors: Record<string, string>;
    attemptedSubmit: boolean;
    isDirty: boolean;
}>();

const emit = defineEmits<{ cancel: []; toggleDelete: []; close: [] }>();

const hasErrors = computed(() => props.attemptedSubmit && Object.keys(props.errors).length > 0);
const errorMessages = computed(() => Object.values(props.errors));

function variantLabel(variant: VariantRow) {
    return variant.values.map((v) => v.value).join(' / ');
}
</script>

<template>
    <Card :class="hasErrors ? 'border-2 border-red-400! dark:border-red-700!' : ''">
        <template #content>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-xs uppercase text-surface-500">{{ variantLabel(variant) }}</div>
                    <div class="font-medium text-muted-foreground">{{ variant.sku }}</div>
                </div>
                <Button severity="secondary" variant="text" rounded @click="emit('close')"><Times /></Button>
            </div>

            <Message v-if="hasErrors" severity="error" :closable="false" class="mt-3">
                <div class="flex items-start gap-2">
                    <ExclamationTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                    <div>
                        <div class="font-medium">This variant has {{ errorMessages.length }} issue{{ errorMessages.length > 1 ? 's' : '' }}:</div>
                        <ul class="mt-1 list-disc pl-4">
                            <li v-for="(msg, i) in errorMessages" :key="i">{{ msg }}</li>
                        </ul>
                    </div>
                </div>
            </Message>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-medium text-muted-foreground">Additional Price</label>
                    <InputNumber
                        v-model="working.additional_price"
                        mode="currency" currency="MYR" locale="en-US" fluid
                        :disabled="working.markedForDelete"
                        :invalid="attemptedSubmit && !!errors.additional_price"
                    />
                    <p v-if="attemptedSubmit && errors.additional_price" class="text-xs text-red-600">{{ errors.additional_price }}</p>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-muted-foreground">Stock</label>
                    <InputNumber
                        v-model="working.stock"
                        fluid
                        :disabled="working.markedForDelete"
                        :invalid="attemptedSubmit && !!errors.stock"
                    />
                    <p v-if="attemptedSubmit && errors.stock" class="text-xs text-red-600">{{ errors.stock }}</p>
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <Button v-if="isDirty && !working.markedForDelete" label="Revert" severity="secondary" variant="text" @click="emit('cancel')" />
                <Button
                    :label="working.markedForDelete ? 'Undo delete' : 'Mark for deletion'"
                    :severity="working.markedForDelete ? 'secondary' : 'danger'"
                    variant="outlined"
                    @click="emit('toggleDelete')"
                >
                    <template #icon><Undo v-if="working.markedForDelete" /><Trash v-else /></template>
                </Button>
            </div>
        </template>
    </Card>
</template>