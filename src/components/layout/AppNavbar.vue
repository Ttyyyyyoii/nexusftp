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
  </nav>
</template>

<script>
import { useSettingsStore } from '@/stores/settings'
import { HardDrive, LogIn, Sun, Moon } from 'lucide-vue-next'

export default {
  name: 'AppNavbar',
  components: { HardDrive, LogIn, Sun, Moon },
  data() {
    return {
      settingsStore: useSettingsStore(),
      showLangMenu: false,
      languages: [{ code: 'en', label: 'English' }, { code: 'fr', label: 'Français' }],
      navItems: [
        { path: '/', label: 'nav.home' },
        { path: '/docs', label: 'nav.docs' },
        { path: '/faq', label: 'nav.faq' },
        { path: '/contact', label: 'nav.contact' }
      ]
    }
  },
  mounted() { document.addEventListener('click', this.handleClickOutside) },
  beforeUnmount() { document.removeEventListener('click', this.handleClickOutside) },
  methods: {
    changeLanguage(code) { this.settingsStore.setLocale(code); this.$i18n.locale = code; this.showLangMenu = false },
    handleClickOutside(e) { if (this.$refs.langMenuRef && !this.$refs.langMenuRef.contains(e.target)) this.showLangMenu = false }
  }
}
</script>
