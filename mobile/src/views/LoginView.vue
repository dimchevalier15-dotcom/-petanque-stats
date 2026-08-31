<template>
  <section class="auth-card app-card">
    <div class="auth-brand">
      <span class="logo" aria-hidden="true">🥅</span>
      <h2>{{ t('auth.login.title') }}</h2>
      <p>{{ t('app.title') }}</p>
    </div>

    <form @submit.prevent="onSubmit" class="app-form">
      <Message v-if="errorMessage" severity="error">{{ errorMessage }}</Message>
      <label class="app-field">
        <span>{{ t('auth.email') }}</span>
        <InputText v-model="email" type="email" autocomplete="email" required fluid />
      </label>
      <label class="app-field">
        <span>{{ t('auth.password') }}</span>
        <Password v-model="password" :feedback="false" toggleMask autocomplete="current-password" fluid />
      </label>
      <p class="forgot-row">
        <router-link :to="{ name: 'forgotPassword' }">{{ t('auth.forgot.link') }}</router-link>
      </p>
      <Button type="submit" :label="t('auth.login.submit')" :disabled="loading" class="w-full" />
      <p class="alt">
        {{ t('auth.noAccount') }}
        <router-link :to="registerLink">{{ t('auth.register.link') }}</router-link>
      </p>
      <div class="guest-entry">
        <p class="guest-entry-label">{{ t('guest.entry.prompt') }}</p>
        <Button
          type="button"
          :label="t('guest.entry.playWithoutAccount')"
          severity="secondary"
          outlined
          class="w-full"
          icon="pi pi-play"
          @click="playWithoutAccount"
        />
      </div>
      <AuthLegalNotice />
    </form>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useGuestStore } from '../stores/guest'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import AuthLegalNotice from '../components/legal/AuthLegalNotice.vue'
import { routeAfterGuestAuth } from '../composables/useGuestMatchConversion'
import {
  hasSaveGuestMatchQuery,
  saveGuestMatchQuery,
} from '../utils/guestMatchQuery'
import { isSafeInternalPath } from '../utils/internalRedirect'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const guest = useGuestStore()

const email = ref('')
const password = ref('')

const loading = computed(() => auth.loading)
const errorMessage = computed(() => (auth.lastError ? t(auth.lastError) : ''))
const registerLink = computed(() => ({
  name: 'register',
  query: hasSaveGuestMatchQuery(route.query.saveGuestMatch) ? saveGuestMatchQuery() : {},
}))

async function onSubmit() {
  await auth.login(email.value, password.value)
  if (!auth.isAuthenticated) {
    return
  }

  if (hasSaveGuestMatchQuery(route.query.saveGuestMatch) && auth.user?.id) {
    routeAfterGuestAuth(router, auth.user.id)
    return
  }

  const redirect = route.query.redirect
  router.push(isSafeInternalPath(redirect) ? redirect : { name: 'home' })
}

function playWithoutAccount(): void {
  guest.enterGuestMode()
  router.push({ name: 'newMatch' })
}
</script>

<style scoped>
.auth-card {
  width: 100%;
  max-width: 420px;
  padding: var(--app-space-xl) var(--app-space-lg);
}

.auth-brand {
  text-align: center;
  margin-bottom: var(--app-space-lg);
}

.auth-brand .logo {
  font-size: 2.5rem;
  display: block;
  margin-bottom: var(--app-space-sm);
}

.auth-brand h2 {
  margin: 0;
  font-size: 1.375rem;
}

.auth-brand p {
  margin: 0.25rem 0 0;
  color: var(--app-text-muted);
  font-size: 0.875rem;
}

.forgot-row {
  margin: calc(var(--app-space-sm) * -1) 0 0;
  text-align: right;
  font-size: 0.875rem;
}

.alt {
  margin: 0;
  font-size: 0.875rem;
  text-align: center;
  color: var(--app-text-muted);
}

.guest-entry {
  display: grid;
  gap: var(--app-space-sm);
  padding-top: var(--app-space-md);
  border-top: 1px solid var(--app-border);
}

.guest-entry-label {
  margin: 0;
  font-size: 0.875rem;
  text-align: center;
  color: var(--app-text-muted);
}

.w-full {
  width: 100%;
}
</style>
