<script setup lang="ts">
import { ExclamationTriangle } from '@primeicons/vue';

const props = withDefaults(
    defineProps<{
        processing?: boolean;
        header?: string;
        dialogStyle?: Record<string, string>;
        deleteLabel?: string;
    }>(),
    {
        processing: false,
        header: 'Confirm Deletion',
        dialogStyle: () => ({ width: '26rem' }),
        deleteLabel: 'Delete',
    },
);

const visible = defineModel<boolean>('visible', { default: false });
const emit = defineEmits<{
    confirm: [];
}>();
</script>

<template>
    <Dialog v-model:visible="visible" modal :header="props.header" :style="props.dialogStyle">
        <div class="flex items-start gap-3">
            <ExclamationTriangle :size="32" class="text-red-500" />
            <div class="text-sm text-muted-foreground">
                <slot />
            </div>
        </div>

        <template #footer>
            <Button
                label="Cancel"
                severity="secondary"
                variant="text"
                :disabled="props.processing"
                @click="visible = false"
            />
            <Button
                :label="props.deleteLabel"
                severity="danger"
                :loading="props.processing"
                @click="emit('confirm')"
            />
        </template>
    </Dialog>
</template>
