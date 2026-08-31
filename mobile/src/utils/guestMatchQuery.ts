export const SAVE_GUEST_MATCH_QUERY = 'saveGuestMatch'

export function hasSaveGuestMatchQuery(value: unknown): boolean {
  return value === '1' || value === 'true'
}

export function saveGuestMatchQuery(): Record<string, string> {
  return { [SAVE_GUEST_MATCH_QUERY]: '1' }
}
