<template>
  <DefaultLayout>
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-16">
      <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-br from-surface-0 via-primary-50/30 to-surface-0 dark:from-surface-950 dark:via-primary-900/10 dark:to-surface-950" />
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary-400/20 rounded-full blur-3xl animate-pulse" />
        <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-violet-400/15 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s" />
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
          <div v-for="i in 20" :key="i" class="absolute w-1 h-1 bg-primary-400/40 rounded-full" :style="{ left: `${(i*5)%100}%`, top: `${(i*7)%100}%`, animation: `floatParticle ${6+(i%8)}s linear infinite`, animationDelay: `${i%5}s` }" />
        </div>
      </div>
      <div class="relative z-10 max-w-6xl mx-auto px-6 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 mb-8">
          <Sparkles class="w-4 h-4 text-primary-500" />
          <span class="text-sm font-medium text-primary-700 dark:text-primary-300">Now with SFTP & FTPS Support</span>
        </div>
        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold tracking-tight mb-6">
          <span class="text-surface-900 dark:text-white">{{ $t('home.hero.title') }}</span><br />
          <span class="bg-gradient-to-r from-primary-500 via-violet-500 to-primary-600 bg-clip-text text-transparent">{{ $t('home.hero.titleHighlight') }}</span>
        </h1>
        <p class="text-lg sm:text-xl text-surface-500 dark:text-surface-400 max-w-2xl mx-auto mb-10 leading-relaxed">{{ $t('home.hero.subtitle') }}</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
          <router-link to="/connect" class="btn-primary flex items-center gap-2 text-lg px-8 py-4">
            <Zap class="w-5 h-5" /> {{ $t('home.hero.ctaPrimary') }}
          </router-link>
          <button class="btn-secondary flex items-center gap-2 text-lg px-8 py-4">
            <Play class="w-5 h-5" /> {{ $t('home.hero.ctaSecondary') }}
          </button>
        </div>
        <div class="relative max-w-5xl mx-auto">
          <div class="absolute -inset-1 bg-gradient-to-r from-primary-500 to-violet-500 rounded-2xl blur opacity-20" />
          <div class="relative bg-surface-900 rounded-2xl border border-surface-700 shadow-premium overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-3 border-b border-surface-700 bg-surface-800/50">
              <div class="flex gap-1.5">
                <div class="w-3 h-3 rounded-full bg-rose-500" />
                <div class="w-3 h-3 rounded-full bg-amber-500" />
                <div class="w-3 h-3 rounded-full bg-emerald-500" />
              </div>
              <div class="flex-1 flex justify-center">
                <div class="px-4 py-1 rounded-lg bg-surface-700 text-xs text-surface-400 font-mono">NexusFTP - Connected to server.example.com</div>
              </div>
            </div>
            <div class="flex h-64">
              <div class="w-1/2 border-r border-surface-700 p-4">
                <div class="text-xs text-surface-500 mb-3 font-semibold uppercase tracking-wider">Local Files</div>
                <div class="space-y-2">
                  <div v-for="i in 5" :key="i" class="flex items-center gap-3">
                    <Folder class="w-4 h-4 text-primary-400" />
                    <div class="h-2.5 bg-surface-700 rounded flex-1" :style="{ width: `${60 + (i*5)}%` }" />
                  </div>
                </div>
              </div>
              <div class="w-1/2 p-4">
                <div class="text-xs text-surface-500 mb-3 font-semibold uppercase tracking-wider">Remote Files</div>
                <div class="space-y-2">
                  <div v-for="i in 5" :key="i" class="flex items-center gap-3">
                    <FileText class="w-4 h-4 text-amber-400" />
                    <div class="h-2.5 bg-surface-700 rounded flex-1" :style="{ width: `${50 + (i*8)}%` }" />
                  </div>
                </div>
              </div>
            </div>
            <div class="px-4 py-3 border-t border-surface-700 bg-surface-800/50">
              <div class="flex items-center gap-3">
                <div class="flex-1 h-1.5 bg-surface-700 rounded-full overflow-hidden">
                  <div class="h-full w-2/3 bg-gradient-to-r from-primary-500 to-emerald-500 rounded-full" />
                </div>
                <span class="text-xs text-surface-400 font-mono">67%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="py-20 relative">
      <div class="max-w-6xl mx-auto px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div v-for="(stat, index) in stats" :key="index" class="glass-panel rounded-2xl p-6 text-center group hover:shadow-glow transition-all">
            <div class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-primary-500 to-violet-500 bg-clip-text text-transparent mb-2">{{ stat.value }}</div>
            <div class="text-sm text-surface-500 dark:text-surface-400">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <section class="py-24 relative">
      <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="text-3xl sm:text-4xl font-bold text-surface-900 dark:text-white mb-4">{{ $t('home.features.title') }}</h2>
          <p class="text-lg text-surface-500 dark:text-surface-400 max-w-xl mx-auto">{{ $t('home.features.subtitle') }}</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="(feature, index) in features" :key="index" class="group glass-panel rounded-2xl p-6 hover:shadow-glow transition-all border border-surface-200/50 dark:border-surface-700/50">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-500/10 to-violet-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <component :is="feature.icon" class="w-6 h-6 text-primary-500" />
            </div>
            <h3 class="text-lg font-semibold text-surface-900 dark:text-white mb-2">{{ feature.title }}</h3>
            <p class="text-sm text-surface-500 dark:text-surface-400 leading-relaxed">{{ feature.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="py-24 relative">
      <div class="absolute inset-0 bg-gradient-to-br from-primary-500/5 to-violet-500/5" />
      <div class="relative max-w-4xl mx-auto px-6 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold text-surface-900 dark:text-white mb-6">Ready to Transform Your Workflow?</h2>
        <p class="text-lg text-surface-500 dark:text-surface-400 mb-8 max-w-xl mx-auto">Join thousands of professionals who trust NexusFTP for their critical file transfers.</p>
        <router-link to="/connect" class="btn-primary inline-flex items-center gap-2 text-lg px-8 py-4">
          <Rocket class="w-5 h-5" /> Get Started Now
        </router-link>
      </div>
    </section>
  </DefaultLayout>
</template>

<script>
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { Sparkles, Zap, Play, Folder, FileText, Rocket, Shield, Gauge, Lock, Monitor, RefreshCw, Terminal } from 'lucide-vue-next'
export default {
  name: 'HomePage',
  components: { DefaultLayout, Sparkles, Zap, Play, Folder, FileText, Rocket, Shield, Gauge, Lock, Monitor, RefreshCw, Terminal },
  data() {
    return {
      stats: [{ value: '50K+', label: this.$t('home.stats.users') }, { value: '10M+', label: this.$t('home.stats.transfers') }, { value: '25K+', label: this.$t('home.stats.servers') }, { value: '99.9%', label: this.$t('home.stats.uptime') }],
      features: [
        { icon: 'Monitor', title: this.$t('home.features.ftp.title'), desc: this.$t('home.features.ftp.desc') },
        { icon: 'Gauge', title: this.$t('home.features.speed.title'), desc: this.$t('home.features.speed.desc') },
        { icon: 'Shield', title: this.$t('home.features.security.title'), desc: this.$t('home.features.security.desc') },
        { icon: 'Monitor', title: this.$t('home.features.interface.title'), desc: this.$t('home.features.interface.desc') },
        { icon: 'RefreshCw', title: this.$t('home.features.sync.title'), desc: this.$t('home.features.sync.desc') },
        { icon: 'Terminal', title: this.$t('home.features.terminal.title'), desc: this.$t('home.features.terminal.desc') }
      ]
    }
  }
}
</script>
