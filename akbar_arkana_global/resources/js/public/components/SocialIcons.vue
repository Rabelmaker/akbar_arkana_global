<script setup lang="ts">
import company from '../../config/company.json';
const profiles = [
    { name: 'Instagram', url: company.instagramUrl },
    { name: 'Facebook', url: company.facebookUrl },
];
</script>
<template>
    <div class="social-icons">
        <component v-for="profile in profiles" :key="profile.name" :is="profile.url ? 'a' : 'span'"
            :href="profile.url || undefined" :role="profile.url ? undefined : 'img'"
            :aria-label="profile.name + ' · ' + company.brandName" :title="profile.name">
            <svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22">
                <g v-if="profile.name === 'Instagram'" stroke="currentColor" stroke-width="2" fill="none">
                    <rect x="3" y="3" width="18" height="18" rx="5" />
                    <circle cx="12" cy="12" r="4" /><circle cx="17.6" cy="6.6" r="1" fill="currentColor" stroke="none" />
                </g>
                <path v-else fill="currentColor" d="M14 22v-9h3l.5-3.5H14V7.4c0-1 .3-1.7 1.8-1.7h2V2.5c-.5-.1-1.6-.2-2.8-.2-2.9 0-4.8 1.7-4.8 4.9v2.3H7V13h3.2v9z" />
            </svg>
        </component>
    </div>
</template>
