<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { Plus, Question, QuestionCircle, Times } from '@primeicons/vue';
import { ArrowDownAZ, ArrowUpAZ } from '@lucide/vue';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import { useToast } from 'primevue/usetoast';
import { nextTick, ref, watch } from 'vue';
import { z } from 'zod';
import VariantTypeController from '@/actions/App/Http/Controllers/Product/VariantTypeController';
import CreateDialogForm, { type CreateDialogSubmitEvent } from '@/components/CreateDialogForm.vue';
import Label from '@/components/ui/label/Label.vue';

const visible = defineModel<boolean>('visible', { default: false });

const emit = defineEmits<{ created: [name: string] }>();
const popover = ref();
const toast = useToast();
const MAX_VALUES = 30;

const schema = z.object({
    name: z.string().min(1, 'Variant type name is required').max(255, 'Name must be 255 characters or fewer'),
    generates_variants: z.boolean(),
});

const resolver = zodResolver(schema);
const processing = ref(false);
const values = ref<string[]>([]);
const newValue = ref('');
const valuesError = ref('');
const serverErrors = ref<Record<string, string>>({});

const editingIndex = ref<number | null>(null);
const editingDraft = ref('');
const editInputRefs = ref<Record<number, HTMLInputElement | null>>({});

watch(visible, (isVisible) => {
    if (isVisible) {
        values.value = [];
        newValue.value = '';
        valuesError.value = '';
        serverErrors.value = {};
        editingIndex.value = null;
    }
});

const sortDirection = ref<'asc' | 'desc'>('asc');

function sortAlphabetically() {
    const sortList = values.value;

    sortList.sort((a, b) => {
        const result = a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
        return sortDirection.value === 'asc' ? result : -result;
    });

    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
}

function addValue() {
    const trimmed = newValue.value.trim();
    if (!trimmed) return;

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
    if (editingIndex.value === index) editingIndex.value = null;
    values.value.splice(index, 1);
}

function startEditing(index: number) {
    editingIndex.value = index;
    editingDraft.value = values.value[index];
    nextTick(() => editInputRefs.value[index]?.focus());
}

function commitEdit(index: number) {
    const trimmed = editingDraft.value.trim();

    if (!trimmed) {
        // empty edit just removes the value rather than leaving a blank entry
        removeValue(index);
        return;
    }

    const isDuplicateElsewhere = values.value.some(
        (v, i) => i !== index && v.toLowerCase() === trimmed.toLowerCase(),
    );

    if (isDuplicateElsewhere) {
        toast.add({ severity: 'warn', summary: 'That value already exists', life: 3000 });
        editingDraft.value = values.value[index]; // revert draft, keep editing open so they can fix it
        return;
    }

    values.value[index] = trimmed;
    editingIndex.value = null;
}

function cancelEdit() {
    editingIndex.value = null;
}

function handleSubmit({ valid, values: formValues }: CreateDialogSubmitEvent) {
    serverErrors.value = {};

    if (values.value.length === 0) {
        valuesError.value = 'Add at least one value';
        return;
    }
    if (!valid) return;

    processing.value = true;

    router.post(
        VariantTypeController.store().url,
        { name: formValues.name, generates_variants: formValues.generates_variants, values: values.value },
        {
            preserveScroll: true,

            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Variant type created', life: 3000 });
                visible.value = false;
                emit('created', formValues.name);
            },
            onError: (errors) => {
                serverErrors.value = errors;
                const firstErrorKey = Object.keys(errors)[0];
                const detail = firstErrorKey ? errors[firstErrorKey] : 'Please check the form and try again.';
                toast.add({ severity: 'error', summary: 'Creation failed', detail, life: 5000 });

                const valuesFieldError = Object.entries(errors).find(
                    ([key]) => key === 'values' || key.startsWith('values.'),
                );
                if (valuesFieldError) valuesError.value = valuesFieldError[1];
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

const toggle = (event: any) =>{
    popover.value.toggle(event);
}
</script>

<template>
    <CreateDialogForm
        v-model:visible="visible"
        header="Add Variant Type"
        :dialog-style="{ width: '28rem' }"
        form-class="space-y-6 py-6"
        :resolver="resolver"
        :initial-values="{ name: '', generates_variants: false}"
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
                            <InputText v-model="newValue" @keydown.enter.prevent="addValue" :invalid="!!valuesError" />
                            <label>Values</label>
                        </FloatLabel>
                        <InputGroupAddon @click="addValue">
                            <Plus />
                        </InputGroupAddon>
                    </InputGroup>
                </div>

                <template v-if="values.length">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-muted-foreground">{{ values.length }} / {{ MAX_VALUES }} values added</span>
                        <div class="flex items-center justify-center gap-2">
                            <Button 
                                text 
                                size="small" 
                                @click="sortAlphabetically" 
                                v-tooltip="sortDirection === 'asc' ? 'Sort A-Z' : 'Sort Z-A'"
                            >
                                <ArrowUpAZ v-if="sortDirection === 'asc'" class="size-4" />
                                <ArrowDownAZ v-else class="size-4" />                             
                            </Button>
                        </div>
                    </div>

                    <draggable
                        v-model="values"
                        :item-key="(el: string) => el"
                        class="space-y-1"
                        ghost-class="opacity-40"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="flex w-full min-w-0 items-center gap-2 rounded-md border border-surface-200 px-2 py-1.5 dark:border-surface-700">
                                <span class="drag-handle shrink-0 cursor-grab select-none text-muted-color">⠿</span>
                                <span class="w-4 shrink-0 text-right text-sm tabular-nums opacity-70">{{ index }}</span>

                                <InputText
                                    v-if="editingIndex === index"
                                    :ref="(el: any) => (editInputRefs[index] = el?.$el ?? el)"
                                    v-model="editingDraft"
                                    size="small"
                                    class="flex-1"
                                    @blur="commitEdit(index)"
                                    @keydown.enter.prevent="commitEdit(index)"
                                    @keydown.esc.prevent="cancelEdit"
                                />
                                <span v-else class="flex-1 cursor-text truncate text-sm" @click="startEditing(index)">
                                    {{ element }}
                                </span>

                                <Times class="shrink-0 cursor-pointer text-xs" @click="removeValue(index)" />
                            </div>
                        </template>
                    </draggable>
                </template>
                    <div class="flex justify-end">
                        <span class="text-xs text-muted-color">Drag the handle to reorder · click a value to edit</span>
                    </div>

                <Message v-if="valuesError" severity="error" size="small" variant="simple">
                    {{ valuesError }}
                </Message>
            </div>

            <div class="flex items-center gap-2">
                <Checkbox  name="generates_variants" binary inputId="generates_variants" />

                <label for="generates_variants" class="text-sm font-medium" >
                    Include this type when generating product variants
                </label>
                <Button
                    variant="text"
                    rounded
                    size="small"
                    class="h-6 w-6 text-muted-foreground"
                    @click="toggle"
                >
                    <QuestionCircle />
                </Button>           

                <Popover ref="popover" class="max-w-sm">
                    <div class="space-y-2">
                        <p class="font-semibold">What happens when this is unchecked?</p>

                        <p class="text-sm text-muted-foreground">
                            The values of this type are treated as descriptive product tags rather than purchasable variants.
                        </p>

                        <ul class="text-sm space-y-1 text-muted-foreground" style="list-style-type: circle;">
                            <li>• No separate SKU is created</li>
                            <li>• No separate stock quantity is tracked</li>
                            <li>• No separate price combination is generated</li>
                            <li>• The values can still be displayed on the product page</li>
                        </ul>

                        <div class="rounded-md bg-surface-100 p-2 text-xs dark:bg-surface-800">
                            <strong>Example:</strong> “Material: Cotton” is usually a tag, while “Size” and “Color” are typically variant-generating types.
                        </div>
                    </div>
                </Popover>
            </div>
        </template>
    </CreateDialogForm>
</template>