<template>
  <AppPage>
    <section v-if="draft" class="resume-card app-card">
      <h3 class="resume-title">{{ t('matches.resume.title') }}</h3>
      <p class="resume-message">{{ t('matches.resume.message') }}</p>
      <p class="resume-score">
        {{ t('matches.resume.score', { scoreA: currentScore.scoreA, scoreB: currentScore.scoreB }) }}
      </p>
      <div class="resume-actions">
        <Button :label="t('matches.resume.continue')" icon="pi pi-play" @click="resume" />
        <Button
          :label="t('matches.resume.abandon')"
          severity="secondary"
          outlined
          @click="abandonDialog = true"
        />
      </div>
    </section>

    <Dialog
      v-model:visible="abandonDialog"
      :modal="true"
      :header="t('matches.resume.abandonTitle')"
      class="abandon-dialog"
    >
      <div class="abandon-content">
        <p>{{ t('matches.resume.abandonMessage') }}</p>
        <div class="abandon-actions">
          <Button
            :label="t('matches.resume.abandonCancel')"
            severity="secondary"
            outlined
            @click="abandonDialog = false"
          />
          <Button
            :label="t('matches.resume.abandonConfirm')"
            severity="danger"
            icon="pi pi-trash"
            @click="confirmAbandon"
          />
        </div>
      </div>
    </Dialog>

    <div class="welcome-card app-card">
      <div class="brand-row">
        <span class="logo" aria-hidden="true">🥅</span>
        <div>
          <h2 class="app-section-title">{{ t('app.title') }}</h2>
          <p class="app-section-subtitle">{{ t('home.welcome') }}</p>
        </div>
      </div>
      <p v-if="auth.user" class="connected">{{ t('home.connectedAs', { email: auth.user.email }) }}</p>
      <div v-if="auth.user && auth.user.emailVerified === false" class="verify-banner">
        <p>{{ t('auth.verify.banner') }}</p>
        <Button
          :label="t('auth.verify.resend')"
          size="small"
          outlined
          :disabled="auth.loading"
          @click="onResendVerification"
        />
        <Message v-if="verifyNotice" :severity="verifyNoticeSeverity">{{ verifyNotice }}</Message>
      </div>
    </div>

    <section class="quick-section">
      <h3 class="section-label">{{ t('home.quickActions') }}</h3>
      <div class="quick-grid">
        <button type="button" class="quick-item app-card" @click="goAddPlayer">
          <i class="pi pi-user-plus" aria-hidden="true" />
          <span>{{ t('home.actions.addPlayer') }}</span>
        </button>
        <button type="button" class="quick-item app-card" @click="goShooting">
          <i class="pi pi-bullseye" aria-hidden="true" />
          <span>{{ t('home.actions.shooting') }}</span>
        </button>
        <button type="button" class="quick-item app-card" @click="goTraining">
          <i class="pi pi-flag" aria-hidden="true" />
          <span>{{ t('home.actions.training') }}</span>
        </button>
        <button type="button" class="quick-item app-card" @click="goGuidelines">
          <i class="pi pi-book" aria-hidden="true" />
          <span>{{ t('doc.title') }}</span>
        </button>
        <button type="button" class="quick-item app-card" @click="goSettings">
          <i class="pi pi-cog" aria-hidden="true" />
          <span>{{ t('home.actions.settings') }}</span>
        </button>
        <button
          v-if="isCoach"
          type="button"
          class="quick-item app-card"
          @click="goCoach"
        >
          <i class="pi pi-users" aria-hidden="true" />
          <span>{{ t('home.actions.coach') }}</span>
        </button>
        <button
          v-if="isAdmin"
          type="button"
          class="quick-item app-card"
          @click="goAdmin"
        >
          <i class="pi pi-shield" aria-hidden="true" />
          <span>{{ t('home.actions.admin') }}</span>
        </button>
        <button type="button" class="quick-item app-card" @click="toggleLanguageMenu">
          <i class="pi pi-language" aria-hidden="true" />
          <span>{{ t('home.language') }} · {{ currentLanguage }}</span>
        </button>
        <button type="button" class="quick-item app-card quick-item--danger" @click="onLogout">
          <i class="pi pi-sign-out" aria-hidden="true" />
          <span>{{ t('home.actions.logout') }}</span>
        </button>
      </div>
      <Menu ref="languageMenu" :model="languageItems" popup />
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Menu from 'primevue/menu'
import Message from 'primevue/message'
import { useI18n } from 'vue-i18n'
import { useLocaleSwitcher } from '../composables/useLocaleSwitcher'
import { useRouter } from 'vue-router'
import AppPage from '../components/layout/AppPage.vue'
import { draftScore, useMatchDraftResume } from '../composables/useMatchDraftResume'
import { useIsAdmin } from '../composables/useIsAdmin'
import { useIsCoach } from '../composables/useIsCoach'
import { useAuthStore } from '../stores/auth'

const { t } = useI18n()
const { currentLanguage, languageItems } = useLocaleSwitcher()
const router = useRouter()
const auth = useAuthStore()
const isAdmin = useIsAdmin()
const isCoach = useIsCoach()
const { draft, resume, abandon } = useMatchDraftResume()
const verifyNotice = ref('')
const verifyNoticeSeverity = ref<'success' | 'error'>('success')
const abandonDialog = ref(false)

const currentScore = computed(() => {
  if (!draft.value) return { scoreA: 0, scoreB: 0 }
  return draftScore(draft.value)
})

/** The draft is the only copy of an ongoing match: losing it must be explicit. */
function confirmAbandon(): void {
  abandon()
  abandonDialog.value = false
}

function goAddPlayer(): void {
  router.push({ name: 'addPlayer' })
}
function goShooting(): void {
  router.push({ name: 'shootingHome' })
}
function goTraining(): void {
  router.push({ name: 'trainingHome' })
}
function goSettings(): void {
  router.push({ name: 'settings' })
}
function goAdmin(): void {
  router.push({ name: 'adminHome' })
}
function goCoach(): void {
  router.push({ name: 'coachPlayers' })
}
function goGuidelines(): void {
  router.push({ name: 'guidelines' })
}

function onLogout(): void {
  auth.logout()
  router.push({ name: 'login' })
}

async function onResendVerification(): Promise<void> {
  verifyNotice.value = ''
  const ok = await auth.resendVerification()
  if (ok) {
    verifyNoticeSeverity.value = 'success'
    verifyNotice.value = t('auth.verify.resent')
    return
  }
  verifyNoticeSeverity.value = 'error'
  verifyNotice.value = auth.lastError ? t(auth.lastError) : t('auth.errors.generic')
}

const languageMenu = ref()

function toggleLanguageMenu(event: Event) {
  languageMenu.value.toggle(event)
}
</script>

<style scoped>
.welcome-card {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-sm);
}

.brand-row {
  display: flex;
  align-items: center;
  gap: var(--app-space-md);
}

.logo {
  font-size: 1.75rem;
  line-height: 1;
}

.connected {
  margin: 0;
  font-size: 0.875rem;
  color: var(--app-text-muted);
}

.verify-banner {
  display: grid;
  gap: var(--app-space-sm);
  margin-top: var(--app-space-sm);
}

.verify-banner p {
  margin: 0;
  font-size: 0.875rem;
}

.section-label {
  margin: 0 0 var(--app-space-sm);
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-text-subtle);
}

.quick-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

.quick-item {
  min-height: 5.5rem;
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
  justify-items: center;
  align-content: center;
  text-align: center;
  border: none;
  cursor: pointer;
  font: inherit;
  color: var(--app-text);
  transition: transform 0.12s ease, box-shadow 0.12s ease;
}

.quick-item i {
  font-size: 1.25rem;
  color: var(--app-primary);
}

.quick-item span {
  font-size: 0.8125rem;
  font-weight: 600;
  line-height: 1.25;
}

.quick-item:active {
  transform: scale(0.98);
}

.quick-item--danger i {
  color: #c24141;
}

.resume-card {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
  margin-bottom: var(--app-space-sm);
  border: 1px solid var(--app-primary-border, #86efac);
  background: var(--app-primary-soft, #ecfdf3);
}

.resume-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
}

.resume-message {
  margin: 0;
  font-size: 0.875rem;
  color: var(--app-text-muted);
}

.resume-score {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.resume-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

.abandon-content {
  display: grid;
  gap: var(--app-space-md);
}

.abandon-content p {
  margin: 0;
  line-height: 1.45;
  color: var(--app-text-muted);
}

.abandon-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}
</style>
