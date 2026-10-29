<template>
  <AppPage>
    <PageHeader
      :title="t('jumpShot.home.title')"
      :subtitle="t('jumpShot.home.subtitle')"
      :back-to="{ name: 'trainingHome' }"
    >
      <template #actions>
        <Button
          icon="pi pi-cog"
          text
          rounded
          class="settings-btn"
          :aria-label="t('jumpShot.settings.open')"
          @click="settingsDialog = true"
        />
      </template>
    </PageHeader>

    <div class="home-actions">
      <Button
        class="action-secondary"
        :label="t('jumpShot.home.rules')"
        icon="pi pi-book"
        severity="secondary"
        outlined
        @click="goRules"
      />
      <Button
        class="action-secondary"
        :label="t('jumpShot.home.stats')"
        icon="pi pi-chart-bar"
        severity="secondary"
        outlined
        @click="goStats"
      />
      <Button
        class="action-primary action-span"
        :label="t('jumpShot.home.start')"
        icon="pi pi-play"
        :loading="starting"
        @click="startNewSession"
      />
    </div>

    <section class="history-section">
      <h3 class="section-label">{{ t('jumpShot.home.historyTitle') }}</h3>

      <EmptyState
        v-if="items.length === 0 && !loading"
        :title="t('jumpShot.home.empty')"
        icon="pi pi-flag"
      />

      <ul v-else class="list">
        <li v-for="s in items" :key="s.id">
          <button type="button" class="session-card app-card" @click="openSummary(s.id)">
            <div class="head">
              <span class="date">{{ formatShortDate(s.finishedAt) }}</span>
              <Tag :value="t('jumpShot.home.seriesTag', { n: s.seriesCount })" severity="success" />
            </div>
            <span class="session-meta">{{ t('jumpShot.home.throwsMeta', { n: s.totalThrows }) }}</span>
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
      :header="t('jumpShot.resume.title')"
      class="jump-shot-dialog"
    >
      <div class="resume-content">
        <p>{{ t('jumpShot.resume.message') }}</p>
        <div class="actions">
          <Button
            :label="t('jumpShot.resume.abandon')"
            severity="secondary"
            outlined
            :loading="abandoning"
            @click="abandonCurrent"
          />
          <Button :label="t('jumpShot.resume.continue')" icon="pi pi-arrow-right" @click="resumeCurrent" />
        </div>
      </div>
    </Dialog>

    <Dialog
      v-model:visible="settingsDialog"
      :modal="true"
      :header="t('jumpShot.settings.title')"
      class="jump-shot-dialog jump-shot-settings-dialog"
    >
      <div class="settings-content">
        <p class="settings-label">{{ t('jumpShot.settings.progressionMode') }}</p>
        <SelectButton
          v-model="progressionMode"
          :options="progressionModeOptions"
          option-label="label"
          option-value="value"
          :allow-empty="false"
        />
        <p class="settings-hint">{{ progressionModeHint }}</p>
        <Button :label="t('jumpShot.settings.close')" class="settings-close" @click="settingsDialog = false" />
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
import SelectButton from 'primevue/selectbutton'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import { useJumpShotProgressionMode } from '../composables/useJumpShotProgressionMode'
import { useDateFormat } from '../composables/useDateFormat'
import type { JumpShotSessionHistoryItem, JumpShotSessionStarted } from '../models/JumpShot'
import { jumpShotSessionsService } from '../services/jumpShotSessions'

const { t } = useI18n()
const { formatShortDate } = useDateFormat()
const router = useRouter()

const starting = ref(false)
const abandoning = ref(false)
const resumeDialog = ref(false)
const settingsDialog = ref(false)
const currentSession = ref<JumpShotSessionStarted | null>(null)
const { progressionMode } = useJumpShotProgressionMode()

const progressionModeOptions = computed(() => [
  { value: 'standard' as const, label: t('jumpShot.settings.standard') },
  { value: 'simplified' as const, label: t('jumpShot.settings.simplified') },
])

const progressionModeHint = computed(() =>
  progressionMode.value === 'simplified'
    ? t('jumpShot.settings.simplifiedHint')
    : t('jumpShot.settings.standardHint'),
)

const items = ref<JumpShotSessionHistoryItem[]>([])
const page = ref(1)
const pageSize = ref(20)
const total = ref(0)
const loading = ref(false)

const canLoadMore = computed(() => items.value.length < total.value)

function goRules(): void {
  void router.push({ name: 'jumpShotRules' })
}

function goStats(): void {
  void router.push({ name: 'jumpShotStats' })
}

async function loadHistory(): Promise<void> {
  loading.value = true
  try {
    const res = await jumpShotSessionsService.getHistory(page.value, pageSize.value)
    total.value = res.total
    const known = new Set(items.value.map((i) => i.id))
    const next = res.items.filter((i) => !known.has(i.id))
    items.value = [...items.value, ...next]
  } finally {
    loading.value = false
  }
}

function loadMore(): void {
  if (!canLoadMore.value || loading.value) {
    return
  }
  page.value += 1
  void loadHistory()
}

async function startNewSession(): Promise<void> {
  starting.value = true
  try {
    const session = await jumpShotSessionsService.start(progressionMode.value)
    await router.push({ name: 'jumpShotSession', params: { id: session.id } })
  } finally {
    starting.value = false
  }
}

function resumeCurrent(): void {
  if (!currentSession.value) {
    return
  }
  resumeDialog.value = false
  void router.push({ name: 'jumpShotSession', params: { id: currentSession.value.id } })
}

async function abandonCurrent(): Promise<void> {
  if (!currentSession.value) {
    return
  }
  abandoning.value = true
  try {
    await jumpShotSessionsService.abandon(currentSession.value.id)
    resumeDialog.value = false
    currentSession.value = null
  } finally {
    abandoning.value = false
  }
}

function openSummary(id: number): void {
  void router.push({ name: 'jumpShotSessionSummary', params: { id } })
}

onMounted(async () => {
  const current = await jumpShotSessionsService.current()
  if (current) {
    currentSession.value = current
    resumeDialog.value = true
  }
  void loadHistory()
})
</script>

<style scoped>
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

.action-span {
  grid-column: 1 / -1;
}

.history-section {
  display: grid;
  gap: var(--app-space-sm);
  margin-top: var(--app-space-md);
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
}

.head {
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
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.resume-content {
  display: grid;
  gap: var(--app-space-md);
}

.resume-content .actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

.settings-btn {
  color: var(--app-text);
}

.settings-content {
  display: grid;
  gap: var(--app-space-md);
}

.settings-label {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--app-text-muted);
}

.settings-hint {
  margin: 0;
  font-size: 0.8125rem;
  line-height: 1.45;
  color: var(--app-text-muted);
}

.settings-close {
  justify-self: stretch;
}

.jump-shot-settings-dialog :deep(.p-selectbutton) {
  display: grid;
  grid-template-columns: 1fr 1fr;
  width: 100%;
}

.jump-shot-settings-dialog :deep(.p-selectbutton .p-button) {
  justify-content: center;
}
</style>
