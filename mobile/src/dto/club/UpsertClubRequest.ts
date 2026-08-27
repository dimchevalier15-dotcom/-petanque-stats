export interface UpsertClubRequestDto {
  name: string
  countryId: number
  description?: string | null
}
