<template>
  <div class="h-full flex flex-col bg-surface-50/30 dark:bg-surface-900/30 relative">
    <!-- Dropzone Content -->
    <div 
      class="flex-1 p-6 flex flex-col items-center justify-center text-center transition-colors relative h-full"
      :class="{ 'bg-primary-50/50 dark:bg-primary-900/20 ring-2 ring-inset ring-primary-500': isDragging }"
      @dragenter.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @dragover.prevent="isDragging = true"
      @drop.prevent="handleDrop"
    >
      <input ref="fileInput" type="file" multiple class="hidden" @change="handleFileSelect" />
      <input ref="folderInput" type="file" webkitdirectory directory multiple class="hidden" @change="handleFolderSelect" />
      
      <div class="w-20 h-20 mb-6 rounded-3xl bg-white dark:bg-surface-800 shadow-sm border border-surface-100 dark:border-surface-700 flex items-center justify-center transform transition-all duration-300" :class="{ 'scale-110 shadow-md ring-4 ring-primary-100 dark:ring-primary-900/30': isDragging }">
        <UploadCloud class="w-10 h-10 text-primary-500" />
      </div>
      
      <h3 class="text-xl font-bold text-surface-900 dark:text-white mb-3 transition-colors" :class="{ 'text-primary-600 dark:text-primary-400': isDragging }">
        {{ isDragging ? 'Relâchez pour envoyer' : 'Espace Local' }}
      </h3>
      
      <p v-if="!isDragging" class="text-surface-500 dark:text-surface-400 max-w-sm mb-8 text-sm leading-relaxed">
        Pour des raisons de sécurité, le navigateur ne peut pas afficher vos fichiers locaux. <br/><br/>
        <b>Glissez-déposez</b> vos fichiers ici pour les envoyer sur le serveur, ou utilisez les boutons ci-dessous.
      </p>
      
      <div v-if="!isDragging" class="flex flex-col sm:flex-row gap-4 w-full max-w-sm">
        <button @click="$refs.fileInput.click()" class="flex-1 btn-primary py-3 flex items-center justify-center gap-2 shadow-glow">
          <FilePlus class="w-4 h-4" /> Fichiers
        </button>
        <button @click="$refs.folderInput.click()" class="flex-1 btn-secondary py-3 flex items-center justify-center gap-2 border-2">
          <FolderPlus class="w-4 h-4" /> Dossier
        </button>
      </div>
      
      <!-- Overlay quand on drag -->
      <div v-if="isDragging" class="absolute inset-0 bg-white/50 dark:bg-surface-900/50 backdrop-blur-sm pointer-events-none rounded-lg"></div>
    </div>
  </div>
</template>

<script>
import { UploadCloud, FilePlus, FolderPlus } from 'lucide-vue-next'

export default {
  name: 'LocalFileBrowser',
  components: { UploadCloud, FilePlus, FolderPlus },
  emits: ['upload-direct'],
  data() {
    return {
      isDragging: false
    }
  },
  methods: {
    handleFileSelect(event) {
      const files = Array.from(event.target.files || [])
      if (files.length > 0) this.$emit('upload-direct', files)
      event.target.value = '' 
    },
    handleFolderSelect(event) {
      const files = Array.from(event.target.files || [])
      if (files.length > 0) this.$emit('upload-direct', files)
      event.target.value = '' 
    },
    handleDrop(event) {
      this.isDragging = false
      const files = []
      
      if (event.dataTransfer.items) {
        Array.from(event.dataTransfer.files).forEach(f => files.push(f))
      } else {
        Array.from(event.dataTransfer.files).forEach(f => files.push(f))
      }
      
      if (files.length > 0) this.$emit('upload-direct', files)
    },
    triggerSelect() {
      this.$refs.fileInput.click()
    }
  }
}
</script>
