<template>
  <AppPage>
    <PageHeader :title="t('admin.clubs.title')" :back-to="{ name: 'adminHome' }" />

    <section class="form-card app-card">
      <h3 class="section-title">
        {{ editingId ? t('admin.clubs.editTitle') : t('admin.clubs.addTitle') }}
      </h3>

      <form class="app-form" @submit.prevent="onSubmit">
        <label class="app-field">
          <span>{{ t('admin.clubs.fields.name') }}</span>
          <InputText v-model="form.name" :invalid="!!errors.name" fluid />
          <small v-if="errors.name" class="field-error">{{ errors.name }}</small>
        </label>

        <label class="app-field">
          <span>{{ t('admin.clubs.fields.country') }}</span>
          <Dropdown
            v-model="form.countryId"
            :options="countries"
            option-label="name"
            option-value="id"
            :placeholder="t('admin.clubs.placeholders.country')"
            overlay-class="context-dropdown-overlay"
            scroll-height="40vh"
            filter
            :invalid="!!errors.countryId"
            fluid
          />
          <small v-if="errors.countryId" class="field-error">{{ errors.countryId }}</small>
        </label>

        <label class="app-field">
          <span>{{ t('admin.clubs.fields.description') }}</span>
          <Textarea v-model="form.description" rows="3" auto-resize fluid />
        </label>

        <Message v-if="formError" severity="error">{{ formError }}</Message>

        <div class="form-actions">
          <Button
            type="submit"
            :label="editingId ? t('admin.clubs.actions.save') : t('admin.clubs.actions.add')"
            :loading="submitting"
            class="w-full"
          />
          <Button
            v-if="editingId"
            type="button"
            severity="secondary"
            outlined
            :label="t('admin.clubs.actions.cancelEdit')"
            class="w-full"
            @click="resetForm"
          />
        </div>
      </form>
    </section>

    <section v-if="loading" class="state-card app-card">
      <p>{{ t('admin.clubs.loading') }}</p>
    </section>

    <section v-else-if="clubs.length === 0" class="state-card app-card">
      <p>{{ t('admin.clubs.empty') }}</p>
    </section>

    <section v-else class="list">
      <article v-for="club in clubs" :key="club.id" class="list-item app-card">
        <div class="list-item-body">
          <h4 class="item-title">{{ club.name }}</h4>
          <p class="item-meta">{{ club.country.name }}</p>
          <p v-if="club.description" class="item-context">{{ club.description }}</p>
        </div>
        <div class="list-item-actions">
          <Button
            type="button"
            icon="pi pi-pencil"
            severity="secondary"
            outlined
            :aria-label="t('admin.clubs.actions.edit')"
            @click="startEdit(club)"
          />
          <Button
            type="button"
            icon="pi pi-trash"
            severity="danger"
            outlined
            :aria-label="t('admin.clubs.actions.delete')"
            :loading="deletingId === club.id"
            @click="onDelete(club)"
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
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import { clubLabel, type Club } from '../models/Club'
import type { Country } from '../models/Country'
import { clubsService } from '../services/clubs'
import { countriesService } from '../services/countries'

const { t } = useI18n()

const loading = ref(true)
const submitting = ref(false)
const deletingId = ref<number | null>(null)
const editingId = ref<number | null>(null)
const formError = ref('')
const clubs = ref<Club[]>([])
const countries = ref<Country[]>([])
const errors = reactive<{ name?: string; countryId?: string }>({})
const form = reactive({
  name: '',
  countryId: null as number | null,
  description: '',
})

function resetForm() {
  editingId.value = null
  form.name = ''
  form.countryId = null
  form.description = ''
  formError.value = ''
  errors.name = undefined
  errors.countryId = undefined
}

function validateLocal(): boolean {
  errors.name = form.name.trim() === '' ? t('admin.clubs.validations.required') : undefined
  errors.countryId = form.countryId === null ? t('admin.clubs.validations.required') : undefined

  return !errors.name && !errors.countryId
}

async function load() {
  loading.value = true
  try {
    const [clubItems, countryItems] = await Promise.all([clubsService.list(), countriesService.list()])
    clubs.value = clubItems
    countries.value = countryItems
  } finally {
    loading.value = false
  }
}

function startEdit(club: Club) {
  editingId.value = club.id
  form.name = club.name
  form.countryId = club.country.id
  form.description = club.description ?? ''
  formError.value = ''
}

async function onSubmit() {
  if (!validateLocal() || form.countryId === null) {
    return
  }

  submitting.value = true
  formError.value = ''

  try {
    const payload = {
      name: form.name.trim(),
      countryId: form.countryId,
      description: form.description.trim() || null,
    }
    if (editingId.value) {
      const updated = await clubsService.update(editingId.value, payload)
      clubs.value = clubs.value.map((item) => (item.id === updated.id ? updated : item))
    } else {
      const created = await clubsService.create(payload)
      clubs.value = [...clubs.value, created].sort((a, b) => a.name.localeCompare(b.name))
    }
    resetForm()
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const serverErrors = error.response?.data?.errors as Record<string, string> | undefined
      if (serverErrors) {
        errors.name = serverErrors.name
        errors.countryId = serverErrors.countryId
        return
      }
    }
    formError.value = t('admin.clubs.errors.saveFailed')
  } finally {
    submitting.value = false
  }
}

async function onDelete(club: Club) {
  if (!window.confirm(t('admin.clubs.confirmDelete', { name: clubLabel(club) }))) {
    return
  }

  deletingId.value = club.id
  try {
    await clubsService.remove(club.id)
    clubs.value = clubs.value.filter((item) => item.id !== club.id)
    if (editingId.value === club.id) {
      resetForm()
    }
  } catch {
    formError.value = t('admin.clubs.errors.deleteFailed')
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
