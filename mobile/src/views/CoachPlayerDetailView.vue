<template>
  <AppPage class="coach-player-page">
    <PageHeader
      :title="playerTitle"
      :show-back="true"
      :back-to="playersListBackTo"
      class="coach-player-header"
    />

    <nav class="coach-segments" role="tablist" :aria-label="t('coach.player.tabs.label')">
      <div class="coach-segments-track">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          role="tab"
          class="coach-segment"
          :class="{ 'coach-segment--active': activeTab === tab.id }"
          :aria-selected="activeTab === tab.id"
          @click="activeTab = tab.id"
        >
          <i :class="tab.icon" aria-hidden="true" />
          <span>{{ tab.label }}</span>
        </button>
      </div>
    </nav>

    <div v-show="activeTab === 'shooting'" class="coach-tab-panel">
      <ShootingStatsPanel :fetch-stats="fetchCoachPlayerShootingStats" class="coach-stats-panel" />
    </div>

    <div v-show="activeTab === 'training'" class="coach-tab-panel">
      <PointingStatsPanel :fetch-stats="fetchCoachPlayerPointingStats" class="coach-stats-panel" />
    </div>

    <div v-show="activeTab === 'matches'" class="coach-tab-panel">
      <div class="history-link-row">
        <RouterLink :to="historyLinkTo" class="history-link">
          <i class="pi pi-history" aria-hidden="true" />
          {{ t('coach.player.historyLink') }}
        </RouterLink>
      </div>
      <PlayerStatsPanel
        :fetch-stats="fetchCoachPlayerStats"
        :fetch-tactical-insights="fetchCoachPlayerTacticalInsights"
        :show-empty-actions="false"
        :initial-nature="initialNature"
        :initial-from="initialFrom"
        :initial-to="initialTo"
        class="coach-stats-panel"
        @stats-loaded="onStatsLoaded"
      />
    </div>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import PointingStatsPanel from '../components/pointing/PointingStatsPanel.vue'
import ShootingStatsPanel from '../components/shooting/ShootingStatsPanel.vue'
import PlayerStatsPanel from '../components/stats/PlayerStatsPanel.vue'
import { useCoachSpaceFromRoute } from '../composables/useCoachSpace'
import type { PlayerStatsFetcher } from '../composables/usePlayerStatsPanel'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'
import type { MatchNature } from '../models/MatchContext'
import type { PointingContextNature, PointingStats } from '../models/Pointing'
import type { PlayerStats } from '../models/PlayerStats'
import type { ShootingContextNature, ShootingStats } from '../models/Shooting'
import { coachService } from '../services/coach'

type CoachPlayerTab = 'shooting' | 'training' | 'matches'

const { t } = useI18n()
const route = useRoute()
const {
  selectionGlobaleId,
  playerListRouteName,
  playerHistoryRouteName,
} = useCoachSpaceFromRoute()

const playerId = Number(route.params.id)
const activeTab = ref<CoachPlayerTab>('shooting')

const tabs = computed(() => [
  { id: 'shooting' as const, icon: 'pi pi-bullseye', label: t('coach.player.tabs.shootingShort') },
  { id: 'training' as const, icon: 'pi pi-circle', label: t('coach.player.tabs.pointingShort') },
  { id: 'matches' as const, icon: 'pi pi-chart-line', label: t('coach.player.tabs.matchesShort') },
])

const coachSpaceQuery = computed(() =>
  selectionGlobaleId.value ? { selectionGlobaleId: selectionGlobaleId.value } : undefined,
)

const playersListBackTo = computed(() => {
  if (selectionGlobaleId.value) {
    return { name: playerListRouteName.value, params: { selectionId: selectionGlobaleId.value } }
  }
  return { name: playerListRouteName.value }
})

const historyLinkTo = computed(() => {
  const params: Record<string, string | number> = { id: playerId }
  if (selectionGlobaleId.value) {
    params.selectionId = selectionGlobaleId.value
  }
  return {
    name: playerHistoryRouteName.value,
    params,
    query: { name: playerTitle.value },
  }
})

const playerTitle = ref(String(route.query.name ?? t('coach.player.title')))

const initialNature = ((): MatchNature | 'all' => {
  const nature = route.query.nature
  if (nature === 'training' || nature === 'friendly' || nature === 'competition') {
    return nature
  }
  return 'all'
})()

const initialFrom = typeof route.query.from === 'string' ? route.query.from : undefined
const initialTo = typeof route.query.to === 'string' ? route.query.to : undefined

function fetchCoachPlayerShootingStats(
  range: StatsDateRangeParams,
  nature: ShootingContextNature | 'all',
): Promise<ShootingStats> {
  return coachService.getPlayerShootingStats(playerId, range, nature, coachSpaceQuery.value)
}

function fetchCoachPlayerPointingStats(
  range: StatsDateRangeParams,
  nature: PointingContextNature | 'all',
): Promise<PointingStats> {
  return coachService.getPlayerPointingStats(playerId, range, nature, coachSpaceQuery.value)
}

const fetchCoachPlayerStats: PlayerStatsFetcher = (range, nature, type, distance, competitionId) =>
  coachService.getPlayerStats(
    playerId,
    range,
    nature,
    type,
    distance,
    competitionId,
    coachSpaceQuery.value,
  )

const fetchCoachPlayerTacticalInsights = (
  range: Parameters<PlayerStatsFetcher>[0],
  nature: Parameters<PlayerStatsFetcher>[1],
  type: Parameters<PlayerStatsFetcher>[2],
  distance: Parameters<PlayerStatsFetcher>[3],
  competitionId: Parameters<PlayerStatsFetcher>[4],
) =>
  coachService.getPlayerTacticalInsights(
    playerId,
    range,
    nature,
    type,
    distance,
    competitionId,
    coachSpaceQuery.value,
  )

function onStatsLoaded(stats: PlayerStats): void {
  if (stats.displayName) {
    playerTitle.value = stats.displayName
  }
}
</script>

<style scoped>
.coach-player-page {
  min-width: 0;
  overflow-x: clip;
  gap: var(--app-space-sm);
}

.coach-player-header :deep(.page-header) {
  padding: var(--app-space-xs) var(--app-space-md);
  min-height: 2.75rem;
}

.coach-player-header :deep(.title) {
  font-size: 0.875rem;
}

.coach-segments {
  margin: 0;
  min-width: 0;
}

.coach-segments-track {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.1875rem;
  padding: 0.25rem;
  background: var(--app-surface-muted);
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-lg);
}

.coach-segment {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.125rem;
  min-width: 0;
  min-height: 2.75rem;
  padding: 0.375rem 0.125rem;
  border: none;
  border-radius: calc(var(--app-radius-lg) - 0.25rem);
  background: transparent;
  color: var(--app-text-muted);
  font-size: 0.625rem;
  font-weight: 600;
  line-height: 1.15;
  text-align: center;
  cursor: pointer;
}

.coach-segment span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 100%;
}

.coach-segment .pi {
  font-size: 0.875rem;
  flex-shrink: 0;
}

.coach-segment--active {
  background: var(--app-surface);
  color: var(--app-primary);
  box-shadow: var(--app-shadow-sm);
}

.coach-tab-panel {
  min-width: 0;
}

.coach-stats-panel :deep(.hero-score .p-tag) {
  font-size: 1.25rem !important;
  padding: 0.25rem 0.5rem !important;
}

.coach-stats-panel :deep(.kpi-value) {
  font-size: 1.125rem;
}

.coach-stats-panel :deep(.kpi-label) {
  font-size: 0.625rem;
}

.coach-stats-panel :deep(.heatmap) {
  grid-template-columns: minmax(3.25rem, 1fr) repeat(4, minmax(0, 1fr));
  gap: 0.25rem;
}

.coach-stats-panel :deep(.heatmap-col-label),
.coach-stats-panel :deep(.heatmap-row-label) {
  font-size: 0.5625rem;
}

.coach-stats-panel :deep(.cell-score) {
  font-size: 0.75rem;
}

.history-link-row {
  display: flex;
  justify-content: flex-end;
  margin-bottom: var(--app-space-xs);
}

.history-link {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--app-primary);
  text-decoration: none;
}

@media (min-width: 420px) {
  .coach-segment {
    flex-direction: row;
    gap: 0.3125rem;
    font-size: 0.6875rem;
    padding: 0.5rem 0.25rem;
  }
}
</style>
