import type { Country } from './Country'

export interface Club {
  id: number
  name: string
  description: string | null
  country: Country
}

export function clubLabel(club: Club): string {
  return `${club.name} — ${club.country.name}`
}
