<template>
  <section class="auth-card app-card">
    <div class="auth-brand">
      <span class="logo" aria-hidden="true">🥅</span>
      <h2>{{ t('auth.register.title') }}</h2>
      <p>{{ t('app.title') }}</p>
    </div>

    <form @submit.prevent="onSubmit" class="app-form" novalidate>
      <Message v-if="errorMessage" severity="error">{{ errorMessage }}</Message>

      <label class="app-field">
        <span>{{ t('auth.email') }}</span>
        <InputText
          v-model="email"
          type="email"
          autocomplete="email"
          :invalid="!!errors.email"
          fluid
        />
        <small v-if="errors.email" class="field-error">{{ errors.email }}</small>
      </label>

      <label class="app-field">
        <span>{{ t('auth.password') }}</span>
        <Password
          v-model="password"
          :feedback="false"
          toggleMask
          autocomplete="new-password"
          :invalid="!!errors.password"
          fluid
        />
        <small v-if="errors.password" class="field-error">{{ errors.password }}</small>
      </label>

      <div v-if="!selectedPlayer" class="profile-section app-card">
        <h3 class="section-title">{{ t('auth.register.profile.title') }}</h3>
        <p class="section-hint">{{ t('auth.register.profile.hint') }}</p>

        <label class="app-field">
          <span>{{ t('players.fields.firstName') }}</span>
          <InputText
            v-model="firstName"
            autocomplete="given-name"
            :invalid="!!errors.firstName"
            fluid
          />
          <small v-if="errors.firstName" class="field-error">{{ errors.firstName }}</small>
        </label>

        <label class="app-field">
          <span>{{ t('players.fields.lastName') }}</span>
          <InputText
            v-model="lastName"
            autocomplete="family-name"
            :invalid="!!errors.lastName"
            fluid
          />
          <small v-if="errors.lastName" class="field-error">{{ errors.lastName }}</small>
        </label>

        <label class="app-field">
          <span>{{ t('players.fields.nickname') }}</span>
          <InputText v-model="nickname" autocomplete="nickname" fluid />
        </label>

        <ClubSelect v-model="clubId" />
      </div>

      <div class="link-section app-card">
        <h3 class="link-title">{{ t('auth.register.linkPlayer.title') }}</h3>
        <p class="link-hint">{{ t('auth.register.linkPlayer.hint') }}</p>
        <PlayerSearchSelect
          v-model="selectedPlayer"
          :placeholder="t('auth.register.linkPlayer.searchPlaceholder')"
          :empty-hint="t('auth.register.linkPlayer.empty')"
          :search-players="searchUnlinkedPlayersForRegistration"
        />
      </div>

      <Button type="submit" :label="t('auth.register.submit')" :disabled="loading" class="w-full" />
      <p class="alt">
        {{ t('auth.haveAccount') }}
        <router-link :to="{ name: 'login' }">{{ t('auth.login.link') }}</router-link>
      </p>
      <AuthLegalNotice />
    </form>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import PlayerSearchSelect from '../components/players/PlayerSearchSelect.vue'
import ClubSelect from '../components/players/ClubSelect.vue'
import AuthLegalNotice from '../components/legal/AuthLegalNotice.vue'
import { authService } from '../services/auth'
import type { Player } from '../models/Player'
import type { RegisterRequest } from '../dto/auth/RegisterRequest'

type FieldErrors = {
  email?: string
  password?: string
  firstName?: string
  lastName?: string
}

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const firstName = ref('')
const lastName = ref('')
const nickname = ref('')
const clubId = ref<number | null>(null)
const selectedPlayer = ref<Player | null>(null)
const errors = reactive<FieldErrors>({})

const loading = computed(() => auth.loading)
const errorMessage = computed(() => (auth.lastError === 'auth.errors.validation' ? '' : auth.lastError ? t(auth.lastError) : ''))

function searchUnlinkedPlayersForRegistration(query: string): Promise<Player[]> {
  return authService.searchUnlinkedPlayers(query)
}

watch(selectedPlayer, (player) => {
  if (player) {
    errors.firstName = undefined
    errors.lastName = undefined
    clubId.value = null
  }
})

function validateLocal(): boolean {
  errors.email = email.value.trim() === ''
    ? t('auth.validations.required')
    : !EMAIL_PATTERN.test(email.value.trim())
      ? t('auth.validations.emailInvalid')
      : undefined

  errors.password = password.value === ''
    ? t('auth.validations.required')
    : password.value.length < 8
      ? t('auth.validations.passwordMinLength')
      : undefined

  if (!selectedPlayer.value) {
    errors.firstName = firstName.value.trim() === '' ? t('players.validations.required') : undefined
    errors.lastName = lastName.value.trim() === '' ? t('players.validations.required') : undefined
  } else {
    errors.firstName = undefined
    errors.lastName = undefined
  }

  return !errors.email && !errors.password && !errors.firstName && !errors.lastName
}

function applyServerFieldErrors(): void {
  const serverErrors = auth.lastFieldErrors
  if (serverErrors.email) {
    errors.email = serverErrors.email
  }
  if (serverErrors.password) {
    errors.password = serverErrors.password
  }
  if (serverErrors.firstName) {
    errors.firstName = serverErrors.firstName
  }
  if (serverErrors.lastName) {
    errors.lastName = serverErrors.lastName
  }
}

async function onSubmit() {
  if (!validateLocal()) {
    return
  }

  const payload: RegisterRequest = {
    email: email.value.trim(),
    password: password.value,
  }

  if (selectedPlayer.value) {
    payload.playerId = selectedPlayer.value.id
  } else {
    payload.firstName = firstName.value.trim()
    payload.lastName = lastName.value.trim()
    const trimmedNickname = nickname.value.trim()
    if (trimmedNickname) {
      payload.nickname = trimmedNickname
    }
    payload.clubId = clubId.value
  }

  await auth.register(payload)
  if (auth.lastError === 'auth.errors.validation') {
    applyServerFieldErrors()
    return
  }

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

.profile-section,
.link-section {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
  background: var(--app-surface-muted, rgba(0, 0, 0, 0.02));
}

.section-title,
.link-title {
  margin: 0;
  font-size: 0.9375rem;
}

.section-hint,
.link-hint {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
  line-height: 1.4;
}

.field-error {
  color: #c24141;
  font-size: 0.8125rem;
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
