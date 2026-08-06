<template>
  <AppLayout>
    <div class="h-full overflow-y-auto bg-surface-50 dark:bg-surface-950 p-4 md:p-6">
      <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-surface-900 dark:text-white flex items-center gap-3">
              <LayoutDashboard class="w-6 h-6 text-primary-500" />
              {{ $t('nav.dashboard') }}
            </h1>
            <p class="text-surface-500 dark:text-surface-400 mt-1 text-sm">
              {{ connectionStore.isConnected ? `Connecté à ${connectionStore.connectionLabel}` : 'Aucune connexion active' }}
            </p>
          </div>
          <button @click="refreshStats" :disabled="!connectionStore.isConnected || loading"
            class="flex items-center gap-2 px-4 py-2 rounded-xl btn-secondary text-sm">
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
            Actualiser
          </button>
        </div>

        <!-- Not connected -->
        <div v-if="!connectionStore.isConnected" class="flex flex-col items-center justify-center h-72 bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm">
          <Server class="w-16 h-16 text-surface-300 dark:text-surface-600 mb-4" />
          <p class="text-surface-600 dark:text-surface-400 text-center max-w-sm mb-6">
            Connectez-vous à un serveur FTP ou SFTP pour afficher les statistiques en temps réel.
          </p>
          <router-link to="/connect" class="btn-primary text-sm px-6 py-2.5">
            Se connecter
          </router-link>
        </div>

        <!-- Content -->
        <div v-else-if="loading" class="space-y-6">
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="h-28 bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 loading-shimmer" />
          </div>
          <div class="h-64 bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 loading-shimmer" />
        </div>

        <div v-else class="space-y-6">
          
          <!-- Metric Cards -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Disk total -->
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-5 border border-surface-200 dark:border-surface-800 shadow-sm">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                  <HardDrive class="w-5 h-5 text-primary-500" />
                </div>
                <span class="text-xs font-semibold text-surface-500 uppercase tracking-wider">Espace Total</span>
              </div>
              <p class="text-2xl font-bold text-surface-900 dark:text-white">
                {{ stats.diskTotal ? formatSize(stats.diskTotal) : 'N/A' }}
              </p>
              <p class="text-xs text-surface-400 mt-1">{{ stats.diskTotal ? 'Disque serveur' : 'Non disponible via FTP' }}</p>
            </div>

            <!-- Disk used -->
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-5 border border-surface-200 dark:border-surface-800 shadow-sm">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center">
                  <PieChart class="w-5 h-5 text-rose-500" />
                </div>
                <span class="text-xs font-semibold text-surface-500 uppercase tracking-wider">Utilisé</span>
              </div>
              <p class="text-2xl font-bold text-surface-900 dark:text-white">
                {{ stats.diskUsed ? formatSize(stats.diskUsed) : 'N/A' }}
              </p>
              <p class="text-xs text-surface-400 mt-1">{{ stats.diskUsed && stats.diskTotal ? usagePercent + '% du disque' : 'Non disponible via FTP' }}</p>
            </div>

            <!-- Disk free -->
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-5 border border-surface-200 dark:border-surface-800 shadow-sm">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                  <Database class="w-5 h-5 text-emerald-500" />
                </div>
                <span class="text-xs font-semibold text-surface-500 uppercase tracking-wider">Libre</span>
              </div>
              <p class="text-2xl font-bold text-surface-900 dark:text-white">
                {{ stats.diskFree ? formatSize(stats.diskFree) : 'N/A' }}
              </p>
              <p class="text-xs text-surface-400 mt-1">{{ stats.diskFree ? 'Espace disponible' : 'Non disponible via FTP' }}</p>
            </div>

            <!-- Protocol -->
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-5 border border-surface-200 dark:border-surface-800 shadow-sm">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                  <Shield class="w-5 h-5 text-amber-500" />
                </div>
                <span class="text-xs font-semibold text-surface-500 uppercase tracking-wider">Protocole</span>
              </div>
              <p class="text-2xl font-bold text-surface-900 dark:text-white uppercase">
                {{ connectionInfo?.type || 'FTP' }}
              </p>
              <p class="text-xs mt-1" :class="connectionInfo?.type === 'sftp' || connectionInfo?.type === 'ftps' || connectionInfo?.type === 'ftpse' ? 'text-emerald-500' : 'text-amber-500'">
                {{ connectionInfo?.type === 'sftp' ? 'Chiffré SSH' : (connectionInfo?.type?.startsWith('ftp') && connectionInfo?.type !== 'ftp' ? 'Chiffré SSL/TLS' : 'Non chiffré') }}
              </p>
            </div>
          </div>

          <!-- Disk usage bar -->
          <div v-if="stats.diskTotal" class="bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm p-6">
            <div class="flex items-center justify-between mb-3">
              <h2 class="font-semibold text-surface-900 dark:text-white text-sm">Utilisation du disque</h2>
              <span class="text-sm font-mono text-surface-600 dark:text-surface-400">{{ formatSize(stats.diskUsed || 0) }} / {{ formatSize(stats.diskTotal) }}</span>
            </div>
            <div class="w-full bg-surface-100 dark:bg-surface-800 rounded-full h-4 overflow-hidden">
              <div class="h-4 rounded-full transition-all duration-1000"
                :class="usagePercent > 90 ? 'bg-rose-500' : usagePercent > 70 ? 'bg-amber-500' : 'bg-emerald-500'"
                :style="{ width: usagePercent + '%' }"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-surface-400">
              <span>{{ usagePercent }}% utilisé</span>
              <span>{{ formatSize(stats.diskFree || 0) }} libre</span>
            </div>
          </div>

          <!-- Main Grid -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- File type breakdown -->
            <div class="lg:col-span-2 bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm p-6">
              <div class="flex items-center justify-between mb-5">
                <h2 class="font-bold text-surface-900 dark:text-white">Répartition des fichiers</h2>
                <span class="text-xs text-surface-400 font-mono">{{ stats.totalFiles }} fichiers — {{ formatSize(stats.totalSize) }}</span>
              </div>
              <div v-if="stats.types && stats.types.length" class="space-y-4">
                <div v-for="type in stats.types" :key="type.name">
                  <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center gap-2">
                      <div class="w-3 h-3 rounded-full" :class="type.dotClass"></div>
                      <span class="text-sm font-medium text-surface-700 dark:text-surface-300">{{ type.label }}</span>
                      <span class="text-xs text-surface-400">({{ type.count }})</span>
                    </div>
                    <span class="text-xs text-surface-500 font-mono">{{ formatSize(type.size) }} · {{ type.percentage }}%</span>
                  </div>
                  <div class="w-full bg-surface-100 dark:bg-surface-800 rounded-full h-2 overflow-hidden">
                    <div class="h-2 rounded-full transition-all duration-1000 ease-out" :class="type.colorClass" :style="{ width: type.percentage + '%' }"></div>
                  </div>
                </div>
              </div>
              <div v-else class="flex flex-col items-center justify-center py-12 text-surface-400 gap-2">
                <FolderOpen class="w-10 h-10 text-surface-300" />
                <p class="text-sm">Aucun fichier dans le répertoire courant.</p>
              </div>
            </div>

            <!-- Server Info panel -->
            <div class="bg-white dark:bg-surface-900 rounded-2xl border border-surface-200 dark:border-surface-800 shadow-sm p-6">
              <h2 class="font-bold text-surface-900 dark:text-white mb-5">Informations serveur</h2>
              <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between py-2 border-b border-surface-100 dark:border-surface-800">
                  <span class="text-surface-500">Hôte</span>
                  <span class="font-medium text-surface-900 dark:text-white font-mono text-xs truncate max-w-[120px]">{{ connectionInfo?.host || '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-surface-100 dark:border-surface-800">
                  <span class="text-surface-500">Port</span>
                  <span class="font-medium text-surface-900 dark:text-white font-mono">{{ connectionInfo?.port || '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-surface-100 dark:border-surface-800">
                  <span class="text-surface-500">Utilisateur</span>
                  <span class="font-medium text-surface-900 dark:text-white font-mono text-xs truncate max-w-[120px]">{{ connectionInfo?.username || '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-surface-100 dark:border-surface-800">
                  <span class="text-surface-500">Logiciel</span>
                  <span class="font-medium text-surface-900 dark:text-white text-xs truncate max-w-[120px]">{{ stats.serverSoftware || 'Inconnu' }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-surface-100 dark:border-surface-800">
                  <span class="text-surface-500">Mode passif</span>
                  <span class="px-2 py-0.5 rounded text-xs font-medium"
                    :class="connectionInfo?.passive !== false ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-surface-200 text-surface-600 dark:bg-surface-700 dark:text-surface-300'">
                    {{ connectionInfo?.passive !== false ? 'Oui' : 'Non' }}
                  </span>
                </div>
                <div class="flex items-center justify-between py-2">
                  <span class="text-surface-500">Répertoire courant</span>
                  <span class="font-medium text-surface-900 dark:text-white font-mono text-xs truncate max-w-[120px]">{{ connectionStore.currentPath || '/' }}</span>
                </div>
              </div>

              <!-- Features badge -->
              <div v-if="stats.features" class="mt-4 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-xl">
                <p class="text-xs font-semibold text-primary-700 dark:text-primary-400 mb-1">Fonctionnalités (FEAT)</p>
                <p class="text-xs text-primary-600 dark:text-primary-500 font-mono break-all">{{ stats.features }}</p>
              </div>
            </div>
          </div>

          <!-- FTP note -->
          <div v-if="connectionInfo?.type === 'ftp'" class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl flex items-start gap-3">
            <AlertCircle class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
            <p class="text-sm text-amber-700 dark:text-amber-400">
              <strong>Note :</strong> Le protocole FTP classique ne permet pas d'accéder aux informations de disque du serveur. 
              Pour des statistiques complètes (espace disque, logiciel système), connectez-vous en <strong>SFTP</strong> (SSH).
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { useConnectionStore } from '@/stores/connection'
import { LayoutDashboard, RefreshCw, Server, HardDrive, PieChart, Database, Shield, FolderOpen, AlertCircle } from 'lucide-vue-next'

export default {
  name: 'DashboardPage',
  components: { AppLayout, LayoutDashboard, RefreshCw, Server, HardDrive, PieChart, Database, Shield, FolderOpen, AlertCircle },
  data() {
    return {
      connectionStore: useConnectionStore(),
      loading: false,
      stats: {
        diskTotal: null,
        diskFree: null,
        diskUsed: null,
        serverSoftware: null,
        features: null,
        totalFiles: 0,
        totalSize: 0,
        types: []
      }
    }
  },
  computed: {
    connectionInfo() {
      return this.connectionStore.connectionInfo
    },
    usagePercent() {
      if (!this.stats.diskTotal || !this.stats.diskUsed) return 0
      return Math.round((this.stats.diskUsed / this.stats.diskTotal) * 100)
    }
  },
  mounted() {
    if (this.connectionStore.isConnected) this.refreshStats()
  },
  methods: {
    formatSize(bytes) {
      if (!bytes || bytes === 0) return '0 B'
      const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    },
    async refreshStats() {
      if (!this.connectionStore.isConnected) return
      this.loading = true

      try {
        const API_BASE = import.meta.env.VITE_API_URL || '/api'
        const res = await fetch(`${API_BASE}/dashboard.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ sessionId: this.connectionStore.sessionId })
        })
        const data = await res.json()
        if (data.success) {
          Object.assign(this.stats, data.stats)
        }
      } catch (e) {
        // ignore network errors, keep showing what we have
      }

      // Compute file type stats from remote files
      const files = this.connectionStore.remoteFiles || []
      const typesMap = {
        image:      { label: 'Images',       dotClass: 'bg-violet-500', colorClass: 'bg-violet-500', count: 0, size: 0 },
        code:       { label: 'Code',          dotClass: 'bg-amber-500',  colorClass: 'bg-amber-500',  count: 0, size: 0 },
        video:      { label: 'Vidéos',        dotClass: 'bg-rose-500',   colorClass: 'bg-rose-500',   count: 0, size: 0 },
        archive:    { label: 'Archives',      dotClass: 'bg-orange-500', colorClass: 'bg-orange-500', count: 0, size: 0 },
        document:   { label: 'Documents',     dotClass: 'bg-blue-500',   colorClass: 'bg-blue-500',   count: 0, size: 0 },
        spreadsheet:{ label: 'Tableurs',      dotClass: 'bg-emerald-500',colorClass: 'bg-emerald-500',count: 0, size: 0 },
        audio:      { label: 'Audio',         dotClass: 'bg-pink-500',   colorClass: 'bg-pink-500',   count: 0, size: 0 },
        text:       { label: 'Texte',         dotClass: 'bg-sky-500',    colorClass: 'bg-sky-500',    count: 0, size: 0 },
        file:       { label: 'Autres',        dotClass: 'bg-surface-400',colorClass: 'bg-surface-400',count: 0, size: 0 }
      }

      let totalSize = 0, totalFiles = 0
      files.forEach(f => {
        if (!f.isDirectory) {
          totalFiles++
          const s = f.size || 0
          totalSize += s
          const t = f.type || 'file'
          if (typesMap[t]) { typesMap[t].count++; typesMap[t].size += s }
          else { typesMap.file.count++; typesMap.file.size += s }
        }
      })

      const typesArray = Object.entries(typesMap)
        .filter(([, v]) => v.count > 0)
        .map(([, v]) => ({ ...v, percentage: totalSize > 0 ? Math.round((v.size / totalSize) * 100) : 0 }))
        .sort((a, b) => b.size - a.size)

      this.stats.totalFiles = totalFiles
      this.stats.totalSize = totalSize
      this.stats.types = typesArray

      this.loading = false
    }
  }
}
</script>
