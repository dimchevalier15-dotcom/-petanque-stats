<template>
  <section class="auth">
    <h2>{{ t('auth.login.title') }}</h2>
    <form @submit.prevent="onSubmit" class="form">
      <Message v-if="errorMessage" severity="error">{{ errorMessage }}</Message>
      <label class="field">
        <span>{{ t('auth.email') }}</span>
        <InputText v-model="email" type="email" autocomplete="email" required />
      </label>
      <label class="field">
        <span>{{ t('auth.password') }}</span>
        <Password v-model="password" :feedback="false" toggleMask autocomplete="current-password" />
      </label>
      <Button type="submit" :label="t('auth.login.submit')" :disabled="loading" />
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
.auth { max-width: 420px; margin: 2rem auto; display: grid; gap: 1rem; }
.form { display: grid; gap: 1rem; }
.field { display: grid; gap: 0.25rem; }
.alt { margin: 0; font-size: 0.9rem; }
</style>
