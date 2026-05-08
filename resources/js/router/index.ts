import { createRouter, createWebHistory } from 'vue-router'
import App2 from '../App2.vue'  // <--- Ваш основной компонент
import Register from '../views/register.vue'
import Profile from '../views/Profile.vue'
import SignIn from '../views/signIn.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: App2,  
    },
    {
      path: '/profile',
      name: 'profile',
      component: Profile,
    },
    {
      path: '/register',
      name: 'register',
      component: Register,
    },
    {
      path: '/signin',
      name: 'signIn',
      component: SignIn,
    },
  ],
})

export default router