<template>
  <DefaultLayout>
    <div class="pt-24 pb-16">
      <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-12">
          <h1 class="text-3xl sm:text-4xl font-bold text-surface-900 dark:text-white mb-4">{{ $t('faq.title') }}</h1>
          <p class="text-lg text-surface-500 dark:text-surface-400">{{ $t('faq.subtitle') }}</p>
        </div>
        <div class="space-y-4">
          <div v-for="(faq, index) in faqs" :key="index" class="glass-panel rounded-2xl overflow-hidden">
            <button @click="toggleFaq(index)" class="w-full flex items-center justify-between p-5 text-left hover:bg-surface-50 dark:hover:bg-surface-800/50 transition-colors">
              <span class="font-medium text-surface-900 dark:text-white pr-4">{{ faq.question }}</span>
              <ChevronDown class="w-5 h-5 text-surface-400 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': openFaq === index }" />
            </button>
            <transition name="accordion">
              <div v-show="openFaq === index" class="px-5 pb-5">
                <p class="text-surface-600 dark:text-surface-400 leading-relaxed">{{ faq.answer }}</p>
              </div>
            </transition>
          </div>
        </div>
        <div class="mt-12 text-center">
          <p class="text-surface-500 dark:text-surface-400 mb-4">Still have questions?</p>
          <router-link to="/contact" class="btn-primary inline-flex items-center gap-2"><MessageCircle class="w-4 h-4" /> Contact Us</router-link>
        </div>
      </div>
    </div>
  </DefaultLayout>
</template>

<script>
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { ChevronDown, MessageCircle } from 'lucide-vue-next'
export default {
  name: 'FAQPage',
  components: { DefaultLayout, ChevronDown, MessageCircle },
  data() {
    return {
      openFaq: null,
      faqs: [
        { question: this.$t('faq.questions.q1.question'), answer: this.$t('faq.questions.q1.answer') },
        { question: this.$t('faq.questions.q2.question'), answer: this.$t('faq.questions.q2.answer') },
        { question: this.$t('faq.questions.q3.question'), answer: this.$t('faq.questions.q3.answer') },
        { question: this.$t('faq.questions.q4.question'), answer: this.$t('faq.questions.q4.answer') },
        { question: this.$t('faq.questions.q5.question'), answer: this.$t('faq.questions.q5.answer') },
        { question: this.$t('faq.questions.q6.question'), answer: this.$t('faq.questions.q6.answer') }
      ]
    }
  },
  methods: { toggleFaq(index) { this.openFaq = this.openFaq === index ? null : index } }
}
</script>

<style scoped>
.accordion-enter-active, .accordion-leave-active { transition: all 0.2s ease; }
.accordion-enter-from, .accordion-leave-to { opacity: 0; max-height: 0; }
</style>
