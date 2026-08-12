<template>
  <footer class="bg-surface-50 dark:bg-surface-900 border-t border-surface-200 dark:border-surface-800">
    <div class="max-w-7xl mx-auto px-6 py-12">
      <div class="flex flex-col items-center text-center">
        <div class="flex items-center gap-3 mb-4">
          <svg width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0">
            <defs>
              <linearGradient id="footerBg" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                <stop offset="0%" stop-color="#6366f1"/>
                <stop offset="100%" stop-color="#8b5cf6"/>
              </linearGradient>
            </defs>
            <rect width="32" height="32" rx="8" fill="url(#footerBg)"/>
            <rect x="5" y="7" width="22" height="5" rx="2" fill="white" fill-opacity="0.3"/>
            <rect x="5" y="14" width="22" height="5" rx="2" fill="white" fill-opacity="0.2"/>
            <path d="M10 25 L10 19" stroke="white" stroke-width="2" stroke-linecap="round"/>
            <path d="M7 22 L10 19 L13 22" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <path d="M22 19 L22 25" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round"/>
            <path d="M19 22 L22 25 L25 22" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <circle cx="23" cy="9.5" r="1.5" fill="#34d399"/>
            <circle cx="23" cy="16.5" r="1.5" fill="#fbbf24"/>
          </svg>
          <span class="text-xl font-bold text-surface-900 dark:text-white">{{ $t('app.name') }}</span>
        </div>
        <p class="text-surface-500 dark:text-surface-400 text-sm max-w-sm mb-6">{{ $t('footer.description') || 'Enterprise-grade FTP, SFTP, and FTPS client built for professionals.' }}</p>
        <div class="flex gap-4">
          <a href="#" class="p-2 rounded-lg bg-surface-200 dark:bg-surface-800 text-surface-600 dark:text-surface-400 hover:text-primary-500"><Github class="w-5 h-5" /></a>
          <a href="#" class="p-2 rounded-lg bg-surface-200 dark:bg-surface-800 text-surface-600 dark:text-surface-400 hover:text-primary-500"><Twitter class="w-5 h-5" /></a>
        </div>
      </div>
      <div class="mt-12 pt-8 border-t border-surface-200 dark:border-surface-800 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-sm text-surface-400">&copy; {{ currentYear }} NexusFTP. {{ $t('footer.rights') || 'All rights reserved.' }}</p>
        <div class="flex gap-6">
          <router-link to="/privacy" class="text-sm text-surface-400 hover:text-surface-600">{{ $t('footer.privacy') }}</router-link>
          <router-link to="/terms" class="text-sm text-surface-400 hover:text-surface-600">{{ $t('footer.terms') }}</router-link>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
import { HardDrive, Github, Twitter } from 'lucide-vue-next'
export default {
  name: 'AppFooter',
  components: { HardDrive, Github, Twitter },
  computed: { currentYear() { return new Date().getFullYear() } }
}
</script>
