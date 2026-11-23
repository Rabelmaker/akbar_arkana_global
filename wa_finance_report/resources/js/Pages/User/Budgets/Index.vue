<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    budgets: Object,
    filters: Object,
    categories: Array,
    financialAccounts: Array,
    periodTypes: Array,
    summary: Object,
});

const page = usePage();
const dialog = ref(false);
const editing = ref(null);
const canWrite = computed(() => page.props.auth.subscription?.can_write);
const filterForm = useForm({
    search: props.filters.search || '',
    status: props.filters.status || null,
    period_type: props.filters.period_type || null,
    date: props.filters.date || '',
});

const formatDate = (date) => {
    const value = new Date(date);
    value.setMinutes(value.getMinutes() - value.getTimezoneOffset());
    return value.toISOString().slice(0, 10);
};
const periodDates = (type) => {
    const now = new Date();
    let start = new Date(now);
    let end = new Date(now);
    if (type === 'weekly') {
        const day = (now.getDay() + 6) % 7;
        start.setDate(now.getDate() - day);
        end = new Date(start);
        end.setDate(start.getDate() + 6);
    } else if (type === 'monthly') {
        start = new Date(now.getFullYear(), now.getMonth(), 1);
        end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    } else if (type === 'quarterly') {
        const quarterStart = Math.floor(now.getMonth() / 3) * 3;
        start = new Date(now.getFullYear(), quarterStart, 1);
        end = new Date(now.getFullYear(), quarterStart + 3, 0);
    } else if (type === 'yearly') {
        start = new Date(now.getFullYear(), 0, 1);
        end = new Date(now.getFullYear(), 11, 31);
    }
    return { start_date: formatDate(start), end_date: formatDate(end) };
};
const defaults = () => ({
    name: '',
    category_id: null,
    financial_account_id: null,
    period_type: 'monthly',
    ...periodDates('monthly'),
    amount: null,
    alert_percentage: 80,
    rollover_enabled: false,
    status: 'active',
    notes: '',
});
const form = useForm(defaults());

const rupiah = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(Number(value || 0));
const compactRupiah = (value) => new Intl.NumberFormat('id-ID', {
    notation: 'compact', style: 'currency', currency: 'IDR', maximumFractionDigits: 1,
}).format(Number(value || 0));
const dateLabel = (value) => new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
});
const healthMeta = (health) => ({
    safe: { color: 'success', label: 'Aman', icon: 'ri-checkbox-circle-line' },
    warning: { color: 'warning', label: 'Hampir Habis', icon: 'ri-alarm-warning-line' },
    exceeded: { color: 'error', label: 'Terlampaui', icon: 'ri-error-warning-line' },
}[health]);
const scopeLabel = (budget) => {
    if (budget.category && budget.financial_account) return `${budget.category.name} · ${budget.financial_account.name}`;
    if (budget.category) return budget.category.name;
    if (budget.financial_account) return budget.financial_account.name;
    return 'Seluruh pengeluaran';
};

const syncPeriod = (type) => {
    if (type !== 'custom') Object.assign(form, periodDates(type));
};
const openCreate = () => {
    editing.value = null;
    form.defaults(defaults());
    form.reset();
    form.clearErrors();
    dialog.value = true;
};
const openEdit = (budget) => {
    editing.value = budget;
    form.defaults({
        name: budget.name,
        category_id: budget.category_id,
        financial_account_id: budget.financial_account_id,
        period_type: budget.period_type,
        start_date: budget.start_date,
        end_date: budget.end_date,
        amount: Number(budget.amount),
        alert_percentage: budget.alert_percentage,
        rollover_enabled: budget.rollover_enabled,
        status: budget.status,
        notes: budget.notes || '',
    });
    form.reset();
    form.clearErrors();
    dialog.value = true;
};
const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => { dialog.value = false; } };
    if (editing.value) form.patch(route('budgets.update', editing.value.id), options);
    else form.post(route('budgets.store'), options);
};
const remove = (budget) => {
    if (window.confirm(`Hapus budget “${budget.name}”? Riwayat transaksi tidak akan berubah.`)) {
        router.delete(route('budgets.destroy', budget.id), { preserveScroll: true });
    }
};
const applyFilters = () => filterForm.get(route('budgets.index'), {
    preserveState: true, preserveScroll: true, replace: true,
});
const clearFilters = () => {
    filterForm.reset();
    router.get(route('budgets.index'), {}, { preserveState: true, replace: true });
};
const paginationLabel = (label) => label.includes('previous') ? '‹' : label.includes('next') ? '›' : label;
</script>

<template>
    <Head title="Budget" />
    <UserLayout>
        <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
            <div>
                <div class="text-overline text-primary font-weight-bold">SPENDING PLAN</div>
                <h1 class="dashboard-page-title font-weight-bold mb-1">Budget</h1>
                <p class="dashboard-page-copy text-medium-emphasis mb-0">Rencanakan pengeluaran dan pantau realisasinya langsung dari transaksi posted.</p>
            </div>
            <VBtn color="primary" size="large" prepend-icon="ri-add-line" :disabled="!canWrite" @click="openCreate">Buat Budget</VBtn>
        </div>

        <VAlert v-if="summary.exceeded" color="error" variant="tonal" rounded="xl" class="mb-5" icon="ri-error-warning-line">
            <strong>{{ summary.exceeded }} budget telah terlampaui.</strong> Tinjau pengeluaran atau sesuaikan alokasi periode aktif.
        </VAlert>
        <VAlert v-else-if="summary.warning" color="warning" variant="tonal" rounded="xl" class="mb-5" icon="ri-alarm-warning-line">
            {{ summary.warning }} budget sudah melewati ambang peringatan.
        </VAlert>

        <VRow class="mb-2">
            <VCol cols="12" sm="6" xl="3"><VCard rounded="xl"><VCardText class="summary-card"><VAvatar color="primary" variant="tonal" rounded="lg"><VIcon icon="ri-funds-line" /></VAvatar><div><span>Total Alokasi</span><strong>{{ compactRupiah(summary.allocated) }}</strong></div></VCardText></VCard></VCol>
            <VCol cols="12" sm="6" xl="3"><VCard rounded="xl"><VCardText class="summary-card"><VAvatar color="warning" variant="tonal" rounded="lg"><VIcon icon="ri-shopping-bag-3-line" /></VAvatar><div><span>Sudah Terpakai</span><strong>{{ compactRupiah(summary.used) }}</strong></div></VCardText></VCard></VCol>
            <VCol cols="12" sm="6" xl="3"><VCard rounded="xl"><VCardText class="summary-card"><VAvatar :color="summary.remaining < 0 ? 'error' : 'success'" variant="tonal" rounded="lg"><VIcon icon="ri-safe-2-line" /></VAvatar><div><span>Sisa Budget</span><strong>{{ compactRupiah(summary.remaining) }}</strong></div></VCardText></VCard></VCol>
            <VCol cols="12" sm="6" xl="3"><VCard rounded="xl"><VCardText class="summary-card"><VAvatar color="info" variant="tonal" rounded="lg"><VIcon icon="ri-pie-chart-2-line" /></VAvatar><div><span>Budget Aktif</span><strong>{{ summary.active }}</strong></div></VCardText></VCard></VCol>
        </VRow>

        <VCard rounded="xl" class="mb-5">
            <VCardText class="pa-5">
                <VRow align="center">
                    <VCol cols="12" lg="4"><VTextField v-model="filterForm.search" label="Cari budget" prepend-inner-icon="ri-search-line" hide-details clearable @keyup.enter="applyFilters" /></VCol>
                    <VCol cols="6" md="3" lg="2"><VSelect v-model="filterForm.status" label="Status" clearable hide-details :items="[{ title: 'Aktif', value: 'active' }, { title: 'Nonaktif', value: 'inactive' }, { title: 'Selesai', value: 'completed' }]" /></VCol>
                    <VCol cols="6" md="3" lg="2"><VSelect v-model="filterForm.period_type" label="Periode" clearable hide-details :items="periodTypes" /></VCol>
                    <VCol cols="12" md="6" lg="2"><VTextField v-model="filterForm.date" type="date" label="Aktif pada tanggal" hide-details clearable /></VCol>
                    <VCol cols="12" lg="2" class="d-flex gap-2"><VBtn color="primary" variant="tonal" icon="ri-filter-3-line" @click="applyFilters" /><VBtn variant="text" icon="ri-refresh-line" @click="clearFilters" /></VCol>
                </VRow>
            </VCardText>
        </VCard>

        <VRow v-if="budgets.data.length">
            <VCol v-for="budget in budgets.data" :key="budget.id" cols="12" md="6" xl="4">
                <VCard rounded="xl" class="budget-card h-100" :class="`health-${budget.health_status}`">
                    <VCardText class="pa-5">
                        <div class="d-flex align-start justify-space-between mb-5">
                            <VAvatar :color="healthMeta(budget.health_status).color" variant="tonal" rounded="lg" size="50"><VIcon :icon="healthMeta(budget.health_status).icon" /></VAvatar>
                            <div class="d-flex align-center">
                                <VChip :color="healthMeta(budget.health_status).color" variant="tonal" size="small">{{ healthMeta(budget.health_status).label }}</VChip>
                                <VMenu>
                                    <template #activator="{ props: menuProps }"><VBtn v-bind="menuProps" icon="ri-more-2-fill" variant="text" size="small" class="ms-1" /></template>
                                    <VList density="compact"><VListItem prepend-icon="ri-edit-line" title="Edit budget" :disabled="!canWrite" @click="openEdit(budget)" /><VListItem prepend-icon="ri-delete-bin-line" title="Hapus budget" base-color="error" :disabled="!canWrite" @click="remove(budget)" /></VList>
                                </VMenu>
                            </div>
                        </div>
                        <div class="text-overline text-primary">{{ budget.period_label }}</div>
                        <h2 class="text-h6 font-weight-bold mb-1">{{ budget.name }}</h2>
                        <div class="text-body-2 text-medium-emphasis mb-5">{{ scopeLabel(budget) }}</div>

                        <div class="d-flex align-end justify-space-between gap-3 mb-3">
                            <div><div class="text-caption text-medium-emphasis">Terpakai</div><div class="text-h6 font-weight-bold">{{ rupiah(budget.used_amount) }}</div></div>
                            <div class="text-end"><div class="text-caption text-medium-emphasis">dari {{ rupiah(budget.amount) }}</div><strong :class="`text-${healthMeta(budget.health_status).color}`">{{ budget.usage_percentage }}%</strong></div>
                        </div>
                        <div class="budget-progress mb-4"><span :class="`bg-${healthMeta(budget.health_status).color}`" :style="{ width: `${Math.min(budget.usage_percentage, 100)}%` }" /><i :style="{ left: `${budget.alert_percentage}%` }" /></div>

                        <div class="d-flex justify-space-between text-body-2 mb-2"><span class="text-medium-emphasis">Sisa budget</span><strong :class="budget.remaining_amount < 0 ? 'text-error' : 'text-success'">{{ rupiah(budget.remaining_amount) }}</strong></div>
                        <div class="d-flex justify-space-between text-body-2"><span class="text-medium-emphasis">Periode</span><span>{{ dateLabel(budget.start_date) }} – {{ dateLabel(budget.end_date) }}</span></div>
                        <div class="d-flex flex-wrap gap-2 mt-4"><VChip v-if="budget.rollover_enabled" size="x-small" color="info" variant="tonal" prepend-icon="ri-loop-left-line">Rollover</VChip><VChip v-if="budget.status !== 'active'" size="x-small" variant="tonal">{{ budget.status_label }}</VChip></div>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>
        <VCard v-else rounded="xl"><VCardText class="text-center py-16"><VAvatar color="primary" variant="tonal" size="72"><VIcon icon="ri-funds-line" size="36" /></VAvatar><h2 class="text-h6 font-weight-bold mt-5">Belum ada budget</h2><p class="text-medium-emphasis mb-5">Buat batas pengeluaran pertama agar penggunaan dana lebih terarah.</p><VBtn color="primary" :disabled="!canWrite" @click="openCreate">Buat Budget Pertama</VBtn></VCardText></VCard>

        <div v-if="budgets.data.length" class="d-flex flex-wrap align-center justify-space-between gap-3 mt-4">
            <span class="text-caption text-medium-emphasis">Menampilkan {{ budgets.from || 0 }}–{{ budgets.to || 0 }} dari {{ budgets.total }}</span>
            <div class="d-flex gap-1"><Link v-for="link in budgets.links" :key="link.label" :href="link.url || '#'" preserve-scroll class="text-decoration-none"><VBtn :disabled="!link.url" :color="link.active ? 'primary' : undefined" :variant="link.active ? 'flat' : 'text'" size="small">{{ paginationLabel(link.label) }}</VBtn></Link></div>
        </div>

        <VDialog v-model="dialog" max-width="820" persistent scrollable>
            <VCard rounded="xl">
                <VCardItem class="pa-6 pb-2">
                    <template #prepend><VAvatar color="primary" variant="tonal"><VIcon icon="ri-pie-chart-2-line" /></VAvatar></template>
                    <VCardTitle>{{ editing ? 'Edit Budget' : 'Budget Baru' }}</VCardTitle>
                    <VCardSubtitle>Realisasi hanya menghitung expense berstatus posted.</VCardSubtitle>
                    <template #append><VBtn icon="ri-close-line" variant="text" @click="dialog = false" /></template>
                </VCardItem>
                <VForm @submit.prevent="submit">
                    <VCardText class="pa-6">
                        <VRow>
                            <VCol cols="12" md="8"><VTextField v-model="form.name" label="Nama budget" placeholder="Contoh: Operasional Bulanan" :error-messages="form.errors.name" /></VCol>
                            <VCol cols="12" md="4"><VSelect v-model="form.status" label="Status" :items="[{ title: 'Aktif', value: 'active' }, { title: 'Nonaktif', value: 'inactive' }, { title: 'Selesai', value: 'completed' }]" :error-messages="form.errors.status" /></VCol>
                            <VCol cols="12" md="6"><VSelect v-model="form.category_id" clearable label="Kategori pengeluaran" :items="categories" item-title="name" item-value="id" hint="Kosongkan untuk seluruh kategori" persistent-hint :error-messages="form.errors.category_id" /></VCol>
                            <VCol cols="12" md="6"><VSelect v-model="form.financial_account_id" clearable label="Rekening" :items="financialAccounts" item-title="name" item-value="id" hint="Kosongkan untuk seluruh rekening" persistent-hint :error-messages="form.errors.financial_account_id" /></VCol>
                            <VCol cols="12" md="4"><VSelect v-model="form.period_type" label="Jenis periode" :items="periodTypes" :error-messages="form.errors.period_type" @update:model-value="syncPeriod" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.start_date" type="date" label="Tanggal mulai" :error-messages="form.errors.start_date" /></VCol>
                            <VCol cols="12" md="4"><VTextField v-model="form.end_date" type="date" label="Tanggal selesai" :error-messages="form.errors.end_date" /></VCol>
                            <VCol cols="12" md="7"><VTextField v-model.number="form.amount" type="number" min="1" label="Jumlah budget" prefix="Rp" :error-messages="form.errors.amount" /></VCol>
                            <VCol cols="12" md="5"><VTextField v-model.number="form.alert_percentage" type="number" min="1" max="100" label="Peringatan pada" suffix="%" :error-messages="form.errors.alert_percentage" /></VCol>
                            <VCol cols="12"><VTextarea v-model="form.notes" label="Catatan" rows="2" :error-messages="form.errors.notes" /></VCol>
                            <VCol cols="12"><VSwitch v-model="form.rollover_enabled" color="primary" label="Aktifkan rollover sisa budget ke periode lanjutan" hide-details /></VCol>
                        </VRow>
                    </VCardText>
                    <VCardActions class="px-6 pb-6"><VSpacer /><VBtn variant="text" @click="dialog = false">Batal</VBtn><VBtn color="primary" type="submit" :loading="form.processing">{{ editing ? 'Simpan Perubahan' : 'Buat Budget' }}</VBtn></VCardActions>
                </VForm>
            </VCard>
        </VDialog>
    </UserLayout>
</template>

<style scoped>
.summary-card { display: flex; align-items: center; gap: 14px; padding: 20px; }
.summary-card span { display: block; color: rgba(var(--v-theme-on-surface), .62); font-size: .78rem; }
.summary-card strong { display: block; margin-top: 2px; font-size: 1.12rem; }
.budget-card { position: relative; overflow: hidden; transition: transform .2s ease, box-shadow .2s ease; }
.budget-card::before { position: absolute; inset: 0 0 auto; height: 4px; content: ''; background: rgb(var(--v-theme-success)); }
.budget-card.health-warning::before { background: rgb(var(--v-theme-warning)); }
.budget-card.health-exceeded::before { background: rgb(var(--v-theme-error)); }
.budget-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(47,43,61,.1); }
.budget-progress { position: relative; height: 10px; border-radius: 999px; background: rgba(var(--v-theme-on-surface), .08); }
.budget-progress span { display: block; height: 100%; border-radius: inherit; transition: width .35s ease; }
.budget-progress i { position: absolute; top: -3px; width: 2px; height: 16px; border-radius: 2px; background: rgba(var(--v-theme-on-surface), .42); transform: translateX(-1px); }
</style>
