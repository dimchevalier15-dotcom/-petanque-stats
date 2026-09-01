import type { BallNote, EndRecord, EndShot, TeamSide } from '../models/MatchPlay'

export function shotsForPlayer(end: EndRecord, playerId: number): EndShot[] {
  return (end.shots ?? [])
    .filter((shot) => shot.playerId === playerId)
    .sort((a, b) => a.sequenceOrder - b.sequenceOrder)
}

export function playerShotCount(end: EndRecord, playerId: number): number {
  return shotsForPlayer(end, playerId).length
}

export function shotAt(end: EndRecord, playerId: number, slotIndex: number): EndShot | undefined {
  return shotsForPlayer(end, playerId)[slotIndex]
}

export function noteAt(end: EndRecord, playerId: number, slotIndex: number): BallNote | undefined {
  return shotAt(end, playerId, slotIndex)?.note
}

export function shotTypeAt(end: EndRecord, playerId: number, slotIndex: number): 'point' | 'tir' | undefined {
  return shotAt(end, playerId, slotIndex)?.shotType
}

export function distanceAt(end: EndRecord, playerId: number, slotIndex: number): number | null | undefined {
  return shotAt(end, playerId, slotIndex)?.distance
}

export function isCochonnetAt(end: EndRecord, playerId: number, slotIndex: number): boolean {
  return shotAt(end, playerId, slotIndex)?.isCochonnet === true
}

export function totalShotsInEnd(end: EndRecord): number {
  return end.shots.length
}

export function maxSequenceOrder(end: EndRecord): number {
  if (end.shots.length === 0) {
    return 0
  }
  return Math.max(...end.shots.map((shot) => shot.sequenceOrder))
}

export function nextSequenceOrder(end: EndRecord): number {
  return maxSequenceOrder(end) + 1
}

export function canEditShot(end: EndRecord, playerId: number, slotIndex: number): boolean {
  return shotAt(end, playerId, slotIndex) !== undefined
}

export function canAddShot(end: EndRecord, playerId: number, slotIndex: number, ballsPerPlayer: number): boolean {
  const count = playerShotCount(end, playerId)
  return slotIndex === count && count < ballsPerPlayer
}

export function canEnterBallSlot(
  end: EndRecord,
  playerId: number,
  slotIndex: number,
  ballsPerPlayer: number,
): boolean {
  return canEditShot(end, playerId, slotIndex) || canAddShot(end, playerId, slotIndex, ballsPerPlayer)
}

export function hasAnyPlayedShot(end: EndRecord): boolean {
  return end.shots.length > 0
}

export function addShot(end: EndRecord, shot: Omit<EndShot, 'sequenceOrder'>): EndShot {
  const entry: EndShot = {
    ...shot,
    sequenceOrder: nextSequenceOrder(end),
  }
  end.shots.push(entry)
  return entry
}

export function updateShot(
  end: EndRecord,
  playerId: number,
  slotIndex: number,
  patch: Partial<Pick<EndShot, 'note' | 'shotType' | 'distance' | 'isCochonnet'>>,
): void {
  const shot = shotAt(end, playerId, slotIndex)
  if (!shot) {
    return
  }
  if (patch.note !== undefined) {
    shot.note = patch.note
  }
  if (patch.shotType !== undefined) {
    shot.shotType = patch.shotType
  }
  if (patch.distance !== undefined) {
    shot.distance = patch.distance
  }
  if (patch.isCochonnet !== undefined) {
    shot.isCochonnet = patch.isCochonnet
  }
}

export function undoLastShot(end: EndRecord): EndShot | null {
  if (end.shots.length === 0) {
    return null
  }
  const order = maxSequenceOrder(end)
  const index = end.shots.findIndex((shot) => shot.sequenceOrder === order)
  if (index < 0) {
    return null
  }
  const [removed] = end.shots.splice(index, 1)
  return removed ?? null
}

export function teamForShot(end: EndRecord, sequenceOrder: number, teamOfPlayer: (playerId: number) => TeamSide | null): TeamSide | null {
  const shot = end.shots.find((entry) => entry.sequenceOrder === sequenceOrder)
  if (!shot) {
    return null
  }
  return teamOfPlayer(shot.playerId)
}

/** Legacy draft payload: per-player ball arrays without global order. */
export interface LegacyEndBallEntry {
  playerId: number
  notes: BallNote[]
  shotTypes: ('point' | 'tir')[]
  distances: (number | null)[]
  isCochonnet?: boolean[]
}

export function migrateLegacyBallsToShots(balls: LegacyEndBallEntry[]): EndShot[] {
  const shots: EndShot[] = []
  let sequenceOrder = 1
  const maxNotes = balls.reduce((max, entry) => Math.max(max, entry.notes.length), 0)

  for (let slot = 0; slot < maxNotes; slot += 1) {
    for (const entry of balls) {
      if (slot >= entry.notes.length) {
        continue
      }
      shots.push({
        sequenceOrder,
        playerId: entry.playerId,
        note: entry.notes[slot]!,
        shotType: entry.shotTypes[slot] ?? 'point',
        distance: entry.distances[slot] ?? null,
        isCochonnet: entry.isCochonnet?.[slot] === true,
      })
      sequenceOrder += 1
    }
  }

  return shots
}

export function ensureEndHasShotStructure(end: EndRecord): void {
  if (!Array.isArray(end.shots)) {
    end.shots = []
  }
}

export function hasValidShotSequence(end: EndRecord, ballsPerPlayer: number): boolean {
  if (end.shots.length === 0) {
    return true
  }
  const orders = [...end.shots.map((shot) => shot.sequenceOrder)].sort((a, b) => a - b)
  const expected = Array.from({ length: orders.length }, (_, index) => index + 1)
  if (orders.some((value, index) => value !== expected[index])) {
    return false
  }

  const perPlayer = new Map<number, number>()
  for (const shot of end.shots) {
    perPlayer.set(shot.playerId, (perPlayer.get(shot.playerId) ?? 0) + 1)
    if ((perPlayer.get(shot.playerId) ?? 0) > ballsPerPlayer) {
      return false
    }
  }

  return true
}
