import type { ComposerTranslation } from 'vue-i18n'
import type { MatchSummary, MatchSummaryPlayer, MatchSummaryShotBreakdown } from '../models/MatchSummary'
import { successRateFromNoteCounts } from './matchSuccessRate'
import { buildNoteDistributionChart } from './usePlayerStatsCharts'

interface ChartBundle {
  data: object
  options: object
}

const TEAM_COLORS = {
  A: '#22C55E',
  B: '#3B82F6',
} as const

export function playerBallCount(player: MatchSummaryPlayer): number {
  return player.p2 + player.p1 + player.p0 + player.m1 + player.m2
}

export function playerToOverallBreakdown(player: MatchSummaryPlayer): MatchSummaryShotBreakdown {
  return {
    average: player.average,
    p2: player.p2,
    p1: player.p1,
    p0: player.p0,
    m1: player.m1,
    m2: player.m2,
    successRate: successRateFromNoteCounts(player.p2, player.p1, player.p0, player.m1, player.m2),
  }
}

export function mergeTeamBreakdown(players: MatchSummaryPlayer[]): MatchSummaryShotBreakdown | null {
  if (players.length === 0) {
    return null
  }

  let p2 = 0
  let p1 = 0
  let p0 = 0
  let m1 = 0
  let m2 = 0
  let sum = 0

  for (const player of players) {
    const count = playerBallCount(player)
    if (count === 0) {
      continue
    }
    p2 += player.p2
    p1 += player.p1
    p0 += player.p0
    m1 += player.m1
    m2 += player.m2
    sum += player.average * count
  }

  const total = p2 + p1 + p0 + m1 + m2
  if (total === 0) {
    return null
  }

  return {
    average: sum / total,
    p2,
    p1,
    p0,
    m1,
    m2,
    successRate: successRateFromNoteCounts(p2, p1, p0, m1, m2),
  }
}

export function playerDisplayName(player: MatchSummaryPlayer): string {
  const base = `${player.firstName} ${player.lastName}`.trim()
  return player.nickname ? `${player.nickname} (${base})` : base
}

export function playerShortName(player: MatchSummaryPlayer): string {
  return player.nickname || player.firstName
}

export function buildPlayerComparisonChart(
  players: MatchSummaryPlayer[],
  t: ComposerTranslation,
): ChartBundle | null {
  const withData = players.filter((player) => playerBallCount(player) > 0)
  if (withData.length === 0) {
    return null
  }

  return {
    data: {
      labels: withData.map((player) => playerShortName(player)),
      datasets: [
        {
          label: t('summary.charts.average'),
          data: withData.map((player) => player.average),
          backgroundColor: withData.map((player) => TEAM_COLORS[player.team]),
          borderRadius: 6,
          barThickness: 18,
        },
      ],
    },
    options: {
      indexAxis: 'y' as const,
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
      },
      scales: {
        x: {
          suggestedMin: -2,
          suggestedMax: 2,
          grid: { color: 'rgba(0,0,0,0.06)' },
        },
        y: {
          grid: { display: false },
        },
      },
    },
  }
}

export function buildTeamDistributionChart(
  players: MatchSummaryPlayer[],
  t: ComposerTranslation,
): ChartBundle | null {
  return buildNoteDistributionChart(mergeTeamBreakdown(players), t)
}

export function buildPlayerDistributionChart(
  player: MatchSummaryPlayer,
  t: ComposerTranslation,
): ChartBundle | null {
  return buildNoteDistributionChart(playerToOverallBreakdown(player), t)
}

export function buildPlayerShotChart(
  breakdown: MatchSummaryShotBreakdown | null | undefined,
  t: ComposerTranslation,
): ChartBundle | null {
  if (!breakdown) {
    return null
  }
  return buildNoteDistributionChart(breakdown, t)
}

export function hasTrackedData(summary: MatchSummary): boolean {
  return summary.players.some((player) => playerBallCount(player) > 0)
}
