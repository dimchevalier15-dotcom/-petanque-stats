<template>
  <AppPage>
    <PageHeader
      :title="playerTitle"
      :subtitle="t('coach.player.subtitle')"
      :back-to="playersListBackTo"
    />

    <TabView v-model:active-index="activeTab" class="coach-player-tabs">
      <TabPanel :header="t('coach.player.tabs.shooting')">
        <ShootingStatsPanel :fetch-stats="fetchCoachPlayerShootingStats" />
      </TabPanel>
      <TabPanel :header="t('coach.player.tabs.training')">
        <EmptyState
          :title="t('coach.player.trainingComingSoon')"
          icon="pi pi-wrench"
        />
      </TabPanel>
      <TabPanel :header="t('coach.player.tabs.matches')">
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
          @stats-loaded="onStatsLoaded"
        />
      </TabPanel>
    </TabView>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import TabPanel from 'primevue/tabpanel'
import TabView from 'primevue/tabview'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import ShootingStatsPanel from '../components/shooting/ShootingStatsPanel.vue'
import PlayerStatsPanel from '../components/stats/PlayerStatsPanel.vue'
import { useCoachSpaceFromRoute } from '../composables/useCoachSpace'
import type { PlayerStatsFetcher } from '../composables/usePlayerStatsPanel'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'
import type { MatchNature } from '../models/MatchContext'
import type { PlayerStats } from '../models/PlayerStats'
import type { ShootingContextNature, ShootingStats } from '../models/Shooting'
import { coachService } from '../services/coach'

const { t } = useI18n()
const route = useRoute()
const {
  selectionGlobaleId,
  playerListRouteName,
  playerHistoryRouteName,
} = useCoachSpaceFromRoute()

const playerId = Number(route.params.id)
const activeTab = ref(0)

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
.coach-player-tabs :deep(.p-tabview-panels) {
  padding: var(--app-space-sm) 0 0;
}

.history-link-row {
  display: flex;
  justify-content: flex-end;
  margin-bottom: var(--app-space-sm);
}

.history-link {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--app-primary);
  text-decoration: none;
}

.history-link:hover {
  text-decoration: underline;
}
</style>
