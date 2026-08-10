import { computed, type Ref } from 'vue'
import type { ComposerTranslation } from 'vue-i18n'
import type { MatchContext } from '../models/MatchContext'
import type { TeamSide } from '../models/MatchPlay'

function resolveTeamLabel(name: string | null | undefined, fallback: string): string {
  const trimmed = name?.trim()
  return trimmed ? trimmed : fallback
}

export function useMatchTeamLabels(context: Ref<MatchContext | null>, t: ComposerTranslation) {
  const teamALabel = computed(() => resolveTeamLabel(context.value?.teamAName, t('matches.teams.a')))
  const teamBLabel = computed(() => resolveTeamLabel(context.value?.teamBName, t('matches.teams.b')))

  function labelForTeam(team: TeamSide): string {
    return team === 'A' ? teamALabel.value : teamBLabel.value
  }

  return { teamALabel, teamBLabel, labelForTeam }
}
