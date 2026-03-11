import { createApp } from 'vue'
import App from './App.vue'

const app = createApp(App)

app.mixin({ methods: { t, n } })

app.mount('#filedrop')
