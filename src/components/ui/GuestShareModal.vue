<template>
  <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="$emit('close')">
    <div class="bg-surface-50 dark:bg-surface-900 w-full max-w-lg rounded-2xl shadow-2xl border border-surface-200 dark:border-surface-800 flex flex-col max-h-[90vh]">
      
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-surface-200 dark:border-surface-800">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
            <Users class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-surface-900 dark:text-white">Espace Collaboratif</h3>
            <p class="text-xs text-surface-500">Créer un accès invité restreint</p>
          </div>
        </div>
        <button @click="$emit('close')" class="p-2 text-surface-400 hover:text-surface-600 dark:hover:text-surface-300 hover:bg-surface-100 dark:hover:bg-surface-800 rounded-xl transition-colors">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-5 overflow-y-auto space-y-6">
        
        <!-- Result State -->
        <div v-if="generatedUrl" class="space-y-4">
          <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 text-center">
            <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/40 rounded-full flex items-center justify-center mx-auto mb-3">
              <Check class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <h4 class="text-emerald-800 dark:text-emerald-300 font-bold mb-1">Espace créé avec succès !</h4>
            <p class="text-emerald-600 dark:text-emerald-400 text-sm">Le lien invité est prêt à être partagé.</p>
          </div>
          
          <div class="space-y-2">
            <label class="block text-xs font-medium text-surface-600 dark:text-surface-400">Lien public</label>
            <div class="flex gap-2">
              <input type="text" :value="generatedUrl" readonly class="input-field flex-1 font-mono text-sm" @click="$event.target.select()" />
              <button @click="copyUrl" class="btn-primary shrink-0">
                <Copy class="w-4 h-4" /> Copier
              </button>
            </div>
          </div>
          
          <div class="pt-4 flex justify-center">
            <button @click="reset" class="btn-secondary text-sm">Créer un autre lien</button>
          </div>
        </div>

        <!-- Form State -->
        <form v-else @submit.prevent="createShare" class="space-y-5">
          
          <!-- Dossier à partager -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Dossier à partager</label>
            <div class="relative">
              <Folder class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
              <input type="text" v-model="form.remotePath" required class="input-field pl-10" />
            </div>
            <p class="text-xs text-surface-500">L'invité ne verra que le contenu de ce dossier et ses sous-dossiers.</p>
          </div>

          <!-- Permissions -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Permissions de l'invité</label>
            <div class="grid grid-cols-2 gap-3">
              <label class="relative flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition-colors" :class="form.permission === 'read' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-surface-200 dark:border-surface-700 hover:bg-surface-50 dark:hover:bg-surface-800'">
                <input type="radio" v-model="form.permission" value="read" class="sr-only" />
                <Eye class="w-5 h-5 text-indigo-500" />
                <div>
                  <div class="text-sm font-medium text-surface-900 dark:text-white">Lecture seule</div>
                  <div class="text-xs text-surface-500">Téléchargement uniquement</div>
                </div>
              </label>
              
              <label class="relative flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition-colors" :class="form.permission === 'upload' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-surface-200 dark:border-surface-700 hover:bg-surface-50 dark:hover:bg-surface-800'">
                <input type="radio" v-model="form.permission" value="upload" class="sr-only" />
                <Upload class="w-5 h-5 text-indigo-500" />
                <div>
                  <div class="text-sm font-medium text-surface-900 dark:text-white">Lecture & Ajout</div>
                  <div class="text-xs text-surface-500">Peut uploader des fichiers</div>
                </div>
              </label>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <!-- Expiration -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Expiration</label>
              <select v-model="form.expiration" class="input-field">
                <option value="1">24 heures</option>
                <option value="7">7 jours</option>
                <option value="30">30 jours</option>
                <option value="0">Jamais (Permanent)</option>
              </select>
            </div>
            
            <!-- Mot de passe -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-surface-700 dark:text-surface-300">Mot de passe <span class="text-surface-400 font-normal">(Optionnel)</span></label>
              <div class="relative">
                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                <input type="text" v-model="form.password" placeholder="Protéger par mot de passe" class="input-field pl-10" />
              </div>
            </div>
          </div>

          <div v-if="error" class="flex items-start gap-2 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-3">
            <AlertCircle class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
            <p class="text-sm text-rose-700 dark:text-rose-300">{{ error }}</p>
          </div>

        </form>
      </div>

      <!-- Footer -->
      <div v-if="!generatedUrl" class="p-4 border-t border-surface-200 dark:border-surface-800 bg-surface-100/50 dark:bg-surface-800/50 flex justify-end gap-3 rounded-b-2xl">
        <button type="button" @click="$emit('close')" class="btn-secondary">Annuler</button>
        <button type="button" @click="createShare" :disabled="loading || !form.remotePath" class="btn-primary flex items-center gap-2">
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          <Users v-else class="w-4 h-4" />
          {{ loading ? 'Création...' : 'Créer l\'espace' }}
        </button>
      </div>
      
    </div>
  </div>
</template>

<script>
import { Users, X, Folder, Eye, Upload, Lock, Check, Copy, AlertCircle, Loader2 } from 'lucide-vue-next'
import { useToast } from 'vue-toastification'
import { useConnectionStore } from '@/stores/connection'

export default {
  name: 'GuestShareModal',
  components: { Users, X, Folder, Eye, Upload, Lock, Check, Copy, AlertCircle, Loader2 },
  props: {
    visible: { type: Boolean, default: false },
    currentPath: { type: String, default: '/' },
    sessionId: { type: String, default: '' }
  },
  emits: ['close'],
  data() {
    return {
      loading: false,
      error: null,
      generatedUrl: null,
      form: {
        remotePath: '/',
        permission: 'read',
        expiration: '7',
        password: ''
      }
    }
  },
  watch: {
    visible(val) {
      if (val) {
        this.reset()
        this.form.remotePath = this.currentPath || '/'
      }
    }
  },
  setup() {
    return { toast: useToast(), connectionStore: useConnectionStore() }
  },
  methods: {
    reset() {
      this.generatedUrl = null
      this.error = null
      this.form = {
        remotePath: this.currentPath || '/',
        permission: 'read',
        expiration: '7',
        password: ''
      }
    },
    async createShare() {
      if (!this.form.remotePath || !this.sessionId) return
      this.loading = true
      this.error = null
      
      try {
        const API_BASE = import.meta.env.VITE_API_URL || '/api'
        const res = await fetch(`${API_BASE}/guest_share.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            sessionId: this.sessionId,
            ...this.form
          })
        })
        const data = await res.json()
        if (data.success) {
          this.generatedUrl = data.guestUrl
          this.toast.success('Espace collaboratif créé avec succès !')
        } else {
          throw new Error(data.message || 'Erreur lors de la création')
        }
      } catch (err) {
        this.error = err.message
      } finally {
        this.loading = false
      }
    },
    async copyUrl() {
      try {
        await navigator.clipboard.writeText(this.generatedUrl)
        this.toast.success('Lien copié dans le presse-papier')
      } catch (e) {
        this.toast.error('Erreur lors de la copie')
      }
    }
  }
}
</script>
