<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    financialAccounts: Array,
    summary: Object,
    types: Array,
});

const page = usePage();
const dialog = ref(false);
const editing = ref(null);
const canWrite = computed(() => page.props.auth.subscription?.can_write);
const colors = ['#7367F0', '#28C76F', '#00BAD1', '#FF9F43', '#EA5455', '#A8AAAE'];
const icons = [
    { title: 'Dompet', value: 'ri-wallet-3-line' },
    { title: 'Kas', value: 'ri-cash-line' },
    { title: 'Bank', value: 'ri-bank-line' },
    { title: 'Kartu', value: 'ri-bank-card-line' },
    { title: 'Ponsel', value: 'ri-smartphone-line' },
    { title: 'Brankas', value: 'ri-safe-2-line' },
];

const emptyForm = () => ({
    name: '',
    type: 'cash',
    institution_name: '',
    account_number: '',
    account_holder_name: '',
    opening_balance: 0,
    color: '#7367F0',
    icon: 'ri-wallet-3-line',
    is_active: true,
    include_in_total: true,
    notes: '',
});

const form = useForm(emptyForm());
const rupiah = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value || 0));

const openCreate = () => {
    editing.value = null;
    form.defaults(emptyForm());
    form.reset();
    form.clearErrors();
    dialog.value = true;
};

const openEdit = (item) => {
    editing.value = item;
    form.defaults({
        name: item.name,
        type: item.type,
        institution_name: item.institution_name || '',
        account_number: item.account_number || '',
        account_holder_name: item.account_holder_name || '',
        opening_balance: Number(item.opening_balance),
        color: item.color,
        icon: item.icon,
        is_active: item.is_active,
        include_in_total: item.include_in_total,
        notes: item.notes || '',
    });
    form.reset();
    form.clearErrors();
    dialog.value = true;
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => { dialog.value = false; } };
    if (editing.value) form.patch(route('financial-accounts.update', editing.value.id), options);
    else form.post(route('financial-accounts.store'), options);
};

const remove = (item) => {
    if (window.confirm(`Hapus rekening “${item.name}”? Data ini tidak akan tampil lagi.`)) {
        router.delete(route('financial-accounts.destroy', item.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Rekening Keuangan" />
    <UserLayout>
        <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
            <div>
                <div class="text-overline text-primary font-weight-bold">FINANCIAL FOUNDATION</div>
                <h1 class="text-h4 font-weight-bold mb-1">Rekening Keuangan</h1>
                <p class="text-medium-emphasis mb-0">Kelola seluruh sumber dana dan saldo pembuka dalam satu tempat.</p>
            </div>
            <VBtn color="primary" size="large" prepend-icon="ri-add-line" :disabled="!canWrite" @click="openCreate">Tambah Rekening</VBtn>
        </div>

        <VAlert v-if="!canWrite" color="warning" variant="tonal" rounded="xl" class="mb-6" icon="ri-lock-line">
            Mode hanya-baca aktif karena subscription tidak aktif.
        </VAlert>

        <VRow class="mb-2">
            <VCol cols="12" sm="4">
                <VCard rounded="xl"><VCardText class="d-flex align-center pa-5">
                    <VAvatar color="primary" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-funds-box-line" /></VAvatar>
                    <div><div class="text-caption text-medium-emphasis">Total Saldo</div><div class="text-h6 font-weight-bold">{{ rupiah(summary.total_balance) }}</div></div>
                </VCardText></VCard>
            </VCol>
            <VCol cols="12" sm="4">
                <VCard rounded="xl"><VCardText class="d-flex align-center pa-5">
                    <VAvatar color="success" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-checkbox-circle-line" /></VAvatar>
                    <div><div class="text-caption text-medium-emphasis">Rekening Aktif</div><div class="text-h6 font-weight-bold">{{ summary.active_count }}</div></div>
                </VCardText></VCard>
            </VCol>
            <VCol cols="12" sm="4">
                <VCard rounded="xl"><VCardText class="d-flex align-center pa-5">
                    <VAvatar color="info" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-bank-card-2-line" /></VAvatar>
                    <div><div class="text-caption text-medium-emphasis">Semua Sumber Dana</div><div class="text-h6 font-weight-bold">{{ summary.total_count }}</div></div>
                </VCardText></VCard>
            </VCol>
        </VRow>

        <VRow v-if="financialAccounts.length">
            <VCol v-for="item in financialAccounts" :key="item.id" cols="12" md="6" xl="4">
                <VCard rounded="xl" class="account-card h-100" :class="{ 'account-card-inactive': !item.is_active }">
                    <div class="account-card-accent" :style="{ background: item.color }" />
                    <VCardText class="pa-5">
                        <div class="d-flex align-start justify-space-between mb-6">
                            <VAvatar :color="item.color" variant="tonal" rounded="lg" size="50"><VIcon :icon="item.icon" size="26" /></VAvatar>
                            <div class="d-flex align-center gap-1">
                                <VChip :color="item.is_active ? 'success' : 'secondary'" size="small" variant="tonal">{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</VChip>
                                <VMenu>
                                    <template #activator="{ props: menuProps }"><VBtn v-bind="menuProps" icon="ri-more-2-fill" size="small" variant="text" /></template>
                                    <VList density="compact">
                                        <VListItem prepend-icon="ri-edit-line" title="Edit" :disabled="!canWrite" @click="openEdit(item)" />
                                        <VListItem prepend-icon="ri-delete-bin-6-line" title="Hapus" base-color="error" :disabled="!canWrite" @click="remove(item)" />
                                    </VList>
                                </VMenu>
                            </div>
                        </div>
                        <div class="text-caption text-medium-emphasis">{{ item.type_label }}</div>
                        <h2 class="text-h6 font-weight-bold mb-1">{{ item.name }}</h2>
                        <div class="text-body-2 text-medium-emphasis mb-5">{{ item.institution_name || 'Dikelola internal' }}</div>
                        <div class="text-caption text-medium-emphasis">Saldo saat ini</div>
                        <div class="text-h5 font-weight-bold mb-5">{{ rupiah(item.current_balance) }}</div>
                        <VDivider class="mb-4" />
                        <div class="d-flex justify-space-between text-body-2">
                            <span class="text-medium-emphasis">Saldo pembuka</span><strong>{{ rupiah(item.opening_balance) }}</strong>
                        </div>
                        <div v-if="item.account_number" class="d-flex justify-space-between text-body-2 mt-2">
                            <span class="text-medium-emphasis">Nomor rekening</span><strong>•••• {{ item.account_number.slice(-4) }}</strong>
                        </div>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>

        <VCard v-else rounded="xl">
            <VCardText class="text-center py-16">
                <VAvatar color="primary" variant="tonal" size="72"><VIcon icon="ri-wallet-add-line" size="36" /></VAvatar>
                <h2 class="text-h5 font-weight-bold mt-5">Belum ada rekening keuangan</h2>
                <p class="text-medium-emphasis mx-auto mb-5 empty-copy">Tambahkan kas, bank, e-wallet, atau sumber dana lain untuk menyiapkan pencatatan transaksi.</p>
                <VBtn color="primary" prepend-icon="ri-add-line" :disabled="!canWrite" @click="openCreate">Tambah Rekening Pertama</VBtn>
            </VCardText>
        </VCard>

        <VDialog v-model="dialog" max-width="760" persistent>
            <VCard rounded="xl">
                <VCardItem class="pa-6 pb-2">
                    <template #prepend><VAvatar color="primary" variant="tonal"><VIcon :icon="editing ? 'ri-edit-line' : 'ri-wallet-add-line'" /></VAvatar></template>
                    <VCardTitle>{{ editing ? 'Edit Rekening' : 'Tambah Rekening' }}</VCardTitle>
                    <VCardSubtitle>Saldo saat ini mengikuti perubahan saldo pembuka secara otomatis.</VCardSubtitle>
                    <template #append><VBtn icon="ri-close-line" variant="text" @click="dialog = false" /></template>
                </VCardItem>
                <VForm @submit.prevent="submit">
                    <VCardText class="pa-6">
                        <VRow>
                            <VCol cols="12" md="7"><VTextField v-model="form.name" label="Nama rekening" placeholder="Contoh: Bank Operasional" :error-messages="form.errors.name" /></VCol>
                            <VCol cols="12" md="5"><VSelect v-model="form.type" label="Jenis rekening" :items="types" :error-messages="form.errors.type" /></VCol>
                            <VCol cols="12" md="6"><VTextField v-model="form.institution_name" label="Nama institusi" placeholder="Bank / penyedia e-wallet" :error-messages="form.errors.institution_name" /></VCol>
                            <VCol cols="12" md="6"><VTextField v-model="form.account_number" label="Nomor rekening" :error-messages="form.errors.account_number" /></VCol>
                            <VCol cols="12" md="6"><VTextField v-model="form.account_holder_name" label="Nama pemilik" :error-messages="form.errors.account_holder_name" /></VCol>
                            <VCol cols="12" md="6"><VTextField v-model.number="form.opening_balance" type="number" label="Saldo pembuka" prefix="Rp" :error-messages="form.errors.opening_balance" /></VCol>
                            <VCol cols="12" md="6"><VSelect v-model="form.icon" label="Ikon" :items="icons" :error-messages="form.errors.icon"><template #prepend-inner><VIcon :icon="form.icon" /></template></VSelect></VCol>
                            <VCol cols="12" md="6">
                                <div class="text-body-2 mb-2">Warna identitas</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button v-for="color in colors" :key="color" type="button" class="color-option" :class="{ selected: form.color === color }" :style="{ background: color }" @click="form.color = color" />
                                </div>
                                <div v-if="form.errors.color" class="text-error text-caption mt-1">{{ form.errors.color }}</div>
                            </VCol>
                            <VCol cols="12"><VTextarea v-model="form.notes" label="Catatan" rows="2" :error-messages="form.errors.notes" /></VCol>
                            <VCol cols="12" md="6"><VSwitch v-model="form.is_active" color="primary" label="Rekening aktif" hide-details /></VCol>
                            <VCol cols="12" md="6"><VSwitch v-model="form.include_in_total" color="primary" label="Hitung dalam total saldo" hide-details /></VCol>
                        </VRow>
                    </VCardText>
                    <VCardActions class="px-6 pb-6">
                        <VSpacer />
                        <VBtn variant="text" @click="dialog = false">Batal</VBtn>
                        <VBtn color="primary" type="submit" :loading="form.processing">{{ editing ? 'Simpan Perubahan' : 'Tambah Rekening' }}</VBtn>
                    </VCardActions>
                </VForm>
            </VCard>
        </VDialog>
    </UserLayout>
</template>

<style scoped>
.account-card { position: relative; overflow: hidden; transition: transform .2s ease, box-shadow .2s ease; }
.account-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(47, 43, 61, .1); }
.account-card-inactive { opacity: .72; }
.account-card-accent { position: absolute; inset: 0 0 auto; height: 4px; }
.empty-copy { max-width: 520px; }
.color-option { width: 34px; height: 34px; border-radius: 10px; border: 3px solid transparent; transition: transform .15s ease; }
.color-option:hover { transform: scale(1.08); }
.color-option.selected { border-color: rgb(var(--v-theme-surface)); outline: 2px solid currentColor; }
</style>
