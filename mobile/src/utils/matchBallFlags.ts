import type { EndBallEntry } from '../models/MatchPlay'

export function isCochonnetShot(entry: EndBallEntry, index: number): boolean {
  return entry.isCochonnet?.[index] === true
}

export function normalizeBallEntry(ball: EndBallEntry): EndBallEntry {
  return {
    ...ball,
    distances: ball.distances ?? ball.notes.map(() => null),
    isCochonnet: ball.isCochonnet ?? ball.notes.map(() => false),
  }
}
