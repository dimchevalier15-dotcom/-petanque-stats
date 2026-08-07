import { computed, reactive, ref, watch } from 'vue'
import type { MatchType, StatisticsMode } from '../models/Match'
import type { BallNote, EndRecord, TeamSide } from '../models/MatchPlay'

export interface MatchSetup {
  id: number
  type: MatchType
  targetScore: number
  statisticsMode: StatisticsMode
  teamA: number[]
  teamB: number[]
  trackedPlayers: number[]
  defaultShotTypes?: Record<number, 'point' | 'tir'>
}

export function useMatchPlay(setup: MatchSetup) {
  const currentEndIndex = ref(0) // 0-based index
  const ends = reactive<EndRecord[]>([
    { index: 1, balls: [], winner: undefined, points: undefined, canceled: false },
  ])

  // Precompute balls per player per end depending on type
  const ballsPerPlayer = computed(() => (setup.type === 'triplette' ? 2 : 3))

  const allPlayers = computed<number[]>(() => [...setup.teamA, ...setup.teamB])
  const trackedSet = computed(() => new Set<number>(setup.trackedPlayers))

  function ensureEndStructure(end: EndRecord): void {
    if (end.balls.length === 0) {
      // initialize entries for tracked players only
      end.balls = allPlayers.value
        .filter((id) => trackedSet.value.has(id))
        .map((playerId) => ({ playerId, notes: [] as BallNote[], shotTypes: [] as ('point'|'tir')[] }))
    }
    // clamp length of arrays
    for (const entry of end.balls) {
      if (entry.notes.length > ballsPerPlayer.value) {
        entry.notes = entry.notes.slice(0, ballsPerPlayer.value)
      }
      if (entry.shotTypes.length > ballsPerPlayer.value) {
        entry.shotTypes = entry.shotTypes.slice(0, ballsPerPlayer.value)
      }
    }
  }

  // call on creation
  ensureEndStructure(ends[0])

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

  const isFinished = computed(() => scoreA.value >= setup.targetScore || scoreB.value >= setup.targetScore)

  function goPrevEnd(): void {
    if (currentEndIndex.value > 0) currentEndIndex.value -= 1
  }
  function goNextEnd(): void {
    if (currentEndIndex.value < ends.length - 1) currentEndIndex.value += 1
  }

  function addEndIfNeeded(): void {
    if (currentEndIndex.value === ends.length - 1) {
      const e: EndRecord = { index: ends.length + 1, balls: [], winner: undefined, points: undefined, canceled: false }
      ensureEndStructure(e)
      ends.push(e)
    }
  }

  function notesOptions(): BallNote[] {
    return setup.statisticsMode === 'standard' ? [-2, -1, 0, 1, 2] : [-1, 1]
  }

  function setNoteWithShot(playerId: number, noteIndex: number, value: BallNote | null, shotType?: 'point' | 'tir'): void {
    if (isFinished.value) return
    const end = ends[currentEndIndex.value]
    ensureEndStructure(end)
    const entry = end.balls.find((b) => b.playerId === playerId)
    if (!entry) return
    const max = ballsPerPlayer.value
    if (noteIndex >= max) return
    if (value === null) {
      // remove this note and its shot type
      if (noteIndex < entry.notes.length) {
        entry.notes.splice(noteIndex, 1)
      }
      if (noteIndex < entry.shotTypes.length) {
        entry.shotTypes.splice(noteIndex, 1)
      }
    } else {
      // fill preceding slots to allow random access
      while (entry.notes.length < noteIndex) entry.notes.push(0)
      while (entry.shotTypes.length < noteIndex) entry.shotTypes.push(setup.defaultShotTypes?.[playerId] ?? 'point')
      entry.notes[noteIndex] = value
      entry.shotTypes[noteIndex] = shotType ?? (setup.defaultShotTypes?.[playerId] ?? 'point')
      if (entry.notes.length > max) entry.notes = entry.notes.slice(0, max)
      if (entry.shotTypes.length > max) entry.shotTypes = entry.shotTypes.slice(0, max)
    }
    // autoshow end scoring if all notes filled for tracked players
    if (allTrackedNotesFilled(end)) {
      // no-op here; the view can open the dialog when this computed flips to true
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

  function setEndScore(winner: TeamSide, points: number): void {
    const end = currentEnd.value
    end.canceled = false
    end.winner = winner
    end.points = points
    recomputeGlobalScore()
    if (!isFinished.value) {
      addEndIfNeeded()
      currentEndIndex.value += 1
    }
  }

  function cancelCurrentEnd(): void {
    if (isFinished.value) return
    const end = currentEnd.value
    // Mark as canceled and reset scoring-related data
    end.canceled = true
    end.winner = undefined
    end.points = 0
    // Clear notes to reflect interruption
    for (const entry of end.balls) {
      entry.notes = []
      entry.shotTypes = []
    }
    recomputeGlobalScore()
    // Immediately start next end
    addEndIfNeeded()
    currentEndIndex.value += 1
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
        .filter((e) => (e.canceled === true) || (e.winner && e.points))
        .map((e) => ({
          index: e.index,
          winner: (e.winner as TeamSide) ?? 'A',
          points: e.canceled ? 0 : ((e.points as number) ?? 0),
          canceled: e.canceled === true,
          balls: e.canceled ? [] : e.balls.map((b) => ({ playerId: b.playerId, notes: b.notes, shotTypes: b.shotTypes })),
        })),
    }
  }

  return {
    // state
    currentEndIndex,
    currentEnd,
    ends,
    scoreA,
    scoreB,
    isFinished,
    ballsPerPlayer,
    // actions
    goPrevEnd,
    goNextEnd,
    setNote,
    setNoteWithShot,
    setEndScore,
    // helpers
    notesOptions,
    currentEndComplete,
    colorFor,
    toSubmission,
    cancelCurrentEnd,
  }
}
