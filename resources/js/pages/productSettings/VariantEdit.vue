<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import draggable from 'vuedraggable';
import { nextTick, computed, ref, watch } from 'vue';
import VariantTypeController from '@/actions/App/Http/Controllers/Product/VariantTypeController';
import ForceDeleteValuesDialog from './ForceDeleteVariant.vue';
import type { VariantType } from '@/types/product-settings';
import { Trash, Plus, Undo, QuestionCircle } from '@primeicons/vue';
import { ArrowDownAZ, ArrowUpAZ } from '@lucide/vue';

const visible = defineModel<boolean>('visible', { default: false });
const props = defineProps<{ variantType: VariantType | null; prefillValue?: string | null }>();
const emit = defineEmits<{ updated: [] }>();
const popover = ref();
const toast = useToast();

interface WorkingValue {
    id: number | null;
    value: string;
    markedForDelete: boolean;
}

interface BlockedValue {
    id: number;
    value: string;
    variant_count: number;
    skus: string[];
    attribute_product_count: number;
    attribute_product_names: string[];
}

const typeName = ref('');
const generates_variants = ref(false);
const values = ref<WorkingValue[]>([]);
const newValue = ref('');
const saving = ref(false);
const editingIndex = ref<number | null>(null);
const editingDraft = ref('');
const editInputRefs = ref<Record<number, HTMLInputElement | null>>({});
const saveError = ref('');
const blockedValues = ref<BlockedValue[]>([]);
const forceDialogVisible = ref(false);

function snapshotFromType(t: VariantType | null) {
    typeName.value = t?.name ?? '';
    generates_variants.value = t?.generates_variants ?? false;
    values.value = (t?.values ?? []).map((v) => ({ id: v.id, value: v.value, markedForDelete: false }));

    if (props.prefillValue?.trim()) {
        values.value.push({ id: null, value: props.prefillValue.trim(), markedForDelete: false });
    }
}

const toggle = (event: any) =>{
    popover.value.toggle(event);
}

watch(() => props.variantType, snapshotFromType, { immediate: true });

watch(visible, (isVisible) => {
    if (!isVisible) {
        newValue.value = '';
    } else {
        saveError.value = '';
        blockedValues.value = [];
        snapshotFromType(props.variantType);
    }
});

const sortDirection = ref<'asc' | 'desc'>('asc');
function sortAlphabetically() {
    const active = values.value.filter((v) => !v.markedForDelete);
    const deleted = values.value.filter((v) => v.markedForDelete);

    active.sort((a, b) => {
        const result = a.value.localeCompare(b.value, undefined, { numeric: true, sensitivity: 'base' });
        return sortDirection.value === 'asc' ? result : -result;
    });

    values.value = [...active, ...deleted];
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
}
const isDirty = computed(() => {
    if (!props.variantType) return false;
    if (typeName.value.trim() !== props.variantType.name) return true;
    if (generates_variants.value !== props.variantType.generates_variants) return true;

    const original = props.variantType.values ?? [];
    if (values.value.length !== original.length) return true;

    return values.value.some((v, index) => {
        const o = original[index];
        return !o || v.id !== o.id || v.value.trim() !== o.value || v.markedForDelete;
    });
});

function addValue() {
    const trimmed = newValue.value.trim();
    if (!trimmed) return;

    const isDuplicate = values.value.some(
        (v) => !v.markedForDelete && v.value.trim().toLowerCase() === trimmed.toLowerCase(),
    );
    if (isDuplicate) {
        toast.add({ severity: 'warn', summary: 'That value already exists', life: 3000 });
        return;
    }

    values.value.push({ id: null, value: trimmed, markedForDelete: false });
    newValue.value = '';
}

function toggleDelete(item: WorkingValue) {
    // an unsaved row (never persisted) can just be dropped outright, no "undo" needed
    if (item.id === null) {
        values.value = values.value.filter((v) => v !== item);
        return;
    }
    item.markedForDelete = !item.markedForDelete;
}

function discard() {
    snapshotFromType(props.variantType);
}

function startEditing(index: number) {
    if (values.value[index].markedForDelete) return;
    editingIndex.value = index;
    editingDraft.value = values.value[index].value;
    nextTick(() => editInputRefs.value[index]?.focus());
}

function commitEdit(index: number) {
    const trimmed = editingDraft.value.trim();

    if (!trimmed) {
        // reject empty edits rather than saving a blank value
        editingDraft.value = values.value[index].value;
        editingIndex.value = null;
        return;
    }

    const isDuplicateElsewhere = values.value.some(
        (v, i) => i !== index && !v.markedForDelete && v.value.toLowerCase() === trimmed.toLowerCase(),
    );

    if (isDuplicateElsewhere) {
        toast.add({ severity: 'warn', summary: 'That value already exists', life: 3000 });
        return; // keep editing open so they can fix it
    }

    values.value[index].value = trimmed;
    editingIndex.value = null;
}

function cancelEdit(index: number) {
    editingDraft.value = values.value[index].value;
    editingIndex.value = null;
}

function save(force = false) {
    if (!props.variantType) return;
    
    const trimmedName = typeName.value.trim();
    if (!trimmedName) {
        toast.add({ severity: 'warn', summary: 'Type name is required', life: 3000 });
        return;
    }

    const payloadValues = values.value
        .filter((v) => !v.markedForDelete)
        .map((v) => ({ id: v.id, value: v.value.trim() }));

    if (payloadValues.length === 0) {
        toast.add({ severity: 'warn', summary: 'At least one value is required', life: 3000 });
        return;
    }

    saveError.value = '';    
    saving.value = true;

    router.patch(
        VariantTypeController.update(props.variantType.id).url,
        { name: trimmedName, generates_variants: generates_variants.value, values: payloadValues, force },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Variant type saved', life: 3000 });
                forceDialogVisible.value = false;
                blockedValues.value = [];
                visible.value = false; 
                emit('updated');
            },
            onError: (errors) => {
                if (errors.blocked_values) {
                    try {
                        blockedValues.value = JSON.parse(errors.blocked_values);
                    } catch {
                        blockedValues.value = [];
                    }
                }

                saveError.value = errors.values ?? errors.name ?? 'Please review the form.';
                toast.add({ severity: 'error', summary: 'Save failed', detail: saveError.value, life: 5000 });
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function confirmForceDelete() {
    save(true);
}

</script>

<template>
    <Dialog 
        v-model:visible="visible" 
        modal 
        header="Edit Variant Type" 
        :style="{ width: '30rem' }" 
        :pt="{
            content: { class:'max-h-[500px] overflow-y-auto'},
            footer: { class:'flex-col! justify-between! gap-3!'},
        }"
    >
        <div v-if="variantType" class="space-y-4 py-4">
            <div class="grid gap-1.5">
                <label class="text-sm font-medium">Type name</label>
                <InputText v-model="typeName" fluid />
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium">Values</label>
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
                <draggable v-model="values" item-key="id" class="space-y-1" ghost-class="opacity-40" handle=".drag-handle" >
                    <template #item="{ element: option, index }">
                        <div
                            class="flex w-full min-w-0 items-center gap-2 rounded-md border border-surface-200 px-2 py-1.5 dark:border-surface-700"
                            :class="{ 'opacity-40': option.markedForDelete }"
                        >
                            <span class="drag-handle shrink-0 cursor-grab select-none text-muted-color">⠿</span>
                            <span class="w-4 shrink-0 text-right text-sm tabular-nums opacity-70">{{ index }}</span>

                            <InputText
                                v-if="editingIndex === index"
                                :ref="(el: any) => (editInputRefs[index] = el?.$el ?? el)"
                                v-model="editingDraft"
                                class="flex-1"
                                size="small"
                                @blur="commitEdit(index)"
                                @keydown.enter.prevent="commitEdit(index)"
                                @keydown.esc.prevent="cancelEdit(index)"
                            />
                            <span
                                v-else
                                class="flex-1 truncate text-sm"
                                :class="option.markedForDelete ? 'cursor-not-allowed' : 'cursor-text'"
                                @click="startEditing(index)"
                            >
                                {{ option.value }}
                            </span>
                            <Button
                                :severity="option.markedForDelete ? 'secondary' : 'danger'"
                                text
                                rounded
                                size="small"
                                @click.stop="toggleDelete(option)"
                                @mousedown.stop
                            >
                                <Undo v-if="option.markedForDelete" />
                                <Trash v-else />
                            </Button>
                        </div>
                    </template>
                </draggable>
                <div class="flex items-center justify-end">
                    <span class="text-xs text-muted-color">Drag the handle to reorder · click to edit</span>
                </div>
                <InputGroup class="mt-2">
                    <InputText v-model="newValue" placeholder="Add a value" fluid @keydown.enter.prevent="addValue" />
                    <InputGroupAddon>
                        <Button variant="text" rounded @click="addValue">
                            <Plus />
                        </Button>
                    </InputGroupAddon>
                </InputGroup>
            </div>
            <Message v-if="saveError" severity="error" variant="text" closable>
                <div class="flex flex-col gap-1">
                    <span>{{ saveError }}</span>
                    <Button
                        v-if="blockedValues.length"
                        label="View affected product variants"
                        text
                        size="small"
                        severity="danger"
                        class="self-start p-0 underline"
                        @click="forceDialogVisible = true"
                    />
                </div>
            </Message>
        </div>
        <template #footer>
            <div class="flex items-center gap-2">
                <Checkbox v-model="generates_variants" binary inputId="generates_variants" />

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
            <div class="shrink-0 flex justify-end gap-[--p-dialog-footer-gap] p-[--p-dialog-footer-padding]" >
                <Button label="Discard" severity="secondary" variant="text" :disabled="!isDirty || saving" @click="discard" />
                <Button label="Save changes" :loading="saving" :disabled="!isDirty" @click="() => save()" />
            </div>
        </template>
    </Dialog>
    <ForceDeleteValuesDialog
        v-model:visible="forceDialogVisible"
        :blocked-values="blockedValues"
        :processing="saving"
        @confirm="confirmForceDelete"
    />
</template>