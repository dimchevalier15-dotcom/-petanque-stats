import { computed, reactive, ref, watch } from 'vue'
import type { PlayerRole } from '../models/Match'
import type { MatchPlayState, MatchSetup } from '../models/MatchDraft'
import type { BallNote, EndRecord, TeamSide } from '../models/MatchPlay'
import {
  activePlayerForSlot,
  activeTeamPlayerIds,
  allMatchPlayerIds,
  canTeamSubstitute,
  isPlayerInMatch,
  substitutionsAllowed,
  teamForActivePlayer,
  teamSlotsForEnd,
} from '../utils/matchSubstitutions'
import type { TeamSubstitution } from '../models/MatchPlay'
import { matchScore } from '../utils/matchScore'
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

function allPlayerIds(setup: MatchSetup, substitutions: TeamSubstitution[]): number[] {
  return allMatchPlayerIds(setup.teamA, setup.teamB, substitutions)
}

function trackedPlayerIdsForEnd(
  setup: MatchSetup,
  substitutions: TeamSubstitution[],
  endIndex: number,
  end: EndRecord,
): number[] {
  const ids = new Set<number>()

  for (const playerId of setup.trackedPlayers) {
    const team = teamForPlayer(playerId, setup.teamA, setup.teamB)
    if (!team) {
      continue
    }

    const activeId = activePlayerForSlot(playerId, team, substitutions, endIndex)
    ids.add(activeId)

    const sub = substitutions.find((item) => item.team === team && item.outPlayerId === playerId)
    if (sub && endIndex >= sub.fromEndIndex) {
      const originalEntry = end.balls.find((ball) => ball.playerId === playerId)
      if ((originalEntry?.notes.length ?? 0) > 0) {
        ids.add(playerId)
      }
    } else {
      ids.add(playerId)
    }
  }

  return Array.from(ids)
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
  const openingScoreA = initial?.openingScoreA ?? 0
  const openingScoreB = initial?.openingScoreB ?? 0

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

  const substitutions = reactive<TeamSubstitution[]>(
    initial?.substitutions?.map((sub) => ({ ...sub })) ?? [],
  )

  const ballsPerPlayer = computed(() => (setup.type === 'triplette' ? 2 : 3))
  const allPlayers = computed<number[]>(() => allPlayerIds(setup, substitutions))
  const trackedSet = computed(() => new Set<number>(setup.trackedPlayers))
  const showRoles = computed(() => setup.type === 'doublette' || setup.type === 'triplette')
  const allowSubstitutions = computed(() => substitutionsAllowed(setup.type))
  const canSubstituteTeamA = computed(() => allowSubstitutions.value && canTeamSubstitute('A', substitutions))
  const canSubstituteTeamB = computed(() => allowSubstitutions.value && canTeamSubstitute('B', substitutions))
  const canMakeSubstitution = computed(() => canSubstituteTeamA.value || canSubstituteTeamB.value)

  const teamASlots = computed(() =>
    teamSlotsForEnd(setup.teamA, 'A', substitutions, ends[currentEndIndex.value]?.index ?? 1),
  )
  const teamBSlots = computed(() =>
    teamSlotsForEnd(setup.teamB, 'B', substitutions, ends[currentEndIndex.value]?.index ?? 1),
  )

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
    const endIndex = ends[currentEndIndex.value]?.index ?? 1
    const activeIds = activeTeamPlayerIds(teamIds, team, substitutions, endIndex)
    const [p1, p2] = activeIds
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

    const team = teamForActivePlayer(playerId, setup.teamA, setup.teamB, substitutions)
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
    const trackedIds = trackedPlayerIdsForEnd(setup, substitutions, end.index, end)

    if (end.balls.length === 0) {
      end.balls = trackedIds.map((playerId) => ({
        playerId,
        notes: [] as BallNote[],
        shotTypes: [] as ('point' | 'tir')[],
        distances: [] as (number | null)[],
      }))
    } else {
      for (const playerId of trackedIds) {
        if (!end.balls.some((ball) => ball.playerId === playerId)) {
          end.balls.push({
            playerId,
            notes: [] as BallNote[],
            shotTypes: [] as ('point' | 'tir')[],
            distances: [] as (number | null)[],
          })
        }
      }
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
      substitutions: substitutions.map((sub) => ({ ...sub })),
      openingScoreA,
      openingScoreB,
    }
  }

  function persist(): void {
    onPersist?.(snapshot())
  }

  watch([currentEndIndex, distanceEstimate], persist)
  watch(ends, persist, { deep: true })
  watch(currentRoles, persist, { deep: true })
  watch(substitutions, persist, { deep: true })

  const scoreA = ref(0)
  const scoreB = ref(0)

  function recomputeGlobalScore(): void {
    const total = matchScore(ends, openingScoreA, openingScoreB)
    scoreA.value = total.scoreA
    scoreB.value = total.scoreB
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
    for (const playerId of setup.trackedPlayers) {
      const team = teamForPlayer(playerId, setup.teamA, setup.teamB)
      if (!team) {
        continue
      }
      const activeId = activePlayerForSlot(playerId, team, substitutions, end.index)
      const entry = end.balls.find((ball) => ball.playerId === activeId)
      if (!entry || entry.notes.length < ballsPerPlayer.value) {
        return false
      }
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
    snapshotEndRoles(end, currentRoles, playersForEndRoles(end))
    continueMatchAfterEndChange()
  }

  function cancelCurrentEnd(): void {
    const end = currentEnd.value
    end.canceled = true
    end.winner = 'A'
    end.points = 0
    snapshotEndRoles(end, currentRoles, playersForEndRoles(end))
    continueMatchAfterEndChange()
  }

  function playersForEndRoles(end: EndRecord): number[] {
    const ids = new Set(allPlayers.value)
    for (const ball of end.balls) {
      ids.add(ball.playerId)
    }
    return Array.from(ids)
  }

  function applySubstitution(team: TeamSide, outPlayerId: number, inPlayerId: number): boolean {
    if (!allowSubstitutions.value) {
      return false
    }
    if (!canTeamSubstitute(team, substitutions)) {
      return false
    }

    const teamIds = team === 'A' ? setup.teamA : setup.teamB
    if (!teamIds.includes(outPlayerId)) {
      return false
    }
    if (isPlayerInMatch(inPlayerId, setup.teamA, setup.teamB, substitutions)) {
      return false
    }

    const end = ends[currentEndIndex.value]
    if (!end) {
      return false
    }

    const role = currentRoles[outPlayerId] ?? startingRoles[outPlayerId] ?? 'pointeur'
    substitutions.push({
      team,
      outPlayerId,
      inPlayerId,
      fromEndIndex: end.index,
    })
    currentRoles[inPlayerId] = role
    ensureEndStructure(end)
    snapshotEndRoles(end, currentRoles, playersForEndRoles(end))
    return true
  }

  function substitutionFor(team: TeamSide): TeamSubstitution | undefined {
    return substitutions.find((sub) => sub.team === team)
  }

  function hasPlayedBallsInEnd(playerId: number, end: EndRecord): boolean {
    const entry = end.balls.find((ball) => ball.playerId === playerId)
    return (entry?.notes.length ?? 0) > 0
  }

  function isTracked(playerId: number): boolean {
    if (trackedSet.value.has(playerId)) {
      return true
    }
    const sub = substitutions.find((item) => item.inPlayerId === playerId)
    if (!sub) {
      return false
    }
    return trackedSet.value.has(sub.outPlayerId)
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
    snapshot,
    cancelCurrentEnd,
    substitutions,
    allowSubstitutions,
    canMakeSubstitution,
    canSubstituteTeamA,
    canSubstituteTeamB,
    teamASlots,
    teamBSlots,
    applySubstitution,
    substitutionFor,
    hasPlayedBallsInEnd,
    isTracked,
  }
}
