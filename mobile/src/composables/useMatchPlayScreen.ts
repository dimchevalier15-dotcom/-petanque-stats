import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useMatchPlay, type MatchSetup } from './useMatchPlay'
import { useMatchTeamLabels } from './useMatchTeamLabels'
import type { MatchContext } from '../models/MatchContext'
import type { BallNote, EndRecord, TeamSide } from '../models/MatchPlay'
import { DEFAULT_TARGET_SCORE, type MatchType, type ShotType, type StatisticsMode } from '../models/Match'
import { matchesService } from '../services/matches'
import { playersService } from '../services/players'
import type { CompleteMatchRequestDto } from '../dto/match/CompleteMatchRequest'

export type BallVisualState = 'empty' | 'played' | 'next' | 'locked'

export interface ParsedMatchSetup extends MatchSetup {
  type: MatchType
}

function parseSetupFromRoute(routeQuery: Record<string, string | undefined>, matchId: number): ParsedMatchSetup | null {
  const type = (routeQuery.type as MatchType) || 'doublette'
  const statisticsMode = (routeQuery.statisticsMode as StatisticsMode) || 'standard'
  const teamA = routeQuery.teamA ? routeQuery.teamA.split(',').map((x) => Number(x)) : []
  const teamB = routeQuery.teamB ? routeQuery.teamB.split(',').map((x) => Number(x)) : []

  if (!matchId || teamA.length === 0 || teamB.length === 0) {
    return null
  }

  const trackedPlayers = routeQuery.tracked
    ? routeQuery.tracked.split(',').map((x) => Number(x))
    : [...teamA, ...teamB]

  const defaultShotTypes: Record<number, ShotType> = {}
  const defaultsParam = routeQuery.defaults || ''
  if (defaultsParam) {
    for (const pair of defaultsParam.split(',')) {
      const [pidStr, shotType] = pair.split(':')
      const pid = Number(pidStr)
      if (pid && (shotType === 'point' || shotType === 'tir')) {
        defaultShotTypes[pid] = shotType
      }
    }
  }

  return {
    id: matchId,
    type,
    targetScore: DEFAULT_TARGET_SCORE,
    statisticsMode,
    teamA,
    teamB,
    trackedPlayers,
    defaultShotTypes,
  }
}

export function useMatchPlayScreen() {
  const { t } = useI18n()
  const route = useRoute()
  const router = useRouter()

  const matchId = Number(route.params.id)
  const setup = parseSetupFromRoute(route.query as Record<string, string | undefined>, matchId)
  const playEngine = setup ? useMatchPlay(setup) : null

  const context = ref<MatchContext | null>(null)
  const names = ref<Record<number, string>>({})
  const { teamALabel, teamBLabel } = useMatchTeamLabels(context, t)

  const scoreDialog = ref(false)
  const cancelDialog = ref(false)
  const finishDialog = ref(false)
  const formChartDialog = ref(false)
  const selectedChartPlayerId = ref<number | null>(null)
  const noteCtx = ref<{ playerId: number; noteIndex: number } | null>(null)
  const shotType = ref<ShotType>('point')
  const winner = ref<TeamSide | null>(null)
  const points = ref<number | null>(null)

  const shotOptions = computed(() => [
    { label: t('play.shots.point'), value: 'point' as ShotType },
    { label: t('play.shots.tir'), value: 'tir' as ShotType },
  ])

  const winnerOptions = computed(() => [
    { label: teamALabel.value, value: 'A' as TeamSide },
    { label: teamBLabel.value, value: 'B' as TeamSide },
  ])

  const isCurrentEndClosed = computed(() => {
    if (!playEngine) return false
    const end = playEngine.currentEnd.value
    return end.canceled === true || (end.winner !== undefined && end.points !== undefined)
  })

  const canGoNextEnd = computed(() => {
    if (!playEngine) return false
    return playEngine.currentEndIndex.value < playEngine.ends.length - 1
  })

  function nameFor(playerId: number): string {
    return names.value[playerId] ?? `#${playerId}`
  }

  function shortNameFor(playerId: number): string {
    const full = nameFor(playerId)
    const nickMatch = full.match(/^([^(]+)/)
    return nickMatch ? nickMatch[1].trim() : full
  }

  function isTracked(playerId: number): boolean {
    return setup?.trackedPlayers.includes(playerId) ?? false
  }

  function noteAt(playerId: number, idx: number): BallNote | undefined {
    if (!playEngine) return undefined
    const entry = playEngine.currentEnd.value.balls.find((ball) => ball.playerId === playerId)
    return entry?.notes[idx]
  }

  function shotAt(playerId: number, idx: number): ShotType | undefined {
    if (!playEngine) return undefined
    const entry = playEngine.currentEnd.value.balls.find((ball) => ball.playerId === playerId)
    return entry?.shotTypes[idx]
  }

  function ballState(playerId: number, noteIndex: number): BallVisualState {
    if (!playEngine || !isTracked(playerId)) return 'locked'
    if (playEngine.isFinished.value || isCurrentEndClosed.value) {
      return noteAt(playerId, noteIndex) === undefined ? 'locked' : 'played'
    }
    if (!playEngine.canPlayBallSlot(playEngine.currentEnd.value, playerId, noteIndex)) {
      return noteAt(playerId, noteIndex) === undefined ? 'locked' : 'played'
    }
    const entry = playEngine.currentEnd.value.balls.find((ball) => ball.playerId === playerId)
    const nextIndex = entry?.notes.length ?? 0
    if (noteIndex === nextIndex) return 'next'
    return noteAt(playerId, noteIndex) === undefined ? 'empty' : 'played'
  }

  function canEnterBall(playerId: number, noteIndex: number): boolean {
    if (!playEngine || playEngine.isFinished.value || isCurrentEndClosed.value) return false
    return playEngine.canPlayBallSlot(playEngine.currentEnd.value, playerId, noteIndex)
  }

  function formatNote(note: number): string {
    return note > 0 ? `+${note}` : String(note)
  }

  function ballLabel(playerId: number, idx: number): string {
    const note = noteAt(playerId, idx)
    if (note === undefined) return ''
    return formatNote(note)
  }

  function shotBadge(playerId: number, idx: number): string {
    const shot = shotAt(playerId, idx)
    if (!shot) return ''
    return shot === 'tir' ? t('play.shots.tirShort') : t('play.shots.pointShort')
  }

  function noteTone(note: BallNote | undefined): string {
    if (note === undefined) return 'neutral'
    switch (note) {
      case -2:
        return 'bad'
      case -1:
        return 'warn'
      case 0:
        return 'neutral'
      case 1:
        return 'good'
      case 2:
        return 'great'
      default:
        return 'neutral'
    }
  }

  function prepareNote(playerId: number, noteIndex: number): void {
    noteCtx.value = { playerId, noteIndex }
    shotType.value = shotAt(playerId, noteIndex) ?? setup?.defaultShotTypes?.[playerId] ?? 'point'
  }

  function applyNote(value: BallNote, hideOverlay: () => void): void {
    if (!playEngine || !noteCtx.value) return
    playEngine.setNoteWithShot(noteCtx.value.playerId, noteCtx.value.noteIndex, value, shotType.value)
    hideOverlay()
  }

  function openFormChart(playerId: number): void {
    if (!isTracked(playerId)) return
    selectedChartPlayerId.value = playerId
    formChartDialog.value = true
  }

  function confirmEndScore(): void {
    if (!playEngine || !winner.value || !points.value) return
    playEngine.setEndScore(winner.value, points.value)
    scoreDialog.value = false
  }

  function reopenEndDialog(): void {
    winner.value = winner.value ?? null
    points.value = points.value ?? 1
    scoreDialog.value = true
  }

  function confirmCancelEnd(): void {
    playEngine?.cancelCurrentEnd()
    cancelDialog.value = false
  }

  async function confirmFinish(): Promise<void> {
    if (!playEngine || !setup) return
    finishDialog.value = false
    const payload: CompleteMatchRequestDto = playEngine.toSubmission()
    await matchesService.complete(matchId, payload)
    router.push({ name: 'matchSummary', params: { id: matchId } })
  }

  if (playEngine) {
    watch(playEngine.currentEndComplete, (complete) => {
      if (complete && !playEngine.currentEnd.value.points && !isCurrentEndClosed.value) {
        winner.value = null
        points.value = 1
        scoreDialog.value = true
      }
    })
  }

  onMounted(async () => {
    if (!setup) {
      router.replace({ name: 'home' })
      return
    }

    const ids = Array.from(new Set([...setup.teamA, ...setup.teamB]))
    try {
      const [contextData, ...playerResults] = await Promise.all([
        matchesService.getContext(matchId),
        ...ids.map((id) => playersService.getById(id)),
      ])
      context.value = contextData
      for (const player of playerResults) {
        const full = `${player.firstName} ${player.lastName}`.trim()
        names.value[player.id] = player.nickname ? `${player.nickname} (${full})` : full
      }
    } catch {
      // keep fallback labels
    }
  })

  return {
    setup,
    playEngine,
    currentEndIndex: playEngine?.currentEndIndex ?? ref(0),
    currentEnd: playEngine?.currentEnd ?? computed((): EndRecord => ({ index: 1, balls: [] })),
    ends: playEngine?.ends ?? [],
    scoreA: playEngine?.scoreA ?? ref(0),
    scoreB: playEngine?.scoreB ?? ref(0),
    isFinished: playEngine?.isFinished ?? computed(() => false),
    ballsPerPlayer: playEngine?.ballsPerPlayer ?? computed(() => 3),
    canValidateEnd: playEngine?.canValidateEnd ?? computed(() => false),
    currentEndComplete: playEngine?.currentEndComplete ?? computed(() => false),
    notesOptions: () => playEngine?.notesOptions() ?? [],
    goPrevEnd: () => playEngine?.goPrevEnd(),
    goNextEnd: () => playEngine?.goNextEnd(),
    teamALabel,
    teamBLabel,
    scoreDialog,
    cancelDialog,
    finishDialog,
    formChartDialog,
    selectedChartPlayerId,
    noteCtx,
    shotType,
    winner,
    points,
    shotOptions,
    winnerOptions,
    isCurrentEndClosed,
    canGoNextEnd,
    shortNameFor,
    isTracked,
    noteAt,
    ballState,
    canEnterBall,
    formatNote,
    ballLabel,
    shotBadge,
    noteTone,
    prepareNote,
    applyNote,
    openFormChart,
    confirmEndScore,
    reopenEndDialog,
    confirmCancelEnd,
    confirmFinish,
  }
}
