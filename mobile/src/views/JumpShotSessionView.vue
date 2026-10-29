<template>
  <section class="jump-shot-play">
    <header class="scoreboard app-card">
      <div class="scoreboard-top">
        <span class="series-label">
          {{ t('jumpShot.play.seriesLabel', { n: currentSeriesNumber }) }}
        </span>
        <button type="button" class="rules-link" @click="goRules">
          {{ t('jumpShot.play.rules') }}
        </button>
      </div>
      <h2 class="distance-title">{{ t('jumpShot.play.currentDistance', { n: currentDistance }) }}</h2>
      <p class="success-line">
        {{ t('jumpShot.play.successInSeries', { count: successCountInDraft, total: 3 }) }}
      </p>
      <div class="distance-track" aria-label="Progression">
        <span
          v-for="d in JUMP_SHOT_DISTANCES"
          :key="d"
          class="distance-step"
          :class="{ active: d === currentDistance, passed: d < currentDistance }"
        >
          {{ d }} m
        </span>
      </div>
    </header>

    <div v-if="loading" class="loading">
      <ProgressSpinner stroke-width="4" />
    </div>

    <div v-else-if="submitting" class="loading">
      <ProgressSpinner stroke-width="4" />
    </div>

    <template v-else-if="!sessionFinished">
      <div v-for="position in [1, 2, 3] as const" :key="position" class="throw-block app-card">
        <h3>{{ t('jumpShot.play.throwLabel', { n: position }) }}</h3>
        <div class="result-grid">
          <Button
            v-for="result in JUMP_SHOT_THROW_RESULTS"
            :key="`${position}-${result}`"
            :label="t(`jumpShot.results.${result}`)"
            :severity="resultSeverity(result)"
            :outlined="draft[position] !== result"
            class="result-btn"
            @click="setThrow(position, result)"
          />
        </div>
      </div>
    </template>

    <footer class="play-actions">
      <Button
        v-if="seriesCount > 0"
        class="undo-action"
        :label="t('jumpShot.play.undoLastSeries')"
        icon="pi pi-undo"
        severity="secondary"
        text
        :loading="reverting"
        :disabled="submitting"
        @click="undoLastSeries"
      />
      <Button
        class="abandon-action"
        :label="t('jumpShot.play.abandon')"
        severity="secondary"
        text
        :disabled="submitting || reverting"
        @click="abandonDialog = true"
      />
    </footer>

    <Dialog
      v-model:visible="abandonDialog"
      :modal="true"
      :header="t('jumpShot.abandon.title')"
      :closable="false"
      class="jump-shot-dialog"
    >
      <div class="abandon-content">
        <p>{{ t('jumpShot.abandon.message') }}</p>
        <div class="actions">
          <Button :label="t('jumpShot.abandon.cancel')" severity="secondary" @click="abandonDialog = false" />
          <Button
            :label="t('jumpShot.abandon.confirm')"
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
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'
import {
  JUMP_SHOT_DISTANCES,
  JUMP_SHOT_THROW_RESULTS,
  jumpShotResultIsSuccess,
  type JumpShotDistance,
  type JumpShotThrowResult,
} from '../models/JumpShot'
import { jumpShotSessionsService } from '../services/jumpShotSessions'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const sessionId = computed(() => Number(route.params.id))

const loading = ref(true)
const submitting = ref(false)
const reverting = ref(false)
const abandoning = ref(false)
const abandonDialog = ref(false)

const currentDistance = ref<JumpShotDistance>(6)
const seriesCount = ref(0)
const sessionFinished = ref(false)

const draft = reactive<Record<1 | 2 | 3, JumpShotThrowResult | null>>({
  1: null,
  2: null,
  3: null,
})

const currentSeriesNumber = computed(() => seriesCount.value + 1)

const successCountInDraft = computed(() =>
  [1, 2, 3].reduce((acc, pos) => {
    const r = draft[pos as 1 | 2 | 3]
    return r && jumpShotResultIsSuccess(r) ? acc + 1 : acc
  }, 0),
)

function resultSeverity(result: JumpShotThrowResult): 'secondary' | 'success' | 'warn' | 'danger' | 'info' {
  if (result === 'missed') {
    return 'danger'
  }
  if (result === 'carreau') {
    return 'success'
  }
  if (result === 'hit') {
    return 'warn'
  }
  return 'info'
}

function setThrow(position: 1 | 2 | 3, result: JumpShotThrowResult): void {
  if (submitting.value || reverting.value) {
    return
  }
  draft[position] = result
  if (draft[1] !== null && draft[2] !== null && draft[3] !== null) {
    void submitSeries()
  }
}

function resetDraft(): void {
  draft[1] = null
  draft[2] = null
  draft[3] = null
}

function applyRestoredThrows(throws: { position: number; result: JumpShotThrowResult }[]): void {
  resetDraft()
  for (const throwEntry of throws) {
    const pos = throwEntry.position
    if (pos === 1 || pos === 2 || pos === 3) {
      draft[pos] = throwEntry.result
    }
  }
}

function goRules(): void {
  void router.push({ name: 'jumpShotRules', query: { sessionId: String(sessionId.value) } })
}

async function loadSession(): Promise<void> {
  loading.value = true
  try {
    const summary = await jumpShotSessionsService.getSummary(sessionId.value)
    if (summary.won) {
      await router.replace({ name: 'jumpShotVictory', params: { id: summary.id } })
      return
    }
    currentDistance.value = summary.currentDistance
    seriesCount.value = summary.seriesCount
    sessionFinished.value = summary.finishedAt !== null
  } finally {
    loading.value = false
  }
}

async function submitSeries(): Promise<void> {
  if (draft[1] === null || draft[2] === null || draft[3] === null || submitting.value) {
    return
  }
  submitting.value = true
  try {
    const payload = {
      throws: ([1, 2, 3] as const).map((position) => ({
        position,
        result: draft[position] as JumpShotThrowResult,
      })),
    }
    const res = await jumpShotSessionsService.submitSeries(sessionId.value, payload)
    if (res.sessionWon) {
      await router.push({ name: 'jumpShotVictory', params: { id: sessionId.value } })
      return
    }
    seriesCount.value = res.session.seriesCount
    currentDistance.value = res.nextDistance
    sessionFinished.value = res.sessionFinished
    resetDraft()
  } finally {
    submitting.value = false
  }
}

async function undoLastSeries(): Promise<void> {
  if (seriesCount.value === 0 || reverting.value || submitting.value) {
    return
  }
  reverting.value = true
  try {
    const res = await jumpShotSessionsService.revertLastSeries(sessionId.value)
    seriesCount.value = res.session.seriesCount
    currentDistance.value = res.session.currentDistance
    sessionFinished.value = false
    applyRestoredThrows(res.restoredThrows)
  } finally {
    reverting.value = false
  }
}

async function confirmAbandon(): Promise<void> {
  abandoning.value = true
  try {
    await jumpShotSessionsService.abandon(sessionId.value)
    await router.push({ name: 'jumpShotHome' })
  } finally {
    abandoning.value = false
  }
}

onMounted(() => {
  void loadSession()
})
</script>

<style scoped>
.jump-shot-play {
  display: grid;
  gap: var(--app-space-md);
  padding-bottom: var(--app-space-xl);
}

.scoreboard {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.scoreboard-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--app-space-sm);
}

.series-label {
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--app-text-muted);
}

.rules-link {
  border: none;
  background: transparent;
  color: var(--app-primary);
  font: inherit;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
}

.distance-title {
  margin: 0;
  font-size: 1.5rem;
}

.success-line {
  margin: 0;
  font-size: 0.9375rem;
}

.distance-track {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.375rem;
}

.distance-step {
  text-align: center;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.375rem 0.25rem;
  border-radius: 999px;
  border: 1px solid var(--app-border);
  color: var(--app-text-muted);
}

.distance-step.passed {
  background: var(--app-primary-soft);
  color: var(--app-primary);
}

.distance-step.active {
  border-color: var(--app-primary);
  background: var(--app-primary);
  color: #fff;
}

.throw-block {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.throw-block h3 {
  margin: 0;
  font-size: 0.9375rem;
}

.result-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

.result-btn {
  width: 100%;
}

.loading {
  display: grid;
  place-items: center;
  min-height: 8rem;
}

.play-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: var(--app-space-xs);
}

.abandon-content {
  display: grid;
  gap: var(--app-space-md);
}

.abandon-content .actions {
  display: grid;
  gap: var(--app-space-sm);
}
</style>
