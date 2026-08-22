<script setup>
import { ref } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import DressCodeItem from './DressCodeItem.vue';

const props = defineProps({
    items: { type: Array, required: true },
});

const currentIndex = ref(0);

function prev() {
    currentIndex.value = currentIndex.value === 0
        ? props.items.length - 1
        : currentIndex.value - 1;
}

function next() {
    currentIndex.value = currentIndex.value === props.items.length - 1
        ? 0
        : currentIndex.value + 1;
}

function goTo(idx) {
    currentIndex.value = idx;
}

// ── Touch / swipe ──────────────────────────────────────────────
let touchStartX = 0;
let touchEndX = 0;

function onTouchStart(e) {
    touchStartX = e.changedTouches[0].screenX;
}

function onTouchEnd(e) {
    touchEndX = e.changedTouches[0].screenX;
    const diff = touchStartX - touchEndX;
    if (Math.abs(diff) > 50) {
        if (diff > 0) next();
        else prev();
    }
}
</script>

<template>
    <div class="relative">
        <!-- Slides wrapper -->
        <div
            class="overflow-hidden rounded-xl"
            @touchstart="onTouchStart"
            @touchend="onTouchEnd"
        >
            <div
                class="flex transition-transform duration-400 ease-out"
                :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
            >
                <div
                    v-for="(item, idx) in items"
                    :key="idx"
                    class="w-full flex-shrink-0"
                >
                    <DressCodeItem
                        :image-url="item.imageUrl"
                        :description="item.description"
                        :label="item.label"
                        :fallback-icon="item.fallbackIcon"
                    />
                </div>
            </div>
        </div>

        <!-- Arrows -->
        <button
            v-if="items.length > 1"
            @click="prev"
            class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1/2 bg-white/80 hover:bg-white text-cuero/60 hover:text-cuero rounded-full p-2 shadow-md shadow-cuero/10 border border-cuero/10 transition-colors"
            aria-label="Anterior"
        >
            <ChevronLeftIcon class="w-5 h-5" />
        </button>
        <button
            v-if="items.length > 1"
            @click="next"
            class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 bg-white/80 hover:bg-white text-cuero/60 hover:text-cuero rounded-full p-2 shadow-md shadow-cuero/10 border border-cuero/10 transition-colors"
            aria-label="Siguiente"
        >
            <ChevronRightIcon class="w-5 h-5" />
        </button>

        <!-- Dots -->
        <div
            v-if="items.length > 1"
            class="flex justify-center gap-2 mt-4"
        >
            <button
                v-for="(item, idx) in items"
                :key="idx"
                @click="goTo(idx)"
                class="w-2.5 h-2.5 rounded-full transition-colors"
                :class="idx === currentIndex ? 'bg-dorado' : 'bg-cuero/20 hover:bg-cuero/40'"
                :aria-label="`Ir a ${item.label}`"
            />
        </div>
    </div>
</template>
