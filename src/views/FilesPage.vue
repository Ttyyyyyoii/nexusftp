<template>
  <AppLayout>
    <div class="h-full flex flex-col relative">
      <div class="flex items-center gap-2 px-4 py-2 border-b border-surface-200 dark:border-surface-800 bg-surface-50/50 dark:bg-surface-900/50 shrink-0">
        <button v-for="action in toolbarActions" :key="action.id" @click="action.handler" :disabled="action.disabled"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all disabled:opacity-30 disabled:cursor-not-allowed"
          :class="action.danger ? 'text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20' : 'text-surface-700 dark:text-surface-300 hover:bg-surface-100 dark:hover:bg-surface-800'">
          <component :is="action.icon" class="w-4 h-4" /><span class="hidden sm:inline">{{ action.label }}</span>
        </button>
        <div class="flex-1" />
        <button @click="showSearch = true" :disabled="!connectionStore.isConnected" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-all disabled:opacity-30">
          <SearchIcon class="w-4 h-4" /><span class="hidden sm:inline">{{ $t('files.searchBtn') }}</span>
        </button>
        <button @click="toggleAI" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all">
          <Bot class="w-4 h-4" /><span class="hidden sm:inline">{{ $t('files.aiBot') }}</span>
        </button>
        <button @click="openGitHub" :disabled="!connectionStore.isConnected" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all disabled:opacity-30">
          <Github class="w-4 h-4" /><span class="hidden sm:inline">{{ $t('files.githubBtn') }}</span>
        </button>
        <button @click="showGuestShare = true" :disabled="!connectionStore.isConnected" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all disabled:opacity-30">
          <Users class="w-4 h-4" /><span class="hidden sm:inline">{{ $t('guest.inviteBtn') }}</span>
        </button>
        <button @click="openCreateModal('file')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all">
          <FilePlus class="w-4 h-4" /><span class="hidden sm:inline">{{ $t('files.newFile') }}</span>
        </button>
        <button @click="openCreateModal('folder')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all">
          <FolderPlus class="w-4 h-4" /><span class="hidden sm:inline">{{ $t('files.newFolder') }}</span>
        </button>
      </div>
      <div class="flex-1 overflow-hidden">
        <splitpanes class="h-full" @resized="onPaneResized">
          <pane min-size="20" :size="leftPanelSize">
            <div class="h-full flex flex-col border-r border-surface-200 dark:border-surface-800">
              <div class="px-4 py-2 bg-surface-100/50 dark:bg-surface-800/50 border-b border-surface-200 dark:border-surface-800 flex items-center gap-2">
                <Monitor class="w-4 h-4 text-surface-500" />
                <span class="text-xs font-semibold text-surface-600 dark:text-surface-400 uppercase tracking-wider">{{ $t('files.local') }}</span>
              </div>
              <LocalFileBrowser ref="localBrowser" @files-selected="localFilesSelected = $event" @upload-direct="handleUpload" />
            </div>
          </pane>
          <pane min-size="20" :size="100 - leftPanelSize">
            <div class="h-full flex flex-col">
              <div class="px-4 py-2 bg-surface-100/50 dark:bg-surface-800/50 border-b border-surface-200 dark:border-surface-800 flex items-center gap-2">
                <Globe class="w-4 h-4 text-surface-500" />
                <span class="text-xs font-semibold text-surface-600 dark:text-surface-400 uppercase tracking-wider">{{ $t('files.remote') }}</span>
                <div v-if="loading" class="ml-auto"><Loader2 class="w-3 h-3 animate-spin text-primary-500" /></div>
              </div>
              <FileList v-if="connectionStore.isConnected" :files="connectionStore.remoteFiles" :current-path="connectionStore.currentPath"
                :is-remote="true" :loading="loading" @navigate="navigateRemote" @navigate-up="navigateUp" @refresh="refreshRemote"
                @selection-change="remoteFilesSelected = $event"
                @file-open="openFile" @upload="handleUpload" @download="handleDownload" @delete="handleDelete" @rename="handleRename" @edit="handleEdit" @share="handleShare" @optimize="handleOptimize" />
              <div v-else class="flex flex-col items-center justify-center h-full text-center p-8">
                <Unlink class="w-12 h-12 text-surface-300 dark:text-surface-700 mb-4" />
                <p class="text-sm text-surface-500 dark:text-surface-400 mb-4">{{ $t('statusbar.notConnected') }}</p>
                <router-link to="/connect" class="btn-primary text-sm">Connect Now</router-link>
              </div>
            </div>
          </pane>
        </splitpanes>
      </div>
      <TransferPanel v-if="transfersStore.transfers.length > 0" />
      
      <!-- Create Modal -->
      <BaseModal :visible="showCreateModal" :title="createType === 'folder' ? 'Créer un dossier' : 'Créer un fichier'" @close="showCreateModal = false">
        <input v-model="newItemName" type="text" :placeholder="createType === 'folder' ? 'Nom du dossier' : 'nom_du_fichier.txt'" class="w-full px-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200" @keyup.enter="confirmCreate" />
        <template #footer>
          <button @click="showCreateModal = false" class="btn-secondary text-sm">{{ $t('common.cancel') }}</button>
          <button @click="confirmCreate" class="btn-primary text-sm">{{ $t('files.create') }}</button>
        </template>
      </BaseModal>
      
      <!-- Rename Modal -->
      <BaseModal :visible="showRename" title="Rename" @close="showRename = false">
        <input v-model="renameNewName" type="text" class="w-full px-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200" @keyup.enter="confirmRename" />
        <template #footer>
          <button @click="showRename = false" class="btn-secondary text-sm">{{ $t('common.cancel') }}</button>
          <button @click="confirmRename" class="btn-primary text-sm">{{ $t('files.rename') }}</button>
        </template>
      </BaseModal>
      
      <!-- Delete Modal -->
      <BaseModal :visible="showDelete" :title="$t('files.confirmDeleteTitle')" @close="showDelete = false">
        <p class="text-surface-600 dark:text-surface-400">{{ $t('files.confirmDelete', { count: filesToDelete.length }) }}</p>
        <template #footer>
          <button @click="showDelete = false" class="btn-secondary text-sm">{{ $t('common.cancel') }}</button>
          <button @click="confirmDelete" class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-rose-500 hover:bg-rose-600 transition-colors">{{ $t('files.delete') }}</button>
        </template>
      </BaseModal>
      
      <!-- Edit File Modal with Monaco Editor -->
      <BaseModal :visible="showFileEditor" :title="'✏️ Modifier: ' + (fileEditorFile?.name || '')" @close="showFileEditor = false" maxWidth="max-w-5xl">
        <div class="h-[65vh] -mx-2 -mt-2 rounded-xl overflow-hidden border border-surface-200 dark:border-surface-700">
          <MonacoEditor v-if="showFileEditor" v-model="fileEditorContent" :language="editorLanguage" />
        </div>
        <template #footer>
          <button @click="showFileEditor = false" class="btn-secondary text-sm" :disabled="isEditingFile">{{ $t('common.cancel') }}</button>
          <button @click="saveFileEdit" class="btn-primary text-sm flex items-center gap-2" :disabled="isEditingFile">
            <Loader2 v-if="isEditingFile" class="w-4 h-4 animate-spin" />
            Sauvegarder
          </button>
        </template>
      </BaseModal>
      
      <!-- Global Loader Overlay -->
      <div v-if="globalLoader.show" class="absolute inset-0 z-50 bg-white/80 dark:bg-surface-950/80 backdrop-blur-sm flex flex-col items-center justify-center">
        <div class="bg-white dark:bg-surface-800 p-8 rounded-2xl shadow-xl flex flex-col items-center max-w-sm w-full text-center">
          <Loader2 class="w-12 h-12 text-primary-500 animate-spin mb-4" />
          <h3 class="text-lg font-bold text-surface-900 dark:text-white mb-2">{{ globalLoader.title }}</h3>
          <p class="text-sm text-surface-500 dark:text-surface-400 mb-4">{{ globalLoader.message }}</p>
          
          <div v-if="globalLoader.total && globalLoader.total > 1" class="w-full flex flex-col gap-1">
            <div class="w-full bg-surface-100 dark:bg-surface-700 rounded-full h-2">
              <div class="bg-primary-500 h-2 rounded-full transition-all duration-300" :style="{ width: `${(globalLoader.progress / globalLoader.total) * 100}%` }"></div>
            </div>
            <p class="text-xs text-surface-400 font-medium text-right">
              {{ Math.round((globalLoader.progress / globalLoader.total) * 100) }}%
            </p>
          </div>
        </div>
      </div>

      <!-- Search Modal -->
      <SearchModal :visible="showSearch" @close="showSearch = false" @navigate="handleSearchNavigate" />

      <!-- Share Modal -->
      <ShareModal :visible="showShareModal" :file="shareFile" :remote-path="connectionStore.currentPath" @close="showShareModal = false" />
      
      <!-- Media Viewer -->
      <MediaViewer :visible="showMediaViewer" :file="mediaViewerFile" :remote-path="connectionStore.currentPath" @close="showMediaViewer = false" />

      <!-- AI Assistant -->
      <AIAssistant :visible="showAI" :fileContext="aiContextFile" :fileContent="aiContextContent" @close="showAI = false" @clear-context="aiContextFile = null; aiContextContent = ''" />

      <!-- GitHub Deploy Modal -->
      <GitHubDeployModal :visible="showGitHub" :current-path="connectionStore.currentPath" :session-id="connectionStore.sessionId" @close="showGitHub = false" @refresh-files="refreshRemote" />
      <!-- Guest Share Modal -->
      <GuestShareModal :visible="showGuestShare" :current-path="connectionStore.currentPath" :session-id="connectionStore.activeSessionId" @close="showGuestShare = false" />
      
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { Splitpanes, Pane } from 'splitpanes'
import 'splitpanes/dist/splitpanes.css'
import { useConnectionStore } from '@/stores/connection'
import { useTransfersStore } from '@/stores/transfers'
import { useSettingsStore } from '@/stores/settings'
import { useLogStore } from '@/stores/log'
import FileList from '@/components/ftp/FileList.vue'
import LocalFileBrowser from '@/components/ftp/LocalFileBrowser.vue'
import TransferPanel from '@/components/transfers/TransferPanel.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import MonacoEditor from '@/components/ui/MonacoEditor.vue'
import SearchModal from '@/components/ftp/SearchModal.vue'
import ShareModal from '@/components/ui/ShareModal.vue'
import MediaViewer from '@/components/ui/MediaViewer.vue'
import AIAssistant from '@/components/ftp/AIAssistant.vue'
import GitHubDeployModal from '@/components/ui/GitHubDeployModal.vue'
import GuestShareModal from '@/components/ui/GuestShareModal.vue'
import { Upload, Download, Trash2, Edit2, RefreshCw, FolderPlus, FilePlus, Monitor, Globe, Loader2, Unlink, Search as SearchIcon, Bot, Github, Users } from 'lucide-vue-next'

const LANG_MAP = {
  js: 'javascript', ts: 'typescript', vue: 'html', html: 'html', htm: 'html',
  css: 'css', scss: 'scss', less: 'less', php: 'php', py: 'python',
  json: 'json', xml: 'xml', md: 'markdown', sh: 'shell', bash: 'shell',
  sql: 'sql', yaml: 'yaml', yml: 'yaml', txt: 'plaintext', env: 'plaintext'
}

export default {
  name: 'FilesPage',
  components: { AppLayout, Splitpanes, Pane, FileList, LocalFileBrowser, TransferPanel, BaseModal, MonacoEditor, SearchModal, ShareModal, MediaViewer, AIAssistant, GitHubDeployModal, GuestShareModal, Monitor, Globe, Loader2, Unlink, FolderPlus, FilePlus, Upload, Download, Trash2, Edit2, RefreshCw, SearchIcon, Bot, Github, Users },
  data() {
    return {
      connectionStore: useConnectionStore(),
      transfersStore: useTransfersStore(),
      logStore: useLogStore(),
      settingsStore: useSettingsStore(),
      loading: false, leftPanelSize: 50, localFilesSelected: [], remoteFilesSelected: [],
      showCreateModal: false, createType: 'folder', newItemName: '',
      showRename: false, renameOldName: '', renameNewName: '',
      showDelete: false, filesToDelete: [],
      showFileEditor: false, fileEditorFile: null, fileEditorContent: '', fileEditorSaving: false,
      deployPollInterval: null,
      lastDeployTimestamp: 0,
      showSearch: false,
      showShareModal: false, shareFile: null,
      showGuestShare: false,
      showMediaViewer: false, mediaViewerFile: null,
      showAI: false, aiContextFile: null, aiContextContent: '',
      showGitHub: false,
      globalLoader: { show: false, title: '', message: '' }
    }
  },
  computed: {
    toolbarActions() {
      return [
        { id: 'upload', label: this.$t('files.upload'), icon: 'Upload', handler: this.triggerUpload, disabled: !this.connectionStore.isConnected },
        { id: 'download', label: this.$t('files.download'), icon: 'Download', handler: this.triggerDownload, disabled: !this.connectionStore.isConnected || this.remoteFilesSelected.length === 0 },
        { id: 'delete', label: this.$t('files.delete'), icon: 'Trash2', handler: this.triggerDelete, disabled: !this.connectionStore.isConnected || this.remoteFilesSelected.length === 0, danger: true },
        { id: 'refresh', label: this.$t('files.refresh'), icon: 'RefreshCw', handler: this.refreshRemote, disabled: !this.connectionStore.isConnected }
      ]
    },
    editorLanguage() {
      if (!this.fileEditorFile?.name) return 'plaintext'
      const ext = this.fileEditorFile.name.split('.').pop()?.toLowerCase() || ''
      return LANG_MAP[ext] || 'plaintext'
    }
  },
  watch: {
    'connectionStore.activeSessionId': {
      handler(newId) {
        if (newId) {
          this.refreshRemote()
        }
      }
    }
  },
  async mounted() { 
    if (this.connectionStore.isConnected) await this.refreshRemote() 
    this.deployPollInterval = setInterval(this.checkDeployStatus, 5000)
  },
  beforeUnmount() {
    if (this.deployPollInterval) clearInterval(this.deployPollInterval)
  },
  methods: {
    async checkDeployStatus() {
      if (!this.connectionStore.isConnected) return
      try {
        const res = await fetch(`${import.meta.env.VITE_API_URL || '/api'}/github_status.php`)
        const data = await res.json()
        if (data && data.success && data.lastDeploy) {
          const deploy = data.lastDeploy
          // Si le déploiement a réussi et est plus récent que ce qu'on a déjà vu
          if (deploy.timestamp > this.lastDeployTimestamp) {
            // Si on avait déjà enregistré un timestamp (pas le premier chargement de page)
            if (this.lastDeployTimestamp > 0) {
              this.showToast(`Déploiement GitHub ${deploy.repo} détecté. Actualisation...`, 'success')
              
              // Nettoyer les slashes de fin pour la comparaison
              const current = this.connectionStore.currentPath.replace(/\/$/, '') || '/'
              const target = deploy.remotePath.replace(/\/$/, '') || '/'
              
              // On rafraîchit si on est dans le même dossier ou à la racine
              if (current === target || current === '/') {
                await this.refreshRemote()
              }
            }
            this.lastDeployTimestamp = deploy.timestamp
          }
        }
      } catch (err) {
        // Silencieux
      }
    },
    onPaneResized(event) { this.leftPanelSize = event[0].size },
    async navigateRemote(path) { this.loading = true; try { await this.connectionStore.listRemotePath(path); this.logStore.logInfo(`Navigated to ${path}`) } catch (err) { this.logStore.logError(`Navigation failed: ${err.message}`) } this.loading = false },
    async navigateUp() { const path = this.connectionStore.currentPath; const parent = path.split('/').filter(Boolean).slice(0, -1).join('/'); await this.navigateRemote('/' + parent) },
    async refreshRemote() { this.loading = true; try { await this.connectionStore.listRemotePath(this.connectionStore.currentPath) } catch (err) { console.error('Refresh error:', err) } this.loading = false; this.remoteFilesSelected = []; },
    openFile(file) { 
      if (file.isDirectory) { 
        const newPath = this.connectionStore.currentPath.replace(/\/$/, '') + '/' + file.name; 
        this.navigateRemote(newPath + '/') 
      } else {
        const ext = file.name.split('.').pop().toLowerCase()
        const mediaExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'mp4', 'webm', 'ogg', 'mov', 'mp3', 'wav', 'm4a']
        if (mediaExts.includes(ext)) {
          this.mediaViewerFile = file
          this.showMediaViewer = true
        } else {
          this.handleEdit(file)
        }
      } 
    },
    openGitHub() {
      if (!this.settingsStore.isPremium) {
        window.dispatchEvent(new CustomEvent('show-premium-modal'));
        return;
      }
      this.showGitHub = true;
    },
    handleShare(file) {
      if (!this.settingsStore.isPremium) {
        window.dispatchEvent(new CustomEvent('show-premium-modal'));
        return;
      }
      this.shareFile = file;
      this.showShareModal = true;
    },
    async handleOptimize(file) {
      if (!this.settingsStore.isPremium) {
        window.dispatchEvent(new CustomEvent('show-premium-modal'));
        return;
      }
      if (!this.settingsStore.allowImageOptimization) {
        this.showToast('L\'optimisation d\'images est désactivée dans vos paramètres. Veuillez l\'activer pour l\'utiliser.', 'warning');
        return;
      }
      
      this.globalLoader = { show: true, title: 'Optimisation en cours', message: `Compression in-place de ${file.name}...` };
      try {
        const response = await fetch(`${import.meta.env.VITE_API_URL || '/api'}/optimize.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({
            sessionId: this.connectionStore.sessionId,
            remotePath: this.connectionStore.currentPath,
            remoteName: file.name
          })
        });
        const data = await response.json();
        if (data.success) {
          this.showToast(`${file.name} optimisé (-${data.savedPercentage}%)`, 'success');
          this.refreshRemote();
        } else {
          this.showToast(data.message || 'Erreur lors de l\'optimisation', 'error');
        }
      } catch (err) {
        this.showToast('Erreur réseau', 'error');
      } finally {
        this.globalLoader.show = false;
      }
    },
    toggleAI() {
      if (!this.settingsStore.isPremium) {
        window.dispatchEvent(new CustomEvent('show-premium-modal'));
        return;
      }
      this.showAI = !this.showAI;
    },
    async handleEdit(file) {
      if (file.isDirectory) return;
      this.globalLoader = { show: true, title: 'Ouverture en cours', message: `Téléchargement de ${file.name}...` };
      try {
        const blob = await this.connectionStore.downloadFile(file);
        this.fileEditorContent = await blob.text();
        this.fileEditorFile = file;
        this.showFileEditor = true;
        
        // Also update AI context if AI is open
        if (this.showAI) {
          this.aiContextFile = file;
          this.aiContextContent = this.fileEditorContent;
        }
      } catch (err) {
        this.showToast(`Erreur de téléchargement: ${err.message}`, 'error');
      } finally {
        this.globalLoader.show = false;
      }
    },
    async saveFileEdit() {
      this.isEditingFile = true;
      try {
        const blob = new Blob([this.fileEditorContent], { type: 'text/plain' });
        const newFile = new File([blob], this.fileEditorFile.name, { type: 'text/plain' });
        await this.connectionStore.uploadFile(newFile, this.connectionStore.currentPath);
        this.showFileEditor = false;
        this.showToast('Fichier sauvegardé avec succès', 'success');
        await this.refreshRemote();
      } catch (err) {
        this.showToast(`Erreur lors de la sauvegarde: ${err.message}`, 'error');
      } finally {
        this.isEditingFile = false;
      }
    },
    async handleUpload(files) { 
      const settingsStore = useSettingsStore()
      const maxSimultaneous = settingsStore.planLimits.maxSimultaneous
      const maxFileSizeMB = settingsStore.planLimits.maxFileSize
      const maxFileSizeBytes = maxFileSizeMB * 1024 * 1024

      // Filter files by size
      const validFiles = []
      for (const file of files) {
        if (file.size > maxFileSizeBytes) {
          this.showToast(`Le fichier ${file.name} dépasse la limite de ${maxFileSizeMB} MB`, 'error')
        } else {
          validFiles.push(file)
        }
      }

      if (validFiles.length === 0) return;
      if (validFiles.length < files.length && !settingsStore.isPremium) {
        window.dispatchEvent(new CustomEvent('show-premium-modal'))
      }

      const total = validFiles.length;
      this.globalLoader = { 
        show: true, 
        title: 'Sending files', 
        message: `Preparing upload...`,
        progress: 0,
        total: total
      };
      
      let successCount = 0;
      let activeUploads = 0;
      let currentIndex = 0;
      
      return new Promise((resolve) => {
        const nextUpload = async () => {
          if (currentIndex >= total && activeUploads === 0) {
            this.globalLoader.show = false;
            if (successCount > 0) this.showToast(`${successCount} fichier(s) envoyé(s)`, 'success') 
            await this.refreshRemote();
            resolve();
            return;
          }
          
          while (activeUploads < maxSimultaneous && currentIndex < total) {
            const i = currentIndex++;
            activeUploads++;
            const file = validFiles[i];
            
            let targetPath = this.connectionStore.currentPath;
            let relativeDir = '';
            const relativePath = file.customPath || file.webkitRelativePath;
            if (relativePath) {
              const parts = relativePath.split('/');
              if (parts.length > 1) {
                parts.pop();
                relativeDir = '/' + parts.join('/');
                targetPath = (targetPath === '/' ? '' : targetPath) + relativeDir;
              }
            }
            
            this.globalLoader.message = `Envoi de ${successCount} sur ${total}: ${file.name}`;
            
            const transferId = this.transfersStore.addTransfer(file, 'upload', targetPath); 
            
            this.connectionStore.uploadFile(file, targetPath).then(() => {
              this.transfersStore.completeTransfer(transferId); 
              successCount++;
            }).catch((err) => {
              this.transfersStore.failTransfer(transferId, err.message); 
              this.showToast(`Échec de l'envoi (${file.name}): ${err.message}`, 'error') 
            }).finally(() => {
              activeUploads--;
              this.globalLoader.progress = successCount;
              this.globalLoader.message = `Envoi de ${successCount} sur ${total}...`;
              nextUpload();
            });
          }
        };
        nextUpload();
      });
    },
    async handleDownload(files) { for (const file of files) { if (file.isDirectory) continue; const transferId = this.transfersStore.addTransfer({ name: file.name, size: file.size, path: '' }, 'download', this.connectionStore.currentPath); try { const blob = await this.connectionStore.downloadFile(file); const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = file.name; document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url); this.transfersStore.completeTransfer(transferId); this.showToast(`Downloaded ${file.name}`, 'success') } catch (err) { this.transfersStore.failTransfer(transferId, err.message); this.showToast(`Download failed: ${file.name}`, 'error') } } },
    handleDelete(files) { this.filesToDelete = files; this.showDelete = true },
    async confirmDelete() { 
      this.showDelete = false; 
      const total = this.filesToDelete.length;
      this.globalLoader = { 
        show: true, 
        title: 'Suppression en cours', 
        message: `Préparation de la suppression...`,
        progress: 0,
        total: 0
      };
      let successCount = 0;
      try { 
        const items = this.filesToDelete.map(f => ({ name: f.name, isDirectory: f.isDirectory }));
        this.globalLoader.message = `Suppression en cours...`;
        await this.connectionStore.deleteRemoteItems(
          items, 
          this.connectionStore.currentPath, 
          (progressMsg) => {
            this.globalLoader.message = progressMsg;
          }
        ); 
        successCount = items.length;
      } catch (err) { 
        this.showToast(`Échec de la suppression: ${err.message}`, 'error') 
      } 
      this.globalLoader.show = false;
      if (successCount > 0) this.showToast(`${successCount} élément(s) supprimé(s)`, 'success');
      await this.refreshRemote() 
    },
    handleRename(file) { this.renameOldName = file.name; this.renameNewName = file.name; this.showRename = true },
    async confirmRename() { this.showRename = false; try { await this.connectionStore.renameRemoteItem(this.renameOldName, this.renameNewName, this.connectionStore.currentPath); this.showToast('Renamed successfully', 'success'); await this.refreshRemote() } catch (err) { this.showToast('Rename failed', 'error') } },
    openCreateModal(type) {
      this.createType = type;
      this.newItemName = '';
      this.showCreateModal = true;
    },
    async confirmCreate() { 
      if (!this.newItemName.trim()) return; 
      this.showCreateModal = false; 
      
      const isFolder = this.createType === 'folder';
      this.globalLoader = { 
        show: true, 
        title: isFolder ? 'Création du dossier' : 'Création du fichier', 
        message: `Création de "${this.newItemName}" en cours...` 
      };
      
      try { 
        if (isFolder) {
          await this.connectionStore.createRemoteFolder(this.newItemName, this.connectionStore.currentPath); 
        } else {
          await this.connectionStore.createRemoteFile(this.newItemName, this.connectionStore.currentPath); 
        }
        this.globalLoader.show = false;
        this.showToast(`${isFolder ? 'Dossier' : 'Fichier'} "${this.newItemName}" créé`, 'success'); 
        await this.refreshRemote() 
      } catch (err) { 
        this.globalLoader.show = false;
        this.showToast(`Erreur: ${err.message}`, 'error') 
      } 
    },
    triggerUpload() { if (this.$refs.localBrowser) this.$refs.localBrowser.triggerSelect() },
    triggerDownload() { const selected = this.connectionStore.remoteFiles.filter(f => this.remoteFilesSelected.includes(f.name)); if (selected.length > 0) this.handleDownload(selected) },
    triggerDelete() { const selected = this.connectionStore.remoteFiles.filter(f => this.remoteFilesSelected.includes(f.name)); if (selected.length > 0) this.handleDelete(selected) },
    handleShare(file) { this.shareFile = file; this.showShareModal = true },
    async handleSearchNavigate(path, targetFile) {
      await this.navigateRemote(path)
      if (targetFile) {
        setTimeout(() => {
          const id = 'file-item-' + targetFile.name.replace(/[^a-zA-Z0-9]/g, '_')
          const el = document.getElementById(id)
          if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' })
            const highlightClasses = ['bg-primary-100', 'dark:bg-primary-900/50', 'duration-1000']
            el.classList.add(...highlightClasses)
            setTimeout(() => {
              el.classList.remove(...highlightClasses)
            }, 3000)
          }
        }, 500)
      }
    },
    showToast(title, type) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { title, type } })) }
  }
}
</script>
