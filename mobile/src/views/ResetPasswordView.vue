<template>
  <section class="auth-card app-card">
    <div class="auth-brand">
      <AppLogo centered />
      <h2>{{ t('auth.reset.title') }}</h2>
      <p>{{ t('app.title') }}</p>
    </div>

    <form v-if="!success" @submit.prevent="onSubmit" class="app-form">
      <Message v-if="errorMessage" severity="error">{{ errorMessage }}</Message>
      <label class="app-field">
        <span>{{ t('auth.reset.password') }}</span>
        <Password v-model="password" :feedback="false" toggleMask autocomplete="new-password" fluid />
      </label>
      <label class="app-field">
        <span>{{ t('auth.reset.confirm') }}</span>
        <Password v-model="confirm" :feedback="false" toggleMask autocomplete="new-password" fluid />
      </label>
      <Button type="submit" :label="t('auth.reset.submit')" :disabled="loading || !token" class="w-full" />
      <p class="alt">
        <router-link :to="{ name: 'login' }">{{ t('auth.login.link') }}</router-link>
      </p>
    </form>

    <div v-else class="app-form">
      <Message severity="success">{{ t('auth.reset.success') }}</Message>
      <Button :label="t('auth.login.link')" class="w-full" @click="goLogin" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import AppLogo from '../components/layout/AppLogo.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const token = computed(() => {
  const value = route.query.token
  return typeof value === 'string' ? value : ''
})
const password = ref('')
const confirm = ref('')
const success = ref(false)
const localError = ref('')

const loading = computed(() => auth.loading)
const errorMessage = computed(() => {
  if (localError.value) {
    return t(localError.value)
  }
  return auth.lastError ? t(auth.lastError) : ''
})

async function onSubmit() {
  localError.value = ''
  if (!token.value) {
    localError.value = 'auth.reset.invalidToken'
    return
  }
  if (password.value.length < 8) {
    localError.value = 'auth.validations.passwordMinLength'
    return
  }
  if (password.value !== confirm.value) {
    localError.value = 'auth.reset.mismatch'
    return
  }
  const ok = await auth.resetPassword(token.value, password.value)
  if (ok) {
    success.value = true
  }
}

function goLogin() {
  router.push({ name: 'login' })
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

.auth-brand h2 {
  margin: 0;
  font-size: 1.375rem;
}

.auth-brand p {
  margin: 0.25rem 0 0;
  color: var(--app-text-muted);
  font-size: 0.875rem;
}

.alt {
  margin: 0;
  font-size: 0.875rem;
  text-align: center;
  color: var(--app-text-muted);
}

.w-full {
  width: 100%;
}
</style>
