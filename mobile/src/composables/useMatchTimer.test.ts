/**
 * @vitest-environment node
 */
import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest'
import { formatMatchTimer, useMatchTimer } from './useMatchTimer'

describe('formatMatchTimer', () => {
  it('formats under a minute', () => {
    expect(formatMatchTimer(0)).toBe('0:00')
    expect(formatMatchTimer(5400)).toBe('0:05')
  })

  it('formats minutes', () => {
    expect(formatMatchTimer(65_000)).toBe('1:05')
    expect(formatMatchTimer(12 * 60_000 + 34_000)).toBe('12:34')
  })

  it('formats hours', () => {
    expect(formatMatchTimer(3661000)).toBe('1:01:01')
  })
})

describe('useMatchTimer', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-08-26T10:00:00Z'))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('starts on toggle and advances', () => {
    const timer = useMatchTimer()
    expect(timer.display.value).toBe('0:00')
    timer.toggle()
    vi.advanceTimersByTime(5000)
    expect(timer.display.value).toBe('0:05')
    expect(timer.running.value).toBe(true)
  })

  it('pauses and resumes without resetting', () => {
    const timer = useMatchTimer()
    timer.toggle()
    vi.advanceTimersByTime(3000)
    timer.toggle()
    expect(timer.running.value).toBe(false)
    expect(timer.display.value).toBe('0:03')
    vi.advanceTimersByTime(10_000)
    expect(timer.display.value).toBe('0:03')
    timer.toggle()
    vi.advanceTimersByTime(2000)
    expect(timer.display.value).toBe('0:05')
  })

  it('startIfIdle only starts once', () => {
    const timer = useMatchTimer()
    timer.startIfIdle()
    vi.advanceTimersByTime(2000)
    timer.pause()
    timer.startIfIdle()
    expect(timer.running.value).toBe(false)
    expect(timer.display.value).toBe('0:02')
  })
})
