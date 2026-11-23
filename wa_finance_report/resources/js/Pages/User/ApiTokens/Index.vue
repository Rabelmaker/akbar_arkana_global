<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    tokens: Array,
    abilities: Array,
    apiAccess: Boolean,
});

const page = usePage();
const dialog = ref(false);
const copied = ref(false);
const newToken = computed(() => page.props.flash?.new_token);
const form = useForm({
    name: '',
    abilities: ['transactions:read', 'reports:read', 'financial-accounts:read', 'budgets:read'],
    expires_at: '',
});

const createdLabel = (value) => new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
const lastUsedLabel = (value) => value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : 'Belum pernah';
const submit = () => form.post(route('api-tokens.store'), {
    preserveScroll: true,
    onSuccess: () => {
        dialog.value = false;
        form.reset();
    },
});
const revoke = (token) => {
    if (window.confirm(`Cabut token “${token.name}”? Aplikasi yang menggunakannya akan langsung kehilangan akses.`)) {
        router.delete(route('api-tokens.destroy', token.id), { preserveScroll: true });
    }
};
const copyToken = async () => {
    await navigator.clipboard.writeText(newToken.value);
    copied.value = true;
    window.setTimeout(() => { copied.value = false; }, 1800);
};
const toggleAbility = (ability) => {
    form.abilities = form.abilities.includes(ability)
        ? form.abilities.filter((item) => item !== ability)
        : [...form.abilities, ability];
};
</script>

<template>
    <Head title="API Tokens" />
    <UserLayout>
        <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
            <div>
                <div class="text-overline text-primary font-weight-bold">DEVELOPER ACCESS</div>
                <h1 class="dashboard-page-title font-weight-bold mb-1">API Tokens</h1>
                <p class="dashboard-page-copy text-medium-emphasis mb-0">Hubungkan aplikasi atau workflow n8n dengan scope akses yang terkontrol.</p>
            </div>
            <VBtn color="primary" size="large" prepend-icon="ri-add-line" :disabled="!apiAccess" @click="dialog = true">Buat Token</VBtn>
        </div>

        <VAlert v-if="!apiAccess" color="warning" variant="tonal" rounded="xl" icon="ri-lock-2-line" class="mb-6">
            Paket aktif belum menyertakan akses API. Token baru hanya dapat dibuat pada paket dengan fitur API.
        </VAlert>

        <VAlert v-if="newToken" color="success" variant="tonal" rounded="xl" icon="ri-key-2-line" class="mb-6">
            <div class="font-weight-bold mb-1">Token baru siap digunakan — salin sekarang.</div>
            <div class="text-body-2 mb-3">Token ini tidak akan ditampilkan kembali setelah halaman ditutup.</div>
            <div class="token-reveal">
                <code>{{ newToken }}</code>
                <VBtn color="success" variant="flat" size="small" :prepend-icon="copied ? 'ri-check-line' : 'ri-file-copy-line'" @click="copyToken">{{ copied ? 'Tersalin' : 'Salin' }}</VBtn>
            </div>
        </VAlert>

        <VRow class="mb-2">
            <VCol cols="12" md="4"><VCard rounded="xl"><VCardText class="metric"><VAvatar color="primary" variant="tonal" rounded="lg"><VIcon icon="ri-key-2-line" /></VAvatar><span><small>Token tersimpan</small><strong>{{ tokens.length }}</strong></span></VCardText></VCard></VCol>
            <VCol cols="12" md="4"><VCard rounded="xl"><VCardText class="metric"><VAvatar color="success" variant="tonal" rounded="lg"><VIcon icon="ri-shield-check-line" /></VAvatar><span><small>Status API</small><strong>{{ apiAccess ? 'Tersedia' : 'Terkunci' }}</strong></span></VCardText></VCard></VCol>
            <VCol cols="12" md="4"><VCard rounded="xl"><VCardText class="metric"><VAvatar color="info" variant="tonal" rounded="lg"><VIcon icon="ri-code-s-slash-line" /></VAvatar><span><small>Base endpoint</small><strong>/api/v1</strong></span></VCardText></VCard></VCol>
        </VRow>

        <VCard rounded="xl" class="overflow-hidden">
            <VCardItem class="pa-5"><VCardTitle>Token Aktif</VCardTitle><VCardSubtitle>Token disimpan dalam bentuk hash oleh Laravel Sanctum.</VCardSubtitle></VCardItem>
            <VTable class="token-table">
                <thead><tr><th>Token</th><th>Scope</th><th>Dibuat</th><th>Terakhir dipakai</th><th>Kedaluwarsa</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    <tr v-for="token in tokens" :key="token.id">
                        <td><div class="d-flex align-center py-2"><VAvatar color="primary" variant="tonal" rounded="lg" class="me-3"><VIcon icon="ri-key-line" /></VAvatar><div><strong>{{ token.name }}</strong><div class="text-caption text-medium-emphasis">Token #{{ token.id }}</div></div></div></td>
                        <td><div class="d-flex flex-wrap gap-1"><VChip v-for="ability in token.abilities.slice(0, 3)" :key="ability" size="x-small" variant="tonal">{{ ability }}</VChip><VChip v-if="token.abilities.length > 3" size="x-small">+{{ token.abilities.length - 3 }}</VChip></div></td>
                        <td>{{ createdLabel(token.created_at) }}</td>
                        <td>{{ lastUsedLabel(token.last_used_at) }}</td>
                        <td><VChip :color="token.expired ? 'error' : 'success'" size="small" variant="tonal">{{ token.expires_at || 'Tidak dibatasi' }}</VChip></td>
                        <td class="text-end"><VBtn color="error" variant="text" size="small" prepend-icon="ri-delete-bin-line" @click="revoke(token)">Cabut</VBtn></td>
                    </tr>
                    <tr v-if="!tokens.length"><td colspan="6"><div class="text-center py-14 text-medium-emphasis"><VIcon icon="ri-key-2-line" size="38" class="mb-2" /><div>Belum ada API token.</div></div></td></tr>
                </tbody>
            </VTable>
        </VCard>

        <VDialog v-model="dialog" max-width="680" persistent>
            <VCard rounded="xl" class="api-token-dialog-card">
                <VCardItem class="pa-6 pb-2"><template #prepend><VAvatar color="primary" variant="tonal"><VIcon icon="ri-key-2-line" /></VAvatar></template><VCardTitle>Buat API Token</VCardTitle><VCardSubtitle>Pilih scope minimum yang dibutuhkan integrasi.</VCardSubtitle><template #append><VBtn icon="ri-close-line" variant="text" @click="dialog = false" /></template></VCardItem>
                <VForm class="dialog-form" @submit.prevent="submit">
                    <VCardText class="pa-6">
                        <VTextField v-model="form.name" label="Nama token" placeholder="Contoh: n8n Production" :error-messages="form.errors.name" class="mb-3" />
                        <VTextField v-model="form.expires_at" type="date" label="Tanggal kedaluwarsa (opsional)" :error-messages="form.errors.expires_at" class="mb-3" />
                        <div class="text-subtitle-2 mb-2">Scope akses</div>
                        <div class="ability-grid">
                            <button
                                v-for="ability in abilities"
                                :key="ability.value"
                                type="button"
                                class="ability-option"
                                :class="{ 'ability-option--selected': form.abilities.includes(ability.value) }"
                                role="checkbox"
                                :aria-checked="form.abilities.includes(ability.value)"
                                @click="toggleAbility(ability.value)"
                            >
                                <VIcon
                                    :icon="form.abilities.includes(ability.value) ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line'"
                                    :color="form.abilities.includes(ability.value) ? 'primary' : undefined"
                                    size="22"
                                />
                                <span>
                                    <strong>{{ ability.label }}</strong>
                                    <small>{{ ability.value }}</small>
                                </span>
                            </button>
                        </div>
                        <div v-if="form.errors.abilities" class="text-error text-caption mt-2">{{ form.errors.abilities }}</div>
                    </VCardText>
                    <VCardActions class="px-6 pb-6"><VSpacer /><VBtn variant="text" @click="dialog = false">Batal</VBtn><VBtn color="primary" type="submit" :loading="form.processing">Buat Token</VBtn></VCardActions>
                </VForm>
            </VCard>
        </VDialog>
    </UserLayout>
</template>

<style scoped>
.token-reveal { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; border-radius: 10px; background: rgba(var(--v-theme-surface), .8); }
.token-reveal code { overflow: hidden; font-size: .76rem; text-overflow: ellipsis; white-space: nowrap; }
.metric { display: flex; align-items: center; gap: 14px; padding: 20px; }
.metric small, .metric strong { display: block; }
.metric small { color: rgba(var(--v-theme-on-surface), .58); }
.metric strong { margin-top: 2px; font-size: 1.08rem; }
.ability-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; }
.api-token-dialog-card { max-height: min(90vh, 760px); }
.dialog-form { display: flex; min-height: 0; flex: 1 1 auto; flex-direction: column; }
.dialog-form > .v-card-text { min-height: 0; overflow-y: auto; }
.ability-option { display: flex; align-items: center; gap: 12px; min-width: 0; padding: 12px 14px; border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); border-radius: 12px; background: rgba(var(--v-theme-surface)); color: rgb(var(--v-theme-on-surface)); cursor: pointer; text-align: start; transition: border-color .18s ease, background-color .18s ease, box-shadow .18s ease; }
.ability-option:hover { border-color: rgba(var(--v-theme-primary), .46); background: rgba(var(--v-theme-primary), .035); }
.ability-option--selected { border-color: rgba(var(--v-theme-primary), .58); background: rgba(var(--v-theme-primary), .07); box-shadow: 0 0 0 1px rgba(var(--v-theme-primary), .08); }
.ability-option span, .ability-option strong, .ability-option small { display: block; min-width: 0; }
.ability-option strong { font-size: .86rem; font-weight: 600; }
.ability-option small { margin-top: 2px; overflow: hidden; color: rgba(var(--v-theme-on-surface), .56); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .68rem; text-overflow: ellipsis; white-space: nowrap; }
@media (max-width: 959px) { .token-table { min-width: 920px; } }
@media (max-width: 600px) { .ability-grid { grid-template-columns: 1fr; } .token-reveal { align-items: stretch; flex-direction: column; } }
</style>
