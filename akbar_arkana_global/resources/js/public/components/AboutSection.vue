<script setup lang="ts">
import RollingArrow from "./RollingArrow.vue";
import { RouterLink } from 'vue-router';
import company from '../../config/company.json';
</script>

<template>
    <section
        id="about"
        class="phase-section about-section"
        tabindex="-1"
        aria-labelledby="about-title"
    >
        <div class="phase-section__inner">
            <div class="about-intro">
                <div data-phase-reveal>
                    <p class="phase-label">About AAG</p>
                    <h2 id="about-title" class="phase-title">
                        Two Industries.<br />One Vision.
                    </h2>
                    <p class="about-intro__description">
                        Akbar Arkana Global brings together technology,
                        innovation, and international commerce. We develop
                        digital solutions that help businesses grow while
                        connecting Indonesian commodities with global market
                        opportunities.
                    </p>
                </div>
                <img class="editorial-photo" src="/assets/editorial/trade.webp" alt="Container shipping and international commerce" loading="lazy" data-phase-reveal />
            </div>
            <div class="about-divisions">
                <article data-phase-reveal>
                    <span class="phase-label" aria-hidden="true">01</span>
                    <h3>Technology &amp; Digital Solutions</h3>
                    <p>
                        We develop websites, business systems, SaaS platforms,
                        IoT applications, and custom digital solutions.
                    </p>
                    <RouterLink to="/#expertise"
                        >Explore our expertise
                        <RollingArrow /></RouterLink>
                </article>
                <article data-phase-reveal>
                    <span class="phase-label" aria-hidden="true">02</span>
                    <h3>International Export &amp; Trading</h3>
                    <p>
                        Through {{ company.exportBrand }}, we introduce
                        Indonesian spices, tropical fruits, and agricultural
                        commodities to international markets.
                    </p>
                    <a
                        :href="company.exportWebsite"
                        target="_blank"
                        rel="noopener noreferrer"
                        >Visit Akbar Spices
                        <RollingArrow /></a>
                </article>
            </div>
        </div>
    </section>
</template>
