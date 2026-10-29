<template>
  <AppPage>
    <PageHeader
      :title="t('jumpShot.stats.title')"
      :subtitle="t('jumpShot.stats.subtitle')"
      :back-to="{ name: 'jumpShotHome' }"
    />

    <JumpShotStatsPanel :fetch-stats="fetchStats" show-empty-start-action @start-session="goHome" />
  </AppPage>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import JumpShotStatsPanel from '../components/jumpShot/JumpShotStatsPanel.vue'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'
import type { JumpShotStats } from '../models/JumpShot'
import { jumpShotSessionsService } from '../services/jumpShotSessions'

const router = useRouter()
const { t } = useI18n()

function fetchStats(range: StatsDateRangeParams): Promise<JumpShotStats> {
  return jumpShotSessionsService.getStats(range)
}

function goHome(): void {
  void router.push({ name: 'jumpShotHome' })
}
</script>
