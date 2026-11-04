<template>
  <AppPage>
    <PageHeader
      :title="t('rings.stats.title')"
      :subtitle="t(config.titleKey)"
      :back-to="{ name: config.routes.home }"
    />

    <RingsStatsPanel :fetch-stats="fetchStats" :mode="mode" show-empty-start-action @start-session="goHome" />
  </AppPage>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import RingsStatsPanel from '../components/rings/RingsStatsPanel.vue'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'
import { RINGS_MODE_CONFIG, type RingsMode, type RingsStats } from '../models/Rings'
import { ringsSessionsService } from '../services/ringsSessions'

const props = withDefaults(defineProps<{ mode?: RingsMode }>(), { mode: 'point' })

const { t } = useI18n()
const router = useRouter()

const config = computed(() => RINGS_MODE_CONFIG[props.mode])

function fetchStats(range: StatsDateRangeParams): Promise<RingsStats> {
  return ringsSessionsService.getStats(range, props.mode)
}

function goHome(): void {
  void router.push({ name: config.value.routes.home })
}
</script>
