import { computed, reactive, ref } from 'vue'
import {
  SHOOTING_DISTANCES,
  SHOOTING_WORKSHOPS,
  pointsFor,
  type ShootingDistance,
  type ShootingShot,
  type ShootingShotResult,
  type ShootingWorkshop,
} from '../models/Shooting'
import type { CompleteShootingSessionRequestDto } from '../dto/shooting/CompleteShootingSessionRequest'

function draftKey(sessionId: number): string {
  return `shooting_draft_${sessionId}`
}

/**
 * Local-only safety net: the 20 shots are only sent to the backend once the
 * session is completed (like matches), so we keep a draft in localStorage to
 * avoid losing entered shots if the app is closed or loses connectivity mid
 * session. The draft is cleared once the session is completed or abandoned.
 */
function loadDraft(sessionId: number): ShootingShot[] {
  try {
    const raw = localStorage.getItem(draftKey(sessionId))
    if (!raw) return []
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? (parsed as ShootingShot[]) : []
  } catch {
    return []
  }
}

function saveDraft(sessionId: number, shots: ShootingShot[]): void {
  try {
    localStorage.setItem(draftKey(sessionId), JSON.stringify(shots))
  } catch {
    // Storage unavailable (e.g. private mode): the session still works,
    // it just won't survive an accidental app close.
  }
}

export function clearShootingDraft(sessionId: number): void {
  try {
    localStorage.removeItem(draftKey(sessionId))
  } catch {
    // ignore
  }
}

export function useShootingSessionPlay(sessionId: number) {
  const shots = reactive<ShootingShot[]>(loadDraft(sessionId))
  const currentWorkshopIndex = ref(0)

  function persist(): void {
    saveDraft(sessionId, shots)
  }

  function shotAt(workshop: ShootingWorkshop, distance: ShootingDistance): ShootingShot | undefined {
    return shots.find((s) => s.workshop === workshop && s.distance === distance)
  }

  function setResult(workshop: ShootingWorkshop, distance: ShootingDistance, result: ShootingShotResult): void {
    const existing = shotAt(workshop, distance)
    if (existing) {
      existing.result = result
    } else {
      shots.push({ workshop, distance, result })
    }
    persist()
  }

  function scoreOf(shot: ShootingShot | undefined): number | undefined {
    return shot ? pointsFor(shot.workshop, shot.result) : undefined
  }

  function isWorkshopComplete(workshop: ShootingWorkshop): boolean {
    return SHOOTING_DISTANCES.every((distance) => shotAt(workshop, distance) !== undefined)
  }

  function workshopScore(workshop: ShootingWorkshop): number {
    return SHOOTING_DISTANCES.reduce((sum, distance) => sum + (scoreOf(shotAt(workshop, distance)) ?? 0), 0)
  }

  const totalScore = computed(() =>
    SHOOTING_WORKSHOPS.reduce((sum, workshop) => sum + workshopScore(workshop), 0),
  )

  const currentWorkshop = computed<ShootingWorkshop>(() => SHOOTING_WORKSHOPS[currentWorkshopIndex.value])
  const currentWorkshopComplete = computed(() => isWorkshopComplete(currentWorkshop.value))
  const isLastWorkshop = computed(() => currentWorkshopIndex.value === SHOOTING_WORKSHOPS.length - 1)
  const isSessionComplete = computed(() => SHOOTING_WORKSHOPS.every((w) => isWorkshopComplete(w)))

  function goPrevWorkshop(): void {
    if (currentWorkshopIndex.value > 0) currentWorkshopIndex.value -= 1
  }

  function goNextWorkshop(): void {
    if (currentWorkshopIndex.value < SHOOTING_WORKSHOPS.length - 1) currentWorkshopIndex.value += 1
  }

  function toCompletionPayload(): CompleteShootingSessionRequestDto {
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
