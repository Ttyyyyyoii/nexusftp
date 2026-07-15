<template>
  <div class="file-row flex items-center px-4 py-2 cursor-pointer transition-all duration-150 border-b border-surface-100 dark:border-surface-800/50"
    :class="{ 'bg-primary-50/50 dark:bg-primary-900/10': selected, 'hover:bg-surface-50 dark:hover:bg-surface-800/30': !selected }"
    @click="$emit('select', $event)" @dblclick="$emit('dblclick', file)" @contextmenu="$emit('contextmenu', $event, file)"
    draggable="true" @dragstart="$emit('dragstart', file)">
    <div class="w-6 flex items-center justify-center mr-2 shrink-0" @click.stop>
      <input type="checkbox" :checked="selected" @change="$emit('select', { ctrlKey: true })" class="w-4 h-4 rounded border-surface-300 dark:border-surface-600 bg-surface-0 dark:bg-surface-800 text-primary-600 focus:ring-primary-500 cursor-pointer" />
    </div>
    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 shrink-0"
      :class="file.isDirectory ? 'bg-primary-100 dark:bg-primary-900/20' : 'bg-surface-100 dark:bg-surface-800'">
      <Folder v-if="file.isDirectory" class="w-4 h-4 text-primary-500" />
      <FileImage v-else-if="file.type === 'image'" class="w-4 h-4 text-violet-500" />
      <FileText v-else-if="file.type === 'text' || file.type === 'code'" class="w-4 h-4 text-amber-500" />
      <FileAudio v-else-if="file.type === 'audio'" class="w-4 h-4 text-rose-500" />
      <FileVideo v-else-if="file.type === 'video'" class="w-4 h-4 text-cyan-500" />
      <FileArchive v-else-if="file.type === 'archive'" class="w-4 h-4 text-orange-500" />
      <FileSpreadsheet v-else-if="file.type === 'spreadsheet'" class="w-4 h-4 text-emerald-500" />
      <FileIcon v-else class="w-4 h-4 text-surface-400" />
    </div>
    <div class="flex-1 min-w-0 mr-4">
      <p class="text-sm font-medium text-surface-800 dark:text-surface-200 truncate" :title="file.name">{{ file.name }}</p>
    </div>
    <div class="w-24 text-right shrink-0">
      <span class="text-xs text-surface-500 dark:text-surface-400">{{ file.isDirectory ? '--' : (file.sizeFormatted || formatSize(file.size)) }}</span>
    </div>
    <div class="w-24 text-center shrink-0 hidden lg:block">
      <span class="text-xs text-surface-500 dark:text-surface-400 capitalize">{{ file.type || 'File' }}</span>
    </div>
    <div class="w-36 text-right shrink-0 hidden md:block">
      <span class="text-xs text-surface-500 dark:text-surface-400">{{ file.modified ? formatDate(file.modified) : '--' }}</span>
    </div>
    <div v-if="isRemote" class="w-32 text-right shrink-0 hidden xl:block">
      <span class="text-xs font-mono text-surface-500 dark:text-surface-400">{{ file.permissions || '--' }}</span>
    </div>
  </div>
</template>

<script>
import { Folder, FileImage, FileText, FileAudio, FileVideo, FileArchive, FileSpreadsheet, FileIcon } from 'lucide-vue-next'
import dayjs from 'dayjs'
export default {
  name: 'FileItem',
  components: { Folder, FileImage, FileText, FileAudio, FileVideo, FileArchive, FileSpreadsheet, FileIcon },
  props: { file: { type: Object, required: true }, isRemote: { type: Boolean, default: false }, selected: { type: Boolean, default: false } },
  emits: ['select', 'dblclick', 'contextmenu', 'dragstart'],
  methods: {
    formatSize(bytes) { if (!bytes) return '--'; const units = ['B', 'KB', 'MB', 'GB']; let i = 0; while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++ } return `${bytes.toFixed(1)} ${units[i]}` },
    formatDate(date) { return dayjs(date).format('MMM D, YYYY HH:mm') }
  }
}
</script>
