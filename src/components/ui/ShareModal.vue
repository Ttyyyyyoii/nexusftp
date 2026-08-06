<template>
  <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="$emit('close')">
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-2xl w-full max-w-md border border-surface-200 dark:border-surface-700">
      <!-- Header -->
      <div class="flex items-center gap-3 px-6 py-4 border-b border-surface-200 dark:border-surface-800">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
          <Share2 class="w-5 h-5 text-emerald-500" />
        </div>
        <div class="flex-1">
          <h2 class="text-base font-bold text-surface-900 dark:text-white">Lien de Partage</h2>
          <p class="text-xs text-surface-500 dark:text-surface-400 truncate max-w-[240px]">{{ fileName }}</p>
        </div>
        <button @click="$emit('close')" class="p-1.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors">
          <X class="w-5 h-5 text-surface-500" />
        </button>
      </div>

      <!-- Content -->
      <div class="px-6 py-5 space-y-4">
        <!-- Generating state -->
        <div v-if="generating" class="flex flex-col items-center justify-center py-8 gap-3">
          <Loader2 class="w-10 h-10 text-primary-500 animate-spin" />
          <p class="text-sm text-surface-500 dark:text-surface-400">Génération du lien...</p>
        </div>

        <!-- Share link ready -->
        <template v-else-if="shareUrl">
          <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800 flex items-center gap-2">
            <CheckCircle class="w-4 h-4 text-emerald-500 shrink-0" />
            <p class="text-xs text-emerald-700 dark:text-emerald-300">Lien créé avec succès ! Valable <strong>24 heures</strong>.</p>
          </div>

          <div class="space-y-2">
            <label class="text-xs font-semibold text-surface-700 dark:text-surface-300">URL de téléchargement</label>
            <div class="flex gap-2">
              <div class="flex-1 px-3 py-2.5 rounded-xl bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 text-xs text-surface-600 dark:text-surface-300 font-mono truncate">
                {{ shareUrl }}
              </div>
              <button @click="copyUrl" class="px-3 py-2.5 rounded-xl border transition-all text-xs font-medium shrink-0"
                :class="copied ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-surface-200 dark:border-surface-700 text-surface-700 dark:text-surface-300 hover:bg-surface-100 dark:hover:bg-surface-800'">
                <Check v-if="copied" class="w-4 h-4" />
                <Copy v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div class="flex items-center gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-700">
            <Clock class="w-4 h-4 text-amber-500 shrink-0" />
            <p class="text-xs text-amber-700 dark:text-amber-300">Ce lien expirera dans 24h et ne fonctionnera plus ensuite.</p>
          </div>
        </template>

        <!-- Error state -->
        <div v-else-if="error" class="flex flex-col items-center justify-center py-8 gap-3 text-center">
          <AlertCircle class="w-10 h-10 text-rose-400" />
          <p class="text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-surface-200 dark:border-surface-800 flex justify-end gap-3">
        <button @click="$emit('close')" class="px-4 py-2 rounded-xl text-sm font-medium text-surface-700 dark:text-surface-300 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors">
          Fermer
        </button>
        <button v-if="shareUrl" @click="openUrl" class="px-4 py-2 rounded-xl text-sm font-medium bg-primary-500 hover:bg-primary-600 text-white transition-colors flex items-center gap-2">
          <ExternalLink class="w-4 h-4" />
          Ouvrir
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { Share2, X, Loader2, CheckCircle, Copy, Check, Clock, AlertCircle, ExternalLink } from 'lucide-vue-next'
import { useConnectionStore } from '@/stores/connection'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

export default {
  name: 'ShareModal',
  components: { Share2, X, Loader2, CheckCircle, Copy, Check, Clock, AlertCircle, ExternalLink },
  props: {
    visible: { type: Boolean, default: false },
    file: { type: Object, default: null },
    remotePath: { type: String, default: '/' }
  },
  emits: ['close'],
  data() {
    return {
      generating: false,
      shareUrl: null,
      error: null,
      copied: false,
      connectionStore: useConnectionStore()
    }
  },
  computed: {
    fileName() {
      return this.file?.name || ''
    }
  },
  watch: {
    visible(val) {
      if (val && this.file) {
        this.shareUrl = null
        this.error = null
        this.copied = false
        this.generateLink()
      }
    }
  },
  methods: {
    async generateLink() {
      this.generating = true
      this.error = null
      try {
        const response = await fetch(`${API_BASE}/share.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({
            sessionId: this.connectionStore.sessionId,
            remotePath: this.remotePath,
            remoteName: this.file.name
          })
        })
        const data = await response.json()
        if (data.success) {
          this.shareUrl = data.shareUrl
        } else {
          this.error = data.message || 'Impossible de créer le lien.'
        }
      } catch (e) {
        this.error = 'Erreur réseau. Vérifiez votre connexion.'
      } finally {
        this.generating = false
      }
    },
    async copyUrl() {
      try {
        await navigator.clipboard.writeText(this.shareUrl)
        this.copied = true
        setTimeout(() => { this.copied = false }, 2000)
      } catch (e) {
        // fallback
        const el = document.createElement('textarea')
        el.value = this.shareUrl
        document.body.appendChild(el)
        el.select()
        document.execCommand('copy')
        document.body.removeChild(el)
        this.copied = true
        setTimeout(() => { this.copied = false }, 2000)
      }
    },
    openUrl() {
      window.open(this.shareUrl, '_blank')
    }
  }
}
</script>
