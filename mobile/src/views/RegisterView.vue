<template>
  <section class="auth">
    <h2>{{ t('auth.register.title') }}</h2>
    <form @submit.prevent="onSubmit" class="form">
      <label class="field">
        <span>{{ t('auth.email') }}</span>
        <InputText v-model="email" type="email" autocomplete="email" required />
      </label>
      <label class="field">
        <span>{{ t('auth.password') }}</span>
        <Password v-model="password" :feedback="false" toggleMask autocomplete="new-password" />
      </label>
      <Button type="submit" :label="t('auth.register.submit')" :disabled="loading" />
      <p class="alt">
        {{ t('auth.haveAccount') }}
        <router-link :to="{ name: 'login' }">{{ t('auth.login.link') }}</router-link>
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

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')

const loading = computed(() => auth.loading)

async function onSubmit() {
  await auth.register(email.value, password.value)
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
