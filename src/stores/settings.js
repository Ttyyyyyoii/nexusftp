import { defineStore } from 'pinia'

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    theme: localStorage.getItem('theme') || 'light',
    locale: localStorage.getItem('locale') || 'en',
    sidebarCollapsed: false,
    transferSettings: {
      maxSimultaneous: 3, timeout: 30, retries: 3,
      maxFileSize: 2048, passiveMode: true, transferMode: 'binary'
    },
    notifications: { transferComplete: true, connectionStatus: true, errors: true }
  }),

  getters: {
    isDark: (state) => state.theme === 'dark'
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
    persist() {
      localStorage.setItem('settings-store', JSON.stringify({
        theme: this.theme, locale: this.locale, sidebarCollapsed: this.sidebarCollapsed,
        transferSettings: this.transferSettings, notifications: this.notifications
      }))
    },
    restore() {
      const stored = localStorage.getItem('settings-store')
      if (stored) Object.assign(this, JSON.parse(stored))
      this.applyTheme()
    }
  }
})
