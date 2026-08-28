<template>
  <PageHeader :title="title" :back-to="{ name: 'shootingSessionSummary', params: { id: sessionId } }" />

  <section class="context">
    <p class="hint">{{ t('shooting.context.hint') }}</p>

    <form class="app-form" @submit.prevent="onSubmit">
      <div class="app-card form-card">
        <label class="app-field">
          <span>{{ t('shooting.context.fields.playedAt') }}</span>
          <input v-model="form.playedAt" type="date" class="date-input" />
        </label>

        <h3 class="section-label">{{ t('shooting.context.fields.nature') }}</h3>
        <div class="nature-toggle">
          <button
            type="button"
            class="nature-btn"
            :class="{ active: form.contextNature === 'training' }"
            @click="form.contextNature = 'training'"
          >
            {{ t('shooting.context.nature.training') }}
          </button>
          <button
            type="button"
            class="nature-btn"
            :class="{ active: form.contextNature === 'competition' }"
            @click="form.contextNature = 'competition'"
          >
            {{ t('shooting.context.nature.competition') }}
          </button>
        </div>

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
import { playedAtToInputDate, todayInputDate } from '../models/MatchContext'
import type { ShootingSessionContextForm } from '../models/Shooting'
import { shootingSessionsService } from '../services/shootingSessions'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const sessionId = Number(route.params.id)
const submitting = ref(false)
const hasExistingContext = ref(false)
const form = reactive<ShootingSessionContextForm>({
  contextNature: 'training',
  playedAt: todayInputDate(),
  title: '',
  description: '',
})

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
    form.contextNature = summary.contextNature ?? 'training'
    form.playedAt = playedAtToInputDate(summary.playedAt)
    form.title = summary.title ?? ''
    form.description = summary.description ?? ''
    hasExistingContext.value = Boolean(summary.contextNature || summary.title || summary.description)
  } catch {
    router.replace({ name: 'shootingHome' })
  }
}

async function onSubmit(): Promise<void> {
  submitting.value = true
  try {
    await shootingSessionsService.updateContext(sessionId, {
      contextNature: form.contextNature,
      playedAt: form.playedAt || null,
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

.section-label {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-text-subtle);
}

.nature-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-xs);
  padding: 0.25rem;
  background: var(--app-surface-muted, #f4f4f5);
  border-radius: var(--app-radius-md);
}

.nature-btn {
  min-height: 2.75rem;
  border: none;
  border-radius: var(--app-radius-sm);
  background: transparent;
  font: inherit;
  font-weight: 700;
  color: var(--app-text-muted);
  cursor: pointer;
  transition: background 0.12s ease, color 0.12s ease;
}

.nature-btn.active {
  background: #fff;
  color: var(--app-primary);
  box-shadow: var(--app-shadow-sm);
}

.w-full {
  width: 100%;
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
</style>
