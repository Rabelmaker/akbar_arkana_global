<script setup lang="ts">
import RollingArrow from "./RollingArrow.vue";
import { RouterLink } from 'vue-router';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { serviceGroups } from '../services';
</script>
<template>
    <section id="expertise" class="capabilities" data-navy tabindex="-1" aria-labelledby="expertise-title">
        <h2 id="expertise-title" class="phase-label">Our expertise</h2>
        <details v-for="(group, i) in serviceGroups" :key="group.label" @toggle="ScrollTrigger.refresh()">
            <summary><small>(0{{ i + 1 }})</small>{{ group.label }}<span aria-hidden="true">+</span></summary>
            <ul>
                <li v-for="service in group.services" :key="service.number">
                    <RouterLink :to="group.target">{{ service.title }} <RollingArrow /></RouterLink>
                    <p>{{ service.description }}</p>
                </li>
            </ul>
        </details>
    </section>
</template>
