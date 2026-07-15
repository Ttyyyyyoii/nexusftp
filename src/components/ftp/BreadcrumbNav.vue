<template>
  <div class="flex items-center gap-1 text-sm overflow-hidden">
    <button @click="$emit('navigate', '/')" class="p-1 rounded hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors shrink-0">
      <Home class="w-4 h-4 text-surface-400" />
    </button>
    <template v-for="(segment, index) in segments" :key="index">
      <ChevronRight class="w-3 h-3 text-surface-300 shrink-0" />
      <button v-if="index < segments.length - 1" @click="navigateTo(index)"
        class="px-2 py-0.5 rounded hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors text-surface-600 dark:text-surface-400 truncate max-w-[100px]">
        {{ segment }}
      </button>
      <span v-else class="px-2 py-0.5 font-medium text-surface-800 dark:text-surface-200 truncate max-w-[120px]">{{ segment || '/' }}</span>
    </template>
  </div>
</template>

<script>
import { Home, ChevronRight } from 'lucide-vue-next'
export default {
  name: 'BreadcrumbNav',
  components: { Home, ChevronRight },
  props: { path: { type: String, default: '/' } },
  emits: ['navigate'],
  computed: { segments() { return this.path.split('/').filter(Boolean) } },
  methods: {
    navigateTo(index) { this.$emit('navigate', '/' + this.segments.slice(0, index + 1).join('/')) }
  }
}
</script>
