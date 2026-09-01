import type { MatchDraft, MatchParticipant } from '../models/MatchDraft'
import type { BallNote, EndRecord } from '../models/MatchPlay'
import type {
  MatchSummary,
  MatchSummaryEndTotal,
  MatchSummaryPlayer,
  MatchSummaryShotBreakdown,
} from '../models/MatchSummary'
import { successRateFromNoteCounts } from '../composables/matchSuccessRate'
import { isCochonnetShot } from './matchBallFlags'
import { shotsForPlayer } from './matchEndShots'
import { matchScore } from './matchScore'

interface NoteAggregate {
  count: number
  sum: number
  p2: number
  p1: number
  p0: number
  m1: number
  m2: number
}

function emptyAggregate(): NoteAggregate {
  return { count: 0, sum: 0, p2: 0, p1: 0, p0: 0, m1: 0, m2: 0 }
}

function addNote(aggregate: NoteAggregate, note: BallNote): void {
  aggregate.count++
  aggregate.sum += note
  if (note === 2) aggregate.p2++
  else if (note === 1) aggregate.p1++
  else if (note === 0) aggregate.p0++
  else if (note === -1) aggregate.m1++
  else if (note === -2) aggregate.m2++
}

function breakdownFromAggregate(aggregate: NoteAggregate): MatchSummaryShotBreakdown | null {
  if (aggregate.count === 0) {
    return null
  }
  return {
    average: aggregate.sum / aggregate.count,
    p2: aggregate.p2,
    p1: aggregate.p1,
    p0: aggregate.p0,
    m1: aggregate.m1,
    m2: aggregate.m2,
    successRate: successRateFromNoteCounts(
      aggregate.p2,
      aggregate.p1,
      aggregate.p0,
      aggregate.m1,
      aggregate.m2,
    ),
  }
}

function playerNamesFromParticipant(participant: MatchParticipant): {
  firstName: string
  lastName: string
  nickname: string
} {
  return {
    firstName: participant.label,
    lastName: '',
    nickname: participant.shortLabel,
  }
}

function teamForPlayer(playerId: number, draft: MatchDraft): 'A' | 'B' {
  if (draft.teamA.includes(playerId)) return 'A'
  return 'B'
}

function aggregatePlayerNotes(
  ends: EndRecord[],
  playerId: number,
  filter?: (end: EndRecord, slotIndex: number, shotType: 'point' | 'tir') => boolean,
): NoteAggregate {
  const aggregate = emptyAggregate()
  for (const end of ends) {
    const playerShots = shotsForPlayer(end, playerId)
    for (let i = 0; i < playerShots.length; i++) {
      const shot = playerShots[i]!
      if (filter && !filter(end, i, shot.shotType)) continue
      addNote(aggregate, shot.note)
    }
  }
  return aggregate
}

function endTotalsForPlayer(ends: EndRecord[], playerId: number): MatchSummaryEndTotal[] {
  const totals: MatchSummaryEndTotal[] = []
  for (const end of ends) {
    const playerShots = shotsForPlayer(end, playerId)
    if (playerShots.length === 0) continue
    const total = playerShots.reduce((sum, shot) => sum + shot.note, 0)
    totals.push({ endIndex: end.index, total })
  }
  return totals
}

export function buildLocalMatchSummary(draft: MatchDraft): MatchSummary {
  const ends = draft.ends
  const { scoreA, scoreB } = matchScore(ends, draft.openingScoreA ?? 0, draft.openingScoreB ?? 0)
  const winner: 'A' | 'B' = scoreA >= scoreB ? 'A' : 'B'

  const endIndexes: number[] = []
  const canceledEndIndexes: number[] = []
  for (const end of ends) {
    endIndexes.push(end.index)
    if (end.canceled) {
      canceledEndIndexes.push(end.index)
    }
  }

  const participantMap = new Map(draft.participants.map((participant) => [participant.id, participant]))
  const trackedIds = draft.trackedPlayers

  const players: MatchSummaryPlayer[] = trackedIds.map((playerId) => {
    const participant = participantMap.get(playerId)
    const names = participant
      ? playerNamesFromParticipant(participant)
      : { firstName: `#${playerId}`, lastName: '', nickname: `#${playerId}` }

    const overall = aggregatePlayerNotes(ends, playerId)
    const pointAgg = aggregatePlayerNotes(ends, playerId, (_end, index, shotType) => {
      return shotType === 'point' && !isCochonnetShot(_end, playerId, index)
    })
    const tirAgg = aggregatePlayerNotes(ends, playerId, (_end, index, shotType) => {
      return shotType === 'tir' && !isCochonnetShot(_end, playerId, index)
    })
    const cochonnetAgg = aggregatePlayerNotes(ends, playerId, (end, index) => {
      return isCochonnetShot(end, playerId, index)
    })

    const average = overall.count > 0 ? Math.round((overall.sum / overall.count) * 100) / 100 : 0

    return {
      playerId,
      firstName: names.firstName,
      lastName: names.lastName,
      nickname: names.nickname,
      team: teamForPlayer(playerId, draft),
      average,
      p2: overall.p2,
      p1: overall.p1,
      p0: overall.p0,
      m1: overall.m1,
      m2: overall.m2,
      point: breakdownFromAggregate(pointAgg),
      tir: breakdownFromAggregate(tirAgg),
      cochonnet: breakdownFromAggregate(cochonnetAgg),
      endTotals: endTotalsForPlayer(ends, playerId),
    }
  })

  return {
    matchId: draft.id,
    scoreA,
    scoreB,
    winner,
    ends: ends.length,
    type: draft.type,
    endIndexes,
    canceledEndIndexes,
    players,
  }
}
