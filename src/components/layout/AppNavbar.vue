<template>
  <nav class="fixed top-0 left-0 right-0 z-50 glass-panel border-b border-surface-200/50 dark:border-surface-700/50">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex items-center justify-between h-16">
        <div class="flex items-center gap-3">
          <svg width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0">
            <defs>
              <linearGradient id="navBg" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                <stop offset="0%" stop-color="#6366f1"/>
                <stop offset="100%" stop-color="#8b5cf6"/>
              </linearGradient>
            </defs>
            <rect width="32" height="32" rx="8" fill="url(#navBg)"/>
            <rect x="5" y="7" width="22" height="5" rx="2" fill="white" fill-opacity="0.3"/>
            <rect x="5" y="14" width="22" height="5" rx="2" fill="white" fill-opacity="0.2"/>
            <path d="M10 25 L10 19" stroke="white" stroke-width="2" stroke-linecap="round"/>
            <path d="M7 22 L10 19 L13 22" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <path d="M22 19 L22 25" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round"/>
            <path d="M19 22 L22 25 L25 22" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <circle cx="23" cy="9.5" r="1.5" fill="#34d399"/>
            <circle cx="23" cy="16.5" r="1.5" fill="#fbbf24"/>
          </svg>
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
            <Crown class="w-3.5 h-3.5" /> Premium
          </button>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
          <div class="relative hidden sm:block" ref="langMenuRef">
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
          <router-link to="/connect" class="btn-primary flex items-center gap-2 text-sm px-3 py-1.5 sm:px-4 sm:py-2">
            <LogIn class="w-4 h-4" />
            <span class="hidden sm:inline">{{ $t('nav.connect') }}</span>
          </router-link>
          <button @click="showMobileMenu = !showMobileMenu" class="md:hidden p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors">
            <Menu v-if="!showMobileMenu" class="w-6 h-6 text-surface-600 dark:text-surface-300" />
            <X v-else class="w-6 h-6 text-surface-600 dark:text-surface-300" />
          </button>
        </div>
      </div>
      
      <!-- Mobile Menu -->
      <transition name="slide-down">
        <div v-if="showMobileMenu" class="md:hidden py-4 border-t border-surface-200 dark:border-surface-700 bg-surface-0/95 dark:bg-surface-900/95 backdrop-blur-md absolute left-0 right-0 shadow-lg px-6 flex flex-col gap-2 rounded-b-2xl max-h-[80vh] overflow-y-auto">
          <router-link v-for="item in navItems" :key="item.path" :to="item.path" @click="showMobileMenu = false"
            class="px-4 py-3 rounded-xl text-sm font-medium transition-all"
            :class="$route.path === item.path ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400'">
            {{ $t(item.label) }}
          </router-link>
          <div class="h-px bg-surface-200 dark:bg-surface-700 my-2"></div>
          
          <button v-if="!settingsStore.isPremium" @click="showPremiumModal = true; showMobileMenu = false" class="w-full justify-center px-4 py-3 bg-gradient-to-r from-primary-500 to-purple-500 rounded-xl text-white text-sm font-semibold flex items-center gap-2 shadow">
            <Crown class="w-4 h-4" /> Obtenir Premium
          </button>
          
          <div class="flex items-center justify-between mt-2 px-4 py-2 bg-surface-100 dark:bg-surface-800 rounded-xl">
            <span class="text-sm font-medium text-surface-600 dark:text-surface-400">Langue</span>
            <div class="flex gap-2">
              <button v-for="lang in languages" :key="lang.code" @click="changeLanguage(lang.code)"
                class="px-2 py-1 rounded-md text-xs font-bold uppercase transition-colors"
                :class="settingsStore.locale === lang.code ? 'bg-primary-500 text-white' : 'text-surface-500 hover:bg-surface-200 dark:hover:bg-surface-700'">
                {{ lang.code }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </div>
    <PremiumModal :visible="showPremiumModal" @close="showPremiumModal = false" />
  </nav>
</template>

<script>
import { useSettingsStore } from '@/stores/settings'
import { HardDrive, LogIn, Sun, Moon, Star, Crown, Menu, X } from 'lucide-vue-next'
import PremiumModal from '@/components/PremiumModal.vue'

export default {
  name: 'AppNavbar',
  components: { HardDrive, LogIn, Sun, Moon, Star, Crown, Menu, X, PremiumModal },
  data() {
    return {
      settingsStore: useSettingsStore(),
      showLangMenu: false,
      showMobileMenu: false,
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
