<template>
  <div class="bg-surface-0 dark:bg-surface-900 border-t border-surface-200 dark:border-surface-800 flex flex-col shrink-0 transition-all duration-300" :class="collapsed ? '' : 'h-48'">
    <div class="flex items-center justify-between px-4 py-2 border-b border-surface-200 dark:border-surface-800 bg-surface-50/50 dark:bg-surface-800/50">
      <div class="flex items-center gap-3">
        <ArrowUpDown class="w-4 h-4 text-surface-500" />
        <span class="text-xs font-semibold text-surface-700 dark:text-surface-300 uppercase tracking-wider">{{ $t('transfers.title') }}</span>
        <span class="text-xs px-2 py-0.5 rounded-full bg-primary-100 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400">{{ activeCount }}</span>
      </div>
      <div class="flex items-center gap-2">
        <button @click="transfersStore.clearCompleted()" class="text-xs text-surface-500 hover:text-surface-700 transition-colors">{{ $t('transfers.clearCompleted') }}</button>
        <button @click="collapsed = !collapsed" class="p-1 rounded hover:bg-surface-200 dark:hover:bg-surface-700 transition-colors">
          <ChevronDown v-if="!collapsed" class="w-4 h-4" />
          <ChevronUp v-else class="w-4 h-4" />
        </button>
      </div>
    </div>
    <div v-show="!collapsed" class="flex-1 overflow-y-auto">
      <div v-if="transfers.length === 0" class="flex items-center justify-center h-full text-sm text-surface-400">{{ $t('transfers.noTransfers') }}</div>
      <div v-else class="divide-y divide-surface-100 dark:divide-surface-800/50">
        <div v-for="transfer in transfers.slice(0, 20)" :key="transfer.id" class="flex items-center gap-3 px-4 py-2 hover:bg-surface-50 dark:hover:bg-surface-800/30 transition-colors">
          <div class="shrink-0">
            <ArrowUp v-if="transfer.type === 'upload'" class="w-4 h-4 text-primary-500" />
            <ArrowDown v-else class="w-4 h-4 text-emerald-500" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-sm text-surface-800 dark:text-surface-200 truncate">{{ transfer.fileName }}</span>
              <span class="text-xs px-1.5 py-0.5 rounded bg-surface-100 dark:bg-surface-800 text-surface-500">{{ formatSize(transfer.fileSize) }}</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="flex-1 h-1.5 bg-surface-200 dark:bg-surface-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-300" :class="transferStatusClass(transfer.status)" :style="{ width: transfer.progress + '%' }" />
              </div>
              <span class="text-xs text-surface-500 w-10 text-right">{{ transfer.progress }}%</span>
            </div>
          </div>
          <div class="w-20 text-right shrink-0">
            <span class="text-xs px-2 py-0.5 rounded-full" :class="statusBadgeClass(transfer.status)">{{ transfer.status }}</span>
          </div>
          <button @click="transfersStore.cancelTransfer(transfer.id)" class="p-1 rounded hover:bg-rose-100 dark:hover:bg-rose-900/20 transition-colors">
            <X class="w-3 h-3 text-rose-500" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useTransfersStore } from '@/stores/transfers'
import { ArrowUpDown, ChevronDown, ChevronUp, ArrowUp, ArrowDown, X } from 'lucide-vue-next'
export default {
  name: 'TransferPanel',
  components: { ArrowUpDown, ChevronDown, ChevronUp, ArrowUp, ArrowDown, X },
  data() { return { transfersStore: useTransfersStore(), collapsed: false } },
  computed: {
    transfers() { return this.transfersStore.transfers },
    activeCount() { return this.transfersStore.activeTransfers.length }
  },
  methods: {
    formatSize(bytes) { if (!bytes) return '--'; const units = ['B', 'KB', 'MB', 'GB']; let i = 0; while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++ } return `${bytes.toFixed(1)} ${units[i]}` },
    transferStatusClass(status) { const classes = { active: 'bg-gradient-to-r from-primary-500 to-primary-400', paused: 'bg-amber-400', pending: 'bg-surface-300 dark:bg-surface-600', completed: 'bg-emerald-500', failed: 'bg-rose-500' }; return classes[status] || classes.pending },
    statusBadgeClass(status) { const classes = { active: 'bg-primary-100 dark:bg-primary-900/20 text-primary-600', paused: 'bg-amber-100 dark:bg-amber-900/20 text-amber-600', pending: 'bg-surface-100 dark:bg-surface-800 text-surface-500', completed: 'bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600', failed: 'bg-rose-100 dark:bg-rose-900/20 text-rose-600' }; return classes[status] || classes.pending }
  }
}
</script>
