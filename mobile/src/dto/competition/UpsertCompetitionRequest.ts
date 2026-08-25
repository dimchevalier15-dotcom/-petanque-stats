export interface UpsertCompetitionRequestDto {
  name: string
  eventDate: string
  country: string
  context?: string | null
}
