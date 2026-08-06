import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/HomePage.vue')
  },
  {
    path: '/connect',
    name: 'Connect',
    component: () => import('@/views/ConnectPage.vue')
  },
  {
    path: '/files',
    name: 'Files',
    component: () => import('@/views/FilesPage.vue')
  },
  {
    path: '/history',
    name: 'History',
    component: () => import('@/views/HistoryPage.vue')
  },
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('@/views/SettingsPage.vue')
  },
  {
    path: '/log',
    name: 'Log',
    component: () => import('@/views/LogPage.vue')
  },
  {
    path: '/favorites',
    name: 'Favorites',
    component: () => import('@/views/FavoritesPage.vue')
  },
  {
    path: '/faq',
    name: 'FAQ',
    component: () => import('@/views/FAQPage.vue')
  },
  {
    path: '/contact',
    name: 'Contact',
    component: () => import('@/views/ContactPage.vue')
  },
  {
    path: '/docs',
    name: 'Docs',
    component: () => import('@/views/DocsPage.vue')
  },
  {
    path: '/privacy',
    name: 'Privacy',
    component: () => import('@/views/PrivacyPage.vue')
  },
  {
    path: '/terms',
    name: 'Terms',
    component: () => import('@/views/TermsPage.vue')
  },
  {
    path: '/guest/:token',
    name: 'Guest',
    component: () => import('@/views/GuestPage.vue')
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/DashboardPage.vue')
  },
  {
    path: '/terminal',
    name: 'Terminal',
    component: () => import('@/views/TerminalPage.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0, behavior: 'smooth' }
  }
})

router.afterEach((to) => {
  const baseTitle = 'NexusFTP - Client FTP & SFTP Premium En Ligne'
  if (to.name && to.name !== 'Home') {
    document.title = `${to.name.toString()} | ${baseTitle}`
  } else {
    document.title = baseTitle
  }
})

export default router
