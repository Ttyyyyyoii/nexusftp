<template>
  <BaseModal :visible="visible" :title="$t('guest.modalTitle')" @close="$emit('close')" maxWidth="max-w-lg">

    <!-- Success State -->
    <div v-if="generatedUrl" class="space-y-5">
      <!-- Success Banner -->
      <div class="flex flex-col items-center gap-3 py-6 px-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl text-center">
        <div class="w-14 h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
          <CheckCircle class="w-7 h-7 text-emerald-500" />
        </div>
        <div>
          <p class="font-bold text-emerald-800 dark:text-emerald-300">{{ $t('guest.createdTitle') }}</p>
          <p class="text-sm text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $t('guest.createdDesc') }}</p>
        </div>
      </div>

      <!-- Link Copy -->
      <div class="space-y-1.5">
        <label class="block text-xs font-semibold text-surface-600 dark:text-surface-400 uppercase tracking-wider">
          {{ $t('guest.linkLabel') }}
        </label>
        <div class="flex gap-2">
          <div class="flex-1 flex items-center px-3 py-2.5 rounded-xl bg-surface-50 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 overflow-hidden">
            <LinkIcon class="w-4 h-4 text-surface-400 shrink-0 mr-2" />
            <span class="text-sm font-mono text-surface-700 dark:text-surface-300 truncate">{{ generatedUrl }}</span>
          </div>
          <button @click="copyUrl" class="btn-primary flex items-center gap-2 shrink-0">
            <Copy class="w-4 h-4" />
            {{ $t('guest.copyBtn') }}
          </button>
        </div>
      </div>

      <!-- New link button -->
      <div class="flex justify-center pt-2">
        <button @click="reset" class="btn-secondary text-sm flex items-center gap-2">
          <Plus class="w-4 h-4" />
          {{ $t('guest.createAnother') }}
        </button>
      </div>
    </div>

    <!-- Form State -->
    <div v-else class="space-y-5">
      <!-- Folder Input -->
      <div class="space-y-1.5">
        <label class="block text-xs font-semibold text-surface-600 dark:text-surface-400 uppercase tracking-wider">
          {{ $t('guest.folderLabel') }}
        </label>
        <div class="relative">
          <Folder class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
          <input type="text" v-model="form.remotePath" required
            class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200 text-sm transition-all outline-none" />
        </div>
        <p class="text-xs text-surface-500">{{ $t('guest.folderHint') }}</p>
      </div>

      <!-- Permissions -->
      <div class="space-y-1.5">
        <label class="block text-xs font-semibold text-surface-600 dark:text-surface-400 uppercase tracking-wider">
          {{ $t('guest.permissionsLabel') }}
        </label>
        <div class="grid grid-cols-2 gap-3">
          <label
            class="flex items-center gap-3 p-3.5 border-2 rounded-xl cursor-pointer transition-all"
            :class="form.permission === 'read'
              ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
              : 'border-surface-200 dark:border-surface-700 hover:border-surface-300 dark:hover:border-surface-600'">
            <input type="radio" v-model="form.permission" value="read" class="sr-only" />
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
              :class="form.permission === 'read' ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400' : 'bg-surface-100 dark:bg-surface-800 text-surface-500'">
              <Eye class="w-4 h-4" />
            </div>
            <div>
              <div class="text-sm font-semibold text-surface-900 dark:text-white">{{ $t('guest.readOnly') }}</div>
              <div class="text-xs text-surface-500">{{ $t('guest.readOnlyDesc') }}</div>
            </div>
          </label>

          <label
            class="flex items-center gap-3 p-3.5 border-2 rounded-xl cursor-pointer transition-all"
            :class="form.permission === 'upload'
              ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
              : 'border-surface-200 dark:border-surface-700 hover:border-surface-300 dark:hover:border-surface-600'">
            <input type="radio" v-model="form.permission" value="upload" class="sr-only" />
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
              :class="form.permission === 'upload' ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400' : 'bg-surface-100 dark:bg-surface-800 text-surface-500'">
              <Upload class="w-4 h-4" />
            </div>
            <div>
              <div class="text-sm font-semibold text-surface-900 dark:text-white">{{ $t('guest.readWrite') }}</div>
              <div class="text-xs text-surface-500">{{ $t('guest.readWriteDesc') }}</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Expiration + Password Row -->
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label class="block text-xs font-semibold text-surface-600 dark:text-surface-400 uppercase tracking-wider">
            {{ $t('guest.expirationLabel') }}
          </label>
          <div class="relative">
            <Clock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
            <select v-model="form.expiration"
              class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200 text-sm appearance-none outline-none">
              <option value="1">{{ $t('guest.exp1d') }}</option>
              <option value="7">{{ $t('guest.exp7d') }}</option>
              <option value="30">{{ $t('guest.exp30d') }}</option>
              <option value="0">{{ $t('guest.expNever') }}</option>
            </select>
          </div>
        </div>

        <div class="space-y-1.5">
          <label class="block text-xs font-semibold text-surface-600 dark:text-surface-400 uppercase tracking-wider">
            {{ $t('guest.passwordLabel') }} <span class="normal-case font-normal text-surface-400">({{ $t('guest.passwordHint') }})</span>
          </label>
          <div class="relative">
            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
            <input type="text" v-model="form.password" :placeholder="$t('guest.passwordPlaceholder')"
              class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200 text-sm outline-none" />
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="flex items-start gap-2 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-3">
        <AlertCircle class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
        <p class="text-sm text-rose-700 dark:text-rose-300">{{ error }}</p>
      </div>
    </div>

    <!-- Footer Slot -->
    <template #footer v-if="!generatedUrl">
      <button @click="$emit('close')" class="btn-secondary text-sm">{{ $t('common.cancel') }}</button>
      <button @click="createShare" :disabled="loading || !form.remotePath" class="btn-primary text-sm flex items-center gap-2">
        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
        <Users v-else class="w-4 h-4" />
        {{ loading ? $t('guest.creating') : $t('guest.createBtn') }}
      </button>
    </template>

  </BaseModal>
</template>

<script>
import BaseModal from '@/components/ui/BaseModal.vue'
import { Users, Folder, Eye, Upload, Lock, Clock, CheckCircle, Copy, AlertCircle, Loader2, Link as LinkIcon, Plus } from 'lucide-vue-next'
import { useConnectionStore } from '@/stores/connection'

export default {
  name: 'GuestShareModal',
  components: { BaseModal, Users, Folder, Eye, Upload, Lock, Clock, CheckCircle, Copy, AlertCircle, Loader2, LinkIcon, Plus },
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
      form: { remotePath: '/', permission: 'read', expiration: '7', password: '' }
    }
  },
  watch: {
    visible(val) {
      if (val) this.reset()
    }
  },
  setup() {
    return { connectionStore: useConnectionStore() }
  },
  methods: {
    showToast(title, type = 'info') {
      window.dispatchEvent(new CustomEvent('show-toast', { detail: { title, type } }))
    },
    reset() {
      this.generatedUrl = null
      this.error = null
      this.form = { remotePath: this.currentPath || '/', permission: 'read', expiration: '7', password: '' }
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
          body: JSON.stringify({ sessionId: this.sessionId, ...this.form })
        })
        const data = await res.json()
        if (data.success) {
          this.generatedUrl = data.guestUrl
          this.showToast(this.$t('guest.createSuccess'), 'success')
        } else {
          throw new Error(data.message || this.$t('guest.createError'))
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
        this.showToast(this.$t('guest.copied'), 'success')
      } catch (e) {
        this.showToast(this.$t('guest.copyError'), 'error')
      }
    }
  }
}
</script>
