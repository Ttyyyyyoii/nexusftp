<template>
  <header class="h-14 bg-surface-0 dark:bg-surface-900 border-b border-surface-200 dark:border-surface-800 flex items-center px-4 gap-3 shrink-0">
    <div class="flex items-center gap-3">
      <button @click="$emit('toggle-sidebar')" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors">
        <Menu class="w-5 h-5 text-surface-600 dark:text-surface-400" />
      </button>
      <router-link to="/" class="flex items-center gap-2">
        <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0">
          <defs>
            <linearGradient id="tbBg" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="#6366f1"/>
              <stop offset="100%" stop-color="#8b5cf6"/>
            </linearGradient>
          </defs>
          <rect width="32" height="32" rx="8" fill="url(#tbBg)"/>
          <rect x="5" y="7" width="22" height="5" rx="2" fill="white" fill-opacity="0.3"/>
          <rect x="5" y="14" width="22" height="5" rx="2" fill="white" fill-opacity="0.2"/>
          <path d="M10 25 L10 19" stroke="white" stroke-width="2" stroke-linecap="round"/>
          <path d="M7 22 L10 19 L13 22" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          <path d="M22 19 L22 25" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round"/>
          <path d="M19 22 L22 25 L25 22" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          <circle cx="23" cy="9.5" r="1.5" fill="#34d399"/>
          <circle cx="23" cy="16.5" r="1.5" fill="#fbbf24"/>
        </svg>
        <span class="font-bold text-surface-900 dark:text-white hidden lg:inline">{{ $t('app.name') }}</span>
      </router-link>
    </div>
    <div class="flex-1 mx-4 flex items-center gap-1 overflow-x-auto no-scrollbar">
      <div v-for="session in connectionStore.sessions" :key="session.sessionId"
        @click="connectionStore.switchSession(session.sessionId); $router.push('/files')"
        class="group flex items-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-all border"
        :class="connectionStore.activeSessionId === session.sessionId ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800 text-primary-700 dark:text-primary-300' : 'border-transparent hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-600 dark:text-surface-400'">
        <div class="w-2 h-2 rounded-full" :class="connectionStore.activeSessionId === session.sessionId ? 'bg-primary-500 animate-pulse' : 'bg-surface-400'"></div>
        <span class="text-xs font-medium whitespace-nowrap">{{ session.connectionInfo.username }}@{{ session.connectionInfo.host }}</span>
        <button @click.stop="connectionStore.disconnect(session.sessionId)" class="ml-1 opacity-0 group-hover:opacity-100 hover:text-rose-500 transition-opacity">
          <X class="w-3 h-3" />
        </button>
      </div>
      <router-link to="/connect" class="p-1.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500 transition-colors shrink-0 ml-1" title="Nouvelle connexion">
        <Plus class="w-4 h-4" />
      </router-link>
    </div>
    <div class="flex items-center gap-2">
      <button @click="settingsStore.toggleTheme" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors">
        <Sun v-if="settingsStore.isDark" class="w-5 h-5 text-amber-400" />
        <Moon v-else class="w-5 h-5 text-surface-500" />
      </button>
      <div class="relative" ref="langMenuRef">
        <button @click="showLangMenu = !showLangMenu" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-sm font-medium text-surface-600 dark:text-surface-400">
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
    </div>
  </header>
</template>

<script>
import { useSettingsStore } from '@/stores/settings'
import { useConnectionStore } from '@/stores/connection'
import { Menu, Search, Sun, Moon, X, Plus } from 'lucide-vue-next'

export default {
  name: 'AppToolbar',
  components: { Menu, Search, Sun, Moon, X, Plus },
  emits: ['toggle-sidebar'],
  data() {
    return {
      settingsStore: useSettingsStore(),
      connectionStore: useConnectionStore(),
      searchQuery: '',
      showLangMenu: false,
      languages: [{ code: 'en', label: 'English' }, { code: 'fr', label: 'Francais' }]
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
