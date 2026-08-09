<template>
  <AppPage :title="t('history.title')">
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
            <Tag :value="m.victory ? t('history.victory') : t('history.defeat')" :severity="m.victory ? 'success' : 'danger'" />
          </div>
          <div class="type">{{ typeLabel(m.type) }}</div>
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
import { onMounted, ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import { matchesService } from '../services/matches'
import type { MatchHistoryItem, MatchHistoryPage } from '../models/MatchHistory'

const { t, d } = useI18n()
const router = useRouter()

const items = ref<MatchHistoryItem[]>([])
const page = ref(1)
const pageSize = ref(20)
const total = ref(0)
const loading = ref(false)

const canLoadMore = computed(() => items.value.length < total.value)

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
    const res: MatchHistoryPage = await matchesService.getHistory(page.value, pageSize.value)
    total.value = res.total
    const known = new Set(items.value.map((i) => i.id))
    const next = res.items.filter((i) => !known.has(i.id))
    items.value = [...items.value, ...next]
  } finally {
    loading.value = false
  }
}

function loadMore() {
  if (!canLoadMore.value || loading.value) return
  page.value += 1
  load()
}

function open(id: number) {
  router.push({ name: 'matchSummary', params: { id } })
}

onMounted(load)
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

.score {
  font-weight: 800;
  font-size: 1.375rem;
  letter-spacing: -0.02em;
}
</style>
