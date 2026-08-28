import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ForgotPasswordView from '../views/ForgotPasswordView.vue'
import ResetPasswordView from '../views/ResetPasswordView.vue'
import NewMatchView from '../views/NewMatchView.vue'
import AddPlayerView from '../views/AddPlayerView.vue'
import MatchHistoryView from '../views/MatchHistoryView.vue'
import GuidelinessView from "../views/GuidelinessView.vue";
import MyStatsView from '../views/MyStatsView.vue'
import AccountSettingsView from '../views/AccountSettingsView.vue'
import ShootingHomeView from '../views/ShootingHomeView.vue'
import TrainingHomeView from '../views/TrainingHomeView.vue'
import PrivacyView from '../views/PrivacyView.vue'
import TermsView from '../views/TermsView.vue'
import LegalNoticeView from '../views/LegalNoticeView.vue'
import DeleteAccountView from '../views/DeleteAccountView.vue'
import { GUEST_ONLY_ROUTE_NAMES, LEGAL_PATHS, PUBLIC_ROUTE_NAMES } from './publicRoutes'
import { userHasMasterAccess } from '../models/UserRole'
import { userIsCoach } from '../composables/useIsCoach'
import { useAuthStore } from '../stores/auth'

const routes: RouteRecordRaw[] = [
  { path: '/', redirect: { name: 'home' } },
  { path: '/home', name: 'home', component: HomeView, meta: { layout: 'main' } },
  { path: '/login', name: 'login', component: LoginView, meta: { layout: 'auth' } },
  { path: '/register', name: 'register', component: RegisterView, meta: { layout: 'auth' } },
  { path: '/forgot-password', name: 'forgotPassword', component: ForgotPasswordView, meta: { layout: 'auth' } },
  { path: '/reset-password', name: 'resetPassword', component: ResetPasswordView, meta: { layout: 'auth' } },
  { path: LEGAL_PATHS.privacy, name: 'privacy', component: PrivacyView, meta: { layout: 'focus' } },
  { path: LEGAL_PATHS.terms, name: 'terms', component: TermsView, meta: { layout: 'focus' } },
  { path: LEGAL_PATHS.legal, name: 'legal', component: LegalNoticeView, meta: { layout: 'focus' } },
  { path: LEGAL_PATHS.deleteAccount, name: 'deleteAccount', component: DeleteAccountView, meta: { layout: 'focus' } },
  { path: '/settings', name: 'settings', component: AccountSettingsView, meta: { layout: 'focus' } },
  {
    path: '/admin',
    name: 'adminHome',
    component: () => import('../views/AdminHomeView.vue'),
    meta: { layout: 'focus', requiresAdmin: true },
  },
  {
    path: '/admin/impersonate',
    name: 'adminImpersonate',
    component: () => import('../views/AdminImpersonateView.vue'),
    meta: { layout: 'focus', requiresAdmin: true },
  },
  {
    path: '/admin/competitions',
    name: 'adminCompetitions',
    component: () => import('../views/AdminCompetitionsView.vue'),
    meta: { layout: 'focus', requiresAdmin: true },
  },
  {
    path: '/admin/clubs',
    name: 'adminClubs',
    component: () => import('../views/AdminClubsView.vue'),
    meta: { layout: 'focus', requiresAdmin: true },
  },
  {
    path: '/admin/coach',
    name: 'adminCoach',
    component: () => import('../views/AdminCoachView.vue'),
    meta: { layout: 'focus', requiresAdmin: true },
  },
  {
    path: '/coach',
    name: 'coachPlayers',
    component: () => import('../views/CoachPlayersView.vue'),
    meta: { layout: 'focus', requiresCoach: true },
  },
  {
    path: '/coach/players/:id',
    name: 'coachPlayer',
    component: () => import('../views/CoachPlayerDetailView.vue'),
    meta: { layout: 'focus', requiresCoach: true },
  },
  {
    path: '/coach/players/:id/history',
    name: 'coachPlayerHistory',
    component: () => import('../views/CoachPlayerHistoryView.vue'),
    meta: { layout: 'focus', requiresCoach: true },
  },
  { path: '/match/new', name: 'newMatch', component: NewMatchView, meta: { layout: 'focus' } },
  { path: '/players/new', name: 'addPlayer', component: AddPlayerView, meta: { layout: 'focus' } },
  { path: '/matches/history', name: 'matchHistory', component: MatchHistoryView, meta: { layout: 'main' } },
  { path: '/matches/guidelines', name: 'guidelines', component: GuidelinessView, meta: { layout: 'focus' } },
  { path: '/matches/:id/score', name: 'matchScore', component: () => import('../views/MatchPlayView.vue'), meta: { layout: 'play' } },
  { path: '/live/:uuid', name: 'liveMatch', component: () => import('../views/LiveMatchView.vue'), meta: { layout: 'focus' } },
  { path: '/matches/:id/summary', name: 'matchSummary', component: () => import('../views/MatchSummaryView.vue'), meta: { layout: 'focus' } },
  { path: '/matches/:id/context', name: 'matchContext', component: () => import('../views/MatchContextView.vue'), meta: { layout: 'focus' } },
  { path: '/stats', name: 'myStats', component: MyStatsView, meta: { layout: 'main' } },
  { path: '/shooting', name: 'shootingHome', component: ShootingHomeView, meta: { layout: 'main' } },
  { path: '/shooting/stats', name: 'shootingStats', component: () => import('../views/ShootingStatsView.vue'), meta: { layout: 'main' } },
  { path: '/shooting/:id', name: 'shootingSession', component: () => import('../views/ShootingSessionView.vue'), meta: { layout: 'play' } },
  { path: '/shooting/:id/summary', name: 'shootingSessionSummary', component: () => import('../views/ShootingSessionSummaryView.vue'), meta: { layout: 'focus' } },
  { path: '/shooting/:id/context', name: 'shootingSessionContext', component: () => import('../views/ShootingSessionContextView.vue'), meta: { layout: 'focus' } },
  { path: '/training', name: 'trainingHome', component: TrainingHomeView, meta: { layout: 'main' } },
  { path: '/training/stats', name: 'trainingStats', component: () => import('../views/TrainingStatsView.vue'), meta: { layout: 'main' } },
  { path: '/training/:id', name: 'trainingSession', component: () => import('../views/TrainingSessionView.vue'), meta: { layout: 'play' } },
  { path: '/training/:id/summary', name: 'trainingSessionSummary', component: () => import('../views/TrainingSessionSummaryView.vue'), meta: { layout: 'focus' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

const publicRouteNames = new Set<string>(PUBLIC_ROUTE_NAMES)
const guestOnlyRouteNames = new Set<string>(GUEST_ONLY_ROUTE_NAMES)

router.beforeEach(async (to) => {
  const isPublic = to.name && publicRouteNames.has(String(to.name))
  const isGuestOnly = to.name && guestOnlyRouteNames.has(String(to.name))
  const token = localStorage.getItem('auth_token')
  if (!token && !isPublic) {
    return { name: 'login' }
  }
  if (token && isGuestOnly) {
    return { name: 'home' }
  }
  if (to.meta.requiresAdmin) {
    const auth = useAuthStore()
    if (!auth.user && token) {
      await auth.initFromStorage()
    }
    if (!userHasMasterAccess(auth.user)) {
      return { name: 'home' }
    }
  }
  if (to.meta.requiresCoach) {
    const auth = useAuthStore()
    if (!auth.user && token) {
      await auth.initFromStorage()
    }
    if (!userIsCoach(auth.user)) {
      return { name: 'home' }
    }
  }
  return true
})

export default router
