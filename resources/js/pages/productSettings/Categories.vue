<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import CategoryCreate from './CategoryCreate.vue';
import CategoryDelete from './CategoryDelete.vue';
import CategoryController from '@/actions/App/Http/Controllers/Product/CategoryController'; 
import { categories as categoryIndex } from '@/routes/product-settings/index';
import type { Category } from '@/types/product-settings';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import ToggleSwitch from 'primevue/toggleswitch';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';

// Icons
import { Plus, Trash } from '@primeicons/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Product Settings',
                href: '',
            },
            {
                title: 'Categories',
                href: categoryIndex(),
            }
        ]
    }
});

defineProps<{
    categories: Category[];
}>();

const toast = useToast();

const showCreateDialog = ref(false);
const showDeleteDialog = ref(false);
const categoryToDelete = ref<Category | null>(null);
const editingRows = ref([]);

function handleCreated() {
    router.reload({ only: ['categories'] });
}

function onRowEditSave(event: { newData: Category; index: number }) {
    const { newData } = event;

    router.patch(
        CategoryController.update(newData.id).url,
        {
            name: newData.name,
            description: newData.description,
            is_active: newData.is_active,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Category updated', life: 3000 });
                router.reload({ only: ['categories'] });
            },
            onError: (errors) => {
                toast.add({ severity: 'error', summary: 'Update failed', detail: Object.values(errors)[0], life: 3000 });
                router.reload({ only: ['categories'] });
            },
        },
    );
}

function openDeleteDialog(category: Category) {
    categoryToDelete.value = category;
    showDeleteDialog.value = true;
}

function handleDeleted() {
    router.reload({ only: ['categories'] });
}

</script>

<template>
    <Head title="Categories" />
    <h1 class="sr-only">Categories</h1>

    <div class="space-y-6 max-w-5xl">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Categories"
                description="Manage your product categories"
            />
            <Button @click="showCreateDialog = true" >
                <Plus />
                Add
            </Button>
        </div>

        <DataTable 
            v-model:editingRows="editingRows" 
            :value="categories" 
            editMode="row"
            dataKey="id"
            stripedRows 
            removableSort 
            scrollable 
            scrollHeight="500px"  
            class="w-full"
            @row-edit-save="onRowEditSave"
        >
            <Column field="name" header="Name" sortable style="width: 20%" bodyStyle="text-align:left">
                <template #editor="{data, field }">
                    <InputText v-model="data[field]" fluid />
                </template>
            </Column>
            <Column field="description" header="Description" sortable style="width: 55%" bodyStyle="text-align:left">
                <template #body="{ data }">
                    <span class="text-muted-foreground">{{ data.description ?? '-' }}</span>
                </template>
                <template #editor="{data, field }">
                    <Textarea v-model="data[field]" rows="1" fluid autoScroll />
                </template>
            </Column>
            <Column field="is_active" header="Status" sortable style="width: 10%" bodyStyle="text-align:left">
                <template #body="{ data }">
                    <Tag v-if="data.is_active" value="Active" severity="success" />
                    <Tag v-else value="Inactive" severity="danger" />
                </template>
                <template #editor="{data, field }">
                    <ToggleSwitch v-model="data[field]" />
                </template>
            </Column>
            <Column :rowEditor="true" style="width: 10%" bodyStyle="text-align:center" />
            <Column field="actions" header="" style="width: 1%" bodyStyle="text-align:center">
                <template #body="{ data }">
                    <Button
                        severity="danger"
                        variant="text"
                        @click="openDeleteDialog(data)"
                        rounded    
                    >
                        <Trash />
                    </Button>
                </template>
            </Column>
            <template #empty>
                <p class="text-sm text-muted-foreground text-center p-5">
                    No categories created yet.
                    <Button @click="showCreateDialog = true" class="text-foreground! underline! decoration-neutral-300! underline-offset-4! transition-colors! duration-300! ease-out! hover:decoration-current! dark:decoration-neutral-500! border-0! bg-transparent!">
                        Create one now
                    </Button>
                </p>
            </template>
        </DataTable>

        <CategoryCreate v-model:visible="showCreateDialog" @created="handleCreated" />
        <CategoryDelete
            v-model:visible="showDeleteDialog"
            :category="categoryToDelete"
            @deleted="handleDeleted"
        />
    </div>
</template>
