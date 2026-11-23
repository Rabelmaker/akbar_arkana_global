<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    account: Object,
    metrics: Object,
    financialAccounts: Array,
    budgetSummary: Object,
});

const page = usePage();
const subscription = computed(() => page.props.auth.subscription);
const firstName = computed(() => page.props.auth.user?.name?.split(' ')[0] || 'Teman');
const rupiah = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: props.account.currency,
    maximumFractionDigits: 0,
}).format(Number(value || 0));

const cards = computed(() => [
    { value: rupiah(props.metrics.balance), label: 'Total Saldo', caption: `${props.metrics.financial_account_count} rekening aktif`, icon: 'ri-wallet-3-line', color: 'primary' },
    { value: rupiah(props.metrics.income_today), label: 'Pemasukan Hari Ini', caption: 'Transaksi masuk', icon: 'ri-arrow-down-circle-line', color: 'success' },
    { value: rupiah(props.metrics.expense_today), label: 'Pengeluaran Hari Ini', caption: 'Transaksi keluar', icon: 'ri-arrow-up-circle-line', color: 'error' },
    { value: props.metrics.category_count, label: 'Kategori Aktif', caption: 'Siap untuk pencatatan', icon: 'ri-price-tag-3-line', color: 'info' },
]);

const maxBalance = computed(() => Math.max(...props.financialAccounts.map((item) => Math.abs(Number(item.balance))), 1));
</script>

<template>
    <Head title="Dashboard" />
    <UserLayout>
        <VAlert
            v-if="!subscription?.can_write"
            type="warning"
            variant="tonal"
            prominent
            rounded="xl"
            class="mb-6"
            title="Subscription tidak aktif"
        >
            Data tetap dapat dilihat, tetapi penambahan dan perubahan data finansial dinonaktifkan.
        </VAlert>

        <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-6">
            <div>
                <div class="text-overline text-primary font-weight-bold">FINANCIAL OVERVIEW</div>
                <h1 class="dashboard-page-title font-weight-bold mb-1">Dashboard Keuangan</h1>
                <p class="dashboard-page-copy text-medium-emphasis mb-0">Ringkasan real-time untuk {{ account.name }}</p>
            </div>
            <VChip color="primary" variant="tonal" prepend-icon="ri-time-line" size="large">{{ account.timezone }}</VChip>
        </div>

        <VRow>
            <VCol cols="12" lg="7">
                <VCard class="hero-card overflow-hidden h-100" rounded="xl">
                    <VCardText class="pa-6 pa-md-8 position-relative">
                        <div class="hero-orb hero-orb-one" />
                        <div class="hero-orb hero-orb-two" />
                        <VChip color="white" variant="tonal" class="mb-5">Keuangan lebih terkendali</VChip>
                        <h2 class="dashboard-hero-title font-weight-bold text-white mb-3">Selamat datang, {{ firstName }}! 👋</h2>
                        <p class="text-body-1 text-white hero-copy mb-6">
                            Catat arus kas, pantau perubahan saldo, dan pindahkan dana antar rekening dari satu workspace.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <VBtn :to="route('transactions.index')" color="white" class="text-primary" prepend-icon="ri-add-circle-line">Catat Transaksi</VBtn>
                            <VBtn :to="route('financial-accounts.index')" color="white" variant="outlined" prepend-icon="ri-wallet-3-line">Kelola Rekening</VBtn>
                        </div>
                    </VCardText>
                </VCard>
            </VCol>
            <VCol cols="12" lg="5">
                <VCard rounded="xl" class="h-100">
                    <VCardItem>
                        <template #prepend><VAvatar color="primary" variant="tonal"><VIcon icon="ri-pulse-line" /></VAvatar></template>
                        <VCardTitle>Kesiapan Workspace</VCardTitle>
                        <VCardSubtitle>Fondasi pencatatan keuangan</VCardSubtitle>
                    </VCardItem>
                    <VCardText>
                        <div class="d-flex justify-space-between text-body-2 mb-2"><span>Rekening keuangan</span><strong>{{ metrics.financial_account_count ? 'Siap' : 'Belum diatur' }}</strong></div>
                        <div class="progress-track mb-5"><span class="progress-fill bg-primary" :style="{ width: `${metrics.financial_account_count ? 100 : 25}%` }" /></div>
                        <div class="d-flex justify-space-between text-body-2 mb-2"><span>Kategori transaksi</span><strong>{{ metrics.category_count }} kategori</strong></div>
                        <div class="progress-track mb-5"><span class="progress-fill bg-success" :style="{ width: `${Math.min(metrics.category_count * 12, 100)}%` }" /></div>
                        <VAlert color="success" variant="tonal" density="compact" icon="ri-checkbox-circle-line">Modul transaksi dan pembaruan saldo sudah aktif.</VAlert>
                    </VCardText>
                </VCard>
            </VCol>

            <VCol v-for="card in cards" :key="card.label" cols="12" sm="6" xl="3">
                <VCard rounded="xl" class="metric-card h-100">
                    <VCardText class="d-flex align-center pa-5">
                        <VAvatar :color="card.color" variant="tonal" rounded="lg" size="48" class="me-4">
                            <VIcon :icon="card.icon" size="25" />
                        </VAvatar>
                        <div class="min-width-0">
                            <div class="text-h6 font-weight-bold text-truncate">{{ card.value }}</div>
                            <div class="text-body-2">{{ card.label }}</div>
                            <div class="text-caption text-medium-emphasis">{{ card.caption }}</div>
                        </div>
                    </VCardText>
                </VCard>
            </VCol>

            <VCol cols="12">
                <VCard rounded="xl" class="budget-overview">
                    <VCardText class="pa-5 pa-md-6">
                        <div class="d-flex flex-wrap align-center justify-space-between gap-4">
                            <div class="d-flex align-center gap-4">
                                <VAvatar color="warning" variant="tonal" rounded="lg" size="52"><VIcon icon="ri-pie-chart-2-line" size="27" /></VAvatar>
                                <div>
                                    <div class="text-h6 font-weight-bold">Budget Periode Aktif</div>
                                    <div class="text-body-2 text-medium-emphasis">{{ budgetSummary.active }} budget · {{ budgetSummary.alerts }} perlu perhatian</div>
                                </div>
                            </div>
                            <div class="budget-stat"><span>Dialokasikan</span><strong>{{ rupiah(budgetSummary.allocated) }}</strong></div>
                            <div class="budget-stat"><span>Terpakai</span><strong>{{ rupiah(budgetSummary.used) }}</strong></div>
                            <div class="budget-stat"><span>Sisa</span><strong :class="budgetSummary.remaining < 0 ? 'text-error' : 'text-success'">{{ rupiah(budgetSummary.remaining) }}</strong></div>
                            <VBtn :to="route('budgets.index')" color="primary" variant="tonal" append-icon="ri-arrow-right-line">Kelola Budget</VBtn>
                        </div>
                    </VCardText>
                </VCard>
            </VCol>

            <VCol cols="12" lg="7">
                <VCard rounded="xl" class="h-100">
                    <VCardItem>
                        <VCardTitle>Komposisi Saldo</VCardTitle>
                        <VCardSubtitle>Sumber dana aktif berdasarkan saldo saat ini</VCardSubtitle>
                        <template #append><VBtn :to="route('financial-accounts.index')" variant="text" color="primary">Lihat semua</VBtn></template>
                    </VCardItem>
                    <VCardText v-if="financialAccounts.length" class="pt-2">
                        <div v-for="item in financialAccounts" :key="item.id" class="d-flex align-center mb-5">
                            <VAvatar :color="item.color" rounded="lg" variant="tonal" class="me-4"><VIcon :icon="item.icon" /></VAvatar>
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-space-between gap-3 mb-2">
                                    <div><div class="font-weight-bold">{{ item.name }}</div><div class="text-caption text-medium-emphasis">{{ item.type }}</div></div>
                                    <strong>{{ rupiah(item.balance) }}</strong>
                                </div>
                                <div class="progress-track"><span class="progress-fill" :style="{ width: `${Math.abs(Number(item.balance)) / maxBalance * 100}%`, background: item.color }" /></div>
                            </div>
                        </div>
                    </VCardText>
                    <VCardText v-else class="text-center py-10">
                        <VAvatar color="primary" variant="tonal" size="64"><VIcon icon="ri-wallet-add-line" size="32" /></VAvatar>
                        <h3 class="text-h6 mt-4">Belum ada rekening</h3>
                        <p class="text-medium-emphasis">Tambahkan sumber dana pertama untuk mulai.</p>
                    </VCardText>
                </VCard>
            </VCol>
            <VCol cols="12" lg="5">
                <VCard rounded="xl" class="h-100">
                    <VCardItem>
                        <VCardTitle>Akses Cepat</VCardTitle>
                        <VCardSubtitle>Kelola fondasi finansial</VCardSubtitle>
                    </VCardItem>
                    <VCardText class="quick-grid">
                        <Link :href="route('financial-accounts.index')" class="quick-action">
                            <VAvatar color="primary" variant="tonal"><VIcon icon="ri-bank-card-line" /></VAvatar>
                            <div><strong>Rekening</strong><small>Saldo & sumber dana</small></div>
                            <VIcon icon="ri-arrow-right-s-line" />
                        </Link>
                        <Link :href="route('transaction-categories.index')" class="quick-action">
                            <VAvatar color="success" variant="tonal"><VIcon icon="ri-folder-chart-line" /></VAvatar>
                            <div><strong>Kategori</strong><small>Struktur pemasukan & biaya</small></div>
                            <VIcon icon="ri-arrow-right-s-line" />
                        </Link>
                        <Link :href="route('transactions.index')" class="quick-action">
                            <VAvatar color="info" variant="tonal"><VIcon icon="ri-exchange-dollar-line" /></VAvatar>
                            <div><strong>Transaksi</strong><small>Catat & pantau arus kas</small></div>
                            <VIcon icon="ri-arrow-right-s-line" />
                        </Link>
                        <Link :href="route('budgets.index')" class="quick-action">
                            <VAvatar color="warning" variant="tonal"><VIcon icon="ri-funds-line" /></VAvatar>
                            <div><strong>Budget</strong><small>Rencana & realisasi biaya</small></div>
                            <VIcon icon="ri-arrow-right-s-line" />
                        </Link>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>
    </UserLayout>
</template>

<style scoped>
.hero-card { background: linear-gradient(118deg, #7367f0 0%, #9e95f5 100%); box-shadow: 0 10px 28px rgba(115, 103, 240, .28); }
.dashboard-hero-title { font-size: clamp(1.65rem, 2.25vw, 2.15rem); line-height: 1.24; letter-spacing: -.035em; }
.hero-copy { max-width: 650px; opacity: .88; }
.hero-orb { position: absolute; border-radius: 50%; background: rgba(255, 255, 255, .1); }
.hero-orb-one { width: 180px; height: 180px; right: -40px; top: -70px; }
.hero-orb-two { width: 110px; height: 110px; right: 150px; bottom: -70px; }
.metric-card { transition: transform .2s ease, box-shadow .2s ease; }
.metric-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(47, 43, 61, .1); }
.budget-overview { border: 1px solid rgba(var(--v-theme-warning), .22); background: linear-gradient(110deg, rgba(var(--v-theme-warning), .07), rgba(var(--v-theme-surface), 1) 48%); }
.budget-stat { min-width: 125px; }
.budget-stat span { display: block; color: rgba(var(--v-theme-on-surface), .62); font-size: .78rem; }
.budget-stat strong { display: block; margin-top: 3px; font-size: 1rem; }
.quick-grid { display: grid; gap: 12px; }
.quick-action { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 14px; padding: 14px; border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); border-radius: 14px; color: inherit; text-decoration: none; transition: border-color .2s ease, transform .2s ease; }
.quick-action:hover { border-color: rgb(var(--v-theme-primary)); transform: translateX(3px); }
.quick-action small { display: block; color: rgba(var(--v-theme-on-surface), .62); }
.min-width-0 { min-width: 0; }
.progress-track { width: 100%; height: 8px; overflow: hidden; border-radius: 999px; background: rgba(var(--v-theme-on-surface), .08); }
.progress-fill { display: block; height: 100%; border-radius: inherit; transition: width .35s ease; }
</style>
