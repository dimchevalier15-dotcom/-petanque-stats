export interface CreatePlayerResponseDto {
  id: number
  firstName: string
  lastName: string
  nickname: string
  clubId?: number | null
  clubName?: string | null
}
