import axios from 'axios'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import type { LiveMatchData } from '../models/LiveMatch'
import type { EndRecord } from '../models/MatchPlay'
import { liveMatchesService } from '../services/liveMatches'
import { playerShotCount, noteAt as noteAtShot } from '../utils/matchEndShots'
import { matchScore } from '../utils/matchScore'
import { teamSlotsForEnd, type TeamSlotDisplay } from '../utils/matchSubstitutions'

const DEFAULT_POLL_INTERVAL_MS = 5000

function remainingBallsForTeam(slots: TeamSlotDisplay[], end: EndRecord, ballsPerPlayer: number): number {
  return slots.reduce(
    (sum, slot) => sum + Math.max(0, ballsPerPlayer - playerShotCount(end, slot.activePlayerId)),
    0,
  )
}

function teamBallStates(slots: TeamSlotDisplay[], end: EndRecord, ballsPerPlayer: number): boolean[] {
  const states: boolean[] = []
  for (const slot of slots) {
    for (let i = 0; i < ballsPerPlayer; i++) {
      states.push(noteAtShot(end, slot.activePlayerId, i) === undefined)
    }
  }
  return states
}

export function useLiveMatchPolling(uuid: string, pollIntervalMs = DEFAULT_POLL_INTERVAL_MS) {
  const loading = ref(true)
  const notFound = ref(false)
  const matchStatus = ref<'active' | 'finished' | null>(null)
  const matchData = ref<LiveMatchData | null>(null)
  const lastUpdatedAt = ref<string | null>(null)
  const finishedAt = ref<string | null>(null)
  const timerAccumulatedMs = ref(0)
  const timerRunning = ref(false)
  const timerRunningSince = ref<string | null>(null)
  let pollTimer: ReturnType<typeof setInterval> | null = null

  const isLiveActive = computed(() => matchStatus.value === 'active')
  const isFinished = computed(() => matchStatus.value === 'finished')
  const ballsPerPlayer = computed(() => (matchData.value?.type === 'triplette' ? 2 : 3))
  const substitutions = computed(() => matchData.value?.substitutions ?? [])

  const currentEnd = computed<EndRecord>(() => {
    const ends = matchData.value?.ends ?? []
    const index = matchData.value?.currentEndIndex ?? 0
    return ends[index] ?? { index: 1, shots: [] }
  })

  const teamALabel = computed(() => matchData.value?.teamALabel ?? '')
  const teamBLabel = computed(() => matchData.value?.teamBLabel ?? '')

  const teamASlots = computed(() => {
    if (!matchData.value) return []
    return teamSlotsForEnd(matchData.value.teamA, 'A', substitutions.value, currentEnd.value.index)
  })

  const teamBSlots = computed(() => {
    if (!matchData.value) return []
    return teamSlotsForEnd(matchData.value.teamB, 'B', substitutions.value, currentEnd.value.index)
  })

  const scoreA = computed(
    () =>
      matchScore(
        matchData.value?.ends ?? [],
        matchData.value?.openingScoreA ?? 0,
        matchData.value?.openingScoreB ?? 0,
      ).scoreA,
  )

  const scoreB = computed(
    () =>
      matchScore(
        matchData.value?.ends ?? [],
        matchData.value?.openingScoreA ?? 0,
        matchData.value?.openingScoreB ?? 0,
      ).scoreB,
  )

  const remainingBallsA = computed(() =>
    remainingBallsForTeam(teamASlots.value, currentEnd.value, ballsPerPlayer.value),
  )

  const remainingBallsB = computed(() =>
    remainingBallsForTeam(teamBSlots.value, currentEnd.value, ballsPerPlayer.value),
  )

  const teamABallStates = computed(() =>
    teamBallStates(teamASlots.value, currentEnd.value, ballsPerPlayer.value),
  )

  const teamBBallStates = computed(() =>
    teamBallStates(teamBSlots.value, currentEnd.value, ballsPerPlayer.value),
  )

  const winnerSide = computed<'A' | 'B' | null>(() => {
    if (scoreA.value === scoreB.value) return null
    return scoreA.value > scoreB.value ? 'A' : 'B'
  })

  function stopPolling(): void {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  }

  function startPolling(): void {
    stopPolling()
    pollTimer = setInterval(() => {
      void fetchLiveMatch()
    }, pollIntervalMs)
  }

  async function fetchLiveMatch(): Promise<void> {
    try {
      const response = await liveMatchesService.getPublic(uuid)
      matchData.value = response.data
      matchStatus.value = response.status
      lastUpdatedAt.value = response.updatedAt
      timerAccumulatedMs.value = response.timerAccumulatedMs ?? 0
      timerRunning.value = response.timerRunning ?? false
      timerRunningSince.value = response.timerRunningSince
      finishedAt.value = response.finishedAt
      notFound.value = false

      if (response.status === 'finished') {
        stopPolling()
      }
    } catch (error) {
      if (axios.isAxiosError(error) && error.response?.status === 404) {
        notFound.value = true
        matchData.value = null
        matchStatus.value = null
        timerAccumulatedMs.value = 0
        timerRunning.value = false
        timerRunningSince.value = null
        finishedAt.value = null
        stopPolling()
      }
    } finally {
      loading.value = false
    }
  }

  onMounted(async () => {
    await fetchLiveMatch()
    if (isLiveActive.value) {
      startPolling()
    }
  })

  onUnmounted(() => {
    stopPolling()
  })

  return {
    loading,
    notFound,
    matchStatus,
    matchData,
    lastUpdatedAt,
    finishedAt,
    timerAccumulatedMs,
    timerRunning,
    timerRunningSince,
    isLiveActive,
    isFinished,
    ballsPerPlayer,
    currentEnd,
    teamALabel,
    teamBLabel,
    scoreA,
    scoreB,
    remainingBallsA,
    remainingBallsB,
    teamABallStates,
    teamBBallStates,
    winnerSide,
    fetchLiveMatch,
  }
}
