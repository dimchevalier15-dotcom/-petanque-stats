export function buildSharedMatchUrl(uuid: string): string {
  return `${window.location.origin}/recap/${uuid}`
}
