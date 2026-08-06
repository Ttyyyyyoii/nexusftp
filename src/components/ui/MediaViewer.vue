<template>
  <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md" @click.self="$emit('close')">
    <div class="absolute top-4 right-4 flex gap-3">
      <button v-if="mediaUrl" @click="download" class="p-2 rounded-full bg-surface-800/50 hover:bg-surface-700 text-white transition-colors">
        <Download class="w-6 h-6" />
      </button>
      <button @click="$emit('close')" class="p-2 rounded-full bg-surface-800/50 hover:bg-surface-700 text-white transition-colors">
        <X class="w-6 h-6" />
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center justify-center gap-4">
      <Loader2 class="w-12 h-12 text-primary-500 animate-spin" />
      <p class="text-white text-sm font-medium">Préparation du média...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex flex-col items-center justify-center gap-4 bg-surface-900 p-8 rounded-2xl border border-surface-700 max-w-sm text-center">
      <AlertCircle class="w-12 h-12 text-rose-500" />
      <h3 class="text-white font-bold text-lg">Erreur de lecture</h3>
      <p class="text-surface-400 text-sm">{{ error }}</p>
      <button @click="$emit('close')" class="mt-4 px-6 py-2 bg-surface-800 hover:bg-surface-700 text-white rounded-xl transition-colors">Fermer</button>
    </div>

    <!-- Viewer -->
    <div v-else-if="mediaUrl" class="w-full h-full max-w-6xl max-h-[85vh] flex flex-col items-center justify-center">
      <template v-if="type === 'image'">
        <img :src="mediaUrl" :alt="file?.name" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl" />
      </template>
      <template v-else-if="type === 'video'">
        <video :src="mediaUrl" controls autoplay class="max-w-full max-h-full rounded-lg shadow-2xl outline-none bg-black"></video>
      </template>
      <template v-else-if="type === 'audio'">
        <div class="bg-surface-900 p-8 rounded-2xl border border-surface-700 shadow-2xl flex flex-col items-center gap-6 min-w-[320px]">
          <div class="w-20 h-20 rounded-full bg-primary-900/30 flex items-center justify-center">
            <Music class="w-10 h-10 text-primary-500" />
          </div>
          <p class="text-white font-medium text-center truncate w-full px-4">{{ file?.name }}</p>
          <audio :src="mediaUrl" controls autoplay class="w-full outline-none"></audio>
        </div>
      </template>
    </div>
    
    <div v-if="!loading && !error && mediaUrl" class="absolute bottom-6 text-white/50 text-sm font-mono truncate max-w-xl px-4 text-center">
      {{ file?.name }}
    </div>
  </div>
</template>

<script>
import { X, Loader2, Download, AlertCircle, Music } from 'lucide-vue-next'
import { useConnectionStore } from '@/stores/connection'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

export default {
  name: 'MediaViewer',
  components: { X, Loader2, Download, AlertCircle, Music },
  props: {
    visible: { type: Boolean, default: false },
    file: { type: Object, default: null },
    remotePath: { type: String, default: '/' }
  },
  emits: ['close'],
  data() {
    return {
      loading: false,
      error: null,
      mediaUrl: null,
      type: 'image', // 'image', 'video', 'audio'
      connectionStore: useConnectionStore()
    }
  },
  watch: {
    visible(val) {
      if (val && this.file) {
        this.detectType()
        this.loadMedia()
      } else {
        this.mediaUrl = null
      }
    }
  },
  methods: {
    detectType() {
      const ext = this.file.name.split('.').pop().toLowerCase()
      if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)) this.type = 'image'
      else if (['mp4', 'webm', 'ogg', 'mov'].includes(ext)) this.type = 'video'
      else if (['mp3', 'wav', 'ogg', 'm4a'].includes(ext)) this.type = 'audio'
    },
    async loadMedia() {
      this.loading = true
      this.error = null
      this.mediaUrl = null
      try {
        // We use the share token mechanism to stream the file securely
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
          this.mediaUrl = data.shareUrl
        } else {
          this.error = data.message || 'Impossible de lire ce média.'
        }
      } catch (e) {
        this.error = 'Erreur réseau.'
      } finally {
        this.loading = false
      }
    },
    download() {
      if (!this.mediaUrl) return
      const a = document.createElement('a')
      a.href = this.mediaUrl
      a.download = this.file.name
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
    }
  }
}
</script>
