<script setup lang="ts">
import { computed, ref, watch } from 'vue';

interface BlockedValue {
    id: number;
    value: string;
    variant_count: number;
    skus: string[];
    attribute_product_count: number;
    attribute_product_names: string[];
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
                        <div class="flex w-full items-center justify-between gap-2">
                            <span>{{ value.value }}</span>

                            <div class="flex gap-2">
                                <Tag v-if="value.variant_count" severity="danger">
                                    {{ value.variant_count }} variant{{ value.variant_count === 1 ? '' : 's' }}
                                </Tag>
                                <Tag v-if="value.attribute_product_count" severity="warn">
                                    {{ value.attribute_product_count }} product{{ value.attribute_product_count === 1 ? '' : 's' }} tagged
                                </Tag>
                            </div>
                        </div>
                    </AccordionHeader>

                    <AccordionContent>
                        <div class="space-y-3 text-sm">
                            <div v-if="value.skus.length">
                                <p class="mb-1 font-medium text-muted-foreground">Used in variants (SKU):</p>
                                <ul class="space-y-1">
                                    <li v-for="sku in value.skus" :key="sku">• {{ sku }}</li>
                                </ul>
                            </div>

                            <div v-if="value.attribute_product_names.length">
                                <p class="mb-1 font-medium text-muted-foreground">Tagged as an attribute on:</p>
                                <ul class="space-y-1">
                                    <li v-for="name in value.attribute_product_names" :key="name">• {{ name }}</li>
                                </ul>
                            </div>
                        </div>
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