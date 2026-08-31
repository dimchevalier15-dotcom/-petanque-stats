<template>
  <AppPage>
    <PageHeader :title="t('summary.title')" :back-to="{ name: 'home' }" />

    <section class="summary">
      <div class="hero-banner app-card" :class="winnerClass">
        <span class="winner-badge">{{ winnerText }}</span>
        <div class="score-row">
          <div class="score-team" :class="{ 'score-team--win': summary.winner === 'A' }">
            <span class="score-label">{{ teamALabel }}</span>
            <strong>{{ summary.scoreA }}</strong>
          </div>
          <span class="score-sep">–</span>
          <div class="score-team" :class="{ 'score-team--win': summary.winner === 'B' }">
            <span class="score-label">{{ teamBLabel }}</span>
            <strong>{{ summary.scoreB }}</strong>
          </div>
        </div>
        <span class="ends-meta">{{ t('summary.ends', { n: summary.ends }) }}</span>
      </div>

      <MatchSummaryEndGrid
        :players="summary.players"
        :end-indexes="summary.endIndexes ?? []"
        :canceled-end-indexes="summary.canceledEndIndexes ?? []"
      />

      <section v-if="!hasData" class="panel app-card notice">
        <p class="notice-title">{{ t('summary.empty.noTrackedDataTitle') }}</p>
        <p class="panel-hint">{{ t('summary.empty.noTrackedData') }}</p>
      </section>

      <template v-else>
        <section class="team-section">
          <MatchSummaryTeamBlock
            v-if="showTeamBlocks"
            team="A"
            :label="teamALabel"
            :players="teamA"
          />

          <MatchSummaryPlayerCard
            v-for="player in teamA"
            :key="player.playerId"
            :player="player"
          />
        </section>

        <section class="team-section">
          <MatchSummaryTeamBlock
            v-if="showTeamBlocks"
            team="B"
            :label="teamBLabel"
            :players="teamB"
          />

          <MatchSummaryPlayerCard
            v-for="player in teamB"
            :key="player.playerId"
            :player="player"
          />
        </section>
      </template>

      <section v-if="comparisonChart" class="panel app-card">
        <h3>{{ t('summary.sections.comparison') }}</h3>
        <p class="panel-hint">{{ t('summary.comparison.hint') }}</p>
        <div class="chart-box chart-comparison">
          <Chart type="bar" :data="comparisonChart.data" :options="comparisonChart.options" />
        </div>
      </section>

      <section v-if="contextSummary.length > 0" class="panel app-card context-panel">
        <h3>{{ t('context.summaryTitle') }}</h3>
        <ul class="context-list">
          <li v-for="line in contextSummary" :key="line">{{ line }}</li>
        </ul>
      </section>

      <section v-if="canManageValidation" class="panel app-card validation-panel">
        <h3>{{ t('validation.summaryTitle') }}</h3>
        <p class="panel-hint">{{ t('validation.summaryHint') }}</p>
        <div class="validation-actions">
          <Button
            v-if="myHasValidatedMatch !== true"
            :label="t('validation.accept')"
            icon="pi pi-check"
            severity="success"
            :loading="validationLoading && validationTarget === true"
            :disabled="validationLoading"
            class="w-full"
            @click="updateMyValidation(true)"
          />
          <Button
            v-if="myHasValidatedMatch !== false"
            :label="t('validation.reject')"
            icon="pi pi-times"
            severity="danger"
            outlined
            :loading="validationLoading && validationTarget === false"
            :disabled="validationLoading"
            class="w-full"
            @click="updateMyValidation(false)"
          />
        </div>
      </section>

      <div class="app-actions">
        <Button
          :label="contextActionLabel"
          severity="secondary"
          outlined
          class="w-full"
          @click="openContext"
        />
        <Button class="w-full" :label="t('summary.actions.backHome')" @click="goHome" />
      </div>
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import MatchSummaryEndGrid from '../components/match/MatchSummaryEndGrid.vue'
import MatchSummaryPlayerCard from '../components/match/MatchSummaryPlayerCard.vue'
import MatchSummaryTeamBlock from '../components/match/MatchSummaryTeamBlock.vue'
import type { MatchSummary, MatchSummaryPlayer } from '../models/MatchSummary'
import { formatPlayedAt, hasMatchContextData, type MatchContext } from '../models/MatchContext'
import { competitionLabel, type Competition } from '../models/Competition'
import { useMatchContextOptions } from '../composables/useMatchContextOptions'
import { useMatchTeamLabels } from '../composables/useMatchTeamLabels'
import {
  buildPlayerComparisonChart,
  hasTrackedData,
} from '../composables/useMatchSummaryCharts'
import { matchesService } from '../services/matches'
import { competitionsService } from '../services/competitions'
import { useAuthStore } from '../stores/auth'

const { t } = useI18n()
const { natureOptions, competitionStageOptions, terrainTypeOptions } = useMatchContextOptions(t)
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const matchId = Number(route.params.id)
const summary = ref<MatchSummary>({ matchId, scoreA: 0, scoreB: 0, winner: 'A', ends: 0, players: [] })
const context = ref<MatchContext | null>(null)
const competitions = ref<Competition[]>([])
const myMatchPlayerId = ref<number | null>(null)
const myHasValidatedMatch = ref<boolean | null>(null)
const validationLoading = ref(false)
const validationTarget = ref<boolean | null>(null)
const { teamALabel, teamBLabel, labelForTeam } = useMatchTeamLabels(context, t)

const teamA = computed<MatchSummaryPlayer[]>(() => summary.value.players.filter((p) => p.team === 'A'))
const teamB = computed<MatchSummaryPlayer[]>(() => summary.value.players.filter((p) => p.team === 'B'))
const isHeadToHead = computed(
  () =>
    summary.value.type === 'tete_a_tete' ||
    (summary.value.type === undefined && teamA.value.length <= 1 && teamB.value.length <= 1),
)
const showTeamBlocks = computed(() => !isHeadToHead.value)

const hasData = computed(() => hasTrackedData(summary.value))

const comparisonChart = computed(() => buildPlayerComparisonChart(summary.value.players, t))

const contextActionLabel = computed(() =>
  context.value && hasMatchContextData(context.value)
    ? t('context.actions.edit')
    : t('context.actions.add'),
)

const contextSummary = computed<string[]>(() => {
  if (!context.value) {
    return []
  }
  const lines: string[] = []
  if (context.value.playedAt) {
    lines.push(`${t('context.fields.playedAt')}: ${formatPlayedAt(context.value.playedAt)}`)
  }
  if (!hasMatchContextData(context.value)) {
    return lines
  }
  if (context.value.nature) {
    const nature = natureOptions.value.find((o) => o.value === context.value?.nature)
    if (nature) lines.push(`${t('context.fields.nature')}: ${nature.label}`)
  }
  if (context.value.competitionId) {
    const competition = competitions.value.find((item) => item.id === context.value?.competitionId)
    const label = competition ? competitionLabel(competition) : null
    if (label) lines.push(`${t('context.fields.competitionName')}: ${label}`)
  } else if (context.value.competitionName) {
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

const winnerText = computed(() => t('summary.winner', { team: labelForTeam(summary.value.winner) }))
const winnerClass = computed(() => (summary.value.winner === 'A' ? 'hero-a' : 'hero-b'))
const canManageValidation = computed(() => myMatchPlayerId.value !== null)

async function load() {
  if (!matchId) {
    router.replace({ name: 'home' })
    return
  }
  try {
    const [summaryData, contextData, competitionList] = await Promise.all([
      matchesService.getSummary(matchId),
      matchesService.getContext(matchId),
      competitionsService.list(),
    ])
    summary.value = summaryData
    context.value = contextData
    competitions.value = competitionList
    myMatchPlayerId.value = summaryData.myMatchPlayerId ?? null
    myHasValidatedMatch.value = summaryData.myHasValidatedMatch ?? null
  } catch {
    router.replace({ name: 'home' })
  }
}

async function updateMyValidation(validated: boolean) {
  if (myMatchPlayerId.value === null) {
    return
  }
  validationLoading.value = true
  validationTarget.value = validated
  try {
    await matchesService.updateValidation(myMatchPlayerId.value, validated)
    myHasValidatedMatch.value = validated
    if (auth.user) {
      const count = await matchesService.getPendingValidationCount()
      auth.user = { ...auth.user, pendingValidationCount: count }
    }
  } finally {
    validationLoading.value = false
    validationTarget.value = null
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
.summary {
  display: grid;
  gap: var(--app-space-md);
  min-width: 0;
}

.hero-banner {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-sm);
  text-align: center;
  border-width: 1px;
}

.hero-a {
  border-color: #bbf7d0;
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
}

.hero-b {
  border-color: #bfdbfe;
  background: linear-gradient(135deg, #eff6ff 0%, #eef2ff 100%);
}

.winner-badge {
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  opacity: 0.85;
}

.score-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--app-space-md);
}

.score-team {
  display: grid;
  gap: 0.125rem;
  min-width: 5rem;
  opacity: 0.75;
}

.score-team--win {
  opacity: 1;
}

.score-team strong {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.02em;
}

.score-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--app-text-muted);
}

.score-sep {
  font-size: 1.5rem;
  font-weight: 300;
  opacity: 0.45;
}

.ends-meta {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.panel {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.panel h3,
.panel h4 {
  margin: 0;
  font-size: 1rem;
}

.panel-hint {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.panel.notice {
  border-style: dashed;
  background: #fafafa;
}

.notice-title {
  margin: 0;
  font-weight: 700;
}

.chart-box {
  height: 180px;
}

.chart-comparison {
  height: 200px;
}

.team-section {
  display: grid;
  gap: var(--app-space-sm);
}

.context-panel {
  gap: 0.375rem;
}

.context-list {
  margin: 0;
  padding-left: 1rem;
  display: grid;
  gap: 0.25rem;
  font-size: 0.875rem;
}

.validation-panel {
  gap: var(--app-space-sm);
}

.validation-actions {
  display: grid;
  gap: var(--app-space-sm);
}

.w-full {
  width: 100%;
}
</style>
