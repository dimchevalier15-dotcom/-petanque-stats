/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import { LEGAL_PATHS, PUBLIC_ROUTE_NAMES } from './publicRoutes'

describe('public legal routes', () => {
  it('registers privacy, terms, legal and delete-account as public paths', () => {
    expect(LEGAL_PATHS.privacy).toBe('/privacy')
    expect(LEGAL_PATHS.terms).toBe('/terms')
    expect(LEGAL_PATHS.legal).toBe('/legal')
    expect(LEGAL_PATHS.deleteAccount).toBe('/delete-account')
  })

  it('allows unauthenticated access to legal pages', () => {
    expect(PUBLIC_ROUTE_NAMES).toEqual(
      expect.arrayContaining(['privacy', 'terms', 'legal', 'deleteAccount']),
    )
  })

  it('allows unauthenticated access to guest match routes', () => {
    expect(PUBLIC_ROUTE_NAMES).toEqual(
      expect.arrayContaining(['newMatch', 'matchScore', 'guestMatchSummary']),
    )
  })

  it('does not treat legal pages as guest-only', async () => {
    const { GUEST_ONLY_ROUTE_NAMES } = await import('./publicRoutes')
    expect(GUEST_ONLY_ROUTE_NAMES).not.toContain('privacy')
    expect(GUEST_ONLY_ROUTE_NAMES).not.toContain('terms')
    expect(GUEST_ONLY_ROUTE_NAMES).not.toContain('legal')
    expect(GUEST_ONLY_ROUTE_NAMES).not.toContain('deleteAccount')
  })
})
