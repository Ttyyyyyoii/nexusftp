<template>
  <div class="fixed top-4 right-4 z-[100] space-y-3 pointer-events-none">
    <transition-group name="toast">
      <div v-for="toast in toasts" :key="toast.id"
        class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-premium border min-w-[320px] max-w-md backdrop-blur-xl"
        :class="toastClass(toast.type)">
        <component :is="toastIcon(toast.type)" class="w-5 h-5 shrink-0" />
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium">{{ toast.title }}</p>
          <p v-if="toast.message" class="text-xs opacity-80 truncate">{{ toast.message }}</p>
        </div>
        <button @click="removeToast(toast.id)" class="p-1 rounded-lg hover:bg-black/10 transition-colors shrink-0">
          <X class="w-4 h-4" />
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script>
import { CheckCircle, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next'
export default {
  name: 'ToastContainer',
  components: { CheckCircle, AlertCircle, AlertTriangle, Info, X },
  data() { return { toasts: [] } },
  mounted() { window.addEventListener('show-toast', this.handleToastEvent) },
  beforeUnmount() { window.removeEventListener('show-toast', this.handleToastEvent) },
  methods: {
    handleToastEvent(e) { this.addToast(e.detail) },
    addToast({ type = 'info', title, message, duration = 4000 }) {
      const id = Date.now() + Math.random()
      this.toasts.push({ id, type, title, message })
      setTimeout(() => this.removeToast(id), duration)
    },
    removeToast(id) { this.toasts = this.toasts.filter(t => t.id !== id) },
    toastClass(type) {
      const classes = {
        success: 'bg-emerald-50/90 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300',
        error: 'bg-rose-50/90 dark:bg-rose-900/30 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300',
        warning: 'bg-amber-50/90 dark:bg-amber-900/30 border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300',
        info: 'bg-primary-50/90 dark:bg-primary-900/30 border-primary-200 dark:border-primary-800 text-primary-800 dark:text-primary-300'
      }
      return classes[type] || classes.info
    },
    toastIcon(type) {
      const icons = { success: 'CheckCircle', error: 'AlertCircle', warning: 'AlertTriangle', info: 'Info' }
      return icons[type] || 'Info'
    }
  }
}
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(100%); }
</style>
