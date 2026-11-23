<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>

<template>
    <div>
        <h1 class="text-4xl font-semibold">Page not found</h1>
        <RouterLink class="mt-6 inline-block underline underline-offset-4" to="/">
            Return to the homepage
        </RouterLink>
    </div>
</template>
