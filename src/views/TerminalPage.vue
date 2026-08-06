<template>
  <AppLayout>
    <div class="h-full flex flex-col bg-surface-950 p-4 md:p-6">
      
      <!-- Header -->
      <div class="flex items-center justify-between mb-4 shrink-0">
        <div>
          <h1 class="text-2xl font-bold text-white flex items-center gap-3">
            <Terminal class="w-6 h-6 text-primary-500" />
            {{ $t('nav.terminal') }}
          </h1>
          <p class="text-surface-400 text-sm mt-1">
            Console SSH Interactive
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span v-if="connectionStore.isConnected && connectionStore.type === 'sftp'" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-medium border border-emerald-500/20">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
            Connecté (SSH)
          </span>
          <span v-else-if="connectionStore.isConnected" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-500/10 text-amber-400 text-xs font-medium border border-amber-500/20">
            <AlertCircle class="w-3.5 h-3.5" />
            Mode Limité (Pas SSH)
          </span>
          <button @click="clearTerminal" class="p-2 rounded-lg bg-surface-900 text-surface-400 hover:text-white hover:bg-surface-800 transition-colors" title="Effacer l'écran">
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Terminal Window -->
      <div 
        class="flex-1 bg-[#0f111a] rounded-xl border border-surface-800 shadow-2xl overflow-hidden flex flex-col font-mono text-sm relative"
        @click="focusInput"
      >
        <!-- Top bar fake -->
        <div class="h-8 bg-surface-900 border-b border-surface-800 flex items-center px-4 gap-2 shrink-0">
          <div class="w-3 h-3 rounded-full bg-rose-500/80"></div>
          <div class="w-3 h-3 rounded-full bg-amber-500/80"></div>
          <div class="w-3 h-3 rounded-full bg-emerald-500/80"></div>
          <div class="flex-1 text-center text-xs text-surface-500 font-sans">
            {{ connectionStore.isConnected ? `${connectionStore.username}@${connectionStore.host}` : 'nexus-terminal' }}
          </div>
        </div>

        <!-- Terminal Output -->
        <div ref="terminalOutput" class="flex-1 overflow-y-auto p-4 text-surface-300 space-y-1">
          
          <!-- Initial Welcome Message -->
          <div class="mb-4 text-surface-500">
            <div>Welcome to NexusFTP Web Terminal v1.0.0</div>
            <div v-if="!connectionStore.isConnected" class="text-rose-400 mt-2">
              Erreur: Vous devez d'abord vous connecter à un serveur.
            </div>
            <div v-else-if="connectionStore.type !== 'sftp'" class="text-amber-400 mt-2">
              Attention: Le terminal complet nécessite une connexion SFTP (SSH). 
              La connexion actuelle ({{ connectionStore.type.toUpperCase() }}) ne supporte pas l'exécution de commandes système directes.
            </div>
            <div v-else class="text-emerald-400 mt-2">
              Connexion SSH établie avec {{ connectionStore.host }}.
            </div>
          </div>

          <!-- Lines -->
          <div v-for="(line, index) in lines" :key="index" class="whitespace-pre-wrap break-words" :class="line.type === 'error' ? 'text-rose-400' : (line.type === 'command' ? 'text-white font-semibold' : 'text-surface-300')">
            <span v-if="line.type === 'command'" class="text-primary-500 mr-2">➜</span>
            <span v-if="line.type === 'command'" class="text-emerald-400 mr-2">~</span>
            {{ line.text }}
          </div>

          <!-- Current Input Line -->
          <div v-if="connectionStore.isConnected && connectionStore.type === 'sftp'" class="flex items-center mt-2 group relative">
            <span class="text-primary-500 mr-2 shrink-0">➜</span>
            <span class="text-emerald-400 mr-2 shrink-0">~</span>
            <input 
              ref="commandInput"
              v-model="currentCommand"
              @keydown.enter="executeCommand"
              @keydown.up.prevent="historyUp"
              @keydown.down.prevent="historyDown"
              type="text" 
              class="flex-1 bg-transparent border-none outline-none text-white focus:ring-0 p-0 m-0 caret-transparent"
              spellcheck="false"
              autocomplete="off"
              :disabled="executing"
            />
            <!-- Custom Blinking Cursor positioned exactly at input text length -->
            <span class="absolute pointer-events-none text-white h-4 w-2 bg-white/70 animate-pulse" :style="{ left: cursorOffset + 'px', top: '4px' }"></span>
          </div>

          <!-- Executing indicator -->
          <div v-if="executing" class="text-surface-500 mt-2 flex items-center gap-2">
            <Loader2 class="w-4 h-4 animate-spin" /> Exécution...
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/layouts/AppLayout.vue'
import { useConnectionStore } from '@/stores/connection'
import { Terminal, Trash2, AlertCircle, Loader2 } from 'lucide-vue-next'

export default {
  name: 'TerminalPage',
  components: { AppLayout, Terminal, Trash2, AlertCircle, Loader2 },
  data() {
    return {
      connectionStore: useConnectionStore(),
      lines: [],
      currentCommand: '',
      commandHistory: [],
      historyIndex: -1,
      executing: false
    }
  },
  computed: {
    cursorOffset() {
      // Very basic approximation for monospace char width + the prompt width
      const promptWidth = 40 // roughly the width of "➜ ~ "
      const charWidth = 8.4 // roughly 8.4px per char in font-mono text-sm
      return promptWidth + (this.currentCommand.length * charWidth)
    }
  },
  mounted() {
    this.focusInput()
  },
  methods: {
    focusInput() {
      if (this.$refs.commandInput) {
        this.$refs.commandInput.focus()
      }
    },
    clearTerminal() {
      this.lines = []
      this.focusInput()
    },
    historyUp() {
      if (this.commandHistory.length === 0) return
      if (this.historyIndex < this.commandHistory.length - 1) {
        this.historyIndex++
        this.currentCommand = this.commandHistory[this.commandHistory.length - 1 - this.historyIndex]
      }
    },
    historyDown() {
      if (this.historyIndex > 0) {
        this.historyIndex--
        this.currentCommand = this.commandHistory[this.commandHistory.length - 1 - this.historyIndex]
      } else if (this.historyIndex === 0) {
        this.historyIndex = -1
        this.currentCommand = ''
      }
    },
    async executeCommand() {
      const cmd = this.currentCommand.trim()
      if (!cmd) return
      
      this.lines.push({ type: 'command', text: cmd })
      this.commandHistory.push(cmd)
      this.historyIndex = -1
      this.currentCommand = ''
      
      if (cmd === 'clear') {
        this.clearTerminal()
        return
      }

      this.executing = true
      this.scrollToBottom()

      try {
        const API_BASE = import.meta.env.VITE_API_URL || '/api'
        const response = await fetch(`${API_BASE}/terminal.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({
            sessionId: this.connectionStore.sessionId,
            command: cmd
          })
        })
        
        const data = await response.json()
        
        if (data.success) {
          if (data.output) {
            this.lines.push({ type: 'output', text: data.output })
          } else {
            this.lines.push({ type: 'output', text: ' ' }) // empty response ok
          }
        } else {
          this.lines.push({ type: 'error', text: data.message || 'Erreur lors de l\'exécution' })
        }
      } catch (err) {
        this.lines.push({ type: 'error', text: 'Erreur réseau: Impossible de contacter le serveur.' })
      } finally {
        this.executing = false
        this.scrollToBottom()
        this.$nextTick(() => {
          this.focusInput()
        })
      }
    },
    scrollToBottom() {
      this.$nextTick(() => {
        if (this.$refs.terminalOutput) {
          this.$refs.terminalOutput.scrollTop = this.$refs.terminalOutput.scrollHeight
        }
      })
    }
  }
}
</script>
