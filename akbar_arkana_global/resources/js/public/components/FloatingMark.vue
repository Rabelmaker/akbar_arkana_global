<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import company from '../../config/company.json';

const props = defineProps<{ scrollRotation?: boolean }>();
const container = ref<HTMLElement>();
const ready = ref(false);
let media: MediaQueryList | undefined;
let destroy: (() => void) | undefined;
let generation = 0;

async function syncMotion() {
    const request = ++generation;
    destroy?.();
    destroy = undefined;
    ready.value = false;
    if (media?.matches || !container.value) return;

    try {
        const { mountMarkScene } = await import('../motion/markScene');
        if (request !== generation || !container.value) return;
        destroy = mountMarkScene(container.value, (visible) => {
            ready.value = visible;
        }, props.scrollRotation);
    } catch {
        // Keep the official image visible if the renderer cannot start.
    }
}

onMounted(() => {
    media = window.matchMedia('(prefers-reduced-motion: reduce)');
    media.addEventListener('change', syncMotion);
    void syncMotion();
});

onUnmounted(() => {
    ++generation;
    media?.removeEventListener('change', syncMotion);
    destroy?.();
});
</script>

<template>
    <div
        ref="container"
        class="floating-mark"
        :class="{ 'floating-mark--ready': ready }"
    >
        <img
            src="/assets/branding/brand-symbol.webp"
            :alt="company.shortName"
            width="1000"
            height="359"
            fetchpriority="high"
        />
    </div>
</template>
