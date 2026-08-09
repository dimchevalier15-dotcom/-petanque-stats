<template>
  <section class="summary">
    <h2>{{ t('summary.title') }}</h2>

    <div class="banner">
      <div class="winner" :class="winnerClass">{{ winnerText }}</div>
      <div class="final-score">{{ summary.scoreA }} - {{ summary.scoreB }}</div>
      <div class="ends">{{ t('summary.ends', { n: summary.ends }) }}</div>
    </div>

    <div class="teams">
      <div class="team">
        <h3>{{ teamALabel }}</h3>
        <div v-for="p in teamA" :key="p.playerId" class="player-block">
          <div class="player-header">{{ fullName(p) }}</div>
          <div class="line">
            <div class="counts counts-header">
              <Tag value="Moy" severity="secondary" class="counts-moy-header" />
              <Tag :value="'+2'" severity="secondary" />
              <Tag :value="'+1'" severity="secondary" />
              <Tag :value="'0'" severity="secondary" />
              <Tag :value="'-1'" severity="secondary" />
              <Tag :value="'-2'" severity="secondary" />
            </div>
          </div>
          <div class="line">
            <span class="label">{{ t('play.shots.point') }}</span>
            <Tag class="avg" :value="formatAvg(p.point?.average ?? 0)" :severity="avgSeverity(p.point?.average ?? 0)" />
            <div class="counts">
              <Tag :value="String(p.point?.p2 ?? 0)" severity="help" />
              <Tag :value="String(p.point?.p1 ?? 0)" severity="success" />
              <Tag :value="String(p.point?.p0 ?? 0)" severity="secondary" />
              <Tag :value="String(p.point?.m1 ?? 0)" severity="warn" />
              <Tag :value="String(p.point?.m2 ?? 0)" severity="danger" />
            </div>
          </div>
          <div class="line">
            <span class="label">{{ t('play.shots.tir') }}</span>
            <Tag class="avg" :value="formatAvg(p.tir?.average ?? 0)" :severity="avgSeverity(p.tir?.average ?? 0)" />
            <div class="counts">
              <Tag :value="String(p.tir?.p2 ?? 0)" severity="help" />
              <Tag :value="String(p.tir?.p1 ?? 0)" severity="success" />
              <Tag :value="String(p.tir?.p0 ?? 0)" severity="secondary" />
              <Tag :value="String(p.tir?.m1 ?? 0)" severity="warn" />
              <Tag :value="String(p.tir?.m2 ?? 0)" severity="danger" />
            </div>
          </div>
        </div>
      </div>

      <div class="team">
        <h3>{{ teamBLabel }}</h3>
        <div v-for="p in teamB" :key="p.playerId" class="player-block">
          <div class="player-header">{{ fullName(p) }}</div>
          <div class="line">
            <div class="counts counts-header">
              <Tag value="Moy" severity="secondary" class="counts-moy-header"/>
              <Tag :value="'+2'" severity="secondary" />
              <Tag :value="'+1'" severity="secondary" />
              <Tag :value="'0'" severity="secondary" />
              <Tag :value="'-1'" severity="secondary" />
              <Tag :value="'-2'" severity="secondary" />
            </div>
          </div>
          <div class="line">
            <span class="label">{{ t('play.shots.point') }}</span>
            <Tag class="avg" :value="formatAvg(p.point?.average ?? 0)" :severity="avgSeverity(p.point?.average ?? 0)" />
            <div class="counts">
              <Tag :value="String(p.point?.p2 ?? 0)" severity="help" />
              <Tag :value="String(p.point?.p1 ?? 0)" severity="success" />
              <Tag :value="String(p.point?.p0 ?? 0)" severity="secondary" />
              <Tag :value="String(p.point?.m1 ?? 0)" severity="warn" />
              <Tag :value="String(p.point?.m2 ?? 0)" severity="danger" />
            </div>
          </div>
          <div class="line">
            <span class="label">{{ t('play.shots.tir') }}</span>
            <Tag class="avg" :value="formatAvg(p.tir?.average ?? 0)" :severity="avgSeverity(p.tir?.average ?? 0)" />
            <div class="counts">
              <Tag :value="String(p.tir?.p2 ?? 0)" severity="help" />
              <Tag :value="String(p.tir?.p1 ?? 0)" severity="success" />
              <Tag :value="String(p.tir?.p0 ?? 0)" severity="secondary" />
              <Tag :value="String(p.tir?.m1 ?? 0)" severity="warn" />
              <Tag :value="String(p.tir?.m2 ?? 0)" severity="danger" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="contextSummary.length > 0" class="context-summary">
      <h3>{{ t('context.summaryTitle') }}</h3>
      <ul>
        <li v-for="line in contextSummary" :key="line">{{ line }}</li>
      </ul>
    </div>

    <div class="actions">
      <Button
        :label="contextActionLabel"
        severity="secondary"
        outlined
        @click="openContext"
      />
      <Button :label="t('summary.actions.backHome')" @click="goHome" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import type { MatchSummary, MatchSummaryPlayer } from '../models/MatchSummary'
import type { MatchContext } from '../models/MatchContext'
import { hasMatchContextData } from '../models/MatchContext'
import { useMatchContextOptions } from '../composables/useMatchContextOptions'
import { matchesService } from '../services/matches'

const { t } = useI18n()
const { natureOptions, competitionStageOptions, terrainTypeOptions } = useMatchContextOptions(t)
const route = useRoute()
const router = useRouter()

const matchId = Number(route.params.id)
const summary = ref<MatchSummary>({ matchId, scoreA: 0, scoreB: 0, winner: 'A', ends: 0, players: [] })
const context = ref<MatchContext | null>(null)

const teamA = computed<MatchSummaryPlayer[]>(() => summary.value.players.filter((p) => p.team === 'A'))
const teamB = computed<MatchSummaryPlayer[]>(() => summary.value.players.filter((p) => p.team === 'B'))

const teamALabel = computed(() => context.value?.teamAName?.trim() || t('matches.teams.a'))
const teamBLabel = computed(() => context.value?.teamBName?.trim() || t('matches.teams.b'))

const contextActionLabel = computed(() =>
  context.value && hasMatchContextData(context.value)
    ? t('context.actions.edit')
    : t('context.actions.add'),
)

const contextSummary = computed<string[]>(() => {
  if (!context.value || !hasMatchContextData(context.value)) {
    return []
  }
  const lines: string[] = []
  if (context.value.nature) {
    const nature = natureOptions.value.find((o) => o.value === context.value?.nature)
    if (nature) lines.push(`${t('context.fields.nature')}: ${nature.label}`)
  }
  if (context.value.competitionName) {
    lines.push(`${t('context.fields.competitionName')}: ${context.value.competitionName}`)
  }
  if (context.value.competitionStage) {
    const stage = competitionStageOptions.value.find((o) => o.value === context.value?.competitionStage)
    if (stage) lines.push(`${t('context.fields.competitionStage')}: ${stage.label}`)
  }
  if (context.value.terrainType) {
    const terrain = terrainTypeOptions.value.find((o) => o.value === context.value?.terrainType)
    if (terrain) lines.push(`${t('context.fields.terrainType')}: ${terrain.label}`)
  }
  if (context.value.comment) {
    lines.push(`${t('context.fields.comment')}: ${context.value.comment}`)
  }
  return lines
})

const winnerText = computed(() => (summary.value.winner === 'A' ? t('summary.winnerA') : t('summary.winnerB')))
const winnerClass = computed(() => (summary.value.winner === 'A' ? 'win-a' : 'win-b'))

function formatAvg(n: number): string {
  return (n ?? 0).toFixed(2)
}
function avgSeverity(n?: number): 'danger' | 'warn' | 'secondary' | 'success' | 'help' | undefined {
  const v = n ?? 0
  if (v >= 1) return 'help'
  if (v > 0) return 'success'
  if (v === 0) return 'secondary'
  if (v > -1) return 'warn'
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
    const [summaryData, contextData] = await Promise.all([
      matchesService.getSummary(matchId),
      matchesService.getContext(matchId),
    ])
    summary.value = summaryData
    context.value = contextData
  } catch {
    router.replace({ name: 'home' })
  }
}

function openContext() {
  router.push({ name: 'matchContext', params: { id: matchId } })
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
.final-score { font-size: 1.5rem; font-weight: 800; }
.ends { opacity: 0.8; }
.teams { display: grid; gap: 1rem; grid-template-columns: 1fr; }
.team { border: 1px solid #eee; border-radius: 10px; padding: 0.5rem; display: grid; gap: 0.5rem; }
.player-block { border-top: 1px solid #f0f0f0; padding-top: 0.5rem; }
.player-block:first-of-type { border-top: none; padding-top: 0; }
.player-header { font-weight: 700; margin-bottom: 0.25rem; }
.line { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; padding: 0.125rem 0; }
.line .label { font-size: 0.75rem; text-transform: uppercase; opacity: 0.8; min-width: 48px; }
.avg :deep(.p-tag) { font-weight: 700; }
:deep(.p-tag) { width: 38px; }
.counts { display: flex; align-items: center; gap: 0.25rem; flex-wrap: wrap; }
.counts-header { margin-left: 56px; }
.counts-moy-header { margin-right: 4px; }
.actions { display: grid; gap: 0.5rem; margin-top: 0.5rem; }
.context-summary { border: 1px solid #eee; border-radius: 10px; padding: 0.5rem 0.75rem; display: grid; gap: 0.25rem; }
.context-summary h3 { margin: 0; font-size: 0.95rem; }
.context-summary ul { margin: 0; padding-left: 1rem; display: grid; gap: 0.125rem; font-size: 0.9rem; }
</style>
