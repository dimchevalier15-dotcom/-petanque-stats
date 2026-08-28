import { computed, onMounted, ref, watch, type Ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, type RouteLocationRaw } from 'vue-router'
import { formatMasters, shotMasters, shotSuccessRate } from './matchSuccessRate'
import {
  avgSeverity,
  breakdownBallCount,
  distanceBucketLabel,
  formatAvg,
  formatLabel,
  natureLabel,
  usePlayerStatsCharts,
} from './usePlayerStatsCharts'
import { useStatsDateRange, toInputDate, type StatsDateRangeParams } from './useStatsDateRange'
import type { MatchNature } from '../models/MatchContext'
import { competitionLabel, type Competition } from '../models/Competition'
import type { MatchType } from '../models/Match'
import {
  DISTANCE_BUCKET_KEYS,
  type DistanceBucketKey,
  type PlayerStats,
  type PlayerStatsByDistance,
} from '../models/PlayerStats'
import type { MatchSummaryShotBreakdown } from '../models/MatchSummary'
import { competitionsService } from '../services/competitions'

export type PlayerStatsFetcher = (
  range: StatsDateRangeParams,
  nature: MatchNature | 'all',
  type: MatchType | 'all',
  distance: DistanceBucketKey | 'all',
  competitionId: number | 'all',
) => Promise<PlayerStats>

export interface UsePlayerStatsPanelOptions {
  fetchStats: PlayerStatsFetcher
  showEmptyActions?: boolean
  initialNature?: MatchNature | 'all'
  initialFrom?: string
  initialTo?: string
  onStatsLoaded?: (stats: PlayerStats) => void
  reloadKey?: Ref<unknown>
}

export function usePlayerStatsPanel(options: UsePlayerStatsPanelOptions) {
  const { t } = useI18n()
  const router = useRouter()

  const loading = ref(true)
  const refreshing = ref(false)
  const loadError = ref(false)
  const stats = ref<PlayerStats | null>(null)
  const natureFilter = ref<MatchNature | 'all'>(options.initialNature ?? 'all')
  const competitionFilter = ref<number | null>(null)
  const competitions = ref<Competition[]>([])
  const formatFilter = ref<MatchType | 'all'>('all')
  const distanceFilter = ref<DistanceBucketKey | 'all'>('all')
  const { dateFrom, dateTo, maxDate, dateFilterEnabled, normalizeRange, queryParams } = useStatsDateRange()
  if (options.initialFrom) {
    dateFrom.value = options.initialFrom
  }
  if (options.initialTo) {
    dateTo.value = options.initialTo
  }

  const defaultDateFrom = (() => {
    const today = new Date()
    const from = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate())
    return toInputDate(from)
  })()

  const natureFilterOptions = computed(() => [
    { value: 'all' as const, label: t('stats.filters.all') },
    { value: 'friendly' as const, label: t('context.nature.friendly') },
    { value: 'training' as const, label: t('context.nature.training') },
    { value: 'competition' as const, label: t('context.nature.competition') },
  ])

  const competitionFilterOptions = computed(() =>
    competitions.value.map((competition) => ({
      value: competition.id,
      label: competitionLabel(competition),
    })),
  )

  const formatFilterOptions = computed(() => [
    { value: 'all' as const, label: t('stats.filters.all') },
    { value: 'tete_a_tete' as const, label: t('matches.types.teteATete') },
    { value: 'doublette' as const, label: t('matches.types.doublette') },
    { value: 'triplette' as const, label: t('matches.types.triplette') },
  ])

  const distanceFilterOptions = computed(() => [
    { value: 'all' as const, label: t('stats.filters.all') },
    ...DISTANCE_BUCKET_KEYS.map((bucket) => ({
      value: bucket,
      label: distanceBucketLabel(t, bucket),
    })),
  ])

  const activeFilterCount = computed(() => {
    let count = 0
    if (natureFilter.value !== 'all') count++
    if (competitionFilter.value !== null) count++
    if (formatFilter.value !== 'all') count++
    if (distanceFilter.value !== 'all') count++
    if (!dateFilterEnabled.value) count++
    else if (dateFrom.value !== defaultDateFrom || dateTo.value !== maxDate) count++
    return count
  })

  const showDateFilter = computed(() => stats.value !== null && stats.value.status !== 'no_player')

  const {
    showEvolution,
    showDistribution,
    evolutionChart,
    distributionChart,
    pointDistributionChart,
    tirDistributionChart,
  } = usePlayerStatsCharts(stats, t)

  function successWithMasters(breakdown: MatchSummaryShotBreakdown | null | undefined): string | null {
    const rate = shotSuccessRate(breakdown)
    const masters = shotMasters(breakdown)
    if (rate === null) {
      return null
    }
    const pct = t('stats.successRate.value', { rate })
    return masters ? `${pct} (${formatMasters(masters)})` : pct
  }

  function distanceBreakdown(item: PlayerStatsByDistance): MatchSummaryShotBreakdown {
    return {
      average: item.average,
      p2: item.p2,
      p1: item.p1,
      p0: item.p0,
      m1: item.m1,
      m2: item.m2,
      successRate: null,
    }
  }

  const emptyTitleKey = computed(() => {
    switch (stats.value?.status) {
      case 'no_player':
        return 'stats.empty.noPlayerTitle'
      case 'no_matches':
        return 'stats.empty.noMatchesTitle'
      case 'no_data_in_period':
        return 'stats.empty.noDataInPeriodTitle'
      default:
        return 'stats.empty.noDataTitle'
    }
  })

  const emptyHintKey = computed(() => {
    switch (stats.value?.status) {
      case 'no_player':
        return 'stats.empty.noPlayerHint'
      case 'no_matches':
        return 'stats.empty.noMatchesHint'
      case 'no_data_in_period':
        return 'stats.empty.noDataInPeriodHint'
      default:
        return 'stats.empty.noDataHint'
    }
  })

  const emptyActionKey = computed(() => {
    switch (stats.value?.status) {
      case 'no_matches':
        return 'stats.empty.startMatch'
      default:
        return 'stats.empty.goHome'
    }
  })

  const emptyActionRoute = computed<RouteLocationRaw | null>(() => {
    if (options.showEmptyActions === false) {
      return null
    }
    switch (stats.value?.status) {
      case 'no_matches':
        return { name: 'newMatch' }
      case 'no_player':
      case 'no_tracked_data':
        return { name: 'home' }
      case 'no_data_in_period':
        return null
      default:
        return null
    }
  })

  const showAverageDetails = computed(() => !!(stats.value?.point || stats.value?.tir))

  function setNatureFilter(value: MatchNature | 'all'): void {
    natureFilter.value = value
    if (value !== 'competition') {
      competitionFilter.value = null
    }
    if (stats.value) {
      void load({ refresh: true })
    }
  }

  function onCompetitionFilterChange(): void {
    if (stats.value) {
      void load({ refresh: true })
    }
  }

  function setFormatFilter(value: MatchType | 'all'): void {
    formatFilter.value = value
    if (stats.value) {
      void load({ refresh: true })
    }
  }

  function setDistanceFilter(value: DistanceBucketKey | 'all'): void {
    distanceFilter.value = value
    if (stats.value) {
      void load({ refresh: true })
    }
  }

  async function load(loadOptions: { refresh?: boolean } = {}): Promise<void> {
    const isRefresh = loadOptions.refresh === true
    if (isRefresh) {
      refreshing.value = true
    } else {
      loading.value = true
    }
    loadError.value = false
    try {
      stats.value = await options.fetchStats(
        queryParams(),
        natureFilter.value,
        formatFilter.value,
        distanceFilter.value,
        competitionFilter.value ?? 'all',
      )
      options.onStatsLoaded?.(stats.value)
    } catch {
      loadError.value = true
    } finally {
      loading.value = false
      refreshing.value = false
    }
  }

  function onDateRangeChange(): void {
    normalizeRange()
    if (stats.value) {
      void load({ refresh: true })
    }
  }

  onMounted(async () => {
    try {
      competitions.value = await competitionsService.list()
    } catch {
      competitions.value = []
    }
    await load()
  })

  if (options.reloadKey !== undefined) {
    watch(options.reloadKey, (next, prev) => {
      if (next !== prev && stats.value) {
        void load({ refresh: true })
      }
    })
  }

  return {
    router,
    t,
    loading,
    refreshing,
    loadError,
    stats,
    natureFilter,
    competitionFilter,
    formatFilter,
    distanceFilter,
    dateFrom,
    dateTo,
    maxDate,
    dateFilterEnabled,
    natureFilterOptions,
    competitionFilterOptions,
    formatFilterOptions,
    distanceFilterOptions,
    activeFilterCount,
    showDateFilter,
    showEvolution,
    showDistribution,
    evolutionChart,
    distributionChart,
    pointDistributionChart,
    tirDistributionChart,
    successWithMasters,
    distanceBreakdown,
    emptyTitleKey,
    emptyHintKey,
    emptyActionKey,
    emptyActionRoute,
    showAverageDetails,
    setNatureFilter,
    onCompetitionFilterChange,
    setFormatFilter,
    setDistanceFilter,
    load,
    onDateRangeChange,
    avgSeverity,
    breakdownBallCount,
    distanceBucketLabel,
    formatAvg,
    formatLabel,
    natureLabel,
  }
}
