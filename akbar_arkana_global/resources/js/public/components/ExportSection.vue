<script setup lang="ts">
import RollingArrow from "./RollingArrow.vue";
import company from '../../config/company.json';
const commodities = [
    { file: 'nutmeg', label: 'Nutmeg' },
    { file: 'black-pepper', label: 'Black pepper' },
    { file: 'star-anise', label: 'Star anise' },
    { file: 'white-pepper', label: 'White pepper' },
    { file: 'red-chilli', label: 'Red chilli' },
    { file: 'cassia-cinnamon', label: 'Cassia cinnamon' },
    { file: 'andaliman', label: 'Andaliman pepper' },
    { file: 'clove-bud-stem', label: 'Cloves' },
];
</script>
<template>
    <section id="akbar-spices" class="export-scene" tabindex="-1" aria-labelledby="export-title">
        <div class="export-frame">
        <p class="phase-label">Akbar Spices International</p>
        <h2 id="export-title">Indonesian spices.<br /><span>Global markets.</span></h2>
        <div class="export-gallery">
            <figure v-for="item in commodities" :key="item.file">
                <img :src="'/assets/editorial/' + item.file + '-640.webp'" :alt="item.label" loading="lazy" />
                <figcaption>{{ item.label }}</figcaption>
            </figure>
        </div>
        <div class="export-copy">
            <p>From Indonesian growers to international buyers, Akbar Spices connects sourcing, product specifications, and export inquiries.</p>
            <a class="project-link" :href="company.exportWebsite">Explore Akbar Spices <RollingArrow /></a>
        </div>
        </div>
    </section>
</template>
