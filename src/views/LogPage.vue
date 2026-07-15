<template>
  <AppLayout>
    <div class="h-full flex flex-col">
      <div class="flex items-center gap-2 px-4 py-2 border-b border-surface-200 dark:border-surface-800 bg-surface-50/50 dark:bg-surface-800/50 shrink-0">
        <ScrollText class="w-4 h-4 text-surface-500" />
        <span class="text-xs font-semibold text-surface-700 dark:text-surface-300 uppercase tracking-wider">{{ $t('log.title') }}</span>
        <div class="flex-1" />
        <div class="flex gap-1">
          <button v-for="filter in filters" :key="filter.value" @click="logStore.setFilter(filter.value)"
            class="px-3 py-1 rounded-lg text-xs font-medium transition-all"
            :class="logStore.filter === filter.value ? 'bg-primary-100 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-surface-500 hover:bg-surface-100 dark:hover:bg-surface-700'">
            {{ filter.label }}
          </button>
        </div>
        <div class="w-px h-4 bg-surface-300 dark:bg-surface-700 mx-2" />
        <button @click="logStore.clear()" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors"><Trash2 class="w-3 h-3" />{{ $t('log.clear') }}</button>
      </div>
      <div class="flex-1 overflow-auto">
        <div v-if="logStore.filteredEntries.length === 0" class="flex flex-col items-center justify-center h-64 text-center">
          <ScrollText class="w-12 h-12 text-surface-300 dark:text-surface-700 mb-4" />
          <p class="text-sm text-surface-500 dark:text-surface-400">{{ $t('log.empty') }}</p>
        </div>
        <div v-else class="divide-y divide-surface-100 dark:divide-surface-800/50">
          <div v-for="entry in logStore.filteredEntries" :key="entry.id" class="flex items-start gap-3 px-4 py-2.5 hover:bg-surface-50 dark:hover:bg-surface-800/30 transition-colors">
            <component :is="levelIcon(entry.level)" class="w-4 h-4 mt-0.5 shrink-0" :class="levelColor(entry.level)" />
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono text-surface-400">{{ formatTime(entry.timestamp) }}</span>
                <span class="text-xs px-1.5 py-0.5 rounded font-medium uppercase" :class="levelBadgeClass(entry.level)">{{ entry.level }}</span>
              </div>
              <p class="text-sm text-surface-700 dark:text-surface-300 mt-0.5">{{ entry.message }}</p>
              <p v-if="entry.details" class="text-xs text-surface-500 mt-1">{{ entry.details }}</p>
            </div>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-4 px-4 py-2 border-t border-surface-200 dark:border-surface-800 bg-surface-50/50 dark:bg-surface-800/50 text-xs shrink-0">
        <span class="text-surface-500"><span class="font-medium text-surface-700 dark:text-surface-300">{{ logStore.entries.length }}</span> entries</span>
        <span v-if="logStore.errorCount > 0" class="text-rose-600"><span class="font-medium">{{ logStore.errorCount }}</span> errors</span>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { useLogStore } from '@/stores/log'
import { ScrollText, Trash2, Info, AlertCircle, AlertTriangle, CheckCircle, Plug, Terminal } from 'lucide-vue-next'
import dayjs from 'dayjs'
export default {
  name: 'LogPage',
  components: { AppLayout, ScrollText, Trash2, Info, AlertCircle, AlertTriangle, CheckCircle, Plug, Terminal },
  data() {
    return {
      logStore: useLogStore(),
      filters: [
        { value: 'all', label: this.$t('log.all') }, { value: 'info', label: this.$t('log.info') },
        { value: 'warning', label: this.$t('log.warning') }, { value: 'error', label: this.$t('log.error') },
        { value: 'connection', label: this.$t('log.connection') }
      ]
    }
  },
  mounted() { this.logStore.restore() },
  methods: {
    formatTime(ts) { return dayjs(ts).format('HH:mm:ss.SSS') },
    levelIcon(level) { const icons = { info: 'Info', warning: 'AlertTriangle', error: 'AlertCircle', success: 'CheckCircle', connection: 'Plug', command: 'Terminal' }; return icons[level] || 'Info' },
    levelColor(level) { const colors = { info: 'text-primary-500', warning: 'text-amber-500', error: 'text-rose-500', success: 'text-emerald-500', connection: 'text-violet-500', command: 'text-cyan-500' }; return colors[level] || 'text-surface-400' },
    levelBadgeClass(level) { const classes = { info: 'bg-primary-100 dark:bg-primary-900/20 text-primary-600', warning: 'bg-amber-100 dark:bg-amber-900/20 text-amber-600', error: 'bg-rose-100 dark:bg-rose-900/20 text-rose-600', success: 'bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600', connection: 'bg-violet-100 dark:bg-violet-900/20 text-violet-600', command: 'bg-cyan-100 dark:bg-cyan-900/20 text-cyan-600' }; return classes[level] || 'bg-surface-100 text-surface-600' }
  }
}
</script>
