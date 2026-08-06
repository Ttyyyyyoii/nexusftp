import { defineStore } from 'pinia'

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    theme: localStorage.getItem('theme') || 'light',
    locale: localStorage.getItem('locale') || 'en',
    sidebarCollapsed: false,
    isPremium: localStorage.getItem('isPremium') === 'true',
    transferSettings: {
      maxSimultaneous: 2, timeout: 30, retries: 0,
      maxFileSize: 256, passiveMode: true, transferMode: 'binary'
    },
    notifications: { transferComplete: true, connectionStatus: true, errors: true }
  }),

  getters: {
    isDark: (state) => state.theme === 'dark',
    planLimits: (state) => {
      if (state.isPremium) {
        return {
          maxSimultaneous: 10,
          maxFileSize: 512, // in MB
          maxSavedConnections: 999, // unlimited
          maxHistory: 999, // unlimited
          allowRetry: true,
          allowTimeoutChange: true,
          allowTransferModeChange: true
        }
      } else {
        return {
          maxSimultaneous: 2,
          maxFileSize: 256, // in MB
          maxSavedConnections: 2,
          maxHistory: 10,
          allowRetry: false,
          allowTimeoutChange: false,
          allowTransferModeChange: false
        }
      }
    }
  },

  actions: {
    setTheme(theme) {
      this.theme = theme
      localStorage.setItem('theme', theme)
      this.applyTheme()
    },
    toggleTheme() {
      this.theme = this.theme === 'light' ? 'dark' : 'light'
      localStorage.setItem('theme', this.theme)
      this.applyTheme()
    },
    applyTheme() {
      const root = document.documentElement
      if (this.theme === 'dark') root.classList.add('dark')
      else root.classList.remove('dark')
    },
    setLocale(locale) {
      this.locale = locale
      localStorage.setItem('locale', locale)
    },
    toggleSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed
    },
    updateTransferSettings(settings) {
      this.transferSettings = { ...this.transferSettings, ...settings }
    },
    updateNotifications(notifications) {
      this.notifications = { ...this.notifications, ...notifications }
    },
    activatePremium() {
      this.isPremium = true
      localStorage.setItem('isPremium', 'true')
    },
    deactivatePremium() {
      this.isPremium = false
      localStorage.setItem('isPremium', 'false')
      // Reset settings to free limits
      this.transferSettings.maxSimultaneous = Math.min(this.transferSettings.maxSimultaneous, 2)
      this.transferSettings.maxFileSize = Math.min(this.transferSettings.maxFileSize, 256)
      this.transferSettings.retries = 0
      this.transferSettings.timeout = 30
      this.transferSettings.transferMode = 'binary'
      this.persist()
    },
    persist() {
      localStorage.setItem('settings-store', JSON.stringify({
        theme: this.theme, locale: this.locale, sidebarCollapsed: this.sidebarCollapsed,
        transferSettings: this.transferSettings, notifications: this.notifications
      }))
    },
    restore() {
      const stored = localStorage.getItem('settings-store')
      if (stored) {
        const parsed = JSON.parse(stored)
        if (parsed.transferSettings) {
            // Apply free limits if not premium
            if (!this.isPremium) {
                parsed.transferSettings.maxSimultaneous = Math.min(parsed.transferSettings.maxSimultaneous || 2, 2)
                parsed.transferSettings.maxFileSize = Math.min(parsed.transferSettings.maxFileSize || 256, 256)
                parsed.transferSettings.retries = 0
                parsed.transferSettings.timeout = 30
                parsed.transferSettings.transferMode = 'binary'
            }
        }
        Object.assign(this, parsed)
      }
      this.applyTheme()
    }
  }
})
