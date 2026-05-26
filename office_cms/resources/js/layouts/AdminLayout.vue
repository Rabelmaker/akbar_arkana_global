<script setup>
import navItems from '@/navigation/vertical'
import Footer from '@/layouts/components/Footer.vue'
import NavBarNotifications from '@/layouts/components/NavBarNotifications.vue'
import NavBarI18n from '@core/components/I18n.vue'
import NavbarThemeSwitcher from '@/layouts/components/NavbarThemeSwitcher.vue'
import UserProfile from '@/layouts/components/UserProfile.vue'
import { useAuthStore } from '@/stores/auth'
import { useConfigStore } from '@core/stores/config'
import { themeConfig } from '@themeConfig'
import { VerticalNavLayout } from '@layouts'

const isFallbackStateActive = ref(false)
const refLoadingIndicator = ref(null)
const configStore = useConfigStore()
const authStore = useAuthStore()
const verticalNavHeaderActionAnimationName = ref(null)
const filteredNavItems = computed(() => navItems.filter(item => !item.permission || authStore.can(item.permission)))

watch([isFallbackStateActive, refLoadingIndicator], () => {
  if (isFallbackStateActive.value && refLoadingIndicator.value)
    refLoadingIndicator.value.fallbackHandle()

  if (!isFallbackStateActive.value && refLoadingIndicator.value)
    refLoadingIndicator.value.resolveHandle()
}, { immediate: true })

watch([
  () => configStore.isVerticalNavCollapsed,
  () => configStore.isAppRTL,
], ([isCollapsed]) => {
  if (configStore.isAppRTL)
    verticalNavHeaderActionAnimationName.value = isCollapsed ? 'rotate-back-180' : 'rotate-180'
  else
    verticalNavHeaderActionAnimationName.value = isCollapsed ? 'rotate-180' : 'rotate-back-180'
}, { immediate: true })
</script>

<template>
  <VerticalNavLayout :nav-items="filteredNavItems">
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="d-flex h-100 align-center">
        <IconBtn
          id="vertical-nav-toggle-btn"
          class="ms-n2 d-lg-none"
          @click="toggleVerticalOverlayNavActive(true)"
        >
          <VIcon icon="ri-menu-line" />
        </IconBtn>

        <VSpacer />

        <NavBarI18n
          v-if="themeConfig.app.i18n.enable && themeConfig.app.i18n.langConfig?.length"
          :languages="themeConfig.app.i18n.langConfig"
        />
        <NavbarThemeSwitcher />
        <NavBarNotifications class="me-2" />
        <UserProfile class="ms-3" />
      </div>
    </template>

    <AppLoadingIndicator ref="refLoadingIndicator" />

    <RouterView v-slot="{ Component }">
      <Suspense
        :timeout="0"
        @fallback="isFallbackStateActive = true"
        @resolve="isFallbackStateActive = false"
      >
        <div class="pa-6 pa-md-8">
          <component :is="Component" />
        </div>
      </Suspense>
    </RouterView>

    <template #footer>
      <Footer />
    </template>
  </VerticalNavLayout>
</template>

<style lang="scss">
@keyframes rotate-180 {
  from { transform: rotate(0deg); }
  to { transform: rotate(180deg); }
}

@keyframes rotate-back-180 {
  from { transform: rotate(180deg); }
  to { transform: rotate(0deg); }
}

.layout-vertical-nav {
  .nav-header {
    .header-action {
      animation-duration: 0.35s;
      animation-fill-mode: forwards;
      animation-name: v-bind(verticalNavHeaderActionAnimationName);
      transform: rotate(0deg);
    }
  }
}
</style>
