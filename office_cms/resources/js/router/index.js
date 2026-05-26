import { createRouter, createWebHistory } from 'vue-router'
import { store } from '@/plugins/2.pinia'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Roles from '@/pages/apps/roles/Roles.vue'
import Users from '@/pages/apps/users/Users.vue'
import Dashboard from '@/pages/Dashboard.vue'
import ForgotPassword from '@/pages/auth/ForgotPassword.vue'
import Login from '@/pages/auth/Login.vue'
import Register from '@/pages/auth/Register.vue'
import ResetPassword from '@/pages/auth/ResetPassword.vue'
import VerifyEmail from '@/pages/auth/VerifyEmail.vue'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: {
        name: 'admin-dashboard',
      },
    },
    {
      path: '/',
      component: AuthLayout,
      children: [
        {
          path: 'login',
          name: 'login',
          component: Login,
          meta: {
            title: 'Login',
            middleware: 'guest',
          },
        },
        {
          path: 'register',
          name: 'register',
          component: Register,
          meta: {
            title: 'Register',
            middleware: 'guest',
          },
        },
        {
          path: 'forgot-password',
          name: 'forgot-password',
          component: ForgotPassword,
          meta: {
            title: 'Forgot Password',
            middleware: 'guest',
          },
        },
        {
          path: 'reset-password/:token',
          name: 'reset-password',
          component: ResetPassword,
          meta: {
            title: 'Reset Password',
            middleware: 'guest',
          },
        },
        {
          path: 'verify-email',
          name: 'verify-email',
          component: VerifyEmail,
          meta: {
            title: 'Verify Email',
            middleware: 'auth',
          },
        },
        {
          path: 'email/verify',
          name: 'verification-notice',
          component: VerifyEmail,
          meta: {
            title: 'Verify Email',
            middleware: 'auth',
          },
        },
      ],
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: {
        middleware: 'auth',
      },
      children: [
        {
          path: 'dashboard',
          name: 'admin-dashboard',
          component: Dashboard,
          meta: {
            title: 'Dashboard',
            permission: 'dashboard.view',
          },
        },
        {
          path: 'roles',
          name: 'apps-roles',
          component: Roles,
          meta: {
            title: 'Roles',
            permission: 'roles.view',
          },
        },
        {
          path: 'users',
          name: 'apps-users',
          component: Users,
          meta: {
            title: 'Users',
            permission: 'users.view',
          },
        },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: {
        name: 'admin-dashboard',
      },
    },
  ],
  scrollBehavior(to) {
    if (to.hash) {
      return { el: to.hash, behavior: 'smooth', top: 60 }
    }

    return { top: 0 }
  },
})

router.beforeEach(async to => {
  const authStore = useAuthStore(store)

  await authStore.hydrate()

  if (to.meta.middleware === 'guest' && authStore.isAuthenticated)
    return { name: 'admin-dashboard' }

  if (to.meta.middleware === 'auth' && !authStore.isAuthenticated) {
    return {
      name: 'login',
      query: to.fullPath !== '/login'
        ? { redirect: to.fullPath }
        : {},
    }
  }

  if (to.meta.permission && !authStore.can(to.meta.permission))
    return { name: 'login' }

  return true
})

router.afterEach(to => {
  document.title = to.meta?.title
    ? `${to.meta.title} - Office CMS`
    : 'Office CMS'
})

export default router
