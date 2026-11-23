<script setup lang="ts">
import RollingArrow from "./components/RollingArrow.vue";
import { ref } from 'vue';
import ProcessSection from './components/ProcessSection.vue';
import { RouterLink } from 'vue-router';
import CompanyFooter from './components/CompanyFooter.vue';
import AboutSection from './components/AboutSection.vue';
import BrandIntro from './components/BrandIntro.vue';
import ExpertiseSection from './components/ExpertiseSection.vue';
import ExportSection from './components/ExportSection.vue';
import HeroSection from './components/HeroSection.vue';
import ProjectsSection from './components/ProjectsSection.vue';
import { useLandingMotion } from './composables/useLandingMotion';

const page = ref<HTMLElement>();
useLandingMotion(page);
</script>

<template>
    <div ref="page">
        <BrandIntro />
        <HeroSection />
        <AboutSection />
        <ExpertiseSection />
        <ProjectsSection />
        <div class="export-chapter">
            <ExportSection />
            <ProcessSection />
        </div>
        <section id="contact" class="project-invite" tabindex="-1">
            <p class="phase-label">Next chapter</p>
            <h2>Your next project.<br />Starts here.</h2>
            <RouterLink to="/contact">Start a project <RollingArrow /></RouterLink>
        </section>
        <CompanyFooter />
    </div>
</template>
