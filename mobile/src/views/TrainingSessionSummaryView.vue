<template>
  <AppPage>
    <PageHeader :title="t('training.summary.title')" :back-to="{ name: 'trainingHome' }" />

    <section v-if="summary" class="summary">
      <div class="hero-banner app-card">
        <span class="hero-badge">{{ t('training.summary.sessionFinished') }}</span>
        <span class="hero-meta">
          {{ t(`training.types.${summary.type}`) }} · {{ t('training.distanceMeters', { n: summary.distance }) }}
        </span>
        <strong class="hero-score">
          {{ summary.successfulBalls }}/{{ summary.plannedBalls }}
        </strong>
        <Tag
          v-if="summary.successRate !== null"
          class="hero-rate"
          :value="t('training.home.successRate', { rate: summary.successRate })"
          :severity="trainingSuccessSeverity(summary.successRate)"
        />
        <span class="hero-score-label">
          {{ t('training.scoreLabel', { score: summary.totalScore ?? 0 }) }}
        </span>
        <span class="hero-date">{{ formatDate(summary.finishedAt) }}</span>
      </div>

      <section v-if="summary.attempts.length > 0" class="panel app-card">
        <h3>{{ t('training.summary.attempts') }}</h3>
        <ul class="attempts-list">
          <li v-for="attempt in summary.attempts" :key="attempt.number" class="attempt-row">
            <span class="attempt-num">{{ t('training.play.ballProgress', { current: attempt.number, total: summary.plannedBalls }) }}</span>
            <Tag :value="t(`training.results.${attempt.result}`)" :severity="resultSeverity(summary.type, asTrainingResult(summary.type, attempt.result))" />
            <span class="attempt-score">+{{ attempt.score }}</span>
          </li>
        </ul>
      </section>

      <div class="app-actions">
        <Button
          class="w-full"
          :label="t('training.summary.actions.stats')"
          icon="pi pi-chart-bar"
          outlined
          @click="goStats"
        />
        <Button class="w-full" :label="t('training.summary.actions.newSession')" @click="goHome" />
      </div>
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import { useDateFormat } from '../composables/useDateFormat'
import { trainingSuccessSeverity } from '../composables/useTrainingStatsCharts'
import { trainingSessionsService } from '../services/trainingSessions'
import { resultSeverity, type TrainingResult, type TrainingSessionSummary } from '../models/Training'

function asTrainingResult(type: TrainingSessionSummary['type'], result: string): TrainingResult {
  return result as TrainingResult
}

const { t } = useI18n()
const { formatShortDate } = useDateFormat()
const route = useRoute()
const router = useRouter()

const sessionId = Number(route.params.id)
const summary = ref<TrainingSessionSummary | null>(null)

function formatDate(iso: string | null): string {
  return iso ? formatShortDate(iso) : ''
}

function goHome(): void {
  router.push({ name: 'trainingHome' })
}

function goStats(): void {
  router.push({ name: 'trainingStats' })
}

onMounted(async () => {
  summary.value = await trainingSessionsService.getSummary(sessionId)
})
</script>

<style scoped>
.summary {
  display: grid;
  gap: var(--app-space-md);
}

.hero-banner {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-sm);
  text-align: center;
  align-items: center;
}

.hero-badge {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--app-primary);
}

.hero-meta {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--app-text-muted);
}

.hero-score {
  font-size: 2.5rem;
  font-weight: 800;
  line-height: 1;
}

.hero-rate {
  justify-self: center;
}

.hero-score-label {
  font-size: 1rem;
  font-weight: 600;
  color: var(--app-text-muted);
}

.hero-date {
  font-size: 0.8125rem;
  color: var(--app-text-subtle);
}

.panel {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.panel h3 {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 700;
}

.attempts-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 0.375rem;
}

.attempt-row {
  display: grid;
  grid-template-columns: 1fr auto auto;
  align-items: center;
  gap: var(--app-space-sm);
  padding: 0.375rem 0;
  border-bottom: 1px solid var(--app-border);
}

.attempt-row:last-child {
  border-bottom: none;
}

.attempt-num {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.attempt-score {
  font-weight: 700;
  font-size: 0.875rem;
  color: var(--app-primary);
}

.w-full {
  width: 100%;
}
</style>
