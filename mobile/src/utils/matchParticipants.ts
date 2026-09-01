import type { CompleteMatchRequestDto } from '../dto/match/CompleteMatchRequest'
import type { MatchParticipant, MatchSetup } from '../models/MatchDraft'
import type { TeamSubstitution } from '../models/MatchPlay'
import type { Player } from '../models/Player'
import { formatPlayerLabel } from '../composables/usePlayerSearch'
import { allMatchPlayerIds } from './matchSubstitutions'

export const PLACEHOLDER_PLAYER_COUNT = 6

export class TooManyPlaceholderParticipantsError extends Error {
  constructor() {
    super('Too many unresolved participants for placeholder mapping')
  }
}

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

/** Letters used when empty roster slots are auto-filled at match start (up to triplette). */
export const DEFAULT_PROVISIONAL_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'] as const

export type MatchTeamSide = 'A' | 'B'

export interface EmptyMatchSlot {
  team: MatchTeamSide
  slot: number
  letter: (typeof DEFAULT_PROVISIONAL_LETTERS)[number]
}

/** Empty slots in team A then team B order, each assigned the next default letter. */
export function emptySlotsToFill(
  teamASlots: number[],
  teamBSlots: number[],
  hasSelection: (team: MatchTeamSide, slot: number) => boolean,
): EmptyMatchSlot[] {
  const pending: EmptyMatchSlot[] = []
  let letterIndex = 0

  for (const team of ['A', 'B'] as const) {
    const slots = team === 'A' ? teamASlots : teamBSlots
    for (const slot of slots) {
      if (hasSelection(team, slot)) {
        continue
      }
      const letter = DEFAULT_PROVISIONAL_LETTERS[letterIndex]
      if (!letter) {
        return pending
      }
      letterIndex += 1
      pending.push({ team, slot, letter })
    }
  }

  return pending
}

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
      shots: end.shots.map((shot) => ({
        ...shot,
        playerId: remapParticipantId(shot.playerId, mapping),
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
      ...end.shots.map((shot) => shot.playerId),
      ...(end.roles ?? []).map((role) => role.playerId),
    ]),
  ]
  return ids.some(isProvisionalParticipant)
}

/** Maps unresolved provisional ids to placeholder Player ids (A–F), one each. */
export function assignPlaceholderPlayers(
  provisionalIds: number[],
  placeholderPlayerIds: number[],
  mapping: Record<number, number>,
): void {
  if (provisionalIds.length > placeholderPlayerIds.length) {
    throw new TooManyPlaceholderParticipantsError()
  }

  const used = new Set(Object.values(mapping))
  let cursor = 0

  for (const provisionalId of provisionalIds) {
    while (cursor < placeholderPlayerIds.length && used.has(placeholderPlayerIds[cursor]!)) {
      cursor += 1
    }
    if (cursor >= placeholderPlayerIds.length) {
      throw new TooManyPlaceholderParticipantsError()
    }

    const placeholderId = placeholderPlayerIds[cursor]!
    mapping[provisionalId] = placeholderId
    used.add(placeholderId)
    cursor += 1
  }
}

export function excludePlayersFromTracked(
  trackedPlayers: number[],
  excludedIds: ReadonlySet<number>,
): number[] {
  return trackedPlayers.filter((playerId) => !excludedIds.has(playerId))
}
