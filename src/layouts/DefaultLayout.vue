<template>
  <div class="min-h-screen bg-surface-0 dark:bg-surface-950 text-surface-700 dark:text-surface-300">
    <AppNavbar />
    <main>
      <slot />
    </main>
    <AppFooter />
    <ToastContainer />
  </div>
</template>

<script>
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'

export default {
  name: 'DefaultLayout',
  components: { AppNavbar, AppFooter, ToastContainer }
}
</script>
