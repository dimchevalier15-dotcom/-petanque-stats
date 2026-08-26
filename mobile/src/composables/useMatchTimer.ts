import { computed, getCurrentInstance, onUnmounted, ref } from 'vue'

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

export function useMatchTimer() {
  const running = ref(false)
  const hasStarted = ref(false)
  const accumulatedMs = ref(0)
  const startedAt = ref<number | null>(null)
  const now = ref(0)
  let tick: ReturnType<typeof setInterval> | null = null

  const elapsedMs = computed(() => {
    if (running.value && startedAt.value !== null) {
      return accumulatedMs.value + (now.value - startedAt.value)
    }
    return accumulatedMs.value
  })

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
  }
}
