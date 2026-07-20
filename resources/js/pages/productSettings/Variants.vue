<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { variants as variantIndex } from '@/routes/product-settings/index';
import VariantCreate from './VariantCreate.vue';
import VariantTypeDelete from './VariantTypeDelete.vue';
import VariantEdit from '@/components/VariantEdit.vue';
import type { VariantType } from '@/types/product-settings';
import { Plus, Trash, Pencil } from '@primeicons/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Product Settings',
                href: '',
            },
            { 
                title: 'Product Variants',
                href: variantIndex() 
            }],
    },
});

const props = defineProps<{
    variantTypes: VariantType[];
}>();

const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedType = ref<VariantType | null>(null);

function handleCreated() {
    router.reload({ only: ['variantTypes'] });
}

function handleChanged() {
    router.reload({
        only: ['variantTypes'],
        onSuccess: () => {
            if (selectedType.value) {
                const fresh = props.variantTypes.find((t) => t.id === selectedType.value!.id);
                if (fresh) selectedType.value = fresh;
            }
        },
    });
}

function toggleEdit(type: VariantType) {
    selectedType.value = type;
    showEditDialog.value = true;
}


function openDeleteDialog(type: VariantType) {
    selectedType.value = type;
    showDeleteDialog.value = true;
}

function handleDeleted() {
    router.reload({ only: ['variantTypes'] });
}
</script>

<template>
    <Head title="Product Variants" />
    <h1 class="sr-only">Product Variants</h1>

    <div class="max-w-5xl space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Product Variants"
                description="Manage your product variant types"
            />
            <Button @click="showCreateDialog = true">
                <Plus />
                Add
            </Button>
        </div>

        <DataTable
            :value="variantTypes"
            dataKey="id"
            tableStyle="min-width: 50rem"
            stripedRows
        >
            <Column field="name" header="Type" headerStyle="width: 20%">
                <template #body="{ data }">
                    <span class="font-medium">{{ data.name }}</span>
                </template>
            </Column>

            <Column header="Values" headerStyle="width: 65%">
                <template #body="{ data }">
                    <div class="flex flex-wrap gap-2.5">
                        <Tag
                            v-for="val in data.values"
                            :key="val.id"
                            severity="primary"
                            class="w-max px-5!"
                        >
                            {{ val.value }}
                        </Tag>
                        <span v-if="!data.values?.length" class="text-sm text-muted-foreground">
                            No values yet
                        </span>
                    </div>
                </template>
            </Column>

            <Column header="" headerStyle="width: 15%" bodyStyle="text-align:center">
                <template #body="{ data }">
                    <Button severity="secondary" variant="text" rounded @click="toggleEdit(data)">
                        <Pencil />
                    </Button>
                    <Button severity="danger" variant="text" rounded @click="openDeleteDialog(data)">
                        <Trash />
                    </Button>
                </template>
            </Column>
            <template #empty>
                <p class="text-sm text-muted-foreground text-center p-5">
                    No variant types created yet.
                    <Button @click="showCreateDialog = true" class="text-foreground! underline! decoration-neutral-300! underline-offset-4! transition-colors! duration-300! ease-out! hover:decoration-current! dark:decoration-neutral-500! border-0! bg-transparent!">
                        Create one now
                    </Button>
                </p>
            </template>
        </DataTable>

        <VariantEdit v-model:visible="showEditDialog" :type="selectedType" @changed="handleChanged" />
        <VariantCreate v-model:visible="showCreateDialog" @created="handleCreated" />
        <VariantTypeDelete v-model:visible="showDeleteDialog" :type="selectedType" @deleted="handleDeleted" />
    </div>
</template>
