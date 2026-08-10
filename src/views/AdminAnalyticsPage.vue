<template>
  <div class="min-h-screen" style="background: #0a0a0f; color: #e2e8f0; font-family: 'Inter', sans-serif;">
    
    <!-- Header Admin -->
    <header style="background: rgba(15,15,25,0.95); border-bottom: 1px solid rgba(139,92,246,0.2); backdrop-filter: blur(20px);" class="sticky top-0 z-50">
      <div class="max-w-screen-2xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div style="background: linear-gradient(135deg, #8b5cf6, #6366f1); border-radius: 10px;" class="w-9 h-9 flex items-center justify-center shadow-lg">
            <span style="color: white; font-weight: 800; font-size: 16px;">N</span>
          </div>
          <div>
            <span style="font-size: 18px; font-weight: 700; color: #f1f5f9;">NexusFTP </span>
            <span style="font-size: 18px; font-weight: 700; color: #8b5cf6;">Analytics</span>
          </div>
          <span style="background: rgba(139,92,246,0.15); color: #a78bfa; border: 1px solid rgba(139,92,246,0.3); border-radius: 20px; padding: 2px 10px; font-size: 11px; font-weight: 600;">ADMIN</span>
        </div>
        <div class="flex items-center gap-4">
          <span style="color: #64748b; font-size: 13px;">Dernière mise à jour : {{ lastRefresh }}</span>
          <button @click="fetchStats" style="background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.3); color: #a78bfa; border-radius: 8px; padding: 6px 14px; font-size: 13px; cursor: pointer; transition: all 0.2s;" 
            @mouseover="e => e.target.style.background='rgba(139,92,246,0.25)'"
            @mouseleave="e => e.target.style.background='rgba(139,92,246,0.1)'">
            ↻ Actualiser
          </button>
          <button @click="logout" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #f87171; border-radius: 8px; padding: 6px 14px; font-size: 13px; cursor: pointer; transition: all 0.2s;"
            @mouseover="e => e.target.style.background='rgba(239,68,68,0.25)'"
            @mouseleave="e => e.target.style.background='rgba(239,68,68,0.1)'">
            Déconnexion
          </button>
        </div>
      </div>
    </header>

    <!-- Loader -->
    <div v-if="loading" class="flex flex-col justify-center items-center" style="height: calc(100vh - 64px); gap: 16px;">
      <div style="width: 48px; height: 48px; border: 3px solid rgba(139,92,246,0.2); border-top: 3px solid #8b5cf6; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
      <p style="color: #64748b; font-size: 14px;">Chargement des données...</p>
    </div>

    <main v-else-if="stats" class="max-w-screen-2xl mx-auto px-6 py-8 space-y-8">
      
      <!-- KPIs -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="kpi in kpis" :key="kpi.label" 
          style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 20px; transition: all 0.3s;"
          @mouseover="e => e.currentTarget.style.borderColor='rgba(139,92,246,0.4)'"
          @mouseleave="e => e.currentTarget.style.borderColor='rgba(255,255,255,0.07)'">
          <div class="flex items-start justify-between">
            <div>
              <p style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">{{ kpi.label }}</p>
              <h3 style="font-size: 28px; font-weight: 800; color: #f1f5f9; line-height: 1;">{{ kpi.value }}</h3>
            </div>
            <div :style="{ background: kpi.bg }" style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
              {{ kpi.icon }}
            </div>
          </div>
        </div>
      </div>

      <!-- Row 2: Top Pages + Devices + Browsers -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Top Pages -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 24px;" class="lg:col-span-1">
          <h3 style="font-size: 15px; font-weight: 700; color: #f1f5f9; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <span>📄</span> Pages populaires
          </h3>
          <div class="space-y-3">
            <div v-for="(page, i) in stats.top_pages" :key="i" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
              <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                <span style="color: #8b5cf6; font-size: 12px; font-weight: 700; width: 20px;">{{ i+1 }}.</span>
                <span style="color: #94a3b8; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ page.page_url }}</span>
              </div>
              <span style="color: #a78bfa; font-size: 13px; font-weight: 700; margin-left: 8px; flex-shrink: 0;">{{ page.views }}</span>
            </div>
            <div v-if="!stats.top_pages.length" style="color: #475569; font-size: 13px; text-align: center; padding: 20px;">Aucune donnée</div>
          </div>
        </div>

        <!-- Pays -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 24px;">
          <h3 style="font-size: 15px; font-weight: 700; color: #f1f5f9; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <span>🌍</span> Top Pays
          </h3>
          <div class="space-y-3">
            <div v-for="c in stats.countries" :key="c.country" style="display: flex; align-items: center; justify-content: space-between;">
              <span style="color: #94a3b8; font-size: 13px;">{{ c.country }}</span>
              <div style="display: flex; align-items: center; gap: 10px;">
                <div style="height: 6px; border-radius: 3px; background: linear-gradient(90deg, #8b5cf6, #6366f1);" :style="{ width: Math.max(20, (c.count / stats.countries[0].count) * 100) + 'px' }"></div>
                <span style="color: #a78bfa; font-size: 13px; font-weight: 700; width: 24px; text-align: right;">{{ c.count }}</span>
              </div>
            </div>
            <div v-if="!stats.countries.length" style="color: #475569; font-size: 13px; text-align: center; padding: 20px;">Aucune donnée</div>
          </div>
        </div>

        <!-- Navigateurs & Appareils -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 24px;">
          <h3 style="font-size: 15px; font-weight: 700; color: #f1f5f9; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <span>🖥️</span> Navigateurs & Appareils
          </h3>
          <p style="font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; margin-top: 4px;">Navigateurs</p>
          <div class="space-y-2">
            <div v-for="b in stats.browsers" :key="b.browser" style="display: flex; justify-content: space-between; padding: 4px 0;">
              <span style="color: #94a3b8; font-size: 13px;">{{ b.browser }}</span>
              <span style="color: #a78bfa; font-size: 13px; font-weight: 700;">{{ b.count }}</span>
            </div>
          </div>
          <p style="font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; margin-top: 16px;">Appareils</p>
          <div class="space-y-2">
            <div v-for="d in stats.devices" :key="d.device_type" style="display: flex; justify-content: space-between; padding: 4px 0;">
              <span style="color: #94a3b8; font-size: 13px;">{{ d.device_type === 'Mobile' ? '📱 Mobile' : '💻 Desktop' }}</span>
              <span style="color: #a78bfa; font-size: 13px; font-weight: 700;">{{ d.count }}</span>
            </div>
          </div>
        </div>
      </div>


      <!-- Navigation dans l'App (Fonctionnalités utilisées) -->
      <div v-if="stats.app_pages && stats.app_pages.length" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; overflow: hidden;">
        <div style="padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.07); display: flex; align-items: center; justify-content: space-between;">
          <div>
            <h3 style="font-size: 15px; font-weight: 700; color: #f1f5f9; display: flex; align-items: center; gap: 8px;">
              <span>⚡</span> Navigation dans l'App — Fonctionnalités utilisées
            </h3>
            <p style="color: #64748b; font-size: 12px; margin-top: 4px;">Pages visitées par les utilisateurs connectés ({{ stats.app_sessions }} sessions actives)</p>
          </div>
          <div style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); color: #34d399; border-radius: 8px; padding: 4px 12px; font-size: 12px; font-weight: 600;">{{ stats.app_sessions }} utilisateurs connectés</div>
        </div>
        
        <!-- En-têtes -->
        <div style="display: grid; grid-template-columns: 1fr 80px 100px 100px; padding: 10px 24px; border-bottom: 1px solid rgba(255,255,255,0.07); background: rgba(0,0,0,0.2);">
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Page / Fonctionnalité</span>
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Vues</span>
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Utilisateurs</span>
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Temps moy.</span>
        </div>
        
        <div>
          <div v-for="page in stats.app_pages" :key="page.page_url"
            style="display: grid; grid-template-columns: 1fr 80px 100px 100px; padding: 12px 24px; border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.15s;"
            @mouseover="e => e.currentTarget.style.background='rgba(16,185,129,0.04)'"
            @mouseleave="e => e.currentTarget.style.background='transparent'">
            
            <div style="display: flex; align-items: center; gap: 10px;">
              <!-- Icône selon la page -->
              <span style="font-size: 16px;">{{ appPageIcon(page.page_url) }}</span>
              <div>
                <div style="color: #e2e8f0; font-size: 13px; font-weight: 600;">{{ appPageLabel(page.page_url) }}</div>
                <div style="color: #475569; font-size: 11px; font-family: monospace;">{{ page.page_url }}</div>
              </div>
              <!-- Barre de progression relative -->
              <div style="flex: 1; margin-left: 16px; height: 4px; background: rgba(255,255,255,0.05); border-radius: 2px; max-width: 200px;">
                <div style="height: 100%; border-radius: 2px; background: linear-gradient(90deg, #10b981, #34d399); transition: width 0.3s;" 
                     :style="{ width: Math.max(4, (page.views / stats.app_pages[0].views) * 100) + '%' }"></div>
              </div>
            </div>
            
            <div style="color: #a78bfa; font-size: 13px; font-weight: 700;">{{ page.views }}</div>
            <div style="color: #64748b; font-size: 13px;">{{ page.unique_users }} users</div>
            <div style="color: #34d399; font-size: 13px;">{{ page.avg_time ? formatTime(page.avg_time) : '—' }}</div>
          </div>
        </div>
      </div>

      <!-- Tableau des Visiteurs Individuels -->
      <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; overflow: hidden;">
        <div style="padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.07); display: flex; align-items: center; justify-content: space-between;">
          <h3 style="font-size: 15px; font-weight: 700; color: #f1f5f9; display: flex; align-items: center; gap: 8px;">
            <span>👥</span> Visiteurs individuels <span style="color: #64748b; font-size: 13px; font-weight: 400;">({{ filteredVisitors.length }} sessions)</span>
          </h3>
          <div class="flex items-center gap-3">
            <!-- Filtre pays -->
            <select v-model="filterCountry" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; border-radius: 8px; padding: 6px 12px; font-size: 13px; outline: none; cursor: pointer;">
              <option value="">Tous les pays</option>
              <option v-for="c in allCountries" :key="c" :value="c">{{ c }}</option>
            </select>
            <!-- Filtre appareil -->
            <select v-model="filterDevice" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; border-radius: 8px; padding: 6px 12px; font-size: 13px; outline: none; cursor: pointer;">
              <option value="">Tous les appareils</option>
              <option value="Mobile">Mobile</option>
              <option value="Desktop">Desktop</option>
            </select>
            <!-- Recherche -->
            <input v-model="searchQuery" placeholder="Rechercher IP, pays..." style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; border-radius: 8px; padding: 6px 14px; font-size: 13px; outline: none; width: 200px;" />
          </div>
        </div>

        <!-- En-têtes du tableau -->
        <div style="display: grid; grid-template-columns: 140px 100px 130px 100px 90px 80px 100px 80px 1fr; padding: 10px 24px; border-bottom: 1px solid rgba(255,255,255,0.07); background: rgba(0,0,0,0.2);">
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Date</span>
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Pays / Ville</span>
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">IP</span>
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Appareil</span>
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Navigateur</span>
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Langue</span>
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Temps</span>
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Pages</span>
          <span style="color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Parcours</span>
        </div>

        <!-- Lignes visiteurs -->
        <div style="max-height: 600px; overflow-y: auto;">
          <div 
            v-for="(v, i) in paginatedVisitors" :key="v.session_id"
            style="display: grid; grid-template-columns: 140px 100px 130px 100px 90px 80px 100px 80px 1fr; padding: 12px 24px; border-bottom: 1px solid rgba(255,255,255,0.04); cursor: pointer; transition: background 0.15s;"
            @click="selectedVisitor = v"
            @mouseover="e => e.currentTarget.style.background='rgba(139,92,246,0.05)'"
            @mouseleave="e => e.currentTarget.style.background='transparent'">
            
            <div>
              <div style="color: #e2e8f0; font-size: 12px; font-weight: 600;">{{ formatDate(v.first_visit) }}</div>
              <div style="color: #475569; font-size: 11px;">{{ formatHour(v.first_visit) }}</div>
            </div>
            
            <div>
              <div style="color: #e2e8f0; font-size: 12px; font-weight: 600;">{{ v.country || '?' }}</div>
              <div style="color: #475569; font-size: 11px;">{{ v.city || '' }}</div>
            </div>
            
            <div style="color: #64748b; font-size: 12px; font-family: monospace;">{{ v.ip_address }}</div>
            
            <div>
              <span :style="{ background: v.device_type === 'Mobile' ? 'rgba(59,130,246,0.15)' : 'rgba(16,185,129,0.15)', color: v.device_type === 'Mobile' ? '#60a5fa' : '#34d399' }" style="font-size: 11px; font-weight: 600; border-radius: 6px; padding: 2px 8px;">
                {{ v.device_type === 'Mobile' ? '📱 Mobile' : '💻 Desktop' }}
              </span>
            </div>
            
            <div style="color: #94a3b8; font-size: 12px;">{{ v.browser }}</div>
            <div style="color: #64748b; font-size: 12px;">{{ v.language }}</div>
            
            <div style="color: #a78bfa; font-size: 12px; font-weight: 600;">{{ formatTime(v.total_time_spent) }}</div>
            
            <div style="color: #8b5cf6; font-size: 12px; font-weight: 700; text-align: center;">{{ v.pages_visited }}</div>
            
            <div style="color: #475569; font-size: 11px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" :title="v.pages_path">{{ v.pages_path }}</div>
          </div>
          <div v-if="!paginatedVisitors.length" style="padding: 40px; text-align: center; color: #475569; font-size: 14px;">Aucun visiteur trouvé</div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" style="padding: 14px 24px; border-top: 1px solid rgba(255,255,255,0.07); display: flex; align-items: center; justify-content: space-between;">
          <span style="color: #475569; font-size: 13px;">Page {{ currentPage }} / {{ totalPages }}</span>
          <div class="flex gap-2">
            <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; border-radius: 6px; padding: 5px 12px; font-size: 13px; cursor: pointer;" :style="{ opacity: currentPage === 1 ? 0.4 : 1 }">← Préc.</button>
            <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; border-radius: 6px; padding: 5px 12px; font-size: 13px; cursor: pointer;" :style="{ opacity: currentPage === totalPages ? 0.4 : 1 }">Suiv. →</button>
          </div>
        </div>
      </div>

      <!-- Modal Détail Visiteur -->
      <div v-if="selectedVisitor" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100; display: flex; align-items: center; justify-content: center; padding: 24px;" @click.self="selectedVisitor = null">
        <div style="background: #111827; border: 1px solid rgba(139,92,246,0.3); border-radius: 20px; padding: 32px; max-width: 700px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.5);">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <h2 style="font-size: 18px; font-weight: 700; color: #f1f5f9; display: flex; align-items: center; gap: 10px;">
              <span>👤</span> Détail du visiteur
            </h2>
            <button @click="selectedVisitor = null" style="background: rgba(255,255,255,0.07); border: none; color: #94a3b8; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; font-size: 16px;">✕</button>
          </div>
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
            <div v-for="field in visitorFields" :key="field.label" style="background: rgba(255,255,255,0.03); border-radius: 10px; padding: 12px 16px;">
              <p style="color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">{{ field.label }}</p>
              <p style="color: #e2e8f0; font-size: 14px; font-weight: 600;">{{ field.value }}</p>
            </div>
          </div>

          <div style="background: rgba(255,255,255,0.03); border-radius: 10px; padding: 16px;">
            <p style="color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px;">📍 Parcours de navigation</p>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
              <span v-for="(page, i) in (selectedVisitor.pages_path || '').split(' → ')" :key="i" style="background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.2); color: #a78bfa; border-radius: 6px; padding: 4px 10px; font-size: 12px;">
                {{ page }}
              </span>
            </div>
          </div>
        </div>
      </div>

    </main>

    <style>
      @keyframes spin { to { transform: rotate(360deg); } }
    </style>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { baseUrl } from '@/utils/tracker'

export default {
  name: 'AdminAnalyticsView',
  setup() {
    const router = useRouter()
    const stats = ref(null)
    const loading = ref(true)
    const lastRefresh = ref('')
    const selectedVisitor = ref(null)
    const searchQuery = ref('')
    const filterCountry = ref('')
    const filterDevice = ref('')
    const currentPage = ref(1)
    const perPage = 25

    const checkAuth = () => {
      // Pas de login bloquant pour le moment sur le FTP
      return true
    }

    const fetchStats = async () => {
      if (!checkAuth()) return
      loading.value = true
      try {
        const res = await fetch(`${baseUrl}/getAnalytics.php`)
        const data = await res.json()
        if (data.status === 'success') {
          stats.value = data.data
          lastRefresh.value = new Date().toLocaleTimeString('fr-FR')
        }
      } catch (e) {
        console.error(e)
      } finally {
        loading.value = false
      }
    }

    const kpis = computed(() => {
      if (!stats.value) return []
      return [
        { label: 'Sessions uniques', value: stats.value.total_sessions, icon: '👤', bg: 'rgba(139,92,246,0.15)' },
        { label: 'Pages vues', value: stats.value.total_page_views, icon: '👁️', bg: 'rgba(99,102,241,0.15)' },
        { label: 'Temps moyen / page', value: formatTime(stats.value.average_time_spent), icon: '⏱️', bg: 'rgba(16,185,129,0.15)' },
        { label: 'Pays distincts', value: stats.value.countries?.length || 0, icon: '🌍', bg: 'rgba(245,158,11,0.15)' },
      ]
    })

    const allCountries = computed(() => {
      if (!stats.value?.visitors) return []
      return [...new Set(stats.value.visitors.map(v => v.country).filter(Boolean))].sort()
    })

    const filteredVisitors = computed(() => {
      if (!stats.value?.visitors) return []
      return stats.value.visitors.filter(v => {
        const q = searchQuery.value.toLowerCase()
        const matchSearch = !q || (v.ip_address || '').toLowerCase().includes(q) || (v.country || '').toLowerCase().includes(q) || (v.city || '').toLowerCase().includes(q) || (v.browser || '').toLowerCase().includes(q)
        const matchCountry = !filterCountry.value || v.country === filterCountry.value
        const matchDevice = !filterDevice.value || v.device_type === filterDevice.value
        return matchSearch && matchCountry && matchDevice
      })
    })

    const totalPages = computed(() => Math.ceil(filteredVisitors.value.length / perPage))

    const paginatedVisitors = computed(() => {
      const start = (currentPage.value - 1) * perPage
      return filteredVisitors.value.slice(start, start + perPage)
    })

    const visitorFields = computed(() => {
      const v = selectedVisitor.value
      if (!v) return []
      return [
        { label: 'IP', value: v.ip_address },
        { label: 'Pays', value: v.country || '?' },
        { label: 'Ville', value: v.city || '?' },
        { label: 'Appareil', value: v.device_type },
        { label: 'OS', value: v.os },
        { label: 'Navigateur', value: v.browser },
        { label: 'Langue', value: v.language || '?' },
        { label: 'Résolution', value: v.screen_res || '?' },
        { label: 'Pages visitées', value: v.pages_visited },
        { label: 'Temps total', value: formatTime(v.total_time_spent) },
        { label: 'Référent', value: v.referrer || 'Direct' },
        { label: 'Première visite', value: formatDate(v.first_visit) + ' ' + formatHour(v.first_visit) },
      ]
    })

    const formatTime = (seconds) => {
      if (!seconds || seconds <= 0) return '0s'
      if (seconds < 60) return `${seconds}s`
      const m = Math.floor(seconds / 60), s = seconds % 60
      return `${m}m ${s}s`
    }

    const formatDate = (dt) => {
      if (!dt) return ''
      return new Date(dt).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    }

    const formatHour = (dt) => {
      if (!dt) return ''
      return new Date(dt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    }

    const logout = () => {
      localStorage.removeItem('nuvio_admin')
      router.push('/admin/login')
    }

    const appPageLabel = (url) => {
      if (url.includes('/connect')) return 'Connexion'
      if (url.includes('/files')) return 'Fichiers'
      if (url.includes('/history')) return 'Historique'
      if (url.includes('/settings')) return 'Paramètres'
      if (url.includes('/terminal')) return 'Terminal'
      if (url.includes('/log')) return 'Logs'
      if (url.includes('/docs')) return 'Documentation'
      if (url.includes('/favorites')) return 'Favoris'
      if (url.includes('/')) return 'Accueil'
      return 'Page interne'
    }

    const appPageIcon = (url) => {
      if (url.includes('/connect')) return '🔌'
      if (url.includes('/files')) return '📁'
      if (url.includes('/history')) return '⏱️'
      if (url.includes('/settings')) return '⚙️'
      if (url.includes('/terminal')) return '⌨️'
      if (url.includes('/log')) return '📄'
      if (url.includes('/docs')) return '📚'
      if (url.includes('/favorites')) return '⭐'
      if (url.includes('/')) return '🏠'
      return '🔹'
    }

    onMounted(() => fetchStats())

    return {
      stats, loading, lastRefresh, selectedVisitor, searchQuery,
      filterCountry, filterDevice, currentPage, totalPages,
      kpis, allCountries, filteredVisitors, paginatedVisitors, visitorFields,
      formatTime, formatDate, formatHour, logout, fetchStats,
      appPageLabel, appPageIcon
    }
  }
}
</script>
