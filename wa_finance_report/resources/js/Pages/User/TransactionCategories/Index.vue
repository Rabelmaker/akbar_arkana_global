<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ categories: Array, types: Array, summary: Object });
const page = usePage();
const dialog = ref(false);
const editing = ref(null);
const typeFilter = ref('all');
const canWrite = computed(() => page.props.auth.subscription?.can_write);
const parentOptions = computed(() => props.categories
    .filter((item) => item.id !== editing.value?.id)
    .map((item) => ({ title: item.name, value: item.id })));
const filteredCategories = computed(() => typeFilter.value === 'all'
    ? props.categories
    : props.categories.filter((item) => item.type === typeFilter.value || item.type === 'both'));
const colors = ['#7367F0', '#28C76F', '#00BAD1', '#FF9F43', '#EA5455', '#A8AAAE'];
const icons = [
    { title: 'Tag', value: 'ri-price-tag-3-line' },
    { title: 'Belanja', value: 'ri-shopping-bag-3-line' },
    { title: 'Toko', value: 'ri-store-2-line' },
    { title: 'Tim', value: 'ri-team-line' },
    { title: 'Mobil', value: 'ri-car-line' },
    { title: 'Tagihan', value: 'ri-bill-line' },
    { title: 'Pendapatan', value: 'ri-add-circle-line' },
];
const emptyForm = () => ({
    name: '', type: 'expense', parent_id: null, icon: 'ri-price-tag-3-line',
    color: '#7367F0', description: '', is_active: true, sort_order: 0,
});
const form = useForm(emptyForm());

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
        name: item.name, type: item.type, parent_id: item.parent_id, icon: item.icon,
        color: item.color, description: item.description || '', is_active: item.is_active, sort_order: item.sort_order,
    });
    form.reset();
    form.clearErrors();
    dialog.value = true;
};
const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => { dialog.value = false; } };
    if (editing.value) form.patch(route('transaction-categories.update', editing.value.id), options);
    else form.post(route('transaction-categories.store'), options);
};
const remove = (item) => {
    if (window.confirm(`Hapus kategori “${item.name}”?`)) {
        router.delete(route('transaction-categories.destroy', item.id), { preserveScroll: true });
    }
};
const typeColor = (type) => ({ income: 'success', expense: 'error', both: 'info' }[type] || 'secondary');
</script>

<template>
    <Head title="Kategori Transaksi" />
    <UserLayout>
        <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
            <div>
                <div class="text-overline text-primary font-weight-bold">SMART CLASSIFICATION</div>
                <h1 class="text-h4 font-weight-bold mb-1">Kategori Transaksi</h1>
                <p class="text-medium-emphasis mb-0">Bangun struktur kategori yang rapi untuk laporan yang mudah dibaca.</p>
            </div>
            <VBtn color="primary" size="large" prepend-icon="ri-add-line" :disabled="!canWrite" @click="openCreate">Tambah Kategori</VBtn>
        </div>

        <VAlert v-if="!canWrite" color="warning" variant="tonal" rounded="xl" class="mb-6" icon="ri-lock-line">Mode hanya-baca aktif karena subscription tidak aktif.</VAlert>

        <VRow class="mb-2">
            <VCol cols="12" sm="4"><VCard rounded="xl"><VCardText class="d-flex align-center pa-5"><VAvatar color="success" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-arrow-down-circle-line" /></VAvatar><div><div class="text-caption text-medium-emphasis">Pemasukan</div><div class="text-h5 font-weight-bold">{{ summary.income }}</div></div></VCardText></VCard></VCol>
            <VCol cols="12" sm="4"><VCard rounded="xl"><VCardText class="d-flex align-center pa-5"><VAvatar color="error" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-arrow-up-circle-line" /></VAvatar><div><div class="text-caption text-medium-emphasis">Pengeluaran</div><div class="text-h5 font-weight-bold">{{ summary.expense }}</div></div></VCardText></VCard></VCol>
            <VCol cols="12" sm="4"><VCard rounded="xl"><VCardText class="d-flex align-center pa-5"><VAvatar color="primary" variant="tonal" rounded="lg" class="me-4"><VIcon icon="ri-checkbox-circle-line" /></VAvatar><div><div class="text-caption text-medium-emphasis">Kategori Aktif</div><div class="text-h5 font-weight-bold">{{ summary.active }}</div></div></VCardText></VCard></VCol>
        </VRow>

        <VCard rounded="xl">
            <VCardItem class="pa-5">
                <VCardTitle>Daftar Kategori</VCardTitle>
                <VCardSubtitle>Kategori bawaan dilindungi agar struktur dasar tetap tersedia.</VCardSubtitle>
                <template #append>
                    <VBtnToggle v-model="typeFilter" mandatory color="primary" density="comfortable" variant="outlined" class="d-none d-md-flex">
                        <VBtn value="all">Semua</VBtn><VBtn value="income">Pemasukan</VBtn><VBtn value="expense">Pengeluaran</VBtn>
                    </VBtnToggle>
                </template>
            </VCardItem>
            <VDivider />
            <VList v-if="filteredCategories.length" lines="two" class="pa-2">
                <template v-for="(item, index) in filteredCategories" :key="item.id">
                    <VListItem rounded="lg" class="category-item py-3">
                        <template #prepend><VAvatar :color="item.color" variant="tonal" rounded="lg" class="me-4"><VIcon :icon="item.icon" /></VAvatar></template>
                        <VListItemTitle class="font-weight-bold">
                            {{ item.name }}
                            <VChip v-if="item.is_default" size="x-small" color="primary" variant="tonal" class="ms-2">Bawaan</VChip>
                        </VListItemTitle>
                        <VListItemSubtitle>
                            <span class="d-sm-none">{{ item.type_label }} · </span><span v-if="item.parent_name">Di bawah {{ item.parent_name }} · </span>{{ item.description || item.slug }}
                        </VListItemSubtitle>
                        <template #append>
                            <VChip :color="typeColor(item.type)" size="small" variant="tonal" class="me-3 d-none d-sm-inline-flex">{{ item.type_label }}</VChip>
                            <VChip :color="item.is_active ? 'success' : 'secondary'" size="small" variant="text" class="d-none d-sm-inline-flex">{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</VChip>
                            <VBtn icon="ri-edit-line" size="small" variant="text" :disabled="!canWrite" @click="openEdit(item)" />
                            <VBtn icon="ri-delete-bin-6-line" size="small" variant="text" color="error" :disabled="!canWrite || item.is_default" @click="remove(item)" />
                        </template>
                    </VListItem>
                    <VDivider v-if="index < filteredCategories.length - 1" inset />
                </template>
            </VList>
            <VCardText v-else class="text-center py-14">
                <VAvatar color="primary" variant="tonal" size="64"><VIcon icon="ri-folder-add-line" size="32" /></VAvatar>
                <h3 class="text-h6 mt-4">Kategori belum tersedia</h3>
                <p class="text-medium-emphasis">Tambahkan kategori pertama untuk mengelompokkan transaksi.</p>
            </VCardText>
        </VCard>

        <VDialog v-model="dialog" max-width="700" persistent>
            <VCard rounded="xl">
                <VCardItem class="pa-6 pb-2">
                    <template #prepend><VAvatar color="primary" variant="tonal"><VIcon icon="ri-folder-add-line" /></VAvatar></template>
                    <VCardTitle>{{ editing ? 'Edit Kategori' : 'Tambah Kategori' }}</VCardTitle>
                    <VCardSubtitle>Kategori dapat disusun bertingkat tanpa batas yang berputar.</VCardSubtitle>
                    <template #append><VBtn icon="ri-close-line" variant="text" @click="dialog = false" /></template>
                </VCardItem>
                <VForm @submit.prevent="submit">
                    <VCardText class="pa-6">
                        <VRow>
                            <VCol cols="12" md="7"><VTextField v-model="form.name" label="Nama kategori" :error-messages="form.errors.name" /></VCol>
                            <VCol cols="12" md="5"><VSelect v-model="form.type" label="Jenis" :items="types" :error-messages="form.errors.type" /></VCol>
                            <VCol cols="12" md="6"><VSelect v-model="form.parent_id" label="Kategori induk (opsional)" :items="parentOptions" clearable :error-messages="form.errors.parent_id" /></VCol>
                            <VCol cols="12" md="6"><VSelect v-model="form.icon" label="Ikon" :items="icons" :error-messages="form.errors.icon"><template #prepend-inner><VIcon :icon="form.icon" /></template></VSelect></VCol>
                            <VCol cols="12">
                                <div class="text-body-2 mb-2">Warna identitas</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button v-for="color in colors" :key="color" type="button" class="color-option" :class="{ selected: form.color === color }" :style="{ background: color }" @click="form.color = color" />
                                </div>
                                <div v-if="form.errors.color" class="text-error text-caption mt-1">{{ form.errors.color }}</div>
                            </VCol>
                            <VCol cols="12"><VTextarea v-model="form.description" label="Deskripsi" rows="2" :error-messages="form.errors.description" /></VCol>
                            <VCol cols="12" md="6"><VTextField v-model.number="form.sort_order" type="number" min="0" label="Urutan" :error-messages="form.errors.sort_order" /></VCol>
                            <VCol cols="12" md="6" class="d-flex align-center"><VSwitch v-model="form.is_active" color="primary" label="Kategori aktif" hide-details /></VCol>
                        </VRow>
                    </VCardText>
                    <VCardActions class="px-6 pb-6"><VSpacer /><VBtn variant="text" @click="dialog = false">Batal</VBtn><VBtn color="primary" type="submit" :loading="form.processing">{{ editing ? 'Simpan Perubahan' : 'Tambah Kategori' }}</VBtn></VCardActions>
                </VForm>
            </VCard>
        </VDialog>
    </UserLayout>
</template>

<style scoped>
.category-item { transition: background .2s ease; }
.category-item:hover { background: rgba(var(--v-theme-primary), .04); }
.color-option { width: 34px; height: 34px; border-radius: 10px; border: 3px solid transparent; transition: transform .15s ease; }
.color-option:hover { transform: scale(1.08); }
.color-option.selected { border-color: rgb(var(--v-theme-surface)); outline: 2px solid currentColor; }
@media (max-width: 600px) {
    .category-item :deep(.v-list-item__append) { gap: 2px; }
    .category-item :deep(.v-list-item-subtitle) { white-space: normal; }
}
</style>
