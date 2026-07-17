<script lang="ts">
export interface CreateDialogSubmitEvent {
    valid: boolean;
    values: Record<string, any>;
}
</script>

<script setup lang="ts">
import { Form as PVForm } from '@primevue/forms';
import { ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        header: string;
        resolver: any;
        initialValues: Record<string, any>;
        processing?: boolean;
        dialogClass?: string;
        dialogStyle?: Record<string, string>;
        formClass?: string;
        saveLabel?: string;
    }>(),
    {
        processing: false,
        dialogClass: '',
        formClass: 'space-y-6 py-5',
        saveLabel: 'Save',
    },
);

const visible = defineModel<boolean>('visible', { default: false });
const emit = defineEmits<{
    submit: [event: CreateDialogSubmitEvent];
}>();

const formKey = ref(0);

watch(visible, (isVisible) => {
    if (isVisible) {
        formKey.value += 1;
    }
});
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :header="props.header"
        :class="props.dialogClass"
        :style="props.dialogStyle"
    >
        <PVForm
            :key="formKey"
            v-slot="$form"
            :resolver="props.resolver"
            :initial-values="props.initialValues"
            :class="props.formClass"
            @submit="emit('submit', $event)"
        >
            <slot :form="$form" />

            <div class="flex justify-end gap-2 pt-2">
                <Button
                    type="button"
                    label="Cancel"
                    severity="secondary"
                    variant="text"
                    @click="visible = false"
                />
                <Button type="submit" :label="props.saveLabel" :loading="props.processing" />
            </div>
        </PVForm>
    </Dialog>
</template>
