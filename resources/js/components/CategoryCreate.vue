<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Form as PVForm } from '@primevue/forms';
import CategoryController from '@/actions/App/Http/Controllers/Product/CategoryController';
import {z} from 'zod';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import { ref } from 'vue';
// Icons
import { Spinner } from '@primeicons/vue';

const visible = defineModel<boolean>('visible', { default: false });

const emit = defineEmits<{
    created: [];
}>();

const categorySchema = z.object({
    name: z.string().min(1, 'Category name is required').max(255),
    description: z.string().max(1000,'Category Description must not exceed 1000 characters').optional().or(z.literal('')),
    is_active: z.boolean().optional(),
});

const resolver = zodResolver(categorySchema);
const processing = ref(false);
const serverErrors = ref<Record<string, string>>({});

function handleSubmit({ valid, values }: { valid: boolean; values: Record<string, any> }) {
    if (!valid) return;

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
    <Dialog
        v-model:visible="visible"
        modal
        header="Add Category"
        class=" w-full h-max max-w-2xl"
    >
        <PVForm
            v-slot="$form"
            :resolver="resolver"
            :initial-values="{ name:'', description:'', is_active: true }"
            @submit="handleSubmit"
            class="space-y-6 py-5"
        >
            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <InputText
                        id="name"
                        name="name"
                        fluid
                        autocomplete="off"
                    />
                    <label for="name">Category name</label>
                </FloatLabel>
                <Message severity="error" v-if="$form.name?.invalid" size="small" variant="simple">
                    {{ $form.name.error?.message }}
                </Message>
                <Message severity="error" v-if="serverErrors.name" :life="3000" size="small" variant="simple">
                    {{ serverErrors.name }}
                </Message>
            </div>

            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <Textarea
                        id="description"
                        name="description"
                        fluid
                        rows="5"
                    />
                    <label for="description" >Description</label>
                </FloatLabel>
                <Message severity="error" v-if="$form.description?.invalid" size="small" variant="simple">
                    {{ $form.description.error?.message }}
                </Message>
                <Message severity="error" v-if="serverErrors.description" :life="3000" size="small" variant="simple">
                    {{ serverErrors.description }}
                </Message>
            </div>

            <div class="flex items-center gap-2">
                <ToggleSwitch inputId="is_active" name="is_active" :modelValue="true" />
                <label for="is_active">Active</label>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button
                    type="button"
                    label="Cancel"
                    severity="secondary"
                    variant="text"
                    @click="visible = false"
                />
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" class="animate-spin" />
                    Save
                </Button>
            </div>
        </PVForm>
    </Dialog>
</template>