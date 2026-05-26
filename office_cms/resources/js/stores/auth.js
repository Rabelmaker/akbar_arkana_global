import axios from '@/lib/http'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const initialized = ref(false)
  const loading = ref(false)
  const errors = ref({})
  const statusMessage = ref('')
  const redirectTo = ref('/admin/dashboard')

  const isAuthenticated = computed(() => Boolean(user.value))
  const requiresEmailVerification = computed(() => isAuthenticated.value && !user.value?.email_verified_at)
  const permissions = computed(() => user.value?.permissions ?? [])
  const roles = computed(() => user.value?.roles ?? [])

  const normalizeErrors = error => {
    if (error?.response?.status === 422)
      return error.response.data?.errors ?? {}

    if (error?.response?.status === 429)
      return { email: ['Too many login attempts. Please try again later.'] }

    if (error?.response?.data?.message)
      return { email: [error.response.data.message] }

    return { email: ['Unable to process your request right now.'] }
  }

  const clearErrors = () => {
    errors.value = {}
  }

  const clearStatus = () => {
    statusMessage.value = ''
  }

  const setRedirectTo = path => {
    redirectTo.value = path || '/admin/dashboard'
  }

  const can = permission => permissions.value.includes(permission)

  const hasRole = role => roles.value.includes(role)

  const ensureCsrfCookie = async () => {
    await axios.get('/sanctum/csrf-cookie')
  }

  const fetchUser = async () => {
    try {
      const { data } = await axios.get('/api/user')
      user.value = data
    }
    catch (error) {
      if ([401, 419].includes(error?.response?.status)) {
        user.value = null
        return null
      }

      throw error
    }
    finally {
      initialized.value = true
    }

    return user.value
  }

  const hydrate = async () => {
    if (initialized.value)
      return user.value

    return fetchUser()
  }

  const login = async payload => {
    loading.value = true
    clearErrors()
    clearStatus()

    try {
      await ensureCsrfCookie()
      await axios.post('/login', payload)
      await fetchUser()
    }
    catch (error) {
      errors.value = normalizeErrors(error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  const register = async payload => {
    loading.value = true
    clearErrors()
    clearStatus()

    try {
      await ensureCsrfCookie()
      await axios.post('/register', payload)
      await fetchUser()
    }
    catch (error) {
      errors.value = normalizeErrors(error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  const forgotPassword = async payload => {
    loading.value = true
    clearErrors()
    clearStatus()

    try {
      await ensureCsrfCookie()
      const { data } = await axios.post('/forgot-password', payload)
      statusMessage.value = data?.status ?? 'Password reset link sent.'
    }
    catch (error) {
      errors.value = normalizeErrors(error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  const resetPassword = async payload => {
    loading.value = true
    clearErrors()
    clearStatus()

    try {
      await ensureCsrfCookie()
      const { data } = await axios.post('/reset-password', payload)
      statusMessage.value = data?.status ?? 'Password has been reset.'
    }
    catch (error) {
      errors.value = normalizeErrors(error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  const resendVerificationEmail = async () => {
    loading.value = true
    clearErrors()
    clearStatus()

    try {
      await ensureCsrfCookie()
      const { data } = await axios.post('/email/verification-notification')

      statusMessage.value = data?.status === 'already-verified'
        ? 'Your email address is already verified.'
        : 'A new verification link has been sent to your email address.'
    }
    catch (error) {
      errors.value = normalizeErrors(error)
      throw error
    }
    finally {
      loading.value = false
    }
  }

  const logout = async () => {
    loading.value = true

    try {
      await axios.post('/logout')
    }
    finally {
      user.value = null
      clearErrors()
      clearStatus()
      loading.value = false
      initialized.value = true
    }
  }

  return {
    user,
    initialized,
    loading,
    errors,
    statusMessage,
    redirectTo,
    isAuthenticated,
    requiresEmailVerification,
    permissions,
    roles,
    clearErrors,
    clearStatus,
    setRedirectTo,
    can,
    hasRole,
    ensureCsrfCookie,
    fetchUser,
    hydrate,
    login,
    register,
    forgotPassword,
    resetPassword,
    resendVerificationEmail,
    logout,
  }
})
