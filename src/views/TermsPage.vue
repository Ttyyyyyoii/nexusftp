<template>
  <DefaultLayout>
    <section class="relative pt-24 pb-16 overflow-hidden min-h-[80vh]">
      <div class="absolute inset-0 bg-gradient-to-br from-surface-0 via-primary-50/20 to-surface-0 dark:from-surface-950 dark:via-primary-900/5 dark:to-surface-950" />
      <div class="relative max-w-4xl mx-auto px-6">
        <div class="text-center mb-12">
          <h1 class="text-4xl sm:text-5xl font-bold text-surface-900 dark:text-white mb-4">{{ $t('terms.title') }}</h1>
        </div>
        <div class="glass-panel rounded-2xl p-8 sm:p-12 text-surface-700 dark:text-surface-300 leading-relaxed text-lg shadow-xl shadow-primary-500/5">
          <p>{{ $t('terms.content') }}</p>
        </div>
      </div>
    </section>
  </DefaultLayout>
</template>

<script>
import DefaultLayout from '@/layouts/DefaultLayout.vue'

export default {
  name: 'TermsPage',
  components: { DefaultLayout }
}
</script>
