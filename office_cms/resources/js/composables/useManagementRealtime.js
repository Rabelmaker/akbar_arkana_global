const MANAGEMENT_EVENT = 'office-cms:management-updated'

export const emitManagementUpdated = detail => {
  if (typeof window === 'undefined')
    return

  window.dispatchEvent(new CustomEvent(MANAGEMENT_EVENT, {
    detail: {
      timestamp: Date.now(),
      ...detail,
    },
  }))
}

export const useManagementUpdateListener = callback => {
  const handler = event => {
    callback(event.detail ?? {})
  }

  onMounted(() => {
    window.addEventListener(MANAGEMENT_EVENT, handler)
  })

  onBeforeUnmount(() => {
    window.removeEventListener(MANAGEMENT_EVENT, handler)
  })
}
