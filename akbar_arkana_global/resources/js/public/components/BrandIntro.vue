<script setup lang="ts">
import company from '../../config/company.json';
</script>

<template>
    <div class="brand-intro" data-intro aria-hidden="true">
        <div class="brand-intro__badge" data-intro-mark>
            <img
                src="/assets/branding/brand-symbol.webp"
                alt=""
                width="190"
                height="68"
            />
            <span class="brand-intro__sweep" data-intro-sweep></span>
        </div>
        <span class="brand-intro__name" data-intro-name>{{
            company.brandName
        }}</span>
    </div>
</template>
