<template>
  <AppPage>
    <PageHeader
      :title="playerTitle"
      :subtitle="t('coach.player.historySubtitle')"
      :back-to="{ name: 'coachPlayer', params: { id: playerId }, query: { name: playerTitle } }"
    />

    <EmptyState
      v-if="items.length === 0 && !loading"
      :title="t('history.empty')"
      icon="pi pi-inbox"
    />

    <ul v-else class="list">
      <li v-for="m in items" :key="m.id">
        <button type="button" class="match-card app-card" @click="open(m.id)">
          <div class="head">
            <span class="date">{{ formatDate(m.date) }}</span>
            <Tag
              v-if="m.victory !== null"
              :value="m.victory ? t('history.victory') : t('history.defeat')"
              :severity="m.victory ? 'success' : 'danger'"
            />
          </div>
          <div class="type">{{ typeLabel(m.type) }}</div>
          <div v-if="hasContext(m)" class="context-row">
            <Tag v-if="contextLabels(m).nature" :value="contextLabels(m).nature" severity="secondary" />
            <span v-if="contextLabels(m).competition" class="context-competition">
              {{ contextLabels(m).competition }}
            </span>
            <Tag v-if="contextLabels(m).stage" :value="contextLabels(m).stage" severity="info" />
          </div>
          <div class="score">{{ m.scoreA }} - {{ m.scoreB }}</div>
        </button>
      </li>
    </ul>

    <div v-if="canLoadMore" class="app-actions">
      <Button :label="t('history.loadMore')" :loading="loading" outlined @click="loadMore" />
    </div>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import { useMatchHistoryContext } from '../composables/useMatchHistoryContext'
import type { MatchHistoryItem } from '../models/MatchHistory'
import { coachService } from '../services/coach'

const { t, d } = useI18n()
const route = useRoute()
const router = useRouter()
const { contextLabels, hasContext } = useMatchHistoryContext(t)

const playerId = Number(route.params.id)
const playerTitle = ref(String(route.query.name ?? t('coach.player.title')))

const items = ref<MatchHistoryItem[]>([])
const page = ref(1)
const pageSize = ref(20)
const total = ref(0)
const loading = ref(false)

const canLoadMore = computed(() => items.value.length < total.value)

function typeLabel(type: MatchHistoryItem['type']): string {
  switch (type) {
    case 'tete_a_tete':
      return t('matches.types.teteATete')
    case 'doublette':
      return t('matches.types.doublette')
    case 'triplette':
      return t('matches.types.triplette')
    default:
      return String(type)
  }
}

function formatDate(iso: string): string {
  try {
    return d(new Date(iso), 'short') as string
  } catch {
    return new Date(iso).toLocaleDateString()
  }
}

async function load(): Promise<void> {
  loading.value = true
  try {
    const res = await coachService.getPlayerHistory(playerId, page.value, pageSize.value)
    total.value = res.total
    const known = new Set(items.value.map((item) => item.id))
    const next = res.items.filter((item) => !known.has(item.id))
    items.value = [...items.value, ...next]
  } finally {
    loading.value = false
  }
}

function loadMore(): void {
  if (!canLoadMore.value || loading.value) return
  page.value += 1
  void load()
}

function open(id: number): void {
  router.push({ name: 'matchSummary', params: { id } })
}

onMounted(() => {
  void load()
})
</script>

<style scoped>
.list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: var(--app-space-sm);
}

.match-card {
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

.match-card:active {
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

.type {
  font-size: 0.875rem;
  color: var(--app-text-muted);
}

.context-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.375rem;
}

.context-competition {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.score {
  font-weight: 800;
  font-size: 1.375rem;
  letter-spacing: -0.02em;
}

.app-actions {
  margin-top: var(--app-space-md);
  display: flex;
  justify-content: center;
}
</style>
