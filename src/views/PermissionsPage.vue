<template>
  <AppLayout>
    <div class="h-full overflow-y-auto bg-surface-50 dark:bg-surface-950 p-4 md:p-6 flex flex-col">
      <div class="max-w-7xl mx-auto w-full space-y-6 flex-1 flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-surface-900 dark:text-white flex items-center gap-3">
              <ShieldCheck class="w-6 h-6 text-primary-500" />
              {{ $t('nav.permissions') }}
            </h1>
            <p class="text-surface-500 dark:text-surface-400 mt-1 text-sm">
              {{ $t('permissions.subtitle') }}
            </p>
          </div>
        </div>

        <!-- Folder navigation bar -->
        <div v-if="connectionStore.isConnected" class="flex items-center gap-2 bg-white dark:bg-surface-900 border border-surface-200 dark:border-surface-800 rounded-xl px-3 py-2 shrink-0">
          <button @click="navigateUp" :disabled="currentPath === '/'" class="p-1.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 disabled:opacity-30 transition-colors">
            <ChevronLeft class="w-4 h-4 text-surface-500 dark:text-surface-400" />
          </button>
          <div class="flex items-center gap-1 flex-1 overflow-x-auto">
            <button @click="navigateTo('/')" class="flex items-center gap-1 text-xs text-surface-500 hover:text-primary-500 transition-colors shrink-0">
              <Home class="w-3.5 h-3.5" />
            </button>
            <span v-for="(seg, i) in pathSegments" :key="i" class="flex items-center gap-1 shrink-0">
              <ChevronRight class="w-3 h-3 text-surface-400 dark:text-surface-600" />
              <button @click="navigateTo(seg.path)" class="text-xs font-medium transition-colors" :class="i === pathSegments.length - 1 ? 'text-surface-900 dark:text-white' : 'text-surface-500 dark:text-surface-400 hover:text-primary-500'">{{ seg.name }}</button>
            </span>
          </div>
          <!-- Subfolder list -->
          <div class="flex items-center gap-1 ml-2">
            <button v-for="folder in subfolders.slice(0,4)" :key="folder.name" @click="navigateTo(currentPath.replace(/\/$/,'') + '/' + folder.name)"
              class="px-2 py-1 text-xs bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-300 hover:bg-primary-50 dark:hover:bg-primary-900/40 hover:text-primary-600 dark:hover:text-primary-300 rounded-lg transition-colors truncate max-w-[80px]">
              📁 {{ folder.name }}
            </button>
            <span v-if="subfolders.length > 4" class="text-xs text-surface-500">+{{ subfolders.length - 4 }}</span>
          </div>
          <button @click="refreshDir" class="p-1.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors">
            <RefreshCw class="w-3.5 h-3.5 text-surface-500 dark:text-surface-400" :class="navigating ? 'animate-spin' : ''" />
          </button>
        </div>

        <!-- Not connected -->
        <div v-if="!connectionStore.isConnected" class="flex flex-col items-center justify-center h-72 bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm">
          <ShieldOff class="w-14 h-14 text-surface-300 dark:text-surface-600 mb-4" />
          <p class="text-surface-500 text-center max-w-xs mb-4">{{ $t('permissions.notConnected') }}</p>
          <router-link to="/connect" class="btn-primary text-sm px-5 py-2">{{ $t('dashboard.connectBtn') }}</router-link>
        </div>

        <div v-else class="space-y-4">

          <!-- File list -->
          <div class="bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-surface-100 dark:border-surface-800 flex items-center justify-between">
              <h2 class="font-semibold text-surface-900 dark:text-white text-sm">{{ $t('permissions.files') }}</h2>
              <span class="text-xs text-surface-400">{{ files.length }} {{ $t('permissions.items') }}</span>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="p-8 text-center">
              <Loader2 class="w-8 h-8 animate-spin text-primary-500 mx-auto mb-2" />
              <p class="text-sm text-surface-400">{{ $t('permissions.loading') }}</p>
            </div>

            <!-- Table -->
            <div v-else-if="files.length" class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="bg-surface-50 dark:bg-surface-800/50 text-left">
                    <th class="px-4 py-3 text-xs font-semibold text-surface-500 uppercase tracking-wider">{{ $t('permissions.name') }}</th>
                    <th class="px-4 py-3 text-xs font-semibold text-surface-500 uppercase tracking-wider">{{ $t('permissions.currentPerms') }}</th>
                    <th class="px-4 py-3 text-xs font-semibold text-surface-500 uppercase tracking-wider text-center">{{ $t('permissions.owner') }}</th>
                    <th class="px-4 py-3 text-xs font-semibold text-surface-500 uppercase tracking-wider text-center">{{ $t('permissions.group') }}</th>
                    <th class="px-4 py-3 text-xs font-semibold text-surface-500 uppercase tracking-wider text-center">{{ $t('permissions.others') }}</th>
                    <th class="px-4 py-3 text-xs font-semibold text-surface-500 uppercase tracking-wider">{{ $t('permissions.octal') }}</th>
                    <th class="px-4 py-3"></th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                  <tr v-for="file in files" :key="file.name"
                    class="hover:bg-surface-50 dark:hover:bg-surface-800/40 transition-colors">
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2">
                        <component :is="file.isDirectory ? 'FolderIcon' : 'FileIcon'" class="w-4 h-4 shrink-0" :class="file.isDirectory ? 'text-amber-500' : 'text-surface-400'" />
                        <span 
                          class="font-medium truncate max-w-[180px] transition-colors"
                          :class="file.isDirectory ? 'text-primary-600 dark:text-primary-400 cursor-pointer hover:underline' : 'text-surface-800 dark:text-surface-200'"
                          @click="file.isDirectory && navigateTo(currentPath.replace(/\/$/,'') + '/' + file.name)"
                        >{{ file.name }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-3">
                      <span class="font-mono text-xs" :class="permColor(file.permOctal)">{{ file.permissions || '???' }}</span>
                    </td>
                    <!-- Owner checkboxes -->
                    <td class="px-4 py-3">
                      <div class="flex justify-center gap-2">
                        <label v-for="bit in ['r','w','x']" :key="'o'+bit" class="perm-check" :title="bit">
                          <input type="checkbox" v-model="file.perm.owner[bit]" @change="recalcOctal(file)" class="sr-only" />
                          <span class="perm-bit" :class="file.perm.owner[bit] ? 'active' : ''">{{ bit }}</span>
                        </label>
                      </div>
                    </td>
                    <!-- Group checkboxes -->
                    <td class="px-4 py-3">
                      <div class="flex justify-center gap-2">
                        <label v-for="bit in ['r','w','x']" :key="'g'+bit" class="perm-check" :title="bit">
                          <input type="checkbox" v-model="file.perm.group[bit]" @change="recalcOctal(file)" class="sr-only" />
                          <span class="perm-bit" :class="file.perm.group[bit] ? 'active' : ''">{{ bit }}</span>
                        </label>
                      </div>
                    </td>
                    <!-- Others checkboxes -->
                    <td class="px-4 py-3">
                      <div class="flex justify-center gap-2">
                        <label v-for="bit in ['r','w','x']" :key="'a'+bit" class="perm-check" :title="bit">
                          <input type="checkbox" v-model="file.perm.others[bit]" @change="recalcOctal(file)" class="sr-only" />
                          <span class="perm-bit" :class="file.perm.others[bit] ? 'active' : ''">{{ bit }}</span>
                        </label>
                      </div>
                    </td>
                    <td class="px-4 py-3">
                      <span class="font-mono text-sm font-bold" :class="permColor(file.permOctal)">{{ file.permOctal }}</span>
                    </td>
                    <td class="px-4 py-3">
                      <button @click="applyChmod(file)"
                        :disabled="file.applying || file.permOctal === file.originalOctal"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                        :class="file.permOctal !== file.originalOctal ? 'bg-primary-500 text-white hover:bg-primary-600' : 'bg-surface-100 dark:bg-surface-800 text-surface-400 cursor-not-allowed'">
                        <Loader2 v-if="file.applying" class="w-3 h-3 animate-spin" />
                        <Check v-else-if="file.success" class="w-3 h-3" />
                        <Lock v-else class="w-3 h-3" />
                        {{ file.applying ? $t('permissions.applying') : file.success ? $t('permissions.applied') : $t('permissions.apply') }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="p-10 text-center text-surface-400">
              <FolderOpen class="w-10 h-10 mx-auto mb-2 text-surface-300" />
              <p class="text-sm">{{ $t('permissions.empty') }}</p>
            </div>
          </div>

          <!-- FTP note -->
          <div v-if="connectionStore.connectionInfo?.type === 'ftp'" class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl flex gap-3">
            <AlertCircle class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
            <p class="text-sm text-amber-700 dark:text-amber-400">{{ $t('permissions.ftpNote') }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { useConnectionStore } from '@/stores/connection'
import { ShieldCheck, ShieldOff, Loader2, Check, Lock, FolderOpen as FolderIcon, File as FileIcon, AlertCircle, Home, ChevronLeft, ChevronRight, RefreshCw, FolderOpen } from 'lucide-vue-next'

export default {
  name: 'PermissionsPage',
  components: { AppLayout, ShieldCheck, ShieldOff, Loader2, Check, Lock, FolderIcon, FileIcon, AlertCircle, Home, ChevronLeft, ChevronRight, RefreshCw },
  data() {
    return {
      connectionStore: useConnectionStore(),
      files: [],
      loading: false,
      navigating: false
    }
  },
  computed: {
    remoteFiles() { return this.connectionStore.remoteFiles },
    currentPath() { return this.connectionStore.currentPath || '/' },
    pathSegments() {
      const parts = this.currentPath.split('/').filter(Boolean)
      let p = ''
      return parts.map(part => {
        p += '/' + part
        return { name: part, path: p }
      })
    },
    subfolders() {
      return this.connectionStore.remoteFiles?.filter(f => f.isDirectory && f.name !== '.' && f.name !== '..') || []
    }
  },
  mounted() {
    if (this.connectionStore.isConnected) this.loadFiles()
  },
  methods: {
    permColor(octal) {
      const n = parseInt(octal, 10)
      if (n >= 777) return 'text-rose-500'
      if (n >= 755) return 'text-emerald-500'
      if (n >= 644) return 'text-primary-500'
      return 'text-surface-500'
    },
    parsePerms(perms) {
      // perms like "rwxr-xr-x"  (9 chars)
      const p = (perms || '---------').replace(/^[d\-lbcsp]/, '').padEnd(9, '-')
      return {
        owner:  { r: p[0]==='r', w: p[1]==='w', x: p[2]==='x' },
        group:  { r: p[3]==='r', w: p[4]==='w', x: p[5]==='x' },
        others: { r: p[6]==='r', w: p[7]==='w', x: p[8]==='x' },
      }
    },
    octalFromPerm(perm) {
      const calc = (g) => (g.r ? 4 : 0) + (g.w ? 2 : 0) + (g.x ? 1 : 0)
      return `${calc(perm.owner)}${calc(perm.group)}${calc(perm.others)}`
    },
    recalcOctal(file) {
      file.permOctal = this.octalFromPerm(file.perm)
    },
    showToast(title, type) { 
      window.dispatchEvent(new CustomEvent('show-toast', { detail: { title, type } })) 
    },
    loadFiles() {
      this.loading = true
      const remoteFiles = this.connectionStore.remoteFiles || []
      this.files = remoteFiles
        .filter(f => f.name && f.name !== '.' && f.name !== '..')
        .map(f => {
          const perm = this.parsePerms(f.permissions)
          const octal = this.octalFromPerm(perm)
          return {
            ...f,
            perm,
            permOctal: octal,
            originalOctal: octal,
            applying: false,
            success: false
          }
        })
      this.loading = false
    },
    async navigateTo(path) {
      if (path === this.connectionStore.currentPath) return
      this.navigating = true
      await this.connectionStore.listRemotePath(path)
      this.navigating = false
    },
    async navigateUp() {
      if (this.currentPath === '/') return
      const parent = this.currentPath.split('/').slice(0, -1).join('/') || '/'
      await this.navigateTo(parent)
    },
    async refreshDir() {
      this.navigating = true
      await this.connectionStore.listRemotePath(this.currentPath)
      this.navigating = false
    },
    async applyChmod(file) {
      file.applying = true
      file.success = false
      try {
        const API_BASE = import.meta.env.VITE_API_URL || '/api'
        const path = (this.connectionStore.currentPath || '/').replace(/\/$/, '') + '/' + file.name
        const res = await fetch(`${API_BASE}/chmod.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ sessionId: this.connectionStore.sessionId, path, mode: file.permOctal })
        })
        const data = await res.json()
        if (data.success) {
          file.originalOctal = file.permOctal
          file.success = true
          this.showToast(this.$t('permissions.applied'), 'success')
          setTimeout(() => { file.success = false }, 2000)
        } else {
          this.showToast(data.message || 'Error applying permissions', 'error')
        }
      } catch (e) {
        this.showToast('Network error applying permissions', 'error')
      }
      file.applying = false
    }
  },
  watch: {
    remoteFiles: {
      handler() { if (this.connectionStore.isConnected) this.loadFiles() },
      deep: true
    },
    currentPath() {
      if (this.connectionStore.isConnected) this.loadFiles()
    }
  }
}
</script>

<style scoped>
.perm-check { cursor: pointer; }
.perm-bit {
  display: inline-flex; align-items: center; justify-content: center;
  width: 22px; height: 22px; border-radius: 4px; font-size: 10px; font-family: monospace;
  font-weight: 700; border: 1px solid;
  @apply border-surface-200 dark:border-surface-700 text-surface-400 bg-surface-50 dark:bg-surface-800 transition-all;
}
.perm-bit.active {
  @apply bg-primary-500 border-primary-500 text-white;
}
.perm-check:hover .perm-bit { @apply bg-primary-100 dark:bg-primary-900/30 border-primary-300; }
</style>
