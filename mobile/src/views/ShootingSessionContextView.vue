<template>
  <PageHeader :title="title" :back-to="{ name: 'shootingSessionSummary', params: { id: sessionId } }" />

  <section class="context">
    <p class="hint">{{ t('shooting.context.hint') }}</p>

    <form class="app-form" @submit.prevent="onSubmit">
      <div class="app-card form-card">
        <label class="app-field">
          <span>{{ t('shooting.context.fields.title') }}</span>
          <InputText v-model="form.title" :maxlength="100" fluid />
        </label>

        <label class="app-field">
          <span>{{ t('shooting.context.fields.description') }}</span>
          <Textarea v-model="form.description" rows="4" auto-resize :maxlength="2000" fluid />
        </label>
      </div>

      <div class="app-actions">
        <Button type="submit" class="w-full" :label="t('shooting.context.actions.save')" :loading="submitting" />
        <Button
          type="button"
          severity="secondary"
          outlined
          class="w-full"
          :label="t('shooting.context.actions.skip')"
          @click="goToSummary"
        />
      </div>
    </form>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import PageHeader from '../components/layout/PageHeader.vue'
import type { ShootingSessionContextForm } from '../models/Shooting'
import { shootingSessionsService } from '../services/shootingSessions'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const sessionId = Number(route.params.id)
const submitting = ref(false)
const hasExistingContext = ref(false)
const form = reactive<ShootingSessionContextForm>({ title: '', description: '' })

const title = computed(() =>
  hasExistingContext.value ? t('shooting.context.titleEdit') : t('shooting.context.titleAdd'),
)

async function load(): Promise<void> {
  if (!sessionId) {
    router.replace({ name: 'shootingHome' })
    return
  }
  try {
    const summary = await shootingSessionsService.getSummary(sessionId)
    form.title = summary.title ?? ''
    form.description = summary.description ?? ''
    hasExistingContext.value = Boolean(summary.title || summary.description)
  } catch {
    router.replace({ name: 'shootingHome' })
  }
}

async function onSubmit(): Promise<void> {
  submitting.value = true
  try {
    await shootingSessionsService.updateContext(sessionId, {
      title: form.title.trim() || null,
      description: form.description.trim() || null,
    })
    goToSummary()
  } finally {
    submitting.value = false
  }
}

function goToSummary(): void {
  router.push({ name: 'shootingSessionSummary', params: { id: sessionId } })
}

onMounted(load)
</script>

<style scoped>
.context {
  display: grid;
  gap: var(--app-space-md);
}

.hint {
  margin: 0;
  font-size: 0.875rem;
  color: var(--app-text-muted);
}

.form-card {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-md);
}

.w-full {
  width: 100%;
}
</style>
