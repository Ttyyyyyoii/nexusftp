<template>
  <transition name="modal">
    <div v-if="visible" class="fixed inset-0 z-[90] flex items-center justify-center p-4" @click.self="close">
      <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" />
      <div class="relative bg-white dark:bg-surface-800 rounded-2xl shadow-premium border border-surface-200 dark:border-surface-700 w-full overflow-hidden" :class="maxWidth">
        <div v-if="title" class="flex items-center justify-between px-6 py-4 border-b border-surface-200 dark:border-surface-700">
          <h3 class="text-lg font-semibold text-surface-900 dark:text-white">{{ title }}</h3>
          <button @click="close" class="p-1.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors">
            <X class="w-5 h-5 text-surface-500" />
          </button>
        </div>
        <div class="px-6 py-4"><slot /></div>
        <div v-if="$slots.footer" class="flex items-center justify-end gap-3 px-6 py-4 border-t border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-900/50">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
import { X } from 'lucide-vue-next'
export default {
  name: 'BaseModal',
  components: { X },
  props: { visible: { type: Boolean, default: false }, title: { type: String, default: '' }, maxWidth: { type: String, default: 'max-w-lg' } },
  emits: ['close'],
  methods: { close() { this.$emit('close') } }
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .relative, .modal-leave-to .relative { transform: scale(0.95); }
</style>
