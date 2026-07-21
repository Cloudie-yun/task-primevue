<script setup lang="ts">
import { computed, ref, watch } from 'vue';

interface BlockedValue {
    id: number;
    value: string;
    variant_count: number;
    skus: string[];
}

const visible = defineModel<boolean>('visible', { default: false });
const props = defineProps<{ blockedValues: BlockedValue[]; processing?: boolean }>();
const emit = defineEmits<{ confirm: [] }>();

const typedConfirmation = ref('');

const requiredPhrase = computed(() => {
    const names = props.blockedValues.map((v) => v.value).join(', ');
    return props.blockedValues.length === 1 ? `FORCE DELETE VALUE '${names}'` : `FORCE DELETE VALUES '${names}'`;
});

const isConfirmed = computed(() => typedConfirmation.value.trim() === requiredPhrase.value);

watch(visible, (isVisible) => {
    if (isVisible) typedConfirmation.value = '';
});
</script>

<template>
    <Dialog v-model:visible="visible" modal header="Confirm force delete" :style="{ width: '32rem' }">
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                The following value(s) are still used by existing product variants. Deleting them will
                <strong class="text-foreground">permanently delete those product variants too</strong> — this cannot be undone.
            </p>
            <Accordion>
                <AccordionPanel
                    v-for="value in blockedValues"
                    :key="value.id"
                    :value="value.id"
                >
                    <AccordionHeader>
                        <div class="flex w-full items-center justify-between">
                            <span>{{ value.value }}</span>

                            <Tag severity="danger" class="mr-3">
                                {{ value.variant_count }} variants
                            </Tag>
                        </div>
                    </AccordionHeader>

                    <AccordionContent>
                        <ul class="space-y-1 text-sm">
                            <li
                                v-for="sku in value.skus"
                                :key="sku"
                            >
                                • {{ sku }}
                            </li>
                        </ul>
                    </AccordionContent>
                </AccordionPanel>
            </Accordion>
            <div class="grid gap-1.5">
                <label class="text-sm font-medium">
                    Type <code class="rounded bg-surface-100 px-1 py-0.5 dark:bg-surface-800">{{ requiredPhrase }}</code> to confirm
                </label>
                <InputText v-model="typedConfirmation" fluid autocomplete="off" />
            </div>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" variant="outlined" :disabled="processing" @click="visible = false" />
            <Button
                label="Force delete"
                severity="danger"
                :disabled="!isConfirmed"
                :loading="processing"
                @click="emit('confirm')"
            />
        </template>
    </Dialog>
</template>