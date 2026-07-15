<template>
  <div class="h-screen flex flex-col bg-surface-0 dark:bg-surface-900 text-surface-700 dark:text-surface-300 overflow-hidden">
    <AppToolbar />
    <div class="flex flex-1 overflow-hidden">
      <AppSidebar v-if="!settingsStore.sidebarCollapsed" />
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
    return { settingsStore: useSettingsStore() }
  }
}
</script>
