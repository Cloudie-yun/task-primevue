<script setup lang="ts">
import { ExclamationTriangle, ExclamationCircle } from '@primeicons/vue';

const props = withDefaults(
    defineProps<{
        processing?: boolean;
        header?: string;
        dialogStyle?: Record<string, string>;
        deleteLabel?: string;
        error?: string;
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
        <div class="flex items-start gap-3 mb-5">
            <ExclamationTriangle :size="32" class="text-red-500" />
            <div class="text-sm text-muted-foreground">
                <slot />
            </div>
        </div>
        <Message
            v-if="props.error"
            severity="error"
            variant="text"
            closable
        >
            <template #icon>
                <ExclamationCircle :size="24"  />
            </template>

            {{ props.error }}
        </Message>
        <template #footer>
            <Button
                label="Cancel"
                severity="secondary"
                variant="outlined"
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