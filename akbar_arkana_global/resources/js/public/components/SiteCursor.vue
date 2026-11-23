<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

const dot = ref<HTMLElement>();
let cleanup: (() => void) | undefined;
onMounted(() => {
    let x = -1;
    let y = -1;
    let frame = 0;
    let followX = 0;
    let followY = 0;
    let lastTime = 0;
    const refresh = (now: number) => {
        frame = 0;
        const el = dot.value;
        if (!el) return;
        const target = document.elementFromPoint(x, y);
        const entering = el.hidden;
        el.hidden = !target || !!target.closest('dialog');
        el.classList.toggle('on-navy', !!target?.closest('[data-navy]'));
        el.classList.toggle('on-work', !!target?.closest('[data-view]'));
        if (el.hidden) return;
        const blend = entering ? 1 : 1 - Math.exp(-Math.min(now - lastTime, 40) / 95);
        lastTime = now;
        followX += (x - followX) * blend;
        followY += (y - followY) * blend;
        el.style.transform = `translate3d(${followX}px,${followY}px,0)`;
        if (Math.hypot(x - followX, y - followY) > 0.15) schedule();
    };
    const schedule = () => { if (!frame) frame = requestAnimationFrame(refresh); };
    const move = (e: PointerEvent) => { x = e.clientX; y = e.clientY; schedule(); };
    const hide = () => { x = y = -1; schedule(); };
    document.addEventListener('pointermove', move);
    document.documentElement.addEventListener('pointerleave', hide);
    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('blur', hide);
    cleanup = () => {
        cancelAnimationFrame(frame);
        document.removeEventListener('pointermove', move);
        document.documentElement.removeEventListener('pointerleave', hide);
        window.removeEventListener('scroll', schedule);
        window.removeEventListener('blur', hide);
    };
});
onUnmounted(() => cleanup?.());
</script>
<template>
    <div ref="dot" class="site-cursor" aria-hidden="true" hidden><div><span>↗<br />View project</span></div></div>
</template>
