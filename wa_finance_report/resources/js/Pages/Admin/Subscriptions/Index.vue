<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    subscriptions: { type: Object, required: true },
    accounts: { type: Array, required: true },
    plans: { type: Array, required: true },
});

const dialog = ref(false);
const editingId = ref(null);
const search = ref('');
const statusFilter = ref('all');
const filteredSubscriptions = computed(() => props.subscriptions.data.filter((subscription) => {
    const matchesSearch = !search.value
        || subscription.account?.name?.toLowerCase().includes(search.value.toLowerCase())
        || subscription.plan?.name?.toLowerCase().includes(search.value.toLowerCase());
    const matchesStatus = statusFilter.value === 'all' || subscription.status === statusFilter.value;

    return matchesSearch && matchesStatus;
}));
const activeOnPage = computed(() => props.subscriptions.data.filter((item) => ['active', 'trial', 'grace_period'].includes(item.status)).length);
const paidOnPage = computed(() => props.subscriptions.data.filter((item) => item.payment_status === 'paid').length);
const expiringOnPage = computed(() => props.subscriptions.data.filter((item) => {
    if (!item.end_date) return false;
    const days = (new Date(`${item.end_date}T00:00:00`) - new Date()) / 86400000;
    return days >= 0 && days <= 7;
}).length);

const today = () => new Date().toISOString().slice(0, 10);
const addDays = (date, days) => {
    const result = new Date(`${date}T00:00:00`);
    result.setDate(result.getDate() + Number(days) - 1);
    return result.toISOString().slice(0, 10);
};

const defaults = () => ({
    account_id: null,
    plan_id: null,
    start_date: today(),
    end_date: '',
    status: 'active',
    price: 0,
    payment_status: 'pending',
    auto_renew: false,
    notes: '',
});

const form = useForm(defaults());

const syncPlanDefaults = (planId) => {
    const plan = props.plans.find((item) => item.id === planId);
    if (!plan) return;

    form.price = plan.price;
    form.end_date = plan.billing_period === 'lifetime'
        ? ''
        : addDays(form.start_date, plan.duration_days || 30);
};

const openCreate = () => {
    editingId.value = null;
    form.defaults(defaults());
    form.reset();
    form.clearErrors();
    dialog.value = true;
};

const openEdit = (subscription) => {
    editingId.value = subscription.id;
    form.defaults({
        account_id: subscription.account_id,
        plan_id: subscription.plan_id,
        start_date: subscription.start_date,
        end_date: subscription.end_date ?? '',
        status: subscription.status,
        price: subscription.price,
        payment_status: subscription.payment_status,
        auto_renew: subscription.auto_renew,
        notes: subscription.notes ?? '',
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
        form.patch(route('admin.subscriptions.update', editingId.value), options);
    } else {
        form.post(route('admin.subscriptions.store'), options);
    }
};

const statusColor = (status) => ({
    trial: 'info',
    active: 'success',
    grace_period: 'warning',
    expired: 'error',
    suspended: 'error',
    cancelled: 'secondary',
}[status] || 'secondary');

const money = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value));

const paginationLabel = (label) => {
    if (label.includes('previous')) return '‹';
    if (label.includes('next')) return '›';

    return label;
};
</script>

<template>
    <Head title="Subscriptions" />

    <AdminLayout>
        <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
            <div>
                <div class="text-overline text-primary font-weight-bold">CUSTOMER ENTITLEMENT</div>
                <h1 class="dashboard-page-title font-weight-bold mb-1">Subscriptions</h1>
                <p class="dashboard-page-copy text-medium-emphasis mb-0">Kelola trial, aktivasi, perpanjangan, pembayaran, dan status tenant.</p>
            </div>
            <VBtn color="primary" size="large" prepend-icon="ri-add-line" @click="openCreate">Berikan Subscription</VBtn>
        </div>

        <VRow class="mb-2">
            <VCol cols="12" sm="6" xl="3"><VCard rounded="xl"><VCardText class="d-flex align-center pa-5"><VAvatar color="primary" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-bank-card-line" /></VAvatar><div><div class="text-caption text-medium-emphasis">Total Subscription</div><div class="text-h5 font-weight-bold">{{ subscriptions.total }}</div></div></VCardText></VCard></VCol>
            <VCol cols="12" sm="6" xl="3"><VCard rounded="xl"><VCardText class="d-flex align-center pa-5"><VAvatar color="success" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-checkbox-circle-line" /></VAvatar><div><div class="text-caption text-medium-emphasis">Aktif di Halaman Ini</div><div class="text-h5 font-weight-bold">{{ activeOnPage }}</div></div></VCardText></VCard></VCol>
            <VCol cols="12" sm="6" xl="3"><VCard rounded="xl"><VCardText class="d-flex align-center pa-5"><VAvatar color="info" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-secure-payment-line" /></VAvatar><div><div class="text-caption text-medium-emphasis">Pembayaran Lunas</div><div class="text-h5 font-weight-bold">{{ paidOnPage }}</div></div></VCardText></VCard></VCol>
            <VCol cols="12" sm="6" xl="3"><VCard rounded="xl"><VCardText class="d-flex align-center pa-5"><VAvatar color="warning" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-timer-line" /></VAvatar><div><div class="text-caption text-medium-emphasis">Berakhir ≤ 7 Hari</div><div class="text-h5 font-weight-bold">{{ expiringOnPage }}</div></div></VCardText></VCard></VCol>
        </VRow>

        <VCard rounded="xl" class="overflow-hidden">
            <VCardItem class="pa-5">
                <template #prepend><VAvatar color="primary" variant="tonal"><VIcon icon="ri-list-check-3" /></VAvatar></template>
                <VCardTitle>Daftar Subscription</VCardTitle>
                <VCardSubtitle>Kelola entitlement seluruh tenant platform</VCardSubtitle>
            </VCardItem>
            <VCardText class="pt-0 pb-5">
                <VRow>
                    <VCol cols="12" md="8"><VTextField v-model="search" prepend-inner-icon="ri-search-line" label="Cari tenant atau paket" hide-details clearable /></VCol>
                    <VCol cols="12" md="4"><VSelect v-model="statusFilter" label="Filter status" hide-details :items="[{ title: 'Semua status', value: 'all' }, { title: 'Aktif', value: 'active' }, { title: 'Trial', value: 'trial' }, { title: 'Grace period', value: 'grace_period' }, { title: 'Expired', value: 'expired' }, { title: 'Suspended', value: 'suspended' }, { title: 'Cancelled', value: 'cancelled' }]" /></VCol>
                </VRow>
            </VCardText>
            <VDivider />
            <VTable class="admin-subscription-table">
                <thead>
                    <tr><th>Account</th><th>Plan</th><th>Periode</th><th>Harga</th><th>Pembayaran</th><th>Status</th><th class="text-end">Aksi</th></tr>
                </thead>
                <tbody>
                    <tr v-for="subscription in filteredSubscriptions" :key="subscription.id">
                        <td><div class="d-flex align-center"><VAvatar color="primary" variant="tonal" rounded="lg" size="38" class="me-3"><VIcon icon="ri-building-2-line" /></VAvatar><div><div class="font-weight-bold">{{ subscription.account?.name }}</div><div class="text-caption text-medium-emphasis">Tenant #{{ subscription.account_id }}</div></div></div></td>
                        <td><div class="font-weight-medium">{{ subscription.plan?.name }}</div><div class="text-caption text-primary">{{ subscription.plan?.code }}</div></td>
                        <td>
                            <div>{{ subscription.start_date }}</div>
                            <div class="text-caption text-medium-emphasis">s/d {{ subscription.end_date || 'Lifetime' }}</div>
                        </td>
                        <td>{{ money(subscription.price) }}</td>
                        <td><VChip size="small" :color="subscription.payment_status === 'paid' ? 'success' : 'warning'" variant="tonal">{{ subscription.payment_status }}</VChip></td>
                        <td><VChip size="small" variant="tonal" :color="statusColor(subscription.status)">{{ subscription.status }}</VChip></td>
                        <td class="text-end"><VBtn icon="ri-edit-line" variant="text" size="small" aria-label="Edit subscription" @click="openEdit(subscription)" /></td>
                    </tr>
                    <tr v-if="!filteredSubscriptions.length"><td colspan="7" class="text-center text-medium-emphasis py-12"><VIcon icon="ri-search-eye-line" size="32" class="mb-2" /><div>Tidak ada subscription yang sesuai.</div></td></tr>
                </tbody>
            </VTable>

            <VDivider />
            <div class="d-flex flex-wrap align-center justify-space-between gap-3 pa-4">
                <div class="text-caption text-medium-emphasis">
                    Menampilkan {{ subscriptions.from || 0 }}–{{ subscriptions.to || 0 }} dari {{ subscriptions.total }}
                </div>
                <div class="d-flex gap-1">
                    <Link
                        v-for="link in subscriptions.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        preserve-scroll
                        class="text-decoration-none"
                    >
                        <VBtn
                            :disabled="!link.url"
                            :color="link.active ? 'primary' : undefined"
                            :variant="link.active ? 'flat' : 'text'"
                            size="small"
                        >
                            {{ paginationLabel(link.label) }}
                        </VBtn>
                    </Link>
                </div>
            </div>
        </VCard>

        <VDialog v-model="dialog" max-width="760" persistent scrollable>
            <VCard rounded="xl">
                <VCardItem class="pa-6 pb-2">
                    <template #prepend><VAvatar color="primary" variant="tonal"><VIcon icon="ri-bank-card-line" /></VAvatar></template>
                    <VCardTitle>{{ editingId ? 'Edit Subscription' : 'Berikan Subscription' }}</VCardTitle>
                    <VCardSubtitle>Subscription aktif baru akan menutup entitlement aktif sebelumnya.</VCardSubtitle>
                    <template #append><VBtn icon="ri-close-line" variant="text" @click="dialog = false" /></template>
                </VCardItem>
                <VCardText class="pa-6">
                    <VForm @submit.prevent="submit">
                        <VRow>
                            <VCol cols="12" md="6">
                                <VSelect v-model="form.account_id" label="Account" :items="accounts" item-title="name" item-value="id" :disabled="Boolean(editingId)" :error-messages="form.errors.account_id" />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VSelect v-model="form.plan_id" label="Plan" :items="plans" item-title="name" item-value="id" :error-messages="form.errors.plan_id" @update:model-value="syncPlanDefaults" />
                            </VCol>
                            <VCol cols="12" md="6"><VTextField v-model="form.start_date" type="date" label="Tanggal mulai" :error-messages="form.errors.start_date" /></VCol>
                            <VCol cols="12" md="6"><VTextField v-model="form.end_date" type="date" label="Tanggal berakhir" :error-messages="form.errors.end_date" /></VCol>
                            <VCol cols="12" md="4"><VSelect v-model="form.status" label="Status" :items="['trial', 'active', 'grace_period', 'expired', 'suspended', 'cancelled']" :error-messages="form.errors.status" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.price" type="number" min="0" label="Harga" :error-messages="form.errors.price" /></VCol>
                            <VCol cols="12" md="4"><VSelect v-model="form.payment_status" label="Pembayaran" :items="['pending', 'paid', 'failed', 'refunded', 'waived']" :error-messages="form.errors.payment_status" /></VCol>
                            <VCol cols="12"><VTextarea v-model="form.notes" label="Catatan" rows="2" :error-messages="form.errors.notes" /></VCol>
                            <VCol cols="12"><VSwitch v-model="form.auto_renew" color="primary" label="Perpanjang otomatis" /></VCol>
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
    </AdminLayout>
</template>

<style scoped>
.admin-subscription-table :deep(tbody tr) { transition: background .18s ease; }
.admin-subscription-table :deep(tbody tr:hover) { background: rgba(var(--v-theme-primary), .035); }
@media (max-width: 959px) {
    .admin-subscription-table { min-width: 940px; }
}
</style>
