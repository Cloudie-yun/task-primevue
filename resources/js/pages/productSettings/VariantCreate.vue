<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Plus, Times } from '@primeicons/vue';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';
import { z } from 'zod';
import VariantTypeController from '@/actions/App/Http/Controllers/Product/VariantTypeController';
import CreateDialogForm, { type CreateDialogSubmitEvent } from '@/components/CreateDialogForm.vue';
import Label from '@/components/ui/label/Label.vue';

const visible = defineModel<boolean>('visible', { default: false });

const emit = defineEmits<{
    created: [];
}>();

const toast = useToast();
const MAX_VALUES = 30;

const schema = z.object({
    name: z
        .string()
        .min(1, 'Variant type name is required')
        .max(255, 'Name must be 255 characters or fewer'),
});

const resolver = zodResolver(schema);
const processing = ref(false);
const values = ref<string[]>([]);
const newValue = ref('');
const valuesError = ref('');
const serverErrors = ref<Record<string, string>>({});

watch(visible, (isVisible) => {
    if (isVisible) {
        values.value = [];
        newValue.value = '';
        valuesError.value = '';
        serverErrors.value = {};
    }
});

function addValue() {
    const trimmed = newValue.value.trim();

    if (!trimmed) {
        return;
    }

    if (trimmed.length > 255) {
        valuesError.value = 'Each value must be 255 characters or fewer';
        return;
    }

    if (values.value.length >= MAX_VALUES) {
        valuesError.value = `You can add up to ${MAX_VALUES} values`;
        return;
    }

    if (values.value.some((value) => value.toLowerCase() === trimmed.toLowerCase())) {
        valuesError.value = 'That value has already been added';
        return;
    }

    values.value.push(trimmed);
    newValue.value = '';
    valuesError.value = '';
}

function removeValue(index: number) {
    values.value.splice(index, 1);
}

function handleSubmit({ valid, values: formValues }: CreateDialogSubmitEvent) {
    serverErrors.value = {};

    if (values.value.length === 0) {
        valuesError.value = 'Add at least one value';
        return;
    }

    if (!valid) {
        return;
    }

    processing.value = true;

    router.post(
        VariantTypeController.store().url,
        { name: formValues.name, values: values.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Variant type created', life: 3000 });
                visible.value = false;
                emit('created');
            },
            onError: (errors) => {
                serverErrors.value = errors;

                const firstErrorKey = Object.keys(errors)[0];
                const detail = firstErrorKey ? errors[firstErrorKey] : 'Please check the form and try again.';

                toast.add({ severity: 'error', summary: 'Creation failed', detail, life: 5000 });

                const valuesFieldError = Object.entries(errors).find(
                    ([key]) => key === 'values' || key.startsWith('values.'),
                );

                if (valuesFieldError) {
                    valuesError.value = valuesFieldError[1];
                }
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <CreateDialogForm
        v-model:visible="visible"
        header="Add Variant Type"
        :dialog-style="{ width: '28rem' }"
        form-class="space-y-6 py-6"
        :resolver="resolver"
        :initial-values="{ name: '' }"
        :processing="processing"
        @submit="handleSubmit"
    >
        <template #default="{ form }">
            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <InputText id="name" name="name" fluid />
                    <label for="name">Name</label>
                </FloatLabel>
                <Message v-if="form.name?.invalid" severity="error" size="small" variant="simple">
                    {{ form.name.error?.message }}
                </Message>
                <Message v-else-if="serverErrors.name" severity="error" size="small" variant="simple">
                    {{ serverErrors.name }}
                </Message>
            </div>

            <div class="grid gap-1.5">
                <div class="grid">
                    <Label class="text-zinc-400 text-xs justify-end">Type a value and press Enter</Label>
                    <InputGroup>
                        <FloatLabel variant="on">
                            <InputText
                                v-model="newValue"
                                @keydown.enter.prevent="addValue"
                                :invalid="!!valuesError"
                            />
                            <label>Values</label>
                        </FloatLabel>
                        <InputGroupAddon @click="addValue">
                            <Plus />
                        </InputGroupAddon>
                    </InputGroup>
                </div>
                
                <div v-if="values.length" class="flex flex-wrap gap-2 pt-1">
                    <Tag v-for="(value, index) in values" :key="value" severity="secondary">
                        <span class="flex items-center gap-1.5">
                            {{ value }}
                            <Times class="cursor-pointer text-xs" @click="removeValue(index)" />
                        </span>
                    </Tag>
                </div>
                <Message v-if="valuesError" severity="error" size="small" variant="simple">
                    {{ valuesError }}
                </Message>
                <span v-else class="text-xs text-muted-foreground">
                    {{ values.length }} / {{ MAX_VALUES }} values added
                </span>
            </div>
        </template>
    </CreateDialogForm>
</template>
