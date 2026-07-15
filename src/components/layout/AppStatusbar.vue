<template>
  <footer class="h-8 bg-surface-100 dark:bg-surface-950 border-t border-surface-200 dark:border-surface-800 flex items-center px-4 gap-4 text-xs shrink-0">
    <div class="flex items-center gap-2">
      <div class="w-2 h-2 rounded-full" :class="connectionStore.isConnected ? 'bg-emerald-500' : 'bg-surface-400'" />
      <span class="text-surface-600 dark:text-surface-400">{{ connectionStore.isConnected ? $t('statusbar.connected') : $t('statusbar.disconnected') }}</span>
    </div>
    <div class="w-px h-4 bg-surface-300 dark:bg-surface-700" />
    <span class="text-surface-500 dark:text-surface-500">{{ $t('statusbar.items', { count: itemsCount }) }}</span>
    <div class="flex-1" />
    <span class="text-surface-400 dark:text-surface-600">NexusFTP v1.0.0</span>
  </footer>
</template>

<script>
import { useConnectionStore } from '@/stores/connection'
export default {
  name: 'AppStatusbar',
  data() { return { connectionStore: useConnectionStore() } },
  computed: {
    itemsCount() { return this.connectionStore.remoteFiles?.length || 0 }
  }
}
</script>
