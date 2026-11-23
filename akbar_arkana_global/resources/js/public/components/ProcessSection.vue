<script setup lang="ts">
const steps = [
    ['Discovery', 'We learn how your business works and identify the opportunity.'],
    ['Strategy', 'Together we define the scope, technical architecture, and priorities.'],
    ['Design', 'We shape the interface around the people who will use it.'],
    ['Development', 'We build and integrate the product around the agreed scope.'],
    ['Delivery & Support', 'We test, deploy, and refine the product as your needs evolve.'],
];
</script>
<template>
    <section id="process" class="phase-section process-section" tabindex="-1" aria-labelledby="process-title">
        <div class="phase-section__inner">
            <p class="phase-label">Our process</p>
            <h2 id="process-title" class="phase-title">From Ideas to Impact.</h2>
            <ol class="process-list">
                <li v-for="([title, copy], index) in steps" :key="title">
                    <small>0{{ index + 1 }}</small><div><h3>{{ title }}</h3><p>{{ copy }}</p></div>
                </li>
            </ol>
        </div>
    </section>
</template>
