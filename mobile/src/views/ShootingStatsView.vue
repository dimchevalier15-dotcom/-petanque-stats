<template>
  <AppPage>
    <PageHeader
      :title="t('shooting.stats.title')"
      :subtitle="t('shooting.stats.subtitle')"
      :back-to="{ name: 'shootingHome' }"
    />

    <ShootingStatsPanel
      :fetch-stats="fetchStats"
      show-empty-start-action
      @start-session="goHome"
    />
  </AppPage>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import ShootingStatsPanel from '../components/shooting/ShootingStatsPanel.vue'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'
import type { ShootingContextNature, ShootingStats } from '../models/Shooting'
import { shootingSessionsService } from '../services/shootingSessions'

const { t } = useI18n()
const router = useRouter()

function fetchStats(range: StatsDateRangeParams, nature: ShootingContextNature | 'all'): Promise<ShootingStats> {
  return shootingSessionsService.getStats(range, nature)
}

function goHome(): void {
  router.push({ name: 'shootingHome' })
}
</script>
