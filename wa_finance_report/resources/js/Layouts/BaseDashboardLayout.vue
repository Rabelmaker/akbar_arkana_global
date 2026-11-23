<script setup>
import logo from '@images/logo.svg';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useDisplay, useTheme } from 'vuetify';

defineProps({
    menuItems: { type: Array, required: true },
    sectionLabel: { type: String, required: true },
});

const page = usePage();
const theme = useTheme();
const { lgAndUp } = useDisplay();
const drawer = ref(lgAndUp.value);
const snackbar = ref(false);
const snackbarMessage = ref('');
const snackbarColor = ref('success');
const user = computed(() => page.props.auth.user);
const account = computed(() => page.props.auth.account);
const subscription = computed(() => page.props.auth.subscription);
const isSuperAdmin = computed(() => user.value?.roles?.includes('super-admin'));
const isDark = computed(() => theme.global.current.value.dark);

watch(lgAndUp, (isDesktop) => {
    drawer.value = isDesktop;
});

watch(
    () => [page.props.flash?.success, page.props.flash?.error],
    ([success, error]) => {
        if (success || error) {
            snackbarMessage.value = success || error;
            snackbarColor.value = error ? 'error' : 'success';
            snackbar.value = true;
        }
    },
    { immediate: true },
);

const visit = (item) => {
    if (item.route && !item.disabled) router.visit(route(item.route));
};

const logout = () => router.post(route('logout'));
const toggleTheme = () => {
    theme.global.name.value = isDark.value ? 'light' : 'dark';
};
</script>

<template>
    <VApp class="materialize-app">
        <VNavigationDrawer v-model="drawer" width="282" class="materialize-drawer">
            <div class="brand-wrap d-flex align-center gap-3 px-6">
                <div class="brand-logo"><img :src="logo" width="38" height="38" alt="WA Finance Report" /></div>
                <div>
                    <div class="text-h6 font-weight-bold brand-title">WA Finance</div>
                    <div class="text-caption text-medium-emphasis">{{ sectionLabel }}</div>
                </div>
            </div>

            <div class="px-4 mb-4">
                <VCard color="primary" variant="tonal" rounded="xl" class="account-mini-card">
                    <VCardText class="pa-4">
                        <div class="text-caption text-medium-emphasis mb-1">{{ isSuperAdmin ? 'Platform control' : 'Workspace aktif' }}</div>
                        <div class="font-weight-bold text-truncate">{{ isSuperAdmin ? 'WA Finance Platform' : (account?.name || sectionLabel) }}</div>
                        <div class="d-flex align-center justify-space-between mt-3">
                            <VChip size="x-small" color="primary" variant="flat">{{ isSuperAdmin ? 'Super Admin' : (subscription?.plan || 'No plan') }}</VChip>
                            <VIcon icon="ri-shield-check-line" size="18" color="primary" />
                        </div>
                    </VCardText>
                </VCard>
            </div>

            <div class="text-overline text-medium-emphasis px-7 mb-1">Menu utama</div>
            <VList nav class="px-3 pb-4">
                <VListItem
                    v-for="item in menuItems"
                    :key="item.title"
                    :active="item.route ? route().current(item.route) : false"
                    :disabled="item.disabled"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    color="primary"
                    rounded="lg"
                    class="nav-item mb-1"
                    @click="visit(item)"
                >
                    <template v-if="item.disabled" #append>
                        <VChip size="x-small" variant="tonal">Segera</VChip>
                    </template>
                </VListItem>
            </VList>

            <template #append>
                <div class="px-4 pb-4">
                    <VCard variant="tonal" color="info" rounded="xl">
                        <VCardText class="pa-4">
                            <VIcon :icon="isSuperAdmin ? 'ri-shield-keyhole-line' : 'ri-whatsapp-line'" color="info" class="mb-2" />
                            <div class="text-body-2 font-weight-bold">{{ isSuperAdmin ? 'System Control' : 'WhatsApp Finance' }}</div>
                            <div class="text-caption text-medium-emphasis">{{ isSuperAdmin ? 'Pantau tenant, entitlement, dan kesehatan platform.' : 'Integrasi pencatatan hadir di fase berikutnya.' }}</div>
                        </VCardText>
                    </VCard>
                </div>
            </template>
        </VNavigationDrawer>

        <VAppBar class="floating-appbar" flat height="76">
            <VAppBarNavIcon @click="drawer = !drawer" />
            <VAppBarTitle>
                <div class="text-body-2 text-medium-emphasis d-none d-sm-block">{{ isSuperAdmin ? 'Platform operations center' : 'Kelola keuangan lebih tenang' }}</div>
                <div class="text-subtitle-1 font-weight-bold">{{ account?.name || sectionLabel }}</div>
            </VAppBarTitle>
            <VBtn :icon="isDark ? 'ri-sun-line' : 'ri-moon-clear-line'" variant="text" aria-label="Ganti tema" @click="toggleTheme" />
            <VBtn icon="ri-notification-3-line" variant="text" aria-label="Notifikasi" class="d-none d-sm-inline-flex" />
            <VMenu>
                <template #activator="{ props }">
                    <VBtn v-bind="props" variant="text" class="text-none ms-1 profile-button">
                        <VAvatar color="primary" size="38">
                            <span class="text-white font-weight-bold">{{ user?.name?.charAt(0)?.toUpperCase() }}</span>
                        </VAvatar>
                        <div class="d-none d-md-block text-start ms-3">
                            <div class="text-body-2 font-weight-bold">{{ user?.name }}</div>
                            <div class="text-caption text-medium-emphasis">{{ user?.roles?.[0] || 'user' }}</div>
                        </div>
                        <VIcon icon="ri-arrow-down-s-line" class="ms-1 d-none d-md-inline-flex" />
                    </VBtn>
                </template>
                <VList min-width="230" rounded="lg">
                    <VListItem prepend-icon="ri-user-line" title="Profil saya" @click="router.visit(route('profile.edit'))" />
                    <VListItem v-if="account" prepend-icon="ri-settings-3-line" title="Pengaturan akun" @click="router.visit(route('account.settings.edit'))" />
                    <VDivider class="my-2" />
                    <VListItem prepend-icon="ri-logout-box-r-line" title="Keluar" base-color="error" @click="logout" />
                </VList>
            </VMenu>
        </VAppBar>

        <VMain>
            <VContainer fluid class="content-container pa-4 pa-md-6">
                <slot />
            </VContainer>
        </VMain>

        <VSnackbar v-model="snackbar" :color="snackbarColor" location="top end" :timeout="3500">
            <div class="d-flex align-center"><VIcon icon="ri-checkbox-circle-line" class="me-2" />{{ snackbarMessage }}</div>
        </VSnackbar>
    </VApp>
</template>

<style scoped>
.materialize-app { overflow-x: hidden; background: rgb(var(--v-theme-background)); }
.materialize-drawer { border: 0 !important; box-shadow: 0 0 28px rgba(47, 43, 61, .08); }
.brand-wrap { min-height: 88px; }
.brand-logo { display: grid; place-items: center; width: 46px; height: 46px; border-radius: 14px; background: rgba(var(--v-theme-primary), .12); }
.brand-title { letter-spacing: -.4px; }
.account-mini-card { border: 1px solid rgba(var(--v-theme-primary), .13); }
.nav-item { min-height: 46px; }
.nav-item.v-list-item--active { box-shadow: 0 4px 14px rgba(var(--v-theme-primary), .35); color: white !important; background: linear-gradient(98deg, rgb(var(--v-theme-primary)), rgba(var(--v-theme-primary), .78)); }
.floating-appbar { box-sizing: border-box; margin-block-start: 14px; margin-inline-start: 20px; width: auto !important; inset-inline-end: 20px !important; border-radius: 14px; border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); box-shadow: 0 4px 18px rgba(47, 43, 61, .07) !important; backdrop-filter: blur(14px); background: rgba(var(--v-theme-surface), .9) !important; }
.content-container { max-width: 1600px; margin-inline: auto; padding-top: 34px !important; }
.profile-button { height: 58px; }
:global(.dashboard-page-title) { font-size: clamp(1.65rem, 2vw, 2rem); line-height: 1.2; letter-spacing: -.035em; }
:global(.dashboard-page-copy) { max-width: 720px; font-size: .975rem; line-height: 1.6; }
@media (max-width: 600px) {
    .floating-appbar { margin-block-start: 8px; margin-inline-start: 0; inset-inline-start: 10px !important; inset-inline-end: 10px !important; }
    .content-container { padding-top: 26px !important; }
    :global(.dashboard-page-title) { font-size: 1.55rem; }
}
</style>
