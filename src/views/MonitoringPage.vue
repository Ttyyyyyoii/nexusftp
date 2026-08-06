<template>
  <AppLayout>
    <div class="h-full overflow-y-auto bg-surface-950 p-4 md:p-6">
      <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
              <Activity class="w-6 h-6 text-emerald-400" />
              {{ $t('nav.monitoring') }}
            </h1>
            <p class="text-surface-400 mt-1 text-sm">{{ $t('monitoring.subtitle') }}</p>
          </div>
          <div class="flex items-center gap-3">
            <span v-if="autoRefresh" class="flex items-center gap-1.5 text-xs text-emerald-400 bg-emerald-900/30 border border-emerald-800 px-3 py-1.5 rounded-lg">
              <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
              {{ $t('monitoring.live') }}
            </span>
            <button @click="toggleAutoRefresh"
              class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm border transition-colors"
              :class="autoRefresh ? 'border-rose-700 text-rose-400 hover:bg-rose-900/20' : 'border-surface-700 text-surface-400 hover:bg-surface-800'">
              <RefreshCw class="w-4 h-4" :class="{'animate-spin': loading}" />
              {{ autoRefresh ? $t('monitoring.stop') : $t('monitoring.start') }}
            </button>
            <button @click="fetchMetrics(true)"
              class="p-2 rounded-lg bg-surface-800 text-surface-400 hover:text-white transition-colors">
              <RefreshCw class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Not connected -->
        <div v-if="!connectionStore.isConnected" class="flex flex-col items-center justify-center h-72 bg-surface-900 rounded-2xl border border-surface-800">
          <Server class="w-14 h-14 text-surface-600 mb-4" />
          <p class="text-surface-400 text-center max-w-xs mb-4">{{ $t('monitoring.notConnected') }}</p>
          <router-link to="/connect" class="btn-primary text-sm px-5 py-2">{{ $t('dashboard.connectBtn') }}</router-link>
        </div>

        <!-- SFTP required -->
        <div v-else-if="connectionStore.connectionInfo?.type !== 'sftp'" class="flex flex-col items-center justify-center h-72 bg-surface-900 rounded-2xl border border-surface-800">
          <AlertCircle class="w-14 h-14 text-amber-500 mb-4" />
          <p class="text-white font-semibold mb-2">{{ $t('monitoring.sftpRequired') }}</p>
          <p class="text-surface-400 text-sm text-center max-w-xs">{{ $t('monitoring.sftpNote') }}</p>
        </div>

        <!-- Loading skeleton -->
        <div v-else-if="loading && !metrics" class="space-y-4">
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="h-28 bg-surface-900 rounded-2xl border border-surface-800 loading-shimmer" />
          </div>
          <div class="h-64 bg-surface-900 rounded-2xl border border-surface-800 loading-shimmer" />
        </div>

        <div v-else-if="metrics" class="space-y-6">
          <!-- Server info bar -->
          <div class="flex items-center gap-4 p-4 bg-surface-900 border border-surface-800 rounded-xl text-sm">
            <div class="flex items-center gap-2">
              <Server class="w-4 h-4 text-surface-400" />
              <span class="text-surface-300 font-mono">{{ metrics.hostname || connectionStore.connectionInfo?.host }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Monitor class="w-4 h-4 text-surface-400" />
              <span class="text-surface-300">{{ metrics.os || 'Linux' }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Clock class="w-4 h-4 text-surface-400" />
              <span class="text-surface-300">{{ $t('monitoring.uptime') }}: {{ metrics.uptime || 'N/A' }}</span>
            </div>
            <div class="ml-auto text-xs text-surface-500">{{ $t('monitoring.lastUpdate') }}: {{ lastUpdate }}</div>
          </div>

          <!-- Metric cards -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- CPU -->
            <div class="bg-surface-900 rounded-2xl p-5 border border-surface-800">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-primary-900/30 flex items-center justify-center">
                  <Cpu class="w-5 h-5 text-primary-400" />
                </div>
                <span class="text-xs font-semibold text-surface-400 uppercase tracking-wider">CPU</span>
              </div>
              <p class="text-3xl font-bold text-white">{{ metrics.cpu_percent ?? 'N/A' }}<span v-if="metrics.cpu_percent != null" class="text-lg text-surface-400">%</span></p>
              <div class="mt-2 h-1.5 bg-surface-800 rounded-full overflow-hidden">
                <div class="h-1.5 rounded-full transition-all duration-1000"
                  :class="metrics.cpu_percent > 90 ? 'bg-rose-500' : metrics.cpu_percent > 70 ? 'bg-amber-500' : 'bg-primary-500'"
                  :style="{ width: (metrics.cpu_percent || 0) + '%' }" />
              </div>
              <p class="text-xs text-surface-500 mt-1">{{ $t('monitoring.load') }}: {{ metrics.load1 ?? 'N/A' }}</p>
            </div>

            <!-- RAM -->
            <div class="bg-surface-900 rounded-2xl p-5 border border-surface-800">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-violet-900/30 flex items-center justify-center">
                  <MemoryStick class="w-5 h-5 text-violet-400" />
                </div>
                <span class="text-xs font-semibold text-surface-400 uppercase tracking-wider">RAM</span>
              </div>
              <p class="text-3xl font-bold text-white">{{ metrics.ram_percent ?? 'N/A' }}<span v-if="metrics.ram_percent != null" class="text-lg text-surface-400">%</span></p>
              <div class="mt-2 h-1.5 bg-surface-800 rounded-full overflow-hidden">
                <div class="h-1.5 rounded-full transition-all duration-1000"
                  :class="metrics.ram_percent > 90 ? 'bg-rose-500' : metrics.ram_percent > 70 ? 'bg-amber-500' : 'bg-violet-500'"
                  :style="{ width: (metrics.ram_percent || 0) + '%' }" />
              </div>
              <p class="text-xs text-surface-500 mt-1">{{ metrics.ram_used_mb ?? '?' }} / {{ metrics.ram_total_mb ?? '?' }} MB</p>
            </div>

            <!-- Disk -->
            <div class="bg-surface-900 rounded-2xl p-5 border border-surface-800">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-900/30 flex items-center justify-center">
                  <HardDrive class="w-5 h-5 text-emerald-400" />
                </div>
                <span class="text-xs font-semibold text-surface-400 uppercase tracking-wider">{{ $t('monitoring.disk') }}</span>
              </div>
              <p class="text-3xl font-bold text-white">{{ metrics.disk_percent ?? 'N/A' }}<span v-if="metrics.disk_percent != null" class="text-lg text-surface-400">%</span></p>
              <div class="mt-2 h-1.5 bg-surface-800 rounded-full overflow-hidden">
                <div class="h-1.5 rounded-full transition-all duration-1000"
                  :class="metrics.disk_percent > 90 ? 'bg-rose-500' : metrics.disk_percent > 70 ? 'bg-amber-500' : 'bg-emerald-500'"
                  :style="{ width: (metrics.disk_percent || 0) + '%' }" />
              </div>
              <p class="text-xs text-surface-500 mt-1">{{ formatKB(metrics.disk_used_kb) }} / {{ formatKB(metrics.disk_total_kb) }}</p>
            </div>

            <!-- Network connections -->
            <div class="bg-surface-900 rounded-2xl p-5 border border-surface-800">
              <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-amber-900/30 flex items-center justify-center">
                  <Network class="w-5 h-5 text-amber-400" />
                </div>
                <span class="text-xs font-semibold text-surface-400 uppercase tracking-wider">{{ $t('monitoring.connections') }}</span>
              </div>
              <p class="text-3xl font-bold text-white">{{ metrics.connections_established ?? 'N/A' }}</p>
              <p class="text-xs text-surface-500 mt-3">{{ $t('monitoring.established') }}</p>
            </div>
          </div>

          <!-- Load average -->
          <div class="bg-surface-900 rounded-2xl border border-surface-800 p-6">
            <h2 class="font-bold text-white mb-4 text-sm uppercase tracking-wider">{{ $t('monitoring.loadAvg') }}</h2>
            <div class="grid grid-cols-3 gap-6">
              <div v-for="(val, label) in { '1 min': metrics.load1, '5 min': metrics.load5, '15 min': metrics.load15 }" :key="label" class="text-center">
                <p class="text-3xl font-bold text-white font-mono">{{ val ?? 'N/A' }}</p>
                <p class="text-xs text-surface-500 mt-1">{{ label }}</p>
                <div class="mt-2 h-1.5 bg-surface-800 rounded-full overflow-hidden">
                  <div class="h-1.5 rounded-full" :class="val > 2 ? 'bg-rose-500' : val > 1 ? 'bg-amber-500' : 'bg-emerald-500'"
                    :style="{ width: Math.min((val || 0) * 33, 100) + '%' }" />
                </div>
              </div>
            </div>
          </div>

          <!-- Top processes -->
          <div v-if="metrics.top_processes?.length" class="bg-surface-900 rounded-2xl border border-surface-800 overflow-hidden">
            <div class="p-4 border-b border-surface-800">
              <h2 class="font-bold text-white text-sm">{{ $t('monitoring.topProcesses') }}</h2>
            </div>
            <table class="w-full text-xs">
              <thead>
                <tr class="bg-surface-800/50">
                  <th class="px-4 py-2.5 text-left text-surface-400 font-semibold uppercase tracking-wider">PID</th>
                  <th class="px-4 py-2.5 text-left text-surface-400 font-semibold uppercase tracking-wider">{{ $t('monitoring.user') }}</th>
                  <th class="px-4 py-2.5 text-left text-surface-400 font-semibold uppercase tracking-wider">CPU %</th>
                  <th class="px-4 py-2.5 text-left text-surface-400 font-semibold uppercase tracking-wider">MEM %</th>
                  <th class="px-4 py-2.5 text-left text-surface-400 font-semibold uppercase tracking-wider">{{ $t('monitoring.command') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-surface-800">
                <tr v-for="proc in metrics.top_processes" :key="proc.pid" class="hover:bg-surface-800/40">
                  <td class="px-4 py-2.5 font-mono text-surface-400">{{ proc.pid }}</td>
                  <td class="px-4 py-2.5 text-surface-300">{{ proc.user }}</td>
                  <td class="px-4 py-2.5">
                    <span class="font-mono font-bold" :class="parseFloat(proc.cpu) > 50 ? 'text-rose-400' : parseFloat(proc.cpu) > 20 ? 'text-amber-400' : 'text-emerald-400'">
                      {{ proc.cpu }}%
                    </span>
                  </td>
                  <td class="px-4 py-2.5 font-mono text-violet-400">{{ proc.mem }}%</td>
                  <td class="px-4 py-2.5 font-mono text-surface-300 truncate max-w-[200px]">{{ proc.command }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { useConnectionStore } from '@/stores/connection'
import { Activity, RefreshCw, Server, AlertCircle, Cpu, HardDrive, Network, Monitor, Clock } from 'lucide-vue-next'

// Icons not in lucide-vue-next bundle, use simple SVG wrappers
import { h } from 'vue'
const MemoryStick = { render() { return h('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', class: this.$attrs.class }, [h('path', { d: 'M6 19v-3' }), h('path', { d: 'M10 19v-3' }), h('path', { d: 'M14 19v-3' }), h('path', { d: 'M18 19v-3' }), h('path', { d: 'M8 11V9' }), h('path', { d: 'M16 11V9' }), h('rect', { x: '2', y: '5', width: '20', height: '8', rx: '1' })]) } }

export default {
  name: 'MonitoringPage',
  components: { AppLayout, Activity, RefreshCw, Server, AlertCircle, Cpu, HardDrive, Network, Monitor, Clock, MemoryStick },
  data() {
    return {
      connectionStore: useConnectionStore(),
      metrics: null,
      loading: false,
      autoRefresh: false,
      timer: null,
      lastUpdate: '—'
    }
  },
  mounted() {
    if (this.connectionStore.isConnected && this.connectionStore.connectionInfo?.type === 'sftp') {
      this.fetchMetrics()
    }
  },
  beforeUnmount() {
    this.stopTimer()
  },
  methods: {
    formatKB(kb) {
      if (!kb) return 'N/A'
      if (kb > 1024 * 1024) return (kb / 1024 / 1024).toFixed(1) + ' TB'
      if (kb > 1024) return (kb / 1024).toFixed(1) + ' GB'
      return kb + ' MB'
    },
    async fetchMetrics(force = false) {
      if (!this.connectionStore.isConnected) return
      this.loading = true
      try {
        const API_BASE = import.meta.env.VITE_API_URL || '/api'
        const res = await fetch(`${API_BASE}/monitoring.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ sessionId: this.connectionStore.sessionId })
        })
        const data = await res.json()
        if (data.success && data.available) {
          this.metrics = data.metrics
          const now = new Date()
          this.lastUpdate = now.toLocaleTimeString()
        }
      } catch (e) {}
      this.loading = false
    },
    toggleAutoRefresh() {
      this.autoRefresh = !this.autoRefresh
      if (this.autoRefresh) {
        this.fetchMetrics()
        this.timer = setInterval(() => this.fetchMetrics(), 5000)
      } else {
        this.stopTimer()
      }
    },
    stopTimer() {
      if (this.timer) { clearInterval(this.timer); this.timer = null }
      this.autoRefresh = false
    }
  }
}
</script>
