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
        <button @click="openCreateModal('file')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all">
          <FilePlus class="w-4 h-4" /><span class="hidden sm:inline">Nouveau Fichier</span>
        </button>
        <button @click="openCreateModal('folder')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all">
          <FolderPlus class="w-4 h-4" /><span class="hidden sm:inline">Nouveau Dossier</span>
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
                @file-open="openFile" @upload="handleUpload" @download="handleDownload" @delete="handleDelete" @rename="handleRename" @edit="handleEdit" />
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
      
      <!-- Edit File Modal -->
      <BaseModal :visible="showFileEditor" :title="'Modifier: ' + (fileEditorFile?.name || '')" @close="showFileEditor = false" maxWidth="max-w-4xl">
        <div class="h-[60vh] flex flex-col -mx-2 -mt-2">
          <textarea v-model="fileEditorContent" class="flex-1 w-full p-4 font-mono text-sm bg-surface-50 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-xl focus:ring-2 focus:ring-primary-500 dark:text-surface-200 resize-none outline-none" spellcheck="false"></textarea>
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
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { Splitpanes, Pane } from 'splitpanes'
import 'splitpanes/dist/splitpanes.css'
import { useConnectionStore } from '@/stores/connection'
import { useTransfersStore } from '@/stores/transfers'
import { useLogStore } from '@/stores/log'
import FileList from '@/components/ftp/FileList.vue'
import LocalFileBrowser from '@/components/ftp/LocalFileBrowser.vue'
import TransferPanel from '@/components/transfers/TransferPanel.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { Upload, Download, Trash2, Edit2, RefreshCw, FolderPlus, FilePlus, Monitor, Globe, Loader2, Unlink } from 'lucide-vue-next'
export default {
  name: 'FilesPage',
  components: { AppLayout, Splitpanes, Pane, FileList, LocalFileBrowser, TransferPanel, BaseModal, Monitor, Globe, Loader2, Unlink, FolderPlus, FilePlus, Upload, Download, Trash2, Edit2, RefreshCw },
  data() {
    return {
      connectionStore: useConnectionStore(),
      transfersStore: useTransfersStore(),
      logStore: useLogStore(),
      loading: false, leftPanelSize: 50, localFilesSelected: [], remoteFilesSelected: [],
      showCreateModal: false, createType: 'folder', newItemName: '',
      showRename: false, renameOldName: '', renameNewName: '',
      showDelete: false, filesToDelete: [],
      showFileEditor: false, fileEditorFile: null, fileEditorContent: '', isEditingFile: false,
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
  async mounted() { if (this.connectionStore.isConnected) await this.refreshRemote() },
  methods: {
    onPaneResized(event) { this.leftPanelSize = event[0].size },
    async navigateRemote(path) { this.loading = true; try { await this.connectionStore.listRemotePath(path); this.logStore.logInfo(`Navigated to ${path}`) } catch (err) { this.logStore.logError(`Navigation failed: ${err.message}`) } this.loading = false },
    async navigateUp() { const path = this.connectionStore.currentPath; const parent = path.split('/').filter(Boolean).slice(0, -1).join('/'); await this.navigateRemote('/' + parent) },
    async refreshRemote() { this.loading = true; try { await this.connectionStore.listRemotePath(this.connectionStore.currentPath) } catch (err) { console.error('Refresh error:', err) } this.loading = false; this.remoteFilesSelected = []; },
    openFile(file) { if (file.isDirectory) { const newPath = this.connectionStore.currentPath.replace(/\/$/, '') + '/' + file.name; this.navigateRemote(newPath + '/') } },
    async handleEdit(file) {
      if (file.isDirectory) return;
      this.globalLoader = { show: true, title: 'Ouverture en cours', message: `Téléchargement de ${file.name}...` };
      try {
        const blob = await this.connectionStore.downloadFile(file);
        this.fileEditorContent = await blob.text();
        this.fileEditorFile = file;
        this.showFileEditor = true;
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
      const total = files.length;
      this.globalLoader = { 
        show: true, 
        title: 'Envoi en cours', 
        message: `Préparation de l'envoi...`,
        progress: 0,
        total: total
      };
      
      let successCount = 0;
      for (let i = 0; i < total; i++) {
        const file = files[i];
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
        
        this.globalLoader.message = `Envoi de ${file.name} (${i + 1}/${total})...`;
        const transferId = this.transfersStore.addTransfer(file, 'upload', targetPath); 
        try { 
          await this.connectionStore.uploadFile(file, targetPath); 
          this.transfersStore.completeTransfer(transferId); 
          successCount++;
        } catch (err) { 
          this.transfersStore.failTransfer(transferId, err.message); 
          this.showToast(`Échec de l'envoi: ${file.name}`, 'error') 
        } 
        this.globalLoader.progress = i + 1;
      } 
      
      this.globalLoader.show = false;
      if (successCount > 0) this.showToast(`${successCount} fichier(s) envoyé(s)`, 'success') 
      await this.refreshRemote() 
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
        total: total
      };
      let successCount = 0;
      for (let i = 0; i < total; i++) {
        const file = this.filesToDelete[i];
        this.globalLoader.message = `Suppression de ${file.name} (${i + 1}/${total})...`;
        try { 
          await this.connectionStore.deleteRemoteItem(file.name, this.connectionStore.currentPath, file.isDirectory); 
          successCount++;
        } catch (err) { 
          this.showToast(`Échec de la suppression: ${file.name}. ${err.message}`, 'error') 
        } 
        this.globalLoader.progress = i + 1;
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
    showToast(title, type) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { title, type } })) }
  }
}
</script>
