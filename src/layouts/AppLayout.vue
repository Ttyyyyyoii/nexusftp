<template>
  <div class="h-screen flex flex-col bg-surface-0 dark:bg-surface-900 text-surface-700 dark:text-surface-300 overflow-hidden">
    <AppToolbar @toggle-sidebar="toggleSidebar" />
    <div class="flex flex-1 overflow-hidden relative">
      <!-- Overlay for mobile sidebar -->
      <transition name="fade">
        <div
          v-show="sidebarOpen && isMobile"
          class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm"
          @click="sidebarOpen = false"
        />
      </transition>
      <!-- Sidebar -->
      <AppSidebar
        class="md:relative fixed top-0 bottom-0 left-0 z-50 shadow-2xl md:shadow-none transition-transform duration-300 ease-in-out"
        :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0', !sidebarOpen && isMobile ? 'invisible md:visible' : '']"
        @close="sidebarOpen = false"
      />
      <main class="flex-1 overflow-auto">
        <slot />
      </main>
    </div>
    <AppStatusbar />
    <ToastContainer />
  </div>
</template>

<script>
import { useSettingsStore } from '@/stores/settings'
import AppToolbar from '@/components/layout/AppToolbar.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppStatusbar from '@/components/layout/AppStatusbar.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'

export default {
  name: 'AppLayout',
  components: { AppToolbar, AppSidebar, AppStatusbar, ToastContainer },
  data() {
    const mobile = window.innerWidth < 768
    const store = useSettingsStore()
    return {
      settingsStore: store,
      // Initialiser correctement la valeur par defaut
      sidebarOpen: mobile ? false : !store.sidebarCollapsed,
      isMobile: mobile
    }
  },
  mounted() {
    this._onResize = () => { this.isMobile = window.innerWidth < 768 }
    window.addEventListener('resize', this._onResize)
  },
  beforeUnmount() {
    window.removeEventListener('resize', this._onResize)
  },
  methods: {
    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen
      this.settingsStore.sidebarCollapsed = !this.sidebarOpen
    }
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
