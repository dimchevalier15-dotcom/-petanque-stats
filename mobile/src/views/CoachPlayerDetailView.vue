<template>
  <AppPage class="coach-player-page">
    <PageHeader
      :title="playerTitle"
      :show-back="true"
      :back-to="playersListBackTo"
      class="coach-player-header"
    />

    <nav class="coach-segments" role="tablist" :aria-label="t('coach.player.tabs.label')">
      <div class="coach-segments-track coach-segments-track--main">
        <button
          v-for="tab in mainTabs"
          :key="tab.id"
          type="button"
          role="tab"
          class="coach-segment"
          :class="{ 'coach-segment--active': activeMainTab === tab.id }"
          :aria-selected="activeMainTab === tab.id"
          @click="activeMainTab = tab.id"
        >
          <i :class="tab.icon" aria-hidden="true" />
          <span>{{ tab.label }}</span>
        </button>
      </div>
    </nav>

    <nav
      v-if="activeSubTabs.length > 0"
      class="coach-subsegments"
      role="tablist"
      :aria-label="t('coach.player.subTabs.label')"
    >
      <div class="coach-subsegments-track">
        <button
          v-for="sub in activeSubTabs"
          :key="sub.id"
          type="button"
          role="tab"
          class="coach-subsegment"
          :class="{ 'coach-subsegment--active': activeSubTab === sub.id }"
          :aria-selected="activeSubTab === sub.id"
          @click="selectSubTab(sub.id)"
        >
          {{ sub.label }}
        </button>
      </div>
    </nav>

    <div v-show="activeMainTab === 'exercises' && activeSubTab === 'jumpShot'" class="coach-tab-panel">
      <JumpShotStatsPanel :fetch-stats="fetchCoachPlayerJumpShotStats" class="coach-stats-panel" />
    </div>

    <div v-show="activeMainTab === 'exercises' && activeSubTab === 'ringsPoint'" class="coach-tab-panel">
      <RingsStatsPanel :fetch-stats="fetchCoachPlayerRingsPointStats" mode="point" class="coach-stats-panel" />
    </div>

    <div v-show="activeMainTab === 'exercises' && activeSubTab === 'ringsShooting'" class="coach-tab-panel">
      <RingsStatsPanel
        :fetch-stats="fetchCoachPlayerRingsShootingStats"
        mode="shooting"
        class="coach-stats-panel"
      />
    </div>

    <div v-show="activeMainTab === 'precision' && activeSubTab === 'shooting'" class="coach-tab-panel">
      <ShootingStatsPanel :fetch-stats="fetchCoachPlayerShootingStats" class="coach-stats-panel" />
    </div>

    <div v-show="activeMainTab === 'precision' && activeSubTab === 'pointing'" class="coach-tab-panel">
      <PointingStatsPanel :fetch-stats="fetchCoachPlayerPointingStats" class="coach-stats-panel" />
    </div>

    <div v-show="activeMainTab === 'matches'" class="coach-tab-panel">
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
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import JumpShotStatsPanel from '../components/jumpShot/JumpShotStatsPanel.vue'
import PointingStatsPanel from '../components/pointing/PointingStatsPanel.vue'
import RingsStatsPanel from '../components/rings/RingsStatsPanel.vue'
import ShootingStatsPanel from '../components/shooting/ShootingStatsPanel.vue'
import PlayerStatsPanel from '../components/stats/PlayerStatsPanel.vue'
import { useCoachSpaceFromRoute } from '../composables/useCoachSpace'
import type { PlayerStatsFetcher } from '../composables/usePlayerStatsPanel'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'
import type { MatchNature } from '../models/MatchContext'
import type { JumpShotStats } from '../models/JumpShot'
import type { PointingContextNature, PointingStats } from '../models/Pointing'
import type { PlayerStats } from '../models/PlayerStats'
import type { RingsStats } from '../models/Rings'
import type { ShootingContextNature, ShootingStats } from '../models/Shooting'
import { coachService } from '../services/coach'

type CoachMainTab = 'exercises' | 'precision' | 'matches'
type ExerciseSubTab = 'jumpShot' | 'ringsPoint' | 'ringsShooting'
type PrecisionSubTab = 'shooting' | 'pointing'
type CoachSubTab = ExerciseSubTab | PrecisionSubTab

const { t } = useI18n()
const route = useRoute()
const { selectionGlobaleId, playerListRouteName, playerHistoryRouteName } = useCoachSpaceFromRoute()

const playerId = Number(route.params.id)
const activeMainTab = ref<CoachMainTab>('precision')
const exerciseSubTab = ref<ExerciseSubTab>('jumpShot')
const precisionSubTab = ref<PrecisionSubTab>('shooting')

const mainTabs = computed(() => [
  { id: 'exercises' as const, icon: 'pi pi-flag', label: t('coach.player.tabs.exercisesShort') },
  { id: 'precision' as const, icon: 'pi pi-bullseye', label: t('coach.player.tabs.precisionShort') },
  { id: 'matches' as const, icon: 'pi pi-chart-line', label: t('coach.player.tabs.matchesShort') },
])

const exerciseSubTabs = computed(() => [
  { id: 'jumpShot' as const, label: t('coach.player.subTabs.jumpShot') },
  { id: 'ringsPoint' as const, label: t('coach.player.subTabs.ringsPoint') },
  { id: 'ringsShooting' as const, label: t('coach.player.subTabs.ringsShooting') },
])

const precisionSubTabs = computed(() => [
  { id: 'shooting' as const, label: t('coach.player.subTabs.shooting') },
  { id: 'pointing' as const, label: t('coach.player.subTabs.pointing') },
])

const activeSubTabs = computed(() => {
  if (activeMainTab.value === 'exercises') return exerciseSubTabs.value
  if (activeMainTab.value === 'precision') return precisionSubTabs.value
  return []
})

const activeSubTab = computed<CoachSubTab | null>(() => {
  if (activeMainTab.value === 'exercises') return exerciseSubTab.value
  if (activeMainTab.value === 'precision') return precisionSubTab.value
  return null
})

function selectSubTab(id: CoachSubTab): void {
  if (activeMainTab.value === 'exercises') {
    exerciseSubTab.value = id as ExerciseSubTab
    return
  }
  if (activeMainTab.value === 'precision') {
    precisionSubTab.value = id as PrecisionSubTab
  }
}

watch(activeMainTab, (tab) => {
  if (tab === 'exercises' && !exerciseSubTabs.value.some((s) => s.id === exerciseSubTab.value)) {
    exerciseSubTab.value = 'jumpShot'
  }
  if (tab === 'precision' && !precisionSubTabs.value.some((s) => s.id === precisionSubTab.value)) {
    precisionSubTab.value = 'shooting'
  }
})

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

function fetchCoachPlayerJumpShotStats(range: StatsDateRangeParams): Promise<JumpShotStats> {
  return coachService.getPlayerJumpShotStats(playerId, range, coachSpaceQuery.value)
}

function fetchCoachPlayerRingsPointStats(range: StatsDateRangeParams): Promise<RingsStats> {
  return coachService.getPlayerRingsStats(playerId, range, 'point', coachSpaceQuery.value)
}

function fetchCoachPlayerRingsShootingStats(range: StatsDateRangeParams): Promise<RingsStats> {
  return coachService.getPlayerRingsStats(playerId, range, 'shooting', coachSpaceQuery.value)
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
  gap: 0.1875rem;
  padding: 0.25rem;
  background: var(--app-surface-muted);
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-lg);
}

.coach-segments-track--main {
  grid-template-columns: repeat(3, minmax(0, 1fr));
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

.coach-subsegments {
  min-width: 0;
}

.coach-subsegments-track {
  display: flex;
  gap: 0.375rem;
  overflow-x: auto;
  padding: 0.125rem 0.125rem 0.25rem;
  -webkit-overflow-scrolling: touch;
}

.coach-subsegment {
  flex: 0 0 auto;
  min-height: 2rem;
  padding: 0.375rem 0.75rem;
  border: 1px solid var(--app-border);
  border-radius: 999px;
  background: var(--app-surface);
  color: var(--app-text-muted);
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
}

.coach-subsegment--active {
  border-color: var(--app-primary);
  background: var(--app-primary-soft);
  color: var(--app-primary-dark);
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
