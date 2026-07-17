<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import { ref, watch } from 'vue';
import { z } from 'zod';
import CategoryController from '@/actions/App/Http/Controllers/Product/CategoryController';
import CreateDialogForm, { type CreateDialogSubmitEvent } from '@/components/CreateDialogForm.vue';

const visible = defineModel<boolean>('visible', { default: false });

const props = defineProps<{
    initialName?: string;
}>();

const emit = defineEmits<{
    created: [];
}>();

const schema = z.object({
    name: z.string().min(1, 'Category name is required').max(255),
    description: z
        .string()
        .max(1000, 'Category Description must not exceed 1000 characters')
        .optional()
        .or(z.literal('')),
    is_active: z.boolean().optional(),
});

const resolver = zodResolver(schema);
const processing = ref(false);
const serverErrors = ref<Record<string, string>>({});

watch(visible, (isVisible) => {
    if (isVisible) {
        serverErrors.value = {};
    }
});

function handleSubmit({ valid, values }: CreateDialogSubmitEvent) {
    if (!valid) {
        return;
    }

    processing.value = true;
    serverErrors.value = {};

    router.post(CategoryController.store().url, values, {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
            emit('created');
        },
        onError: (errors) => {
            serverErrors.value = errors;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <CreateDialogForm
        v-model:visible="visible"
        header="Add Category"
        dialog-class="w-full h-max max-w-2xl"
        :resolver="resolver"
        :initial-values="{ name:props?.initialName ?? '', description: '', is_active: true }"
        :processing="processing"
        @submit="handleSubmit"
    >
        <template #default="{ form }">
            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <InputText id="name" name="name" fluid autocomplete="off" />
                    <label for="name">Category name</label>
                </FloatLabel>
                <Message v-if="form.name?.invalid" severity="error" size="small" variant="simple">
                    {{ form.name.error?.message }}
                </Message>
                <Message v-if="serverErrors.name" severity="error" size="small" variant="simple">
                    {{ serverErrors.name }}
                </Message>
            </div>

            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <Textarea id="description" name="description" fluid rows="5" />
                    <label for="description">Description</label>
                </FloatLabel>
                <Message v-if="form.description?.invalid" severity="error" size="small" variant="simple">
                    {{ form.description.error?.message }}
                </Message>
                <Message v-if="serverErrors.description" severity="error" size="small" variant="simple">
                    {{ serverErrors.description }}
                </Message>
            </div>

            <div class="flex items-center gap-2">
                <ToggleSwitch input-id="is_active" name="is_active" :model-value="true" />
                <label for="is_active">Active</label>
            </div>
        </template>
    </CreateDialogForm>
</template>
