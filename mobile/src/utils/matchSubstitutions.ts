import type { TeamSide, TeamSubstitution } from '../models/MatchPlay'

export interface TeamSlotDisplay {
  originalPlayerId: number
  activePlayerId: number
  isSubstitutedOut: boolean
  substitution?: TeamSubstitution
}

export function substitutionsAllowed(type: string): boolean {
  return type === 'doublette' || type === 'triplette'
}

export function canTeamSubstitute(team: TeamSide, substitutions: TeamSubstitution[]): boolean {
  return !substitutions.some((sub) => sub.team === team)
}

export function activePlayerForSlot(
  originalPlayerId: number,
  team: TeamSide,
  substitutions: TeamSubstitution[],
  endIndex: number,
): number {
  const sub = substitutions.find((item) => item.team === team && item.outPlayerId === originalPlayerId)
  if (sub && endIndex >= sub.fromEndIndex) {
    return sub.inPlayerId
  }
  return originalPlayerId
}

export function teamSlotsForEnd(
  teamIds: number[],
  team: TeamSide,
  substitutions: TeamSubstitution[],
  endIndex: number,
): TeamSlotDisplay[] {
  return teamIds.map((originalPlayerId) => {
    const substitution = substitutions.find((item) => item.team === team && item.outPlayerId === originalPlayerId)
    const isSubstitutedOut = substitution !== undefined && endIndex >= substitution.fromEndIndex
    return {
      originalPlayerId,
      activePlayerId: isSubstitutedOut ? substitution.inPlayerId : originalPlayerId,
      isSubstitutedOut,
      substitution: isSubstitutedOut ? substitution : undefined,
    }
  })
}

export function teamForActivePlayer(
  playerId: number,
  teamA: number[],
  teamB: number[],
  substitutions: TeamSubstitution[],
): TeamSide | null {
  const direct = teamA.includes(playerId) ? 'A' : teamB.includes(playerId) ? 'B' : null
  if (direct) {
    return direct
  }
  const sub = substitutions.find((item) => item.inPlayerId === playerId)
  return sub?.team ?? null
}

export function activeTeamPlayerIds(
  teamIds: number[],
  team: TeamSide,
  substitutions: TeamSubstitution[],
  endIndex: number,
): number[] {
  return teamIds.map((originalId) => activePlayerForSlot(originalId, team, substitutions, endIndex))
}

export function allMatchPlayerIds(
  teamA: number[],
  teamB: number[],
  substitutions: TeamSubstitution[],
): number[] {
  const ids = new Set([...teamA, ...teamB, ...substitutions.map((sub) => sub.inPlayerId)])
  return Array.from(ids)
}

export function isPlayerInMatch(
  playerId: number,
  teamA: number[],
  teamB: number[],
  substitutions: TeamSubstitution[],
): boolean {
  return allMatchPlayerIds(teamA, teamB, substitutions).includes(playerId)
}

export function trackedPlayersForSubmission(
  trackedPlayers: number[],
  substitutions: TeamSubstitution[],
): number[] {
  const result = new Set(trackedPlayers)
  for (const sub of substitutions) {
    if (trackedPlayers.includes(sub.outPlayerId)) {
      result.add(sub.inPlayerId)
    }
  }
  return Array.from(result)
}
