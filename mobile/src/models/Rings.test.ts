/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import { RINGS_MAX_SESSION_SCORE, ringsMaxSessionScore, ringsTargetBreakdown, ringsThrowScore } from './Rings'

describe('Rings scoring', () => {
  it('scores the aimed ring points only on success', () => {
    expect(ringsThrowScore('near', true)).toBe(1)
    expect(ringsThrowScore('middle', true)).toBe(3)
    expect(ringsThrowScore('far', true)).toBe(5)
    expect(ringsThrowScore('far', false)).toBe(0)
  })

  it('caps a session at 15 successful far balls', () => {
    expect(RINGS_MAX_SESSION_SCORE).toBe(75)
  })

  it('breaks throws down by ring, farthest first', () => {
    const breakdown = ringsTargetBreakdown([
      { target: 'far', success: true },
      { target: 'far', success: false },
      { target: 'near', success: true },
    ])

    expect(breakdown).toEqual([
      { target: 'far', attempts: 2, successes: 1, carreaux: 0, points: 5 },
      { target: 'middle', attempts: 0, successes: 0, carreaux: 0, points: 0 },
      { target: 'near', attempts: 1, successes: 1, carreaux: 0, points: 1 },
    ])
  })

  it('doubles the points of a carreau', () => {
    expect(ringsThrowScore('near', true, true)).toBe(2)
    expect(ringsThrowScore('middle', true, true)).toBe(6)
    expect(ringsThrowScore('far', true, true)).toBe(10)
    expect(ringsThrowScore('far', false, true)).toBe(0)
  })

  it('caps a shooting session at 15 far carreaux', () => {
    expect(ringsMaxSessionScore('point')).toBe(75)
    expect(ringsMaxSessionScore('shooting')).toBe(150)
  })

  it('counts carreaux and their doubled points in the breakdown', () => {
    const [far] = ringsTargetBreakdown([
      { target: 'far', success: true, carreau: true },
      { target: 'far', success: true },
    ])

    expect(far).toEqual({ target: 'far', attempts: 2, successes: 2, carreaux: 1, points: 15 })
  })
})
