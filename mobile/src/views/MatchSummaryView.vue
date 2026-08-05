<template>
  <section class="summary">
    <h2>{{ t('summary.title') }}</h2>

    <div class="banner">
      <div class="winner" :class="winnerClass">
        {{ winnerText }}
      </div>
      <div class="final-score">{{ summary.scoreA }} - {{ summary.scoreB }}</div>
      <div class="ends">{{ t('summary.ends', { n: summary.ends }) }}</div>
    </div>

    <DataTable :value="summary.players" dataKey="playerId" size="small" class="table">
      <Column field="name" :header="t('summary.table.player')">
        <template #body="{ data }">
          <div class="player-name">{{ fullName(data) }}</div>
        </template>
      </Column>
      <Column field="average" :header="t('summary.table.avg')">
        <template #body="{ data }">
          <Tag :value="formatAvg(data.average)" :severity="avgSeverity(data.average)" />
        </template>
      </Column>
      <Column field="p2" header="+2">
        <template #body="{ data }">
          <Tag :value="String(data.p2)" severity="success" />
        </template>
      </Column>
      <Column field="p1" header="+1">
        <template #body="{ data }">
          <Tag :value="String(data.p1)" severity="success" />
        </template>
      </Column>
      <Column field="p0" header="0">
        <template #body="{ data }">
          <Tag :value="String(data.p0)" severity="secondary" />
        </template>
      </Column>
      <Column field="m1" header="-1">
        <template #body="{ data }">
          <Tag :value="String(data.m1)" severity="warn" />
        </template>
      </Column>
      <Column field="m2" header="-2">
        <template #body="{ data }">
          <Tag :value="String(data.m2)" severity="danger" />
        </template>
      </Column>
    </DataTable>

    <div class="actions">
      <Button :label="t('summary.actions.backHome')" @click="goHome" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import type { MatchSummary } from '../models/MatchSummary'
import { matchesService } from '../services/matches'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const matchId = Number(route.params.id)
const summary = ref<MatchSummary>({ matchId, scoreA: 0, scoreB: 0, winner: 'A', ends: 0, players: [] })

const winnerText = computed(() =>
  summary.value.winner === 'A' ? t('summary.winnerA') : t('summary.winnerB'),
)
const winnerClass = computed(() => (summary.value.winner === 'A' ? 'win-a' : 'win-b'))

function formatAvg(n: number): string {
  return n.toFixed(2)
}
function avgSeverity(n: number): 'danger' | 'warn' | 'secondary' | 'success' | 'help' | undefined {
  if (n >= 1) return 'help'
  if (n > 0) return 'success'
  if (n === 0) return 'secondary'
  if (n > -1) return 'warn'
  return 'danger'
}
function fullName(row: MatchSummary['players'][number]): string {
  const base = `${row.firstName} ${row.lastName}`.trim()
  return row.nickname ? `${row.nickname} (${base})` : base
}

async function load() {
  if (!matchId) {
    router.replace({ name: 'home' })
    return
  }
  try {
    summary.value = await matchesService.getSummary(matchId)
  } catch {
    router.replace({ name: 'home' })
  }
}

function goHome() {
  router.push({ name: 'home' })
}

onMounted(load)
</script>

<style scoped>
.summary { max-width: 560px; margin: 0.75rem auto 1.5rem; display: grid; gap: 0.75rem; }
.banner { display: grid; gap: 0.25rem; text-align: center; }
.winner { font-weight: 700; }
.win-a { color: #16a34a; }
.win-b { color: #2563eb; }
.final-score { font-size: 1.5rem; font-weight: 800; }
.ends { opacity: 0.8; }
.table :deep(tbody td) { vertical-align: middle; }
.player-name { font-weight: 600; }
.actions { display: grid; margin-top: 0.5rem; }
</style>
