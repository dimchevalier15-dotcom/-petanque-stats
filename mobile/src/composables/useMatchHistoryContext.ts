import type { ComposerTranslation } from 'vue-i18n'
import type { MatchHistoryItem } from '../models/MatchHistory'
import { useMatchContextOptions } from './useMatchContextOptions'

export interface MatchHistoryContextLabels {
  nature?: string
  competition?: string
  stage?: string
}

export function useMatchHistoryContext(t: ComposerTranslation) {
  const { natureOptions, competitionStageOptions } = useMatchContextOptions(t)

  function contextLabels(item: MatchHistoryItem): MatchHistoryContextLabels {
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

  function hasContext(item: MatchHistoryItem): boolean {
    return item.nature !== null || item.competitionLabel !== null || item.competitionStage !== null
  }

  return { contextLabels, hasContext }
}
