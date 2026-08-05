<template>
  <section class="player-form">
    <h2>{{ t('players.create.title') }}</h2>

    <form @submit.prevent="onSubmit" class="form" novalidate>
      <label class="field">
        <span>{{ t('players.fields.firstName') }}</span>
        <InputText v-model="firstName" :invalid="!!errors.firstName" autocomplete="given-name" />
        <small v-if="errors.firstName" class="error">{{ errors.firstName }}</small>
      </label>

      <label class="field">
        <span>{{ t('players.fields.lastName') }}</span>
        <InputText v-model="lastName" :invalid="!!errors.lastName" autocomplete="family-name" />
        <small v-if="errors.lastName" class="error">{{ errors.lastName }}</small>
      </label>

      <label class="field">
        <span>{{ t('players.fields.nickname') }}</span>
        <InputText v-model="nickname" autocomplete="nickname" />
      </label>

      <Button type="submit" :label="t('players.actions.submit')" :disabled="submitting || !canSubmit" />
    </form>
  </section>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import { playersService } from '../services/players'

type Errors = { firstName?: string; lastName?: string }

const { t } = useI18n()
const router = useRouter()
const toast = useToast()

const firstName = ref('')
const lastName = ref('')
const nickname = ref('')

const submitting = ref(false)
const errors = reactive<Errors>({})

const canSubmit = computed(() => firstName.value.trim() !== '' && lastName.value.trim() !== '')

function validateLocal(): boolean {
  errors.firstName = firstName.value.trim() === '' ? t('players.validations.required') : undefined
  errors.lastName = lastName.value.trim() === '' ? t('players.validations.required') : undefined
  return !errors.firstName && !errors.lastName
}

async function onSubmit(): Promise<void> {
  if (!validateLocal()) return
  submitting.value = true
  try {
    await playersService.create({
      firstName: firstName.value.trim(),
      lastName: lastName.value.trim(),
      nickname: nickname.value.trim() || undefined,
    })

    toast.add({ severity: 'success', summary: t('players.create.toast.success'), life: 2000 })
    router.push({ name: 'home' })
  } catch (e: unknown) {
    // Try to read server-side validation format { errors: { firstName?: string, lastName?: string } }
    const err = e as import('axios').AxiosError<{ errors?: Record<string, string> }>
    const serverErrors = err.response?.data?.errors
    if (serverErrors) {
      errors.firstName = serverErrors.firstName ? serverErrors.firstName : undefined
      errors.lastName = serverErrors.lastName ? serverErrors.lastName : undefined
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.player-form { max-width: 480px; margin: 1.5rem auto; display: grid; gap: 1rem; }
.form { display: grid; gap: 0.9rem; }
.field { display: grid; gap: 0.25rem; }
.error { color: #dc2626; font-size: 0.8rem; }
</style>
