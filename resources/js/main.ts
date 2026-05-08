import '../assets/main.css'
import '../css/app.css'
import { createApp } from 'vue'
import App from './App.vue'  
import router from './router'
import Antd from 'ant-design-vue'
import 'ant-design-vue/dist/reset.css'
import { createPinia } from 'pinia'
import { initializeApp } from '@firebase/app'

const firebaseConfig = {
  apiKey: 'AIzaSyDnXXJ1R-lkoheA8LEJuHLzy2kjUvcC4-w',
  authDomain: 'myproject-35bc3.firebaseapp.com',
  projectId: 'myproject-35bc3',
  storageBucket: 'myproject-35bc3.firebasestorage.app',
  messagingSenderId: '1064017138140',
  appId: '1:1064017138140:web:b294ccd4f2b9c9762abf19',
}

initializeApp(firebaseConfig)

const app = createApp(App)
app.use(router)
app.use(Antd)
app.mount('#app')