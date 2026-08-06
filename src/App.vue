<template>
  <div id="app" :class="{ dark: settingsStore.isDark }">
    <router-view v-slot="{ Component }">
      <transition name="fade" mode="out-in">
        <component :is="Component" />
      </transition>
    </router-view>
  </div>
</template>

<script>
import { useSettingsStore } from '@/stores/settings'

export default {
  name: 'App',
  data() {
    return {
      settingsStore: useSettingsStore(),
      pingInterval: null
    }
  },
  mounted() {
    this.settingsStore.restore()
    
    // Keep-alive ping pour empêcher Render de s'endormir (toutes les 10 min)
    this.pingInterval = setInterval(() => {
      fetch('/api/ping.php').catch(() => {})
    }, 10 * 60 * 1000)
  },
  beforeUnmount() {
    if (this.pingInterval) clearInterval(this.pingInterval)
  }
}
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
