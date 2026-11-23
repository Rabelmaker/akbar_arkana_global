<script setup lang="ts">
import RollingArrow from "./RollingArrow.vue";
import { ref } from 'vue';
import { projects } from '../projects';

const internal = projects.find(project => !project.url)!;
const dialog = ref<HTMLDialogElement>();
</script>
<template>
    <section id="projects" class="selected-works" tabindex="-1" aria-labelledby="projects-title">
        <header class="works-intro">
            <h2 id="projects-title" class="phase-title">Selected Works.</h2>
            <p>Digital products for environmental monitoring, business operations, and international trade.</p>
        </header>
        <article v-for="(project, index) in projects" :key="project.id" class="work-scene"
            :data-navy="project.id === 'office-management' ? '' : undefined" :aria-labelledby="project.id">
            <p class="phase-label">{{ String(index + 1).padStart(2, '0') }} / {{ project.category }}</p>
            <div class="work-stage">
                <div class="work-type" aria-hidden="true">
                    <span v-for="n in 2" :key="n">{{ project.title }} · {{ project.title }} ·&nbsp;</span>
                </div>
                <figure class="project-visual">
                    <component :is="project.url ? 'a' : 'button'" :href="project.url || undefined" data-view
                        :aria-label="'View project: ' + project.title" @click="!project.url && dialog?.showModal()">
                        <img :src="project.image" :alt="project.alt" width="1440" height="900" loading="lazy" />
                    </component>
                    <figcaption>{{ project.caption }}</figcaption>
                </figure>
            </div>
            <div class="project-row__copy">
                <h3 :id="project.id">{{ project.title }}</h3>
                <p v-if="'client' in project">{{ project.client }}</p>
                <p class="project-row__description">{{ project.description }}</p>
                <a v-if="project.url" class="project-link" :href="project.url">{{ project.id === 'office-management' ? 'Visit client website' : 'Visit website' }} <RollingArrow /></a>
                <button v-else class="project-link" @click="dialog?.showModal()">View project <RollingArrow /></button>
            </div>
        </article>
        <dialog ref="dialog" class="project-dialog" aria-labelledby="aqms-dialog-title">
            <form method="dialog"><button autofocus>Close ✕</button></form>
            <h2 id="aqms-dialog-title">{{ internal.title }}</h2>
            <img :src="internal.image" :alt="internal.alt" loading="lazy" />
            <small>{{ internal.caption }}</small>
            <p>{{ internal.description }}</p>
        </dialog>
    </section>
</template>
