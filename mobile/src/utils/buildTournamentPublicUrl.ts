import { getPublicAppBaseUrl } from './getPublicAppBaseUrl'

export function buildTournamentPublicUrl(publicUuid: string): string {
  return `${getPublicAppBaseUrl()}/tournaments/p/${publicUuid}`
}
