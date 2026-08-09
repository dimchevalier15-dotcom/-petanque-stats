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
      <Button type="submit" :label="t('auth.login.submit')" :disabled="loading" class="w-full" />
      <p class="alt">
        {{ t('auth.noAccount') }}
        <router-link :to="{ name: 'register' }">{{ t('auth.register.link') }}</router-link>
      </p>
    </form>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')

const loading = computed(() => auth.loading)
const errorMessage = computed(() => (auth.lastError ? t(auth.lastError) : ''))

async function onSubmit() {
  await auth.login(email.value, password.value)
  if (auth.isAuthenticated) {
    router.push({ name: 'home' })
  }
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
