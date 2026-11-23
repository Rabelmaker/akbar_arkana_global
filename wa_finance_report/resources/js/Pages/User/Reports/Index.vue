<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    report: Object,
    transactions: Object,
    filters: Object,
    financialAccounts: Array,
    categories: Array,
    users: Array,
    filterOptions: Object,
    canExport: Boolean,
});

const exportingXlsx = ref(false);
const exportError = ref('');
const advancedFilters = ref(Boolean(
    props.filters.source || props.filters.category_id || props.filters.created_by
    || props.filters.amount_min || props.filters.amount_max,
));
const filterForm = useForm({
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    type: props.filters.type || null,
    status: props.filters.status || 'posted',
    source: props.filters.source || null,
    financial_account_id: props.filters.financial_account_id || null,
    category_id: props.filters.category_id || null,
    created_by: props.filters.created_by || null,
    amount_min: props.filters.amount_min || null,
    amount_max: props.filters.amount_max || null,
});

const queryParams = computed(() => Object.fromEntries(
    Object.entries(filterForm.data()).filter(([, value]) => value !== null && value !== ''),
));
const maxCashFlow = computed(() => Math.max(
    1,
    ...props.report.cashFlow.flatMap((item) => [item.income, item.expense]),
));
const totalCategoryExpense = computed(() => Math.max(
    1,
    props.report.categories.reduce((total, item) => total + Number(item.expense), 0),
));
const totalBalance = computed(() => props.report.balances.reduce((total, item) => total + Number(item.balance), 0));

const rupiah = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(Number(value || 0));
const compactRupiah = (value) => new Intl.NumberFormat('id-ID', {
    notation: 'compact', style: 'currency', currency: 'IDR', maximumFractionDigits: 1,
}).format(Number(value || 0));
const dateLabel = (value) => new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
});
const typeColor = (type) => ({ income: 'success', expense: 'error', transfer: 'info', adjustment: 'warning' }[type] || 'secondary');
const typeIcon = (type) => ({ income: 'ri-arrow-down-circle-line', expense: 'ri-arrow-up-circle-line', transfer: 'ri-arrow-left-right-line', adjustment: 'ri-equalizer-line' }[type]);
const changeLabel = (change) => change === null ? 'Baru pada periode ini' : `${change >= 0 ? '+' : ''}${change}%`;
const changeColor = (change, inverse = false) => {
    if (change === null) return 'primary';
    const positive = change >= 0;
    return (inverse ? !positive : positive) ? 'success' : 'error';
};
const healthMeta = (health) => ({
    safe: { color: 'success', label: 'Aman' },
    warning: { color: 'warning', label: 'Waspada' },
    exceeded: { color: 'error', label: 'Terlampaui' },
}[health]);
const paginationLabel = (label) => label.includes('previous') ? '‹' : label.includes('next') ? '›' : label;

const applyFilters = () => filterForm.get(route('reports.index'), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
});
const resetFilters = () => router.get(route('reports.index'), {}, {
    preserveState: true,
    replace: true,
});
const exportUrl = (format) => route(`reports.export.${format}`, queryParams.value);
const printUrl = computed(() => route('reports.print', queryParams.value));

const downloadXlsx = async () => {
    exportingXlsx.value = true;
    exportError.value = '';
    try {
        const { default: ExcelJS } = await import('exceljs');
        const response = await fetch(exportUrl('xlsx'), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error('Data ekspor tidak dapat diambil.');
        const payload = await response.json();
        const workbook = new ExcelJS.Workbook();
        workbook.creator = 'WA Finance';
        workbook.created = new Date();

        const summary = workbook.addWorksheet('Ringkasan', {
            views: [{ showGridLines: false }],
            properties: { tabColor: { argb: 'FF7367F0' } },
        });
        summary.columns = [{ width: 28 }, { width: 24 }];
        summary.addRow(['LAPORAN KEUANGAN', payload.account]);
        summary.addRow(['Periode', `${dateLabel(payload.period.from)} - ${dateLabel(payload.period.to)}`]);
        summary.addRow([]);
        summary.addRow(['Metrik', 'Nilai']);
        [
            ['Pemasukan', payload.summary.income],
            ['Pengeluaran', payload.summary.expense],
            ['Arus Kas Bersih', payload.summary.net],
            ['Transfer', payload.summary.transfer],
            ['Biaya Admin', payload.summary.fees],
            ['Jumlah Transaksi', payload.summary.transaction_count],
        ].forEach((row) => summary.addRow(row));
        summary.mergeCells('A1:B1');
        summary.getCell('A1').font = { bold: true, size: 18, color: { argb: 'FFFFFFFF' } };
        summary.getCell('A1').fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF7367F0' } };
        summary.getCell('A1').alignment = { vertical: 'middle' };
        summary.getRow(1).height = 34;
        summary.getRow(4).font = { bold: true, color: { argb: 'FFFFFFFF' } };
        summary.getRow(4).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF4B465C' } };
        for (let row = 5; row <= 9; row += 1) summary.getCell(`B${row}`).numFmt = '"Rp" #,##0';

        const details = workbook.addWorksheet('Transaksi', {
            views: [{ state: 'frozen', ySplit: 1 }],
            properties: { tabColor: { argb: 'FF28C76F' } },
        });
        details.columns = [
            { header: 'Nomor', key: 'number', width: 23 },
            { header: 'Tanggal', key: 'date', width: 19 },
            { header: 'Jenis', key: 'type', width: 14 },
            { header: 'Deskripsi', key: 'description', width: 36 },
            { header: 'Rekening', key: 'account', width: 22 },
            { header: 'Rekening Tujuan', key: 'destination', width: 22 },
            { header: 'Kategori', key: 'category', width: 20 },
            { header: 'Nominal', key: 'amount', width: 18 },
            { header: 'Biaya', key: 'fee', width: 16 },
            { header: 'Total', key: 'total', width: 18 },
            { header: 'Status', key: 'status', width: 14 },
            { header: 'Sumber', key: 'source', width: 13 },
            { header: 'Dibuat Oleh', key: 'creator', width: 20 },
        ];
        payload.transactions.forEach((item) => details.addRow({
            number: item.transaction_number,
            date: item.date,
            type: item.type_label,
            description: item.description,
            account: item.financial_account,
            destination: item.destination_account,
            category: item.category,
            amount: item.amount,
            fee: item.fee,
            total: item.total,
            status: item.status_label,
            source: item.source,
            creator: item.creator,
        }));
        details.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } };
        details.getRow(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF7367F0' } };
        details.autoFilter = { from: 'A1', to: 'M1' };
        ['H', 'I', 'J'].forEach((column) => { details.getColumn(column).numFmt = '"Rp" #,##0'; });
        details.eachRow((row, index) => {
            if (index > 1 && index % 2 === 1) {
                row.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8F7FC' } };
            }
        });

        const buffer = await workbook.xlsx.writeBuffer();
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob([buffer], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        }));
        link.download = payload.filename;
        link.click();
        URL.revokeObjectURL(link.href);
    } catch (error) {
        exportError.value = error.message || 'Ekspor XLSX gagal.';
    } finally {
        exportingXlsx.value = false;
    }
};
</script>

<template>
    <Head title="Laporan Keuangan" />
    <UserLayout>
        <div class="report-heading mb-6">
            <div>
                <div class="text-overline text-primary font-weight-bold">FINANCIAL INTELLIGENCE</div>
                <h1 class="dashboard-page-title font-weight-bold mb-1">Laporan Keuangan</h1>
                <p class="dashboard-page-copy text-medium-emphasis mb-0">
                    Pahami arus kas, kinerja kategori, rekening, dan budget dalam satu pandangan.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <VBtn :href="printUrl" target="_blank" variant="tonal" prepend-icon="ri-printer-line">Print</VBtn>
                <VMenu v-if="canExport">
                    <template #activator="{ props: menuProps }">
                        <VBtn v-bind="menuProps" color="primary" prepend-icon="ri-download-cloud-2-line" append-icon="ri-arrow-down-s-line">
                            Export
                        </VBtn>
                    </template>
                    <VList density="comfortable">
                        <VListItem :href="exportUrl('csv')" prepend-icon="ri-file-text-line" title="CSV" subtitle="Data transaksi mentah" />
                        <VListItem prepend-icon="ri-file-excel-2-line" title="Excel / XLSX" subtitle="Ringkasan dan transaksi" :disabled="exportingXlsx" @click="downloadXlsx" />
                        <VListItem :href="exportUrl('pdf')" prepend-icon="ri-file-pdf-2-line" title="PDF" subtitle="Siap dibagikan" />
                    </VList>
                </VMenu>
                <VTooltip v-else text="Paket aktif tidak mendukung ekspor laporan">
                    <template #activator="{ props: tooltipProps }"><span v-bind="tooltipProps"><VBtn color="primary" disabled prepend-icon="ri-download-cloud-2-line">Export</VBtn></span></template>
                </VTooltip>
            </div>
        </div>

        <VAlert v-if="exportError" color="error" variant="tonal" closable rounded="xl" class="mb-5" @click:close="exportError = ''">{{ exportError }}</VAlert>

        <VCard rounded="xl" class="filter-card mb-6">
            <VCardText class="pa-5">
                <div class="d-flex align-center justify-space-between mb-4">
                    <div><div class="font-weight-bold">Periode & Filter Laporan</div><div class="text-caption text-medium-emphasis">Ringkasan mengikuti seluruh filter di bawah.</div></div>
                    <VBtn variant="text" size="small" :append-icon="advancedFilters ? 'ri-arrow-up-s-line' : 'ri-equalizer-line'" @click="advancedFilters = !advancedFilters">Filter lanjutan</VBtn>
                </div>
                <VRow align="center">
                    <VCol cols="12" sm="6" lg="2"><VTextField v-model="filterForm.date_from" type="date" label="Tanggal mulai" hide-details="auto" :error-messages="filterForm.errors.date_from" /></VCol>
                    <VCol cols="12" sm="6" lg="2"><VTextField v-model="filterForm.date_to" type="date" label="Tanggal selesai" hide-details="auto" :error-messages="filterForm.errors.date_to" /></VCol>
                    <VCol cols="12" sm="6" lg="2"><VSelect v-model="filterForm.type" clearable label="Jenis transaksi" :items="filterOptions.types" hide-details /></VCol>
                    <VCol cols="12" sm="6" lg="2"><VSelect v-model="filterForm.status" label="Status" :items="filterOptions.statuses" hide-details /></VCol>
                    <VCol cols="12" md="8" lg="2"><VSelect v-model="filterForm.financial_account_id" clearable label="Sumber dana" :items="financialAccounts" item-title="name" item-value="id" hide-details /></VCol>
                    <VCol cols="12" md="4" lg="2" class="d-flex gap-2"><VBtn color="primary" block prepend-icon="ri-filter-3-line" @click="applyFilters">Terapkan</VBtn><VBtn icon="ri-refresh-line" variant="text" @click="resetFilters" /></VCol>
                </VRow>
                <VExpandTransition>
                    <VRow v-if="advancedFilters" class="mt-1">
                        <VCol cols="12" sm="6" lg="3"><VSelect v-model="filterForm.category_id" clearable label="Kategori" :items="categories" item-title="name" item-value="id" hide-details /></VCol>
                        <VCol cols="12" sm="6" lg="3"><VSelect v-model="filterForm.source" clearable label="Sumber input" :items="filterOptions.sources" hide-details /></VCol>
                        <VCol cols="12" sm="6" lg="2"><VSelect v-model="filterForm.created_by" clearable label="Dibuat oleh" :items="users" item-title="name" item-value="id" hide-details /></VCol>
                        <VCol cols="6" lg="2"><VTextField v-model.number="filterForm.amount_min" type="number" min="0" label="Nominal minimum" prefix="Rp" hide-details /></VCol>
                        <VCol cols="6" lg="2"><VTextField v-model.number="filterForm.amount_max" type="number" min="0" label="Nominal maksimum" prefix="Rp" hide-details /></VCol>
                    </VRow>
                </VExpandTransition>
            </VCardText>
        </VCard>

        <div class="period-strip mb-5">
            <VIcon icon="ri-calendar-event-line" color="primary" />
            <span>{{ dateLabel(report.period.from) }} – {{ dateLabel(report.period.to) }}</span>
            <VChip size="small" variant="tonal" color="primary">{{ report.period.days }} hari</VChip>
            <span class="text-medium-emphasis">{{ report.summary.transaction_count }} transaksi</span>
        </div>

        <VRow class="mb-2">
            <VCol cols="12" sm="6" xl="3">
                <VCard rounded="xl" class="metric-card metric-income h-100"><VCardText><VAvatar color="success" variant="tonal" rounded="lg"><VIcon icon="ri-arrow-down-circle-line" /></VAvatar><span>Pemasukan</span><strong>{{ compactRupiah(report.summary.income) }}</strong><small>{{ rupiah(report.summary.income) }}</small></VCardText></VCard>
            </VCol>
            <VCol cols="12" sm="6" xl="3">
                <VCard rounded="xl" class="metric-card metric-expense h-100"><VCardText><VAvatar color="error" variant="tonal" rounded="lg"><VIcon icon="ri-arrow-up-circle-line" /></VAvatar><span>Pengeluaran</span><strong>{{ compactRupiah(report.summary.expense) }}</strong><small>{{ rupiah(report.summary.expense) }}</small></VCardText></VCard>
            </VCol>
            <VCol cols="12" sm="6" xl="3">
                <VCard rounded="xl" class="metric-card metric-net h-100"><VCardText><VAvatar :color="report.summary.net >= 0 ? 'primary' : 'error'" variant="tonal" rounded="lg"><VIcon icon="ri-line-chart-line" /></VAvatar><span>Arus Kas Bersih</span><strong :class="report.summary.net < 0 ? 'text-error' : ''">{{ compactRupiah(report.summary.net) }}</strong><small>Pemasukan dikurangi pengeluaran</small></VCardText></VCard>
            </VCol>
            <VCol cols="12" sm="6" xl="3">
                <VCard rounded="xl" class="metric-card h-100"><VCardText><VAvatar color="info" variant="tonal" rounded="lg"><VIcon icon="ri-wallet-3-line" /></VAvatar><span>Total Saldo Saat Ini</span><strong>{{ compactRupiah(totalBalance) }}</strong><small>{{ report.balances.length }} rekening dihitung</small></VCardText></VCard>
            </VCol>
        </VRow>

        <VRow class="mb-2">
            <VCol cols="12" xl="8">
                <VCard rounded="xl" class="h-100">
                    <VCardItem class="pa-5 pb-2">
                        <VCardTitle>Arus Kas</VCardTitle>
                        <VCardSubtitle>Pergerakan pemasukan dan pengeluaran dalam periode terpilih.</VCardSubtitle>
                        <template #append><div class="chart-legend"><span><i class="bg-success" />Pemasukan</span><span><i class="bg-error" />Pengeluaran</span></div></template>
                    </VCardItem>
                    <VCardText class="pa-5 pt-3">
                        <div v-if="report.cashFlow.length" class="cashflow-chart">
                            <div v-for="item in report.cashFlow" :key="item.date" class="chart-column">
                                <div class="bar-area">
                                    <VTooltip :text="`Masuk ${rupiah(item.income)}`"><template #activator="{ props: tooltipProps }"><span v-bind="tooltipProps" class="bar income-bar" :style="{ height: `${Math.max(item.income ? 4 : 0, item.income / maxCashFlow * 100)}%` }" /></template></VTooltip>
                                    <VTooltip :text="`Keluar ${rupiah(item.expense)}`"><template #activator="{ props: tooltipProps }"><span v-bind="tooltipProps" class="bar expense-bar" :style="{ height: `${Math.max(item.expense ? 4 : 0, item.expense / maxCashFlow * 100)}%` }" /></template></VTooltip>
                                </div>
                                <small>{{ item.label }}</small>
                            </div>
                        </div>
                    </VCardText>
                </VCard>
            </VCol>
            <VCol cols="12" xl="4">
                <VCard rounded="xl" class="h-100">
                    <VCardItem class="pa-5 pb-2"><VCardTitle>Perbandingan Periode</VCardTitle><VCardSubtitle>{{ dateLabel(report.comparison.period.from) }} – {{ dateLabel(report.comparison.period.to) }}</VCardSubtitle></VCardItem>
                    <VCardText class="pa-5 pt-3 comparison-list">
                        <div>
                            <VAvatar color="success" variant="tonal" rounded="lg"><VIcon icon="ri-arrow-down-line" /></VAvatar>
                            <span><small>Pemasukan</small><strong>{{ compactRupiah(report.summary.income) }}</strong></span>
                            <VChip :color="changeColor(report.comparison.changes.income)" size="small" variant="tonal">{{ changeLabel(report.comparison.changes.income) }}</VChip>
                        </div>
                        <div>
                            <VAvatar color="error" variant="tonal" rounded="lg"><VIcon icon="ri-arrow-up-line" /></VAvatar>
                            <span><small>Pengeluaran</small><strong>{{ compactRupiah(report.summary.expense) }}</strong></span>
                            <VChip :color="changeColor(report.comparison.changes.expense, true)" size="small" variant="tonal">{{ changeLabel(report.comparison.changes.expense) }}</VChip>
                        </div>
                        <div>
                            <VAvatar color="primary" variant="tonal" rounded="lg"><VIcon icon="ri-line-chart-line" /></VAvatar>
                            <span><small>Arus kas bersih</small><strong>{{ compactRupiah(report.summary.net) }}</strong></span>
                            <VChip :color="changeColor(report.comparison.changes.net)" size="small" variant="tonal">{{ changeLabel(report.comparison.changes.net) }}</VChip>
                        </div>
                        <VDivider />
                        <div class="comparison-note"><VIcon icon="ri-information-line" /><span>Nilai dibandingkan dengan periode sebelumnya yang berdurasi sama.</span></div>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>

        <VRow class="mb-2">
            <VCol cols="12" lg="6">
                <VCard rounded="xl" class="h-100">
                    <VCardItem class="pa-5 pb-2"><VCardTitle>Pengeluaran per Kategori</VCardTitle><VCardSubtitle>Kontributor biaya terbesar pada periode ini.</VCardSubtitle></VCardItem>
                    <VCardText class="pa-5 pt-3">
                        <div v-if="report.categories.length" class="breakdown-list">
                            <div v-for="item in report.categories.slice(0, 7)" :key="item.id || 'uncategorized'">
                                <VAvatar size="38" rounded="lg" :style="{ backgroundColor: `${item.color}1F`, color: item.color }"><VIcon :icon="item.icon" /></VAvatar>
                                <div class="breakdown-copy"><span><strong>{{ item.name }}</strong><small>{{ item.transaction_count }} transaksi</small></span><b>{{ rupiah(item.expense) }}</b><div><i :style="{ width: `${item.expense / totalCategoryExpense * 100}%`, backgroundColor: item.color }" /></div></div>
                            </div>
                        </div>
                        <div v-else class="empty-state"><VIcon icon="ri-pie-chart-2-line" /><span>Belum ada data kategori.</span></div>
                    </VCardText>
                </VCard>
            </VCol>
            <VCol cols="12" lg="6">
                <VCard rounded="xl" class="h-100">
                    <VCardItem class="pa-5 pb-2"><VCardTitle>Kinerja Sumber Dana</VCardTitle><VCardSubtitle>Aktivitas masuk dan keluar per rekening.</VCardSubtitle></VCardItem>
                    <VCardText class="pa-5 pt-3 account-list">
                        <div v-for="item in report.accounts.slice(0, 7)" :key="item.id">
                            <VAvatar size="42" rounded="lg" :style="{ backgroundColor: `${item.color}1F`, color: item.color }"><VIcon :icon="item.icon" /></VAvatar>
                            <span><strong>{{ item.name }}</strong><small>{{ item.transaction_count }} transaksi</small></span>
                            <div><small class="text-success">+{{ compactRupiah(item.income) }}</small><small class="text-error">−{{ compactRupiah(item.expense) }}</small></div>
                        </div>
                        <div v-if="!report.accounts.length" class="empty-state"><VIcon icon="ri-wallet-3-line" /><span>Belum ada aktivitas rekening.</span></div>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>

        <VCard v-if="report.budgets.length" rounded="xl" class="mb-6">
            <VCardItem class="pa-5 pb-2"><VCardTitle>Realisasi Budget</VCardTitle><VCardSubtitle>Penggunaan mengikuti periode asli masing-masing budget.</VCardSubtitle><template #append><Link :href="route('budgets.index')"><VBtn variant="text" append-icon="ri-arrow-right-line">Kelola budget</VBtn></Link></template></VCardItem>
            <VCardText class="pa-5 pt-3"><VRow><VCol v-for="budget in report.budgets" :key="budget.id" cols="12" md="6" xl="3"><div class="budget-tile"><div class="d-flex justify-space-between gap-2"><strong>{{ budget.name }}</strong><VChip :color="healthMeta(budget.health).color" size="x-small" variant="tonal">{{ healthMeta(budget.health).label }}</VChip></div><small>{{ budget.category || budget.financial_account || 'Seluruh pengeluaran' }}</small><div class="budget-bar"><span :class="`bg-${healthMeta(budget.health).color}`" :style="{ width: `${Math.min(budget.percentage, 100)}%` }" /></div><div class="d-flex justify-space-between"><small>{{ rupiah(budget.used) }}</small><strong>{{ budget.percentage }}%</strong></div></div></VCol></VRow></VCardText>
        </VCard>

        <VCard rounded="xl" class="overflow-hidden">
            <VCardItem class="pa-5">
                <VCardTitle>Rincian Transaksi</VCardTitle>
                <VCardSubtitle>Data audit yang membentuk seluruh ringkasan laporan.</VCardSubtitle>
            </VCardItem>
            <div class="table-scroll">
                <VTable class="report-table">
                    <thead><tr><th>Transaksi</th><th>Rekening</th><th>Tanggal</th><th>Status</th><th>Sumber</th><th class="text-end">Nominal</th></tr></thead>
                    <tbody>
                        <tr v-for="item in transactions.data" :key="item.id">
                            <td><div class="d-flex align-center py-2"><VAvatar :color="typeColor(item.type)" variant="tonal" rounded="lg" size="40" class="me-3"><VIcon :icon="typeIcon(item.type)" /></VAvatar><div><div class="font-weight-bold">{{ item.description }}</div><div class="text-caption text-medium-emphasis">{{ item.transaction_number }} · {{ item.category || item.type_label }}</div></div></div></td>
                            <td><div>{{ item.financial_account }}</div><small v-if="item.destination_account" class="text-info">ke {{ item.destination_account }}</small></td>
                            <td>{{ item.date }}</td>
                            <td><VChip size="small" variant="tonal">{{ item.status_label }}</VChip></td>
                            <td><VChip size="x-small" variant="outlined">{{ item.source }}</VChip></td>
                            <td class="text-end font-weight-bold" :class="item.type === 'income' ? 'text-success' : item.type === 'expense' ? 'text-error' : 'text-info'">{{ item.type === 'income' ? '+' : item.type === 'expense' ? '−' : '' }}{{ rupiah(item.total) }}</td>
                        </tr>
                        <tr v-if="!transactions.data.length"><td colspan="6"><div class="empty-state py-10"><VIcon icon="ri-file-search-line" /><span>Tidak ada transaksi yang sesuai filter.</span></div></td></tr>
                    </tbody>
                </VTable>
            </div>
            <VDivider />
            <div class="d-flex flex-wrap align-center justify-space-between gap-3 pa-4">
                <span class="text-caption text-medium-emphasis">Menampilkan {{ transactions.from || 0 }}–{{ transactions.to || 0 }} dari {{ transactions.total }}</span>
                <div class="d-flex gap-1"><Link v-for="link in transactions.links" :key="link.label" :href="link.url || '#'" preserve-scroll class="text-decoration-none"><VBtn :disabled="!link.url" :color="link.active ? 'primary' : undefined" :variant="link.active ? 'flat' : 'text'" size="small">{{ paginationLabel(link.label) }}</VBtn></Link></div>
            </div>
        </VCard>
    </UserLayout>
</template>

<style scoped>
.report-heading { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px; }
.filter-card { border: 1px solid rgba(var(--v-theme-primary), .1); background: linear-gradient(135deg, rgba(var(--v-theme-primary), .035), transparent 50%); }
.period-strip { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; font-size: .86rem; }
.metric-card { position: relative; overflow: hidden; }
.metric-card::after { position: absolute; inset: 0 0 auto; height: 3px; content: ''; background: rgb(var(--v-theme-info)); }
.metric-card.metric-income::after { background: rgb(var(--v-theme-success)); }
.metric-card.metric-expense::after { background: rgb(var(--v-theme-error)); }
.metric-card.metric-net::after { background: rgb(var(--v-theme-primary)); }
.metric-card .v-card-text { display: grid; grid-template-columns: auto 1fr; column-gap: 14px; padding: 20px; }
.metric-card .v-avatar { grid-row: 1 / span 3; }
.metric-card span, .metric-card small { color: rgba(var(--v-theme-on-surface), .6); }
.metric-card span { font-size: .78rem; }
.metric-card strong { margin-top: 2px; font-size: 1.18rem; line-height: 1.35; }
.metric-card small { font-size: .7rem; }
.chart-legend { display: flex; gap: 12px; font-size: .72rem; }
.chart-legend span { display: flex; align-items: center; gap: 5px; }
.chart-legend i { width: 8px; height: 8px; border-radius: 50%; }
.cashflow-chart { display: flex; align-items: stretch; gap: 8px; min-height: 250px; padding: 10px 2px 0; overflow-x: auto; }
.chart-column { display: flex; flex: 1 0 26px; flex-direction: column; align-items: center; min-width: 26px; }
.bar-area { display: flex; flex: 1; align-items: flex-end; gap: 3px; width: 100%; border-bottom: 1px solid rgba(var(--v-border-color), .18); }
.bar { flex: 1; min-height: 0; border-radius: 4px 4px 0 0; transition: opacity .2s; }
.bar:hover { opacity: .72; }
.income-bar { background: linear-gradient(180deg, #28c76f, #7ee2aa); }
.expense-bar { background: linear-gradient(180deg, #ea5455, #f29293); }
.chart-column small { max-width: 44px; padding-top: 7px; color: rgba(var(--v-theme-on-surface), .52); font-size: .58rem; white-space: nowrap; transform: rotate(-35deg); transform-origin: top center; }
.comparison-list { display: flex; flex-direction: column; gap: 15px; }
.comparison-list > div { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 12px; }
.comparison-list span small, .comparison-list span strong { display: block; }
.comparison-list span small { color: rgba(var(--v-theme-on-surface), .58); }
.comparison-list .comparison-note { grid-template-columns: auto 1fr; color: rgba(var(--v-theme-on-surface), .62); font-size: .72rem; }
.breakdown-list { display: flex; flex-direction: column; gap: 15px; }
.breakdown-list > div { display: flex; align-items: center; gap: 12px; }
.breakdown-copy { display: grid; flex: 1; grid-template-columns: 1fr auto; gap: 4px 12px; }
.breakdown-copy span strong, .breakdown-copy span small { display: block; }
.breakdown-copy span small { color: rgba(var(--v-theme-on-surface), .58); }
.breakdown-copy > div { grid-column: 1 / -1; height: 5px; overflow: hidden; border-radius: 20px; background: rgba(var(--v-theme-on-surface), .07); }
.breakdown-copy > div i { display: block; height: 100%; border-radius: inherit; }
.account-list { display: flex; flex-direction: column; gap: 10px; }
.account-list > div { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 12px; padding: 7px 0; }
.account-list span strong, .account-list span small, .account-list > div > div small { display: block; }
.account-list span small { color: rgba(var(--v-theme-on-surface), .58); }
.account-list > div > div { text-align: right; }
.budget-tile { height: 100%; padding: 15px; border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); border-radius: 14px; }
.budget-tile > small { display: block; min-height: 32px; margin-top: 4px; color: rgba(var(--v-theme-on-surface), .58); }
.budget-bar { height: 7px; margin: 12px 0 7px; overflow: hidden; border-radius: 20px; background: rgba(var(--v-theme-on-surface), .08); }
.budget-bar span { display: block; height: 100%; border-radius: inherit; }
.table-scroll { overflow-x: auto; }
.report-table { min-width: 900px; }
.report-table :deep(tbody tr) { transition: background .18s ease; }
.report-table :deep(tbody tr:hover) { background: rgba(var(--v-theme-primary), .035); }
.empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; min-height: 120px; color: rgba(var(--v-theme-on-surface), .5); text-align: center; }
.empty-state .v-icon { font-size: 34px; }
@media (max-width: 959px) {
    .chart-legend { display: none; }
    .cashflow-chart { min-height: 220px; }
}
@media (max-width: 600px) {
    .report-heading > div, .report-heading .v-btn { width: 100%; }
    .report-heading > div:last-child { display: grid !important; grid-template-columns: 1fr 1fr; }
    .report-heading > div:last-child > * { width: 100%; }
}
</style>
