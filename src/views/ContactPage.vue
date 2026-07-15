<template>
  <DefaultLayout>
    <div class="pt-24 pb-16">
      <div class="max-w-2xl mx-auto px-6">
        <div class="text-center mb-12">
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center mx-auto mb-6 shadow-glow">
            <MessageCircle class="w-8 h-8 text-white" />
          </div>
          <h1 class="text-3xl sm:text-4xl font-bold text-surface-900 dark:text-white mb-4">{{ $t('contact.title') }}</h1>
          <p class="text-lg text-surface-500 dark:text-surface-400">{{ $t('contact.subtitle') }}</p>
        </div>
        <form @submit.prevent="handleSubmit" class="glass-panel rounded-2xl p-8 space-y-6">
          <div class="grid sm:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">{{ $t('contact.name') }}</label>
              <div class="relative">
                <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                <input v-model="form.name" type="text" required class="w-full pl-10 pr-4 py-3 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200 transition-all" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">{{ $t('contact.email') }}</label>
              <div class="relative">
                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                <input v-model="form.email" type="email" required class="w-full pl-10 pr-4 py-3 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200 transition-all" />
              </div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">{{ $t('contact.subject') }}</label>
            <input v-model="form.subject" type="text" required class="w-full px-4 py-3 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200 transition-all" />
          </div>
          <div>
            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">{{ $t('contact.message') }}</label>
            <textarea v-model="form.message" rows="5" required class="w-full px-4 py-3 rounded-xl bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 focus:ring-2 focus:ring-primary-500 dark:text-surface-200 transition-all resize-none"></textarea>
          </div>
          <button type="submit" :disabled="sending" class="w-full btn-primary flex items-center justify-center gap-2 py-3 disabled:opacity-50">
            <Loader2 v-if="sending" class="w-4 h-4 animate-spin" /><Send v-else class="w-4 h-4" />{{ $t('contact.send') }}
          </button>
          <p v-if="result" class="text-center text-sm p-3 rounded-xl" :class="resultClass">{{ result }}</p>
        </form>
      </div>
    </div>
  </DefaultLayout>
</template>

<script>
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { MessageCircle, User, Mail, Send, Loader2 } from 'lucide-vue-next'
export default {
  name: 'ContactPage',
  components: { DefaultLayout, MessageCircle, User, Mail, Send, Loader2 },
  data() { return { form: { name: '', email: '', subject: '', message: '' }, sending: false, result: '', resultClass: '' } },
  methods: {
    async handleSubmit() {
      this.sending = true; this.result = ''
      await new Promise(resolve => setTimeout(resolve, 1500))
      this.result = this.$t('contact.success')
      this.resultClass = 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400'
      this.sending = false
      this.form = { name: '', email: '', subject: '', message: '' }
      setTimeout(() => { this.result = '' }, 5000)
    }
  }
}
</script>
