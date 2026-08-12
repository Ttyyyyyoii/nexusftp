<template>
  <Teleport to="body">
    <Transition name="panel-slide">
      <div
        v-if="queueStore.batches.length > 0 && isVisible"
        class="fixed top-0 right-0 h-full z-[80] flex flex-col"
        style="width: 340px;"
      >
        <!-- Panneau principal -->
        <div class="h-full flex flex-col bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-white/[0.07] shadow-2xl">

          <!-- Header -->
          <div class="flex items-center justify-between px-4 py-3.5 border-b border-slate-200 dark:border-white/[0.07] shrink-0"
            style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);">
            <div class="flex items-center gap-2.5">
              <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                <Upload class="w-3.5 h-3.5 text-white" />
              </div>
              <div>
                <p class="text-white font-bold text-sm leading-tight">{{ $t('queuePanel.title') }}</p>
                <p class="text-white/70 text-xs">{{ queueStore.batches.length }} {{ queueStore.batches.length > 1 ? $t('queuePanel.lots') : $t('queuePanel.lot') }}</p>
              </div>
            </div>
            <div class="flex items-center gap-1.5">
              <button
                v-if="queueStore.batches.some(b => b.status === 'done' || b.status === 'done_with_errors')"
                @click="queueStore.clearDone()"
                class="px-2.5 py-1 rounded-lg bg-white/15 hover:bg-white/25 text-white text-xs font-medium transition-colors"
                :title="$t('queuePanel.clear')"
              >
                {{ $t('queuePanel.clear') }}
              </button>
              <button @click="collapsed = !collapsed" class="w-7 h-7 rounded-lg bg-white/15 hover:bg-white/25 flex items-center justify-center transition-colors">
                <ChevronRight v-if="collapsed" class="w-4 h-4 text-white" />
                <ChevronLeft v-else class="w-4 h-4 text-white" />
              </button>
              <button @click="isVisible = false" class="w-7 h-7 rounded-lg bg-white/15 hover:bg-white/25 flex items-center justify-center transition-colors text-white" :title="$t('common.close')">
                <X class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Résumé global animé -->
          <div v-if="queueStore.activeBatch" class="px-4 py-2.5 bg-indigo-50 dark:bg-indigo-950/40 border-b border-indigo-100 dark:border-indigo-500/20 shrink-0">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse inline-block"></span>
                {{ $t('queuePanel.uploading') }}
              </span>
              <span class="text-xs text-indigo-600 dark:text-indigo-400">
                {{ activeDoneCount }} / {{ queueStore.activeBatch.files.length }}
              </span>
            </div>
            <div class="w-full h-1.5 bg-indigo-200 dark:bg-indigo-900/60 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                style="background: linear-gradient(90deg, #6366f1, #8b5cf6);"
                :style="{ width: activeBatchProgress + '%' }"
              />
            </div>
          </div>

          <!-- Liste des lots (scrollable) -->
          <div v-show="!collapsed" class="flex-1 overflow-y-auto">
            <div v-for="(batch, batchIdx) in [...queueStore.batches].reverse()" :key="batch.id" class="border-b border-slate-100 dark:border-white/[0.05] last:border-b-0">

              <!-- En-tête du lot -->
              <div class="flex items-center justify-between px-4 py-2.5"
                :class="batchHeaderClass(batch.status)">
                <div class="flex items-center gap-2 min-w-0">
                  <!-- Icône de statut du lot -->
                  <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0" :class="batchIconBg(batch.status)">
                    <Loader2 v-if="batch.status === 'processing'" class="w-3.5 h-3.5 animate-spin text-indigo-600 dark:text-indigo-400" />
                    <Clock v-else-if="batch.status === 'pending'" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                    <CheckCircle2 v-else-if="batch.status === 'done'" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                    <AlertCircle v-else class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" />
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">
                      {{ $t('queuePanel.lot') }} {{ queueStore.batches.length - batchIdx }}
                      <span class="font-normal text-slate-500 dark:text-slate-400">— {{ batch.files.length }} {{ batch.files.length > 1 ? $t('queuePanel.files') : $t('queuePanel.file') }}</span>
                    </p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate">{{ batch.remotePath }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                  <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold" :class="batchBadgeClass(batch.status)">
                    {{ batchStatusLabel(batch.status) }}
                  </span>
                  <!-- Bouton annuler (seulement pour les lots en attente) -->
                  <button
                    v-if="batch.status === 'pending'"
                    @click="queueStore.cancelBatch(batch.id)"
                    class="w-5 h-5 rounded flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors"
                    :title="$t('queuePanel.cancelBatch')"
                  >
                    <X class="w-3 h-3" />
                  </button>
                  <!-- Bouton expand/collapse du lot -->
                  <button @click="toggleBatch(batch.id)" class="w-5 h-5 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                    <ChevronDown v-if="!expandedBatches[batch.id]" class="w-3 h-3" />
                    <ChevronUp v-else class="w-3 h-3" />
                  </button>
                </div>
              </div>

              <!-- Fichiers du lot (expandable) -->
              <div v-if="expandedBatches[batch.id]" class="px-3 pb-2 space-y-1">
                <div
                  v-for="fileEntry in batch.files"
                  :key="fileEntry.id"
                  class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg"
                  :class="fileRowClass(fileEntry.status)"
                >
                  <!-- Icône -->
                  <div class="shrink-0">
                    <Loader2 v-if="fileEntry.status === 'uploading'" class="w-3 h-3 animate-spin text-indigo-500" />
                    <CheckCircle2 v-else-if="fileEntry.status === 'done'" class="w-3 h-3 text-emerald-500" />
                    <XCircle v-else-if="fileEntry.status === 'error'" class="w-3 h-3 text-rose-500" />
                    <Clock v-else class="w-3 h-3 text-slate-400" />
                  </div>
                  <!-- Nom du fichier -->
                  <span class="flex-1 text-xs truncate" :class="fileNameClass(fileEntry.status)" :title="fileEntry.name">
                    {{ fileEntry.name }}
                  </span>
                  <!-- Taille -->
                  <span class="text-[10px] text-slate-400 shrink-0">{{ formatSize(fileEntry.size) }}</span>
                  <!-- Erreur -->
                  <span v-if="fileEntry.error" class="text-[10px] text-rose-400 truncate max-w-[80px]" :title="fileEntry.error">{{ fileEntry.error }}</span>
                </div>
              </div>

            </div>
          </div>

          <!-- Footer avec info "en attente" -->
          <div v-show="!collapsed && queueStore.pendingBatches.length > 0" class="px-4 py-2.5 bg-amber-50 dark:bg-amber-950/30 border-t border-amber-100 dark:border-amber-500/20 shrink-0">
            <p class="text-xs text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
              <Clock class="w-3 h-3" />
              <span>{{ queueStore.pendingBatches.length }} lot{{ queueStore.pendingBatches.length > 1 ? 's' : '' }} en attente</span>
            </p>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script>
import { useUploadQueueStore } from '@/stores/uploadQueue'
import { Upload, ChevronLeft, ChevronRight, ChevronDown, ChevronUp, Loader2, Clock, CheckCircle2, AlertCircle, X, XCircle } from 'lucide-vue-next'

export default {
  name: 'UploadQueuePanel',
  components: { Upload, ChevronLeft, ChevronRight, ChevronDown, ChevronUp, Loader2, Clock, CheckCircle2, AlertCircle, X, XCircle },

  data() {
    return {
      queueStore: useUploadQueueStore(),
      collapsed: false,
      expandedBatches: {},
      isVisible: true
    }
  },

  watch: {
    'queueStore.batches.length': {
      handler(newLen, oldLen) {
        if (newLen > (oldLen || 0)) {
          this.isVisible = true; // Réafficher le panneau si un nouveau lot est ajouté
        }
      }
    }
  },

  computed: {
    activeBatchProgress() {
      const batch = this.queueStore.activeBatch
      if (!batch || batch.files.length === 0) return 0
      const done = batch.files.filter(f => f.status === 'done' || f.status === 'error').length
      return Math.round((done / batch.files.length) * 100)
    },
    activeDoneCount() {
      const batch = this.queueStore.activeBatch
      if (!batch) return 0
      return batch.files.filter(f => f.status === 'done' || f.status === 'error').length
    }
  },

  methods: {
    toggleBatch(batchId) {
      this.expandedBatches = {
        ...this.expandedBatches,
        [batchId]: !this.expandedBatches[batchId]
      }
    },

    formatSize(bytes) {
      if (!bytes) return '--'
      const units = ['B', 'KB', 'MB', 'GB']
      let i = 0
      while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++ }
      return `${bytes.toFixed(1)} ${units[i]}`
    },

    batchStatusLabel(status) {
      const labels = { 
        processing: this.$t('queuePanel.processing'), 
        pending: this.$t('queuePanel.pending'), 
        done: this.$t('queuePanel.done'), 
        done_with_errors: this.$t('queuePanel.errors') 
      }
      return labels[status] || status
    },

    batchBadgeClass(status) {
      const classes = {
        processing: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300',
        pending:    'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
        done:       'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
        done_with_errors: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300'
      }
      return classes[status] || classes.pending
    },

    batchHeaderClass(status) {
      const classes = {
        processing: 'bg-indigo-50/60 dark:bg-indigo-950/20',
        pending:    'bg-amber-50/60 dark:bg-amber-950/20',
        done:       'bg-emerald-50/30 dark:bg-transparent',
        done_with_errors: 'bg-rose-50/30 dark:bg-transparent'
      }
      return classes[status] || ''
    },

    batchIconBg(status) {
      const classes = {
        processing: 'bg-indigo-100 dark:bg-indigo-900/40',
        pending:    'bg-amber-100 dark:bg-amber-900/40',
        done:       'bg-emerald-100 dark:bg-emerald-900/40',
        done_with_errors: 'bg-rose-100 dark:bg-rose-900/40'
      }
      return classes[status] || classes.pending
    },

    fileRowClass(status) {
      const classes = {
        uploading: 'bg-indigo-50 dark:bg-indigo-950/30',
        done:      'bg-emerald-50/50 dark:bg-transparent',
        error:     'bg-rose-50/50 dark:bg-rose-950/20',
        pending:   ''
      }
      return classes[status] || ''
    },

    fileNameClass(status) {
      const classes = {
        uploading: 'text-indigo-700 dark:text-indigo-300 font-medium',
        done:      'text-slate-500 dark:text-slate-400 line-through',
        error:     'text-rose-600 dark:text-rose-400',
        pending:   'text-slate-700 dark:text-slate-300'
      }
      return classes[status] || 'text-slate-600 dark:text-slate-300'
    }
  }
}
</script>

<style scoped>
.panel-slide-enter-active,
.panel-slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
}
.panel-slide-enter-from,
.panel-slide-leave-to {
  transform: translateX(100%);
  opacity: 0;
}
</style>
