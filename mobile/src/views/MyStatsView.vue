<template>
  <AppPage :title="t('stats.title')" :subtitle="displayName ?? undefined">
    <PlayerStatsPanel
      :fetch-stats="fetchMyStats"
      :reload-key="impersonation.player?.id ?? null"
      @stats-loaded="onStatsLoaded"
    />
  </AppPage>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppPage from '../components/layout/AppPage.vue'
import PlayerStatsPanel from '../components/stats/PlayerStatsPanel.vue'
import type { PlayerStatsFetcher } from '../composables/usePlayerStatsPanel'
import type { PlayerStats } from '../models/PlayerStats'
import { statsService } from '../services/stats'
import { useImpersonationStore } from '../stores/impersonation'

const { t } = useI18n()
const impersonation = useImpersonationStore()

const displayName = ref<string | undefined>()

const fetchMyStats: PlayerStatsFetcher = (range, nature, type, distance, competitionId) =>
  statsService.getMyStats(range, nature, type, distance, competitionId)

function onStatsLoaded(stats: PlayerStats): void {
  displayName.value = stats.displayName ?? undefined
}
</script>
