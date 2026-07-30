<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { categories, variants } from '@/routes/product-settings/index';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[]= [
    {
        title: 'Categories',
        href: categories(),
    },
    {
        title: 'Variant Types',
        href: variants(),
    },
];
const { isCurrentOrParentUrl } = useCurrentUrl();

</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Product Settings"
            description="Manage product categories and attributes"
        />

        <div class="flex flex-col gap-8 lg:flex-row lg:space-x-12">
            <aside class="w-full lg:w-52">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="Product Settings"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start hover:bg-surface-300/30',
                            { 'bg-surface-300/70': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>
            <Separator class="lg:hidden"/>
            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>