import type { TournamentMatchSide } from '../models/TournamentDay'

export function formatTournamentMatchSideName(
  side: TournamentMatchSide,
  t: (key: string, params?: Record<string, unknown>) => string,
): string {
  if (side.name) {
    return side.name
  }
  if (side.pendingWinnerOfRound) {
    return t('tournaments.matches.pending', {
      round: t(`tournaments.matches.rounds.${side.pendingWinnerOfRound}`),
      slot: side.pendingWinnerOfSlot ?? '',
    })
  }
  return '—'
}
