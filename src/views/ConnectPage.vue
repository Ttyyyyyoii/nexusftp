<template>
  <AppLayout>
    <div class="flex flex-col lg:flex-row h-full">
      <div class="flex-1 flex items-center justify-center p-6 overflow-auto">
        <div class="w-full max-w-md">
          <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center mx-auto mb-4 shadow-glow">
              <Plug class="w-8 h-8 text-white" />
            </div>
            <h1 class="text-2xl font-bold text-surface-900 dark:text-white mb-2">{{ $t('connection.title') }}</h1>
            <p class="text-sm text-surface-500 dark:text-surface-400">Connect to your FTP, FTPS, or SFTP server</p>
          </div>
          <form @submit.prevent="handleConnect" class="space-y-4">
            <div class="grid grid-cols-4 gap-2 p-1 bg-surface-100 dark:bg-surface-800 rounded-xl">
              <button v-for="type in connectionTypes" :key="type.value" type="button" @click="form.type = type.value"
                class="py-2 px-3 rounded-lg text-xs font-medium transition-all"
                :class="form.type === type.value ? 'bg-white dark:bg-surface-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-surface-500 dark:text-surface-400 hover:text-surface-700'">
                {{ type.label }}
              </button>
            </div>
            <div>
              <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1.5">{{ $t('connection.host') }}</label>
              <div class="relative">
                <Server class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                <input v-model="form.host" type="text" :placeholder="$t('connection.hostPlaceholder')" required
                  class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200 text-sm transition-all" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1.5">{{ $t('connection.username') }}</label>
                <div class="relative">
                  <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                  <input v-model="form.username" type="text" :placeholder="$t('connection.usernamePlaceholder')" required
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200 text-sm transition-all" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1.5">{{ $t('connection.password') }}</label>
                <div class="relative">
                  <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                  <input v-model="form.password" :type="showPassword ? 'text' : 'password'" :placeholder="$t('connection.passwordPlaceholder')" required
                    class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200 text-sm transition-all" />
                  <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-surface-400 hover:text-surface-600">
                    <Eye v-if="!showPassword" class="w-4 h-4" /><EyeOff v-else class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1.5">{{ $t('connection.port') }}</label>
              <input v-model="form.port" type="number" :placeholder="defaultPort"
                class="w-full px-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200 text-sm transition-all" />
            </div>
            <label class="flex items-center gap-2 cursor-pointer py-2">
              <input v-model="form.saveConnection" type="checkbox" class="w-4 h-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500" />
              <span class="text-sm text-surface-600 dark:text-surface-400">{{ $t('connection.saveConnection') }}</span>
            </label>
            <div class="flex gap-3 pt-2">
              <button type="submit" :disabled="connectionStore.isConnecting" class="flex-1 btn-primary flex items-center justify-center gap-2 py-3 disabled:opacity-50">
                <Loader2 v-if="connectionStore.isConnecting" class="w-4 h-4 animate-spin" />
                <Plug v-else class="w-4 h-4" />
                {{ connectionStore.isConnecting ? $t('connection.connecting') : $t('connection.quickConnect') }}
              </button>
              <button type="button" @click="testConnection" :disabled="testing" class="btn-secondary flex items-center gap-2 py-3 disabled:opacity-50">
                <Activity v-if="!testing" class="w-4 h-4" /><Loader2 v-else class="w-4 h-4 animate-spin" />
                {{ $t('connection.testConnection') }}
              </button>
            </div>
            <p v-if="error" class="text-sm text-rose-600 dark:text-rose-400 text-center bg-rose-50 dark:bg-rose-900/20 p-3 rounded-xl">{{ error }}</p>
          </form>
        </div>
      </div>
      <div class="lg:w-80 bg-surface-50 dark:bg-surface-900 border-l border-surface-200 dark:border-surface-800 p-6 overflow-auto">
        <div class="mb-6">
          <h3 class="font-semibold text-surface-900 dark:text-white mb-4 flex items-center gap-2"><Bookmark class="w-4 h-4" />{{ $t('connection.savedConnections') }}</h3>
          <div v-if="savedConnections.length === 0" class="text-center py-8"><Bookmark class="w-10 h-10 text-surface-300 mx-auto mb-2" /><p class="text-sm text-surface-400">No saved connections</p></div>
          <div v-else class="space-y-2">
            <div v-for="conn in savedConnections" :key="conn.id" class="group p-3 rounded-xl bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 hover:shadow-md transition-all">
              <div class="flex items-center justify-between mb-1">
                <span class="font-medium text-sm text-surface-800 dark:text-surface-200">{{ conn.label }}</span>
                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="loadConnection(conn)" class="p-1 rounded hover:bg-surface-100 dark:hover:bg-surface-700"><Edit2 class="w-3 h-3 text-surface-400" /></button>
                  <button @click="removeSaved(conn.id)" class="p-1 rounded hover:bg-rose-100 dark:hover:bg-rose-900/20"><Trash2 class="w-3 h-3 text-rose-400" /></button>
                </div>
              </div>
              <div class="flex items-center gap-2 text-xs text-surface-500">
                <span class="px-1.5 py-0.5 rounded bg-surface-100 dark:bg-surface-700 font-mono uppercase">{{ conn.type }}</span>
                <span>{{ conn.host }}:{{ conn.port }}</span>
              </div>
              <button @click="quickConnect(conn)" class="mt-2 w-full py-1.5 text-xs font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors">{{ $t('connection.connect') }}</button>
            </div>
          </div>
        </div>
        <div>
          <h3 class="font-semibold text-surface-900 dark:text-white mb-4 flex items-center gap-2"><Clock class="w-4 h-4" />{{ $t('connection.recentConnections') }}</h3>
          <div v-if="recentConnections.length === 0" class="text-center py-8"><Clock class="w-10 h-10 text-surface-300 mx-auto mb-2" /><p class="text-sm text-surface-400">No recent connections</p></div>
          <div v-else class="space-y-2">
            <button v-for="conn in recentConnections.slice(0, 5)" :key="conn.host + conn.username" @click="loadConnection(conn)"
              class="w-full text-left p-3 rounded-xl bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 hover:shadow-md transition-all">
              <div class="font-medium text-sm text-surface-800 dark:text-surface-200">{{ conn.username }}@{{ conn.host }}</div>
              <div class="flex items-center gap-2 mt-1">
                <span class="px-1.5 py-0.5 rounded bg-surface-100 dark:bg-surface-700 text-xs font-mono uppercase">{{ conn.type }}</span>
                <span class="text-xs text-surface-400">{{ formatDate(conn.date) }}</span>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { useConnectionStore } from '@/stores/connection'
import { useLogStore } from '@/stores/log'
import { Plug, Server, User, Lock, Eye, EyeOff, Loader2, Activity, Bookmark, Clock, Edit2, Trash2 } from 'lucide-vue-next'
import dayjs from 'dayjs'
export default {
  name: 'ConnectPage',
  components: { AppLayout, Plug, Server, User, Lock, Eye, EyeOff, Loader2, Activity, Bookmark, Clock, Edit2, Trash2 },
  data() {
    return {
      connectionStore: useConnectionStore(),
      logStore: useLogStore(),
      form: { host: '', port: '', username: '', password: '', type: 'ftp', saveConnection: false },
      showPassword: false, testing: false, error: '',
      connectionTypes: [{ value: 'ftp', label: 'FTP' }, { value: 'ftps', label: 'FTPS' }, { value: 'ftpse', label: 'FTPES' }, { value: 'sftp', label: 'SFTP' }]
    }
  },
  computed: {
    savedConnections() { return this.connectionStore.savedConnections },
    recentConnections() { return this.connectionStore.recentConnections },
    defaultPort() { const ports = { ftp: 21, ftps: 990, ftpse: 21, sftp: 22 }; return ports[this.form.type] || 21 }
  },
  mounted() {
    this.connectionStore.restoreSession()
  },
  methods: {
    async handleConnect() {
      this.error = ''
      const result = await this.connectionStore.connect({ host: this.form.host, port: parseInt(this.form.port) || this.defaultPort, username: this.form.username, password: this.form.password, type: this.form.type, saveConnection: this.form.saveConnection })
      if (result.success) { 
        this.logStore.logConnection(`Connected to ${this.form.host}`); 
        this.$router.push('/files') 
      }
      else { 
        if (result.error === 'PREMIUM_REQUIRED_SAVED_CONNECTIONS') {
          this.error = "Limite de connexions sauvegardées atteinte. Veuillez passer en Premium."
          window.dispatchEvent(new CustomEvent('show-premium-modal'))
        } else {
          this.error = result.error || 'Connection failed'
        }
        this.logStore.logError(`Connection failed: ${this.error}`) 
      }
    },
    async testConnection() { this.testing = true; this.error = ''; try { const result = await this.connectionStore.connect({ host: this.form.host, port: parseInt(this.form.port) || this.defaultPort, username: this.form.username, password: this.form.password, type: this.form.type, saveConnection: false }); if (result.success) { await this.connectionStore.disconnect(); this.showToast('Connection test successful', 'success') } else this.error = result.error || 'Connection test failed' } finally { this.testing = false } },
    quickConnect(conn) { this.form = { host: conn.host, port: conn.port || '', username: conn.username, password: this.connectionStore.getDecryptedPassword(conn.password), type: conn.type, saveConnection: false } },
    loadConnection(conn) { this.quickConnect(conn) },
    removeSaved(id) { this.connectionStore.removeSavedConnection(id) },
    formatDate(date) { return dayjs(date).format('MMM D, HH:mm') },
    showToast(title, type) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { title, type } })) }
  }
}
</script>
