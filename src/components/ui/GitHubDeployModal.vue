<template>
  <BaseModal :visible="visible" title="🚀 GitHub CI/CD Auto-Deploy" @close="$emit('close')" maxWidth="max-w-2xl">
    <div class="space-y-5">

      <!-- STEP 1 : Not connected -->
      <div v-if="step === 'connect'" class="space-y-5">
        <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-4 border border-surface-200 dark:border-surface-700 text-sm text-surface-600 dark:text-surface-400 leading-relaxed">
          Connectez votre compte GitHub pour voir tous vos dépôts et activer le déploiement automatique vers votre serveur FTP à chaque <code class="bg-surface-200 dark:bg-surface-600 px-1 rounded text-xs">git push</code>.
        </div>
        <button @click="connectGitHub" class="w-full flex items-center justify-center gap-3 py-3 px-6 bg-[#24292e] hover:bg-[#1a1f24] text-white font-semibold rounded-xl transition-all hover:scale-[1.02] shadow-lg">
          <Github class="w-5 h-5" />
          Se connecter avec GitHub
        </button>
        <div v-if="error" class="flex items-start gap-2 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-3">
          <AlertCircle class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
          <p class="text-sm text-rose-700 dark:text-rose-300">{{ error }}</p>
        </div>
      </div>

      <!-- STEP 2 : Connected, select repo -->
      <div v-if="step === 'select'" class="space-y-4">
        <!-- User badge -->
        <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-3">
          <CheckCircle class="w-5 h-5 text-emerald-500 shrink-0" />
          <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Connecté en tant que <strong>{{ username }}</strong></span>
          <button @click="disconnect" class="ml-auto text-xs text-emerald-600 hover:underline">Déconnecter</button>
        </div>

        <!-- Loading repos -->
        <div v-if="loadingRepos" class="flex items-center justify-center gap-3 py-6 text-surface-500">
          <Loader2 class="w-5 h-5 animate-spin" />
          <span class="text-sm">Chargement de vos dépôts...</span>
        </div>

        <div v-else class="space-y-4">
          <!-- Search repos -->
          <div class="relative">
            <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-surface-400" />
            <input v-model="repoSearch" type="text" placeholder="Rechercher un dépôt..." class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-900 border border-surface-300 dark:border-surface-600 focus:ring-2 focus:ring-primary-500 dark:text-white text-sm" />
          </div>

          <!-- Repo list -->
          <div class="max-h-52 overflow-y-auto rounded-xl border border-surface-200 dark:border-surface-700 divide-y divide-surface-100 dark:divide-surface-800">
            <div v-if="filteredRepos.length === 0" class="p-4 text-center text-sm text-surface-400">Aucun dépôt trouvé</div>
            <button
              v-for="repo in filteredRepos" :key="repo.fullName"
              @click="selectRepo(repo)"
              class="w-full flex items-center gap-3 px-4 py-3 hover:bg-surface-50 dark:hover:bg-surface-800 transition-colors text-left"
              :class="selectedRepo?.fullName === repo.fullName ? 'bg-primary-50 dark:bg-primary-900/20' : ''"
            >
              <BookOpen class="w-4 h-4 shrink-0" :class="selectedRepo?.fullName === repo.fullName ? 'text-primary-500' : 'text-surface-400'" />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate" :class="selectedRepo?.fullName === repo.fullName ? 'text-primary-700 dark:text-primary-300' : 'text-surface-800 dark:text-surface-200'">{{ repo.fullName }}</p>
                <p v-if="repo.description" class="text-xs text-surface-400 truncate">{{ repo.description }}</p>
              </div>
              <span class="text-xs px-2 py-0.5 rounded-full shrink-0" :class="repo.private ? 'bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' : 'bg-emerald-100 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400'">
                {{ repo.private ? '🔒 Privé' : '🌐 Public' }}
              </span>
            </button>
          </div>

          <!-- Branch + Remote path (only show when repo selected) -->
          <template v-if="selectedRepo">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-surface-600 dark:text-surface-400 mb-1.5">Branche</label>
                <input v-model="branch" type="text" class="w-full px-3 py-2 rounded-xl text-sm bg-surface-0 dark:bg-surface-900 border border-surface-300 dark:border-surface-600 focus:ring-2 focus:ring-primary-500 dark:text-white" />
              </div>
              <div>
                <label class="block text-xs font-medium text-surface-600 dark:text-surface-400 mb-1.5">Dossier FTP de destination</label>
                <input v-model="remotePath" type="text" class="w-full px-3 py-2 rounded-xl text-sm bg-surface-0 dark:bg-surface-900 border border-surface-300 dark:border-surface-600 focus:ring-2 focus:ring-primary-500 dark:text-white" />
              </div>
            </div>
          </template>

          <div v-if="error" class="flex items-start gap-2 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-3">
            <AlertCircle class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
            <p class="text-sm text-rose-700 dark:text-rose-300">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- STEP 3 : Success -->
      <div v-if="step === 'success'" class="space-y-4">
        <div class="flex flex-col items-center text-center gap-4 py-4">
          <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
            <CheckCircle class="w-9 h-9 text-emerald-500" />
          </div>
          <div>
            <h3 class="font-bold text-surface-900 dark:text-white text-lg">Déploiement activé !</h3>
            <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">Chaque <code class="bg-surface-100 dark:bg-surface-700 px-1 rounded">git push</code> sur <strong>{{ branch }}</strong> déploiera automatiquement vers <strong class="text-primary-600">{{ remotePath }}</strong></p>
          </div>
        </div>
        <div class="bg-surface-50 dark:bg-surface-800 rounded-xl p-4 border border-surface-200 dark:border-surface-700 space-y-2">
          <div class="flex justify-between text-xs text-surface-500"><span>Dépôt</span><span class="font-medium text-surface-800 dark:text-surface-200">{{ deployResult.repo }}</span></div>
          <div class="flex justify-between text-xs text-surface-500"><span>Branche</span><span class="font-medium text-surface-800 dark:text-surface-200">{{ deployResult.branch }}</span></div>
          <div class="flex justify-between text-xs text-surface-500"><span>Destination FTP</span><span class="font-medium text-surface-800 dark:text-surface-200">{{ remotePath }}</span></div>
          <div class="flex justify-between text-xs text-surface-500"><span>Webhook ID</span><span class="font-mono text-surface-500">#{{ deployResult.hookId }}</span></div>
        </div>
      </div>

    </div>

    <template #footer>
      <button @click="$emit('close')" class="btn-secondary text-sm">{{ step === 'success' ? 'Fermer' : 'Annuler' }}</button>
      <button
        v-if="step === 'select' && selectedRepo && !loadingRepos"
        @click="activateDeploy"
        :disabled="deploying"
        class="btn-primary text-sm flex items-center gap-2"
      >
        <Loader2 v-if="deploying" class="w-4 h-4 animate-spin" />
        <Zap v-else class="w-4 h-4" />
        {{ deploying ? 'Activation...' : 'Activer le déploiement' }}
      </button>
      <button v-if="step === 'success'" @click="reset" class="btn-secondary text-sm flex items-center gap-2">
        <RefreshCw class="w-4 h-4" />
        Reconfigurer
      </button>
    </template>
  </BaseModal>
</template>

<script>
import BaseModal from '@/components/ui/BaseModal.vue'
import { Github, Loader2, CheckCircle, AlertCircle, BookOpen, Search, Zap, RefreshCw } from 'lucide-vue-next'

const API_BASE    = import.meta.env.VITE_API_URL || '/api'
const CLIENT_ID   = 'Ov23liyWoYP9G3BMK5q8'

export default {
  name: 'GitHubDeployModal',
  components: { BaseModal, Github, Loader2, CheckCircle, AlertCircle, BookOpen, Search, Zap, RefreshCw },
  props: {
    visible:     { type: Boolean, default: false },
    currentPath: { type: String,  default: '/'   },
    sessionId:   { type: String,  default: ''    }
  },
  emits: ['close'],
  data() {
    return {
      step: 'connect',     // connect | select | success
      oauthToken: '',
      username: '',
      repos: [],
      repoSearch: '',
      selectedRepo: null,
      branch: 'main',
      remotePath: '',
      loadingRepos: false,
      deploying: false,
      error: '',
      deployResult: {}
    }
  },
  computed: {
    filteredRepos() {
      if (!this.repoSearch.trim()) return this.repos
      const q = this.repoSearch.toLowerCase()
      return this.repos.filter(r => r.fullName.toLowerCase().includes(q) || (r.description || '').toLowerCase().includes(q))
    }
  },
  watch: {
    visible(val) {
      if (val) this.remotePath = this.currentPath
    }
  },
  methods: {
    connectGitHub() {
      this.error = ''
      const callbackUrl = `${window.location.origin}/api/github_oauth_callback.php`
      const scope = 'repo,admin:repo_hook'
      const oauthUrl = `https://github.com/login/oauth/authorize?client_id=${CLIENT_ID}&redirect_uri=${encodeURIComponent(callbackUrl)}&scope=${encodeURIComponent(scope)}`
      
      const popup = window.open(oauthUrl, 'github-oauth', 'width=600,height=700,left=200,top=100')
      
      const messageHandler = async (event) => {
        if (event.data?.type === 'GITHUB_OAUTH_SUCCESS') {
          window.removeEventListener('message', messageHandler)
          this.oauthToken = event.data.token
          await this.loadRepos()
        } else if (event.data?.type === 'GITHUB_OAUTH_ERROR') {
          window.removeEventListener('message', messageHandler)
          this.error = 'Connexion GitHub annulée ou refusée.'
        }
      }
      window.addEventListener('message', messageHandler)
      
      // Nettoyer si la popup est fermée manuellement
      const timer = setInterval(() => {
        if (popup.closed) {
          clearInterval(timer)
          window.removeEventListener('message', messageHandler)
        }
      }, 500)
    },
    async loadRepos() {
      this.loadingRepos = true
      this.step = 'select'
      try {
        const res  = await fetch(`${API_BASE}/github_repos.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ token: this.oauthToken })
        })
        const data = await res.json()
        if (data.success) {
          this.repos    = data.repos
          this.username = data.username
        } else {
          this.error = data.message || 'Impossible de charger vos dépôts.'
        }
      } catch {
        this.error = 'Erreur réseau lors du chargement des dépôts.'
      } finally {
        this.loadingRepos = false
      }
    },
    selectRepo(repo) {
      this.selectedRepo = repo
      this.branch = repo.defaultBranch || 'main'
      this.error = ''
    },
    async activateDeploy() {
      if (!this.selectedRepo) return
      this.deploying = true
      this.error = ''
      try {
        const res  = await fetch(`${API_BASE}/github_link.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({
            sessionId:   this.sessionId,
            remotePath:  this.remotePath || this.currentPath,
            githubRepo:  this.selectedRepo.fullName,
            githubBranch: this.branch,
            githubToken: this.oauthToken
          })
        })
        const data = await res.json()
        if (data.success) {
          this.deployResult = data
          this.step = 'success'
        } else {
          this.error = data.message || 'Erreur lors de l\'activation du déploiement.'
        }
      } catch {
        this.error = 'Erreur réseau.'
      } finally {
        this.deploying = false
      }
    },
    disconnect() {
      this.oauthToken  = ''
      this.repos       = []
      this.username    = ''
      this.selectedRepo = null
      this.step        = 'connect'
    },
    reset() {
      this.step         = 'connect'
      this.selectedRepo = null
      this.error        = ''
      this.deployResult = {}
    }
  }
}
</script>
