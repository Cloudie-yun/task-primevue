<script setup lang="ts">
import { onUnmounted, ref, computed } from 'vue';
import type { Product } from '@/types/product';
import { Undo, Times, Trash, Plus } from '@primeicons/vue';
import ProductImageGallery from './ProductImageGallery.vue';

const props = defineProps<{
    product?: Product | null;
    errors?: string;
}>();

const images = defineModel<File[]>({ default: () => [] });

const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
const MAX_SIZE_BYTES = 5 * 1024 * 1024;

const deletedIds = defineModel<number[]>('deletedIds', { default: () => [] });
const previews = ref<string[]>([]);
const validationErrors = ref<string[]>([]);
const fileUploadRef = ref();
const stagedIds = ref<string[]>([]);

const remainingImageCount = computed(() => {
    const existingCount = props.product?.media?.length ?? 0;
    const deletedCount = deletedIds.value.length;
    const newCount = images.value.length;

    return (existingCount - deletedCount) + newCount;
});

const hasNoImages = computed(() => remainingImageCount.value <= 0);

function validateFiles(files: File[]): File[] {
    const valid: File[] = [];
    const errors: string[] = [];

    for (const file of files) {
        if (!ALLOWED_TYPES.includes(file.type)) {
            errors.push(`${file.name}: unsupported format (use JPG, PNG, GIF, WEBP, or AVIF)`);
            continue;
        }
        if (file.size > MAX_SIZE_BYTES) {
            errors.push(`${file.name}: exceeds 5MB limit`);
            continue;
        }
        valid.push(file);
    }

    validationErrors.value = errors;
    return valid;
}

function formatSize(bytes: number) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function openFileDialog() {
    fileUploadRef.value?.choose();
}

function handleSelect(event: { files: File[] }) {
    const valid = validateFiles(event.files);

    if (valid.length !== event.files.length) {
        fileUploadRef.value?.files?.splice(0, fileUploadRef.value.files.length, ...valid);
    }

    previews.value.forEach((url) => URL.revokeObjectURL(url));
    previews.value = valid.map((f) => URL.createObjectURL(f));
    stagedIds.value = valid.map(() => crypto.randomUUID());
    images.value = valid;
}

function removeStagedFile(index: number) {
    URL.revokeObjectURL(previews.value[index]);
    previews.value.splice(index, 1);
    images.value = images.value.filter((_, i) => i !== index);
    stagedIds.value.splice(index, 1);
    fileUploadRef.value?.files?.splice(index, 1);
}

function isMarkedForDeletion(mediaId: number): boolean {
    return deletedIds.value.includes(mediaId);
}

function toggleExisting(mediaId: number) {
    deletedIds.value = isMarkedForDeletion(mediaId)
        ? deletedIds.value.filter((id) => id !== mediaId)
        : [...deletedIds.value, mediaId];
}

// Gallery mode
interface GalleryItem {
    key: string;
    url: string;
    name: string;
    kind: 'existing' | 'new';
    mediaId?: number;   // present for kind: 'existing'
    fileIndex?: number; // present for kind: 'new'
    markedForDeletion: boolean;
}

const galleryItems = computed<GalleryItem[]>(() => {
    const existing: GalleryItem[] = (props.product?.media ?? []).map((media) => ({
        key: `existing-${media.id}`,
        url: media.original_url,
        name: media.name,
        kind: 'existing',
        mediaId: media.id,
        markedForDeletion: isMarkedForDeletion(media.id),
    }));

    const staged: GalleryItem[] = images.value.map((file, index) => ({
        key: `new-${stagedIds.value[index]}`,
        url: previews.value[index],
        name: file.name,
        kind: 'new',
        fileIndex: index,
        markedForDeletion: false,
    }));

    return [...existing, ...staged];
});

const galleryVisible = ref(false);
const activeGalleryIndex = ref(0);

function openPreview(index: number) {
    activeGalleryIndex.value = index;
    galleryVisible.value = true;
}

function handleGalleryAction(item: GalleryItem) {
    if (item.kind === 'existing' && item.mediaId !== undefined) {
        toggleExisting(item.mediaId);
        return;
    }

    if (item.kind === 'new' && item.fileIndex !== undefined) {
        const totalBefore = galleryItems.value.length;
        const globalIndex = (props.product?.media?.length ?? 0) + item.fileIndex;

        if (totalBefore - 1 === 0) {
            removeStagedFile(item.fileIndex);
            galleryVisible.value = false;
            return;
        }

        // set index BEFORE the item is removed & Gallery remounts
        activeGalleryIndex.value = globalIndex === totalBefore - 1
            ? globalIndex - 1
            : globalIndex;

        removeStagedFile(item.fileIndex);
    }
}

function reset() {
    previews.value.forEach((url) => URL.revokeObjectURL(url));
    previews.value = [];
    validationErrors.value = [];
    images.value = [];
    deletedIds.value = [];
    fileUploadRef.value?.clear?.();
}

onUnmounted(() => {
    previews.value.forEach((url) => URL.revokeObjectURL(url));
});

defineExpose({ reset, hasNoImages });
</script>

<template>
    <div class="space-y-4">
        <div v-if="product?.media?.length" class="flex flex-wrap gap-3">
            <div
                v-for="(media, i) in product.media"
                :key="media.id"
                class="group relative h-24 w-24 cursor-pointer overflow-hidden rounded-md border"
                :class="{ 'opacity-40 grayscale': isMarkedForDeletion(media.id) }"
                @click="openPreview(galleryItems.findIndex((g) => g.key === `existing-${media.id}`))"
            >
                <img :src="media.original_url" :alt="media.name" class="h-full w-full object-cover" />
                <Button
                    v-if="!isMarkedForDeletion(media.id)"
                    severity="danger"
                    rounded
                    class="absolute! right-1 top-1 h-6 w-6 opacity-0 transition-opacity group-hover:opacity-100"
                    @click.stop="toggleExisting(media.id)"
                >
                    <Times />
                </Button>
                <Button
                    v-else
                    severity="secondary"
                    rounded
                    class="absolute! right-1 top-1 h-6 w-6"
                    @click.stop="toggleExisting(media.id)"
                >
                    <Undo />
                </Button>
            </div>
        </div>
        <FileUpload
            ref="fileUploadRef"
            :multiple="true"
            accept="image/*"
            :maxFileSize="MAX_SIZE_BYTES"
            mode="advanced"
            :showChooseButton="false"
            :showUploadButton="false"
            :showCancelButton="false"
            :pt="{ 
                root: { class: 'border-0!' },
                header: { class: 'hidden!'}
            }"
            @select="handleSelect"
        >
            <!-- <template #header="{ chooseCallback }">
                <Button type="button" severity="secondary" variant="outlined" size="small" @click="chooseCallback()">
                    <Plus />
                    Add Images
                </Button>
            </template> -->

            <template #content="{ files }">
                <div v-if="files?.length > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                    <div
                        v-for="(file, i) of files"
                        :key="file.name + file.type + file.size"
                        class="group relative cursor-pointer overflow-hidden rounded-lg border border-surface-200 dark:border-surface-700"
                        @click="openPreview((product?.media?.length ?? 0) + i)"
                    >
                        <img :src="previews[i]" :alt="file.name" class="h-32 w-full object-cover" />

                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                            <button
                                type="button"
                                class="pointer-events-auto cursor-pointer rounded-full border-0 bg-red-500 p-2 text-white hover:bg-red-600"
                                @click.stop="removeStagedFile(i)"
                            >
                                <Times />
                            </button>
                        </div>

                        <div class="p-2">
                            <div class="truncate text-xs font-medium">{{ file.name }}</div>
                            <div class="text-xs text-muted-color">{{ formatSize(file.size) }}</div>
                        </div>
                    </div>
                    <div
                        class="flex h-full min-h-34 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-surface-200 text-muted-color transition-colors hover:border-primary-400 dark:border-surface-700"
                        @click="openFileDialog"
                    >
            <Plus />
            <span class="text-xs">Add more</span>
        </div>
                </div>
            </template>

            <template #empty>
                <div 
                    class="cursor-pointer rounded-xl border-2 border-dashed border-surface-200 p-12 py-24 text-center transition-colors hover:border-primary-400 dark:border-surface-700"
                    @click="openFileDialog"
                >
                    <p class="m-0! text-muted-color!">Drag and drop images here, or click to browse.</p>
                </div>
            </template>
        </FileUpload>

        <div class="pl-4">
            <p class="text-xs text-muted-foreground">
                Accepted formats: JPG, PNG, GIF, WEBP, AVIF. Max 5MB per image.
            </p>
            <Message v-if="hasNoImages" severity="error" size="small" variant="simple">
                At least one product image is required.
            </Message>
            <Message v-for="(err, i) in validationErrors" :key="i" severity="error" size="small" variant="simple">
                {{ err }}
            </Message>

            <Message v-if="errors" severity="error" size="small" variant="simple">
                {{ errors }}
            </Message>
        </div>
    </div>
    <ProductImageGallery
        v-model:visible="galleryVisible"
        v-model:active-index="activeGalleryIndex"
        :items="galleryItems"
        @action="handleGalleryAction"
    />
</template>
