<template>
  <div class="flex flex-col h-full bg-surface-0 dark:bg-surface-900">
    <div class="flex items-center gap-2 px-4 py-2 border-b border-surface-200 dark:border-surface-800 shrink-0">
      <button @click="$emit('navigate-up')" class="p-1.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors" :disabled="currentPath === '/'" :class="currentPath === '/' ? 'opacity-30' : ''">
        <ChevronUp class="w-4 h-4" />
      </button>
      <BreadcrumbNav :path="currentPath" @navigate="$emit('navigate', $event)" />
      <div class="flex-1" />
      <button @click="$emit('refresh')" class="p-1.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors" :class="{ 'animate-spin': loading }">
        <RefreshCw class="w-4 h-4" />
      </button>
    </div>
    <div class="flex items-center px-4 py-2 border-b border-surface-200 dark:border-surface-800 text-xs font-semibold text-surface-500 dark:text-surface-400 uppercase tracking-wider shrink-0">
      <div class="flex-1 min-w-0">{{ $t('files.name') }}</div>
      <div class="w-24 text-right">{{ $t('files.size') }}</div>
      <div class="w-24 text-center hidden lg:block">{{ $t('files.type') }}</div>
      <div class="w-36 text-right hidden md:block">{{ $t('files.modified') }}</div>
      <div v-if="isRemote" class="w-32 text-right hidden xl:block">{{ $t('files.permissions') }}</div>
    </div>
    <div class="flex-1 overflow-y-auto pb-24" @dragover.prevent="dragOver = true" @dragleave="dragOver = false" @drop.prevent="handleDrop">
      <div v-if="dragOver" class="absolute inset-0 z-10 bg-primary-500/10 border-2 border-dashed border-primary-500 rounded-xl m-2 flex items-center justify-center pointer-events-none">
        <div class="text-center">
          <Upload class="w-12 h-12 text-primary-500 mx-auto mb-2" />
          <p class="text-lg font-medium text-primary-700 dark:text-primary-300">{{ $t('files.dropHere') }}</p>
        </div>
      </div>
      <div v-if="loading" class="p-8 space-y-3">
        <div v-for="i in 5" :key="i" class="flex items-center gap-4">
          <div class="w-8 h-8 rounded-lg loading-shimmer" />
          <div class="flex-1 h-4 rounded loading-shimmer" />
          <div class="w-20 h-4 rounded loading-shimmer" />
        </div>
      </div>
      <div v-else-if="files.length === 0" class="flex flex-col items-center justify-center h-64 text-center">
        <FolderOpen class="w-16 h-16 text-surface-300 dark:text-surface-700 mb-4" />
        <p class="text-surface-500 dark:text-surface-400 text-sm">{{ $t('files.empty') }}</p>
      </div>
      <template v-else>
        <FileItem v-for="file in sortedFiles" :key="file.name" :file="file" :is-remote="isRemote"
          :selected="selectedFiles.includes(file.name)"
          @select="toggleSelect(file.name, $event)" @dblclick="$emit('file-open', file)"
          @contextmenu="showContextMenu($event, file)" />
      </template>
    </div>
    <div v-if="contextMenu.show" class="fixed z-50 bg-white dark:bg-surface-800 rounded-xl shadow-premium border border-surface-200 dark:border-surface-700 py-1 min-w-[180px]" :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }">
      <button v-for="item in contextMenuItems" :key="item.action" @click="handleContextAction(item.action)"
        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-surface-700 dark:text-surface-300 hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors text-left"
        :class="item.danger ? 'text-rose-600 dark:text-rose-400' : ''">
        <component :is="item.icon" class="w-4 h-4" /> {{ item.label }}
      </button>
    </div>
  </div>
</template>

<script>
import { ChevronUp, RefreshCw, Upload, FolderOpen, Edit3, Download, Edit2, Trash2 } from 'lucide-vue-next'
import FileItem from './FileItem.vue'
import BreadcrumbNav from './BreadcrumbNav.vue'
export default {
  name: 'FileList',
  components: { ChevronUp, RefreshCw, Upload, FolderOpen, FileItem, BreadcrumbNav, Edit3, Download, Edit2, Trash2 },
  props: { files: { type: Array, default: () => [] }, currentPath: { type: String, default: '/' }, isRemote: { type: Boolean, default: false }, loading: { type: Boolean, default: false } },
  emits: ['navigate', 'navigate-up', 'refresh', 'file-open', 'upload', 'download', 'delete', 'rename', 'edit'],
  data() {
    return { dragOver: false, selectedFiles: [], sortBy: 'name', sortAsc: true, contextMenu: { show: false, x: 0, y: 0, file: null } }
  },
  computed: {
    sortedFiles() {
      const files = [...this.files]
      files.sort((a, b) => { if (a.isDirectory && !b.isDirectory) return -1; if (!a.isDirectory && b.isDirectory) return 1; let cmp = 0; if (this.sortBy === 'name') cmp = a.name.localeCompare(b.name); else if (this.sortBy === 'size') cmp = (a.size || 0) - (b.size || 0); return this.sortAsc ? cmp : -cmp })
      return files
    },
    contextMenuItems() {
      const file = this.contextMenu.file; if (!file) return []
      const items = []
      if (file.isDirectory) {
        items.push({ action: 'open', label: 'Ouvrir', icon: 'FolderOpen', danger: false })
      } else {
        items.push({ action: 'edit', label: 'Modifier le contenu', icon: 'Edit3', danger: false })
      }
      items.push({ action: 'download', label: this.$t('files.download'), icon: 'Download', danger: false })
      items.push({ action: 'rename', label: this.$t('files.rename'), icon: 'Edit2', danger: false })
      items.push({ action: 'delete', label: this.$t('files.delete'), icon: 'Trash2', danger: true })
      return items
    }
  },
  mounted() { document.addEventListener('click', this.hideContextMenu); document.addEventListener('keydown', this.handleKeydown) },
  beforeUnmount() { document.removeEventListener('click', this.hideContextMenu); document.removeEventListener('keydown', this.handleKeydown) },
  methods: {
    toggleSelect(name, event) { if (event?.ctrlKey || event?.metaKey) { const idx = this.selectedFiles.indexOf(name); if (idx > -1) this.selectedFiles.splice(idx, 1); else this.selectedFiles.push(name) } else { this.selectedFiles = [name] } this.$emit('selection-change', this.selectedFiles) },
    async handleDrop(event) { 
      this.dragOver = false; 
      window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Début analyse du glisser-déposer...', type: 'info' } }));
      try {
        const items = event.dataTransfer?.items;
        if (!items) {
          window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Aucun élément trouvé', type: 'error' } }));
          return;
        }
        
        const filesToUpload = [];
        const traverseFileTree = async (item, path = '') => {
          if (item.isFile) {
            const file = await new Promise((resolve, reject) => item.file(resolve, reject));
            file.customPath = path + file.name;
            filesToUpload.push(file);
          } else if (item.isDirectory) {
            const dirReader = item.createReader();
            const entries = await new Promise((resolve, reject) => {
              const allEntries = [];
              const readEntries = () => {
                dirReader.readEntries(results => {
                  if (!results.length) {
                    resolve(allEntries);
                  } else {
                    allEntries.push(...results);
                    readEntries();
                  }
                }, reject);
              };
              readEntries();
            });
            for (const entry of entries) {
              await traverseFileTree(entry, path + item.name + '/');
            }
          }
        };

        for (let i = 0; i < items.length; i++) {
          const item = items[i].webkitGetAsEntry ? items[i].webkitGetAsEntry() : null;
          if (item) {
            await traverseFileTree(item);
          } else {
            const file = items[i].getAsFile();
            if (file) filesToUpload.push(file);
          }
        }
        
        if (filesToUpload.length > 0) {
          window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: `${filesToUpload.length} fichiers trouvés`, type: 'success' } }));
          this.$emit('upload', filesToUpload);
        } else {
          window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Dossier vide ou illisible', type: 'error' } }));
        }
      } catch (err) {
        window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: `Erreur d'analyse: ${err.message}`, type: 'error' } }));
      }
    },
    showContextMenu(event, file) { event.preventDefault(); this.contextMenu = { show: true, x: event.clientX, y: event.clientY, file } },
    hideContextMenu() { this.contextMenu.show = false },
    handleContextAction(action) { const file = this.contextMenu.file; if (!file) return; switch (action) { case 'open': this.$emit('file-open', file); break; case 'edit': this.$emit('edit', file); break; case 'download': this.$emit('download', [file]); break; case 'rename': this.$emit('rename', file); break; case 'delete': this.$emit('delete', [file]); break } this.hideContextMenu() },
    handleKeydown(e) { if (e.key === 'Delete' && this.selectedFiles.length > 0) { const toDelete = this.files.filter(f => this.selectedFiles.includes(f.name)); this.$emit('delete', toDelete) } if (e.key === 'a' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); this.selectedFiles = this.files.map(f => f.name); this.$emit('selection-change', this.selectedFiles) } }
  }
}
</script>
