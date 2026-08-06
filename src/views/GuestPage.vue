<template>
  <div class="min-h-screen bg-surface-50 dark:bg-surface-950 flex flex-col items-center p-4 sm:p-6 lg:p-8">
    
    <!-- Branding -->
    <div class="w-full max-w-5xl mb-8 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-600/20">
          <Server class="w-6 h-6" />
        </div>
        <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400">
          NexusFTP
        </span>
      </div>
      <div class="flex items-center gap-4">
        <div class="px-4 py-1.5 rounded-full bg-surface-200 dark:bg-surface-800 text-sm font-medium text-surface-600 dark:text-surface-300 border border-surface-300 dark:border-surface-700">
          Espace Invité
        </div>
        <button @click="toggleTheme" class="w-10 h-10 rounded-xl flex items-center justify-center bg-surface-200 dark:bg-surface-800 text-surface-600 dark:text-surface-300 hover:bg-surface-300 dark:hover:bg-surface-700 transition-colors">
          <Moon v-if="!isDark" class="w-5 h-5" />
          <Sun v-else class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Password Prompt -->
    <div v-if="needsPassword && !authenticated" class="w-full max-w-md bg-surface-100 dark:bg-surface-900 rounded-2xl p-6 sm:p-8 shadow-xl border border-surface-200 dark:border-surface-800 animate-in fade-in zoom-in duration-300">
      <div class="w-16 h-16 mx-auto bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mb-6">
        <Lock class="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
      </div>
      <h2 class="text-2xl font-bold text-center text-surface-900 dark:text-white mb-2">Accès Sécurisé</h2>
      <p class="text-center text-surface-500 mb-8">Cet espace est protégé par un mot de passe.</p>
      
      <form @submit.prevent="authenticate" class="space-y-4">
        <div class="relative">
          <Key class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-400" />
          <input type="password" v-model="password" placeholder="Saisissez le mot de passe" required class="w-full bg-surface-50 dark:bg-surface-950 border border-surface-200 dark:border-surface-800 rounded-xl py-3 pl-12 pr-4 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none text-surface-900 dark:text-white" />
        </div>
        <button type="submit" :disabled="loading" class="w-full btn-primary py-3 rounded-xl flex justify-center items-center gap-2">
          <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
          <Unlock v-else class="w-5 h-5" />
          {{ loading ? 'Vérification...' : 'Accéder' }}
        </button>
      </form>
    </div>

    <!-- Main Content -->
    <div v-else-if="authenticated" class="w-full max-w-5xl flex-1 flex flex-col bg-surface-100 dark:bg-surface-900 rounded-2xl shadow-xl border border-surface-200 dark:border-surface-800 overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-500">
      
      <!-- Toolbar -->
      <div class="p-4 border-b border-surface-200 dark:border-surface-800 flex items-center justify-between bg-surface-50/50 dark:bg-surface-950/50">
        
        <!-- Breadcrumbs -->
        <div class="flex items-center text-sm font-medium overflow-x-auto hide-scrollbar whitespace-nowrap">
          <button @click="navigateTo('/')" class="text-surface-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-1">
            <Home class="w-4 h-4" />
            <span class="sr-only sm:not-sr-only sm:ml-1">Accueil</span>
          </button>
          
          <template v-for="(part, index) in pathParts" :key="index">
            <ChevronRight class="w-4 h-4 text-surface-400 mx-1 flex-shrink-0" />
            <button @click="navigateTo(part.full)" class="text-surface-700 dark:text-surface-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors truncate max-w-[100px] sm:max-w-[200px]">
              {{ part.name }}
            </button>
          </template>
        </div>
        
        <!-- Actions -->
        <div class="flex items-center gap-2">
          <button @click="loadFiles" class="btn-secondary p-2" title="Actualiser">
            <RefreshCw class="w-4 h-4" :class="{'animate-spin': loading}" />
          </button>
          <button v-if="permission === 'upload'" @click="$refs.fileInput.click()" class="btn-primary p-2 sm:px-4 sm:py-2 flex items-center gap-2">
            <Upload class="w-4 h-4" />
            <span class="hidden sm:inline">Ajouter</span>
          </button>
          <input type="file" ref="fileInput" @change="handleUpload" class="hidden" multiple />
        </div>
      </div>

      <!-- File List -->
      <div class="flex-1 overflow-y-auto relative min-h-[400px]">
        
        <div v-if="loading && files.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-surface-400">
          <Loader2 class="w-8 h-8 animate-spin text-indigo-500 mb-4" />
          <p>Chargement des fichiers...</p>
        </div>
        
        <div v-else-if="files.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-surface-400">
          <div class="w-16 h-16 rounded-full bg-surface-200 dark:bg-surface-800 flex items-center justify-center mb-4">
            <FolderOpen class="w-8 h-8" />
          </div>
          <h3 class="text-lg font-medium text-surface-700 dark:text-surface-300">Ce dossier est vide</h3>
          <p class="text-sm mt-1">Aucun fichier n'a été partagé ici.</p>
        </div>
        
        <div v-else class="divide-y divide-surface-200 dark:divide-surface-800">
          <!-- Back button if not in root -->
          <div v-if="currentPath !== '/'" @click="navigateUp" class="group flex items-center p-3 sm:p-4 hover:bg-surface-200 dark:hover:bg-surface-800/50 cursor-pointer transition-colors">
            <div class="w-10 h-10 flex items-center justify-center text-surface-400 group-hover:text-surface-600 dark:group-hover:text-surface-200 mr-4">
              <CornerLeftUp class="w-5 h-5" />
            </div>
            <span class="font-medium text-surface-700 dark:text-surface-300">..</span>
          </div>
          
          <div v-for="file in files" :key="file.name" class="group flex items-center p-3 sm:p-4 hover:bg-surface-200 dark:hover:bg-surface-800/50 transition-colors cursor-default">
            
            <!-- Icon -->
            <div class="w-10 h-10 rounded-xl flex flex-shrink-0 items-center justify-center mr-4" :class="file.isDirectory ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'">
              <Folder v-if="file.isDirectory" class="w-5 h-5 fill-current" />
              <FileIcon v-else class="w-5 h-5" />
            </div>
            
            <!-- Info -->
            <div class="flex-1 min-w-0" @click="handleItemClick(file)" :class="{'cursor-pointer': file.isDirectory}">
              <div class="font-medium text-surface-900 dark:text-white truncate" :class="{'group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors': file.isDirectory}">
                {{ file.name }}
              </div>
              <div class="flex items-center text-xs text-surface-500 mt-0.5 gap-3">
                <span v-if="!file.isDirectory">{{ formatSize(file.size) }}</span>
                <span class="hidden sm:inline">{{ formatDate(file.modified) }}</span>
              </div>
            </div>
            
            <!-- Actions -->
            <div class="ml-4 flex items-center gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
              <button v-if="!file.isDirectory" @click.stop="downloadFile(file)" class="p-2 rounded-lg bg-surface-100 dark:bg-surface-800 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-surface-600 dark:text-surface-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors shadow-sm" title="Télécharger">
                <Download class="w-4 h-4" />
              </button>
            </div>
            
          </div>
        </div>
      </div>
      
    </div>

  </div>
</template>

<script>
import { 
  Server, Moon, Sun, Lock, Key, Unlock, Loader2, Home, ChevronRight, 
  RefreshCw, Upload, FolderOpen, Folder, File as FileIcon, Download, CornerLeftUp 
} from 'lucide-vue-next'

export default {
  name: 'GuestPage',
  components: { 
    Server, Moon, Sun, Lock, Key, Unlock, Loader2, Home, ChevronRight, 
    RefreshCw, Upload, FolderOpen, Folder, FileIcon, Download, CornerLeftUp 
  },
  data() {
    return {
      token: '',
      password: '',
      needsPassword: true,
      authenticated: false,
      loading: false,
      files: [],
      currentPath: '/',
      permission: 'read',
      isDark: false
    }
  },
  setup() {
    return { toast: useToast() }
  },
  computed: {
    pathParts() {
      if (this.currentPath === '/') return [];
      const parts = this.currentPath.split('/').filter(p => p);
      let current = '';
      return parts.map(part => {
        current += '/' + part;
        return { name: part, full: current };
      });
    }
  },
  mounted() {
    this.token = this.$route.params.token
    if (!this.token) {
      this.showToast('Jeton invalide', 'error')
      return
    }
    
    // Test without password first
    this.loadFiles(true)
    
    // Theme logic
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark')
      this.isDark = true
    } else {
      document.documentElement.classList.remove('dark')
      this.isDark = false
    }
  },
  methods: {
    showToast(title, type = 'info') {
      window.dispatchEvent(new CustomEvent('show-toast', { detail: { title, type } }))
    },
    toggleTheme() {
      if (this.isDark) {
        document.documentElement.classList.remove('dark')
        localStorage.setItem('theme', 'light')
        this.isDark = false
      } else {
        document.documentElement.classList.add('dark')
        localStorage.setItem('theme', 'dark')
        this.isDark = true
      }
    },
    async authenticate() {
      if (!this.password) return
      await this.loadFiles()
    },
    async loadFiles(initialTest = false) {
      this.loading = true
      try {
        const API_BASE = import.meta.env.VITE_API_URL || '/api'
        const res = await fetch(`${API_BASE}/guest_list.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            token: this.token,
            password: this.password,
            path: this.currentPath
          })
        })
        const data = await res.json()
        
        if (data.success) {
          this.files = data.files
          this.permission = data.permission
          this.authenticated = true
          this.needsPassword = false
        } else {
          if (res.status === 401) {
            this.needsPassword = true
            if (!initialTest) this.showToast('Mot de passe incorrect', 'error')
          } else {
            throw new Error(data.message || 'Erreur lors du chargement')
          }
        }
      } catch (err) {
        if (!initialTest) this.showToast(err.message, 'error')
      } finally {
        this.loading = false
      }
    },
    handleItemClick(item) {
      if (item.isDirectory) {
        this.navigateTo((this.currentPath === '/' ? '/' : this.currentPath + '/') + item.name)
      } else {
        this.downloadFile(item)
      }
    },
    navigateTo(path) {
      this.currentPath = path
      this.loadFiles()
    },
    navigateUp() {
      if (this.currentPath === '/') return
      const parts = this.currentPath.split('/').filter(p => p)
      parts.pop()
      this.navigateTo('/' + parts.join('/'))
    },
    downloadFile(file) {
      const API_BASE = import.meta.env.VITE_API_URL || '/api'
      const filePath = (this.currentPath === '/' ? '/' : this.currentPath + '/') + file.name
      const url = `${API_BASE}/guest_download.php?token=${this.token}&path=${encodeURIComponent(filePath)}&password=${encodeURIComponent(this.password)}`
      window.location.href = url
    },
    async handleUpload(event) {
      const files = event.target.files
      if (!files.length) return
      
      this.showToast(`Upload de ${files.length} fichier(s) en cours...`, 'info')
      
      const API_BASE = import.meta.env.VITE_API_URL || '/api'
      let successCount = 0
      let failCount = 0
      
      for (let i = 0; i < files.length; i++) {
        const file = files[i]
        const formData = new FormData()
        formData.append('file', file)
        formData.append('token', this.token)
        formData.append('password', this.password)
        formData.append('path', this.currentPath)
        formData.append('remoteName', file.name)
        
        try {
          const res = await fetch(`${API_BASE}/guest_upload.php`, {
            method: 'POST',
            body: formData
          })
          const data = await res.json()
          
          if (data.success) {
            successCount++
          } else {
            this.showToast(`Échec pour ${file.name}: ${data.message}`, 'error')
            failCount++
          }
        } catch (err) {
          this.showToast(`Erreur pour ${file.name}`, 'error')
          failCount++
        }
      }
      
      if (successCount > 0) {
        this.showToast(`${successCount} fichier(s) envoyé(s) avec succès !`, 'success')
        this.loadFiles()
      }
      
      // Reset input
      this.$refs.fileInput.value = ''
    },
    formatSize(bytes) {
      if (bytes === 0) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    },
    formatDate(dateStr) {
      if (!dateStr) return ''
      try {
        const d = new Date(dateStr)
        return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      } catch (e) {
        return dateStr
      }
    }
  }
}
</script>
