import { computed, type ComputedRef } from 'vue'
import type { ComposerTranslation } from 'vue-i18n'
import type { CompetitionStage, MatchNature, TerrainType } from '../models/MatchContext'

export interface SelectOption<T extends string> {
  label: string
  value: T
}

export function useMatchContextOptions(t: ComposerTranslation): {
  natureOptions: ComputedRef<SelectOption<MatchNature>[]>
  competitionStageOptions: ComputedRef<SelectOption<CompetitionStage>[]>
  terrainTypeOptions: ComputedRef<SelectOption<TerrainType>[]>
} {
  const natureOptions = computed<SelectOption<MatchNature>[]>(() => [
    { label: t('context.nature.friendly'), value: 'friendly' },
    { label: t('context.nature.training'), value: 'training' },
    { label: t('context.nature.competition'), value: 'competition' },
    { label: t('context.nature.official'), value: 'official' },
  ])

  const competitionStageOptions = computed<SelectOption<CompetitionStage>[]>(() => [
    { label: t('context.competitionStage.group'), value: 'group' },
    { label: t('context.competitionStage.swiss'), value: 'swiss' },
    { label: t('context.competitionStage.top64'), value: 'top_64' },
    { label: t('context.competitionStage.top32'), value: 'top_32' },
    { label: t('context.competitionStage.top16'), value: 'top_16' },
    { label: t('context.competitionStage.quarterFinal'), value: 'quarter_final' },
    { label: t('context.competitionStage.semiFinal'), value: 'semi_final' },
    { label: t('context.competitionStage.final'), value: 'final' },
    { label: t('context.competitionStage.other'), value: 'other' },
  ])

  const terrainTypeOptions = computed<SelectOption<TerrainType>[]>(() => [
    { label: t('context.terrain.gravel'), value: 'gravel' },
    { label: t('context.terrain.stabilized'), value: 'stabilized' },
    { label: t('context.terrain.indoor'), value: 'indoor' },
    { label: t('context.terrain.other'), value: 'other' },
  ])

  return { natureOptions, competitionStageOptions, terrainTypeOptions }
}
