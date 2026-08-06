<template>
  <BaseModal :visible="visible" :title="$t('premium.title')" @close="$emit('close')" maxWidth="max-w-2xl">
    <div class="flex flex-col items-center justify-center p-4 text-center">
      <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center mb-6 shadow-glow">
        <Sparkles class="w-8 h-8 text-white" />
      </div>
      <h2 class="text-2xl font-bold text-surface-900 dark:text-white mb-2">{{ $t('premium.subtitle') }}</h2>
      <p class="text-surface-500 dark:text-surface-400 mb-8 max-w-md">{{ $t('premium.description') }}</p>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full mb-8">
        <div v-for="(feature, index) in features" :key="index" class="bg-surface-50 dark:bg-surface-900/50 rounded-xl p-4 flex items-start gap-3 text-left border border-surface-200 dark:border-surface-800">
          <CheckCircle2 class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
          <div>
            <p class="font-semibold text-surface-800 dark:text-surface-200">{{ feature.title }}</p>
            <p class="text-xs text-surface-500">{{ feature.desc }}</p>
          </div>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-4 w-full justify-center">
        <button v-if="!settingsStore.isPremium" @click="activate" class="px-8 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl font-bold flex items-center justify-center gap-2 shadow-lg transition-all hover:scale-105">
          <Star class="w-5 h-5" /> {{ $t('premium.activate') }}
        </button>
        <button v-else @click="deactivate" class="px-8 py-3 bg-surface-200 hover:bg-surface-300 dark:bg-surface-700 dark:hover:bg-surface-600 text-surface-800 dark:text-surface-200 rounded-xl font-bold flex items-center justify-center gap-2 transition-all">
          {{ $t('premium.deactivate') }}
        </button>
      </div>
      <p class="text-xs text-surface-400 mt-4">{{ $t('premium.note') }}</p>
    </div>
  </BaseModal>
</template>

<script>
import BaseModal from './ui/BaseModal.vue'
import { Sparkles, Star, CheckCircle2 } from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'

export default {
  name: 'PremiumModal',
  components: { BaseModal, Sparkles, Star, CheckCircle2 },
  props: {
    visible: { type: Boolean, default: false }
  },
  data() {
    return {
      settingsStore: useSettingsStore()
    }
  },
  computed: {
    features() {
      return [
        { title: this.$t('premium.f1_title'), desc: this.$t('premium.f1_desc') },
        { title: this.$t('premium.f2_title'), desc: this.$t('premium.f2_desc') },
        { title: this.$t('premium.f3_title'), desc: this.$t('premium.f3_desc') },
        { title: this.$t('premium.f4_title'), desc: this.$t('premium.f4_desc') }
      ]
    }
  },
  methods: {
    activate() {
      this.settingsStore.activatePremium()
      window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: this.$t('premium.success'), type: 'success' } }))
      this.$emit('close')
    },
    deactivate() {
      this.settingsStore.deactivatePremium()
      window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: this.$t('premium.deactivated'), type: 'info' } }))
      this.$emit('close')
    }
  }
}
</script>
