<template>
  <div class="h-screen flex flex-col bg-surface-0 dark:bg-surface-900 text-surface-700 dark:text-surface-300 overflow-hidden">
    <AppToolbar @toggle-sidebar="toggleSidebar" />
    <div class="flex flex-1 overflow-hidden relative">
      <!-- Overlay for mobile sidebar -->
      <transition name="fade">
        <div
          v-if="sidebarOpen && isMobile"
          class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm"
          @click="sidebarOpen = false"
        />
      </transition>
      <!-- Sidebar -->
      <transition name="slide-sidebar">
        <AppSidebar
          v-if="sidebarOpen"
          class="md:relative fixed top-0 bottom-0 left-0 z-50 shadow-2xl md:shadow-none"
          @close="sidebarOpen = false"
        />
      </transition>
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
    return {
      settingsStore: useSettingsStore(),
      sidebarOpen: true,
      isMobile: window.innerWidth < 768
    }
  },
  mounted() {
    const stored = this.settingsStore.sidebarCollapsed
    const mobile = window.innerWidth < 768
    // Sur mobile: toujours fermé au démarrage. Sur desktop: respecter le réglage sauvegardé
    this.sidebarOpen = mobile ? false : !stored
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

.slide-sidebar-enter-active, .slide-sidebar-leave-active { transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
.slide-sidebar-enter-from, .slide-sidebar-leave-to { transform: translateX(-100%); }
.slide-sidebar-enter-to, .slide-sidebar-leave-from { transform: translateX(0); }
</style>
