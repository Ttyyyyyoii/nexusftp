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

const API_BASE = import.meta.env.VITE_API_URL || '/api'

export default {
  name: 'App',
  data() {
    return {
      settingsStore: useSettingsStore(),
      pingInterval: null
    }
  },
  async mounted() {
    this.settingsStore.restore()

    // Warm-up du backend Render avant de cacher le splash
    const splash = document.getElementById('splash-screen')
    if (splash) {
      const statusEl = splash.querySelector('.splash-subtitle')

      // On ping le backend. S'il dort (cold start Render), on affiche un message
      let backendReady = false
      const MAX_WAIT = 60000 // 60 secondes max
      const POLL_INTERVAL = 2500
      const startTime = Date.now()

      const checkBackend = async () => {
        try {
          const res = await fetch(`${API_BASE}/ping.php`, { signal: AbortSignal.timeout(4000) })
          if (res.ok) return true
        } catch (_) {}
        return false
      }

      // Premier essai immédiat
      backendReady = await checkBackend()

      if (!backendReady && statusEl) {
        statusEl.textContent = '⏳ Démarrage du serveur…'
      }

      // Polling tant que pas prêt
      while (!backendReady && (Date.now() - startTime) < MAX_WAIT) {
        await new Promise(r => setTimeout(r, POLL_INTERVAL))
        backendReady = await checkBackend()

        // Mise à jour du message avec le temps écoulé
        if (!backendReady && statusEl) {
          const elapsed = Math.round((Date.now() - startTime) / 1000)
          statusEl.textContent = `⏳ Démarrage du serveur… (${elapsed}s)`
        }
      }

      // Cacher le splash screen
      splash.classList.add('hidden')
      setTimeout(() => splash.remove(), 600)
    }

    // Keep-alive ping pour empêcher Render de se rendormir (toutes les 10 min)
    this.pingInterval = setInterval(() => {
      fetch(`${API_BASE}/ping.php`).catch(() => {})
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
