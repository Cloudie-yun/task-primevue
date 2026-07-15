<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { reactive, ref, watch } from 'vue';
import VariantTypeController from '@/actions/App/Http/Controllers/Product/VariantTypeController';
import VariantValueController from '@/actions/App/Http/Controllers/Product/VariantValueController';
import type { VariantType, VariantValue } from '@/types/product-settings';
import { Trash, Plus, Check } from '@primeicons/vue';

const visible = defineModel<boolean>('visible', { default: false });
const props = defineProps<{ type: VariantType | null }>();
const emit = defineEmits<{ changed: [] }>();
const toast = useToast();

const typeName = ref('');
const localValues = ref<VariantValue[]>([]);
const newValue = ref('');

const savingName = ref(false);
const savingValueIds = reactive<Record<number, boolean>>({});
const savingNewValue = ref(false);

watch(
    () => props.type,
    (t) => {
        if (t) {
            typeName.value = t.name;
            localValues.value = (t.values ?? []).map((v) => ({ ...v }));
        }
    },
    { immediate: true },
);

watch(visible, (isVisible) => {
    if (!isVisible) {
        newValue.value = '';
    }
});

const nameIsDirty = () => !!props.type && typeName.value.trim() !== props.type.name;

function valueIsDirty(val: VariantValue): boolean {
    const original = props.type?.values?.find((v) => v.id === val.id);
    return !!original && val.value.trim() !== original.value;
}

function saveName() {
    if (!props.type || !nameIsDirty()) return;

    const trimmed = typeName.value.trim();
    if (!trimmed) {
        typeName.value = props.type.name;
        return;
    }

    savingName.value = true;

    router.patch(
        VariantTypeController.update(props.type.id).url,
        { name: trimmed },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Type name updated', life: 3000 });
                emit('changed');
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Update failed',
                    detail: errors.name ?? 'Please check the name.',
                    life: 5000,
                });
                typeName.value = props.type!.name;
            },
            onFinish: () => {
                savingName.value = false;
            },
        },
    );
}

function saveValue(val: VariantValue) {
    if (!valueIsDirty(val)) return;

    const trimmed = val.value.trim();
    if (!trimmed) return;

    savingValueIds[val.id] = true;

    router.patch(
        VariantValueController.update(val.id).url,
        { value: trimmed },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Value updated', life: 3000 });
                emit('changed');
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Update failed',
                    detail: errors.value ?? 'Please check the value.',
                    life: 5000,
                });
                const original = props.type?.values?.find((v) => v.id === val.id);
                if (original) val.value = original.value;
            },
            onFinish: () => {
                savingValueIds[val.id] = false;
            },
        },
    );
}

function deleteValue(val: VariantValue) {
    router.delete(VariantValueController.destroy(val.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Value deleted', life: 3000 });
            emit('changed');
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Delete failed', life: 3000 });
        },
    });
}

function addValue() {
    if (!props.type) return;

    const trimmed = newValue.value.trim();
    if (!trimmed) return;

    const isDuplicate = localValues.value.some(
        (v) => v.value.toLowerCase() === trimmed.toLowerCase(),
    );
    if (isDuplicate) {
        toast.add({ severity: 'warn', summary: 'That value already exists', life: 3000 });
        return;
    }

    savingNewValue.value = true;

    router.post(
        VariantValueController.store(props.type.id).url,
        { value: trimmed },
        {
            preserveScroll: true,
            onSuccess: () => {
                newValue.value = '';
                toast.add({ severity: 'success', summary: 'Value added', life: 3000 });
                emit('changed');
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Could not add value',
                    detail: errors.value ?? 'Please check the value.',
                    life: 5000,
                });
            },
            onFinish: () => {
                savingNewValue.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:visible="visible" modal header="Edit Variant Type" :style="{ width: '28rem' }">
        <div v-if="type" class="space-y-4 py-4">
            <div class="grid gap-1.5">
                <label class="text-sm font-medium">Type name</label>
                <InputGroup>
                    <InputText v-model="typeName" fluid @keydown.enter.prevent="saveName" />
                    <InputGroupAddon>
                        <Button
                            text
                            severity="success"
                            :disabled="!nameIsDirty()"
                            :loading="savingName"
                            @click="saveName"
                        >
                            <Check />
                        </Button>
                    </InputGroupAddon>
                </InputGroup>
            </div>

            <div class="space-y-3">
                <label class="text-sm font-medium">Values</label>

                <div class="space-y-2">
                    <div v-for="val in localValues" :key="val.id" class="flex items-center gap-2">
                        <InputGroup class="flex-1">
                            <InputText v-model="val.value" fluid @keydown.enter.prevent="saveValue(val)" />
                            <InputGroupAddon>
                                <Button
                                    text
                                    severity="success"
                                    :disabled="!valueIsDirty(val)"
                                    :loading="savingValueIds[val.id]"
                                    @click="saveValue(val)"
                                    rounded
                                >
                                    <Check />
                                </Button>
                            </InputGroupAddon>
                            <InputGroupAddon>
                                <Button 
                                    severity="danger" 
                                    text 
                                    @click="deleteValue(val)"
                                >
                                    <Trash />
                                </Button>
                            </InputGroupAddon>
                        </InputGroup>

                        
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <InputGroup>
                        <InputText
                            v-model="newValue"
                            placeholder="Add a value"
                            fluid
                            @keydown.enter.prevent="addValue"
                        />
                        <InputGroupAddon>
                            <Button variant="text" rounded :loading="savingNewValue" @click="addValue">
                                <Plus />
                            </Button>
                        </InputGroupAddon>
                    </InputGroup>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button label="Close" severity="secondary" variant="text" @click="visible = false" />
            </div>
        </div>
    </Dialog>
</template>