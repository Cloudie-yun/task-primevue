<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import type { PreviewProduct } from '@/types/preview';
import { index as productsIndex } from '@/routes/products';
import Heading from '@/components/Heading.vue';
import { ChevronLeft, ChevronRight } from '@primeicons/vue';
import Gallery from 'primevue/gallery';
import GalleryPrev from 'primevue/galleryprev';
import GalleryNext from 'primevue/gallerynext';
import GalleryContent from 'primevue/gallerycontent';
import GalleryItem from 'primevue/galleryitem';
import GalleryFooter from 'primevue/galleryfooter';
import GalleryThumbnail from 'primevue/gallerythumbnail';
import GalleryThumbnailContent from 'primevue/gallerythumbnailcontent';
import GalleryThumbnailItem from 'primevue/gallerythumbnailitem';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Products List', href: productsIndex() },
            { title: 'Preview Product', href: '' },
        ],
    },
});

const props = defineProps<{ product: PreviewProduct }>();

const hasVariantTypes = computed(() => props.product.variant_types.length > 0);

// selected value id per variant type id
const selection = reactive<Record<number, number | null>>(
    Object.fromEntries(
        props.product.variant_types.map((t) => [t.id, t.values[0]?.id ?? null])
    )
);

function selectValue(typeId: number, valueId: number) {
    selection[typeId] = valueId;
}

const selectedVariant = computed(() => {
    // Products with no generating types just have a single implicit variant (if any).
    if (!hasVariantTypes.value) return props.product.variants[0] ?? null;

    const selectedIds = Object.values(selection).filter((id): id is number => id !== null);
    if (selectedIds.length !== props.product.variant_types.length) return null;

    return (
        props.product.variants.find(
            (v) => v.value_ids.length === selectedIds.length && selectedIds.every((id) => v.value_ids.includes(id))
        ) ?? null
    );
});

const displayPrice = computed(() => {
    const base = Number(props.product.price);
    const addOn = selectedVariant.value ? Number(selectedVariant.value.additional_price) : 0;
    return (base + addOn).toFixed(2);
});

const stockStatus = computed<{ label: string; severity: 'success' | 'danger' | 'warn' }>(() => {
    if (!selectedVariant.value) return { label: 'Not available', severity: 'danger' };
    if (selectedVariant.value.stock <= 0) return { label: 'Out of stock', severity: 'danger' };
    if (selectedVariant.value.stock <= 5) return { label: `Only ${selectedVariant.value.stock} left`, severity: 'warn' };
    return { label: 'In stock', severity: 'success' };
});

const canAddToCart = computed(() => !!selectedVariant.value && selectedVariant.value.stock > 0);

const galleryImages = computed(() =>
    props.product.images.length ? props.product.images : [{ id: 0, url: '/placeholder-product.png' }]
);
</script>

<template>
    <Head :title="`Preview - ${product.name}`" />

    <div class="mx-auto max-w-6xl p-6">
        <Heading
            variant="small"
            :title="`Preview - ${product.name}`"
        />

        <div class="mb-4 flex items-center gap-2 text-sm text-muted-foreground">
            <span>Preview mode</span>
            <span v-if="product.category">· {{ product.category.name }}</span>
        </div>

        <div class="grid grid-cols-1 gap-10 md:grid-cols-2">
            <div>
                <Gallery 
                    class="overflow-hidden rounded-lg border h-96!"
                    :pt="{
                        GalleryPrev: { class: 'bg-primary-100!' },
                        GalleryNext: { class: '' },
                    }"
                >
                    <GalleryPrev v-if="galleryImages.length > 1" class="opacity-70 hover:opacity-100 text-primary-800! bg-surface-300! dark:bg-surface-300! dark:text-primary-800!">
                        <ChevronLeft />
                    </GalleryPrev>
                    <GalleryNext v-if="galleryImages.length > 1" class="opacity-70 hover:opacity-100 text-primary-800! bg-surface-300! dark:bg-surface-300! dark:text-primary-800!">
                        <ChevronRight />
                    </GalleryNext>

                    <GalleryContent>
                        <GalleryItem v-for="image in galleryImages" :key="image.id">
                            <img :src="image.url" :alt="product.name" class="aspect-square w-full object-cover" />
                        </GalleryItem>
                    </GalleryContent>

                    <GalleryFooter v-if="galleryImages.length > 1"  class="bg-transparent! outline-0! border-0!">
                        <GalleryThumbnail>
                            <GalleryThumbnailContent>
                                <GalleryThumbnailItem
                                    v-for="(image, index) in galleryImages"
                                    :key="image.id"
                                    :index="index"
                                    class="bg-transparent! hover:outline-primary-200!"
                                >
                                    <img draggable="false" :src="image.url" :alt="product.name" class="h-full w-full object-cover" />
                                </GalleryThumbnailItem>
                            </GalleryThumbnailContent>
                        </GalleryThumbnail>
                    </GalleryFooter>
                </Gallery>
            </div>

            <!-- Details -->
            <div class="flex flex-col gap-5">
                <div>
                    <h1 class="text-2xl font-semibold">{{ product.name }}</h1>
                    <p class="mt-1 text-2xl font-bold">RM {{ displayPrice }}</p>
                </div>

                <Tag :severity="stockStatus.severity" :value="stockStatus.label" class="w-fit" />

                <p v-if="product.description" class="text-sm leading-relaxed text-muted-foreground">
                    {{ product.description }}
                </p>

                <!-- Static (non-generating) attributes -->
                <div v-if="product.static_attributes.length" class="flex flex-col gap-1 text-sm">
                    <div v-for="attr in product.static_attributes" :key="attr.id" class="flex gap-2">
                        <span class="font-medium">{{ attr.type.name }}:</span>
                        <span class="text-muted-foreground">{{ attr.value }}</span>
                    </div>
                </div>

                <Divider v-if="hasVariantTypes" />

                <!-- Selectable variant option groups -->
                <div v-for="type in product.variant_types" :key="type.id" class="flex flex-col gap-2">
                    <span class="text-sm font-medium">{{ type.name }}</span>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="value in type.values"
                            :key="value.id"
                            :label="value.value"
                            size="small"
                            :severity="selection[type.id] === value.id ? 'primary' : 'secondary'"
                            :outlined="selection[type.id] !== value.id"
                            @click="selectValue(type.id, value.id)"
                        />
                    </div>
                </div>

                <div v-if="selectedVariant" class="text-xs text-muted-foreground">
                    SKU: {{ selectedVariant.sku }}
                </div>

                <Button
                    label="Add to Cart"
                    class="mt-2 w-full md:w-auto"
                    :disabled="!canAddToCart"
                />
            </div>
        </div>
    </div>
</template>