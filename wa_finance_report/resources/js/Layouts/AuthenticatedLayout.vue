<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from './AdminLayout.vue';
import UserLayout from './UserLayout.vue';

const page = usePage();
const layout = computed(() => page.props.auth.user?.roles?.includes('super-admin') ? AdminLayout : UserLayout);
</script>

<template>
    <component :is="layout"><slot /><template v-if="$slots.header" #header><slot name="header" /></template></component>
</template>
