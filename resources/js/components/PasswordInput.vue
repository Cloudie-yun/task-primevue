<script setup lang="ts">
import { ref, useTemplateRef } from 'vue';
import type { HTMLAttributes } from 'vue';

import InputPassword from 'primevue/inputpassword';
import { Eye, EyeSlash } from '@primeicons/vue';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<{
    class?: HTMLAttributes['class'];
    inputId?: string;
    name?: string;
    defaultMask?: boolean;
    autocomplete?: string;
}>();

const mask = ref(props.defaultMask ?? true);

type PasswordInputInstance = {
    $el?: HTMLElement;
};

const inputRef = useTemplateRef('inputRef');

defineExpose({
    $el: inputRef,
    focus: () => (inputRef.value as PasswordInputInstance | null | undefined)?.$el?.focus?.(),
});
</script>

<template>
    <div class="relative">
        <InputPassword
            ref="inputRef"
            :inputId="props.inputId"
            :name="props.name"
            :mask="mask"
            :class="props.class"
            :autocomplete="props.autocomplete"
            fluid
            v-bind="$attrs"
        />

        <button
            type="button"
            @click="mask = !mask"
            class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer"
            :aria-label="mask ? 'Show password' : 'Hide password'"
            :tabindex="-1"
        >
            <Eye v-if="mask" />
            <EyeSlash v-else />
        </button>
    </div>
</template>