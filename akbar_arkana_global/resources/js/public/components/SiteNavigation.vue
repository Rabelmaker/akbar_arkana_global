<script setup lang="ts">
import RollingArrow from "./RollingArrow.vue";
import { X } from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import company from '../../config/company.json';

const route = useRoute();
const links = [
    { label: 'Home', hash: '#home' },
    { label: 'About', hash: '#about' },
    { label: 'Expertise', hash: '#expertise' },
    { label: 'Projects', hash: '#projects' },
    { label: 'Akbar Spices', hash: '#akbar-spices' },
    { label: 'Process', hash: '#process' },
    { label: 'Contact', hash: '#contact' },
];

const menu = ref<HTMLDialogElement>();
const menuButton = ref<HTMLButtonElement>();
const scrolled = ref(false);
const expanded = ref(false);

function openMenu() {
    menu.value?.showModal();
    expanded.value = true;
}

function closeMenu() {
    menu.value?.close();
    expanded.value = false;
    menuButton.value?.focus({ preventScroll: true });
}

function closeAfterNavigation(hash: string) {
    closeMenu();
    document.getElementById(hash.slice(1))?.focus({ preventScroll: true });
}

function updateHeader() {
    scrolled.value = window.scrollY > 12;
}

onMounted(() => {
    window.addEventListener('scroll', updateHeader, { passive: true });
    updateHeader();
});
onUnmounted(() => window.removeEventListener('scroll', updateHeader));
</script>

<template>
    <header class="site-header" :class="{ 'site-header--scrolled': scrolled }">
        <div class="site-header__inner">
            <RouterLink
                class="site-brand"
                to="/#home"
                :aria-label="`${company.brandName} home`"
            >
                <img
                    src="/assets/branding/brand-symbol.webp"
                    alt=""
                    width="72"
                    height="26"
                />
            </RouterLink>
            <button
                ref="menuButton"
                type="button"
                class="menu-toggle"
                aria-controls="site-navigation"
                :aria-expanded="expanded"
                aria-haspopup="dialog"
                @click="openMenu"
            >
                Menu <span class="menu-icon" aria-hidden="true"></span>
            </button>
        </div>
    </header>

    <dialog
        id="site-navigation"
        ref="menu"
        class="mobile-menu"
        aria-label="Navigation menu"
        data-lenis-prevent
        @cancel.prevent="closeMenu()"
    >
        <div class="mobile-menu__top">
            <img
                src="/assets/branding/brand-symbol.webp"
                alt=""
                width="72"
                height="26"
            />
            <button
                type="button"
                class="menu-toggle"
                autofocus
                @click="closeMenu"
            >
                Close <X :size="19" aria-hidden="true" />
            </button>
        </div>
        <nav class="mobile-menu__links" aria-label="Primary navigation">
            <RouterLink
                v-for="(link, index) in links"
                :key="link.hash"
                :to="link.hash === '#contact' ? '/contact' : { path: '/', hash: link.hash }"
                :aria-current-value="
                    (route.hash || '#home') === link.hash ? 'location' : 'false'
                "
                :style="{ '--item-index': index }"
                @click="closeAfterNavigation(link.hash)"
            >
                {{ link.label }} <RollingArrow />
            </RouterLink>
            <RouterLink
                class="mobile-menu__cta"
                to="/contact"
                aria-current-value="false"
                :style="{ '--item-index': links.length }"
                @click="closeAfterNavigation('#contact')"
            >
                Let's Talk <RollingArrow />
            </RouterLink>
        </nav>
    </dialog>
</template>
