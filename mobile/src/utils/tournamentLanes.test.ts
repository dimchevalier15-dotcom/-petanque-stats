import { describe, expect, it } from 'vitest'
import { parseLaneNumbers, usableLaneNumbers } from './tournamentLanes'

describe('usableLaneNumbers', () => {
  it('extends the range to skip excluded numbers', () => {
    expect(usableLaneNumbers(12, [5, 8])).toEqual([1, 2, 3, 4, 6, 7, 9, 10, 11, 12, 13, 14])
  })
})

describe('parseLaneNumbers', () => {
  it('reads a comma separated list', () => {
    expect(parseLaneNumbers('5, 8')).toEqual([5, 8])
  })
})
