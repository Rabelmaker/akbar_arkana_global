<script setup lang="ts">
import RollingArrow from "./RollingArrow.vue";
import { ArrowDown } from '@lucide/vue';
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import company from '../../config/company.json';
import { useHeroPointer } from '../composables/useHeroPointer';
import FloatingMark from './FloatingMark.vue';

const stage = ref<HTMLElement>();
useHeroPointer(stage);
</script>

<template>
    <section
        id="home"
        ref="stage"
        class="hero"
        tabindex="-1"
        aria-labelledby="hero-title"
    >
        <div class="hero__grid">
            <div class="hero__copy">
                <div class="hero__heading">
                    <p class="hero__eyebrow" data-hero-enter>
                        Technology / Global trade
                    </p>
                    <p class="hero__company" data-hero-enter>
                        {{ company.name }}
                    </p>
                    <h1 id="hero-title">
                        <span class="hero__headline">Digital by design.</span
                        >{{ ' ' }}
                        <span class="hero__headline">Global by nature.</span>
                    </h1>
                </div>
                <div class="hero__actions">
                    <RouterLink
                        class="hero-button hero-button--primary"
                        to="/#projects"
                        data-hero-enter
                    >
                        Explore our work
                        <RollingArrow />
                    </RouterLink>
                    <RouterLink
                        class="hero-button hero-button--secondary"
                        to="/#about"
                        data-hero-enter
                    >
                        Our businesses
                        <ArrowDown :size="17" aria-hidden="true" />
                    </RouterLink>
                </div>
            </div>
            <div class="hero__visual" data-hero-mark>
                <FloatingMark />
            </div>
        </div>
        <div class="hero__rail">
            <RouterLink class="hero__scroll" to="/#about">
                <ArrowDown :size="16" aria-hidden="true" /> Discover AAG
            </RouterLink>
        </div>
    </section>
</template>
