<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto p-6 overflow-auto">
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-surface-900 dark:text-white">{{ $t('settings.title') }}</h1>
        
        <!-- Premium Badge & Button -->
        <div class="flex items-center gap-3">
          <div v-if="settingsStore.isPremium" class="px-3 py-1.5 bg-gradient-to-r from-amber-200 to-amber-400 dark:from-amber-600 dark:to-amber-500 rounded-lg text-amber-900 dark:text-amber-50 text-xs font-bold uppercase tracking-wider flex items-center gap-1 shadow-glow">
            <Star class="w-4 h-4" /> Premium Actif
          </div>
          <button v-else @click="showPremiumModal = true" class="px-4 py-2 bg-gradient-to-r from-primary-500 to-purple-500 hover:from-primary-600 hover:to-purple-600 rounded-xl text-white text-sm font-semibold flex items-center gap-2 shadow-lg transition-all hover:scale-105">
            <Sparkles class="w-4 h-4" /> Passer en Premium
          </button>
        </div>
      </div>
      
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
        <div class="glass-panel rounded-2xl p-6 relative overflow-hidden">
          <h2 class="text-lg font-semibold text-surface-900 dark:text-white mb-4 flex items-center gap-2"><ArrowUpDown class="w-5 h-5 text-primary-500" />{{ $t('settings.transfers') }}</h2>
          <div class="space-y-4">
            <div v-for="field in transferFields" :key="field.key" class="flex items-center justify-between" :class="{'opacity-50': field.premiumOnly && !settingsStore.isPremium}">
              <div class="flex items-center gap-2">
                <p class="font-medium text-surface-800 dark:text-surface-200">{{ $t(field.label) }}</p>
                <Lock v-if="field.premiumOnly && !settingsStore.isPremium" class="w-3.5 h-3.5 text-surface-400" />
              </div>
              <div v-if="field.readonly" class="font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-3 py-1 rounded-lg">
                {{ field.value }}{{ field.key === 'maxFileSize' ? ' MB' : '' }}
              </div>
              <input v-else v-model.number="transferSettings[field.key]" type="number" :min="field.min" :max="field.max" :disabled="field.premiumOnly && !settingsStore.isPremium" class="w-24 px-3 py-2 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 text-center dark:text-surface-200 disabled:bg-surface-100 disabled:dark:bg-surface-900" />
            </div>
          </div>
        </div>
        <div class="glass-panel rounded-2xl p-6 relative">
          <h2 class="text-lg font-semibold text-surface-900 dark:text-white mb-4 flex items-center gap-2"><Network class="w-5 h-5 text-primary-500" />{{ $t('settings.network') }}</h2>
          <div class="space-y-4">
            <label class="flex items-center justify-between cursor-pointer">
              <span class="font-medium text-surface-800 dark:text-surface-200">{{ $t('settings.passiveMode') }}</span>
              <input v-model="transferSettings.passiveMode" type="checkbox" class="w-5 h-5 rounded border-surface-300 text-primary-600 focus:ring-primary-500" />
            </label>
            <div class="flex items-center justify-between" :class="{'opacity-50': !settingsStore.planLimits.allowTransferModeChange}">
              <div class="flex items-center gap-2">
                <span class="font-medium text-surface-800 dark:text-surface-200">{{ $t('settings.transferMode') }}</span>
                <Lock v-if="!settingsStore.planLimits.allowTransferModeChange" class="w-3.5 h-3.5 text-surface-400" />
              </div>
              <div class="flex gap-2">
                <button v-for="mode in ['binary', 'ascii']" :key="mode" @click="settingsStore.planLimits.allowTransferModeChange ? transferSettings.transferMode = mode : null" :disabled="!settingsStore.planLimits.allowTransferModeChange" class="px-4 py-2 rounded-xl text-sm border-2 transition-all capitalize" :class="transferSettings.transferMode === mode ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-600' : 'border-surface-200 dark:border-surface-700 text-surface-600 disabled:opacity-50'">{{ mode }}</button>
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
        <div class="glass-panel rounded-2xl p-6 relative">
          <h2 class="text-lg font-semibold text-surface-900 dark:text-white mb-4 flex items-center gap-2"><Image class="w-5 h-5 text-primary-500" />Optimisation d'Images</h2>
          <div class="space-y-4">
            <div class="flex items-center justify-between" :class="{'opacity-50': !settingsStore.isPremium}">
              <div class="flex items-center gap-2">
                <span class="font-medium text-surface-800 dark:text-surface-200">Autoriser l'écrasement des images (In-Place)</span>
                <Lock v-if="!settingsStore.isPremium" class="w-3.5 h-3.5 text-surface-400" />
              </div>
              <input v-model="allowImageOptimization" type="checkbox" :disabled="!settingsStore.isPremium" class="w-5 h-5 rounded border-surface-300 text-primary-600 focus:ring-primary-500 disabled:opacity-50" />
            </div>
            <p class="text-xs text-surface-500 dark:text-surface-400">Si activé, l'optimisation GD va réduire le poids de vos images JPEG/PNG sans modifier le nom du fichier. Le fichier original sera écrasé.</p>
          </div>
        </div>
        <div class="flex justify-end gap-3">
          <button @click="resetSettings" class="btn-secondary">{{ $t('common.reset') }}</button>
          <button @click="saveSettings" class="btn-primary">{{ $t('common.save') }}</button>
        </div>
      </div>
      
      <PremiumModal :visible="showPremiumModal" @close="showPremiumModal = false" />
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import PremiumModal from '@/components/PremiumModal.vue'
import { useSettingsStore } from '@/stores/settings'
import { Palette, Sun, Moon, ArrowUpDown, Network, Bell, Star, Sparkles, Lock, Image } from 'lucide-vue-next'
export default {
  name: 'SettingsPage',
  components: { AppLayout, PremiumModal, Palette, Sun, Moon, ArrowUpDown, Network, Bell, Star, Sparkles, Lock, Image },
  data() {
    return {
      settingsStore: useSettingsStore(),
      locale: 'en',
      transferSettings: { maxSimultaneous: 2, timeout: 30, retries: 0, maxFileSize: 256, passiveMode: true, transferMode: 'binary' },
      notifications: { transferComplete: true, connectionStatus: true, errors: true },
      allowImageOptimization: false,
      showPremiumModal: false
    }
  },
  computed: {
    transferFields() {
      const limits = this.settingsStore.planLimits;
      return [
        { key: 'maxSimultaneous', label: 'settings.maxConnections', readonly: true, value: limits.maxSimultaneous, premiumOnly: false },
        { key: 'maxFileSize', label: 'settings.maxFileSize', readonly: true, value: limits.maxFileSize, premiumOnly: false },
        { key: 'timeout', label: 'settings.timeout', min: 5, max: 120, premiumOnly: !limits.allowTimeoutChange },
        { key: 'retries', label: 'settings.retries', min: 0, max: limits.maxRetries, premiumOnly: !limits.allowRetry }
      ]
    }
  },
  mounted() {
    this.locale = this.settingsStore.locale
    this.transferSettings = { ...this.settingsStore.transferSettings }
    this.notifications = { ...this.settingsStore.notifications }
    this.allowImageOptimization = this.settingsStore.allowImageOptimization
  },
  watch: {
    notifications: {
      deep: true,
      handler(newVal) {
        if (newVal.transferComplete || newVal.connectionStatus || newVal.errors) {
          if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
            Notification.requestPermission();
          }
        }
      }
    }
  },
  methods: {
    changeLanguage() { this.settingsStore.setLocale(this.locale); this.$i18n.locale = this.locale },
    saveSettings() { 
      // Ensure limits are respected when saving
      const limits = this.settingsStore.planLimits;
      if (this.transferSettings.maxSimultaneous > limits.maxSimultaneous) this.transferSettings.maxSimultaneous = limits.maxSimultaneous;
      if (this.transferSettings.maxFileSize > limits.maxFileSize) this.transferSettings.maxFileSize = limits.maxFileSize;
      
      if (!limits.allowRetry) {
        this.transferSettings.retries = 0;
      } else {
        if (this.transferSettings.retries > limits.maxRetries) this.transferSettings.retries = limits.maxRetries;
        if (this.transferSettings.retries < 0) this.transferSettings.retries = 0;
      }

      if (!limits.allowTimeoutChange) {
        this.transferSettings.timeout = 30;
      } else {
        if (this.transferSettings.timeout > 120) this.transferSettings.timeout = 120;
        if (this.transferSettings.timeout < 5) this.transferSettings.timeout = 5;
      }

      if (!limits.allowTransferModeChange) this.transferSettings.transferMode = 'binary';
      
      this.settingsStore.updateTransferSettings(this.transferSettings); 
      this.settingsStore.updateNotifications(this.notifications); 
      this.settingsStore.setAllowImageOptimization(this.allowImageOptimization);
      this.settingsStore.persist(); 
      this.showToast('Settings saved', 'success') 
    },
    resetSettings() { 
      this.transferSettings = { maxSimultaneous: 2, timeout: 30, retries: 0, maxFileSize: 256, passiveMode: true, transferMode: 'binary' }; 
      this.notifications = { transferComplete: true, connectionStatus: true, errors: true };
      this.allowImageOptimization = false;
    },
    showToast(title, type) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { title, type } })) }
  }
}
</script>
