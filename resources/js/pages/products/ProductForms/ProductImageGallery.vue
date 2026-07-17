<script setup lang="ts">
import { Undo, Trash, ChevronLeft, ChevronRight } from '@primeicons/vue';
import { watch } from 'vue';
import Gallery from 'primevue/gallery';
import GalleryPrev from 'primevue/galleryprev';
import GalleryNext from 'primevue/gallerynext';
import GalleryContent from 'primevue/gallerycontent';
import GalleryItem from 'primevue/galleryitem';
import GalleryFooter from 'primevue/galleryfooter';
import GalleryThumbnail from 'primevue/gallerythumbnail';
import GalleryThumbnailContent from 'primevue/gallerythumbnailcontent';
import GalleryThumbnailItem from 'primevue/gallerythumbnailitem';

interface GalleryImageItem {
    key: string;
    url: string;
    name: string;
    kind: 'existing' | 'new';
    mediaId?: number;
    fileIndex?: number;
    markedForDeletion: boolean;
}

const props = defineProps<{
    items: GalleryImageItem[];
}>();

const visible = defineModel<boolean>('visible', { default: false });
const activeIndex = defineModel<number>('activeIndex', { default: 0 });

watch(
    () => props.items.length,
    (newLen) => {
        if (newLen === 0) {
            visible.value = false;
            return;
        }
        if (activeIndex.value > newLen - 1) {
            activeIndex.value = newLen - 1;
        }
    }
);

function open(index: number) {
    activeIndex.value = index;
    visible.value = true;
}

const emit = defineEmits<{
    action: [item: GalleryImageItem];
}>();

defineExpose({ open });
</script>

<template>
    <Dialog v-model:visible="visible" modal :style="{ width: '40rem' }" header="Product Images">
        <Gallery :key="items.length" v-model:activeIndex="activeIndex" class="w-full not-data-fullscreen:h-96!">            <GalleryPrev class="text-zinc-500! bg-black/10! opacity-70 hover:opacity-100">
                <ChevronLeft />
            </GalleryPrev>
            <GalleryNext class="text-zinc-500! bg-black/10! opacity-70 hover:opacity-100">
                <ChevronRight />
            </GalleryNext>

            <GalleryContent class="outline-0! border-0!">
                <GalleryItem v-for="(image, index) in items" :key="image.key">
                    <img
                        :src="image.url"
                        :alt="`image-${index + 1}`"
                        class="rounded!"
                        :class="{ 'opacity-40 grayscale': image.markedForDeletion }"
                    />
                </GalleryItem>
            </GalleryContent>

            <GalleryFooter class="bg-white! outline-0! border-0!">
                <GalleryThumbnail>
                    <GalleryThumbnailContent class="bg-white!">
                        <GalleryThumbnailItem
                            v-for="(image, index) in items"
                            :key="image.key"
                            :index="index"
                            class="bg-white! hover:outline-primary-200!"
                        >
                            <img
                                :draggable="false"
                                :src="image.url"
                                :alt="`image-${index + 1}`"
                                class="h-full w-full object-cover"
                                :class="{ 'opacity-40 grayscale': image.markedForDeletion }"
                            />
                        </GalleryThumbnailItem>
                    </GalleryThumbnailContent>
                </GalleryThumbnail>
            </GalleryFooter>
        </Gallery>

        <template #footer>
            <div class="flex w-full items-center justify-between">
                <span class="text-sm text-muted-foreground">
                    {{ items[activeIndex]?.name }}
                    <Tag v-if="items[activeIndex]?.kind === 'new'" value="New" severity="info" class="ml-2" />
                </span>

                <Button
                    v-if="items[activeIndex]"
                    :severity="items[activeIndex].markedForDeletion ? 'secondary' : 'danger'"
                    size="small"
                    @click="emit('action', items[activeIndex])"
                >
                    <span v-if="items[activeIndex].markedForDeletion" class="flex items-center gap-1.5">
                        <Undo />
                        Undo removal
                    </span>
                    <span v-else class="flex items-center gap-1.5">
                        <Trash />
                        Remove image
                    </span>
                </Button>
            </div>
        </template>
    </Dialog>
</template>