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

const home = ref({ icon: Home, href: dashboard()});

// const items = computed(() =>
//     props.breadcrumbs.map((item) => ({
//         label: item.title,
//         href: item.href,
//     }))
// );
const items = computed(() =>
    props.breadcrumbs
        .filter(item => item.title !== 'Dashboard')
        .map(item => ({
            label: item.title,
            href: item.href,
        }))
);
</script>

<template>
    <Breadcrumb :model="items" :home="home">
        <template #item="{ item }">
            <Link
                v-if="item.icon"
                :href="item.href"
                class="p-breadcrumb-item-link"
            >
                <component :is="item.icon" />
            </Link>
            <Link
                v-if="item.href"
                :href="item.href"
                class="p-breadcrumb-item-link hover:underline!"
            >
                {{ item.label }}
            </Link>

            <span v-else class="p-breadcrumb-item-link">
                {{ item.label }}
            </span>
        </template>
    </Breadcrumb>
</template>