import { computed, reactive } from 'vue'
import { RINGS_THROWS_PER_SESSION, ringsThrowScore, type RingsTarget, type RingsThrow } from '../models/Rings'
import type { CompleteRingsSessionRequestDto } from '../dto/rings/CompleteRingsSessionRequest'

function draftKey(sessionId: number): string {
  return `rings_draft_${sessionId}`
}

function loadDraft(sessionId: number): RingsThrow[] {
  try {
    const raw = localStorage.getItem(draftKey(sessionId))
    if (!raw) return []
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? (parsed as RingsThrow[]).slice(0, RINGS_THROWS_PER_SESSION) : []
  } catch {
    return []
  }
}

function saveDraft(sessionId: number, throws: RingsThrow[]): void {
  try {
    localStorage.setItem(draftKey(sessionId), JSON.stringify(throws))
  } catch {
    // ignore
  }
}

export function clearRingsDraft(sessionId: number): void {
  try {
    localStorage.removeItem(draftKey(sessionId))
  } catch {
    // ignore
  }
}

export function useRingsSessionPlay(sessionId: number) {
  const throws = reactive<RingsThrow[]>(loadDraft(sessionId))

  const isComplete = computed(() => throws.length >= RINGS_THROWS_PER_SESSION)
  const totalScore = computed(() => throws.reduce((sum, t) => sum + ringsThrowScore(t.target, t.success), 0))
  const successCount = computed(() => throws.filter((t) => t.success).length)
  /** 1-based number of the ball about to be played, capped at the last ball. */
  const currentBall = computed(() => Math.min(throws.length + 1, RINGS_THROWS_PER_SESSION))

  function addThrow(target: RingsTarget, success: boolean): void {
    if (isComplete.value) return
    throws.push({ target, success })
    saveDraft(sessionId, throws)
  }

  function undoLast(): void {
    if (throws.length === 0) return
    throws.pop()
    saveDraft(sessionId, throws)
  }

  function toCompletionPayload(): CompleteRingsSessionRequestDto {
    return { throws: throws.map((t) => ({ target: t.target, success: t.success })) }
  }

  return {
    throws,
    isComplete,
    totalScore,
    successCount,
    currentBall,
    addThrow,
    undoLast,
    toCompletionPayload,
  }
}
