import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import router from './router'
import App from './App.vue'
import './styles/global.css'

import en from './i18n/en.json'
import fr from './i18n/fr.json'

const browserLang = navigator.language || navigator.userLanguage || 'fr'
const defaultLang = browserLang.toLowerCase().startsWith('en') ? 'en' : 'fr'

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('locale') || defaultLang,
  fallbackLocale: 'fr',
  messages: { en, fr }
})

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(i18n)

app.mount('#app')
