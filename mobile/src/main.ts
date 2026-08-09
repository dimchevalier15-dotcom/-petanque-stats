import { createApp } from 'vue'
import { createPinia } from 'pinia'

import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'

import App from './App.vue'
import ToastService from 'primevue/toastservice'
import router from './router'
import { i18n } from './i18n'
import { useAuthStore } from './stores/auth'

import 'primeicons/primeicons.css'
import './assets/theme.css'

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)
app.use(router)
app.use(i18n)
app.use(ToastService)

// Restore session from local storage if possible
const auth = useAuthStore(pinia)
auth.initFromStorage()

app.use(PrimeVue, {
  theme: {
    preset: Aura,
  },
})

app.mount('#app')