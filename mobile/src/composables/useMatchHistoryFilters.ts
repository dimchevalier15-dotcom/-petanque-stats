import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { toInputDate, useStatsDateRange, type StatsDateRangeParams } from './useStatsDateRange'
import type { MatchNature } from '../models/MatchContext'
import { competitionLabel, type Competition } from '../models/Competition'
import type { MatchType } from '../models/Match'
import { competitionsService } from '../services/competitions'

export interface MatchHistoryFilterParams {
  nature: MatchNature | 'all'
  type: MatchType | 'all'
  competitionId: number | 'all'
  includeRefused: boolean
  range: StatsDateRangeParams
}

export function useMatchHistoryFilters() {
  const { t } = useI18n()

  const natureFilter = ref<MatchNature | 'all'>('all')
  const competitionFilter = ref<number | null>(null)
  const formatFilter = ref<MatchType | 'all'>('all')
  const includeRefused = ref(false)
  const competitions = ref<Competition[]>([])
  const { dateFrom, dateTo, maxDate, dateFilterEnabled, normalizeRange, queryParams } = useStatsDateRange()

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

  const activeFilterCount = computed(() => {
    let count = 0
    if (natureFilter.value !== 'all') count++
    if (competitionFilter.value !== null) count++
    if (formatFilter.value !== 'all') count++
    if (includeRefused.value) count++
    if (!dateFilterEnabled.value) count++
    else if (dateFrom.value !== defaultDateFrom || dateTo.value !== maxDate) count++
    return count
  })

  function filterParams(): MatchHistoryFilterParams {
    return {
      nature: natureFilter.value,
      type: formatFilter.value,
      competitionId: competitionFilter.value ?? 'all',
      includeRefused: includeRefused.value,
      range: queryParams(),
    }
  }

  function onDateRangeChange(): void {
    normalizeRange()
  }

  onMounted(async () => {
    try {
      competitions.value = await competitionsService.list()
    } catch {
      competitions.value = []
    }
  })

  return {
    natureFilter,
    competitionFilter,
    formatFilter,
    includeRefused,
    dateFrom,
    dateTo,
    maxDate,
    dateFilterEnabled,
    natureFilterOptions,
    competitionFilterOptions,
    formatFilterOptions,
    activeFilterCount,
    filterParams,
    onDateRangeChange,
  }
}
