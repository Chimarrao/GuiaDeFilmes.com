import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'

// Bulma CSS
import 'bulma/css/bulma.min.css'
// Font Awesome
import '@fortawesome/fontawesome-free/css/all.min.css'
// Custom styles
import './assets/style.css'

const app = createApp(App)
app.use(router)
app.use(createPinia())
app.mount('#app')