<template>
  <div class="min-h-screen bg-surface-0 dark:bg-surface-900 flex flex-col text-surface-700 dark:text-surface-300">
    
    <!-- Top Bar (same style as AppToolbar) -->
    <header class="h-14 bg-surface-0 dark:bg-surface-900 border-b border-surface-200 dark:border-surface-800 flex items-center px-4 gap-3 shrink-0">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
          <HardDrive class="w-4 h-4 text-white" />
        </div>
        <span class="font-bold text-surface-900 dark:text-white hidden sm:inline">{{ $t('app.name') }}</span>
      </div>
      <div class="flex-1" />
      <span class="px-3 py-1 rounded-lg bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800 text-primary-700 dark:text-primary-300 text-xs font-semibold flex items-center gap-1.5">
        <Users class="w-3.5 h-3.5" />
        {{ $t('guest.pageBadge') }}
      </span>
      <button @click="toggleTheme" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors">
        <Sun v-if="isDark" class="w-5 h-5 text-amber-400" />
        <Moon v-else class="w-5 h-5 text-surface-500" />
      </button>
    </header>

    <!-- Main area -->
    <div class="flex-1 flex flex-col items-center py-10 px-4">

      <!-- ── PASSWORD SCREEN ── -->
      <div v-if="needsPassword && !authenticated" class="w-full max-w-sm">
        <div class="glass-panel rounded-2xl p-8 space-y-6">
          <!-- Icon -->
          <div class="flex flex-col items-center gap-3 text-center">
            <div class="w-16 h-16 rounded-2xl bg-primary-50 dark:bg-primary-900/30 border border-primary-100 dark:border-primary-800 flex items-center justify-center">
              <Lock class="w-8 h-8 text-primary-500" />
            </div>
            <div>
              <h1 class="text-xl font-bold text-surface-900 dark:text-white">{{ $t('guest.securedTitle') }}</h1>
              <p class="text-sm text-surface-500 mt-1">{{ $t('guest.securedDesc') }}</p>
            </div>
          </div>

          <!-- Password Form -->
          <form @submit.prevent="authenticate" class="space-y-3">
            <div class="relative">
              <Key class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
              <input
                type="password"
                v-model="password"
                :placeholder="$t('guest.passwordInputLabel')"
                required
                autofocus
                class="w-full pl-10 pr-4 py-3 rounded-xl bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-white outline-none text-sm transition-all"
              />
            </div>
            <div v-if="authError" class="flex items-center gap-2 text-rose-500 text-sm px-1">
              <AlertCircle class="w-4 h-4 shrink-0" />
              <span>{{ authError }}</span>
            </div>
            <button type="submit" :disabled="loading" class="btn-primary w-full flex items-center justify-center gap-2">
              <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
              <Unlock v-else class="w-4 h-4" />
              {{ loading ? $t('guest.verifying') : $t('guest.accessBtn') }}
            </button>
          </form>
        </div>
      </div>

      <!-- ── FILE BROWSER ── -->
      <div v-else-if="authenticated" class="w-full max-w-5xl flex flex-col gap-4">

        <!-- Breadcrumb + Actions bar -->
        <div class="flex items-center gap-3">
          <!-- Breadcrumbs -->
          <div class="flex-1 flex items-center gap-1 text-sm font-medium overflow-x-auto hide-scrollbar min-w-0">
            <button @click="navigateTo('/')" class="flex items-center gap-1.5 px-2 py-1 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors shrink-0">
              <Home class="w-4 h-4" />
              <span class="hidden sm:inline">{{ $t('guest.home') }}</span>
            </button>
            <template v-for="(part, i) in pathParts" :key="i">
              <ChevronRight class="w-4 h-4 text-surface-300 dark:text-surface-600 shrink-0" />
              <button
                @click="navigateTo(part.full)"
                class="px-2 py-1 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-700 dark:text-surface-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors truncate max-w-[120px]">
                {{ part.name }}
              </button>
            </template>
          </div>

          <!-- Actions -->
          <button @click="loadFiles" :disabled="loading" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-surface-700 dark:text-surface-300 hover:bg-surface-100 dark:hover:bg-surface-800 transition-all disabled:opacity-40">
            <RefreshCw class="w-4 h-4" :class="loading ? 'animate-spin text-primary-500' : ''" />
            <span class="hidden sm:inline">{{ $t('guest.refresh') }}</span>
          </button>
          <button v-if="permission === 'upload'" @click="$refs.fileInput.click()" class="btn-primary flex items-center gap-1.5 text-sm">
            <Upload class="w-4 h-4" />
            {{ $t('guest.addFiles') }}
          </button>
          <input ref="fileInput" type="file" multiple @change="handleUpload" class="hidden" />
        </div>

        <!-- File List Panel -->
        <div class="glass-panel rounded-2xl overflow-hidden">

          <!-- Loading state -->
          <div v-if="loading && files.length === 0" class="flex flex-col items-center justify-center py-20 gap-3 text-surface-400">
            <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
            <p class="text-sm">{{ $t('guest.loading') }}</p>
          </div>

          <!-- Empty state -->
          <div v-else-if="!loading && files.length === 0" class="flex flex-col items-center justify-center py-20 gap-4 text-surface-400">
            <div class="w-16 h-16 rounded-2xl bg-surface-100 dark:bg-surface-800 flex items-center justify-center">
              <FolderOpen class="w-8 h-8 text-surface-400" />
            </div>
            <div class="text-center">
              <p class="font-semibold text-surface-600 dark:text-surface-300">{{ $t('guest.emptyFolder') }}</p>
              <p class="text-sm mt-1">{{ $t('guest.emptyDesc') }}</p>
            </div>
          </div>

          <!-- Files table -->
          <div v-else>
            <!-- Back row -->
            <div v-if="currentPath !== '/'"
              @click="navigateUp"
              class="file-row group flex items-center gap-3 px-4 py-3 border-b border-surface-200 dark:border-surface-800 cursor-pointer hover:bg-surface-50 dark:hover:bg-surface-800/50 transition-colors">
              <div class="w-9 h-9 rounded-xl bg-surface-100 dark:bg-surface-800 flex items-center justify-center text-surface-400 group-hover:text-surface-600 dark:group-hover:text-surface-200 transition-colors">
                <CornerLeftUp class="w-4 h-4" />
              </div>
              <span class="text-sm font-medium text-surface-500 dark:text-surface-400">..</span>
            </div>

            <div
              v-for="(file, index) in files"
              :key="file.name"
              class="file-row group flex items-center gap-3 px-4 py-3 cursor-default transition-colors hover:bg-surface-50 dark:hover:bg-surface-800/50"
              :class="index < files.length - 1 ? 'border-b border-surface-200 dark:border-surface-800' : ''">

              <!-- Icon -->
              <div class="w-9 h-9 rounded-xl shrink-0 flex items-center justify-center"
                :class="file.isDirectory
                  ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400'
                  : 'bg-primary-50 dark:bg-primary-900/20 text-primary-500 dark:text-primary-400'">
                <Folder v-if="file.isDirectory" class="w-4 h-4 fill-current" />
                <FileIcon v-else class="w-4 h-4" />
              </div>

              <!-- Name + meta -->
              <div class="flex-1 min-w-0"
                @click="handleItemClick(file)"
                :class="file.isDirectory ? 'cursor-pointer' : ''">
                <p class="text-sm font-medium text-surface-900 dark:text-white truncate"
                  :class="file.isDirectory ? 'group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors' : ''">
                  {{ file.name }}
                </p>
                <p class="text-xs text-surface-400 mt-0.5 flex items-center gap-3">
                  <span v-if="!file.isDirectory">{{ formatSize(file.size) }}</span>
                  <span class="hidden sm:inline">{{ formatDate(file.modified) }}</span>
                </p>
              </div>

              <!-- Badge: folder -->
              <span v-if="file.isDirectory" class="hidden sm:inline text-xs px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 font-medium">
                {{ $t('files.newFolder').replace('Nouveau ', '').replace('New ', '') || 'Dossier' }}
              </span>

              <!-- Actions -->
              <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all shrink-0">
                <button
                  v-if="!file.isDirectory && permission === 'upload' && isEditable(file.name)"
                  @click.stop="editFile(file)"
                  class="p-2 rounded-lg bg-surface-100 dark:bg-surface-800 hover:bg-primary-50 dark:hover:bg-primary-900/30 text-surface-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                  :title="$t('files.edit')">
                  <Edit2 class="w-4 h-4" />
                </button>
                <button
                  v-if="!file.isDirectory"
                  @click.stop="downloadFile(file)"
                  class="p-2 rounded-lg bg-surface-100 dark:bg-surface-800 hover:bg-primary-50 dark:hover:bg-primary-900/30 text-surface-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                  :title="$t('guest.download')">
                  <Download class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer info -->
        <p class="text-center text-xs text-surface-400 mt-2">
          {{ $t('app.name') }} — {{ $t('guest.pageBadge') }}
        </p>
      </div>

    </div>

    <!-- Edit File Modal with Monaco Editor -->
    <BaseModal :visible="showFileEditor" :title="'✏️ ' + $t('common.edit') + ': ' + (fileEditorFile?.name || '')" @close="showFileEditor = false" maxWidth="max-w-5xl">
      <div class="h-[65vh] -mx-2 -mt-2 rounded-xl overflow-hidden border border-surface-200 dark:border-surface-700">
        <MonacoEditor v-if="showFileEditor" v-model="fileEditorContent" :language="editorLanguage" />
      </div>
      <template #footer>
        <button @click="showFileEditor = false" class="btn-secondary text-sm" :disabled="fileEditorSaving">{{ $t('common.cancel') }}</button>
        <button @click="saveFileEdit" class="btn-primary text-sm flex items-center gap-2" :disabled="fileEditorSaving">
          <Loader2 v-if="fileEditorSaving" class="w-4 h-4 animate-spin" />
          {{ $t('common.save') }}
        </button>
      </template>
    </BaseModal>

    <!-- Toast notifications (reuse app's system) -->
    <ToastContainer />
  </div>
</template>

<script>
import {
  HardDrive, Users, Sun, Moon, Lock, Key, Unlock, Loader2, AlertCircle,
  Home, ChevronRight, RefreshCw, Upload, FolderOpen, Folder,
  File as FileIcon, Download, CornerLeftUp, Edit2
} from 'lucide-vue-next'
import ToastContainer from '@/components/ui/ToastContainer.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import MonacoEditor from '@/components/ui/MonacoEditor.vue'

const LANG_MAP = {
  js: 'javascript', ts: 'typescript', vue: 'html', html: 'html', htm: 'html',
  css: 'css', scss: 'scss', less: 'less', php: 'php', py: 'python',
  json: 'json', xml: 'xml', md: 'markdown', sh: 'shell', bash: 'shell',
  sql: 'sql', yaml: 'yaml', yml: 'yaml', txt: 'plaintext', env: 'plaintext'
}

export default {
  name: 'GuestPage',
  components: {
    HardDrive, Users, Sun, Moon, Lock, Key, Unlock, Loader2, AlertCircle,
    Home, ChevronRight, RefreshCw, Upload, FolderOpen, Folder,
    FileIcon, Download, CornerLeftUp, Edit2, ToastContainer,
    BaseModal, MonacoEditor
  },
  data() {
    return {
      token: '',
      password: '',
      needsPassword: true,
      authenticated: false,
      loading: false,
      authError: null,
      files: [],
      currentPath: '/',
      permission: 'read',
      isDark: false,
      showFileEditor: false,
      fileEditorFile: null,
      fileEditorContent: '',
      fileEditorSaving: false,
      editorLanguage: 'plaintext'
    }
  },
  computed: {
    pathParts() {
      if (this.currentPath === '/') return []
      const parts = this.currentPath.split('/').filter(Boolean)
      let current = ''
      return parts.map(name => {
        current += '/' + name
        return { name, full: current }
      })
    }
  },
  mounted() {
    this.token = this.$route.params.token
    if (!this.token) {
      this.showToast(this.$t('guest.invalidToken'), 'error')
      return
    }
    // Probe without password first
    this.loadFiles(true)

    // Apply theme
    const saved = localStorage.getItem('theme')
    this.isDark = saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)
    this.applyTheme()
  },
  methods: {
    showToast(title, type = 'info') {
      window.dispatchEvent(new CustomEvent('show-toast', { detail: { title, type } }))
    },
    applyTheme() {
      if (this.isDark) {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    },
    toggleTheme() {
      this.isDark = !this.isDark
      localStorage.setItem('theme', this.isDark ? 'dark' : 'light')
      this.applyTheme()
    },
    async authenticate() {
      this.authError = null
      await this.loadFiles()
    },
    async loadFiles(initialTest = false) {
      this.loading = true
      try {
        const API_BASE = import.meta.env.VITE_API_URL || '/api'
        const res = await fetch(`${API_BASE}/guest_list.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ token: this.token, password: this.password, path: this.currentPath })
        })
        const data = await res.json()

        if (data.success) {
          this.files = data.files
          this.permission = data.permission
          this.authenticated = true
          this.needsPassword = false
          this.authError = null
        } else if (data.requiresPassword) {
          this.needsPassword = true
          if (data.wrongPassword && !initialTest) {
            this.authError = this.$t('guest.wrongPassword')
          }
        } else {
          if (!initialTest) this.showToast(data.message || this.$t('common.error'), 'error')
        }
      } catch (err) {
        if (!initialTest) this.showToast(err.message, 'error')
      } finally {
        this.loading = false
      }
    },
    handleItemClick(item) {
      if (item.isDirectory) {
        const sep = this.currentPath === '/' ? '' : '/'
        this.navigateTo(this.currentPath + sep + item.name)
      }
    },
    navigateTo(path) {
      this.currentPath = path || '/'
      this.loadFiles()
    },
    navigateUp() {
      const parts = this.currentPath.split('/').filter(Boolean)
      parts.pop()
      this.navigateTo('/' + parts.join('/') || '/')
    },
    downloadFile(file) {
      const API_BASE = import.meta.env.VITE_API_URL || '/api'
      const sep = this.currentPath === '/' ? '' : '/'
      const filePath = this.currentPath + sep + file.name
      const url = `${API_BASE}/guest_download.php?token=${encodeURIComponent(this.token)}&path=${encodeURIComponent(filePath)}&password=${encodeURIComponent(this.password)}`
      window.open(url, '_blank')
    },
    async handleUpload(event) {
      const files = Array.from(event.target.files)
      if (!files.length) return
      this.showToast(this.$t('guest.uploadProgress').replace('{count}', files.length), 'info')
      const API_BASE = import.meta.env.VITE_API_URL || '/api'
      let ok = 0, fail = 0
      for (const file of files) {
        const fd = new FormData()
        fd.append('file', file)
        fd.append('token', this.token)
        fd.append('password', this.password)
        fd.append('path', this.currentPath)
        fd.append('remoteName', file.name)
        try {
          const res = await fetch(`${API_BASE}/guest_upload.php`, { method: 'POST', body: fd })
          const data = await res.json()
          if (data.success) {
            ok++
          } else {
            this.showToast(this.$t('guest.uploadFail').replace('{name}', file.name).replace('{msg}', data.message), 'error')
            fail++
          }
        } catch {
          this.showToast(this.$t('guest.uploadError').replace('{name}', file.name), 'error')
          fail++
        }
      }
      if (ok > 0) {
        this.showToast(this.$t('guest.uploadSuccess').replace('{count}', ok), 'success')
        this.loadFiles()
      }
      this.$refs.fileInput.value = ''
    },
    isEditable(filename) {
      if (!filename) return false
      const ext = filename.split('.').pop().toLowerCase()
      return Object.keys(LANG_MAP).includes(ext) || ['env', 'gitignore'].includes(filename)
    },
    async editFile(file) {
      this.fileEditorFile = file
      this.fileEditorContent = ''
      
      const ext = file.name.split('.').pop().toLowerCase()
      this.editorLanguage = LANG_MAP[ext] || 'plaintext'
      
      try {
        const API_BASE = import.meta.env.VITE_API_URL || '/api'
        const sep = this.currentPath === '/' ? '' : '/'
        const filePath = this.currentPath + sep + file.name
        const url = `${API_BASE}/guest_download.php?token=${encodeURIComponent(this.token)}&path=${encodeURIComponent(filePath)}&password=${encodeURIComponent(this.password)}`
        
        this.showToast('Chargement du fichier...', 'info')
        const res = await fetch(url)
        if (!res.ok) throw new Error('Erreur de téléchargement')
        
        const text = await res.text()
        this.fileEditorContent = text
        this.showFileEditor = true
      } catch (err) {
        this.showToast(`Erreur d'ouverture: ${err.message}`, 'error')
      }
    },
    async saveFileEdit() {
      if (!this.fileEditorFile) return
      this.fileEditorSaving = true
      
      try {
        const API_BASE = import.meta.env.VITE_API_URL || '/api'
        
        const blob = new Blob([this.fileEditorContent], { type: 'text/plain' })
        const newFile = new File([blob], this.fileEditorFile.name, { type: 'text/plain' })
        
        const fd = new FormData()
        fd.append('file', newFile)
        fd.append('token', this.token)
        fd.append('password', this.password)
        fd.append('path', this.currentPath)
        fd.append('remoteName', newFile.name)
        
        const res = await fetch(`${API_BASE}/guest_upload.php`, { method: 'POST', body: fd })
        const data = await res.json()
        
        if (data.success) {
          this.showToast(this.$t('common.success'), 'success')
          this.showFileEditor = false
          this.loadFiles()
        } else {
          throw new Error(data.message || 'Erreur inconnue')
        }
      } catch (err) {
        this.showToast(`Erreur lors de la sauvegarde: ${err.message}`, 'error')
      } finally {
        this.fileEditorSaving = false
      }
    },
    formatSize(bytes) {
      if (!bytes) return '0 B'
      const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    },
    formatDate(dateStr) {
      if (!dateStr) return ''
      try {
        return new Date(dateStr).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' })
      } catch { return dateStr }
    }
  }
}
</script>
