<template>
  <div v-if="visible" class="fixed right-0 top-0 bottom-0 w-96 bg-surface-50 dark:bg-surface-900 border-l border-surface-200 dark:border-surface-700 shadow-2xl flex flex-col z-40 transform transition-transform duration-300">
    <!-- Header -->
    <div class="p-4 border-b border-surface-200 dark:border-surface-700 bg-white/50 dark:bg-surface-800/50 backdrop-blur-md flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg shadow-primary-500/20">
          <Bot class="w-5 h-5 text-white" />
        </div>
        <div>
          <h2 class="font-bold text-surface-900 dark:text-white leading-tight">NexusBot</h2>
          <p class="text-xs text-primary-600 dark:text-primary-400 font-medium">Assistant IA Premium</p>
        </div>
      </div>
      <button @click="$emit('close')" class="p-2 rounded-lg text-surface-500 hover:bg-surface-200 dark:hover:bg-surface-700 transition-colors">
        <X class="w-5 h-5" />
      </button>
    </div>

    <!-- File Context Badge -->
    <div v-if="fileContext" class="px-4 py-2 bg-primary-50 dark:bg-primary-900/20 border-b border-primary-100 dark:border-primary-900/30 flex items-center gap-2">
      <FileCode class="w-4 h-4 text-primary-500" />
      <span class="text-xs font-medium text-primary-700 dark:text-primary-300 truncate">Contexte: {{ fileContext.name }}</span>
      <button @click="$emit('clear-context')" class="ml-auto p-1 hover:bg-primary-100 dark:hover:bg-primary-800 rounded text-primary-600 dark:text-primary-400">
        <X class="w-3 h-3" />
      </button>
    </div>

    <!-- Messages Area -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="messagesContainer">
      <div v-if="messages.length === 0" class="h-full flex flex-col items-center justify-center text-center space-y-4 opacity-50">
        <MessageSquare class="w-12 h-12 text-surface-400" />
        <p class="text-sm text-surface-500 dark:text-surface-400 max-w-[200px]">Posez-moi une question sur vos fichiers ou votre code !</p>
      </div>
      
      <div v-for="(msg, index) in messages" :key="index" class="flex gap-3 max-w-full" :class="msg.role === 'user' ? 'flex-row-reverse' : ''">
        <div class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center" :class="msg.role === 'user' ? 'bg-surface-200 dark:bg-surface-700' : 'bg-primary-100 dark:bg-primary-900/30'">
          <User v-if="msg.role === 'user'" class="w-4 h-4 text-surface-600 dark:text-surface-300" />
          <Bot v-else class="w-4 h-4 text-primary-600 dark:text-primary-400" />
        </div>
        <div class="px-4 py-2.5 rounded-2xl max-w-[75%]" :class="msg.role === 'user' ? 'bg-primary-500 text-white rounded-tr-none' : 'bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 text-surface-800 dark:text-surface-200 rounded-tl-none'">
          <div class="prose prose-sm dark:prose-invert max-w-none text-sm whitespace-pre-wrap break-words" v-html="formatMessage(msg.content)"></div>
        </div>
      </div>
      
      <!-- Loading indicator -->
      <div v-if="loading" class="flex gap-3">
        <div class="w-8 h-8 shrink-0 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
          <Bot class="w-4 h-4 text-primary-600 dark:text-primary-400" />
        </div>
        <div class="px-4 py-3 bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-2xl rounded-tl-none flex gap-1 items-center">
          <div class="w-2 h-2 bg-primary-400 rounded-full animate-bounce"></div>
          <div class="w-2 h-2 bg-primary-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
          <div class="w-2 h-2 bg-primary-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
        </div>
      </div>
    </div>

    <!-- Input Area -->
    <div class="p-4 border-t border-surface-200 dark:border-surface-700 bg-white/50 dark:bg-surface-800/50">
      <div class="relative flex items-end gap-2">
        <textarea 
          v-model="input" 
          @keydown.enter.prevent="sendMessage"
          placeholder="Demandez à NexusBot..."
          class="w-full bg-surface-100 dark:bg-surface-900 border border-surface-300 dark:border-surface-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none max-h-32 min-h-[44px]"
          rows="1"
          @input="adjustTextareaHeight"
          ref="textarea"
        ></textarea>
        <button 
          @click="sendMessage" 
          :disabled="!input.trim() || loading"
          class="p-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:hover:bg-primary-500 text-white rounded-xl transition-colors shrink-0"
        >
          <Send class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { Bot, X, Send, User, MessageSquare, FileCode } from 'lucide-vue-next'
import { marked } from 'marked'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

export default {
  name: 'AIAssistant',
  components: { Bot, X, Send, User, MessageSquare, FileCode },
  props: {
    visible: { type: Boolean, default: false },
    fileContext: { type: Object, default: null },
    fileContent: { type: String, default: '' }
  },
  emits: ['close', 'clear-context'],
  data() {
    return {
      input: '',
      messages: [],
      loading: false
    }
  },
  methods: {
    adjustTextareaHeight() {
      const el = this.$refs.textarea
      el.style.height = 'auto'
      el.style.height = Math.min(el.scrollHeight, 128) + 'px'
    },
    formatMessage(text) {
      return marked.parse(text)
    },
    scrollToBottom() {
      this.$nextTick(() => {
        const container = this.$refs.messagesContainer
        if (container) container.scrollTop = container.scrollHeight
      })
    },
    async sendMessage() {
      if (!this.input.trim() || this.loading) return
      
      const prompt = this.input.trim()
      this.input = ''
      this.$refs.textarea.style.height = 'auto'
      
      this.messages.push({ role: 'user', content: prompt })
      this.scrollToBottom()
      
      this.loading = true
      try {
        const response = await fetch(`${API_BASE}/ai.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({
            prompt: prompt,
            fileContent: this.fileContext ? this.fileContent : ''
          })
        })
        const data = await response.json()
        if (data.success) {
          this.messages.push({ role: 'assistant', content: data.reply })
        } else {
          this.messages.push({ role: 'assistant', content: `**Erreur:** ${data.message}` })
        }
      } catch (err) {
        this.messages.push({ role: 'assistant', content: `**Erreur réseau:** Impossible de joindre l'IA.` })
      } finally {
        this.loading = false
        this.scrollToBottom()
      }
    }
  }
}
</script>

<style scoped>
:deep(.prose pre) {
  background-color: #1e293b;
  color: #e2e8f0;
  padding: 0.75rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin-top: 0.5rem;
  margin-bottom: 0.5rem;
}
:deep(.prose code) {
  font-family: monospace;
  background-color: rgba(148, 163, 184, 0.2);
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.875em;
}
</style>
