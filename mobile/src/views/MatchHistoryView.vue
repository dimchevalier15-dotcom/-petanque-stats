<template>
  <section class="history">
    <h2>{{ t('history.title') }}</h2>

    <div v-if="items.length === 0 && !loading" class="empty">{{ t('history.empty') }}</div>

    <ul class="list">
      <li v-for="m in items" :key="m.id">
        <Button class="card" @click="open(m.id)">
          <div class="head">
            <span class="date">{{ formatDate(m.date) }}</span>
            <Tag :value="m.victory ? t('history.victory') : t('history.defeat')" :severity="m.victory ? 'success' : 'danger'" />
          </div>
          <div class="type">{{ typeLabel(m.type) }}</div>
          <div class="score">{{ m.scoreA }} - {{ m.scoreB }}</div>
        </Button>
      </li>
    </ul>

    <div class="actions" v-if="canLoadMore">
      <Button :label="t('history.loadMore')" :loading="loading" @click="loadMore" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
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
  // Use i18n date formatting if configured, fallback to locale string
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
    // Append items (avoid duplicates if any)
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
.history { max-width: 560px; margin: 0.75rem auto 1.5rem; display: grid; gap: 0.75rem; }
.list { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.5rem; }
.card { width: 100%; display: grid; gap: 0.25rem; justify-items: start; border: 1px solid #eee; border-radius: 10px; padding: 0.5rem 0.75rem; text-align: left; }
.head { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; }
.date { opacity: 0.8; font-size: 0.9rem; }
.type { opacity: 0.9; }
.score { font-weight: 800; font-size: 1.25rem; }
.empty { opacity: 0.7; text-align: center; padding: 1rem 0; }
.actions { display: grid; justify-items: center; margin-top: 0.25rem; }
</style>
