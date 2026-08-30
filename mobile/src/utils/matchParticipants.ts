import type { CompleteMatchRequestDto } from '../dto/match/CompleteMatchRequest'
import type { MatchParticipant, MatchSetup } from '../models/MatchDraft'
import type { TeamSubstitution } from '../models/MatchPlay'
import type { Player } from '../models/Player'
import { formatPlayerLabel } from '../composables/usePlayerSearch'
import { allMatchPlayerIds } from './matchSubstitutions'

/**
 * Provisional participants are numbered downwards from -1 so they can never collide with a
 * persisted Player id. See ADR-001.
 */
export function isProvisionalParticipant(id: number): boolean {
  return id < 0
}

/**
 * Sentinel id of the "add this name to the match" suggestion. Never stored: the parent
 * allocates a real provisional id when the suggestion is picked.
 */
export const PROVISIONAL_OPTION_ID = 0

export function nextProvisionalId(participants: MatchParticipant[]): number {
  let next = -1
  for (const participant of participants) {
    if (participant.id <= next) {
      next = participant.id - 1
    }
  }
  return next
}

export function participantFromPlayer(player: Player): MatchParticipant {
  const fullName = `${player.firstName} ${player.lastName}`.trim()
  return {
    id: player.id,
    label: formatPlayerLabel(player),
    shortLabel: (player.nickname || player.firstName || fullName).trim(),
  }
}

export function provisionalParticipant(id: number, name: string): MatchParticipant {
  const label = name.trim()
  return { id, label, shortLabel: label }
}

export function findParticipant(
  participants: MatchParticipant[],
  id: number,
): MatchParticipant | undefined {
  return participants.find((participant) => participant.id === id)
}

/** Participants still waiting to be linked to a Player, in team order. */
export function unresolvedParticipants(
  setup: MatchSetup,
  substitutions: TeamSubstitution[] = [],
  resolvedPlayers: Record<number, number> = {},
): MatchParticipant[] {
  return allMatchPlayerIds(setup.teamA, setup.teamB, substitutions)
    .filter((id) => isProvisionalParticipant(id) && resolvedPlayers[id] === undefined)
    .map((id) => findParticipant(setup.participants, id) ?? provisionalParticipant(id, `#${-id}`))
}

export function remapParticipantId(id: number, mapping: Record<number, number>): number {
  return mapping[id] ?? id
}

function remapIds(ids: number[], mapping: Record<number, number>): number[] {
  return ids.map((id) => remapParticipantId(id, mapping))
}

function remapKeyedRecord<T>(
  source: Record<number, T> | undefined,
  mapping: Record<number, number>,
): Record<number, T> | undefined {
  if (!source) {
    return undefined
  }
  const out: Record<number, T> = {}
  for (const [key, value] of Object.entries(source)) {
    out[remapParticipantId(Number(key), mapping)] = value
  }
  return out
}

/** Replaces every provisional id by its persisted Player id before sending the match. */
export function remapSetup(setup: MatchSetup, mapping: Record<number, number>): MatchSetup {
  return {
    ...setup,
    teamA: remapIds(setup.teamA, mapping),
    teamB: remapIds(setup.teamB, mapping),
    trackedPlayers: remapIds(setup.trackedPlayers, mapping),
    defaultShotTypes: remapKeyedRecord(setup.defaultShotTypes, mapping) ?? {},
    startingRoles: remapKeyedRecord(setup.startingRoles, mapping) ?? {},
    participants: setup.participants.map((participant) => ({
      ...participant,
      id: remapParticipantId(participant.id, mapping),
    })),
  }
}

export function remapSubmission(
  payload: CompleteMatchRequestDto,
  mapping: Record<number, number>,
): CompleteMatchRequestDto {
  return {
    ...payload,
    teamA: remapIds(payload.teamA, mapping),
    teamB: remapIds(payload.teamB, mapping),
    trackedPlayers: remapIds(payload.trackedPlayers, mapping),
    substitutions: (payload.substitutions ?? []).map((substitution) => ({
      ...substitution,
      outPlayerId: remapParticipantId(substitution.outPlayerId, mapping),
      inPlayerId: remapParticipantId(substitution.inPlayerId, mapping),
    })),
    ends: payload.ends.map((end) => ({
      ...end,
      balls: end.balls.map((ball) => ({
        ...ball,
        playerId: remapParticipantId(ball.playerId, mapping),
      })),
      roles: (end.roles ?? []).map((role) => ({
        ...role,
        playerId: remapParticipantId(role.playerId, mapping),
      })),
    })),
  }
}

/**
 * A provisional id reaching the API would have its balls silently dropped by the backend,
 * so the payload is checked before being sent. See ADR-001.
 */
export function containsProvisionalParticipant(payload: CompleteMatchRequestDto): boolean {
  const ids = [
    ...payload.teamA,
    ...payload.teamB,
    ...payload.trackedPlayers,
    ...(payload.substitutions ?? []).flatMap((substitution) => [
      substitution.outPlayerId,
      substitution.inPlayerId,
    ]),
    ...payload.ends.flatMap((end) => [
      ...end.balls.map((ball) => ball.playerId),
      ...(end.roles ?? []).map((role) => role.playerId),
    ]),
  ]
  return ids.some(isProvisionalParticipant)
}
