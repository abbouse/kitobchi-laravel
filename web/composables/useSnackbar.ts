import { ref, readonly } from 'vue'

export type SnackbarType = 'success' | 'error' | 'warning' | 'info'

export interface SnackbarItem {
  id: number
  message: string
  type: SnackbarType
  title?: string
  duration?: number
}

const snackbars = ref<SnackbarItem[]>([])
let counter = 0

export function useSnackbar() {
  function showSnackbar(
    message: string,
    type: SnackbarType = 'info',
    options?: { title?: string; duration?: number }
  ) {
    const id = ++counter
    const duration = options?.duration ?? 3500

    const item: SnackbarItem = {
      id,
      message,
      type,
      title: options?.title,
      duration
    }

    // Keep max 3 snackbars at a time
    if (snackbars.value.length >= 3) {
      snackbars.value.shift()
    }

    snackbars.value.push(item)

    if (duration > 0) {
      setTimeout(() => {
        removeSnackbar(id)
      }, duration)
    }

    return id
  }

  function removeSnackbar(id: number) {
    const idx = snackbars.value.findIndex(s => s.id === id)
    if (idx !== -1) {
      snackbars.value.splice(idx, 1)
    }
  }

  function showSuccess(message: string, title?: string) {
    return showSnackbar(message, 'success', { title })
  }

  function showError(message: string, title?: string) {
    return showSnackbar(message, 'error', { title })
  }

  function showWarning(message: string, title?: string) {
    return showSnackbar(message, 'warning', { title })
  }

  function showInfo(message: string, title?: string) {
    return showSnackbar(message, 'info', { title })
  }

  return {
    snackbars: readonly(snackbars),
    showSnackbar,
    removeSnackbar,
    showSuccess,
    showError,
    showWarning,
    showInfo
  }
}
