<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { dashboard } from '@/routes';
import { Check } from '@primeicons/vue';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const radiusOptions = [
    { name: 'none', label: 'Square', value: '0px' },
    { name: 'sm', label: 'Subtle', value: '0.25rem' },
    { name: 'md', label: 'Default', value: '0.5rem' },
    { name: 'lg', label: 'Round', value: '0.75rem' },
    { name: 'full', label: 'Pill', value: '999px' },
];

const selectedRadius = ref('md');

function applyRadius(name: string) {
    const radius = radiusOptions.find((r) => r.name === name);
    if (!radius) return;
    selectedRadius.value = name;
    document.documentElement.style.setProperty('--app-border-radius', radius.value);
}

const fontSizeOptions = [
    { name: 'sm', label: 'Small', value: '13px' },
    { name: 'base', label: 'Default', value: '14px' },
    { name: 'lg', label: 'Large', value: '16px' },
];

const selectedFontSize = ref('base');

function applyFontSize(name: string) {
    const size = fontSizeOptions.find((s) => s.name === name);
    if (!size) return;
    selectedFontSize.value = name;
    document.documentElement.style.setProperty('--app-font-size', size.value);
}

const fontWeightOptions = [
    { name: 'light', label: 'Light', value: '400' },
    { name: 'medium', label: 'Medium', value: '500' },
    { name: 'bold', label: 'Bold', value: '700' },
];

const selectedFontWeight = ref('medium');

function applyFontWeight(name: string) {
    const weight = fontWeightOptions.find((w) => w.name === name);
    if (!weight) return;
    selectedFontWeight.value = name;
    document.documentElement.style.setProperty('--app-font-weight', weight.value);
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <!-- Playground card 1: border radius -->
            <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <span class="text-sm font-medium">Border Radius</span>
                <div class="mt-3 flex flex-wrap gap-2">
                    <Button
                        v-for="radius in radiusOptions"
                        :key="radius.name"
                        :label="radius.label"
                        size="small"
                        :severity="selectedRadius === radius.name ? 'primary' : 'secondary'"
                        :outlined="selectedRadius !== radius.name"
                        @click="applyRadius(radius.name)"
                    />
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <Button severity="primary"><Check class="mr-1" />Preview Button</Button>
                    <InputText placeholder="Preview input" />
                </div>
            </div>

            <!-- Playground card 2: typography -->
            <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <span class="text-sm font-medium">Typography</span>
                <div class="mt-3 space-y-3">
                    <div>
                        <span class="text-xs text-muted-foreground">Size</span>
                        <div class="mt-1 flex flex-wrap gap-2">
                            <Button
                                v-for="size in fontSizeOptions"
                                :key="size.name"
                                :label="size.label"
                                size="small"
                                :severity="selectedFontSize === size.name ? 'primary' : 'secondary'"
                                :outlined="selectedFontSize !== size.name"
                                @click="applyFontSize(size.name)"
                            />
                        </div>
                    </div>
        
                    <div>
                        <span class="text-xs text-muted-foreground">Weight</span>
                        <div class="mt-1 flex flex-wrap gap-2">
                            <Button
                                v-for="weight in fontWeightOptions"
                                :key="weight.name"
                                :label="weight.label"
                                size="small"
                                :severity="selectedFontWeight === weight.name ? 'primary' : 'secondary'"
                                :outlined="selectedFontWeight !== weight.name"
                                @click="applyFontWeight(weight.name)"
                            />
                        </div>
                    </div>
                </div>
                <p
                    class="mt-4 text-(length:--app-font-size,14px)"
                    :style="{ fontWeight: 'var(--app-font-weight, 500)' }"
                >
                    This paragraph reflects size and weight together.
                </p>
            </div>

            <!-- Playground card 3: combined preview -->
            <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <span class="text-sm font-medium">Combined Preview</span>
                <div class="mt-3 flex flex-col gap-2">
                    <Card>
                        <template #title>Sample Card</template>
                        <template #subtitle>Radius and text scale react together.</template>
                        <template #content>
                            <Button label="Action" class="mt-2" />
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>