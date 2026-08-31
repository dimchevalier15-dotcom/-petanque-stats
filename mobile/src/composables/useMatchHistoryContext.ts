import type { ComposerTranslation } from 'vue-i18n'
import { useMatchContextOptions } from './useMatchContextOptions'

export interface MatchHistoryContextLabels {
  nature?: string
  competition?: string
  stage?: string
}

export interface MatchHistoryContextSource {
  nature?: string | null
  competitionLabel?: string | null
  competitionStage?: string | null
}

export function useMatchHistoryContext(t: ComposerTranslation) {
  const { natureOptions, competitionStageOptions } = useMatchContextOptions(t)

  function contextLabels(item: MatchHistoryContextSource): MatchHistoryContextLabels {
    const labels: MatchHistoryContextLabels = {}

    if (item.nature) {
      labels.nature = natureOptions.value.find((option) => option.value === item.nature)?.label
    }
    if (item.competitionLabel) {
      labels.competition = item.competitionLabel
    }
    if (item.competitionStage) {
      labels.stage = competitionStageOptions.value.find((option) => option.value === item.competitionStage)?.label
    }

    return labels
  }

  function hasContext(item: MatchHistoryContextSource): boolean {
    return !!(item.nature || item.competitionLabel || item.competitionStage)
  }

  return { contextLabels, hasContext }
}
