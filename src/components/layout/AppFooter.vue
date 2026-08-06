<template>
  <footer class="bg-surface-50 dark:bg-surface-900 border-t border-surface-200 dark:border-surface-800">
    <div class="max-w-7xl mx-auto px-6 py-12">
      <div class="flex flex-col items-center text-center">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-glow">
            <HardDrive class="w-5 h-5 text-white" />
          </div>
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
