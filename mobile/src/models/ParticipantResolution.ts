export type ParticipantResolutionKind = 'skip' | 'existing' | 'new'

/**
 * How a provisional participant becomes a real Player at the end of the match.
 * Doubles as the form model of the resolution screen. See US-021.
 */
export interface ParticipantResolution {
  participantId: number
  kind: ParticipantResolutionKind
  /** Selected Player, when linking an existing one. */
  playerId: number | null
  firstName: string
  lastName: string
  nickname: string
  clubId: number | null
}

/** Splits a free-text label into a first and last name, as a starting point for the form. */
export function resolutionFromLabel(participantId: number, label: string): ParticipantResolution {
  const parts = label.trim().split(/\s+/).filter(Boolean)
  return {
    participantId,
    kind: 'skip',
    playerId: null,
    firstName: parts[0] ?? '',
    lastName: parts.slice(1).join(' '),
    nickname: '',
    clubId: null,
  }
}

export function isResolutionComplete(resolution: ParticipantResolution): boolean {
  if (resolution.kind === 'skip') {
    return true
  }
  if (resolution.kind === 'existing') {
    return resolution.playerId !== null
  }
  return resolution.firstName.trim() !== '' && resolution.lastName.trim() !== ''
}
