<template>
  <AppPage :title="t('history.title')">
    <MatchHistoryFiltersPanel
      v-model:include-refused="includeRefused"
      v-model:nature-filter="natureFilter"
      v-model:competition-filter="competitionFilter"
      v-model:format-filter="formatFilter"
      v-model:date-from="dateFrom"
      v-model:date-to="dateTo"
      v-model:date-filter-enabled="dateFilterEnabled"
      :active-filter-count="activeFilterCount"
      :max-date="maxDate"
      :nature-filter-options="natureFilterOptions"
      :competition-filter-options="competitionFilterOptions"
      :format-filter-options="formatFilterOptions"
      @change="reload"
    />

    <EmptyState
      v-if="items.length === 0 && !loading"
      :title="emptyTitle"
      icon="pi pi-inbox"
    />

    <ul v-else class="list">
      <li v-for="m in items" :key="m.id">
        <button
          type="button"
          class="match-card app-card"
          :class="{ 'match-card--refused': m.refused }"
          @click="open(m.id)"
        >
          <div class="head">
            <span class="date">{{ formatDate(m.date) }}</span>
            <Tag
              v-if="m.refused"
              :value="t('history.refused')"
              severity="secondary"
              class="refused-tag"
            />
            <Tag
              v-else-if="m.victory !== null"
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
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import MatchHistoryFiltersPanel from '../components/history/MatchHistoryFiltersPanel.vue'
import { useMatchHistoryContext } from '../composables/useMatchHistoryContext'
import { useMatchHistoryFilters } from '../composables/useMatchHistoryFilters'
import { matchesService } from '../services/matches'
import { useImpersonationStore } from '../stores/impersonation'
import type { MatchHistoryItem, MatchHistoryPage } from '../models/MatchHistory'

const { t, d } = useI18n()
const router = useRouter()
const impersonation = useImpersonationStore()
const { contextLabels, hasContext } = useMatchHistoryContext(t)

const {
  natureFilter,
  competitionFilter,
  formatFilter,
  includeRefused,
  dateFrom,
  dateTo,
  maxDate,
  dateFilterEnabled,
  natureFilterOptions,
  competitionFilterOptions,
  formatFilterOptions,
  activeFilterCount,
  filterParams,
} = useMatchHistoryFilters()

const items = ref<MatchHistoryItem[]>([])
const page = ref(1)
const pageSize = ref(20)
const total = ref(0)
const loading = ref(false)

const canLoadMore = computed(() => items.value.length < total.value)

const emptyTitle = computed(() =>
  activeFilterCount.value > 0 ? t('history.emptyFiltered') : t('history.empty'),
)

function typeLabel(type: 'tete_a_tete' | 'doublette' | 'triplette'): string {
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

async function load() {
  loading.value = true
  try {
    const res: MatchHistoryPage = await matchesService.getHistory(page.value, pageSize.value, filterParams())
    total.value = res.total
    const known = new Set(items.value.map((i) => i.id))
    const next = res.items.filter((i) => !known.has(i.id))
    items.value = [...items.value, ...next]
  } finally {
    loading.value = false
  }
}

function reload() {
  items.value = []
  page.value = 1
  void load()
}

function loadMore() {
  if (!canLoadMore.value || loading.value) return
  page.value += 1
  void load()
}

function open(id: number) {
  router.push({ name: 'matchSummary', params: { id } })
}

onMounted(() => {
  void load()
})

watch(
  () => impersonation.player?.id ?? null,
  (next, prev) => {
    if (next !== prev) {
      reload()
    }
  },
)
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

.match-card--refused {
  opacity: 0.72;
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

.refused-tag :deep(.p-tag) {
  font-size: 0.6875rem;
  font-weight: 600;
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
</style>
