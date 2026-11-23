<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ metrics: Object, recentUsers: Array, recentSubscriptions: Array });
const page = usePage();
const firstName = computed(() => page.props.auth.user?.name?.split(' ')[0] || 'Admin');
const userActivationRate = computed(() => props.metrics.users
    ? Math.round((props.metrics.active_users / props.metrics.users) * 100)
    : 0);
const subscriptionCoverage = computed(() => props.metrics.accounts
    ? Math.min(100, Math.round((props.metrics.active_subscriptions / props.metrics.accounts) * 100))
    : 0);
const statusColor = (status) => ({
    active: 'success', trial: 'info', grace_period: 'warning',
    expired: 'error', suspended: 'error', cancelled: 'secondary',
}[status] || 'secondary');
const dateTime = (value) => new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

const cards = computed(() => [
    { value: props.metrics.users, label: 'Total Pengguna', caption: `${props.metrics.active_users} aktif`, icon: 'ri-group-line', color: 'primary' },
    { value: props.metrics.accounts, label: 'Tenant Accounts', caption: 'Workspace terdaftar', icon: 'ri-building-4-line', color: 'info' },
    { value: props.metrics.active_subscriptions, label: 'Subscription Aktif', caption: `${subscriptionCoverage.value}% tenant terlayani`, icon: 'ri-vip-crown-2-line', color: 'success' },
    { value: props.metrics.expiring_subscriptions, label: 'Segera Berakhir', caption: 'Dalam 7 hari', icon: 'ri-timer-flash-line', color: 'warning' },
]);
</script>

<template>
    <Head title="Super Admin Overview" />
    <AdminLayout>
        <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-6">
            <div>
                <div class="text-overline text-primary font-weight-bold">PLATFORM OPERATIONS</div>
                <h1 class="dashboard-page-title font-weight-bold mb-1">Control Center</h1>
                <p class="dashboard-page-copy text-medium-emphasis mb-0">Pantau pertumbuhan tenant, pengguna, dan entitlement WA Finance.</p>
            </div>
            <VChip color="success" variant="tonal" prepend-icon="ri-pulse-line" size="large">System Operational</VChip>
        </div>

        <VRow>
            <VCol cols="12" lg="8">
                <VCard rounded="xl" class="admin-hero overflow-hidden h-100">
                    <VCardText class="pa-6 pa-md-8 position-relative">
                        <span class="hero-grid" />
                        <VChip color="white" variant="tonal" class="mb-5">Super Admin Workspace</VChip>
                        <h2 class="admin-hero-title font-weight-bold text-white mb-3">Halo, {{ firstName }}! Platform siap dipantau. 🚀</h2>
                        <p class="text-white hero-copy mb-6">Kelola paket subscription, entitlement tenant, dan aktivitas platform dari satu pusat kendali.</p>
                        <div class="d-flex flex-wrap gap-3">
                            <VBtn :to="route('admin.subscriptions.index')" color="white" class="text-primary" prepend-icon="ri-bank-card-line">Kelola Subscription</VBtn>
                            <VBtn :to="route('admin.subscription-plans.index')" color="white" variant="outlined" prepend-icon="ri-price-tag-3-line">Atur Paket</VBtn>
                        </div>
                    </VCardText>
                </VCard>
            </VCol>
            <VCol cols="12" lg="4">
                <VCard rounded="xl" class="h-100">
                    <VCardItem>
                        <template #prepend><VAvatar color="success" variant="tonal"><VIcon icon="ri-shield-check-line" /></VAvatar></template>
                        <VCardTitle>Platform Health</VCardTitle>
                        <VCardSubtitle>Indikator operasional saat ini</VCardSubtitle>
                    </VCardItem>
                    <VCardText>
                        <div class="d-flex justify-space-between text-body-2 mb-2"><span>Aktivasi pengguna</span><strong>{{ userActivationRate }}%</strong></div>
                        <div class="admin-progress mb-5"><span class="bg-success" :style="{ width: `${userActivationRate}%` }" /></div>
                        <div class="d-flex justify-space-between text-body-2 mb-2"><span>Cakupan subscription</span><strong>{{ subscriptionCoverage }}%</strong></div>
                        <div class="admin-progress mb-5"><span class="bg-primary" :style="{ width: `${subscriptionCoverage}%` }" /></div>
                        <VAlert :color="metrics.expired_subscriptions ? 'warning' : 'success'" variant="tonal" density="compact" :icon="metrics.expired_subscriptions ? 'ri-alarm-warning-line' : 'ri-checkbox-circle-line'">
                            {{ metrics.expired_subscriptions ? `${metrics.expired_subscriptions} subscription perlu perhatian.` : 'Tidak ada entitlement bermasalah.' }}
                        </VAlert>
                    </VCardText>
                </VCard>
            </VCol>

            <VCol v-for="card in cards" :key="card.label" cols="12" sm="6" xl="3">
                <VCard rounded="xl" class="admin-metric h-100">
                    <VCardText class="d-flex align-center pa-5">
                        <VAvatar :color="card.color" variant="tonal" rounded="lg" size="50" class="me-4"><VIcon :icon="card.icon" size="26" /></VAvatar>
                        <div><div class="text-h5 font-weight-bold">{{ card.value }}</div><div class="text-body-2 font-weight-medium">{{ card.label }}</div><div class="text-caption text-medium-emphasis">{{ card.caption }}</div></div>
                    </VCardText>
                </VCard>
            </VCol>

            <VCol cols="12" xl="7">
                <VCard rounded="xl" class="h-100">
                    <VCardItem class="pa-5">
                        <template #prepend><VAvatar color="primary" variant="tonal"><VIcon icon="ri-user-add-line" /></VAvatar></template>
                        <VCardTitle>Pengguna Terbaru</VCardTitle>
                        <VCardSubtitle>Registrasi terbaru pada platform</VCardSubtitle>
                    </VCardItem>
                    <VDivider />
                    <VTable class="admin-table">
                        <thead><tr><th>Pengguna</th><th>Status</th><th class="d-none d-md-table-cell">Bergabung</th></tr></thead>
                        <tbody>
                            <tr v-for="user in recentUsers" :key="user.id">
                                <td><div class="d-flex align-center"><VAvatar color="primary" variant="tonal" size="38" class="me-3">{{ user.name.charAt(0).toUpperCase() }}</VAvatar><div><div class="font-weight-bold">{{ user.name }}</div><div class="text-caption text-medium-emphasis">{{ user.email }}</div></div></div></td>
                                <td><VChip size="small" :color="user.status === 'active' ? 'success' : 'warning'" variant="tonal">{{ user.status }}</VChip></td>
                                <td class="d-none d-md-table-cell text-medium-emphasis">{{ dateTime(user.created_at) }}</td>
                            </tr>
                            <tr v-if="!recentUsers.length"><td colspan="3" class="text-center text-medium-emphasis py-10">Belum ada pengguna.</td></tr>
                        </tbody>
                    </VTable>
                </VCard>
            </VCol>

            <VCol cols="12" xl="5">
                <VCard rounded="xl" class="h-100">
                    <VCardItem class="pa-5">
                        <template #prepend><VAvatar color="info" variant="tonal"><VIcon icon="ri-history-line" /></VAvatar></template>
                        <VCardTitle>Subscription Terbaru</VCardTitle>
                        <VCardSubtitle>Aktivitas entitlement tenant</VCardSubtitle>
                        <template #append><VBtn :to="route('admin.subscriptions.index')" color="primary" variant="text">Lihat semua</VBtn></template>
                    </VCardItem>
                    <VCardText class="pt-2">
                        <div v-for="subscription in recentSubscriptions" :key="subscription.id" class="subscription-row">
                            <VAvatar :color="statusColor(subscription.status)" variant="tonal" rounded="lg" class="me-3"><VIcon icon="ri-building-2-line" /></VAvatar>
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-space-between gap-2"><strong class="text-truncate">{{ subscription.account?.name }}</strong><VChip :color="statusColor(subscription.status)" variant="tonal" size="x-small">{{ subscription.status }}</VChip></div>
                                <div class="text-caption text-medium-emphasis">{{ subscription.plan?.name }} · {{ subscription.end_date || 'Lifetime' }}</div>
                            </div>
                        </div>
                        <div v-if="!recentSubscriptions.length" class="text-center text-medium-emphasis py-8">Belum ada subscription.</div>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>
    </AdminLayout>
</template>

<style scoped>
.admin-hero { background: linear-gradient(118deg, #666cff 0%, #8c91ff 100%); box-shadow: 0 12px 30px rgba(102, 108, 255, .28); }
.admin-hero-title { max-width: 760px; font-size: clamp(1.65rem, 2.25vw, 2.15rem); line-height: 1.24; letter-spacing: -.035em; }
.hero-copy { max-width: 650px; opacity: .88; }
.hero-grid { position: absolute; inset: 0; opacity: .14; background-image: linear-gradient(rgba(255,255,255,.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.4) 1px, transparent 1px); background-size: 32px 32px; mask-image: linear-gradient(90deg, transparent, #000); }
.admin-metric { transition: transform .2s ease, box-shadow .2s ease; }
.admin-metric:hover { transform: translateY(-3px); box-shadow: 0 9px 26px rgba(47,43,61,.1); }
.admin-progress { height: 8px; overflow: hidden; border-radius: 999px; background: rgba(var(--v-theme-on-surface), .08); }
.admin-progress span { display: block; height: 100%; border-radius: inherit; }
.subscription-row { display: flex; align-items: center; padding-block: 13px; border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); }
.subscription-row:last-child { border-bottom: 0; }
.min-width-0 { min-width: 0; }
</style>
