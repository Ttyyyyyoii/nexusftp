<template>
  <BaseModal :visible="visible" title="🚀 Déploiement GitHub" @close="$emit('close')" maxWidth="max-w-2xl">
    <div class="space-y-6">
      
      <!-- State: Not configured -->
      <div v-if="state === 'idle'" class="space-y-5">
        <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-4 border border-surface-200 dark:border-surface-700">
          <p class="text-sm text-surface-600 dark:text-surface-400 leading-relaxed">
            🔗 Liez votre dépôt GitHub à ce dossier FTP. À chaque <code class="bg-surface-200 dark:bg-surface-700 px-1 rounded">git push</code>, vos fichiers seront automatiquement déployés sur votre serveur FTP.
          </p>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1.5">Dépôt GitHub <span class="text-rose-500">*</span></label>
            <input v-model="repo" type="text" placeholder="ex: votre-user/votre-repo" class="w-full px-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-900 border border-surface-300 dark:border-surface-600 focus:ring-2 focus:ring-primary-500 dark:text-white text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1.5">Branche</label>
            <input v-model="branch" type="text" placeholder="main" class="w-full px-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-900 border border-surface-300 dark:border-surface-600 focus:ring-2 focus:ring-primary-500 dark:text-white text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1.5">
              Token PAT <span class="text-surface-400 text-xs font-normal">(optionnel — requis pour dépôt privé)</span>
            </label>
            <input v-model="githubToken" type="password" placeholder="ghp_xxxxxxxxxxxxxxxxxxxx" class="w-full px-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-900 border border-surface-300 dark:border-surface-600 focus:ring-2 focus:ring-primary-500 dark:text-white text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1.5">Dossier FTP de destination</label>
            <input v-model="remotePath" type="text" :placeholder="currentPath" class="w-full px-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-900 border border-surface-300 dark:border-surface-600 focus:ring-2 focus:ring-primary-500 dark:text-white text-sm" />
          </div>
        </div>
      </div>

      <!-- State: Success — show webhook URL -->
      <div v-if="state === 'success'" class="space-y-4">
        <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4">
          <CheckCircle class="w-8 h-8 text-emerald-500 shrink-0" />
          <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Lien généré avec succès ! Copiez l'URL ci-dessous dans les paramètres GitHub Webhooks.</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-surface-700 dark:text-surface-300 mb-2">URL Webhook à copier dans GitHub :</label>
          <div class="flex gap-2">
            <input :value="webhookUrl" readonly class="flex-1 px-4 py-2.5 bg-surface-100 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl text-sm text-surface-700 dark:text-surface-300 font-mono select-all truncate" />
            <button @click="copyWebhook" class="px-4 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl transition-colors flex items-center gap-2 shrink-0">
              <Copy v-if="!copied" class="w-4 h-4" />
              <CheckCircle v-else class="w-4 h-4" />
              <span class="text-sm">{{ copied ? 'Copié !' : 'Copier' }}</span>
            </button>
          </div>
        </div>
        
        <div class="bg-primary-50 dark:bg-primary-900/10 border border-primary-100 dark:border-primary-900/30 rounded-xl p-4 space-y-2">
          <p class="text-xs font-bold text-primary-700 dark:text-primary-300 uppercase tracking-wider">📋 Comment activer dans GitHub :</p>
          <ol class="list-decimal list-inside space-y-1 text-xs text-primary-600 dark:text-primary-400">
            <li>Allez dans <strong>Settings</strong> de votre dépôt GitHub</li>
            <li>Cliquez sur <strong>Webhooks</strong> → <strong>Add webhook</strong></li>
            <li>Collez l'URL dans le champ <strong>Payload URL</strong></li>
            <li>Mettez <strong>Content type</strong> sur <code class="bg-primary-100 dark:bg-primary-900/20 px-1 rounded">application/json</code></li>
            <li>Cliquez sur <strong>Add webhook</strong></li>
          </ol>
        </div>
      </div>

      <!-- Error state -->
      <div v-if="error" class="flex items-start gap-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-4">
        <AlertCircle class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" />
        <p class="text-sm text-rose-700 dark:text-rose-300">{{ error }}</p>
      </div>
    </div>

    <template #footer>
      <button @click="$emit('close')" class="btn-secondary text-sm">Fermer</button>
      <button v-if="state === 'idle'" @click="generateLink" :disabled="loading || !repo.trim()" class="btn-primary text-sm flex items-center gap-2">
        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
        <Github v-else class="w-4 h-4" />
        {{ loading ? 'Génération...' : 'Générer le Webhook' }}
      </button>
      <button v-if="state === 'success'" @click="reset" class="btn-secondary text-sm flex items-center gap-2">
        <RefreshCw class="w-4 h-4" />
        Reconfigurer
      </button>
    </template>
  </BaseModal>
</template>

<script>
import BaseModal from '@/components/ui/BaseModal.vue'
import { Github, Loader2, CheckCircle, AlertCircle, Copy, RefreshCw } from 'lucide-vue-next'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

export default {
  name: 'GitHubDeployModal',
  components: { BaseModal, Github, Loader2, CheckCircle, AlertCircle, Copy, RefreshCw },
  props: {
    visible: { type: Boolean, default: false },
    currentPath: { type: String, default: '/' },
    sessionId: { type: String, default: '' }
  },
  emits: ['close'],
  data() {
    return {
      state: 'idle', // idle | success
      loading: false,
      error: '',
      repo: '',
      branch: 'main',
      githubToken: '',
      remotePath: '',
      webhookUrl: '',
      copied: false
    }
  },
  watch: {
    visible(val) {
      if (val) this.remotePath = this.currentPath
    }
  },
  methods: {
    reset() {
      this.state = 'idle'
      this.error = ''
      this.webhookUrl = ''
    },
    async generateLink() {
      if (!this.repo.trim()) return
      this.loading = true
      this.error = ''
      try {
        const res = await fetch(`${API_BASE}/github_link.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({
            sessionId: this.sessionId,
            remotePath: this.remotePath || this.currentPath,
            githubRepo: this.repo.trim(),
            githubBranch: this.branch.trim() || 'main',
            githubToken: this.githubToken.trim()
          })
        })
        const data = await res.json()
        if (data.success) {
          this.webhookUrl = data.webhookUrl
          this.state = 'success'
        } else {
          this.error = data.message || 'Erreur lors de la génération du webhook.'
        }
      } catch (err) {
        this.error = 'Erreur réseau. Vérifiez votre connexion.'
      } finally {
        this.loading = false
      }
    },
    async copyWebhook() {
      await navigator.clipboard.writeText(this.webhookUrl)
      this.copied = true
      setTimeout(() => { this.copied = false }, 2000)
    }
  }
}
</script>
