<script setup lang="ts">
import { RouterView, useRoute } from 'vue-router';
import SiteCursor from './components/SiteCursor.vue';
import SiteNavigation from './components/SiteNavigation.vue';

import { nextTick, onMounted, onUnmounted, watch } from 'vue';

const route = useRoute();
let observer: IntersectionObserver | undefined;
async function observeTitles() {
    await nextTick();
    observer?.disconnect();
    observer = new IntersectionObserver(entries => {
        for (const entry of entries) {
            const title = entry.target as HTMLElement;
            if (!entry.isIntersecting) {
                title.classList.remove('reveal-play');
                continue;
            }
            if (title.classList.contains('reveal-play')) continue;
            title.querySelectorAll('.title-cover').forEach(block => block.remove());
            const range = document.createRange();
            const walker = document.createTreeWalker(title, NodeFilter.SHOW_TEXT);
            const bounds = title.getBoundingClientRect();
            const lines: DOMRect[] = [];
            while (walker.nextNode()) {
                if (!walker.currentNode.textContent?.trim()) continue;
                range.selectNodeContents(walker.currentNode);
                for (const rect of range.getClientRects()) {
                    if (!rect.width || !rect.height) continue;
                    const line = lines.find(row => Math.abs(row.top - rect.top) < 2);
                    if (!line) {
                        lines.push(DOMRect.fromRect(rect));
                        continue;
                    }
                    const right = Math.max(line.right, rect.right);
                    line.x = Math.min(line.x, rect.x);
                    line.width = right - line.x;
                }
            }
            for (const line of lines) {
                const cover = document.createElement('span');
                cover.className = 'title-cover';
                cover.setAttribute('aria-hidden', 'true');
                Object.assign(cover.style, {
                    top: (line.top - bounds.top) + 'px', left: (line.left - bounds.left) + 'px',
                    width: line.width + 'px', height: line.height + 'px',
                });
                title.append(cover);
            }
            title.classList.add('reveal-play');
        }
    }, { threshold: 0 });
    for (const title of document.querySelectorAll('main h1, .phase-title, #export-title, .project-invite h2, .work-scene h3')) {
        if (title.closest('dialog') && !title.closest('dialog[open]')) continue;
        title.classList.add('reveal-target');
        observer.observe(title);
    }
}
onMounted(observeTitles);
watch(() => route.name, observeTitles, { flush: 'post' });
onUnmounted(() => observer?.disconnect());
</script>

<template>
    <a v-if="route.name !== 'not-found'" class="skip-link" href="#main-content">
        Skip to content
    </a>
    <SiteCursor v-if="route.name !== 'not-found'" />
    <SiteNavigation v-if="route.name !== 'not-found'" />
    <main
        id="main-content"
        tabindex="-1"
        :class="{
            'mx-auto grid min-h-dvh max-w-7xl content-center px-6 py-16 sm:px-12':
                route.name === 'not-found',
        }"
    >
        <RouterView />
    </main>
</template>

