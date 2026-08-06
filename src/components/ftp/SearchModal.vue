<template>
  <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="$emit('close')">
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-2xl w-full max-w-2xl border border-surface-200 dark:border-surface-700 flex flex-col max-h-[80vh]">
      <!-- Header -->
      <div class="flex items-center gap-3 px-6 py-4 border-b border-surface-200 dark:border-surface-800">
        <Search class="w-5 h-5 text-primary-500" />
        <h2 class="text-lg font-bold text-surface-900 dark:text-white flex-1">Recherche Globale</h2>
        <button @click="$emit('close')" class="p-1.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors">
          <X class="w-5 h-5 text-surface-500" />
        </button>
      </div>

      <!-- Search input -->
      <div class="px-6 py-4 border-b border-surface-200 dark:border-surface-800">
        <div class="flex gap-3">
          <div class="flex-1 relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
            <input
              ref="searchInput"
              v-model="query"
              type="text"
              placeholder="Nom du fichier ou dossier..."
              class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 text-surface-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              @keyup.enter="search"
            />
          </div>
          <button @click="search" :disabled="loading || !query.trim()" class="px-4 py-2.5 rounded-xl bg-primary-500 hover:bg-primary-600 text-white font-medium text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <span v-else>Chercher</span>
          </button>
        </div>
        <div class="flex items-center gap-4 mt-3 text-xs text-surface-500 dark:text-surface-400">
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input v-model="searchOptions.caseSensitive" type="checkbox" class="rounded" />
            Sensible à la casse
          </label>
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input v-model="searchOptions.includeDirectories" type="checkbox" class="rounded" />
            Inclure les dossiers
          </label>
          <div class="ml-auto">
            <span>Profondeur : </span>
            <span class="font-semibold text-primary-500">{{ maxDepth }} niveau{{ maxDepth > 1 ? 'x' : '' }}</span>
          </div>
        </div>
      </div>

      <!-- Results -->
      <div class="flex-1 overflow-y-auto">
        <!-- Loading -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-12 gap-4">
          <Loader2 class="w-10 h-10 text-primary-500 animate-spin" />
          <p class="text-sm text-surface-500 dark:text-surface-400">Scan du serveur en cours...</p>
        </div>

        <!-- Empty state before search -->
        <div v-else-if="!searched" class="flex flex-col items-center justify-center py-12 gap-3 text-center px-6">
          <FolderSearch class="w-12 h-12 text-surface-300 dark:text-surface-600" />
          <p class="text-surface-500 dark:text-surface-400 text-sm">Entrez un terme et appuyez sur "Chercher"<br/>pour scanner les fichiers de votre serveur.</p>
        </div>

        <!-- No results -->
        <div v-else-if="results.length === 0" class="flex flex-col items-center justify-center py-12 gap-3">
          <FileX class="w-12 h-12 text-surface-300 dark:text-surface-600" />
          <p class="text-surface-500 dark:text-surface-400 text-sm">Aucun résultat pour <strong class="text-surface-700 dark:text-surface-300">{{ lastQuery }}</strong></p>
        </div>

        <!-- Results list -->
        <div v-else class="divide-y divide-surface-100 dark:divide-surface-800">
          <div class="px-6 py-3 text-xs text-surface-500 dark:text-surface-400 bg-surface-50 dark:bg-surface-800/50">
            {{ results.length }} résultat{{ results.length > 1 ? 's' : '' }} pour <strong>{{ lastQuery }}</strong>
          </div>
          <button
            v-for="result in results"
            :key="result.path"
            @click="navigateTo(result)"
            class="w-full flex items-center gap-4 px-6 py-3.5 hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-colors text-left group"
          >
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :class="result.isDirectory ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-primary-100 dark:bg-primary-900/30'">
              <FolderOpen v-if="result.isDirectory" class="w-5 h-5 text-amber-500" />
              <File v-else class="w-5 h-5 text-primary-500" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-surface-900 dark:text-white text-sm truncate">{{ result.name }}</p>
              <p class="text-xs text-surface-500 dark:text-surface-400 truncate">{{ result.path }}</p>
            </div>
            <div class="text-xs text-surface-400 shrink-0 group-hover:text-primary-500 transition-colors">
              <ExternalLink class="w-4 h-4" />
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Search, X, Loader2, FolderSearch, FileX, FolderOpen, File, ExternalLink } from 'lucide-vue-next'
import { useConnectionStore } from '@/stores/connection'
import { useSettingsStore } from '@/stores/settings'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

export default {
  name: 'SearchModal',
  components: { Search, X, Loader2, FolderSearch, FileX, FolderOpen, File, ExternalLink },
  props: { visible: { type: Boolean, default: false } },
  emits: ['close', 'navigate'],
  data() {
    return {
      query: '',
      loading: false,
      searched: false,
      results: [],
      lastQuery: '',
      searchOptions: { caseSensitive: false, includeDirectories: true },
      connectionStore: useConnectionStore(),
      settingsStore: useSettingsStore()
    }
  },
  computed: {
    maxDepth() {
      return this.settingsStore.isPremium ? 5 : 3
    }
  },
  watch: {
    visible(val) {
      if (val) {
        this.$nextTick(() => this.$refs.searchInput?.focus())
        this.query = ''
        this.results = []
        this.searched = false
      }
    }
  },
  methods: {
    async search() {
      if (!this.query.trim() || this.loading) return
      this.loading = true
      this.searched = false
      this.results = []
      this.lastQuery = this.query.trim()

      try {
        const response = await fetch(`${API_BASE}/search.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({
            sessionId: this.connectionStore.sessionId,
            query: this.lastQuery,
            startPath: this.connectionStore.currentPath || '/',
            maxDepth: this.maxDepth,
            caseSensitive: this.searchOptions.caseSensitive,
            includeDirectories: this.searchOptions.includeDirectories
          })
        })
        const data = await response.json()
        if (data.success) {
          this.results = data.results || []
        } else {
          this.results = []
        }
      } catch (e) {
        this.results = []
      } finally {
        this.loading = false
        this.searched = true
      }
    },

    navigateTo(result) {
      // Navigate to the directory containing the file
      const pathParts = result.path.split('/')
      pathParts.pop() // remove filename
      const parentPath = pathParts.join('/') || '/'
      this.$emit('navigate', parentPath, result)
      this.$emit('close')
    }
  }
}
</script>
