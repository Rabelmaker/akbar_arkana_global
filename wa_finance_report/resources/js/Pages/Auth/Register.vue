<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const submit = () => form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <GuestLayout>
        <Head title="Daftar" />
        <VCardText class="px-0">
            <h1 class="text-h4 mb-2">Buat akun</h1>
            <p class="text-medium-emphasis mb-6">Account utama akan dibuat otomatis setelah registrasi.</p>
            <VForm @submit.prevent="submit">
                <VTextField v-model="form.name" label="Nama" prepend-inner-icon="ri-user-line" :error-messages="form.errors.name" class="mb-3" />
                <VTextField v-model="form.email" label="Email" type="email" prepend-inner-icon="ri-mail-line" :error-messages="form.errors.email" class="mb-3" />
                <VTextField v-model="form.password" label="Password" type="password" prepend-inner-icon="ri-lock-line" :error-messages="form.errors.password" class="mb-3" />
                <VTextField v-model="form.password_confirmation" label="Konfirmasi password" type="password" prepend-inner-icon="ri-lock-password-line" class="mb-5" />
                <VBtn block color="primary" size="large" type="submit" :loading="form.processing">Daftar</VBtn>
            </VForm>
            <div class="text-center mt-6 text-body-2">
                Sudah punya akun?
                <Link :href="route('login')" class="text-primary font-weight-medium">Masuk</Link>
            </div>
        </VCardText>
    </GuestLayout>
</template>
