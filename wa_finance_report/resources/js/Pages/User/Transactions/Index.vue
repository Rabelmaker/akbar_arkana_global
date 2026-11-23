<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    transactions: Object,
    financialAccounts: Array,
    categories: Array,
    filters: Object,
    summary: Object,
});

const page = usePage();
const dialog = ref(false);
const editing = ref(null);
const canWrite = computed(() => page.props.auth.subscription?.can_write);
const filterForm = useForm({
    search: props.filters.search || '',
    type: props.filters.type || null,
    status: props.filters.status || null,
    financial_account_id: props.filters.financial_account_id || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const localDateTime = () => {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
};

const defaults = () => ({
    type: 'expense',
    transaction_date: localDateTime(),
    financial_account_id: props.financialAccounts[0]?.id || null,
    destination_financial_account_id: null,
    category_id: null,
    amount: null,
    fee_amount: 0,
    description: '',
    merchant: '',
    reference_number: '',
    payment_method: '',
    notes: '',
    status: 'posted',
    attachments: [],
    idempotency_key: crypto.randomUUID(),
});
const form = useForm(defaults());
const isTransfer = computed(() => form.type === 'transfer');
const availableCategories = computed(() => props.categories.filter((category) =>
    category.type === form.type || category.type === 'both'));
const destinationAccounts = computed(() => props.financialAccounts.filter((account) =>
    account.id !== form.financial_account_id));

const rupiah = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(Number(value || 0));
const dateTime = (value) => new Date(value).toLocaleString('id-ID', {
    dateStyle: 'medium', timeStyle: 'short',
});
const typeColor = (type) => ({ income: 'success', expense: 'error', transfer: 'info', adjustment: 'warning' }[type] || 'secondary');
const typeIcon = (type) => ({ income: 'ri-arrow-down-circle-line', expense: 'ri-arrow-up-circle-line', transfer: 'ri-arrow-left-right-line', adjustment: 'ri-equalizer-line' }[type]);
const statusColor = (status) => ({ draft: 'warning', posted: 'success', cancelled: 'secondary', reversed: 'error', pending: 'info' }[status] || 'secondary');

const openCreate = (type = 'expense') => {
    editing.value = null;
    form.defaults({ ...defaults(), type });
    form.reset();
    form.clearErrors();
    dialog.value = true;
};
const openEdit = (item) => {
    editing.value = item;
    form.defaults({
        type: item.type,
        transaction_date: item.transaction_date.slice(0, 16),
        financial_account_id: item.financial_account_id,
        destination_financial_account_id: item.destination_financial_account_id,
        category_id: item.category_id,
        amount: Number(item.amount),
        fee_amount: Number(item.fee_amount),
        description: item.description,
        merchant: item.merchant || '',
        reference_number: item.reference_number || '',
        payment_method: item.payment_method || '',
        notes: item.notes || '',
        status: 'draft',
        attachments: [],
        idempotency_key: null,
    });
    form.reset();
    form.clearErrors();
    dialog.value = true;
};
const submit = () => {
    const options = { preserveScroll: true, forceFormData: true, onSuccess: () => { dialog.value = false; } };
    if (editing.value) form.post(route('transactions.update', editing.value.id), options);
    else form.post(route('transactions.store'), options);
};
const applyFilters = () => filterForm.get(route('transactions.index'), {
    preserveState: true, preserveScroll: true, replace: true,
});
const clearFilters = () => {
    filterForm.reset();
    router.get(route('transactions.index'), {}, { preserveState: true, replace: true });
};
const postDraft = (item) => router.post(route('transactions.post', item.id), {}, { preserveScroll: true });
const removeDraft = (item) => {
    if (window.confirm(`Hapus draft “${item.description}”?`)) router.delete(route('transactions.destroy', item.id), { preserveScroll: true });
};
const lifecycle = (item, action) => {
    const reason = window.prompt(action === 'cancel' ? 'Alasan pembatalan:' : 'Alasan reversal:');
    if (reason === null) return;
    router.post(route(`transactions.${action}`, item.id), { reason }, { preserveScroll: true });
};
const paginationLabel = (label) => label.includes('previous') ? '‹' : label.includes('next') ? '›' : label;
</script>

<template>
    <Head title="Transaksi" />
    <UserLayout>
        <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
            <div>
                <div class="text-overline text-primary font-weight-bold">CASHFLOW CONTROL</div>
                <h1 class="dashboard-page-title font-weight-bold mb-1">Transaksi</h1>
                <p class="dashboard-page-copy text-medium-emphasis mb-0">Catat pemasukan, pengeluaran, dan transfer dengan jejak saldo yang aman.</p>
            </div>
            <VBtn color="primary" size="large" prepend-icon="ri-add-line" :disabled="!canWrite || !financialAccounts.length" @click="openCreate()">Tambah Transaksi</VBtn>
        </div>

        <VAlert v-if="!financialAccounts.length" color="warning" variant="tonal" rounded="xl" class="mb-6" icon="ri-wallet-3-line">
            Tambahkan rekening keuangan sebelum mencatat transaksi.
        </VAlert>

        <VRow class="mb-2">
            <VCol cols="12" sm="6" xl="3">
                <VCard rounded="xl"><VCardText class="metric-card"><VAvatar color="success" variant="tonal" rounded="lg"><VIcon icon="ri-arrow-down-circle-line" /></VAvatar><div><span>Pemasukan bulan ini</span><strong>{{ rupiah(summary.income) }}</strong></div></VCardText></VCard>
            </VCol>
            <VCol cols="12" sm="6" xl="3">
                <VCard rounded="xl"><VCardText class="metric-card"><VAvatar color="error" variant="tonal" rounded="lg"><VIcon icon="ri-arrow-up-circle-line" /></VAvatar><div><span>Pengeluaran bulan ini</span><strong>{{ rupiah(summary.expense) }}</strong></div></VCardText></VCard>
            </VCol>
            <VCol cols="12" sm="6" xl="3">
                <VCard rounded="xl"><VCardText class="metric-card"><VAvatar color="info" variant="tonal" rounded="lg"><VIcon icon="ri-arrow-left-right-line" /></VAvatar><div><span>Transfer bulan ini</span><strong>{{ rupiah(summary.transfer) }}</strong></div></VCardText></VCard>
            </VCol>
            <VCol cols="12" sm="6" xl="3">
                <VCard rounded="xl"><VCardText class="metric-card"><VAvatar color="warning" variant="tonal" rounded="lg"><VIcon icon="ri-draft-line" /></VAvatar><div><span>Menunggu diposting</span><strong>{{ summary.drafts }} draft</strong></div></VCardText></VCard>
            </VCol>
        </VRow>

        <VCard rounded="xl" class="mb-5">
            <VCardText class="pa-5">
                <VRow align="center">
                    <VCol cols="12" lg="4"><VTextField v-model="filterForm.search" label="Cari transaksi" prepend-inner-icon="ri-search-line" hide-details clearable @keyup.enter="applyFilters" /></VCol>
                    <VCol cols="6" md="3" lg="2"><VSelect v-model="filterForm.type" label="Jenis" clearable hide-details :items="[{ title: 'Pemasukan', value: 'income' }, { title: 'Pengeluaran', value: 'expense' }, { title: 'Transfer', value: 'transfer' }]" /></VCol>
                    <VCol cols="6" md="3" lg="2"><VSelect v-model="filterForm.status" label="Status" clearable hide-details :items="[{ title: 'Draft', value: 'draft' }, { title: 'Tercatat', value: 'posted' }, { title: 'Dibatalkan', value: 'cancelled' }, { title: 'Dibalik', value: 'reversed' }]" /></VCol>
                    <VCol cols="12" md="6" lg="2"><VSelect v-model="filterForm.financial_account_id" label="Rekening" clearable hide-details :items="financialAccounts" item-title="name" item-value="id" /></VCol>
                    <VCol cols="12" lg="2" class="d-flex gap-2"><VBtn color="primary" variant="tonal" icon="ri-filter-3-line" @click="applyFilters" /><VBtn variant="text" icon="ri-refresh-line" @click="clearFilters" /></VCol>
                </VRow>
            </VCardText>
        </VCard>

        <VCard rounded="xl" class="overflow-hidden">
            <VTable class="transaction-table">
                <thead><tr><th>Transaksi</th><th>Rekening</th><th>Tanggal</th><th>Status</th><th class="text-end">Nominal</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    <tr v-for="item in transactions.data" :key="item.id">
                        <td>
                            <div class="d-flex align-center py-2">
                                <VAvatar :color="typeColor(item.type)" variant="tonal" rounded="lg" class="me-3"><VIcon :icon="typeIcon(item.type)" /></VAvatar>
                                <div><div class="font-weight-bold">{{ item.description }}</div><div class="text-caption text-medium-emphasis">{{ item.transaction_number }} · {{ item.category?.name || item.type_label }}</div></div>
                            </div>
                        </td>
                        <td><div>{{ item.financial_account?.name }}</div><div v-if="item.destination_financial_account" class="text-caption text-info">ke {{ item.destination_financial_account.name }}</div></td>
                        <td class="text-medium-emphasis">{{ dateTime(item.transaction_date) }}</td>
                        <td><VChip :color="statusColor(item.status)" variant="tonal" size="small">{{ item.status_label }}</VChip></td>
                        <td class="text-end font-weight-bold" :class="item.type === 'income' ? 'text-success' : item.type === 'expense' ? 'text-error' : 'text-info'">{{ item.type === 'income' ? '+' : item.type === 'expense' ? '-' : '' }}{{ rupiah(item.total_amount) }}</td>
                        <td class="text-end">
                            <VMenu>
                                <template #activator="{ props: menuProps }"><VBtn v-bind="menuProps" icon="ri-more-2-fill" variant="text" size="small" /></template>
                                <VList density="compact">
                                    <template v-if="item.status === 'draft'">
                                        <VListItem prepend-icon="ri-edit-line" title="Edit draft" @click="openEdit(item)" />
                                        <VListItem prepend-icon="ri-send-plane-line" title="Post transaksi" @click="postDraft(item)" />
                                        <VListItem prepend-icon="ri-delete-bin-line" title="Hapus draft" base-color="error" @click="removeDraft(item)" />
                                    </template>
                                    <template v-else-if="item.status === 'posted'">
                                        <VListItem prepend-icon="ri-close-circle-line" title="Batalkan" @click="lifecycle(item, 'cancel')" />
                                        <VListItem prepend-icon="ri-arrow-go-back-line" title="Buat reversal" base-color="error" @click="lifecycle(item, 'reverse')" />
                                    </template>
                                    <VListItem v-else prepend-icon="ri-information-line" title="Tidak ada aksi lanjutan" disabled />
                                </VList>
                            </VMenu>
                        </td>
                    </tr>
                    <tr v-if="!transactions.data.length"><td colspan="6" class="text-center py-14 text-medium-emphasis"><VIcon icon="ri-file-search-line" size="36" class="mb-2" /><div>Belum ada transaksi yang sesuai.</div></td></tr>
                </tbody>
            </VTable>
            <VDivider />
            <div class="d-flex flex-wrap align-center justify-space-between gap-3 pa-4">
                <span class="text-caption text-medium-emphasis">Menampilkan {{ transactions.from || 0 }}–{{ transactions.to || 0 }} dari {{ transactions.total }}</span>
                <div class="d-flex gap-1">
                    <Link v-for="link in transactions.links" :key="link.label" :href="link.url || '#'" preserve-scroll class="text-decoration-none">
                        <VBtn :disabled="!link.url" :color="link.active ? 'primary' : undefined" :variant="link.active ? 'flat' : 'text'" size="small">{{ paginationLabel(link.label) }}</VBtn>
                    </Link>
                </div>
            </div>
        </VCard>

        <VDialog v-model="dialog" max-width="860" persistent scrollable>
            <VCard rounded="xl">
                <VCardItem class="pa-6 pb-2">
                    <template #prepend><VAvatar :color="typeColor(form.type)" variant="tonal"><VIcon :icon="typeIcon(form.type)" /></VAvatar></template>
                    <VCardTitle>{{ editing ? 'Edit Draft' : 'Transaksi Baru' }}</VCardTitle>
                    <VCardSubtitle>Saldo hanya berubah ketika transaksi diposting.</VCardSubtitle>
                    <template #append><VBtn icon="ri-close-line" variant="text" @click="dialog = false" /></template>
                </VCardItem>
                <VForm @submit.prevent="submit">
                    <VCardText class="pa-6">
                        <div class="type-picker mb-6">
                            <button v-for="option in [{ value: 'income', label: 'Pemasukan', icon: 'ri-arrow-down-circle-line' }, { value: 'expense', label: 'Pengeluaran', icon: 'ri-arrow-up-circle-line' }, { value: 'transfer', label: 'Transfer', icon: 'ri-arrow-left-right-line' }]" :key="option.value" type="button" :class="{ active: form.type === option.value }" @click="form.type = option.value; form.category_id = null; form.destination_financial_account_id = null">
                                <VIcon :icon="option.icon" /><span>{{ option.label }}</span>
                            </button>
                        </div>
                        <VRow>
                            <VCol cols="12" md="6"><VTextField v-model="form.transaction_date" type="datetime-local" label="Tanggal transaksi" :error-messages="form.errors.transaction_date" /></VCol>
                            <VCol cols="12" md="6"><VTextField v-model.number="form.amount" type="number" min="1" label="Nominal" prefix="Rp" :error-messages="form.errors.amount" /></VCol>
                            <VCol cols="12" :md="isTransfer ? 6 : 12"><VSelect v-model="form.financial_account_id" label="Rekening sumber" :items="financialAccounts" item-title="name" item-value="id" :error-messages="form.errors.financial_account_id" /></VCol>
                            <VCol v-if="isTransfer" cols="12" md="6"><VSelect v-model="form.destination_financial_account_id" label="Rekening tujuan" :items="destinationAccounts" item-title="name" item-value="id" :error-messages="form.errors.destination_financial_account_id" /></VCol>
                            <VCol v-if="!isTransfer" cols="12" md="6"><VSelect v-model="form.category_id" clearable label="Kategori" :items="availableCategories" item-title="name" item-value="id" :error-messages="form.errors.category_id" /></VCol>
                            <VCol cols="12" md="6"><VTextField v-model.number="form.fee_amount" type="number" min="0" label="Biaya admin" prefix="Rp" :error-messages="form.errors.fee_amount" /></VCol>
                            <VCol cols="12"><VTextField v-model="form.description" label="Deskripsi" placeholder="Contoh: Pembayaran pelanggan" :error-messages="form.errors.description" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.merchant" label="Merchant / pihak terkait" :error-messages="form.errors.merchant" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.reference_number" label="Nomor referensi" :error-messages="form.errors.reference_number" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.payment_method" label="Metode pembayaran" :error-messages="form.errors.payment_method" /></VCol>
                            <VCol cols="12"><VTextarea v-model="form.notes" label="Catatan" rows="2" :error-messages="form.errors.notes" /></VCol>
                            <VCol cols="12"><VFileInput v-model="form.attachments" multiple chips label="Lampiran (maks. 5 file, 5 MB/file)" accept=".jpg,.jpeg,.png,.webp,.pdf" prepend-icon="ri-attachment-2" :error-messages="form.errors.attachments" /></VCol>
                            <VCol v-if="!editing" cols="12"><VRadioGroup v-model="form.status" inline hide-details><VRadio label="Simpan sebagai draft" value="draft" /><VRadio label="Simpan dan langsung post" value="posted" /></VRadioGroup></VCol>
                        </VRow>
                    </VCardText>
                    <VCardActions class="px-6 pb-6"><VSpacer /><VBtn variant="text" @click="dialog = false">Batal</VBtn><VBtn color="primary" type="submit" :loading="form.processing">{{ editing ? 'Simpan Perubahan' : form.status === 'draft' ? 'Simpan Draft' : 'Simpan & Post' }}</VBtn></VCardActions>
                </VForm>
            </VCard>
        </VDialog>
    </UserLayout>
</template>

<style scoped>
.metric-card { display: flex; align-items: center; gap: 14px; padding: 20px; }
.metric-card span { display: block; color: rgba(var(--v-theme-on-surface), .62); font-size: .78rem; }
.metric-card strong { display: block; margin-top: 2px; font-size: 1.05rem; }
.transaction-table :deep(tbody tr) { transition: background .18s ease; }
.transaction-table :deep(tbody tr:hover) { background: rgba(var(--v-theme-primary), .035); }
.type-picker { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.type-picker button { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 13px; border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); border-radius: 12px; color: rgba(var(--v-theme-on-surface), .72); background: transparent; font-weight: 600; transition: .18s ease; }
.type-picker button.active { border-color: rgb(var(--v-theme-primary)); color: rgb(var(--v-theme-primary)); background: rgba(var(--v-theme-primary), .09); }
@media (max-width: 959px) { .transaction-table { min-width: 900px; } }
@media (max-width: 600px) { .type-picker { grid-template-columns: 1fr; } }
</style>
