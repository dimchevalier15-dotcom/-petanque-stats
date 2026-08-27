export interface RegisterRequest {
  email: string
  password: string
  firstName?: string
  lastName?: string
  nickname?: string
  playerId?: number
  clubId?: number | null
}
