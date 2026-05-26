import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router/index.js'
import { useAuthStore } from './stores/auth.js'
import './style.css'

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)

// Auth must be hydrated BEFORE the router runs its navigation guards.
useAuthStore().initFromStorage()

app.use(router)
app.mount('#app')
