import { getPublicAppBaseUrl } from './getPublicAppBaseUrl'

export function buildSharedMatchUrl(uuid: string): string {
  return `${getPublicAppBaseUrl()}/recap/${uuid}`
}
