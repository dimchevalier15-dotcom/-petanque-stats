import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'

const routes: RouteRecordRaw[] = [
  { path: '/', redirect: { name: 'home' } },
  { path: '/home', name: 'home', component: HomeView },
  { path: '/login', name: 'login', component: LoginView },
  { path: '/register', name: 'register', component: RegisterView },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

const publicRouteNames = new Set(['login', 'register'])

router.beforeEach((to) => {
  const isPublic = to.name && publicRouteNames.has(String(to.name))
  const token = localStorage.getItem('auth_token')
  if (!token && !isPublic) {
    return { name: 'login' }
  }
  if (token && isPublic) {
    return { name: 'home' }
  }
  return true
})

export default router
