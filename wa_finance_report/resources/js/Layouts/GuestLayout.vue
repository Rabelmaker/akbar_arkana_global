<script setup>
import logo from '@images/logo.svg';
import loginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png';
import loginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png';
import loginMaskDark from '@images/pages/auth-v2-login-mask-dark.png';
import loginMaskLight from '@images/pages/auth-v2-login-mask-light.png';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTheme } from 'vuetify';

defineProps({
    illustrated: { type: Boolean, default: false },
});

const theme = useTheme();
const illustration = computed(() => theme.global.current.value.dark ? loginIllustrationDark : loginIllustrationLight);
const mask = computed(() => theme.global.current.value.dark ? loginMaskDark : loginMaskLight);
</script>

<template>
    <VApp>
        <VMain v-if="illustrated" class="auth-v2">
            <Link href="/" class="auth-brand d-flex align-center gap-3">
                <span class="auth-brand-mark"><img :src="logo" width="38" height="38" alt="WA Finance Report" /></span>
                <span class="text-h6 font-weight-bold">WA Finance</span>
            </Link>

            <VRow no-gutters class="auth-v2-row">
                <VCol md="7" lg="8" class="auth-visual d-none d-md-flex align-center justify-center position-relative">
                    <div class="auth-visual-copy">
                        <VChip color="primary" variant="tonal" prepend-icon="ri-sparkling-2-line" class="mb-4">Smart Financial Workspace</VChip>
                        <h2 class="text-h4 font-weight-bold mb-2">Keuangan bisnis dalam satu kendali.</h2>
                        <p class="text-medium-emphasis mb-0">Pantau saldo, susun kategori, dan siapkan laporan keuangan dari satu workspace yang aman.</p>
                    </div>
                    <img :src="illustration" class="auth-illustration" alt="Ilustrasi dashboard keuangan" />
                    <img :src="mask" class="auth-mask" alt="" />
                </VCol>

                <VCol cols="12" md="5" lg="4" class="auth-form-panel d-flex align-center justify-center">
                    <VCard flat width="100%" max-width="500" class="auth-form-card pa-5 pa-sm-8">
                        <slot />
                    </VCard>
                </VCol>
            </VRow>
        </VMain>

        <VMain v-else class="bg-background">
            <div class="d-flex align-center justify-center min-vh-100 pa-4">
                <VCard width="100%" max-width="480" elevation="8" class="pa-4 pa-sm-7">
                    <div class="d-flex align-center justify-center gap-3 mb-7">
                        <img :src="logo" width="40" height="40" alt="WA Finance Report" />
                        <div class="text-h5 font-weight-bold">WA Finance Report</div>
                    </div>
                    <slot />
                </VCard>
            </div>
        </VMain>
    </VApp>
</template>

<style scoped>
.min-vh-100 { min-height: 100vh; }
.auth-v2 { min-height: 100dvh; overflow: hidden; background: rgb(var(--v-theme-background)); }
.auth-v2-row { min-height: 100dvh; }
.auth-brand { position: fixed; z-index: 10; inset-block-start: 28px; inset-inline-start: 34px; color: rgb(var(--v-theme-on-background)); text-decoration: none; }
.auth-brand-mark { display: grid; place-items: center; width: 48px; height: 48px; border-radius: 15px; background: rgba(var(--v-theme-primary), .12); }
.auth-visual { min-height: 100dvh; padding: 110px 5vw 46px; }
.auth-visual-copy { position: absolute; z-index: 2; inset-block-start: 100px; inset-inline-start: 8%; max-width: 590px; }
.auth-illustration { position: relative; z-index: 1; width: min(72%, 720px); max-height: 72vh; object-fit: contain; margin-top: 110px; }
.auth-mask { position: absolute; z-index: 0; inset-inline: 0; inset-block-end: 0; width: 100%; max-height: 290px; }
.auth-form-panel { min-height: 100dvh; background: rgb(var(--v-theme-surface)); box-shadow: -8px 0 30px rgba(47, 43, 61, .06); }
.auth-form-card { background: transparent !important; }
@media (max-width: 959px) {
    .auth-brand { inset-block-start: 22px; inset-inline-start: 22px; }
    .auth-form-panel { padding: 88px 16px 24px; }
}
</style>
