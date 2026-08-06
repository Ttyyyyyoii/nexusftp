<template>
  <BaseModal :visible="visible" title="🚀 GitHub CI/CD Auto-Deploy" @close="$emit('close')" maxWidth="max-w-2xl">
    
    <!-- Tabs -->
    <div class="flex gap-1 mb-5 p-1 bg-surface-100 dark:bg-surface-800 rounded-xl">
      <button @click="activeTab = 'new'" class="flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-all" :class="activeTab === 'new' ? 'bg-white dark:bg-surface-700 text-surface-900 dark:text-white shadow' : 'text-surface-500 hover:text-surface-700'">
        + Nouveau déploiement
      </button>
      <button @click="activeTab = 'manage'; loadDeployments()" class="flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2" :class="activeTab === 'manage' ? 'bg-white dark:bg-surface-700 text-surface-900 dark:text-white shadow' : 'text-surface-500 hover:text-surface-700'">
        Gérer les déploiements
        <span v-if="deployments.length > 0" class="w-4 h-4 rounded-full bg-primary-500 text-white text-[10px] flex items-center justify-center">{{ deployments.length }}</span>
      </button>
    </div>

    <div class="space-y-5">

      <!-- ═══════════════ TAB : NEW DEPLOYMENT ═══════════════ -->
      <template v-if="activeTab === 'new'">

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
          <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-3">
            <CheckCircle class="w-5 h-5 text-emerald-500 shrink-0" />
            <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Connecté en tant que <strong>{{ username }}</strong></span>
            <button @click="disconnect" class="ml-auto text-xs text-emerald-600 hover:underline">Déconnecter</button>
          </div>

          <div v-if="loadingRepos" class="flex items-center justify-center gap-3 py-6 text-surface-500">
            <Loader2 class="w-5 h-5 animate-spin" />
            <span class="text-sm">Chargement de vos dépôts...</span>
          </div>

          <div v-else class="space-y-4">
            <div class="relative">
              <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-surface-400" />
              <input v-model="repoSearch" type="text" placeholder="Rechercher un dépôt..." class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-900 border border-surface-300 dark:border-surface-600 focus:ring-2 focus:ring-primary-500 dark:text-white text-sm" />
            </div>

            <div class="max-h-52 overflow-y-auto rounded-xl border border-surface-200 dark:border-surface-700 divide-y divide-surface-100 dark:divide-surface-800">
              <div v-if="filteredRepos.length === 0" class="p-4 text-center text-sm text-surface-400">Aucun dépôt trouvé</div>
              <button v-for="repo in filteredRepos" :key="repo.fullName" @click="selectRepo(repo)"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-surface-50 dark:hover:bg-surface-800 transition-colors text-left"
                :class="selectedRepo?.fullName === repo.fullName ? 'bg-primary-50 dark:bg-primary-900/20' : ''">
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
          <button @click="activeTab = 'manage'; loadDeployments()" class="w-full text-center text-sm text-primary-600 hover:underline">Voir tous mes déploiements →</button>
        </div>

      </template>

      <!-- ═══════════════ TAB : MANAGE DEPLOYMENTS ═══════════════ -->
      <template v-if="activeTab === 'manage'">
        <div v-if="loadingDeployments" class="flex items-center justify-center gap-3 py-8 text-surface-500">
          <Loader2 class="w-5 h-5 animate-spin" />
          <span class="text-sm">Chargement...</span>
        </div>

        <div v-else-if="deployments.length === 0" class="flex flex-col items-center justify-center gap-3 py-10 text-center">
          <div class="text-4xl">🔗</div>
          <p class="text-sm text-surface-500 dark:text-surface-400">Aucun déploiement GitHub actif pour ce serveur FTP.</p>
          <button @click="activeTab = 'new'" class="text-sm text-primary-600 hover:underline">Créer un déploiement</button>
        </div>

        <div v-else class="space-y-3">
          <p class="text-xs text-surface-400 font-medium uppercase tracking-wider">{{ deployments.length }} déploiement(s) actif(s)</p>
          <div v-for="dep in deployments" :key="dep.token" class="bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl p-4 space-y-2">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-2 min-w-0">
                <Github class="w-4 h-4 text-surface-500 shrink-0" />
                <span class="font-semibold text-surface-900 dark:text-white text-sm truncate">{{ dep.githubRepo }}</span>
                <span class="text-xs bg-surface-200 dark:bg-surface-700 text-surface-600 dark:text-surface-400 px-2 py-0.5 rounded-full shrink-0">{{ dep.githubBranch }}</span>
              </div>
              <button @click="confirmDelete(dep)" class="shrink-0 flex items-center gap-1.5 text-xs text-rose-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 px-2 py-1 rounded-lg transition-colors">
                <Trash2 class="w-3.5 h-3.5" />
                Supprimer
              </button>
            </div>
            <div class="flex items-center gap-2 text-xs text-surface-500">
              <span>→</span>
              <span class="font-mono bg-surface-100 dark:bg-surface-700 px-2 py-0.5 rounded text-surface-700 dark:text-surface-300">{{ dep.remotePath }}</span>
              <span v-if="dep.createdAt" class="ml-auto">{{ formatDate(dep.createdAt) }}</span>
            </div>
          </div>
        </div>

        <!-- Confirm delete dialog -->
        <div v-if="deletingDep" class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-4 space-y-3">
          <p class="text-sm font-medium text-rose-700 dark:text-rose-300">⚠️ Supprimer le déploiement <strong>{{ deletingDep.githubRepo }}</strong> → <strong>{{ deletingDep.remotePath }}</strong> ?</p>
          <p class="text-xs text-rose-600 dark:text-rose-400">Le webhook GitHub sera également supprimé (si vous êtes connecté à GitHub).</p>
          <div class="flex gap-2">
            <button @click="deletingDep = null" class="btn-secondary text-xs flex-1">Annuler</button>
            <button @click="deleteDeployment" :disabled="deleting" class="flex-1 px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white text-xs font-medium rounded-xl transition-colors flex items-center justify-center gap-2">
              <Loader2 v-if="deleting" class="w-3.5 h-3.5 animate-spin" />
              <Trash2 v-else class="w-3.5 h-3.5" />
              {{ deleting ? 'Suppression...' : 'Confirmer la suppression' }}
            </button>
          </div>
        </div>

        <div v-if="manageError" class="flex items-start gap-2 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-3">
          <AlertCircle class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
          <p class="text-sm text-rose-700 dark:text-rose-300">{{ manageError }}</p>
        </div>
      </template>

    </div>

    <template #footer>
      <button @click="$emit('close')" class="btn-secondary text-sm">{{ step === 'success' ? 'Fermer' : 'Annuler' }}</button>
      <button v-if="activeTab === 'new' && step === 'select' && selectedRepo && !loadingRepos" @click="activateDeploy" :disabled="deploying" class="btn-primary text-sm flex items-center gap-2">
        <Loader2 v-if="deploying" class="w-4 h-4 animate-spin" />
        <Zap v-else class="w-4 h-4" />
        {{ deploying ? 'Activation...' : 'Activer le déploiement' }}
      </button>
      <button v-if="activeTab === 'new' && step === 'success'" @click="reset" class="btn-secondary text-sm flex items-center gap-2">
        <RefreshCw class="w-4 h-4" />
        Nouveau déploiement
      </button>
    </template>
  </BaseModal>
</template>

<script>
import BaseModal from '@/components/ui/BaseModal.vue'
import { Github, Loader2, CheckCircle, AlertCircle, BookOpen, Search, Zap, RefreshCw, Trash2 } from 'lucide-vue-next'

const API_BASE  = import.meta.env.VITE_API_URL || '/api'
const CLIENT_ID = 'Ov23liyWoYP9G3BMK5q8'

export default {
  name: 'GitHubDeployModal',
  components: { BaseModal, Github, Loader2, CheckCircle, AlertCircle, BookOpen, Search, Zap, RefreshCw, Trash2 },
  props: {
    visible:     { type: Boolean, default: false },
    currentPath: { type: String,  default: '/'   },
    sessionId:   { type: String,  default: ''    }
  },
  emits: ['close'],
  data() {
    return {
      activeTab: 'new',
      // New deployment flow
      step: localStorage.getItem('nexus_github_token') ? 'select' : 'connect',
      oauthToken: localStorage.getItem('nexus_github_token') || '',
      username: localStorage.getItem('nexus_github_username') || '',
      repos: [],
      repoSearch: '',
      selectedRepo: null,
      branch: 'main',
      remotePath: '',
      loadingRepos: false,
      deploying: false,
      error: '',
      deployResult: {},
      // Manage tab
      deployments: [],
      loadingDeployments: false,
      deletingDep: null,
      deleting: false,
      manageError: ''
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
      if (val) {
        this.remotePath = this.currentPath
        this.loadDeployments()
        if (this.oauthToken && this.step === 'select' && this.repos.length === 0) {
          this.loadRepos()
        }
      }
    }
  },
  methods: {
    formatDate(iso) {
      if (!iso) return ''
      const d = new Date(iso)
      return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    },
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
          localStorage.setItem('nexus_github_token', this.oauthToken)
          await this.loadRepos()
        } else if (event.data?.type === 'GITHUB_OAUTH_ERROR') {
          window.removeEventListener('message', messageHandler)
          this.error = 'Connexion GitHub annulée ou refusée.'
        }
      }
      window.addEventListener('message', messageHandler)
      const timer = setInterval(() => {
        if (popup.closed) { clearInterval(timer); window.removeEventListener('message', messageHandler) }
      }, 500)
    },
    async loadRepos() {
      this.loadingRepos = true
      this.step = 'select'
      try {
        const res  = await fetch(`${API_BASE}/github_repos.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ token: this.oauthToken }) })
        const data = await res.json()
        if (data.success) { 
          this.repos = data.repos; 
          this.username = data.username;
          localStorage.setItem('nexus_github_username', this.username)
        }
        else { 
          this.error = data.message || 'Impossible de charger vos dépôts.' 
          if (data.message && data.message.toLowerCase().includes('token')) {
            this.disconnect() // Invalid token
          }
        }
      } catch { this.error = 'Erreur réseau.' }
      finally { this.loadingRepos = false }
    },
    selectRepo(repo) { this.selectedRepo = repo; this.branch = repo.defaultBranch || 'main'; this.error = '' },
    async activateDeploy() {
      if (!this.selectedRepo) return
      this.deploying = true; this.error = ''
      try {
        const res  = await fetch(`${API_BASE}/github_link.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify({ sessionId: this.sessionId, remotePath: this.remotePath || this.currentPath, githubRepo: this.selectedRepo.fullName, githubBranch: this.branch, githubToken: this.oauthToken }) })
        const data = await res.json()
        if (data.success) { this.deployResult = data; this.step = 'success'; this.loadDeployments() }
        else { this.error = data.message || 'Erreur lors de l\'activation.' }
      } catch { this.error = 'Erreur réseau.' }
      finally { this.deploying = false }
    },
    async loadDeployments() {
      this.loadingDeployments = true; this.manageError = ''
      try {
        const res  = await fetch(`${API_BASE}/github_manage.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify({ action: 'list', sessionId: this.sessionId }) })
        const data = await res.json()
        if (data.success) this.deployments = data.deployments
        else this.manageError = data.message || 'Erreur de chargement.'
      } catch { this.manageError = 'Erreur réseau.' }
      finally { this.loadingDeployments = false }
    },
    confirmDelete(dep) { this.deletingDep = dep; this.manageError = '' },
    async deleteDeployment() {
      if (!this.deletingDep) return
      this.deleting = true; this.manageError = ''
      try {
        const res  = await fetch(`${API_BASE}/github_manage.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify({ action: 'delete', sessionId: this.sessionId, token: this.deletingDep.token, oauthToken: this.oauthToken }) })
        const data = await res.json()
        if (data.success) {
          this.deployments = this.deployments.filter(d => d.token !== this.deletingDep.token)
          this.deletingDep = null
          if (data.githubError) this.manageError = data.githubError
        } else { this.manageError = data.message || 'Erreur de suppression.' }
      } catch { this.manageError = 'Erreur réseau.' }
      finally { this.deleting = false }
    },
    disconnect() { 
      this.oauthToken = ''; 
      this.repos = []; 
      this.username = ''; 
      this.selectedRepo = null; 
      this.step = 'connect';
      localStorage.removeItem('nexus_github_token');
      localStorage.removeItem('nexus_github_username');
    },
    reset() { this.step = 'connect'; this.selectedRepo = null; this.error = ''; this.deployResult = {} }
  }
}
</script>
