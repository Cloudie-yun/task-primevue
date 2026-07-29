<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import { Home } from '@primeicons/vue';

import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

const props = defineProps<Props>();

const home = ref({ icon: Home, href: dashboard() });

const items = computed(() =>
    props.breadcrumbs
        .filter((item) => item.title !== 'Dashboard')
        .map((item) => ({
            label: item.title,
            href: item.href,
        }))
);
</script>

<template>
    <div>
        <Breadcrumb 
            :model="items" 
            :home="home"
            :pt="{
                root: { class: 'bg-transparent!' },
            }"
        >
            <template #item="{ item, props: itemProps }">
                <Link
                    v-if="item.href"
                    :href="item.href"
                    class="flex items-center gap-1.5 text-sm text-surface-500 transition-colors hover:text-surface-900 dark:text-surface-400 dark:hover:text-surface-100"
                >
                    <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
                    <span v-if="item.label">{{ item.label }}</span>
                </Link>
                <span v-else class="flex items-center gap-1.5 text-sm font-medium text-surface-900 dark:text-surface-100">
                    <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
                    <span v-if="item.label">{{ item.label }}</span>
                </span>
            </template>
        </Breadcrumb>
    </div>
</template>