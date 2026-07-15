<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto p-6 overflow-auto">
      <h1 class="text-2xl font-bold text-surface-900 dark:text-white mb-8">{{ $t('settings.title') }}</h1>
      <div class="space-y-6">
        <div class="glass-panel rounded-2xl p-6">
          <h2 class="text-lg font-semibold text-surface-900 dark:text-white mb-4 flex items-center gap-2"><Palette class="w-5 h-5 text-primary-500" />{{ $t('settings.appearance') }}</h2>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="font-medium text-surface-800 dark:text-surface-200">{{ $t('settings.theme') }}</p>
                <p class="text-sm text-surface-500">Choose your preferred theme</p>
              </div>
              <div class="flex gap-2">
                <button @click="settingsStore.setTheme('light')" class="flex items-center gap-2 px-4 py-2 rounded-xl border-2 transition-all" :class="settingsStore.theme === 'light' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-surface-200 dark:border-surface-700'"><Sun class="w-4 h-4" />{{ $t('settings.light') }}</button>
                <button @click="settingsStore.setTheme('dark')" class="flex items-center gap-2 px-4 py-2 rounded-xl border-2 transition-all" :class="settingsStore.theme === 'dark' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-surface-200 dark:border-surface-700'"><Moon class="w-4 h-4" />{{ $t('settings.dark') }}</button>
              </div>
            </div>
            <div class="flex items-center justify-between">
              <div>
                <p class="font-medium text-surface-800 dark:text-surface-200">{{ $t('settings.language') }}</p>
                <p class="text-sm text-surface-500">Select interface language</p>
              </div>
              <select v-model="locale" @change="changeLanguage" class="px-4 py-2 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 text-sm dark:text-surface-200">
                <option value="en">English</option>
                <option value="fr">Francais</option>
              </select>
            </div>
          </div>
        </div>
        <div class="glass-panel rounded-2xl p-6">
          <h2 class="text-lg font-semibold text-surface-900 dark:text-white mb-4 flex items-center gap-2"><ArrowUpDown class="w-5 h-5 text-primary-500" />{{ $t('settings.transfers') }}</h2>
          <div class="space-y-4">
            <div v-for="field in transferFields" :key="field.key" class="flex items-center justify-between">
              <p class="font-medium text-surface-800 dark:text-surface-200">{{ $t(field.label) }}</p>
              <input v-model.number="transferSettings[field.key]" type="number" :min="field.min" :max="field.max" class="w-24 px-3 py-2 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 text-center dark:text-surface-200" />
            </div>
          </div>
        </div>
        <div class="glass-panel rounded-2xl p-6">
          <h2 class="text-lg font-semibold text-surface-900 dark:text-white mb-4 flex items-center gap-2"><Network class="w-5 h-5 text-primary-500" />{{ $t('settings.network') }}</h2>
          <div class="space-y-4">
            <label class="flex items-center justify-between cursor-pointer">
              <span class="font-medium text-surface-800 dark:text-surface-200">{{ $t('settings.passiveMode') }}</span>
              <input v-model="transferSettings.passiveMode" type="checkbox" class="w-5 h-5 rounded border-surface-300 text-primary-600 focus:ring-primary-500" />
            </label>
            <div class="flex items-center justify-between">
              <span class="font-medium text-surface-800 dark:text-surface-200">{{ $t('settings.transferMode') }}</span>
              <div class="flex gap-2">
                <button v-for="mode in ['binary', 'ascii']" :key="mode" @click="transferSettings.transferMode = mode" class="px-4 py-2 rounded-xl text-sm border-2 transition-all capitalize" :class="transferSettings.transferMode === mode ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-600' : 'border-surface-200 dark:border-surface-700 text-surface-600'">{{ mode }}</button>
              </div>
            </div>
          </div>
        </div>
        <div class="glass-panel rounded-2xl p-6">
          <h2 class="text-lg font-semibold text-surface-900 dark:text-white mb-4 flex items-center gap-2"><Bell class="w-5 h-5 text-primary-500" />{{ $t('settings.notifications') }}</h2>
          <div class="space-y-4">
            <label v-for="key in ['transferComplete', 'connectionStatus', 'errors']" :key="key" class="flex items-center justify-between cursor-pointer">
              <span class="font-medium text-surface-800 dark:text-surface-200">{{ $t(`settings.${key}`) }}</span>
              <input v-model="notifications[key]" type="checkbox" class="w-5 h-5 rounded border-surface-300 text-primary-600 focus:ring-primary-500" />
            </label>
          </div>
        </div>
        <div class="flex justify-end gap-3">
          <button @click="resetSettings" class="btn-secondary">{{ $t('common.reset') }}</button>
          <button @click="saveSettings" class="btn-primary">{{ $t('common.save') }}</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { useSettingsStore } from '@/stores/settings'
import { Palette, Sun, Moon, ArrowUpDown, Network, Bell } from 'lucide-vue-next'
export default {
  name: 'SettingsPage',
  components: { AppLayout, Palette, Sun, Moon, ArrowUpDown, Network, Bell },
  data() {
    return {
      settingsStore: useSettingsStore(),
      locale: 'en',
      transferSettings: { maxSimultaneous: 3, timeout: 30, retries: 3, maxFileSize: 2048, passiveMode: true, transferMode: 'binary' },
      notifications: { transferComplete: true, connectionStatus: true, errors: true },
      transferFields: [
        { key: 'maxSimultaneous', label: 'settings.maxConnections', min: 1, max: 10 },
        { key: 'timeout', label: 'settings.timeout', min: 5, max: 120 },
        { key: 'retries', label: 'settings.retries', min: 0, max: 10 },
        { key: 'maxFileSize', label: 'settings.maxFileSize', min: 1, max: 10000 }
      ]
    }
  },
  mounted() {
    this.locale = this.settingsStore.locale
    this.transferSettings = { ...this.settingsStore.transferSettings }
    this.notifications = { ...this.settingsStore.notifications }
  },
  methods: {
    changeLanguage() { this.settingsStore.setLocale(this.locale); this.$i18n.locale = this.locale },
    saveSettings() { this.settingsStore.updateTransferSettings(this.transferSettings); this.settingsStore.updateNotifications(this.notifications); this.settingsStore.persist(); this.showToast('Settings saved', 'success') },
    resetSettings() { this.transferSettings = { maxSimultaneous: 3, timeout: 30, retries: 3, maxFileSize: 2048, passiveMode: true, transferMode: 'binary' }; this.notifications = { transferComplete: true, connectionStatus: true, errors: true } },
    showToast(title, type) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { title, type } })) }
  }
}
</script>
