<template>
  <footer class="bg-surface-50 dark:bg-surface-900 border-t border-surface-200 dark:border-surface-800">
    <div class="max-w-7xl mx-auto px-6 py-12">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <div class="col-span-1 md:col-span-2">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-glow">
              <HardDrive class="w-5 h-5 text-white" />
            </div>
            <span class="text-xl font-bold text-surface-900 dark:text-white">{{ $t('app.name') }}</span>
          </div>
          <p class="text-surface-500 dark:text-surface-400 text-sm max-w-sm mb-6">Enterprise-grade FTP, SFTP, and FTPS client built for professionals.</p>
          <div class="flex gap-4">
            <a href="#" class="p-2 rounded-lg bg-surface-200 dark:bg-surface-800 text-surface-600 dark:text-surface-400 hover:text-primary-500"><Github class="w-5 h-5" /></a>
            <a href="#" class="p-2 rounded-lg bg-surface-200 dark:bg-surface-800 text-surface-600 dark:text-surface-400 hover:text-primary-500"><Twitter class="w-5 h-5" /></a>
          </div>
        </div>
        <div>
          <h3 class="font-semibold text-surface-900 dark:text-white mb-4">Product</h3>
          <ul class="space-y-3">
            <li><router-link to="/" class="text-sm text-surface-500 hover:text-primary-500">Home</router-link></li>
            <li><router-link to="/connect" class="text-sm text-surface-500 hover:text-primary-500">Connect</router-link></li>
            <li><router-link to="/faq" class="text-sm text-surface-500 hover:text-primary-500">FAQ</router-link></li>
          </ul>
        </div>
        <div>
          <h3 class="font-semibold text-surface-900 dark:text-white mb-4">Resources</h3>
          <ul class="space-y-3">
            <li><a href="#" class="text-sm text-surface-500 hover:text-primary-500">Documentation</a></li>
            <li><a href="#" class="text-sm text-surface-500 hover:text-primary-500">API Reference</a></li>
          </ul>
        </div>
      </div>
      <div class="mt-12 pt-8 border-t border-surface-200 dark:border-surface-800 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-sm text-surface-400">&copy; {{ currentYear }} NexusFTP. All rights reserved.</p>
        <div class="flex gap-6">
          <a href="#" class="text-sm text-surface-400 hover:text-surface-600">Privacy</a>
          <a href="#" class="text-sm text-surface-400 hover:text-surface-600">Terms</a>
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
