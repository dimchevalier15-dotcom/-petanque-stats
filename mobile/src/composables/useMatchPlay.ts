import { computed, reactive, ref, watch } from 'vue'
import type { PlayerRole } from '../models/Match'
import type { MatchPlayState, MatchSetup } from '../models/MatchDraft'
import type { BallNote, EndRecord, TeamSide } from '../models/MatchPlay'
import {
  cycleTripletteRole,
  inferStartingRoles,
  roleToShot,
  snapshotEndRoles,
  syncCurrentRolesFromEnd,
  teamForPlayer,
  totalBallsInEnd,
} from '../utils/matchRoles'

export type { MatchSetup } from '../models/MatchDraft'

function allPlayerIds(setup: MatchSetup): number[] {
  return [...setup.teamA, ...setup.teamB]
}

function resolveInitialCurrentRoles(setup: MatchSetup, initial?: MatchPlayState): Record<number, PlayerRole> {
  if (initial?.currentRoles && Object.keys(initial.currentRoles).length > 0) {
    return { ...initial.currentRoles }
  }

  const startingRoles = inferStartingRoles(
    setup.type,
    setup.teamA,
    setup.teamB,
    setup.defaultShotTypes,
    setup.startingRoles,
  )

  if (initial?.ends?.length) {
    const end = initial.ends[initial.currentEndIndex ?? 0]
    return syncCurrentRolesFromEnd(end, startingRoles)
  }

  return { ...startingRoles }
}

export function useMatchPlay(setup: MatchSetup, initial?: MatchPlayState, onPersist?: (state: MatchPlayState) => void) {
  const currentEndIndex = ref(initial?.currentEndIndex ?? 0)
  const ends = reactive<EndRecord[]>(
    initial?.ends?.length
      ? initial.ends.map((end) => ({ ...end, balls: end.balls.map((b) => ({ ...b, distances: b.distances ?? [] })) }))
      : [{ index: 1, balls: [], winner: undefined, points: undefined, canceled: false }],
  )

  const startingRoles = inferStartingRoles(
    setup.type,
    setup.teamA,
    setup.teamB,
    setup.defaultShotTypes,
    setup.startingRoles,
  )

  const currentRoles = reactive<Record<number, PlayerRole>>(resolveInitialCurrentRoles(setup, initial))

  const ballsPerPlayer = computed(() => (setup.type === 'triplette' ? 2 : 3))
  const allPlayers = computed<number[]>(() => allPlayerIds(setup))
  const trackedSet = computed(() => new Set<number>(setup.trackedPlayers))
  const showRoles = computed(() => setup.type === 'doublette' || setup.type === 'triplette')

  function roleFor(playerId: number): PlayerRole {
    const end = ends[currentEndIndex.value]
    return end?.roles?.[playerId] ?? currentRoles[playerId] ?? startingRoles[playerId] ?? 'pointeur'
  }

  function shotDefaultFor(playerId: number): 'point' | 'tir' {
    return roleToShot(roleFor(playerId))
  }

  function ensureEndRoles(end: EndRecord): void {
    if (!end.roles) {
      snapshotEndRoles(end, currentRoles, allPlayers.value)
    }
  }

  function applyRolesToCurrentEnd(): void {
    const end = ends[currentEndIndex.value]
    if (!end) return
    snapshotEndRoles(end, currentRoles, allPlayers.value)
  }

  function swapDoubletteTeamRoles(team: 'A' | 'B'): void {
    const teamIds = team === 'A' ? setup.teamA : setup.teamB
    if (teamIds.length !== 2) return
    const [p1, p2] = teamIds
    const r1 = currentRoles[p1]
    const r2 = currentRoles[p2]
    currentRoles[p1] = r2
    currentRoles[p2] = r1
    applyRolesToCurrentEnd()
  }

  function maybeRotateOnFirstBall(playerId: number, end: EndRecord, ballsBefore: number): void {
    if (setup.type !== 'doublette' || ballsBefore > 0) {
      return
    }

    if (roleFor(playerId) !== 'tireur') {
      return
    }

    const team = teamForPlayer(playerId, setup.teamA, setup.teamB)
    if (team) {
      swapDoubletteTeamRoles(team)
    }
  }

  function setPlayerRole(playerId: number, role: PlayerRole): void {
    currentRoles[playerId] = role
    applyRolesToCurrentEnd()
  }

  function cyclePlayerRole(playerId: number): void {
    if (setup.type !== 'triplette') return
    setPlayerRole(playerId, cycleTripletteRole(roleFor(playerId)))
  }

  function ensureEndStructure(end: EndRecord): void {
    if (end.balls.length === 0) {
      end.balls = allPlayers.value
        .filter((id) => trackedSet.value.has(id))
        .map((playerId) => ({
          playerId,
          notes: [] as BallNote[],
          shotTypes: [] as ('point' | 'tir')[],
          distances: [] as (number | null)[],
        }))
    }
    for (const entry of end.balls) {
      if (entry.notes.length > ballsPerPlayer.value) {
        entry.notes = entry.notes.slice(0, ballsPerPlayer.value)
      }
      if (entry.shotTypes.length > ballsPerPlayer.value) {
        entry.shotTypes = entry.shotTypes.slice(0, ballsPerPlayer.value)
      }
      if (!entry.distances) {
        entry.distances = []
      }
      if (entry.distances.length > ballsPerPlayer.value) {
        entry.distances = entry.distances.slice(0, ballsPerPlayer.value)
      }
    }
    ensureEndRoles(end)
  }

  ensureEndStructure(ends[currentEndIndex.value] ?? ends[0])

  const distanceEstimate = ref<number | null>(initial?.distanceEstimate ?? null)

  function snapshot(): MatchPlayState {
    return {
      currentEndIndex: currentEndIndex.value,
      ends: ends.map((end) => ({
        ...end,
        roles: end.roles ? { ...end.roles } : undefined,
        balls: end.balls.map((ball) => ({
          ...ball,
          notes: [...ball.notes],
          shotTypes: [...ball.shotTypes],
          distances: [...(ball.distances ?? [])],
        })),
      })),
      distanceEstimate: distanceEstimate.value,
      currentRoles: { ...currentRoles },
    }
  }

  function persist(): void {
    onPersist?.(snapshot())
  }

  watch([currentEndIndex, distanceEstimate], persist)
  watch(ends, persist, { deep: true })
  watch(currentRoles, persist, { deep: true })

  const scoreA = ref(0)
  const scoreB = ref(0)

  function recomputeGlobalScore(): void {
    let a = 0
    let b = 0
    for (const e of ends) {
      if (e.canceled) continue
      if (e.winner && e.points) {
        if (e.winner === 'A') a += e.points
        else b += e.points
      }
    }
    scoreA.value = a
    scoreB.value = b
  }

  watch(ends, () => recomputeGlobalScore(), { deep: true })
  recomputeGlobalScore()

  const isFinished = computed(() => scoreA.value >= setup.targetScore || scoreB.value >= setup.targetScore)

  function syncRolesForEndIndex(index: number): void {
    const end = ends[index]
    if (!end) return
    const merged = syncCurrentRolesFromEnd(end, startingRoles)
    for (const playerId of allPlayers.value) {
      currentRoles[playerId] = merged[playerId]
    }
  }

  function goPrevEnd(): void {
    if (currentEndIndex.value > 0) {
      currentEndIndex.value -= 1
      syncRolesForEndIndex(currentEndIndex.value)
    }
  }

  function goNextEnd(): void {
    if (currentEndIndex.value < ends.length - 1) {
      currentEndIndex.value += 1
      syncRolesForEndIndex(currentEndIndex.value)
    }
  }

  function addEndIfNeeded(): void {
    const last = ends[ends.length - 1]
    if (last && !isEndScored(last)) {
      return
    }
    const e: EndRecord = { index: ends.length + 1, balls: [], winner: undefined, points: undefined, canceled: false }
    for (const playerId of allPlayers.value) {
      currentRoles[playerId] = currentRoles[playerId] ?? startingRoles[playerId]
    }
    snapshotEndRoles(e, currentRoles, allPlayers.value)
    ensureEndStructure(e)
    ends.push(e)
  }

  function continueMatchAfterEndChange(): void {
    const wasOnLastEnd = currentEndIndex.value === ends.length - 1
    recomputeGlobalScore()
    if (isFinished.value) {
      return
    }
    addEndIfNeeded()
    if (wasOnLastEnd) {
      currentEndIndex.value += 1
      distanceEstimate.value = null
    }
  }

  function notesOptions(): BallNote[] {
    return setup.statisticsMode === 'standard' ? [-2, -1, 0, 1, 2] : [-1, 1]
  }

  function hasAnyPlayedBall(end: EndRecord): boolean {
    return end.balls.some((entry) => entry.notes.length > 0)
  }

  function isEndScored(end: EndRecord): boolean {
    return end.canceled === true || (end.winner !== undefined && end.points !== undefined)
  }

  function canPlayBallSlot(end: EndRecord, playerId: number, noteIndex: number): boolean {
    const entry = end.balls.find((b) => b.playerId === playerId)
    if (!entry) {
      return false
    }
    return noteIndex <= entry.notes.length
  }

  function setDistanceEstimate(value: number | null): void {
    distanceEstimate.value = value
  }

  function setNoteWithShot(
    playerId: number,
    noteIndex: number,
    value: BallNote | null,
    shotType?: 'point' | 'tir',
  ): void {
    const end = ends[currentEndIndex.value]
    ensureEndStructure(end)
    const entry = end.balls.find((b) => b.playerId === playerId)
    if (!entry) return
    const max = ballsPerPlayer.value
    if (noteIndex >= max) return

    const defaultShot = shotType ?? shotDefaultFor(playerId)

    if (value === null) {
      if (noteIndex < entry.notes.length) {
        entry.notes.splice(noteIndex, 1)
        entry.shotTypes.splice(noteIndex, 1)
        entry.distances.splice(noteIndex, 1)
      }
      return
    }

    if (noteIndex < entry.notes.length) {
      entry.notes[noteIndex] = value
      entry.shotTypes[noteIndex] = defaultShot
      return
    }

    if (noteIndex === entry.notes.length) {
      const ballsBefore = totalBallsInEnd(end)
      entry.notes.push(value)
      entry.shotTypes.push(defaultShot)
      entry.distances.push(distanceEstimate.value)
      maybeRotateOnFirstBall(playerId, end, ballsBefore)
    }
  }

  function setNote(playerId: number, noteIndex: number, value: BallNote | null): void {
    setNoteWithShot(playerId, noteIndex, value)
  }

  function allTrackedNotesFilled(end: EndRecord): boolean {
    for (const entry of end.balls) {
      if (entry.notes.length < ballsPerPlayer.value) return false
    }
    return true
  }

  const currentEnd = computed(() => ends[currentEndIndex.value])
  const currentEndComplete = computed(() => allTrackedNotesFilled(currentEnd.value))
  const canValidateEnd = computed(() => hasAnyPlayedBall(currentEnd.value))
  const canEditRoles = computed(() => setup.type === 'triplette')

  function setEndScore(winner: TeamSide, points: number): void {
    const end = currentEnd.value
    end.canceled = false
    end.winner = winner
    end.points = points
    snapshotEndRoles(end, currentRoles, allPlayers.value)
    continueMatchAfterEndChange()
  }

  function cancelCurrentEnd(): void {
    const end = currentEnd.value
    end.canceled = true
    end.winner = 'A'
    end.points = 0
    snapshotEndRoles(end, currentRoles, allPlayers.value)
    continueMatchAfterEndChange()
  }

  function colorFor(note: BallNote | undefined): string {
    if (note === undefined) return 'neutral'
    switch (note) {
      case -2:
        return 'red'
      case -1:
        return 'orange'
      case 0:
        return 'gray'
      case 1:
        return 'light-green'
      case 2:
        return 'green'
      default:
        return 'neutral'
    }
  }

  function toSubmission() {
    return {
      type: setup.type,
      targetScore: setup.targetScore,
      statisticsMode: setup.statisticsMode,
      teamA: setup.teamA,
      teamB: setup.teamB,
      trackedPlayers: setup.trackedPlayers,
      ends: ends
        .filter((e) => e.canceled === true || (e.winner !== undefined && e.points !== undefined))
        .map((e) => ({
          index: e.index,
          winner: (e.winner as TeamSide) ?? 'A',
          points: e.canceled ? 0 : ((e.points as number) ?? 0),
          canceled: e.canceled === true,
          balls: e.balls.map((b) => ({
            playerId: b.playerId,
            notes: b.notes,
            shotTypes: b.shotTypes,
            distances: b.distances,
          })),
          roles: allPlayers.value.map((playerId) => ({
            playerId,
            role: e.roles?.[playerId] ?? startingRoles[playerId] ?? 'pointeur',
          })),
        })),
    }
  }

  return {
    currentEndIndex,
    currentEnd,
    ends,
    scoreA,
    scoreB,
    isFinished,
    ballsPerPlayer,
    showRoles,
    roleFor,
    shotDefaultFor,
    cyclePlayerRole,
    canEditRoles,
    goPrevEnd,
    goNextEnd,
    setNote,
    setNoteWithShot,
    setEndScore,
    distanceEstimate,
    setDistanceEstimate,
    notesOptions,
    currentEndComplete,
    canValidateEnd,
    canPlayBallSlot,
    hasAnyPlayedBall,
    colorFor,
    toSubmission,
    cancelCurrentEnd,
  }
}
