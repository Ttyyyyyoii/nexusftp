<template>
  <AppLayout>
    <div class="h-full overflow-y-auto bg-surface-50 dark:bg-surface-950 p-6">
      <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-surface-900 dark:text-white">{{ $t('nav.dashboard') }}</h1>
            <p class="text-surface-500 dark:text-surface-400 mt-1">
              {{ connectionStore.isConnected ? `Connecté à ${connectionStore.connectionLabel}` : 'Aucune connexion active' }}
            </p>
          </div>
          <button @click="refreshStats" :disabled="!connectionStore.isConnected || loading" class="p-2 rounded-xl bg-surface-200 dark:bg-surface-800 text-surface-700 dark:text-surface-300 hover:bg-primary-100 hover:text-primary-600 transition-colors">
            <RefreshCw class="w-5 h-5" :class="{ 'animate-spin': loading }" />
          </button>
        </div>

        <!-- Not connected state -->
        <div v-if="!connectionStore.isConnected" class="flex flex-col items-center justify-center h-64 bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm">
          <Server class="w-12 h-12 text-surface-300 dark:text-surface-600 mb-4" />
          <p class="text-surface-600 dark:text-surface-400 text-center max-w-sm mb-6">
            Connectez-vous à un serveur pour afficher les statistiques et l'état du système.
          </p>
          <router-link to="/connect" class="btn-primary text-sm px-6 py-2.5">
            Se connecter
          </router-link>
        </div>

        <!-- Dashboard Content -->
        <div v-else class="space-y-6">
          
          <!-- Key Metrics -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-6 border border-surface-200 dark:border-surface-800 shadow-sm flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center shrink-0">
                <HardDrive class="w-6 h-6 text-primary-600 dark:text-primary-400" />
              </div>
              <div>
                <p class="text-sm font-medium text-surface-500 dark:text-surface-400">Espace Total (Estimé)</p>
                <h3 class="text-2xl font-bold text-surface-900 dark:text-white mt-1">250 GB</h3>
              </div>
            </div>
            
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-6 border border-surface-200 dark:border-surface-800 shadow-sm flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                <Activity class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
              </div>
              <div>
                <p class="text-sm font-medium text-surface-500 dark:text-surface-400">Bande Passante (Mois)</p>
                <h3 class="text-2xl font-bold text-surface-900 dark:text-white mt-1">12.4 GB</h3>
              </div>
            </div>

            <div class="bg-white dark:bg-surface-900 rounded-2xl p-6 border border-surface-200 dark:border-surface-800 shadow-sm flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                <FileBox class="w-6 h-6 text-amber-600 dark:text-amber-400" />
              </div>
              <div>
                <p class="text-sm font-medium text-surface-500 dark:text-surface-400">Fichiers Scannés</p>
                <h3 class="text-2xl font-bold text-surface-900 dark:text-white mt-1">{{ stats.totalFiles || '---' }}</h3>
              </div>
            </div>

            <div class="bg-white dark:bg-surface-900 rounded-2xl p-6 border border-surface-200 dark:border-surface-800 shadow-sm flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center shrink-0">
                <Shield class="w-6 h-6 text-rose-600 dark:text-rose-400" />
              </div>
              <div>
                <p class="text-sm font-medium text-surface-500 dark:text-surface-400">Protocole</p>
                <h3 class="text-2xl font-bold text-surface-900 dark:text-white mt-1 uppercase">{{ connectionStore.type }}</h3>
              </div>
            </div>
          </div>

          <!-- Main Grid -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- File Types Breakdown -->
            <div class="lg:col-span-2 bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm p-6">
              <h2 class="text-lg font-bold text-surface-900 dark:text-white mb-6">Répartition par type (Dossier courant)</h2>
              <div v-if="loading" class="h-48 flex items-center justify-center">
                <Loader2 class="w-8 h-8 text-primary-500 animate-spin" />
              </div>
              <div v-else class="space-y-4">
                <div v-for="type in stats.types" :key="type.name" class="relative">
                  <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-surface-700 dark:text-surface-300">{{ type.label }}</span>
                    <span class="text-xs text-surface-500 font-mono">{{ formatSize(type.size) }} ({{ type.percentage }}%)</span>
                  </div>
                  <div class="w-full bg-surface-100 dark:bg-surface-800 rounded-full h-2.5 overflow-hidden">
                    <div class="h-2.5 rounded-full transition-all duration-1000 ease-out" :class="type.colorClass" :style="{ width: type.percentage + '%' }"></div>
                  </div>
                </div>
                <div v-if="stats.types && stats.types.length === 0" class="text-center text-surface-500 py-8">
                  Aucun fichier dans le dossier courant.
                </div>
              </div>
            </div>

            <!-- Server Info -->
            <div class="bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm p-6">
              <h2 class="text-lg font-bold text-surface-900 dark:text-white mb-6">Infos Serveur</h2>
              <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-surface-100 dark:border-surface-800">
                  <span class="text-sm text-surface-500">Hôte</span>
                  <span class="text-sm font-medium text-surface-900 dark:text-white">{{ connectionStore.host }}</span>
                </div>
                <div class="flex items-center justify-between pb-3 border-b border-surface-100 dark:border-surface-800">
                  <span class="text-sm text-surface-500">Port</span>
                  <span class="text-sm font-medium text-surface-900 dark:text-white">{{ connectionStore.port }}</span>
                </div>
                <div class="flex items-center justify-between pb-3 border-b border-surface-100 dark:border-surface-800">
                  <span class="text-sm text-surface-500">Utilisateur</span>
                  <span class="text-sm font-medium text-surface-900 dark:text-white">{{ connectionStore.username }}</span>
                </div>
                <div class="flex items-center justify-between pb-3 border-b border-surface-100 dark:border-surface-800">
                  <span class="text-sm text-surface-500">Mode Passif</span>
                  <span class="text-sm font-medium text-surface-900 dark:text-white">
                    <span v-if="connectionStore.passive" class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-xs">Actif</span>
                    <span v-else class="px-2 py-0.5 rounded bg-surface-200 text-surface-700 dark:bg-surface-700 dark:text-surface-300 text-xs">Inactif</span>
                  </span>
                </div>
                <div class="flex items-center justify-between pt-2">
                  <span class="text-sm text-surface-500">Ping</span>
                  <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">12 ms</span>
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
import { RefreshCw, Server, HardDrive, Activity, Box as FileBox, Shield, Loader2 } from 'lucide-vue-next'

export default {
  name: 'DashboardPage',
  components: { AppLayout, RefreshCw, Server, HardDrive, Activity, FileBox, Shield, Loader2 },
  data() {
    return {
      connectionStore: useConnectionStore(),
      loading: false,
      stats: {
        totalFiles: 0,
        totalSize: 0,
        types: []
      }
    }
  },
  mounted() {
    if (this.connectionStore.isConnected) {
      this.refreshStats()
    }
  },
  methods: {
    formatSize(bytes) {
      if (!bytes) return '0 B'
      const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    },
    async refreshStats() {
      if (!this.connectionStore.isConnected) return
      this.loading = true
      
      // Simulate network delay for effect
      await new Promise(resolve => setTimeout(resolve, 800))
      
      const files = this.connectionStore.remoteFiles || []
      let totalSize = 0
      let typesMap = {
        image: { label: 'Images', size: 0, colorClass: 'bg-violet-500' },
        code: { label: 'Code source', size: 0, colorClass: 'bg-amber-500' },
        video: { label: 'Vidéos', size: 0, colorClass: 'bg-rose-500' },
        archive: { label: 'Archives', size: 0, colorClass: 'bg-orange-500' },
        document: { label: 'Documents', size: 0, colorClass: 'bg-blue-500' },
        other: { label: 'Autres', size: 0, colorClass: 'bg-surface-400' }
      }
      
      let fileCount = 0
      
      files.forEach(f => {
        if (!f.isDirectory) {
          totalSize += f.size || 0
          fileCount++
          
          const type = f.type || 'other'
          if (typesMap[type]) {
            typesMap[type].size += f.size || 0
          } else {
            typesMap.other.size += f.size || 0
          }
        }
      })
      
      const typesArray = []
      for (const [key, data] of Object.entries(typesMap)) {
        if (data.size > 0 || totalSize === 0) {
          const percentage = totalSize > 0 ? Math.round((data.size / totalSize) * 100) : 0
          typesArray.push({ ...data, percentage })
        }
      }
      
      // Sort by size desc
      typesArray.sort((a, b) => b.size - a.size)
      
      this.stats = {
        totalFiles: fileCount,
        totalSize,
        types: typesArray
      }
      
      this.loading = false
    }
  }
}
</script>
