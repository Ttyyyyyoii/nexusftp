<template>
  <aside class="w-64 bg-surface-50 dark:bg-surface-900 border-r border-surface-200 dark:border-surface-800 flex flex-col shrink-0">
    <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
      <router-link v-for="item in menuItems" :key="item.path" :to="item.path"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group"
        :class="$route.path === item.path ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800'">
        <component :is="item.icon" class="w-5 h-5 transition-transform group-hover:scale-110" :class="$route.path === item.path ? 'text-primary-500' : ''" />
        <span>{{ $t(item.label) }}</span>
      </router-link>
      <div v-if="favorites.length > 0" class="pt-4 mt-4 border-t border-surface-200 dark:border-surface-800">
        <p class="px-3 text-xs font-semibold text-surface-400 dark:text-surface-500 uppercase tracking-wider mb-2">{{ $t('favorites.title') }}</p>
        <button v-for="fav in favorites.slice(0, 5)" :key="fav.id" @click="quickConnect(fav)"
          class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800 transition-all text-left">
          <Star class="w-4 h-4 text-amber-500" />
          <span class="truncate">{{ fav.label || fav.host }}</span>
        </button>
      </div>
    </nav>
    <div class="p-3 border-t border-surface-200 dark:border-surface-800">
      <div v-if="connectionStore.isConnected" class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
        <div class="flex items-center gap-2 mb-1">
          <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
          <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">{{ $t('statusbar.connected') }}</span>
        </div>
        <p class="text-xs text-emerald-600 dark:text-emerald-500 truncate">{{ connectionStore.connectionLabel }}</p>
      </div>
      <div v-else class="p-3 rounded-xl bg-surface-100 dark:bg-surface-800 text-center">
        <p class="text-xs text-surface-500 dark:text-surface-400 mb-2">No active connection</p>
        <router-link to="/connect" class="text-xs text-primary-600 hover:text-primary-700 font-medium">Connect to server</router-link>
      </div>
    </div>
  </aside>
</template>

<script>
import { useConnectionStore } from '@/stores/connection'
import { Home, Plug, FolderOpen, Clock, Settings, ScrollText, Star, Heart, LayoutDashboard, TerminalSquare, ShieldCheck, Images, Activity, ArrowLeftRight } from 'lucide-vue-next'

export default {
  name: 'AppSidebar',
  components: { Home, Plug, FolderOpen, Clock, Settings, ScrollText, Star, Heart, LayoutDashboard, TerminalSquare, ShieldCheck, Images, Activity, ArrowLeftRight },
  data() {
    return {
      connectionStore: useConnectionStore(),
      menuItems: [
        { path: '/', label: 'nav.home', icon: 'Home' },
        { path: '/connect', label: 'nav.connect', icon: 'Plug' },
        { path: '/dashboard', label: 'nav.dashboard', icon: 'LayoutDashboard' },
        { path: '/files', label: 'nav.files', icon: 'FolderOpen' },
        { path: '/gallery', label: 'nav.gallery', icon: 'Images' },
        { path: '/permissions', label: 'nav.permissions', icon: 'ShieldCheck' },
        { path: '/terminal', label: 'nav.terminal', icon: 'TerminalSquare' },
        { path: '/monitoring', label: 'nav.monitoring', icon: 'Activity' },
        { path: '/sync', label: 'nav.sync', icon: 'ArrowLeftRight' },
        { path: '/history', label: 'nav.history', icon: 'Clock' },
        { path: '/favorites', label: 'nav.favorites', icon: 'Heart' },
        { path: '/log', label: 'nav.log', icon: 'ScrollText' },
        { path: '/settings', label: 'nav.settings', icon: 'Settings' }
      ]
    }
  },
  computed: {
    favorites() { return this.connectionStore.favorites }
  },
  methods: {
    async quickConnect(fav) {
      const result = await this.connectionStore.connect({ host: fav.host, port: fav.port, username: fav.username, password: '', type: fav.type })
      if (result.success) this.$router.push('/files')
    }
  }
}
</script>
