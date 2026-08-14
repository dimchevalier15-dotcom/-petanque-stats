import type { MatchType, PlayerRole, ShotType } from '../models/Match'
import type { EndRecord } from '../models/MatchPlay'

export function roleToShot(role: PlayerRole): ShotType {
  return role === 'tireur' ? 'tir' : 'point'
}

export function defaultRoleFor(type: MatchType, position: number): PlayerRole {
  if (type === 'doublette') {
    return position === 2 ? 'tireur' : 'pointeur'
  }
  if (type === 'triplette') {
    if (position === 2) return 'milieu'
    if (position === 3) return 'tireur'
    return 'pointeur'
  }
  return 'pointeur'
}

export function buildStartingRoles(
  type: MatchType,
  teamA: number[],
  teamB: number[],
): Record<number, PlayerRole> {
  const roles: Record<number, PlayerRole> = {}
  teamA.forEach((playerId, index) => {
    roles[playerId] = defaultRoleFor(type, index + 1)
  })
  teamB.forEach((playerId, index) => {
    roles[playerId] = defaultRoleFor(type, index + 1)
  })
  return roles
}

export function shotTypeToRole(shot: ShotType): PlayerRole {
  return shot === 'tir' ? 'tireur' : 'pointeur'
}

export function inferStartingRoles(
  type: MatchType,
  teamA: number[],
  teamB: number[],
  defaultShotTypes?: Record<number, ShotType>,
  explicit?: Record<number, PlayerRole>,
): Record<number, PlayerRole> {
  if (explicit && Object.keys(explicit).length > 0) {
    return { ...explicit }
  }

  const roles = buildStartingRoles(type, teamA, teamB)
  if (!defaultShotTypes) {
    return roles
  }

  for (const playerId of [...teamA, ...teamB]) {
    const shot = defaultShotTypes[playerId]
    if (shot === 'tir' && roles[playerId] !== 'tireur') {
      roles[playerId] = 'tireur'
    }
  }

  return roles
}

export function cycleTripletteRole(role: PlayerRole): PlayerRole {
  if (role === 'pointeur') return 'milieu'
  if (role === 'milieu') return 'tireur'
  return 'pointeur'
}

export function teamForPlayer(
  playerId: number,
  teamA: number[],
  teamB: number[],
): 'A' | 'B' | null {
  if (teamA.includes(playerId)) return 'A'
  if (teamB.includes(playerId)) return 'B'
  return null
}

export function totalBallsInEnd(end: EndRecord): number {
  return end.balls.reduce((sum, entry) => sum + entry.notes.length, 0)
}

export function snapshotEndRoles(
  end: EndRecord,
  currentRoles: Record<number, PlayerRole>,
  playerIds: number[],
): void {
  const roles: Record<number, PlayerRole> = {}
  for (const playerId of playerIds) {
    roles[playerId] = currentRoles[playerId]
  }
  end.roles = roles
}

export function syncCurrentRolesFromEnd(
  end: EndRecord | undefined,
  fallback: Record<number, PlayerRole>,
): Record<number, PlayerRole> {
  if (!end?.roles) {
    return { ...fallback }
  }
  return { ...fallback, ...end.roles }
}
