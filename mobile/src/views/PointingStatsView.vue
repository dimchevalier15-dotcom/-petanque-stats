<template>
  <AppPage>
    <PageHeader
      :title="t('pointing.stats.title')"
      :subtitle="t('pointing.stats.subtitle')"
      :back-to="{ name: 'trainingPointPrecision' }"
    />

    <PointingStatsPanel
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
import PointingStatsPanel from '../components/pointing/PointingStatsPanel.vue'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'
import type { PointingContextNature, PointingStats } from '../models/Pointing'
import { pointingSessionsService } from '../services/pointingSessions'

const { t } = useI18n()
const router = useRouter()

function fetchStats(range: StatsDateRangeParams, nature: PointingContextNature | 'all'): Promise<PointingStats> {
  return pointingSessionsService.getStats(range, nature)
}

function goHome(): void {
  router.push({ name: 'trainingPointPrecision' })
}
</script>
