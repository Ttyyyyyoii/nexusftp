<template>
  <AppLayout>
    <div class="h-full overflow-y-auto bg-surface-50 dark:bg-surface-950 p-4 md:p-6">
      <div class="max-w-6xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-surface-900 dark:text-white flex items-center gap-3">
              <ArrowLeftRight class="w-6 h-6 text-primary-500" />
              {{ $t('nav.sync') }}
            </h1>
            <p class="text-surface-500 dark:text-surface-400 mt-1 text-sm">{{ $t('sync.subtitle') }}</p>
          </div>
        </div>

        <!-- Folder navigation bar -->
        <div v-if="connectionStore.isConnected" class="flex items-center gap-2 bg-white dark:bg-surface-900 border border-surface-200 dark:border-surface-800 rounded-xl px-3 py-2 shadow-sm">
          <button @click="navigateUp" :disabled="currentPath === '/'" class="p-1.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-700 disabled:opacity-30 transition-colors">
            <ChevronLeft class="w-4 h-4 text-surface-500" />
          </button>
          <div class="flex items-center gap-1 flex-1 overflow-x-auto">
            <button @click="navigateTo('/')" class="flex items-center gap-1 text-xs text-surface-500 hover:text-primary-500 transition-colors shrink-0">
              <Home class="w-3.5 h-3.5" />
            </button>
            <span v-for="(seg, i) in pathSegments" :key="i" class="flex items-center gap-1 shrink-0">
              <ChevronRight class="w-3 h-3 text-surface-300" />
              <button @click="navigateTo(seg.path)" class="text-xs font-medium transition-colors" :class="i === pathSegments.length - 1 ? 'text-surface-900 dark:text-white' : 'text-surface-500 hover:text-primary-500'">{{ seg.name }}</button>
            </span>
          </div>
          <!-- Subfolder list -->
          <div class="flex items-center gap-1 ml-2">
            <button v-for="folder in subfolders.slice(0,4)" :key="folder.name" @click="navigateTo(currentPath.replace(/\/$/,'') + '/' + folder.name)"
              class="px-2 py-1 text-xs bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-300 hover:bg-primary-100 dark:hover:bg-primary-900/40 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg transition-colors truncate max-w-[80px]">
              📁 {{ folder.name }}
            </button>
            <span v-if="subfolders.length > 4" class="text-xs text-surface-500">+{{ subfolders.length - 4 }}</span>
          </div>
          <button @click="refreshDir" class="p-1.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors">
            <RefreshCw class="w-3.5 h-3.5 text-surface-400" :class="navigating ? 'animate-spin' : ''" />
          </button>
        </div>

        <!-- Not connected -->
        <div v-if="!connectionStore.isConnected" class="flex flex-col items-center justify-center h-72 bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm">
          <ArrowLeftRight class="w-14 h-14 text-surface-300 dark:text-surface-600 mb-4" />
          <p class="text-surface-500 text-center max-w-xs mb-4">{{ $t('sync.notConnected') }}</p>
          <router-link to="/connect" class="btn-primary text-sm px-5 py-2">{{ $t('dashboard.connectBtn') }}</router-link>
        </div>

        <div v-else class="space-y-6">

          <!-- Steps -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Left: Local files drop zone -->
            <div class="bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm overflow-hidden">
              <div class="p-4 border-b border-surface-100 dark:border-surface-800 flex items-center gap-3">
                <div class="w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-xs font-bold text-primary-600 dark:text-primary-400">1</div>
                <h2 class="font-semibold text-surface-900 dark:text-white text-sm">{{ $t('sync.localFiles') }}</h2>
                <span class="ml-auto text-xs text-surface-400">{{ localFiles.length }} {{ $t('sync.files') }}</span>
              </div>
              <!-- Drop zone -->
              <div
                @dragover.prevent="dragging = true"
                @dragleave="dragging = false"
                @drop.prevent="onDrop"
                @click="$refs.fileInput.click()"
                class="m-4 border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all"
                :class="dragging ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/10' : 'border-surface-200 dark:border-surface-700 hover:border-primary-300 hover:bg-surface-50 dark:hover:bg-surface-800/40'">
                <Upload class="w-10 h-10 mx-auto mb-3" :class="dragging ? 'text-primary-500' : 'text-surface-300 dark:text-surface-600'" />
                <p class="text-sm font-medium text-surface-700 dark:text-surface-300">{{ $t('sync.dropHere') }}</p>
                <p class="text-xs text-surface-400 mt-1">{{ $t('sync.dropHint') }}</p>
                <input ref="fileInput" type="file" multiple class="hidden" @change="onFileSelect" />
              </div>
              <!-- Local file list -->
              <div v-if="localFiles.length" class="px-4 pb-4 space-y-1 max-h-48 overflow-y-auto">
                <div v-for="f in localFiles" :key="f.name" class="flex items-center gap-2 py-1.5 px-2 rounded-lg bg-surface-50 dark:bg-surface-800/50 text-xs">
                  <FileText class="w-3.5 h-3.5 text-surface-400 shrink-0" />
                  <span class="truncate text-surface-700 dark:text-surface-300 flex-1">{{ f.name }}</span>
                  <span class="text-surface-400 font-mono shrink-0">{{ formatSize(f.size) }}</span>
                </div>
              </div>
            </div>

            <!-- Right: Remote files -->
            <div class="bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm overflow-hidden">
              <div class="p-4 border-b border-surface-100 dark:border-surface-800 flex items-center gap-3">
                <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-xs font-bold text-emerald-600 dark:text-emerald-400">2</div>
                <h2 class="font-semibold text-surface-900 dark:text-white text-sm">{{ $t('sync.remoteFiles') }}</h2>
                <span class="text-xs text-surface-400 ml-auto font-mono truncate max-w-[160px]">{{ connectionStore.currentPath || '/' }}</span>
              </div>
              <div class="px-4 py-4 max-h-64 overflow-y-auto space-y-1">
                <div v-if="!remoteFilesFiltered.length" class="text-center py-6 text-surface-400 text-sm">
                  {{ $t('sync.noRemoteFiles') }}
                </div>
                <div v-for="f in remoteFilesFiltered" :key="f.name" class="flex items-center gap-2 py-1.5 px-2 rounded-lg bg-surface-50 dark:bg-surface-800/50 text-xs">
                  <FileText class="w-3.5 h-3.5 text-surface-400 shrink-0" />
                  <span class="truncate text-surface-700 dark:text-surface-300 flex-1">{{ f.name }}</span>
                  <span class="text-surface-400 font-mono shrink-0">{{ f.sizeFormatted }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Compare button -->
          <div class="flex justify-center">
            <button @click="compare" :disabled="!localFiles.length || comparing"
              class="flex items-center gap-2 px-8 py-3 rounded-xl btn-primary text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
              <Loader2 v-if="comparing" class="w-4 h-4 animate-spin" />
              <GitCompare v-else class="w-4 h-4" />
              {{ comparing ? $t('sync.comparing') : $t('sync.compare') }}
            </button>
          </div>

          <!-- Results -->
          <div v-if="results" class="space-y-4">
            <!-- Summary badges -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
              <div class="bg-white dark:bg-surface-900 rounded-xl border border-surface-200 dark:border-surface-800 p-4 text-center">
                <p class="text-2xl font-bold text-emerald-500">{{ results.identical.length }}</p>
                <p class="text-xs text-surface-500 mt-1">{{ $t('sync.identical') }}</p>
              </div>
              <div class="bg-white dark:bg-surface-900 rounded-xl border border-surface-200 dark:border-surface-800 p-4 text-center">
                <p class="text-2xl font-bold text-amber-500">{{ results.different.length }}</p>
                <p class="text-xs text-surface-500 mt-1">{{ $t('sync.different') }}</p>
              </div>
              <div class="bg-white dark:bg-surface-900 rounded-xl border border-surface-200 dark:border-surface-800 p-4 text-center">
                <p class="text-2xl font-bold text-rose-500">{{ results.onlyLocal.length }}</p>
                <p class="text-xs text-surface-500 mt-1">{{ $t('sync.onlyLocal') }}</p>
              </div>
              <div class="bg-white dark:bg-surface-900 rounded-xl border border-surface-200 dark:border-surface-800 p-4 text-center">
                <p class="text-2xl font-bold text-blue-500">{{ results.onlyRemote.length }}</p>
                <p class="text-xs text-surface-500 mt-1">{{ $t('sync.onlyRemote') }}</p>
              </div>
            </div>

            <!-- Detail table -->
            <div class="bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm overflow-hidden">
              <div class="p-4 border-b border-surface-100 dark:border-surface-800 flex items-center justify-between">
                <h2 class="font-bold text-surface-900 dark:text-white text-sm">{{ $t('sync.details') }}</h2>
                <button v-if="uploadable.length" @click="uploadAll" :disabled="uploading"
                  class="flex items-center gap-2 px-4 py-1.5 rounded-lg bg-primary-500 text-white text-xs font-medium hover:bg-primary-600 transition-colors disabled:opacity-50">
                  <Upload class="w-3.5 h-3.5" />
                  {{ uploading ? $t('sync.uploading') : $t('sync.uploadAll', { n: uploadable.length }) }}
                </button>
              </div>
              <div class="divide-y divide-surface-100 dark:divide-surface-800 max-h-72 overflow-y-auto">
                <div v-for="item in allResults" :key="item.name" class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-surface-50 dark:hover:bg-surface-800/40">
                  <span class="w-3 h-3 rounded-full shrink-0" :class="statusDot(item.status)" />
                  <span class="flex-1 truncate font-medium text-surface-800 dark:text-surface-200">{{ item.name }}</span>
                  <span class="text-xs text-surface-400 font-mono shrink-0">{{ item.localSize ? formatSize(item.localSize) : '—' }}</span>
                  <span class="text-xs font-medium px-2 py-0.5 rounded-full shrink-0" :class="statusBadge(item.status)">{{ $t('sync.' + item.status) }}</span>
                  <button v-if="item.status === 'onlyLocal' || item.status === 'different'"
                    @click="uploadFile(item)" :disabled="item.uploading || item.uploaded"
                    class="shrink-0 p-1.5 rounded-lg bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 hover:bg-primary-200 disabled:opacity-40 transition-colors">
                    <Check v-if="item.uploaded" class="w-3.5 h-3.5 text-emerald-500" />
                    <Loader2 v-else-if="item.uploading" class="w-3.5 h-3.5 animate-spin" />
                    <Upload v-else class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { useConnectionStore } from '@/stores/connection'
import { ArrowLeftRight, Upload, FileText, GitCompare, Loader2, Check, ChevronLeft, ChevronRight, Home, RefreshCw } from 'lucide-vue-next'

export default {
  name: 'SyncPage',
  components: { AppLayout, ArrowLeftRight, Upload, FileText, GitCompare, Loader2, Check, ChevronLeft, ChevronRight, Home, RefreshCw },
  data() {
    return {
      connectionStore: useConnectionStore(),
      localFiles: [],
      dragging: false,
      comparing: false,
      uploading: false,
      navigating: false,
      results: null
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
    remoteFilesFiltered() {
      return (this.connectionStore.remoteFiles || []).filter(f => !f.isDirectory)
    },
    allResults() {
      if (!this.results) return []
      return [
        ...this.results.onlyLocal.map(f => ({ ...f, status: 'onlyLocal' })),
        ...this.results.different.map(f => ({ ...f, status: 'different' })),
        ...this.results.identical.map(f => ({ ...f, status: 'identical' })),
        ...this.results.onlyRemote.map(f => ({ ...f, status: 'onlyRemote' }))
      ]
    },
    uploadable() {
      return this.allResults.filter(f => f.status === 'onlyLocal' || f.status === 'different')
    }
  },
  methods: {
    formatSize(bytes) {
      if (!bytes) return '0 B'
      const k = 1024, s = ['B','KB','MB','GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return (bytes / Math.pow(k, i)).toFixed(1) + ' ' + s[i]
    },
    showToast(title, type) {
      window.dispatchEvent(new CustomEvent('show-toast', { detail: { title, type } }))
    },
    async navigateTo(path) {
      this.navigating = true
      this.results = null
      await this.connectionStore.listRemotePath(path)
      this.navigating = false
    },
    async navigateUp() {
      const parts = this.currentPath.split('/').filter(Boolean)
      parts.pop()
      await this.navigateTo('/' + parts.join('/') || '/')
    },
    async refreshDir() {
      await this.navigateTo(this.currentPath)
    },
    onDrop(e) {
      this.dragging = false
      this.addFiles([...e.dataTransfer.files])
    },
    onFileSelect(e) { this.addFiles([...e.target.files]) },
    addFiles(files) {
      const existing = new Set(this.localFiles.map(f => f.name))
      files.forEach(f => { if (!existing.has(f.name)) this.localFiles.push(f) })
      this.results = null
    },
    statusDot(status) {
      return { identical: 'bg-emerald-500', different: 'bg-amber-500', onlyLocal: 'bg-rose-500', onlyRemote: 'bg-blue-500' }[status] || 'bg-surface-400'
    },
    statusBadge(status) {
      return {
        identical: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        different: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        onlyLocal: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
        onlyRemote: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
      }[status] || ''
    },
    compare() {
      this.comparing = true
      const remoteMap = {}
      this.remoteFilesFiltered.forEach(f => { remoteMap[f.name] = f })
      const localMap = {}
      this.localFiles.forEach(f => { localMap[f.name] = f })

      const identical = [], different = [], onlyLocal = [], onlyRemote = []

      this.localFiles.forEach(f => {
        if (remoteMap[f.name]) {
          if (Math.abs(f.size - remoteMap[f.name].size) < 10) {
            identical.push({ name: f.name, localSize: f.size, remoteSize: remoteMap[f.name].size, _file: f, uploading: false, uploaded: false })
          } else {
            different.push({ name: f.name, localSize: f.size, remoteSize: remoteMap[f.name].size, _file: f, uploading: false, uploaded: false })
          }
        } else {
          onlyLocal.push({ name: f.name, localSize: f.size, remoteSize: null, _file: f, uploading: false, uploaded: false })
        }
      })
      this.remoteFilesFiltered.forEach(f => {
        if (!localMap[f.name]) {
          onlyRemote.push({ name: f.name, localSize: null, remoteSize: f.size, uploading: false, uploaded: false })
        }
      })

      this.results = { identical, different, onlyLocal, onlyRemote }
      this.comparing = false
    },
    async uploadFile(item) {
      if (!item._file) return
      item.uploading = true
      try {
        const API_BASE = import.meta.env.VITE_API_URL || '/api'
        const formData = new FormData()
        formData.append('sessionId', this.connectionStore.sessionId)
        formData.append('remotePath', this.connectionStore.currentPath || '/')
        formData.append('remoteName', item._file.name)
        formData.append('file', item._file)
        const res = await fetch(`${API_BASE}/upload.php`, { method: 'POST', credentials: 'include', body: formData })
        const data = await res.json()
        if (data.success) {
          item.uploaded = true
          item.status = 'identical'
          this.showToast(`${item.name} uploaded successfully`, 'success')
          // Refresh remote file listing
          await this.connectionStore.listRemotePath(this.connectionStore.currentPath || '/')
          this.results = null
        } else {
          this.showToast(data.message || 'Upload failed', 'error')
        }
      } catch (e) {
        this.showToast('Network error during upload', 'error')
      }
      item.uploading = false
    },
    async uploadAll() {
      this.uploading = true
      const toUpload = this.allResults.filter(f => (f.status === 'onlyLocal' || f.status === 'different') && !f.uploaded && f._file)
      for (const item of toUpload) await this.uploadFile(item)
      this.uploading = false
    }
  }
}
</script>
