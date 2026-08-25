export interface Competition {
  id: number
  name: string
  eventDate: string
  country: string
  context: string | null
}

export const COMPETITION_OTHER_VALUE = 'other' as const

export type CompetitionSelection = number | typeof COMPETITION_OTHER_VALUE | null

export function competitionLabel(competition: Competition): string {
  const year = competition.eventDate.slice(0, 4)
  return `${competition.name} - ${year}`
}
