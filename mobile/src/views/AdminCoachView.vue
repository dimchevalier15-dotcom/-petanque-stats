<template>
  <AppPage>
    <PageHeader :title="t('admin.coach.title')" :back-to="{ name: 'adminHome' }" />

    <section class="form-card app-card">
      <p class="hint">{{ t('admin.coach.hint') }}</p>

      <form class="app-form" @submit.prevent="onSubmit">
        <label class="app-field">
          <span>{{ t('admin.coach.fields.email') }}</span>
          <InputText v-model="form.email" type="email" :invalid="!!errors.email" fluid />
          <small v-if="errors.email" class="field-error">{{ errors.email }}</small>
        </label>

        <label class="app-field">
          <span>{{ t('admin.coach.fields.club') }}</span>
          <Dropdown
            v-model="form.clubId"
            :options="clubOptions"
            option-label="label"
            option-value="value"
            :placeholder="t('admin.coach.placeholders.club')"
            show-clear
            fluid
          />
          <small class="field-hint">{{ t('admin.coach.clearHint') }}</small>
        </label>

        <Message v-if="formError" severity="error">{{ formError }}</Message>
        <Message v-if="successMessage" severity="success">{{ successMessage }}</Message>

        <Button type="submit" :label="t('admin.coach.actions.save')" :loading="submitting" class="w-full" />
      </form>
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import { clubsService } from '../services/clubs'
import { adminUsersService } from '../services/adminUsers'
import type { Club } from '../models/Club'

const { t } = useI18n()

const clubs = ref<Club[]>([])
const submitting = ref(false)
const formError = ref('')
const successMessage = ref('')
const errors = ref<Record<string, string>>({})

const form = ref<{ email: string; clubId: number | null }>({
  email: '',
  clubId: null,
})

const clubOptions = computed(() =>
  clubs.value.map((club) => ({
    label: club.name,
    value: club.id,
  })),
)

async function loadClubs(): Promise<void> {
  clubs.value = await clubsService.list()
}

async function onSubmit(): Promise<void> {
  errors.value = {}
  formError.value = ''
  successMessage.value = ''

  const email = form.value.email.trim()
  if (!email) {
    errors.value.email = t('admin.coach.errors.emailRequired')
    return
  }

  submitting.value = true
  try {
    await adminUsersService.updateCoachClub({
      email,
      clubId: form.value.clubId,
    })
    successMessage.value = t('admin.coach.success')
  } catch {
    formError.value = t('admin.coach.errors.saveFailed')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  void loadClubs()
})
</script>

<style scoped>
.form-card {
  padding: var(--app-space-md);
}

.hint {
  margin: 0 0 var(--app-space-md);
  color: var(--app-text-muted);
  font-size: 0.875rem;
}

.field-hint {
  color: var(--app-text-muted);
  font-size: 0.75rem;
}

.field-error {
  color: var(--app-danger);
}
</style>
