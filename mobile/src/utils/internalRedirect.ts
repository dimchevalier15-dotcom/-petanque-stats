/** Accepts only same-origin path redirects (no protocol-relative or external URLs). */
export function isSafeInternalPath(value: unknown): value is string {
  if (typeof value !== 'string') {
    return false
  }
  if (!value.startsWith('/')) {
    return false
  }
  if (value.startsWith('//') || value.startsWith('/\\')) {
    return false
  }
  if (value.includes('://') || value.includes('\\')) {
    return false
  }
  return true
}
