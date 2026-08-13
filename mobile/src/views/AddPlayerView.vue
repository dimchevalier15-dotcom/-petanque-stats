<template>
  <PageHeader :title="t('players.create.title')" />

  <form @submit.prevent="onSubmit" class="app-form" novalidate>
    <div class="app-card form-card">
      <label class="app-field">
        <span>{{ t('players.fields.firstName') }}</span>
        <InputText v-model="firstName" :invalid="!!errors.firstName" autocomplete="given-name" fluid />
        <small v-if="errors.firstName" class="error">{{ errors.firstName }}</small>
      </label>

      <label class="app-field">
        <span>{{ t('players.fields.lastName') }}</span>
        <InputText v-model="lastName" :invalid="!!errors.lastName" autocomplete="family-name" fluid />
        <small v-if="errors.lastName" class="error">{{ errors.lastName }}</small>
      </label>

      <label class="app-field">
        <span>{{ t('players.fields.nickname') }}</span>
        <InputText v-model="nickname" autocomplete="nickname" fluid />
      </label>
    </div>

    <Button type="submit" class="w-full" :label="t('players.actions.submit')" :disabled="submitting || !canSubmit" />
  </form>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import PageHeader from '../components/layout/PageHeader.vue'
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
    const created = await playersService.create({
      firstName: firstName.value.trim(),
      lastName: lastName.value.trim(),
      nickname: nickname.value.trim() || undefined,
    })

    toast.add({ severity: 'success', summary: t('players.create.toast.success'), life: 2000 })
    const q = router.currentRoute.value.query as Record<string, string | undefined>
    if (q.returnTo === 'newMatch' && q.slot) {
      router.push({ name: 'newMatch', query: { newPlayerId: String(created.id), slot: q.slot } })
    } else {
      router.push({ name: 'home' })
    }
  } catch (e: unknown) {
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
.form-card {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-md);
}

.error {
  color: #c24141;
  font-size: 0.8125rem;
}

.w-full {
  width: 100%;
}
</style>
