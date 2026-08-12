<template>
  <!-- Bouton Installer dans la sidebar -->
  <div v-if="variant === 'sidebar' && canInstall" class="mt-2">
    <button
      @click="installApp"
      class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group
        bg-gradient-to-r from-indigo-500/10 to-violet-500/10
        hover:from-indigo-500/20 hover:to-violet-500/20
        border border-indigo-200 dark:border-indigo-800/50
        text-indigo-700 dark:text-indigo-300"
    >
      <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
        <Download class="w-3 h-3 text-white" />
      </div>
      <span class="truncate">{{ $t('pwa.install') }}</span>
      <Sparkles class="w-3.5 h-3.5 text-indigo-400 shrink-0 ml-auto" />
    </button>
  </div>

  <button
    v-else-if="variant === 'hero' && canInstall"
    @click="installApp"
    class="inline-flex items-center gap-2.5 px-7 py-3.5 rounded-2xl font-semibold text-base transition-all duration-300
      bg-gradient-to-r from-indigo-600 to-violet-600
      hover:from-indigo-500 hover:to-violet-500
      text-white shadow-lg shadow-indigo-500/30
      hover:shadow-xl hover:shadow-indigo-500/40
      hover:-translate-y-0.5 active:translate-y-0"
  >
    <Download class="w-5 h-5" />
    {{ $t('pwa.installFull') }}
  </button>

  <!-- Bannière de mise à jour disponible -->
  <Transition name="slide-up">
    <div
      v-if="updateReady"
      class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[200] flex items-center gap-4 px-5 py-3.5 rounded-2xl shadow-2xl border"
      style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border-color: rgba(99,102,241,0.4);"
    >
      <div class="w-8 h-8 rounded-xl bg-indigo-500/20 flex items-center justify-center shrink-0">
        <RefreshCw class="w-4 h-4 text-indigo-300 animate-spin" />
      </div>
      <div>
        <p class="text-sm font-semibold text-white">{{ $t('pwa.updateTitle') }}</p>
        <p class="text-xs text-indigo-300">{{ $t('pwa.updateDesc') }}</p>
      </div>
      <button
        @click="reloadApp"
        class="px-4 py-1.5 rounded-xl bg-indigo-500 hover:bg-indigo-400 text-white text-xs font-semibold transition-colors whitespace-nowrap"
      >
        {{ $t('pwa.updateBtn') }}
      </button>
      <button @click="updateReady = false" class="text-indigo-400 hover:text-white transition-colors">
        <X class="w-4 h-4" />
      </button>
    </div>
  </Transition>
</template>

<script>
import { Download, Sparkles, RefreshCw, X } from 'lucide-vue-next'

export default {
  name: 'PwaInstallPrompt',
  components: { Download, Sparkles, RefreshCw, X },

  props: {
    variant: {
      type: String,
      default: 'sidebar'
    }
  },

  data() {
    return {
      canInstall: false,
      deferredPrompt: null,
      updateReady: false,
      updateSW: null,
    }
  },

  async mounted() {
    // Écouter l'événement d'installation PWA du navigateur
    window.addEventListener('beforeinstallprompt', this.onBeforeInstallPrompt)
    window.addEventListener('appinstalled', this.onAppInstalled)

    // Gérer les mises à jour du Service Worker
    try {
      const { useRegisterSW } = await import('virtual:pwa-register/vue')
      const { updateServiceWorker } = useRegisterSW({
        onNeedRefresh: () => { this.updateReady = true },
        onOfflineReady: () => { /* prêt hors-ligne */ },
      })
      this.updateSW = updateServiceWorker
    } catch (e) {
      // En mode dev, virtual:pwa-register n'est pas disponible
    }
  },

  beforeUnmount() {
    window.removeEventListener('beforeinstallprompt', this.onBeforeInstallPrompt)
    window.removeEventListener('appinstalled', this.onAppInstalled)
  },

  methods: {
    onBeforeInstallPrompt(e) {
      e.preventDefault()
      this.deferredPrompt = e
      this.canInstall = true
    },

    onAppInstalled() {
      this.canInstall = false
      this.deferredPrompt = null
    },

    async installApp() {
      if (!this.deferredPrompt) return
      this.deferredPrompt.prompt()
      const { outcome } = await this.deferredPrompt.userChoice
      if (outcome === 'accepted') {
        this.canInstall = false
        this.deferredPrompt = null
      }
    },

    reloadApp() {
      if (this.updateSW) this.updateSW()
      else window.location.reload()
    }
  }
}
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translate(-50%, 20px); }
</style>
