<template>
  <section class="home">
    <header class="brand">
      <span class="logo" aria-hidden="true">🏆</span>
      <h2 class="app-name">{{ t('app.title') }}</h2>
    </header>

    <p class="welcome">{{ t('home.welcome') }}</p>
    <p v-if="auth.user" class="connected">{{ t('home.connectedAs', { email: auth.user.email }) }}</p>

    <nav class="actions" :aria-label="t('home.welcome')">
      <Button
        class="action primary"
        :label="t('home.actions.newMatch')"
        icon="pi pi-plus"
        iconPos="left"
        @click="goNewMatch"
      />

      <Button
        class="action"
        :label="t('home.actions.addPlayer')"
        icon="pi pi-user-plus"
        iconPos="left"
        @click="goAddPlayer"
      />

      <Button
        class="action"
        :label="t('home.actions.history')"
        icon="pi pi-history"
        iconPos="left"
        @click="goHistory"
      />

      <div class="action with-badge">
        <Button
          class="w-full"
          :label="t('home.actions.myStats')"
          icon="pi pi-chart-line"
          iconPos="left"
          disabled
        />
        <Tag class="badge" severity="secondary" :value="t('common.comingSoon')" />
      </div>

      <div class="action with-badge">
        <Button
          class="w-full"
          :label="t('home.actions.settings')"
          icon="pi pi-cog"
          iconPos="left"
          disabled
        />
        <Tag class="badge" severity="secondary" :value="t('common.comingSoon')" />
      </div>

      <Button
        class="action"
        severity="secondary"
        outlined
        :label="t('home.actions.logout')"
        icon="pi pi-sign-out"
        iconPos="left"
        @click="onLogout"
      />
    </nav>
  </section>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { useAuthStore } from '../stores/auth'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

function goNewMatch(): void {
  router.push({ name: 'newMatch' })
}
function goAddPlayer(): void {
  router.push({ name: 'addPlayer' })
}
function goHistory(): void {
  router.push({ name: 'matchHistory' })
}

function onLogout(): void {
  auth.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.home { max-width: 480px; margin: 1rem auto 2rem; display: grid; gap: 1rem; }
.brand { display: flex; align-items: center; gap: 0.5rem; }
.logo { font-size: 1.5rem; }
.app-name { margin: 0; font-size: 1.25rem; }
.welcome { margin: 0.25rem 0 0.25rem; opacity: 0.75; }
.connected { margin: 0 0 0.75rem; font-size: 0.95rem; opacity: 0.85; }
.actions { display: grid; gap: 0.75rem; }
.action.with-badge { position: relative; }
.badge { position: absolute; top: -0.5rem; right: -0.5rem; }
</style>
