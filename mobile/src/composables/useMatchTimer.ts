import { computed, getCurrentInstance, onMounted, onUnmounted, ref, type Ref } from 'vue'

export function formatMatchTimer(ms: number): string {
  const totalSec = Math.max(0, Math.floor(ms / 1000))
  const hours = Math.floor(totalSec / 3600)
  const minutes = Math.floor((totalSec % 3600) / 60)
  const seconds = totalSec % 60
  const pad = (n: number) => String(n).padStart(2, '0')
  if (hours > 0) {
    return `${hours}:${pad(minutes)}:${pad(seconds)}`
  }
  return `${minutes}:${pad(seconds)}`
}

export function computeMatchTimerElapsedMs(
  accumulatedMs: number,
  running: boolean,
  runningSinceMs: number | null,
  nowMs: number,
): number {
  if (running && runningSinceMs !== null) {
    return accumulatedMs + Math.max(0, nowMs - runningSinceMs)
  }
  return accumulatedMs
}

export interface MatchTimerSnapshot {
  accumulatedMs: number
  running: boolean
  runningSince: number | null
}

export function useMatchTimer() {
  const running = ref(false)
  const hasStarted = ref(false)
  const accumulatedMs = ref(0)
  const startedAt = ref<number | null>(null)
  const now = ref(0)
  let tick: ReturnType<typeof setInterval> | null = null

  const elapsedMs = computed(() =>
    computeMatchTimerElapsedMs(accumulatedMs.value, running.value, startedAt.value, now.value),
  )

  const display = computed(() => formatMatchTimer(elapsedMs.value))

  function clearTick(): void {
    if (tick !== null) {
      clearInterval(tick)
      tick = null
    }
  }

  function start(): void {
    if (running.value) {
      return
    }
    hasStarted.value = true
    running.value = true
    startedAt.value = Date.now()
    now.value = startedAt.value
    clearTick()
    tick = setInterval(() => {
      now.value = Date.now()
    }, 250)
  }

  function pause(): void {
    if (!running.value) {
      return
    }
    accumulatedMs.value += Date.now() - (startedAt.value ?? Date.now())
    running.value = false
    startedAt.value = null
    clearTick()
  }

  function toggle(): void {
    if (running.value) {
      pause()
    } else {
      start()
    }
  }

  /** Starts only if the user has not already started (or paused) the timer. */
  function startIfIdle(): void {
    if (!hasStarted.value) {
      start()
    }
  }

  function getSnapshot(): MatchTimerSnapshot {
    return {
      accumulatedMs: accumulatedMs.value,
      running: running.value,
      runningSince: running.value ? startedAt.value : null,
    }
  }

  if (getCurrentInstance()) {
    onUnmounted(clearTick)
  }

  return {
    display,
    running,
    hasStarted,
    elapsedMs,
    start,
    pause,
    toggle,
    startIfIdle,
    getSnapshot,
  }
}

export function useMatchTimerFromSnapshot(
  accumulatedMs: Ref<number>,
  running: Ref<boolean>,
  runningSinceIso: Ref<string | null>,
  endAtIso?: Ref<string | null>,
) {
  const now = ref(Date.now())
  let tick: ReturnType<typeof setInterval> | null = null

  onMounted(() => {
    tick = setInterval(() => {
      now.value = Date.now()
    }, 250)
  })

  onUnmounted(() => {
    if (tick) {
      clearInterval(tick)
      tick = null
    }
  })

  const runningSinceMs = computed(() => {
    if (!runningSinceIso.value) {
      return null
    }
    const parsed = Date.parse(runningSinceIso.value)
    return Number.isNaN(parsed) ? null : parsed
  })

  const elapsedMs = computed(() => {
    let endMs = now.value
    if (endAtIso?.value) {
      const parsed = Date.parse(endAtIso.value)
      if (!Number.isNaN(parsed)) {
        endMs = parsed
      }
    }

    return computeMatchTimerElapsedMs(
      accumulatedMs.value,
      running.value,
      runningSinceMs.value,
      endMs,
    )
  })

  const display = computed(() => formatMatchTimer(elapsedMs.value))

  const visible = computed(
    () => running.value || runningSinceIso.value !== null || accumulatedMs.value > 0,
  )

  return {
    display,
    visible,
    elapsedMs,
    running,
  }
}
