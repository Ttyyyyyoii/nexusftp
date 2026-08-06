<template>
  <AppLayout>
    <div class="h-full overflow-y-auto bg-surface-50 dark:bg-surface-950 p-4 md:p-6">
      <div class="max-w-5xl mx-auto space-y-6">

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
          <div class="text-xs text-surface-400 font-mono bg-surface-100 dark:bg-surface-800 px-3 py-1.5 rounded-lg">
            {{ connectionStore.currentPath || '/' }}
          </div>
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
                        <span class="font-medium text-surface-800 dark:text-surface-200 truncate max-w-[180px]">{{ file.name }}</span>
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
import { ShieldCheck, ShieldOff, Loader2, Check, Lock, FolderOpen, AlertCircle } from 'lucide-vue-next'

function FolderIcon(props, { slots }) {
  return h('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', ...props }, [
    h('path', { d: 'M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z' })
  ])
}
function FileIcon(props, { slots }) {
  return h('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', ...props }, [
    h('path', { d: 'M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z' }),
    h('polyline', { points: '13 2 13 9 20 9' })
  ])
}

import { h } from 'vue'

export default {
  name: 'PermissionsPage',
  components: { AppLayout, ShieldCheck, ShieldOff, Loader2, Check, Lock, FolderOpen, AlertCircle, FolderIcon, FileIcon },
  data() {
    return {
      connectionStore: useConnectionStore(),
      files: [],
      loading: false
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
          setTimeout(() => { file.success = false }, 2000)
        }
      } catch (e) {}
      file.applying = false
    }
  },
  watch: {
    'connectionStore.remoteFiles'() { if (this.connectionStore.isConnected) this.loadFiles() },
    'connectionStore.currentPath'() { if (this.connectionStore.isConnected) this.loadFiles() }
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
