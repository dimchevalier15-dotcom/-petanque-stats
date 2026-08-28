<template>
  <AppPage>
    <PageHeader
      :title="playerTitle"
      :subtitle="t('coach.player.subtitle')"
      :back-to="{ name: 'coachPlayers' }"
    />

    <div class="history-link-row">
      <RouterLink
        :to="{ name: 'coachPlayerHistory', params: { id: playerId }, query: { name: playerTitle } }"
        class="history-link"
      >
        <i class="pi pi-history" aria-hidden="true" />
        {{ t('coach.player.historyLink') }}
      </RouterLink>
    </div>

    <PlayerStatsPanel
      :fetch-stats="fetchCoachPlayerStats"
      :show-empty-actions="false"
      :initial-nature="initialNature"
      :initial-from="initialFrom"
      :initial-to="initialTo"
      @stats-loaded="onStatsLoaded"
    />
  </AppPage>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import PlayerStatsPanel from '../components/stats/PlayerStatsPanel.vue'
import type { PlayerStatsFetcher } from '../composables/usePlayerStatsPanel'
import type { MatchNature } from '../models/MatchContext'
import type { PlayerStats } from '../models/PlayerStats'
import { coachService } from '../services/coach'

const { t } = useI18n()
const route = useRoute()

const playerId = Number(route.params.id)
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

const fetchCoachPlayerStats: PlayerStatsFetcher = (range, nature, type, distance, competitionId) =>
  coachService.getPlayerStats(playerId, range, nature, type, distance, competitionId)

function onStatsLoaded(stats: PlayerStats): void {
  if (stats.displayName) {
    playerTitle.value = stats.displayName
  }
}
</script>

<style scoped>
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
