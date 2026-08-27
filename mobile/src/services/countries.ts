import api from './http'
import type { CountryItemDto } from '../dto/country/CountryItem'
import type { Country } from '../models/Country'

function mapCountry(dto: CountryItemDto): Country {
  return {
    id: dto.id,
    isoCode: dto.isoCode,
    name: dto.name,
  }
}

export const countriesService = {
  async list(): Promise<Country[]> {
    const { data } = await api.get<CountryItemDto[]>('/countries')
    return data.map(mapCountry)
  },
}
