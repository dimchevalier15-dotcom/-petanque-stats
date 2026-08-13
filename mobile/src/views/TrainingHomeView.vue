<template>
  <AppPage :title="t('training.home.title')" :subtitle="t('training.home.subtitle')">
    <section class="config-card app-card">
      <h3 class="section-label">{{ t('training.config.type') }}</h3>
      <div class="type-toggle">
        <button
          type="button"
          class="type-btn"
          :class="{ active: selectedType === 'point' }"
          @click="selectedType = 'point'"
        >
          {{ t('training.types.point') }}
        </button>
        <button
          type="button"
          class="type-btn"
          :class="{ active: selectedType === 'tir' }"
          @click="selectedType = 'tir'"
        >
          {{ t('training.types.tir') }}
        </button>
      </div>

      <h3 class="section-label">{{ t('training.config.distance') }}</h3>
      <div class="option-grid">
        <button
          v-for="d in TRAINING_DISTANCES"
          :key="d"
          type="button"
          class="option-btn"
          :class="{ active: selectedDistance === d }"
          @click="selectedDistance = d"
        >
          {{ t('training.distanceMeters', { n: d }) }}
        </button>
      </div>

      <h3 class="section-label">{{ t('training.config.balls') }}</h3>
      <div class="option-grid">
        <button
          v-for="n in TRAINING_BALL_COUNTS"
          :key="n"
          type="button"
          class="option-btn"
          :class="{ active: selectedBalls === n }"
          @click="selectedBalls = n"
        >
          {{ n }}
        </button>
      </div>

      <Button
        class="start-btn"
        :label="t('training.config.start')"
        icon="pi pi-play"
        :loading="starting"
        @click="startSession"
      />
    </section>

    <div class="secondary-actions">
      <Button
        class="action-secondary"
        :label="t('training.home.stats')"
        icon="pi pi-chart-bar"
        severity="secondary"
        outlined
        @click="goStats"
      />
    </div>

    <section class="history-section">
      <h3 class="section-label">{{ t('training.home.historyTitle') }}</h3>

      <EmptyState
        v-if="items.length === 0 && !loading"
        :title="t('training.home.empty')"
        icon="pi pi-flag"
      />

      <ul v-else class="list">
        <li v-for="s in items" :key="s.id">
          <button type="button" class="session-card app-card" @click="openSummary(s.id)">
            <div class="head">
              <span class="date">{{ formatShortDate(s.finishedAt) }}</span>
              <Tag :value="t('training.home.successRate', { rate: s.successRate })" severity="info" />
            </div>
            <span class="session-meta">
              {{ t(`training.types.${s.type}`) }} · {{ t('training.distanceMeters', { n: s.distance }) }}
            </span>
            <span class="session-score">
              {{ t('training.scoreLabel', { score: s.totalScore }) }}
              · {{ s.successfulBalls }}/{{ s.plannedBalls }}
            </span>
          </button>
        </li>
      </ul>

      <div v-if="canLoadMore" class="app-actions">
        <Button :label="t('history.loadMore')" :loading="loading" outlined @click="loadMore" />
      </div>
    </section>

    <Dialog
      v-model:visible="resumeDialog"
      :modal="true"
      :closable="false"
      :header="t('training.resume.title')"
      class="training-dialog"
    >
      <div class="resume-content">
        <p>{{ t('training.resume.message') }}</p>
        <div class="actions">
          <Button
            :label="t('training.resume.abandon')"
            severity="secondary"
            outlined
            :loading="abandoning"
            @click="abandonCurrent"
          />
          <Button :label="t('training.resume.continue')" icon="pi pi-arrow-right" @click="resumeCurrent" />
        </div>
      </div>
    </Dialog>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import { useDateFormat } from '../composables/useDateFormat'
import { trainingSessionsService } from '../services/trainingSessions'
import {
  TRAINING_BALL_COUNTS,
  TRAINING_DISTANCES,
  type TrainingSessionHistoryItem,
  type TrainingSessionStarted,
  type TrainingType,
} from '../models/Training'

const { t } = useI18n()
const { formatShortDate } = useDateFormat()
const router = useRouter()

const selectedType = ref<TrainingType>('point')
const selectedDistance = ref<number>(7)
const selectedBalls = ref<number>(10)

const items = ref<TrainingSessionHistoryItem[]>([])
const page = ref(1)
const pageSize = ref(20)
const total = ref(0)
const loading = ref(false)
const starting = ref(false)
const abandoning = ref(false)

const canLoadMore = computed(() => items.value.length < total.value)

const resumeDialog = ref(false)
const currentSession = ref<TrainingSessionStarted | null>(null)

async function loadHistory(): Promise<void> {
  loading.value = true
  try {
    const res = await trainingSessionsService.getHistory(page.value, pageSize.value)
    total.value = res.total
    const known = new Set(items.value.map((i) => i.id))
    const next = res.items.filter((i) => !known.has(i.id))
    items.value = [...items.value, ...next]
  } finally {
    loading.value = false
  }
}

function loadMore(): void {
  if (!canLoadMore.value || loading.value) return
  page.value += 1
  loadHistory()
}

async function startSession(): Promise<void> {
  starting.value = true
  try {
    const session = await trainingSessionsService.create({
      type: selectedType.value,
      distance: selectedDistance.value,
      plannedBalls: selectedBalls.value,
    })
    router.push({ name: 'trainingSession', params: { id: session.id } })
  } finally {
    starting.value = false
  }
}

async function resumeCurrent(): Promise<void> {
  if (!currentSession.value) return
  resumeDialog.value = false
  router.push({ name: 'trainingSession', params: { id: currentSession.value.id } })
}

async function abandonCurrent(): Promise<void> {
  if (!currentSession.value) return
  abandoning.value = true
  try {
    await trainingSessionsService.abandon(currentSession.value.id)
    currentSession.value = null
    resumeDialog.value = false
  } finally {
    abandoning.value = false
  }
}

function openSummary(id: number): void {
  router.push({ name: 'trainingSessionSummary', params: { id } })
}

function goStats(): void {
  router.push({ name: 'trainingStats' })
}

onMounted(async () => {
  try {
    currentSession.value = await trainingSessionsService.current()
    if (currentSession.value) {
      resumeDialog.value = true
    }
  } catch {
    // resume is optional
  }
  loadHistory()
})
</script>

<style scoped>
.config-card {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.section-label {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-text-subtle);
}

.type-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-xs);
  padding: 0.25rem;
  background: var(--app-surface-muted, #f4f4f5);
  border-radius: var(--app-radius-md);
}

.type-btn {
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

.type-btn.active {
  background: #fff;
  color: var(--app-primary);
  box-shadow: var(--app-shadow-sm);
}

.option-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--app-space-xs);
}

.option-btn {
  min-height: 2.5rem;
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-sm);
  background: #fff;
  font: inherit;
  font-weight: 600;
  color: var(--app-text);
  cursor: pointer;
}

.option-btn.active {
  border-color: var(--app-primary);
  background: var(--app-primary-soft);
  color: var(--app-primary);
}

.start-btn {
  margin-top: var(--app-space-xs);
  min-height: 3rem;
  font-weight: 700;
}

.secondary-actions {
  display: grid;
}

.action-secondary {
  min-height: 2.75rem;
  font-weight: 700;
}

.history-section {
  display: grid;
  gap: var(--app-space-sm);
}

.list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: var(--app-space-sm);
}

.session-card {
  width: 100%;
  padding: var(--app-space-md);
  display: grid;
  gap: 0.125rem;
  text-align: left;
  border: none;
  cursor: pointer;
  font: inherit;
  color: inherit;
  transition: transform 0.12s ease;
}

.session-card:active {
  transform: scale(0.99);
}

.head {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--app-space-sm);
}

.date {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.session-meta {
  font-weight: 700;
  font-size: 0.9375rem;
}

.session-score {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.resume-content {
  display: grid;
  gap: var(--app-space-md);
}

.resume-content p {
  margin: 0;
  color: var(--app-text-muted);
  line-height: 1.45;
}

.resume-content .actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

@media (max-width: 360px) {
  .option-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
