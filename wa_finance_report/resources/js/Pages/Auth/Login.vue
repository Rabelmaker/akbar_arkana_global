<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ canResetPassword: Boolean, status: String });

const passwordVisible = ref(false);
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <GuestLayout illustrated>
        <Head title="Masuk" />
        <VCardText>
            <div class="text-overline text-primary font-weight-bold mb-2">WELCOME BACK</div>
            <h1 class="text-h4 font-weight-bold mb-2">Selamat datang! 👋</h1>
            <p class="text-medium-emphasis mb-7">Masuk ke workspace untuk melanjutkan pengelolaan keuangan Anda.</p>
            <VAlert v-if="status" type="success" variant="tonal" class="mb-5">{{ status }}</VAlert>
            <VForm @submit.prevent="submit">
                <VTextField
                    v-model="form.email"
                    label="Email"
                    type="email"
                    autocomplete="username"
                    autofocus
                    placeholder="nama@perusahaan.com"
                    :error-messages="form.errors.email"
                    class="mb-4"
                />
                <VTextField
                    v-model="form.password"
                    label="Password"
                    :type="passwordVisible ? 'text' : 'password'"
                    autocomplete="current-password"
                    placeholder="············"
                    :append-inner-icon="passwordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                    :error-messages="form.errors.password"
                    @click:append-inner="passwordVisible = !passwordVisible"
                />
                <div class="d-flex align-center flex-wrap justify-space-between gap-2 mb-6">
                    <VCheckbox v-model="form.remember" label="Ingat saya" hide-details density="compact" />
                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-primary">Lupa password?</Link>
                </div>
                <VBtn block color="primary" size="large" type="submit" :loading="form.processing" prepend-icon="ri-login-box-line">Masuk</VBtn>
            </VForm>
            <div class="text-center mt-7 text-body-2">
                Belum punya akun?
                <Link :href="route('register')" class="text-primary font-weight-medium">Daftar</Link>
            </div>
        </VCardText>
    </GuestLayout>
</template>
