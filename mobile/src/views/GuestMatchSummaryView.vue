<template>
  <AppPage>
    <PageHeader :title="t('summary.title')" :back-to="{ name: 'login' }" />

    <section v-if="summary" class="summary">
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

      <MatchSummaryTabNav v-if="hasTacticsInsights" v-model="activeTab" />

      <template v-if="activeTab === 'overview' || !hasTacticsInsights">
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

      <section class="save-panel app-card">
        <h3>{{ t('guest.summary.saveTitle') }}</h3>
        <p class="panel-hint">{{ t('guest.summary.saveHint') }}</p>
        <div class="app-actions">
          <Button
            class="w-full"
            :label="t('guest.summary.createAccount')"
            icon="pi pi-user-plus"
            @click="goRegister"
          />
          <Button
            class="w-full"
            :label="t('guest.summary.login')"
            severity="secondary"
            outlined
            icon="pi pi-sign-in"
            @click="goLogin"
          />
          <Button
            class="w-full back-to-login-btn"
            :label="t('guest.summary.backToLogin')"
            severity="secondary"
            text
            icon="pi pi-home"
            @click="goBackToLogin"
          />
        </div>
      </section>
      </template>

      <template v-else-if="activeTab === 'tactics' && insights">
        <MatchAdvancedInsightsPanel :insights="insights" :context="teamNames" />
      </template>
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import MatchSummaryEndGrid from '../components/match/MatchSummaryEndGrid.vue'
import MatchSummaryPlayerCard from '../components/match/MatchSummaryPlayerCard.vue'
import MatchSummaryTeamBlock from '../components/match/MatchSummaryTeamBlock.vue'
import MatchAdvancedInsightsPanel from '../components/match/MatchAdvancedInsightsPanel.vue'
import MatchSummaryTabNav, { type MatchSummaryTab } from '../components/match/MatchSummaryTabNav.vue'
import type { MatchSummary } from '../models/MatchSummary'
import type { MatchInsights } from '../models/MatchInsights'
import { useMatchTeamLabels } from '../composables/useMatchTeamLabels'
import {
  buildPlayerComparisonChart,
  hasTrackedData,
} from '../composables/useMatchSummaryCharts'
import { clearMatchDraft, loadMatchDraft } from '../services/matchDraftStorage'
import { buildLocalMatchSummary } from '../utils/buildLocalMatchSummary'
import { buildLocalMatchInsights } from '../utils/buildLocalMatchInsights'
import { hasSaveGuestMatchQuery, saveGuestMatchQuery } from '../utils/guestMatchQuery'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const draftId = Number(route.params.id)
const activeTab = ref<MatchSummaryTab>('overview')
const teamNames = ref({ teamAName: null as string | null, teamBName: null as string | null })
const { teamALabel, teamBLabel } = useMatchTeamLabels(teamNames, t)

const summary = ref<MatchSummary | null>(null)
const insights = ref<MatchInsights | null>(null)

const teamA = computed(() => summary.value?.players.filter((p) => p.team === 'A') ?? [])
const teamB = computed(() => summary.value?.players.filter((p) => p.team === 'B') ?? [])
const isHeadToHead = computed(
  () =>
    summary.value?.type === 'tete_a_tete' ||
    (summary.value?.type === undefined &&
      teamA.value.length <= 1 &&
      teamB.value.length <= 1),
)
const showTeamBlocks = computed(() => !isHeadToHead.value)
const hasData = computed(() => summary.value !== null && hasTrackedData(summary.value))
const hasTacticsInsights = computed(() => insights.value?.status === 'ok')
const comparisonChart = computed(() =>
  summary.value ? buildPlayerComparisonChart(summary.value.players, t) : null,
)

const winnerText = computed(() =>
  summary.value ? t('summary.winner', { team: summary.value.winner === 'A' ? teamALabel.value : teamBLabel.value }) : '',
)
const winnerClass = computed(() => (summary.value?.winner === 'A' ? 'hero-a' : 'hero-b'))

function goRegister(): void {
  router.push({ name: 'register', query: saveGuestMatchQuery() })
}

function goLogin(): void {
  router.push({ name: 'login', query: saveGuestMatchQuery() })
}

function goBackToLogin(): void {
  router.push({ name: 'login' })
}

onBeforeRouteLeave((to) => {
  if (to.name === 'login' && !hasSaveGuestMatchQuery(to.query.saveGuestMatch)) {
    clearMatchDraft({ guest: true })
  }
})

onMounted(() => {
  const draft = loadMatchDraft(null, { guest: true })
  if (!draft || draft.id !== draftId) {
    void router.replace({ name: 'login' })
    return
  }
  teamNames.value = { teamAName: draft.teamAName, teamBName: draft.teamBName }
  summary.value = buildLocalMatchSummary(draft)
  const localInsights = buildLocalMatchInsights({
    type: draft.type,
    teamA: draft.teamA,
    teamB: draft.teamB,
    trackedPlayers: draft.trackedPlayers,
    ends: draft.ends,
  })
  insights.value = localInsights
})
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

.save-panel {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
  border-color: var(--p-primary-200, #bfdbfe);
  background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
}

.w-full {
  width: 100%;
}

.back-to-login-btn {
  margin-top: calc(-1 * var(--app-space-xs));
}
</style>
