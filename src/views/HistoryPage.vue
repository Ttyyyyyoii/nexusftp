<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto p-6 overflow-auto">
      <h1 class="text-2xl font-bold text-surface-900 dark:text-white mb-8">{{ $t('nav.history') }}</h1>
      <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="glass-panel rounded-2xl p-5 text-center">
          <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ totalTransfers }}</div>
          <div class="text-xs text-surface-500 mt-1">Total Transfers</div>
        </div>
        <div class="glass-panel rounded-2xl p-5 text-center">
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ completedTransfers }}</div>
          <div class="text-xs text-surface-500 mt-1">Completed</div>
        </div>
        <div class="glass-panel rounded-2xl p-5 text-center">
          <div class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ failedTransfers }}</div>
          <div class="text-xs text-surface-500 mt-1">Failed</div>
        </div>
      </div>
      <div class="glass-panel rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-surface-200 dark:border-surface-800">
          <h2 class="font-semibold text-surface-900 dark:text-white flex items-center gap-2"><Clock class="w-5 h-5 text-primary-500" />Recent Transfers</h2>
          <button v-if="history.length > 0" @click="clearHistory" class="text-xs text-rose-600 hover:text-rose-700">Clear All</button>
        </div>
        <div v-if="history.length === 0" class="p-8 text-center"><Clock class="w-12 h-12 text-surface-300 mx-auto mb-4" /><p class="text-sm text-surface-500 dark:text-surface-400">No transfer history yet</p></div>
        <div v-else class="divide-y divide-surface-100 dark:divide-surface-800">
          <div v-for="item in history" :key="item.completedAt" class="flex items-center gap-4 px-5 py-3 hover:bg-surface-50 dark:hover:bg-surface-800/50 transition-colors">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" :class="item.type === 'upload' ? 'bg-primary-100 dark:bg-primary-900/20' : 'bg-emerald-100 dark:bg-emerald-900/20'">
              <ArrowUp v-if="item.type === 'upload'" class="w-4 h-4 text-primary-500" /><ArrowDown v-else class="w-4 h-4 text-emerald-500" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-surface-800 dark:text-surface-200 truncate">{{ item.fileName }}</p>
              <p class="text-xs text-surface-500">{{ formatDate(item.completedAt) }}</p>
            </div>
            <span class="text-xs text-surface-500 shrink-0">{{ formatSize(item.fileSize) }}</span>
            <span class="text-xs text-surface-400 shrink-0">{{ formatDuration(item.duration) }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { Clock, ArrowUp, ArrowDown } from 'lucide-vue-next'
import dayjs from 'dayjs'
export default {
  name: 'HistoryPage',
  components: { AppLayout, Clock, ArrowUp, ArrowDown },
  data() { return { history: [] } },
  computed: {
    totalTransfers() { return this.history.length },
    completedTransfers() { return this.history.filter(h => h.status !== 'failed').length },
    failedTransfers() { return this.history.filter(h => h.status === 'failed').length }
  },
  mounted() { this.loadHistory() },
  methods: {
    loadHistory() { const stored = localStorage.getItem('completedTransfers'); if (stored) this.history = JSON.parse(stored) },
    clearHistory() { localStorage.removeItem('completedTransfers'); this.history = [] },
    formatDate(date) { return dayjs(date).format('MMM D, YYYY HH:mm') },
    formatSize(bytes) { if (!bytes) return '--'; const units = ['B', 'KB', 'MB', 'GB']; let i = 0; while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++ } return `${bytes.toFixed(1)} ${units[i]}` },
    formatDuration(ms) { if (!ms) return '--'; if (ms < 1000) return `${ms}ms`; if (ms < 60000) return `${(ms / 1000).toFixed(1)}s`; return `${(ms / 60000).toFixed(1)}m` }
  }
}
</script>
