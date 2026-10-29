import { computed, reactive, ref } from 'vue'
import {
  POINTING_DISTANCES,
  POINTING_WORKSHOPS,
  pointsFor,
  type PointingDistance,
  type PointingShot,
  type PointingShotResult,
  type PointingWorkshop,
} from '../models/Pointing'
import type { CompletePointingSessionRequestDto } from '../dto/pointing/CompletePointingSessionRequest'

function draftKey(sessionId: number): string {
  return `pointing_draft_${sessionId}`
}

function loadDraft(sessionId: number): PointingShot[] {
  try {
    const raw = localStorage.getItem(draftKey(sessionId))
    if (!raw) return []
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? (parsed as PointingShot[]) : []
  } catch {
    return []
  }
}

function saveDraft(sessionId: number, shots: PointingShot[]): void {
  try {
    localStorage.setItem(draftKey(sessionId), JSON.stringify(shots))
  } catch {
    // ignore
  }
}

export function clearPointingDraft(sessionId: number): void {
  try {
    localStorage.removeItem(draftKey(sessionId))
  } catch {
    // ignore
  }
}

export function usePointingSessionPlay(sessionId: number) {
  const shots = reactive<PointingShot[]>(loadDraft(sessionId))
  const currentWorkshopIndex = ref(0)

  function persist(): void {
    saveDraft(sessionId, shots)
  }

  function shotAt(workshop: PointingWorkshop, distance: PointingDistance): PointingShot | undefined {
    return shots.find((s) => s.workshop === workshop && s.distance === distance)
  }

  function setResult(workshop: PointingWorkshop, distance: PointingDistance, result: PointingShotResult): void {
    const existing = shotAt(workshop, distance)
    if (existing) {
      existing.result = result
    } else {
      shots.push({ workshop, distance, result })
    }
    persist()
  }

  function scoreOf(shot: PointingShot | undefined): number | undefined {
    return shot ? pointsFor(shot.workshop, shot.result) : undefined
  }

  function isWorkshopComplete(workshop: PointingWorkshop): boolean {
    return POINTING_DISTANCES.every((distance) => shotAt(workshop, distance) !== undefined)
  }

  function workshopScore(workshop: PointingWorkshop): number {
    return POINTING_DISTANCES.reduce((sum, distance) => sum + (scoreOf(shotAt(workshop, distance)) ?? 0), 0)
  }

  const totalScore = computed(() =>
    POINTING_WORKSHOPS.reduce((sum, workshop) => sum + workshopScore(workshop), 0),
  )

  const currentWorkshop = computed<PointingWorkshop>(() => POINTING_WORKSHOPS[currentWorkshopIndex.value])
  const currentWorkshopComplete = computed(() => isWorkshopComplete(currentWorkshop.value))
  const isLastWorkshop = computed(() => currentWorkshopIndex.value === POINTING_WORKSHOPS.length - 1)
  const isSessionComplete = computed(() => POINTING_WORKSHOPS.every((w) => isWorkshopComplete(w)))

  function goPrevWorkshop(): void {
    if (currentWorkshopIndex.value > 0) currentWorkshopIndex.value -= 1
  }

  function goNextWorkshop(): void {
    if (currentWorkshopIndex.value < POINTING_WORKSHOPS.length - 1) currentWorkshopIndex.value += 1
  }

  function toCompletionPayload(): CompletePointingSessionRequestDto {
    return {
      shots: shots.map((s) => ({ workshop: s.workshop, distance: s.distance, result: s.result })),
    }
  }

  return {
    shots,
    currentWorkshopIndex,
    currentWorkshop,
    currentWorkshopComplete,
    isLastWorkshop,
    isSessionComplete,
    totalScore,
    shotAt,
    scoreOf,
    setResult,
    workshopScore,
    isWorkshopComplete,
    goPrevWorkshop,
    goNextWorkshop,
    toCompletionPayload,
  }
}
