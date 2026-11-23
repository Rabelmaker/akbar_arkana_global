<script setup lang="ts">
import RollingArrow from "./RollingArrow.vue";
import SocialIcons from './SocialIcons.vue';
import FloatingMark from './FloatingMark.vue';
import CompanyFooter from './CompanyFooter.vue';
import { Mail, MessageCircle, MapPin } from '@lucide/vue';
import { ref } from 'vue';
import company from '../../config/company.json';
const sending = ref(false);
const status = ref('');
const errors = ref<Record<string, string[]>>({});
const whatsapp = company.whatsappUrl + '?text=' + encodeURIComponent('Hello Akbar Arkana Global, I would like to inquire about your services.');
async function submit(event: Event) {
    if (sending.value) return;
    const form = event.currentTarget as HTMLFormElement;
    const data = new FormData(form);
    sending.value = true;
    status.value = '';
    errors.value = {};
    try {
        const response = await fetch('/api/contact', {
            method: 'POST', body: data,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' },
        });
        if (response.status === 422) {
            errors.value = (await response.json()).errors;
            status.value = 'Please check the highlighted fields.';
        } else if (response.ok) {
            form.reset();
            status.value = 'Thank you. Your inquiry has been sent.';
        } else if (response.status === 429) {
            status.value = 'Too many attempts. Please try again in a minute.';
        } else if (response.status === 419) {
            status.value = 'Your session expired. Reload this page and try again.';
        } else {
            status.value = 'Your inquiry could not be sent. Please use email or WhatsApp.';
        }
    } catch {
        status.value = 'Please check your connection and try again.';
    } finally {
        sending.value = false;
    }
}
</script>
<template>
    <section id="contact" class="phase-section contact-section" tabindex="-1" aria-labelledby="contact-title">
        <div class="phase-section__inner contact-grid">
            <div><p class="phase-label">Start a conversation</p><h1 id="contact-title" class="phase-title">Have a Vision?<br />Let's Build It.</h1>
                <p>Whether you need a digital solution or want to explore international trade opportunities, we're ready to start a conversation.</p>
                <address><a :href="'mailto:' + company.email"><Mail /> {{ company.email }}</a><a :href="whatsapp"><MessageCircle /> WhatsApp {{ company.whatsappDisplay }} <RollingArrow /></a><span><MapPin /> {{ company.address }}</span></address>
                <SocialIcons />
            </div>
            <form class="inquiry-form" @submit.prevent="submit">
                <div class="contact-mark" aria-hidden="true"><FloatingMark scroll-rotation /></div>
                <p class="contact-lead">Tell us about your idea.</p>
                <h2 class="phase-label">01 / About you</h2>
                <label>Full name<input name="name" autocomplete="name" required maxlength="120" /><small>{{ errors.name?.[0] }}</small></label>
                <label>Email address<input name="email" type="email" autocomplete="email" required maxlength="254" /><small>{{ errors.email?.[0] }}</small></label>
                <label>Company name (optional)<input name="company" autocomplete="organization" maxlength="160" /><small>{{ errors.company?.[0] }}</small></label>
                <h2 class="phase-label">02 / Your project</h2>
                <label>Inquiry type<select name="type" required><option value="">Select a service</option><option v-for="type in company.inquiry_types" :key="type">{{ type }}</option></select><small>{{ errors.type?.[0] }}</small></label>
                <label>Message<textarea name="message" rows="4" maxlength="5000" required></textarea><small>{{ errors.message?.[0] }}</small></label>
                <label hidden>Website<input name="website" tabindex="-1" autocomplete="off" /></label>
                <button :disabled="sending">{{ sending ? 'Sending…' : 'Send inquiry' }} <RollingArrow v-if="!sending" /></button>
                <p role="status">{{ status }}</p>
            </form>
        </div>
    </section>
    <CompanyFooter />
</template>
