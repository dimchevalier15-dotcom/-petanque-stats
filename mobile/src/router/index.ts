import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import NewMatchView from '../views/NewMatchView.vue'
import AddPlayerView from '../views/AddPlayerView.vue'
import MatchHistoryView from '../views/MatchHistoryView.vue'
import GuidelinessView from "../views/GuidelinessView.vue";
import MyStatsView from '../views/MyStatsView.vue'
import AccountSettingsView from '../views/AccountSettingsView.vue'

const routes: RouteRecordRaw[] = [
  { path: '/', redirect: { name: 'home' } },
  { path: '/home', name: 'home', component: HomeView, meta: { layout: 'main' } },
  { path: '/login', name: 'login', component: LoginView, meta: { layout: 'auth' } },
  { path: '/register', name: 'register', component: RegisterView, meta: { layout: 'auth' } },
  { path: '/settings', name: 'settings', component: AccountSettingsView, meta: { layout: 'focus' } },
  { path: '/match/new', name: 'newMatch', component: NewMatchView, meta: { layout: 'focus' } },
  { path: '/players/new', name: 'addPlayer', component: AddPlayerView, meta: { layout: 'focus' } },
  { path: '/matches/history', name: 'matchHistory', component: MatchHistoryView, meta: { layout: 'main' } },
  { path: '/matches/guidelines', name: 'guidelines', component: GuidelinessView, meta: { layout: 'focus' } },
  { path: '/matches/:id/score', name: 'matchScore', component: () => import('../views/MatchPlayView.vue'), meta: { layout: 'play' } },
  { path: '/matches/:id/summary', name: 'matchSummary', component: () => import('../views/MatchSummaryView.vue'), meta: { layout: 'focus' } },
  { path: '/matches/:id/context', name: 'matchContext', component: () => import('../views/MatchContextView.vue'), meta: { layout: 'focus' } },
  { path: '/stats', name: 'myStats', component: MyStatsView, meta: { layout: 'main' } },
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
