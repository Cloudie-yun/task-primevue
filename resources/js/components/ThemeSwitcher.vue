<script setup lang="ts">
import { updatePreset, updateSurfacePalette } from '@primeuix/themes';
import { Monitor, Moon, Palette, Sun } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import { presetColors, type ColorOption } from '@/themes/presetColors';
import { surfaceColors, type SurfaceOption } from '@/themes/surfaceColors';
import { loadThemeState, saveThemeState } from '@/themes/persistence';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const appearanceTabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;

const popoverRef = ref();
const selectedPrimary = ref<string>('noir');
const selectedSurface = ref<string>('slate');

function togglePopover(event: Event) {
    popoverRef.value?.toggle(event);
}

function buildPrimaryOverride(color: ColorOption) {
    if (color.name === 'noir') {
        return {
            semantic: {
                primary: {
                    50: '{surface.50}', 100: '{surface.100}', 200: '{surface.200}', 300: '{surface.300}',
                    400: '{surface.400}', 500: '{surface.500}', 600: '{surface.600}', 700: '{surface.700}',
                    800: '{surface.800}', 900: '{surface.900}', 950: '{surface.950}',
                },
                colorScheme: {
                    light: {
                        primary: { color: '{primary.950}', contrastColor: '#ffffff', hoverColor: '{primary.900}', activeColor: '{primary.800}' },
                        highlight: { background: '{primary.950}', focusBackground: '{primary.700}', color: '#ffffff', focusColor: '#ffffff' },
                    },
                    dark: {
                        primary: { color: '{primary.50}', contrastColor: '{primary.950}', hoverColor: '{primary.100}', activeColor: '{primary.200}' },
                        highlight: { background: '{primary.50}', focusBackground: '{primary.300}', color: '{primary.950}', focusColor: '{primary.950}' },
                    },
                },
            },
        };
    }

    return {
        semantic: {
            primary: color.palette,
            colorScheme: {
                light: {
                    primary: { color: '{primary.500}', contrastColor: '#ffffff', hoverColor: '{primary.600}', activeColor: '{primary.700}' },
                    highlight: { background: '{primary.50}', focusBackground: '{primary.100}', color: '{primary.700}', focusColor: '{primary.800}' },
                },
                dark: {
                    primary: { color: '{primary.400}', contrastColor: '{surface.900}', hoverColor: '{primary.300}', activeColor: '{primary.200}' },
                    highlight: {
                        background: 'color-mix(in srgb, {primary.400}, transparent 84%)',
                        focusBackground: 'color-mix(in srgb, {primary.400}, transparent 76%)',
                        color: 'rgba(255,255,255,.87)',
                        focusColor: 'rgba(255,255,255,.87)',
                    },
                },
            },
        },
    };
}

function applyPrimary(name: string) {
    const color = presetColors.find((c) => c.name === name);
    if (!color) return;
    selectedPrimary.value = name;
    updatePreset(buildPrimaryOverride(color));
    saveThemeState({ primary: selectedPrimary.value, surface: selectedSurface.value });
}

function applySurface(name: string) {
    const surface = surfaceColors.find((s) => s.name === name);
    if (!surface) return;
    selectedSurface.value = name;
    updateSurfacePalette(surface.palette);
    saveThemeState({ primary: selectedPrimary.value, surface: selectedSurface.value });

    if (selectedPrimary.value === 'noir') {
        const noir = presetColors.find((c) => c.name === 'noir');
        if (noir) updatePreset(buildPrimaryOverride(noir));
    }
}

onMounted(() => {
    const saved = loadThemeState();
    selectedPrimary.value = saved.primary;
    selectedSurface.value = saved.surface;

    const surface = surfaceColors.find((s) => s.name === saved.surface);
    if (surface) updateSurfacePalette(surface.palette);

    const primary = presetColors.find((c) => c.name === saved.primary);
    if (primary) updatePreset(buildPrimaryOverride(primary));
});
</script>

<template>
    <Button variant="text" @click="togglePopover">
        <Palette class="size-4" />
        <span>Theme</span>
    </Button>

    <Popover ref="popoverRef">
        <div class="w-64 space-y-4 p-1">
            <div class="space-y-2">
                <span class="text-sm font-medium">Appearance</span>
                <div class="flex flex-col gap-1">
                    <label
                        v-for="{ value, Icon, label } in appearanceTabs"
                        :key="value"
                        class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 transition-colors hover:bg-surface-100 dark:hover:bg-surface-800"
                    >
                        <RadioButton
                            :model-value="appearance"
                            :value="value"
                            name="appearance"
                            @update:model-value="updateAppearance"
                        />
                        <component :is="Icon" class="h-4 w-4 text-surface-500 dark:text-surface-400" />
                        <span class="text-sm">{{ label }}</span>
                    </label>
                </div>
            </div>

            <div class="space-y-2">
                <span class="text-sm font-medium">Primary Color</span>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="color in presetColors"
                        :key="color.name"
                        type="button"
                        :title="color.name"
                        class="h-6 w-6 cursor-pointer rounded-full p-0 outline-2 outline-offset-1 outline-transparent"
                        :style="{
                            backgroundColor: color.name === 'noir' ? 'var(--p-surface-500)' : color.palette['500'],
                            outlineColor: selectedPrimary === color.name ? 'var(--p-primary-color)' : 'transparent',
                        }"
                        @click="applyPrimary(color.name)"
                    />
                </div>
            </div>

            <div class="space-y-2">
                <span class="text-sm font-medium">Surface Color</span>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="surface in surfaceColors"
                        :key="surface.name"
                        type="button"
                        :title="surface.name"
                        class="h-6 w-6 cursor-pointer rounded-full p-0 outline-2 outline-offset-1 outline-transparent"
                        :style="{
                            backgroundColor: surface.palette['500'],
                            outlineColor: selectedSurface === surface.name ? 'var(--p-primary-color)' : 'transparent',
                        }"
                        @click="applySurface(surface.name)"
                    />
                </div>
            </div>
        </div>
    </Popover>
</template>