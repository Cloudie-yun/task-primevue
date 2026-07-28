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
    <div
        class="flex flex-wrap items-center gap-3 rounded-lg border p-3"
        :class="hasErrors ? 'border-red-400! dark:border-red-700!' : ''"
    >
        <!-- Identity -->
        <div class="w-max">
            <div class="truncate text-sm font-medium">{{ variantLabel(variant) }}</div>
            <div class="truncate text-xs text-muted-foreground">{{ variant.sku }}</div>
        </div>

        <!-- Fields -->
        <div class="flex items-start gap-5 flex-1 justify-end mr-5">
            <div class="flex flex-col gap-1.5">
                <InputNumber
                    v-model="working.additional_price"
                    mode="currency" currency="MYR" locale="en-US"
                    :disabled="working.markedForDelete"
                    :invalid="attemptedSubmit && !!errors.additional_price"
                    :title="attemptedSubmit ? errors.additional_price : undefined"
                />
                <Message v-if="attemptedSubmit && errors.additional_price" severity="error" variant="simple" size="small">{{ errors.additional_price }}</Message>
            </div>
            
            <div class="flex flex-col gap-1.5">
                <InputNumber
                    v-model="working.stock"
                    :disabled="working.markedForDelete"
                    :invalid="attemptedSubmit && !!errors.stock"
                    :title="attemptedSubmit ? errors.stock : undefined"
                />
                <Message v-if="attemptedSubmit && errors.stock" severity="error" variant="simple" size="small">{{ errors.stock }}</Message>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-1">
            <Button v-if="isDirty && !working.markedForDelete" severity="secondary" variant="text" size="small" @click="emit('cancel')" >
                <Undo />
            </Button>
            <Button
                :severity="working.markedForDelete ? 'secondary' : 'danger'"
                variant="text"
                size="small"
                rounded
                @click="emit('toggleDelete')"
            >
                <Undo v-if="working.markedForDelete" />
                <Trash v-else />
            </Button>
            <Button severity="secondary" variant="text" size="small" rounded @click="emit('close')"><Times /></Button>
        </div>
    </div>
</template>