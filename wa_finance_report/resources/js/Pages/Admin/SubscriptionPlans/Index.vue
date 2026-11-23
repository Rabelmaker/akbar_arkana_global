<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    plans: { type: Array, required: true },
});

const dialog = ref(false);
const deleteDialog = ref(false);
const editingId = ref(null);
const selectedPlan = ref(null);
const activePlans = computed(() => props.plans.filter((plan) => plan.is_active).length);
const totalSubscribers = computed(() => props.plans.reduce((total, plan) => total + plan.subscriptions_count, 0));
const apiPlans = computed(() => props.plans.filter((plan) => plan.api_access).length);

const defaults = () => ({
    name: '',
    code: '',
    description: '',
    price: 0,
    billing_period: 'monthly',
    duration_days: 30,
    max_financial_accounts: 5,
    max_monthly_transactions: 1000,
    max_users: 1,
    api_access: false,
    whatsapp_integration: false,
    google_sheets_integration: false,
    report_export: true,
    is_active: true,
    sort_order: 10,
});

const form = useForm(defaults());

const openCreate = () => {
    editingId.value = null;
    form.defaults(defaults());
    form.reset();
    form.clearErrors();
    dialog.value = true;
};

const openEdit = (plan) => {
    editingId.value = plan.id;
    form.defaults({
        name: plan.name,
        code: plan.code,
        description: plan.description ?? '',
        price: plan.price,
        billing_period: plan.billing_period,
        duration_days: plan.duration_days,
        max_financial_accounts: plan.max_financial_accounts,
        max_monthly_transactions: plan.max_monthly_transactions,
        max_users: plan.max_users,
        api_access: plan.api_access,
        whatsapp_integration: plan.whatsapp_integration,
        google_sheets_integration: plan.google_sheets_integration,
        report_export: plan.report_export,
        is_active: plan.is_active,
        sort_order: plan.sort_order,
    });
    form.reset();
    form.clearErrors();
    dialog.value = true;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialog.value = false;
            form.reset();
        },
    };

    if (editingId.value) {
        form.patch(route('admin.subscription-plans.update', editingId.value), options);
    } else {
        form.post(route('admin.subscription-plans.store'), options);
    }
};

const confirmDelete = (plan) => {
    selectedPlan.value = plan;
    deleteDialog.value = true;
};

const remove = () => {
    router.delete(route('admin.subscription-plans.destroy', selectedPlan.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleteDialog.value = false;
            selectedPlan.value = null;
        },
    });
};

const money = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value));

const periodLabel = (period) => ({
    monthly: '/bulan',
    quarterly: '/kuartal',
    yearly: '/tahun',
    lifetime: 'sekali bayar',
    custom: 'periode khusus',
}[period] || period);
</script>

<template>
    <Head title="Subscription Plans" />

    <AdminLayout>
        <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
            <div>
                <div class="text-overline text-primary font-weight-bold">MONETIZATION</div>
                <h1 class="dashboard-page-title font-weight-bold mb-1">Paket Langganan</h1>
                <p class="dashboard-page-copy text-medium-emphasis mb-0">Rancang harga, kuota, dan akses fitur untuk setiap segmen pelanggan.</p>
            </div>
            <VBtn color="primary" size="large" prepend-icon="ri-add-line" @click="openCreate">Tambah Paket</VBtn>
        </div>

        <VRow class="mb-2">
            <VCol cols="12" sm="4"><VCard rounded="xl"><VCardText class="d-flex align-center pa-5"><VAvatar color="primary" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-price-tag-3-line" /></VAvatar><div><div class="text-caption text-medium-emphasis">Paket Aktif</div><div class="text-h5 font-weight-bold">{{ activePlans }}</div></div></VCardText></VCard></VCol>
            <VCol cols="12" sm="4"><VCard rounded="xl"><VCardText class="d-flex align-center pa-5"><VAvatar color="success" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-user-star-line" /></VAvatar><div><div class="text-caption text-medium-emphasis">Total Subscriber</div><div class="text-h5 font-weight-bold">{{ totalSubscribers }}</div></div></VCardText></VCard></VCol>
            <VCol cols="12" sm="4"><VCard rounded="xl"><VCardText class="d-flex align-center pa-5"><VAvatar color="info" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-code-s-slash-line" /></VAvatar><div><div class="text-caption text-medium-emphasis">Paket dengan API</div><div class="text-h5 font-weight-bold">{{ apiPlans }}</div></div></VCardText></VCard></VCol>
        </VRow>

        <VRow v-if="plans.length">
            <VCol v-for="plan in plans" :key="plan.id" cols="12" md="6" xl="4">
                <VCard rounded="xl" class="plan-card h-100">
                    <VCardText class="pa-6">
                        <div class="d-flex align-start justify-space-between mb-5">
                            <VAvatar color="primary" variant="tonal" rounded="lg" size="52"><VIcon icon="ri-box-3-line" size="27" /></VAvatar>
                            <div class="d-flex align-center">
                                <VChip :color="plan.is_active ? 'success' : 'secondary'" variant="tonal" size="small">{{ plan.is_active ? 'Aktif' : 'Nonaktif' }}</VChip>
                                <VMenu>
                                    <template #activator="{ props: menuProps }"><VBtn v-bind="menuProps" icon="ri-more-2-fill" variant="text" size="small" class="ms-1" /></template>
                                    <VList density="compact"><VListItem prepend-icon="ri-edit-line" title="Edit paket" @click="openEdit(plan)" /><VListItem prepend-icon="ri-delete-bin-line" title="Hapus paket" base-color="error" @click="confirmDelete(plan)" /></VList>
                                </VMenu>
                            </div>
                        </div>
                        <div class="text-overline text-primary">{{ plan.code }}</div>
                        <h2 class="text-h5 font-weight-bold mb-2">{{ plan.name }}</h2>
                        <p class="text-body-2 text-medium-emphasis plan-description">{{ plan.description || 'Paket fleksibel untuk kebutuhan pengelolaan keuangan.' }}</p>
                        <div class="d-flex align-end gap-2 my-6"><span class="plan-price font-weight-bold">{{ money(plan.price) }}</span><span class="text-body-2 text-medium-emphasis mb-1">{{ periodLabel(plan.billing_period) }}</span></div>
                        <VDivider class="mb-5" />
                        <div class="feature-row"><VIcon icon="ri-wallet-3-line" color="primary" /><span>{{ plan.max_financial_accounts || 'Tak terbatas' }} sumber dana</span></div>
                        <div class="feature-row"><VIcon icon="ri-exchange-dollar-line" color="primary" /><span>{{ plan.max_monthly_transactions || 'Tak terbatas' }} transaksi/bulan</span></div>
                        <div class="feature-row"><VIcon icon="ri-team-line" color="primary" /><span>{{ plan.max_users || 'Tak terbatas' }} pengguna</span></div>
                        <div class="feature-row"><VIcon :icon="plan.api_access ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'" :color="plan.api_access ? 'success' : 'secondary'" /><span>API Access</span></div>
                        <div class="feature-row"><VIcon :icon="plan.whatsapp_integration ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'" :color="plan.whatsapp_integration ? 'success' : 'secondary'" /><span>WhatsApp Readiness</span></div>
                        <VAlert color="primary" variant="tonal" density="compact" class="mt-5"><strong>{{ plan.subscriptions_count }}</strong> subscription menggunakan paket ini</VAlert>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>
        <VCard v-else rounded="xl"><VCardText class="text-center py-16"><VAvatar color="primary" variant="tonal" size="68"><VIcon icon="ri-price-tag-3-line" size="34" /></VAvatar><h2 class="text-h6 mt-4">Belum ada paket langganan</h2><VBtn color="primary" class="mt-4" @click="openCreate">Buat Paket Pertama</VBtn></VCardText></VCard>

        <VDialog v-model="dialog" max-width="900" persistent scrollable>
            <VCard rounded="xl">
                <VCardItem class="pa-6 pb-2">
                    <template #prepend><VAvatar color="primary" variant="tonal"><VIcon icon="ri-price-tag-3-line" /></VAvatar></template>
                    <VCardTitle>{{ editingId ? 'Edit Plan' : 'Tambah Plan' }}</VCardTitle>
                    <VCardSubtitle>Nilai tanpa batas dapat dikosongkan.</VCardSubtitle>
                    <template #append><VBtn icon="ri-close-line" variant="text" @click="dialog = false" /></template>
                </VCardItem>
                <VCardText class="pa-6">
                    <VForm @submit.prevent="submit">
                        <VRow>
                            <VCol cols="12" md="8"><VTextField v-model="form.name" label="Nama plan" :error-messages="form.errors.name" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.code" label="Kode unik" :error-messages="form.errors.code" /></VCol>
                            <VCol cols="12"><VTextarea v-model="form.description" label="Deskripsi" rows="2" :error-messages="form.errors.description" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.price" type="number" min="0" label="Harga (IDR)" :error-messages="form.errors.price" /></VCol>
                            <VCol cols="12" md="4">
                                <VSelect v-model="form.billing_period" label="Periode" :items="['monthly', 'quarterly', 'yearly', 'lifetime', 'custom']" :error-messages="form.errors.billing_period" />
                            </VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.duration_days" type="number" min="1" label="Durasi hari" :disabled="form.billing_period === 'lifetime'" :error-messages="form.errors.duration_days" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.max_financial_accounts" type="number" min="1" label="Maks. sumber dana" :error-messages="form.errors.max_financial_accounts" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.max_monthly_transactions" type="number" min="1" label="Maks. transaksi/bulan" :error-messages="form.errors.max_monthly_transactions" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.max_users" type="number" min="1" label="Maks. user" :error-messages="form.errors.max_users" /></VCol>
                            <VCol cols="12" md="4"><VSwitch v-model="form.api_access" color="primary" label="API access" /></VCol>
                            <VCol cols="12" md="4"><VSwitch v-model="form.report_export" color="primary" label="Report export" /></VCol>
                            <VCol cols="12" md="4"><VSwitch v-model="form.is_active" color="success" label="Plan aktif" /></VCol>
                            <VCol cols="12" md="4"><VSwitch v-model="form.whatsapp_integration" color="primary" label="WhatsApp readiness" /></VCol>
                            <VCol cols="12" md="4"><VSwitch v-model="form.google_sheets_integration" color="primary" label="Google Sheets readiness" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.sort_order" type="number" min="0" label="Urutan" :error-messages="form.errors.sort_order" /></VCol>
                        </VRow>
                    </VForm>
                </VCardText>
                <VCardActions class="px-6 pb-6">
                    <VSpacer />
                    <VBtn variant="text" @click="dialog = false">Batal</VBtn>
                    <VBtn color="primary" :loading="form.processing" @click="submit">Simpan</VBtn>
                </VCardActions>
            </VCard>
        </VDialog>

        <VDialog v-model="deleteDialog" max-width="480">
            <VCard title="Hapus subscription plan?" rounded="xl">
                <VCardText>
                    Plan <strong>{{ selectedPlan?.name }}</strong> akan dihapus. Plan yang sudah digunakan akan ditolak oleh server.
                </VCardText>
                <VCardActions>
                    <VSpacer />
                    <VBtn variant="text" @click="deleteDialog = false">Batal</VBtn>
                    <VBtn color="error" @click="remove">Hapus</VBtn>
                </VCardActions>
            </VCard>
        </VDialog>
    </AdminLayout>
</template>

<style scoped>
.plan-card { position: relative; overflow: hidden; transition: transform .2s ease, box-shadow .2s ease; }
.plan-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(47,43,61,.11); }
.plan-price { font-size: clamp(1.65rem, 2.1vw, 2.05rem); line-height: 1.2; letter-spacing: -.025em; }
.plan-description { min-height: 42px; }
.feature-row { display: flex; align-items: center; gap: 10px; margin-block: 12px; font-size: .9rem; }
</style>
