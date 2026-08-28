<template>
  <AppPage :title="t('shooting.home.title')" :subtitle="t('shooting.home.subtitle')">
    <div class="home-actions">
      <Button
        class="action-primary"
        :label="t('shooting.home.newSession')"
        icon="pi pi-bullseye"
        :loading="starting"
        @click="startNewSession"
      />
      <Button
        class="action-secondary"
        :label="t('shooting.home.stats')"
        icon="pi pi-chart-bar"
        severity="secondary"
        outlined
        @click="goStats"
      />
    </div>

    <section class="history-section">
      <h3 class="section-label">{{ t('shooting.home.historyTitle') }}</h3>

      <EmptyState
        v-if="items.length === 0 && !loading"
        :title="t('shooting.home.empty')"
        icon="pi pi-bullseye"
      />

      <ul v-else class="list">
        <li v-for="s in items" :key="s.id">
          <button type="button" class="session-card app-card" @click="openSummary(s.id)">
            <div class="head">
              <span class="date">{{ formatShortDate(s.playedAt) }}</span>
              <Tag :value="t('shooting.home.scoreOn100', { score: s.totalScore })" severity="info" />
            </div>
            <span v-if="s.title" class="session-title">{{ s.title }}</span>
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
      :header="t('shooting.resume.title')"
      class="shooting-dialog"
    >
      <div class="resume-content">
        <p>{{ t('shooting.resume.message') }}</p>
        <div class="actions">
          <Button
            :label="t('shooting.resume.abandon')"
            severity="secondary"
            outlined
            :loading="abandoning"
            @click="abandonCurrent"
          />
          <Button :label="t('shooting.resume.continue')" icon="pi pi-arrow-right" @click="resumeCurrent" />
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
import { shootingSessionsService } from '../services/shootingSessions'
import type { ShootingSessionHistoryItem, ShootingSessionStarted } from '../models/Shooting'

const { t } = useI18n()
const { formatShortDate } = useDateFormat()
const router = useRouter()

const items = ref<ShootingSessionHistoryItem[]>([])
const page = ref(1)
const pageSize = ref(20)
const total = ref(0)
const loading = ref(false)
const starting = ref(false)
const abandoning = ref(false)

const canLoadMore = computed(() => items.value.length < total.value)

const resumeDialog = ref(false)
const currentSession = ref<ShootingSessionStarted | null>(null)

async function loadHistory(): Promise<void> {
  loading.value = true
  try {
    const res = await shootingSessionsService.getHistory(page.value, pageSize.value)
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

async function startNewSession(): Promise<void> {
  starting.value = true
  try {
    const session = await shootingSessionsService.start()
    router.push({ name: 'shootingSession', params: { id: session.id } })
  } finally {
    starting.value = false
  }
}

async function resumeCurrent(): Promise<void> {
  if (!currentSession.value) return
  resumeDialog.value = false
  router.push({ name: 'shootingSession', params: { id: currentSession.value.id } })
}

async function abandonCurrent(): Promise<void> {
  if (!currentSession.value) return
  abandoning.value = true
  try {
    await shootingSessionsService.abandon(currentSession.value.id)
    currentSession.value = null
    resumeDialog.value = false
  } finally {
    abandoning.value = false
  }
}

function openSummary(id: number): void {
  router.push({ name: 'shootingSessionSummary', params: { id } })
}

function goStats(): void {
  router.push({ name: 'shootingStats' })
}

onMounted(async () => {
  try {
    currentSession.value = await shootingSessionsService.current()
    if (currentSession.value) {
      resumeDialog.value = true
    }
  } catch {
    // ignore, resume is a nice-to-have
  }
  loadHistory()
})
</script>

<style scoped>
.w-full {
  width: 100%;
}

.home-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

.action-primary,
.action-secondary {
  min-height: 2.75rem;
  font-weight: 700;
}

.history-section {
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

.session-title {
  font-weight: 700;
  font-size: 0.9375rem;
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
</style>
