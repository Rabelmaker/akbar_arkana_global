<script setup>
import logo from '@images/logo.svg';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const page = usePage();
const authenticated = computed(() => Boolean(page.props.auth.user));

const features = [
    {
        title: 'Multi-account',
        description: 'Data setiap pelanggan dipisahkan berdasarkan account aktif.',
        icon: 'ri-building-2-line',
    },
    {
        title: 'Financial ready',
        description: 'Foundation disiapkan untuk transaksi, budget, dan laporan.',
        icon: 'ri-line-chart-line',
    },
    {
        title: 'Integration ready',
        description: 'REST API versioned dengan autentikasi Laravel Sanctum.',
        icon: 'ri-plug-line',
    },
];
</script>

<template>
    <Head title="Home" />

    <VApp>
        <VAppBar flat border="b">
            <VContainer class="d-flex align-center">
                <img :src="logo" width="36" height="36" alt="WA Finance Report" />
                <span class="text-h6 font-weight-bold ms-3">WA Finance Report</span>
                <VSpacer />
                <Link v-if="authenticated" :href="route('dashboard')">
                    <VBtn color="primary">Dashboard</VBtn>
                </Link>
                <template v-else>
                    <Link v-if="canLogin" :href="route('login')">
                        <VBtn variant="text" class="me-2">Masuk</VBtn>
                    </Link>
                    <Link v-if="canRegister" :href="route('register')">
                        <VBtn color="primary">Daftar</VBtn>
                    </Link>
                </template>
            </VContainer>
        </VAppBar>

        <VMain class="bg-background">
            <VContainer class="py-16">
                <VRow align="center" class="py-md-12">
                    <VCol cols="12" md="7">
                        <VChip color="primary" variant="tonal" class="mb-5">
                            SaaS Financial Reporting
                        </VChip>
                        <h1 class="text-h2 font-weight-bold mb-5">
                            Catat dan pahami keuangan dengan lebih mudah.
                        </h1>
                        <p class="text-h6 text-medium-emphasis mb-8">
                            Website dan REST API yang disiapkan untuk pencatatan keuangan
                            multi-account serta integrasi WhatsApp di tahap berikutnya.
                        </p>
                        <Link :href="authenticated ? route('dashboard') : route('register')">
                            <VBtn color="primary" size="large" append-icon="ri-arrow-right-line">
                                {{ authenticated ? 'Buka Dashboard' : 'Mulai Sekarang' }}
                            </VBtn>
                        </Link>
                    </VCol>
                    <VCol cols="12" md="5">
                        <VCard elevation="8">
                            <VCardText class="pa-8">
                                <VAvatar color="primary" variant="tonal" size="64" class="mb-5">
                                    <VIcon icon="ri-wallet-3-line" size="34" />
                                </VAvatar>
                                <h2 class="text-h4 mb-3">Foundation siap</h2>
                                <p class="text-medium-emphasis mb-0">
                                    Laravel 13, Vue 3, Inertia, Sanctum, role permission,
                                    account isolation, dan audit log telah terhubung.
                                </p>
                            </VCardText>
                        </VCard>
                    </VCol>
                </VRow>

                <VRow class="mt-8">
                    <VCol v-for="feature in features" :key="feature.title" cols="12" md="4">
                        <VCard height="100%">
                            <VCardText class="pa-6">
                                <VIcon :icon="feature.icon" color="primary" size="32" class="mb-4" />
                                <h2 class="text-h6 mb-2">{{ feature.title }}</h2>
                                <p class="text-medium-emphasis mb-0">{{ feature.description }}</p>
                            </VCardText>
                        </VCard>
                    </VCol>
                </VRow>
            </VContainer>
        </VMain>
    </VApp>
</template>
