import type {
  CreateMatchRequestDto,
  DefaultShotTypeDto,
  StartingRoleDto,
} from '../dto/match/CreateMatchRequest'
import type { CompleteMatchRequestDto } from '../dto/match/CompleteMatchRequest'
import type { PlayerRole } from '../models/Match'
import type { MatchPlayState, MatchSetup } from '../models/MatchDraft'
import type { EndRecord, TeamSide } from '../models/MatchPlay'
import { inferStartingRoles, roleToShot } from './matchRoles'
import { allMatchPlayerIds, trackedPlayersForSubmission } from './matchSubstitutions'

function startingRolesOf(setup: MatchSetup): Record<number, PlayerRole> {
  return inferStartingRoles(
    setup.type,
    setup.teamA,
    setup.teamB,
    setup.defaultShotTypes,
    setup.startingRoles,
  )
}

/**
 * Payload creating the match shell. Substitutes are excluded: the backend expects the starting
 * roster only, and registers substitutes when the content is recorded.
 */
export function buildCreateMatchRequest(setup: MatchSetup): CreateMatchRequestDto {
  const startingRoles = startingRolesOf(setup)
  const roster = [...setup.teamA, ...setup.teamB]

  const startingRoleDtos: StartingRoleDto[] = []
  const defaultShotTypeDtos: DefaultShotTypeDto[] = []
  for (const playerId of roster) {
    const role = startingRoles[playerId] ?? 'pointeur'
    startingRoleDtos.push({ playerId, role })
    defaultShotTypeDtos.push({
      playerId,
      defaultShotType: setup.defaultShotTypes?.[playerId] ?? roleToShot(role),
    })
  }

  return {
    type: setup.type,
    targetScore: setup.targetScore,
    statisticsMode: setup.statisticsMode,
    teamA: setup.teamA,
    teamB: setup.teamB,
    teamAName: setup.teamAName,
    teamBName: setup.teamBName,
    trackedPlayers: setup.trackedPlayers.filter((playerId) => roster.includes(playerId)),
    defaultShotTypes: defaultShotTypeDtos,
    startingRoles: startingRoleDtos,
    playedAt: setup.startedAt,
  }
}

/** Payload recording every end, ball and role of the match. */
export function buildMatchSubmission(
  setup: MatchSetup,
  state: MatchPlayState,
): CompleteMatchRequestDto {
  const substitutions = state.substitutions ?? []
  const startingRoles = startingRolesOf(setup)
  const matchPlayers = allMatchPlayerIds(setup.teamA, setup.teamB, substitutions)

  function playersForEndRoles(end: EndRecord): number[] {
    const ids = new Set(matchPlayers)
    for (const ball of end.balls) {
      ids.add(ball.playerId)
    }
    return Array.from(ids)
  }

  return {
    type: setup.type,
    targetScore: setup.targetScore,
    statisticsMode: setup.statisticsMode,
    teamA: setup.teamA,
    teamB: setup.teamB,
    trackedPlayers: trackedPlayersForSubmission(setup.trackedPlayers, substitutions),
    substitutions: substitutions.map((substitution) => ({ ...substitution })),
    openingScoreA: state.openingScoreA ?? 0,
    openingScoreB: state.openingScoreB ?? 0,
    ends: state.ends
      .filter((end) => end.canceled === true || (end.winner !== undefined && end.points !== undefined))
      .map((end) => ({
        index: end.index,
        winner: (end.winner as TeamSide) ?? 'A',
        points: end.canceled ? 0 : (end.points ?? 0),
        canceled: end.canceled === true,
        balls: end.balls.map((ball) => ({
          playerId: ball.playerId,
          notes: ball.notes,
          shotTypes: ball.shotTypes,
          distances: ball.distances,
          isCochonnet: ball.isCochonnet,
        })),
        roles: playersForEndRoles(end).map((playerId) => ({
          playerId,
          role:
            end.roles?.[playerId] ??
            startingRoles[playerId] ??
            state.currentRoles[playerId] ??
            'pointeur',
        })),
      })),
  }
}
