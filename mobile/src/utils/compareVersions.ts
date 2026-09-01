/**
 * Compare two semver-like strings (major.minor.patch).
 * Returns negative if a < b, positive if a > b, 0 if equal.
 */
export function compareVersions(a: string, b: string): number {
  const partsA = a.split('.').map((part) => Number.parseInt(part, 10) || 0)
  const partsB = b.split('.').map((part) => Number.parseInt(part, 10) || 0)
  const length = Math.max(partsA.length, partsB.length)

  for (let index = 0; index < length; index += 1) {
    const valueA = partsA[index] ?? 0
    const valueB = partsB[index] ?? 0
    if (valueA < valueB) {
      return -1
    }
    if (valueA > valueB) {
      return 1
    }
  }

  return 0
}

export function isVersionLowerThan(installed: string, target: string): boolean {
  return compareVersions(installed, target) < 0
}
