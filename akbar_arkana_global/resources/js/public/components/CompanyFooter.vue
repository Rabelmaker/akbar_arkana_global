<script setup lang="ts">
import SocialIcons from './SocialIcons.vue';
import company from '../../config/company.json';
</script>
<template>
    <footer class="company-footer" data-navy>
        <div class="footer-columns">
            <div><img src="/assets/branding/brand-symbol.webp" width="86" alt="AAG" />
                <p>Technology solutions and international trade.</p><address>{{ company.address }}</address></div>
            <nav aria-label="Footer navigation"><h2>Navigation</h2><a v-for="id in ['home','about','expertise','projects','contact']" :key="id" :href="id === 'contact' ? '/contact' : '/#' + id">{{ id }}</a></nav>
            <div><h2>Businesses</h2><a href="/#expertise">Technology &amp; Digital Solutions</a><a href="/#akbar-spices">Akbar Spices International</a></div>
            <div><h2>Contact</h2><a :href="'mailto:' + company.email">{{ company.email }}</a><a :href="company.whatsappUrl">WhatsApp {{ company.whatsappDisplay }}</a><SocialIcons /></div>
        </div>
        <small>© {{ new Date().getFullYear() }} {{ company.name }}. All rights reserved.</small>
    </footer>
</template>
