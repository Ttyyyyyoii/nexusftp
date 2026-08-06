<template>
  <nav class="fixed top-0 left-0 right-0 z-50 glass-panel border-b border-surface-200/50 dark:border-surface-700/50">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex items-center justify-between h-16">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-glow">
            <HardDrive class="w-5 h-5 text-white" />
          </div>
          <span class="text-xl font-bold bg-gradient-to-r from-surface-900 to-surface-600 dark:from-white dark:to-surface-300 bg-clip-text text-transparent">
            {{ $t('app.name') }}
          </span>
        </div>
        <div class="hidden md:flex items-center gap-1">
          <router-link v-for="item in navItems" :key="item.path" :to="item.path"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
            :class="$route.path === item.path ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800'">
            {{ $t(item.label) }}
          </router-link>
          <!-- Premium Link -->
          <div v-if="settingsStore.isPremium" class="px-3 py-1 ml-2 bg-gradient-to-r from-amber-200 to-amber-400 dark:from-amber-600 dark:to-amber-500 rounded-lg text-amber-900 dark:text-amber-50 text-xs font-bold uppercase tracking-wider flex items-center gap-1 shadow-glow cursor-default">
            <Star class="w-3 h-3" /> Premium
          </div>
          <button v-else @click="showPremiumModal = true" class="px-3 py-1.5 ml-2 bg-gradient-to-r from-primary-500 to-purple-500 hover:from-primary-600 hover:to-purple-600 rounded-lg text-white text-xs font-semibold flex items-center gap-1.5 shadow transition-all hover:scale-105">
            <Sparkles class="w-3.5 h-3.5" /> Premium
          </button>
        </div>
        <div class="flex items-center gap-3">
          <div class="relative" ref="langMenuRef">
            <button @click="showLangMenu = !showLangMenu" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-sm font-medium text-surface-600 dark:text-surface-400 transition-colors">
              {{ settingsStore.locale.toUpperCase() }}
            </button>
            <div v-if="showLangMenu" class="absolute right-0 top-full mt-1 w-32 bg-white dark:bg-surface-800 rounded-xl shadow-premium border border-surface-200 dark:border-surface-700 overflow-hidden z-50">
              <button v-for="lang in languages" :key="lang.code" @click="changeLanguage(lang.code)"
                class="w-full px-4 py-2.5 text-sm text-left hover:bg-surface-50 dark:hover:bg-surface-700 transition-colors"
                :class="settingsStore.locale === lang.code ? 'text-primary-600 font-medium' : 'text-surface-700 dark:text-surface-300'">
                {{ lang.label }}
              </button>
            </div>
          </div>
          <button @click="settingsStore.toggleTheme" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors">
            <Sun v-if="settingsStore.isDark" class="w-5 h-5 text-amber-400" />
            <Moon v-else class="w-5 h-5 text-surface-500" />
          </button>
          <router-link to="/connect" class="btn-primary flex items-center gap-2 text-sm">
            <LogIn class="w-4 h-4" />
            <span class="hidden sm:inline">{{ $t('nav.connect') }}</span>
          </router-link>
        </div>
      </div>
    </div>
    <PremiumModal :visible="showPremiumModal" @close="showPremiumModal = false" />
  </nav>
</template>

<script>
import { useSettingsStore } from '@/stores/settings'
import { HardDrive, LogIn, Sun, Moon, Star, Sparkles } from 'lucide-vue-next'
import PremiumModal from '@/components/PremiumModal.vue'

export default {
  name: 'AppNavbar',
  components: { HardDrive, LogIn, Sun, Moon, Star, Sparkles, PremiumModal },
  data() {
    return {
      settingsStore: useSettingsStore(),
      showLangMenu: false,
      showPremiumModal: false,
      languages: [{ code: 'en', label: 'English' }, { code: 'fr', label: 'Français' }],
      navItems: [
        { path: '/', label: 'nav.home' },
        { path: '/docs', label: 'nav.docs' },
        { path: '/faq', label: 'nav.faq' },
        { path: '/contact', label: 'nav.contact' }
      ]
    }
  },
  mounted() { 
    document.addEventListener('click', this.handleClickOutside)
    window.addEventListener('show-premium-modal', this.handleShowPremiumModal)
  },
  beforeUnmount() { 
    document.removeEventListener('click', this.handleClickOutside)
    window.removeEventListener('show-premium-modal', this.handleShowPremiumModal)
  },
  methods: {
    handleShowPremiumModal() {
      this.showPremiumModal = true
    },
    changeLanguage(code) { this.settingsStore.setLocale(code); this.$i18n.locale = code; this.showLangMenu = false },
    handleClickOutside(e) { if (this.$refs.langMenuRef && !this.$refs.langMenuRef.contains(e.target)) this.showLangMenu = false }
  }
}
</script>
