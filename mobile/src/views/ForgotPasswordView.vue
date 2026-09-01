<template>
  <section class="auth-card app-card">
    <div class="auth-brand">
      <AppLogo centered />
      <h2>{{ t('auth.forgot.title') }}</h2>
      <p>{{ t('app.title') }}</p>
    </div>

    <form @submit.prevent="onSubmit" class="app-form">
      <Message v-if="errorMessage" severity="error">{{ errorMessage }}</Message>
      <Message v-if="success" severity="success">{{ t('auth.forgot.success') }}</Message>
      <label class="app-field">
        <span>{{ t('auth.email') }}</span>
        <InputText v-model="email" type="email" autocomplete="email" required fluid />
      </label>
      <Button type="submit" :label="t('auth.forgot.submit')" :disabled="loading" class="w-full" />
      <p class="alt">
        <router-link :to="{ name: 'login' }">{{ t('auth.forgot.back') }}</router-link>
      </p>
    </form>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Message from 'primevue/message'
import AppLogo from '../components/layout/AppLogo.vue'

const { t } = useI18n()
const auth = useAuthStore()

const email = ref('')
const success = ref(false)

const loading = computed(() => auth.loading)
const errorMessage = computed(() => (auth.lastError ? t(auth.lastError) : ''))

async function onSubmit() {
  success.value = false
  const ok = await auth.forgotPassword(email.value)
  if (ok) {
    success.value = true
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
