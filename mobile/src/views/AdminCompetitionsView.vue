<template>
  <AppPage>
    <PageHeader :title="t('admin.competitions.title')" :back-to="{ name: 'settings' }" />

    <section class="form-card app-card">
      <h3 class="section-title">
        {{ editingId ? t('admin.competitions.editTitle') : t('admin.competitions.addTitle') }}
      </h3>

      <form class="app-form" @submit.prevent="onSubmit">
        <label class="app-field">
          <span>{{ t('admin.competitions.fields.name') }}</span>
          <InputText v-model="form.name" :invalid="!!errors.name" fluid />
          <small v-if="errors.name" class="field-error">{{ errors.name }}</small>
        </label>

        <label class="app-field">
          <span>{{ t('admin.competitions.fields.eventDate') }}</span>
          <input v-model="form.eventDate" type="date" class="date-input" />
          <small v-if="errors.eventDate" class="field-error">{{ errors.eventDate }}</small>
        </label>

        <label class="app-field">
          <span>{{ t('admin.competitions.fields.country') }}</span>
          <InputText v-model="form.country" :invalid="!!errors.country" fluid />
          <small v-if="errors.country" class="field-error">{{ errors.country }}</small>
        </label>

        <label class="app-field">
          <span>{{ t('admin.competitions.fields.context') }}</span>
          <InputText v-model="form.context" fluid />
        </label>

        <Message v-if="formError" severity="error">{{ formError }}</Message>

        <div class="form-actions">
          <Button
            type="submit"
            :label="editingId ? t('admin.competitions.actions.save') : t('admin.competitions.actions.add')"
            :loading="submitting"
            class="w-full"
          />
          <Button
            v-if="editingId"
            type="button"
            severity="secondary"
            outlined
            :label="t('admin.competitions.actions.cancelEdit')"
            class="w-full"
            @click="resetForm"
          />
        </div>
      </form>
    </section>

    <section v-if="loading" class="state-card app-card">
      <p>{{ t('admin.competitions.loading') }}</p>
    </section>

    <section v-else-if="competitions.length === 0" class="state-card app-card">
      <p>{{ t('admin.competitions.empty') }}</p>
    </section>

    <section v-else class="list">
      <article v-for="competition in competitions" :key="competition.id" class="list-item app-card">
        <div class="list-item-body">
          <h4 class="item-title">{{ competitionLabel(competition) }}</h4>
          <p class="item-meta">{{ competition.country }}</p>
          <p v-if="competition.context" class="item-context">{{ competition.context }}</p>
        </div>
        <div class="list-item-actions">
          <Button
            type="button"
            icon="pi pi-pencil"
            severity="secondary"
            outlined
            :aria-label="t('admin.competitions.actions.edit')"
            @click="startEdit(competition)"
          />
          <Button
            type="button"
            icon="pi pi-trash"
            severity="danger"
            outlined
            :aria-label="t('admin.competitions.actions.delete')"
            :loading="deletingId === competition.id"
            @click="onDelete(competition)"
          />
        </div>
      </article>
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import { competitionLabel, type Competition } from '../models/Competition'
import { competitionsService } from '../services/competitions'

type FormErrors = {
  name?: string
  eventDate?: string
  country?: string
}

type CompetitionForm = {
  name: string
  eventDate: string
  country: string
  context: string
}

const { t } = useI18n()

const loading = ref(true)
const submitting = ref(false)
const deletingId = ref<number | null>(null)
const editingId = ref<number | null>(null)
const formError = ref('')
const competitions = ref<Competition[]>([])
const errors = reactive<FormErrors>({})

const emptyForm = (): CompetitionForm => ({
  name: '',
  eventDate: '',
  country: '',
  context: '',
})

const form = reactive<CompetitionForm>(emptyForm())

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
  formError.value = ''
  errors.name = undefined
  errors.eventDate = undefined
  errors.country = undefined
}

function validateLocal(): boolean {
  errors.name = form.name.trim() === '' ? t('admin.competitions.validations.required') : undefined
  errors.eventDate = form.eventDate.trim() === '' ? t('admin.competitions.validations.required') : undefined
  errors.country = form.country.trim() === '' ? t('admin.competitions.validations.required') : undefined

  return !errors.name && !errors.eventDate && !errors.country
}

function toPayload() {
  return {
    name: form.name.trim(),
    eventDate: form.eventDate,
    country: form.country.trim(),
    context: form.context.trim() || null,
  }
}

async function load() {
  loading.value = true
  try {
    competitions.value = await competitionsService.list()
  } finally {
    loading.value = false
  }
}

function startEdit(competition: Competition) {
  editingId.value = competition.id
  form.name = competition.name
  form.eventDate = competition.eventDate
  form.country = competition.country
  form.context = competition.context ?? ''
  formError.value = ''
}

async function onSubmit() {
  if (!validateLocal()) {
    return
  }

  submitting.value = true
  formError.value = ''

  try {
    const payload = toPayload()
    if (editingId.value) {
      const updated = await competitionsService.update(editingId.value, payload)
      competitions.value = competitions.value.map((item) => (item.id === updated.id ? updated : item))
    } else {
      const created = await competitionsService.create(payload)
      competitions.value = [created, ...competitions.value]
    }
    resetForm()
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const serverErrors = error.response?.data?.errors as Record<string, string> | undefined
      if (serverErrors) {
        errors.name = serverErrors.name
        errors.eventDate = serverErrors.eventDate
        errors.country = serverErrors.country
        return
      }
    }
    formError.value = t('admin.competitions.errors.saveFailed')
  } finally {
    submitting.value = false
  }
}

async function onDelete(competition: Competition) {
  if (!window.confirm(t('admin.competitions.confirmDelete', { name: competitionLabel(competition) }))) {
    return
  }

  deletingId.value = competition.id
  try {
    await competitionsService.remove(competition.id)
    competitions.value = competitions.value.filter((item) => item.id !== competition.id)
    if (editingId.value === competition.id) {
      resetForm()
    }
  } catch {
    formError.value = t('admin.competitions.errors.deleteFailed')
  } finally {
    deletingId.value = null
  }
}

onMounted(load)
</script>

<style scoped>
.form-card,
.state-card {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-md);
}

.section-title {
  margin: 0;
  font-size: 1rem;
}

.form-actions {
  display: grid;
  gap: var(--app-space-sm);
}

.date-input {
  width: 100%;
  min-height: 2.75rem;
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-sm);
  padding: 0.625rem 0.75rem;
  font: inherit;
  color: var(--app-text);
  background: var(--app-surface);
}

.list {
  display: grid;
  gap: var(--app-space-md);
}

.list-item {
  padding: var(--app-space-md);
  display: flex;
  gap: var(--app-space-md);
  align-items: flex-start;
  justify-content: space-between;
}

.list-item-body {
  display: grid;
  gap: 0.25rem;
  min-width: 0;
}

.item-title {
  margin: 0;
  font-size: 0.95rem;
}

.item-meta,
.item-context {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.list-item-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.field-error {
  color: #c24141;
  font-size: 0.8125rem;
}

.w-full {
  width: 100%;
}
</style>
