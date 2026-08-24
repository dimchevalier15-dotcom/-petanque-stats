import type { MatchSummaryPlayer } from './MatchSummary'

export type EndTotalTone = 'p2' | 'p1' | 'p0' | 'm1' | 'm2'

export function formatSignedTotal(value: number): string {
  return value > 0 ? `+${value}` : String(value)
}

export function endTotalTone(value: number): EndTotalTone {
  if (value >= 2) return 'p2'
  if (value === 1) return 'p1'
  if (value === 0) return 'p0'
  if (value === -1) return 'm1'
  return 'm2'
}

export function endTotalByIndex(player: MatchSummaryPlayer, endIndex: number): number | null {
  const cell = player.endTotals?.find((entry) => entry.endIndex === endIndex)
  return cell === undefined ? null : cell.total
}

export function playerEndTotalsSum(player: MatchSummaryPlayer): number | null {
  const totals = player.endTotals ?? []
  if (totals.length === 0) {
    return null
  }
  return totals.reduce((sum, cell) => sum + cell.total, 0)
}
