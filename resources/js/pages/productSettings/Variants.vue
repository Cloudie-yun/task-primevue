<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { variants as variantIndex } from '@/routes/product-settings/index';
import VariantCreate from '@/components/VariantCreate.vue';
import type { VariantType } from '@/types/product-settings';
// Icon
import { Plus, Trash, Pencil, Times } from '@primeicons/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Product Variants',
                href: variantIndex(),
            },
        ],
    },
});

defineProps<{
    variantTypes: VariantType[];
}>();

const showCreateDialog = ref(false);
const showDeleteDialog = ref(false);
const typeToDelete = ref<VariantType | null>(null);
const editingRows = ref([]);

function handleCreated() {
    router.reload({ only: ['variantTypes'] });
}

function confirmDeleteValue(valueId: number, valueName: string) {
    // wire up to your delete dialog / confirm flow
}

function confirmDeleteType(type: VariantType) {
    // wire up to your delete dialog / confirm flow
}
</script>

<template>
    <Head title="Product Variants" />
    <h1 class="sr-only">Product Variants</h1>

    <div class="space-y-6 max-w-5xl">
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

        <div v-if="variantTypes.length" class="space-y-4">
            <DataTable :value="variantTypes" stripedRows scrollable scrollHeight="400px" tableStyle="min-width: 50rem">
                <Column field="name" header="Type" headerStyle="width:20%;">
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
                                severity="secondary"
                                class="w-max px-3"
                            >
                                <span class="flex items-center gap-3.5">
                                    {{ val.value }}
                                    <Times
                                        class="cursor-pointer text-xs"
                                        @click="confirmDeleteValue(val.id, val.value)"
                                    />
                                </span>
                            </Tag>

                            <span v-if="!data.values?.length" class="text-sm text-muted-foreground">
                                No values yet
                            </span>
                        </div>
                    </template>
                </Column>

                <Column header="" headerStyle="width: 15%" bodyStyle="text-align:center">
                    <template #body="{ data }">
                        <Button
                            severity="secondary"
                            variant="text"
                            rounded
                            @click="$emit('edit-type', data)"
                        >
                            <Pencil />
                        </Button>
                        <Button
                            severity="danger"
                            variant="text"
                            rounded
                            @click="confirmDeleteType(data)"
                        >
                            <Trash />
                        </Button>
                    </template>
                </Column>
            </DataTable>
            <!-- <div
                v-for="type in variantTypes"
                :key="type.id"
                class="rounded-lg border p-4 space-y-3"
            >
                <h3 class="font-medium">{{ type.name }}</h3>

                <div class="flex flex-wrap gap-2">
                    <Tag
                        v-for="val in type.values"
                        :key="val.id"
                        :value="val.value"
                        severity="secondary"
                    />
                    <span v-if="!type.values?.length" class="text-sm text-muted-foreground">
                        No values yet
                    </span>
                </div>
            </div> -->
        </div>

        <p v-else class="text-sm text-muted-foreground">
            No variant types yet. Click "Add" to create one.
        </p>

        <VariantCreate v-model:visible="showCreateDialog" @created="handleCreated" />
    </div>
</template>