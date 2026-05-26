<script setup>
import { useGenerateImageVariant } from '@/@core/composable/useGenerateImageVariant'
import { useAuthStore } from '@/stores/auth'
import authV2ResetPasswordIllustrationBorderedDark from '@images/pages/auth-v2-reset-password-illustration-bordered-dark.png'
import authV2ResetPasswordIllustrationBorderedLight from '@images/pages/auth-v2-reset-password-illustration-bordered-light.png'
import authV2ResetPasswordIllustrationDark from '@images/pages/auth-v2-reset-password-illustration-dark.png'
import authV2ResetPasswordIllustrationLight from '@images/pages/auth-v2-reset-password-illustration-light.png'
import authV2ResetPasswordMaskDark from '@images/pages/auth-v2-reset-password-mask-dark.png'
import authV2ResetPasswordMaskLight from '@images/pages/auth-v2-reset-password-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({
  password: '',
  password_confirmation: '',
})

const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const email = computed(() => `${route.query.email ?? ''}`)
const token = computed(() => `${route.params.token ?? ''}`)

const authV2ResetPasswordMask = useGenerateImageVariant(authV2ResetPasswordMaskLight, authV2ResetPasswordMaskDark)
const authV2ResetPasswordIllustration = useGenerateImageVariant(
  authV2ResetPasswordIllustrationLight,
  authV2ResetPasswordIllustrationDark,
  authV2ResetPasswordIllustrationBorderedLight,
  authV2ResetPasswordIllustrationBorderedDark,
  true,
)

const firstError = field => authStore.errors[field]?.[0] ?? ''
const hasResetContext = computed(() => Boolean(email.value && token.value))

const submit = async () => {
  if (!hasResetContext.value)
    return

  try {
    await authStore.resetPassword({
      token: token.value,
      email: email.value,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })

    await router.replace({ name: 'login', query: { reset: '1' } })
  }
  catch {
    // Validation state is exposed through the auth store.
  }
}

onBeforeUnmount(() => {
  authStore.clearErrors()
  authStore.clearStatus()
})
</script>

<template>
  <RouterLink :to="{ name: 'login' }">
    <div class="app-logo auth-logo">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="app-logo-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </RouterLink>

  <VRow
    no-gutters
    class="auth-wrapper"
  >
    <VCol
      md="8"
      class="d-none d-md-flex align-center justify-center position-relative"
    >
      <div class="d-flex align-center justify-center pa-10">
        <img
          :src="authV2ResetPasswordIllustration"
          class="auth-illustration w-100"
          alt="auth-illustration"
        >
      </div>
      <VImg
        :src="authV2ResetPasswordMask"
        class="d-none d-md-flex auth-footer-mask"
        alt="auth-mask"
      />
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="auth-card-v2 d-flex align-center justify-center"
      style="background-color: rgb(var(--v-theme-surface));"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-5 pa-lg-7"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            Reset Password 🔒
          </h4>
          <p class="mb-0">
            Set a new password for <span class="text-high-emphasis">{{ email || 'your account' }}</span>
          </p>
        </VCardText>

        <VCardText>
          <VAlert
            v-if="!hasResetContext"
            type="warning"
            variant="tonal"
            class="mb-4"
          >
            Reset link is incomplete. Please open the full reset password link from your email.
          </VAlert>

          <VForm @submit.prevent="submit">
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="form.password"
                  autofocus
                  label="New Password"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  :error="Boolean(firstError('password'))"
                  :error-messages="firstError('password')"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="form.password_confirmation"
                  label="Confirm Password"
                  placeholder="············"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  :error="Boolean(firstError('password'))"
                  :error-messages="firstError('password')"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :loading="authStore.loading"
                  :disabled="authStore.loading || !hasResetContext"
                >
                  Set New Password
                </VBtn>
              </VCol>

              <VCol cols="12">
                <RouterLink
                  class="d-flex align-center justify-center"
                  :to="{ name: 'login' }"
                >
                  <VIcon
                    start
                    size="20"
                    icon="ri-arrow-left-s-line"
                    class="flip-in-rtl"
                  />
                  <span class="text-base">Back to login</span>
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
