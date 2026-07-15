<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto p-6 overflow-auto">
      <h1 class="text-2xl font-bold text-surface-900 dark:text-white mb-8">{{ $t('favorites.title') }}</h1>
      <div class="mb-8">
        <h2 class="text-lg font-semibold text-surface-800 dark:text-surface-200 mb-4 flex items-center gap-2"><Heart class="w-5 h-5 text-rose-500" />{{ $t('favorites.connections') }}</h2>
        <div v-if="savedConnections.length === 0" class="glass-panel rounded-2xl p-8 text-center"><Bookmark class="w-12 h-12 text-surface-300 mx-auto mb-4" /><p class="text-surface-500 dark:text-surface-400">{{ $t('favorites.noFavorites') }}</p></div>
        <div v-else class="grid md:grid-cols-2 gap-4">
          <div v-for="conn in savedConnections" :key="conn.id" class="group glass-panel rounded-2xl p-5 hover:shadow-md transition-all">
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center"><Server class="w-5 h-5 text-primary-500" /></div>
                <div>
                  <h3 class="font-semibold text-surface-900 dark:text-white">{{ conn.label || conn.host }}</h3>
                  <p class="text-xs text-surface-500">{{ conn.username }}@{{ conn.host }}:{{ conn.port }}</p>
                </div>
              </div>
              <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button @click="connect(conn)" class="p-1.5 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 text-primary-600"><Plug class="w-4 h-4" /></button>
                <button @click="removeSaved(conn.id)" class="p-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20 text-rose-500"><Trash2 class="w-4 h-4" /></button>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs px-2 py-1 rounded-lg bg-surface-100 dark:bg-surface-800 font-mono uppercase font-medium">{{ conn.type }}</span>
            </div>
          </div>
        </div>
      </div>
      <div>
        <h2 class="text-lg font-semibold text-surface-800 dark:text-surface-200 mb-4 flex items-center gap-2"><Clock class="w-5 h-5 text-primary-500" />{{ $t('favorites.servers') }}</h2>
        <div v-if="recentConnections.length === 0" class="glass-panel rounded-2xl p-8 text-center"><Clock class="w-12 h-12 text-surface-300 mx-auto mb-4" /><p class="text-surface-500 dark:text-surface-400">{{ $t('favorites.noRecent') }}</p></div>
        <div v-else class="glass-panel rounded-2xl overflow-hidden divide-y divide-surface-100 dark:divide-surface-800">
          <div v-for="conn in recentConnections" :key="conn.host + conn.username" class="flex items-center justify-between px-5 py-4 hover:bg-surface-50 dark:hover:bg-surface-800/50 transition-colors">
            <div class="flex items-center gap-3">
              <Globe class="w-5 h-5 text-surface-400" />
              <div>
                <p class="font-medium text-sm text-surface-800 dark:text-surface-200">{{ conn.username }}@{{ conn.host }}</p>
                <p class="text-xs text-surface-400">{{ formatDate(conn.date) }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs px-2 py-1 rounded-lg bg-surface-100 dark:bg-surface-800 font-mono uppercase">{{ conn.type }}</span>
              <button @click="connect(conn)" class="p-2 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 text-primary-600 transition-colors"><Plug class="w-4 h-4" /></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { useConnectionStore } from '@/stores/connection'
import { Heart, Bookmark, Server, Plug, Trash2, Clock, Globe } from 'lucide-vue-next'
import dayjs from 'dayjs'
export default {
  name: 'FavoritesPage',
  components: { AppLayout, Heart, Bookmark, Server, Plug, Trash2, Clock, Globe },
  data() { return { connectionStore: useConnectionStore() } },
  computed: {
    savedConnections() { return this.connectionStore.savedConnections },
    recentConnections() { return this.connectionStore.recentConnections }
  },
  methods: {
    async connect(conn) { const result = await this.connectionStore.connect({ host: conn.host, port: conn.port, username: conn.username, password: '', type: conn.type }); if (result.success) this.$router.push('/files') },
    removeSaved(id) { this.connectionStore.removeSavedConnection(id) },
    formatDate(date) { return dayjs(date).format('MMM D, YYYY HH:mm') }
  }
}
</script>
