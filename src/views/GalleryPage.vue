<template>
  <AppLayout>
    <div class="h-full overflow-y-auto bg-surface-950 p-4 md:p-6">
      <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
              <ImageIcon class="w-6 h-6 text-violet-400" />
              {{ $t('nav.gallery') }}
            </h1>
            <p class="text-surface-400 mt-1 text-sm">
              {{ $t('gallery.subtitle') }}
            </p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-xs text-surface-400 bg-surface-800 px-3 py-1.5 rounded-lg">
              {{ images.length }} {{ $t('gallery.images') }}
            </span>
            <!-- View toggle -->
            <button @click="gridSize = gridSize === 'sm' ? 'lg' : 'sm'"
              class="p-2 rounded-lg bg-surface-800 text-surface-400 hover:text-white transition-colors">
              <LayoutGrid v-if="gridSize === 'sm'" class="w-4 h-4" />
              <Grid3x3 v-else class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Folder navigation bar -->
        <div v-if="connectionStore.isConnected" class="flex items-center gap-2 bg-surface-900 border border-surface-800 rounded-xl px-3 py-2">
          <button @click="navigateUp" :disabled="currentPath === '/'" class="p-1.5 rounded-lg hover:bg-surface-800 disabled:opacity-30 transition-colors">
            <ChevronLeft class="w-4 h-4 text-surface-400" />
          </button>
          <div class="flex items-center gap-1 flex-1 overflow-x-auto">
            <button @click="navigateTo('/')" class="flex items-center gap-1 text-xs text-surface-500 hover:text-violet-400 transition-colors shrink-0">
              <HomeIcon class="w-3.5 h-3.5" />
            </button>
            <span v-for="(seg, i) in pathSegments" :key="i" class="flex items-center gap-1 shrink-0">
              <ChevronRight class="w-3 h-3 text-surface-600" />
              <button @click="navigateTo(seg.path)" class="text-xs font-medium transition-colors" :class="i === pathSegments.length - 1 ? 'text-white' : 'text-surface-400 hover:text-violet-400'">{{ seg.name }}</button>
            </span>
          </div>
          <!-- Subfolder list -->
          <div class="flex items-center gap-1 ml-2">
            <button v-for="folder in subfolders.slice(0,4)" :key="folder.name" @click="navigateTo(currentPath.replace(/\/$/,'') + '/' + folder.name)"
              class="px-2 py-1 text-xs bg-surface-800 text-surface-300 hover:bg-violet-900/40 hover:text-violet-300 rounded-lg transition-colors truncate max-w-[80px]">
              📁 {{ folder.name }}
            </button>
            <span v-if="subfolders.length > 4" class="text-xs text-surface-500">+{{ subfolders.length - 4 }}</span>
          </div>
          <button @click="refreshDir" class="p-1.5 rounded-lg hover:bg-surface-800 transition-colors">
            <RefreshCwIcon class="w-3.5 h-3.5 text-surface-400" :class="navigating ? 'animate-spin' : ''" />
          </button>
        </div>

        <!-- Not connected -->
        <div v-if="!connectionStore.isConnected" class="flex flex-col items-center justify-center h-72 bg-surface-900 rounded-2xl border border-surface-800">
          <ImageOff class="w-14 h-14 text-surface-600 mb-4" />
          <p class="text-surface-400 text-center max-w-xs mb-4">{{ $t('gallery.notConnected') }}</p>
          <router-link to="/connect" class="btn-primary text-sm px-5 py-2">{{ $t('dashboard.connectBtn') }}</router-link>
        </div>

        <!-- No images -->
        <div v-else-if="!images.length" class="flex flex-col items-center justify-center h-72 bg-surface-900 rounded-2xl border border-surface-800">
          <ImageOff class="w-14 h-14 text-surface-600 mb-4" />
          <p class="text-surface-400 text-sm">{{ $t('gallery.noImages') }}</p>
          <p class="text-surface-500 text-xs mt-1">{{ $t('gallery.noImagesHint') }}</p>
        </div>

        <!-- Gallery grid -->
        <div v-else :class="['grid gap-3 transition-all', gridSize === 'sm' ? 'grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6' : 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4']">
          <div v-for="(img, idx) in images" :key="img.name"
            @click="openLightbox(idx)"
            class="group relative aspect-square rounded-xl overflow-hidden bg-surface-800 border border-surface-700 cursor-pointer hover:border-violet-500 hover:scale-[1.02] transition-all duration-200 shadow-sm">
            <img
              :src="imageUrl(img)"
              :alt="img.name"
              class="w-full h-full object-cover opacity-0 transition-opacity duration-300"
              @load="(e) => e.target.classList.add('opacity-100')"
              @error="(e) => e.target.parentElement.classList.add('img-error')"
              loading="lazy"
            />
            <!-- Broken image -->
            <div class="absolute inset-0 hidden img-error-content items-center justify-center bg-surface-800">
              <ImageOff class="w-6 h-6 text-surface-600" />
            </div>
            <!-- Hover overlay -->
            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center p-2">
              <Maximize2 class="w-6 h-6 text-white mb-1" />
              <span class="text-white text-xs text-center truncate w-full font-medium px-1">{{ img.name }}</span>
              <span class="text-surface-300 text-xs">{{ img.sizeFormatted }}</span>
            </div>
          </div>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
          <div v-if="lightbox.open" class="fixed inset-0 z-[9999] bg-black/95 flex flex-col" @keydown.esc="closeLightbox" tabindex="0" ref="lightboxEl">
            <!-- Top bar -->
            <div class="flex items-center justify-between px-6 py-4 shrink-0">
              <div>
                <p class="text-white font-semibold">{{ currentImage?.name }}</p>
                <p class="text-surface-400 text-xs">{{ currentImage?.sizeFormatted }} · {{ lightbox.index + 1 }} / {{ images.length }}</p>
              </div>
              <div class="flex items-center gap-3">
                <a :href="imageUrl(currentImage)" :download="currentImage?.name"
                  class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-surface-800 text-surface-300 hover:text-white text-sm transition-colors">
                  <Download class="w-4 h-4" /> {{ $t('gallery.download') }}
                </a>
                <button @click="closeLightbox" class="p-2 rounded-lg bg-surface-800 text-surface-400 hover:text-white transition-colors">
                  <X class="w-5 h-5" />
                </button>
              </div>
            </div>
            <!-- Image area -->
            <div class="flex-1 flex items-center justify-center relative px-16 min-h-0">
              <button @click="prevImage" class="absolute left-4 p-3 rounded-full bg-surface-800/80 text-white hover:bg-surface-700 transition-colors z-10">
                <ChevronLeft class="w-6 h-6" />
              </button>
              <img
                :src="imageUrl(currentImage)"
                :alt="currentImage?.name"
                class="max-h-full max-w-full object-contain rounded-lg shadow-2xl select-none"
                @keydown.left="prevImage" @keydown.right="nextImage"
              />
              <button @click="nextImage" class="absolute right-4 p-3 rounded-full bg-surface-800/80 text-white hover:bg-surface-700 transition-colors z-10">
                <ChevronRight class="w-6 h-6" />
              </button>
            </div>
            <!-- Thumbnails strip -->
            <div class="flex gap-2 px-6 py-4 overflow-x-auto shrink-0">
              <div v-for="(img, idx) in images" :key="img.name" @click="lightbox.index = idx"
                class="w-14 h-14 rounded-lg overflow-hidden border-2 shrink-0 cursor-pointer transition-all"
                :class="idx === lightbox.index ? 'border-violet-500 scale-110' : 'border-surface-700 opacity-50 hover:opacity-100'">
                <img :src="imageUrl(img)" :alt="img.name" class="w-full h-full object-cover" loading="lazy" />
              </div>
            </div>
          </div>
        </Teleport>

      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { useConnectionStore } from '@/stores/connection'
import { Image as ImageIcon, ImageOff, LayoutGrid, Grid3x3, Maximize2, X, ChevronLeft, ChevronRight, Download, Home as HomeIcon, RefreshCw as RefreshCwIcon } from 'lucide-vue-next'

const IMAGE_EXTS = new Set(['jpg','jpeg','png','gif','webp','svg','bmp','ico','avif','tiff','tif'])

export default {
  name: 'GalleryPage',
  components: { AppLayout, ImageIcon, ImageOff, LayoutGrid, Grid3x3, Maximize2, X, ChevronLeft, ChevronRight, Download, HomeIcon, RefreshCwIcon },
  data() {
    return {
      connectionStore: useConnectionStore(),
      gridSize: 'sm',
      navigating: false,
      lightbox: { open: false, index: 0 }
    }
  },
  computed: {
    currentPath() { return this.connectionStore.currentPath || '/' },
    pathSegments() {
      const parts = this.currentPath.split('/').filter(Boolean)
      let accum = ''
      return parts.map(p => { accum += '/' + p; return { name: p, path: accum } })
    },
    subfolders() {
      return (this.connectionStore.remoteFiles || []).filter(f => f.isDirectory && f.name !== '.' && f.name !== '..')
    },
    images() {
      return (this.connectionStore.remoteFiles || []).filter(f => {
        if (f.isDirectory) return false
        const ext = (f.name || '').split('.').pop().toLowerCase()
        return IMAGE_EXTS.has(ext)
      })
    },
    currentImage() {
      return this.images[this.lightbox.index] || null
    }
  },
  mounted() {
    window.addEventListener('keydown', this.handleKey)
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.handleKey)
  },
  methods: {
    imageUrl(img) {
      if (!img) return ''
      const API_BASE = import.meta.env.VITE_API_URL || '/api'
      const path = (this.connectionStore.currentPath || '/').replace(/\/$/, '') + '/' + img.name
      return `${API_BASE}/download.php?sessionId=${this.connectionStore.sessionId}&path=${encodeURIComponent(path)}&inline=1`
    },
    async navigateTo(path) {
      this.navigating = true
      this.closeLightbox()
      await this.connectionStore.listRemotePath(path)
      this.navigating = false
    },
    async navigateUp() {
      const parts = this.currentPath.split('/').filter(Boolean)
      parts.pop()
      await this.navigateTo('/' + parts.join('/') || '/')
    },
    async refreshDir() { await this.navigateTo(this.currentPath) },
    openLightbox(idx) {
      this.lightbox = { open: true, index: idx }
      this.$nextTick(() => { this.$refs.lightboxEl?.focus() })
    },
    closeLightbox() { this.lightbox.open = false },
    prevImage() { this.lightbox.index = (this.lightbox.index - 1 + this.images.length) % this.images.length },
    nextImage() { this.lightbox.index = (this.lightbox.index + 1) % this.images.length },
    handleKey(e) {
      if (!this.lightbox.open) return
      if (e.key === 'ArrowLeft') this.prevImage()
      else if (e.key === 'ArrowRight') this.nextImage()
      else if (e.key === 'Escape') this.closeLightbox()
    }
  }
}
</script>

<style scoped>
.img-error .img-error-content { display: flex !important; }
</style>
