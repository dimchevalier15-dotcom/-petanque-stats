<template>
  <section class="training-play">
    <header class="scoreboard app-card">
      <div class="scoreboard-main">
        <span class="type-pill">
          {{ t(`training.types.${sessionType}`) }} · {{ t('training.distanceMeters', { n: sessionDistance }) }}
        </span>
        <h2 class="ball-progress">
          {{ t('training.play.ballProgress', { current: currentBall, total: plannedBalls }) }}
        </h2>
        <div class="score-row">
          <span class="score-label">{{ t('training.scoreLabel', { score: currentScore }) }}</span>
        </div>
      </div>
      <div class="progress-bar" aria-hidden="true">
        <div class="progress-fill" :style="{ width: progressPercent + '%' }" />
      </div>
    </header>

    <div v-if="loading" class="loading">
      <ProgressSpinner stroke-width="4" />
    </div>

    <template v-else-if="!sessionFinished">
      <p class="play-hint">{{ t('training.play.hint') }}</p>

      <div class="results" :class="{ 'results--point': sessionType === 'point' }">
        <Button
          v-for="result in results"
          :key="result"
          :label="resultLabel(result)"
          :severity="resultSeverity(sessionType, result)"
          class="result-btn"
          :loading="recording"
          @click="recordResult(result)"
        />
      </div>
    </template>

    <footer class="play-actions">
      <Button
        class="abandon-action"
        :label="t('training.actions.abandon')"
        severity="secondary"
        text
        @click="abandonDialog = true"
      />
    </footer>

    <Dialog
      v-model:visible="abandonDialog"
      :modal="true"
      :header="t('training.abandon.title')"
      :closable="false"
      class="training-dialog"
    >
      <div class="abandon-content">
        <p>{{ t('training.abandon.message') }}</p>
        <div class="actions">
          <Button :label="t('training.abandon.cancel')" severity="secondary" @click="abandonDialog = false" />
          <Button
            :label="t('training.abandon.confirm')"
            severity="danger"
            :loading="abandoning"
            @click="confirmAbandon"
          />
        </div>
      </div>
    </Dialog>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'
import { trainingSessionsService } from '../services/trainingSessions'
import {
  resultsForType,
  resultSeverity,
  type TrainingResult,
  type TrainingType,
} from '../models/Training'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const sessionId = Number(route.params.id)

const loading = ref(true)
const recording = ref(false)
const abandoning = ref(false)
const abandonDialog = ref(false)

const sessionType = ref<TrainingType>('point')
const sessionDistance = ref(7)
const plannedBalls = ref(10)
const attemptsCount = ref(0)
const currentScore = ref(0)
const sessionFinished = ref(false)

const currentBall = computed(() => Math.min(attemptsCount.value + 1, plannedBalls.value))
const progressPercent = computed(() =>
  plannedBalls.value > 0 ? Math.round((attemptsCount.value / plannedBalls.value) * 100) : 0,
)
const results = computed(() => resultsForType(sessionType.value))

function resultLabel(result: TrainingResult): string {
  return t(`training.results.${result}`)
}

async function loadSession(): Promise<void> {
  loading.value = true
  try {
    const summary = await trainingSessionsService.getSummary(sessionId)
    sessionType.value = summary.type
    sessionDistance.value = summary.distance
    plannedBalls.value = summary.plannedBalls
    attemptsCount.value = summary.attempts.length
    currentScore.value = summary.attempts.reduce((sum, a) => sum + a.score, 0)
    sessionFinished.value = summary.finishedAt !== null

    if (sessionFinished.value) {
      router.replace({ name: 'trainingSessionSummary', params: { id: sessionId } })
    }
  } finally {
    loading.value = false
  }
}

async function recordResult(result: TrainingResult): Promise<void> {
  if (recording.value || sessionFinished.value) return
  recording.value = true
  try {
    const res = await trainingSessionsService.recordAttempt(sessionId, { result })
    attemptsCount.value = res.attemptsCount
    currentScore.value = res.currentScore

    if (res.sessionFinished && res.summary) {
      sessionFinished.value = true
      router.push({ name: 'trainingSessionSummary', params: { id: sessionId } })
    }
  } finally {
    recording.value = false
  }
}

async function confirmAbandon(): Promise<void> {
  abandoning.value = true
  try {
    await trainingSessionsService.abandon(sessionId)
    abandonDialog.value = false
    router.push({ name: 'trainingHome' })
  } finally {
    abandoning.value = false
  }
}

onMounted(() => {
  loadSession()
})
</script>

<style scoped>
.training-play {
  min-height: 100%;
  display: grid;
  gap: var(--app-space-md);
  padding: var(--app-space-md);
  padding-bottom: calc(var(--app-space-xl) + env(safe-area-inset-bottom, 0px));
}

.scoreboard {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.scoreboard-main {
  display: grid;
  gap: 0.25rem;
  text-align: center;
}

.type-pill {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--app-text-muted);
}

.ball-progress {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1.2;
}

.score-row {
  display: flex;
  justify-content: center;
  gap: var(--app-space-sm);
}

.score-label {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--app-primary);
}

.progress-bar {
  height: 0.375rem;
  background: var(--app-surface-muted, #f4f4f5);
  border-radius: 999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--app-primary), var(--app-primary-dark));
  border-radius: 999px;
  transition: width 0.2s ease;
}

.play-hint {
  margin: 0;
  text-align: center;
  font-size: 0.875rem;
  color: var(--app-text-muted);
}

.results {
  display: grid;
  gap: var(--app-space-sm);
}

.results--point {
  grid-template-columns: 1fr 1fr;
}

.result-btn {
  min-height: 3.5rem;
  font-size: 1rem;
  font-weight: 700;
}

.loading {
  display: grid;
  place-items: center;
  min-height: 8rem;
}

.play-actions {
  display: grid;
  justify-items: center;
}

.abandon-content {
  display: grid;
  gap: var(--app-space-md);
}

.abandon-content p {
  margin: 0;
  color: var(--app-text-muted);
  line-height: 1.45;
}

.abandon-content .actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}
</style>
