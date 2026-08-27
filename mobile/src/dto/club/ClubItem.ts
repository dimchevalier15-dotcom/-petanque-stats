import type { CountryItemDto } from '../country/CountryItem'

export interface ClubItemDto {
  id: number
  name: string
  description: string | null
  country: CountryItemDto
}
