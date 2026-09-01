import type { MatchType } from '../models/Match'
import type { EndRecord, TeamSubstitution } from '../models/MatchPlay'
import { allMatchPlayerIds, trackedPlayersForSubmission } from './matchSubstitutions'

export function allMatchPlayersTracked(params: {
  teamA: number[]
  teamB: number[]
  trackedPlayers: number[]
  substitutions?: TeamSubstitution[]
}): boolean {
  const roster = allMatchPlayerIds(params.teamA, params.teamB, params.substitutions ?? [])
  const tracked = new Set(trackedPlayersForSubmission(params.trackedPlayers, params.substitutions ?? []))
  return roster.length > 0 && roster.every((playerId) => tracked.has(playerId))
}

export function ballsPerPlayerForType(type: MatchType): number {
  return type === 'triplette' ? 2 : 3
}

export function playersPerTeamForType(type: MatchType): number {
  if (type === 'tete_a_tete') return 1
  if (type === 'doublette') return 2
  return 3
}
