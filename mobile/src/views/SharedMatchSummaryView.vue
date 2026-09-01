<template>
  <section class="shared-summary-view">
    <header class="shared-summary-header">
      <div class="shared-summary-header-main">
        <h1>{{ t('summary.share.publicTitle') }}</h1>
      </div>
      <LanguageSwitcher class="shared-summary-lang-switcher" />
    </header>

    <div v-if="loading" class="shared-summary-state">
      <p>{{ t('summary.share.loading') }}</p>
    </div>

    <div v-else-if="notFound" class="shared-summary-state">
      <p>{{ t('summary.share.notFound') }}</p>
    </div>

    <section v-else-if="summary" class="summary">
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

      <LiveInstallAppPromo />
    </section>
  </section>
</template>

<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import Chart from 'primevue/chart'
import LanguageSwitcher from '../components/layout/LanguageSwitcher.vue'
import LiveInstallAppPromo from '../components/live/LiveInstallAppPromo.vue'
import MatchSummaryEndGrid from '../components/match/MatchSummaryEndGrid.vue'
import MatchSummaryPlayerCard from '../components/match/MatchSummaryPlayerCard.vue'
import MatchSummaryTeamBlock from '../components/match/MatchSummaryTeamBlock.vue'
import type { MatchSummary, MatchSummaryPlayer } from '../models/MatchSummary'
import { formatPlayedAt, hasMatchContextData, type MatchContext } from '../models/MatchContext'
import { useMatchContextOptions } from '../composables/useMatchContextOptions'
import { useMatchTeamLabels } from '../composables/useMatchTeamLabels'
import {
  buildPlayerComparisonChart,
  hasTrackedData,
} from '../composables/useMatchSummaryCharts'
import { sharedMatchesService } from '../services/sharedMatches'

const { t } = useI18n()
const { natureOptions, competitionStageOptions, terrainTypeOptions } = useMatchContextOptions(t)
const route = useRoute()
const uuid = String(route.params.uuid)

const loading = ref(true)
const notFound = ref(false)
const summary = ref<MatchSummary | null>(null)
const context = ref<MatchContext | null>(null)
const competitionLabel = ref<string | null>(null)
const { teamALabel, teamBLabel, labelForTeam } = useMatchTeamLabels(context, t)

const teamA = computed<MatchSummaryPlayer[]>(() => summary.value?.players.filter((p) => p.team === 'A') ?? [])
const teamB = computed<MatchSummaryPlayer[]>(() => summary.value?.players.filter((p) => p.team === 'B') ?? [])
const isHeadToHead = computed(
  () =>
    summary.value?.type === 'tete_a_tete' ||
    (summary.value?.type === undefined && teamA.value.length <= 1 && teamB.value.length <= 1),
)
const showTeamBlocks = computed(() => !isHeadToHead.value)
const hasData = computed(() => summary.value !== null && hasTrackedData(summary.value))
const comparisonChart = computed(() =>
  summary.value ? buildPlayerComparisonChart(summary.value.players, t) : null,
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
  if (competitionLabel.value) {
    lines.push(`${t('context.fields.competitionName')}: ${competitionLabel.value}`)
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

const winnerText = computed(() =>
  summary.value ? t('summary.winner', { team: labelForTeam(summary.value.winner) }) : '',
)
const winnerClass = computed(() => (summary.value?.winner === 'A' ? 'hero-a' : 'hero-b'))

onMounted(async () => {
  try {
    const recap = await sharedMatchesService.getPublic(uuid)
    summary.value = recap.summary
    context.value = recap.context
    competitionLabel.value = recap.competitionLabel
    notFound.value = false
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      notFound.value = true
      summary.value = null
      context.value = null
    }
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.shared-summary-view {
  max-width: var(--app-page-max);
  width: 100%;
  margin: 0 auto;
  padding: var(--app-space-sm) var(--app-space-sm) var(--app-space-lg);
  display: grid;
  gap: var(--app-space-sm);
}

.shared-summary-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--app-space-sm);
}

.shared-summary-header-main {
  min-width: 0;
  flex: 1;
}

.shared-summary-header h1 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.shared-summary-lang-switcher {
  flex-shrink: 0;
  opacity: 0.82;
  transform: scale(0.92);
  transform-origin: top right;
}

.shared-summary-lang-switcher :deep(.lang-switcher) {
  box-shadow: none;
  border-color: var(--app-border);
  background: rgba(255, 255, 255, 0.72);
}

.shared-summary-state {
  text-align: center;
  color: var(--app-text-muted);
  padding: 2rem 0;
}

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

.panel h3 {
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
</style>
