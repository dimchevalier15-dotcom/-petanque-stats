<template>
  <AppPage>
    <PageHeader :title="t(config.titleKey)" :subtitle="t(config.subtitleKey)" :back-to="{ name: 'trainingHome' }" />
    <div class="home-actions">
      <Button
        class="action-secondary"
        :label="t('rings.home.stats')"
        icon="pi pi-chart-bar"
        severity="secondary"
        outlined
        @click="goStats"
      />
      <Button
        class="action-secondary"
        :label="t('rings.home.rules')"
        icon="pi pi-book"
        severity="secondary"
        outlined
        @click="goRules"
      />
      <Button
        class="action-primary action-span"
        :label="t('rings.home.newSession')"
        icon="pi pi-play"
        :loading="starting"
        @click="startNewSession"
      />
    </div>

    <section class="history-section">
      <h3 class="section-label">{{ t('rings.home.historyTitle') }}</h3>

      <EmptyState v-if="items.length === 0 && !loading" :title="t('rings.home.empty')" icon="pi pi-circle" />

      <ul v-else class="list">
        <li v-for="s in items" :key="s.id">
          <button type="button" class="session-card app-card" @click="openSummary(s.id)">
            <span class="date">{{ formatShortDate(s.playedAt) }}</span>
            <Tag :value="t('rings.home.scoreOnMax', { score: s.totalScore, max: maxScore })" severity="info" />
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
      :header="t('rings.resume.title')"
      class="rings-dialog"
    >
      <div class="resume-content">
        <p>{{ t('rings.resume.message') }}</p>
        <div class="actions">
          <Button
            :label="t('rings.resume.abandon')"
            severity="secondary"
            outlined
            :loading="abandoning"
            @click="abandonCurrent"
          />
          <Button :label="t('rings.resume.continue')" icon="pi pi-arrow-right" @click="resumeCurrent" />
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
import PageHeader from '../components/layout/PageHeader.vue'
import { useDateFormat } from '../composables/useDateFormat'
import { clearRingsDraft } from '../composables/useRingsSessionPlay'
import {
  RINGS_MODE_CONFIG,
  ringsMaxSessionScore,
  type RingsMode,
  type RingsSessionHistoryItem,
  type RingsSessionStarted,
} from '../models/Rings'
import { ringsSessionsService } from '../services/ringsSessions'

const props = withDefaults(defineProps<{ mode?: RingsMode }>(), { mode: 'point' })

const { t } = useI18n()
const { formatShortDate } = useDateFormat()
const router = useRouter()

const config = computed(() => RINGS_MODE_CONFIG[props.mode])
const maxScore = computed(() => ringsMaxSessionScore(props.mode))

const items = ref<RingsSessionHistoryItem[]>([])
const page = ref(1)
const total = ref(0)
const loading = ref(false)
const starting = ref(false)
const abandoning = ref(false)
const resumeDialog = ref(false)
const currentSession = ref<RingsSessionStarted | null>(null)

const canLoadMore = computed(() => items.value.length < total.value)

async function loadHistory(): Promise<void> {
  loading.value = true
  try {
    const res = await ringsSessionsService.getHistory(props.mode, page.value)
    total.value = res.total
    const known = new Set(items.value.map((i) => i.id))
    items.value = [...items.value, ...res.items.filter((i) => !known.has(i.id))]
  } finally {
    loading.value = false
  }
}

function loadMore(): void {
  if (!canLoadMore.value || loading.value) return
  page.value += 1
  void loadHistory()
}

async function startNewSession(): Promise<void> {
  starting.value = true
  try {
    const session = await ringsSessionsService.start(props.mode)
    await router.push({ name: config.value.routes.session, params: { id: session.id } })
  } finally {
    starting.value = false
  }
}

function resumeCurrent(): void {
  if (!currentSession.value) return
  resumeDialog.value = false
  void router.push({ name: config.value.routes.session, params: { id: currentSession.value.id } })
}

async function abandonCurrent(): Promise<void> {
  if (!currentSession.value) return
  abandoning.value = true
  try {
    await ringsSessionsService.abandon(currentSession.value.id)
    clearRingsDraft(currentSession.value.id)
    currentSession.value = null
    resumeDialog.value = false
  } finally {
    abandoning.value = false
  }
}

function goRules(): void {
  void router.push({ name: config.value.routes.rules })
}

function goStats(): void {
  void router.push({ name: config.value.routes.stats })
}

function openSummary(id: number): void {
  void router.push({ name: config.value.routes.summary, params: { id } })
}

onMounted(async () => {
  try {
    currentSession.value = await ringsSessionsService.current(props.mode)
    resumeDialog.value = currentSession.value !== null
  } catch {
    // ignore
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
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--app-space-sm);
  border: none;
  cursor: pointer;
  font: inherit;
  color: inherit;
  transition: transform 0.12s ease;
}

.session-card:active {
  transform: scale(0.99);
}

.date {
  font-size: 0.875rem;
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
</style>
